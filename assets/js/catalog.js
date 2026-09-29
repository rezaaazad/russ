(function () {
  var form = document.getElementById("lr-uni-filters");
  var box = document.getElementById("lr-catalog-results");
  if (!form || !box || !window.liferussCatalog) {
    return;
  }

  function esc(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function money(value) {
    var amount = parseFloat(value);
    if (!amount || amount <= 0) {
      return "";
    }
    return "$" + Math.round(amount).toLocaleString("fa-IR");
  }

  function card(item) {
    var bits = [];
    var usd = money(item.min_tuition_usd);
    if (usd) {
      bits.push("از " + usd);
    }
    if (item.best_world_rank) {
      bits.push("رتبه " + item.best_world_rank);
    }
    if (item.health === "approved" || item.science === "approved") {
      bits.push("تأیید وزارتخانه");
    }
    var href = esc(item.url);
    var image = item.thumbnail
      ? '<a href="' + href + '"><img src="' + esc(item.thumbnail) + '" alt=""></a>'
      : "";
    return (
      '<article class="lr-card">' +
      image +
      '<div class="lr-card-body"><h2><a href="' +
      href +
      '">' +
      esc(item.name) +
      "</a></h2>" +
      (item.city ? '<p class="lr-meta">' + esc(item.city) + "</p>" : "") +
      (bits.length ? '<p class="lr-meta">' + esc(bits.join(" · ")) + "</p>" : "") +
      "</div></article>"
    );
  }

  function load(params) {
    var url = new URL(liferussCatalog.endpoint, window.location.origin);
    params.forEach(function (value, key) {
      if (value) {
        url.searchParams.set(key, value);
      }
    });
    if (!url.searchParams.get("page")) {
      url.searchParams.set("page", "1");
    }
    box.setAttribute("aria-busy", "true");
    fetch(url.toString(), { headers: { Accept: "application/json" } })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("catalog");
        }
        return response.json();
      })
      .then(function (data) {
        var html = data.items && data.items.length
          ? data.items.map(card).join("")
          : '<p class="lr-empty">موردی با این فیلتر پیدا نشد.</p>';
        box.innerHTML = html;
        var count = document.querySelector(".lr-count");
        if (count) {
          count.textContent = data.total + " دانشگاه";
        }
        var next = new URL(window.location.href);
        next.search = "";
        params.forEach(function (value, key) {
          if (value && key !== "page") {
            next.searchParams.set(key, value);
          }
        });
        window.history.pushState({}, "", next.toString());
      })
      .catch(function () {
        window.location.search = params.toString();
      })
      .finally(function () {
        box.removeAttribute("aria-busy");
      });
  }

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    load(new URLSearchParams(new FormData(form)));
  });
})();
