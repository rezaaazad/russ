(function () {
  if (!window.lrRatesLive || !lrRatesLive.url) {
    return;
  }
  if (!document.querySelector("[data-lr-code], [data-lr-converter]")) {
    return;
  }

  function digits(text) {
    var map = "۰۱۲۳۴۵۶۷۸۹";
    return String(text).replace(/[0-9]/g, function (d) {
      return map.charAt(Number(d));
    });
  }

  function amount(value, unit) {
    if (value === null || value === undefined || value === "" || !isFinite(Number(value))) {
      return "—";
    }
    var n = Number(value);
    var cash = unit === "تومان" || unit === "ریال";
    var text = cash ? Math.round(n).toLocaleString("en-US") : String(Math.round(n * 10000) / 10000);
    return digits(text) + (unit ? " " + unit : "");
  }

  function paint(board) {
    if (!board || !board.items) {
      return;
    }
    var by = {};
    board.items.forEach(function (item) {
      if (item && item.code) {
        by[item.code] = item;
      }
    });
    document.querySelectorAll("[data-lr-code]").forEach(function (node) {
      var item = by[node.getAttribute("data-lr-code")];
      if (!item) {
        return;
      }
      var unit = node.getAttribute("data-lr-unit") || item.unit || "";
      ["value", "buy", "sell"].forEach(function (field) {
        var cell = node.querySelector('[data-lr-field="' + field + '"]');
        if (cell) {
          cell.textContent = amount(item[field], unit);
        }
      });
      var figure = node.querySelector('[data-lr-field="figure"]');
      if (figure) {
        var shown = item.value;
        if (shown === null || shown === undefined || shown === "") {
          shown = item.sell;
        }
        figure.textContent = amount(shown, unit);
      }
      var source = node.querySelector('[data-lr-field="source"]');
      if (source) {
        source.textContent = item.source || "";
      }
      var age = node.querySelector('[data-lr-field="age-text"]');
      if (age && item.ageText) {
        age.textContent = item.ageText;
      }
      var clock = node.querySelector('[data-lr-field="clock"]');
      if (clock) {
        clock.textContent = item.atText || "";
      }
      var stale = item.state === "stale";
      node.classList.toggle("is-stale", stale);
      var badge = node.querySelector(".lr-rates__badge");
      if (badge) {
        badge.hidden = !stale;
      }
    });
    window.lrRatesBoard = board;
    document.dispatchEvent(new CustomEvent("lr-rates", { detail: board }));
  }

  fetch(lrRatesLive.url, { credentials: "same-origin", cache: "no-store" })
    .then(function (response) {
      if (!response.ok) {
        throw new Error("rates");
      }
      return response.json();
    })
    .then(paint)
    .catch(function () {
      /* Keep the server-rendered figures. */
    });
})();
