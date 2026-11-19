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
    var bits = [];
    if (from.source) {
      bits.push(from.source);
    }
    if (to.source && to.source !== from.source) {
      bits.push(to.source);
    }
    if (from.age) {
      bits.push(from.age);
    }
    if (from.state === "stale" || to.state === "stale") {
      bits.push(data.labels.stale);
    }
    meta.textContent = bits.join(" · ");
  }

  form.addEventListener("input", paint);
  form.addEventListener("change", paint);
  paint();
})();
