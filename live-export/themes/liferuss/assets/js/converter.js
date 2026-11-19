(function () {
  var root = document.querySelector("[data-lr-converter]");
  if (!root) {
    return;
  }
  var raw = root.getAttribute("data-quotes");
  if (!raw) {
    return;
  }
  var data;
  try {
    data = JSON.parse(raw);
  } catch (error) {
    return;
  }
  var form = root.querySelector("form");
  var result = root.querySelector(".lr-conv__result");
  var meta = root.querySelector(".lr-conv__meta");
  if (!form || !result || !data.quotes) {
    return;
  }

  function digits(text) {
    var map = "۰۱۲۳۴۵۶۷۸۹";
    return String(text).replace(/[0-9]/g, function (d) {
      return map.charAt(Number(d));
    });
  }

  function format(value) {
    var abs = Math.abs(value);
    var text;
    if (abs >= 100) {
      text = Math.round(value).toLocaleString("en-US");
    } else {
      text = String(Math.round(value * 10000) / 10000);
    }
    return digits(text);
  }

  function paint() {
    var amount = Number(form.amount.value);
    var from = data.quotes[form.from.value];
    var to = data.quotes[form.to.value];
    if (!isFinite(amount) || amount < 0 || !from || !to) {
      result.textContent = "";
      meta.textContent = "";
      return;
    }
    if (!(from.toman > 0) || !(to.toman > 0)) {
      result.textContent = data.labels.missing;
      meta.textContent = "";
      return;
    }
    if (form.from.value === form.to.value) {
      result.textContent = data.labels.same;
      meta.textContent = "";
      return;
    }
    var value = amount * (from.toman / to.toman);
    result.textContent = data.labels.result + " " + format(value);
    meta.textContent = metaLine(form.from.value, form.to.value);
  }

  function metaLine(fromCode, toCode) {
    var sides = [];
    [fromCode, toCode].forEach(function (code) {
      if (code === "IRR") {
        return;
      }
      var quote = data.quotes[code];
      if (!quote || sides.some(function (side) { return side.code === code; })) {
        return;
      }
      sides.push({
        code: code,
        source: quote.source || "",
        age: quote.age || "",
        ageMs: Number(quote.ageMs) || 0,
        state: quote.state || ""
      });
    });
    if (!sides.length) {
      return "";
    }
    var sources = [];
    var oldest = sides[0];
    var stale = false;
    sides.forEach(function (side) {
      if (side.source && sources.indexOf(side.source) === -1) {
        sources.push(side.source);
      }
      if (side.ageMs >= oldest.ageMs) {
        oldest = side;
      }
      if (side.state === "stale") {
        stale = true;
      }
    });
    var bits = sources.slice();
    if (oldest.age) {
      bits.push(oldest.age);
    }
    if (stale) {
      bits.push(data.labels.stale);
    }
    return bits.join(" · ");
  }

  function applyBoard(board) {
    if (!board || !board.items) {
      return;
    }
    board.items.forEach(function (item) {
      var quote = data.quotes[item.code];
      if (!quote || item.unit !== "تومان" || !(item.value > 0)) {
        return;
      }
      quote.toman = item.value;
      quote.source = item.source || "";
      quote.state = item.state || "";
      quote.age = item.ageText || "";
      quote.ageMs = Number(item.ageMs) || 0;
    });
    paint();
  }

  form.addEventListener("input", paint);
  form.addEventListener("change", paint);
  document.addEventListener("lr-rates", function (event) {
    applyBoard(event.detail);
  });
  if (window.lrRatesBoard) {
    applyBoard(window.lrRatesBoard);
  }
  paint();
})();
