(function () {
  "use strict";

  var body = document.body;
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("site-nav");

  function setNav(open) {
    body.classList.toggle("nav-open", open);
    if (toggle) {
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      var labels = window.liferussTheme && window.liferussTheme.strings ? window.liferussTheme.strings : {};
      toggle.setAttribute("aria-label", open ? (labels.closeMenu || "Close menu") : (labels.openMenu || "Open menu"));
    }
  }

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      setNav(!body.classList.contains("nav-open"));
    });

    nav.addEventListener("click", function (event) {
      if (event.target.closest("a")) {
        setNav(false);
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        setNav(false);
      }
    });
  }

  document.querySelectorAll(".js-scroll-consult").forEach(function (link) {
    link.addEventListener("click", function (event) {
      var target = document.getElementById("consultation");
      if (target) {
        event.preventDefault();
        setNav(false);
        target.scrollIntoView({ behavior: "smooth", block: "start" });
        var first = target.querySelector("input[name='consult_name']");
        if (first) {
          first.focus({ preventScroll: true });
        }
      }
    });
  });

  function initSlider(root) {
    var track = root.querySelector("[data-slider-track]");
    if (!track) {
      return;
    }
    var cards = Array.prototype.slice.call(track.children);
    if (!cards.length) {
      return;
    }

    var index = 0;

    function perView() {
      if (window.innerWidth < 640) {
        return 1;
      }
      if (window.innerWidth < 1100) {
        return 2;
      }
      return 4;
    }

    function maxIndex() {
      return Math.max(0, cards.length - perView());
    }

    function apply() {
      index = Math.min(index, maxIndex());
      var card = cards[0];
      var styles = window.getComputedStyle(track);
      var gap = parseFloat(styles.columnGap || styles.gap) || 18;
      var width = card.getBoundingClientRect().width + gap;
      var rtl = document.documentElement.getAttribute("dir") !== "ltr";
      var sign = rtl ? 1 : -1;
      track.style.transform = "translateX(" + (sign * index * width) + "px)";
    }

    var prev = document.querySelector("[data-slider-prev]");
    var next = document.querySelector("[data-slider-next]");

    if (prev) {
      prev.addEventListener("click", function () {
        index = index <= 0 ? maxIndex() : index - 1;
        apply();
      });
    }
    if (next) {
      next.addEventListener("click", function () {
        index = index >= maxIndex() ? 0 : index + 1;
        apply();
      });
    }

    var startX = 0;
    var delta = 0;
    root.addEventListener("touchstart", function (event) {
      startX = event.changedTouches[0].clientX;
      delta = 0;
    }, { passive: true });
    root.addEventListener("touchmove", function (event) {
      delta = event.changedTouches[0].clientX - startX;
    }, { passive: true });
    root.addEventListener("touchend", function () {
      if (Math.abs(delta) < 40) {
        return;
      }
      var rtl = document.documentElement.getAttribute("dir") !== "ltr";
      var goingNext = rtl ? delta > 0 : delta < 0;
      if (goingNext) {
        index = index >= maxIndex() ? 0 : index + 1;
      } else {
        index = index <= 0 ? maxIndex() : index - 1;
      }
      apply();
    });

    window.addEventListener("resize", apply);
    apply();
  }

  document.querySelectorAll("[data-slider]").forEach(initSlider);

  document.querySelectorAll(".lang-switch a").forEach(function (link) {
    link.addEventListener("click", function () {
      if (window.location.hash) {
        link.href = link.href.split("#")[0] + window.location.hash;
      }
    });
  });

  if (typeof window.liferussTheme !== "undefined") {
    document.querySelectorAll(".consult-form").forEach(function (form) {
      var status = form.parentElement ? form.parentElement.querySelector(".form-status") : document.querySelector(".form-status");

      function showStatus(ok, message) {
        if (!status) {
          return;
        }
        status.textContent = message;
        status.classList.add("is-visible");
        status.classList.toggle("is-ok", ok);
        status.classList.toggle("is-err", !ok);
      }

      form.addEventListener("submit", function (event) {
        event.preventDefault();
        var data = new FormData(form);
        data.set("action", "liferuss_consult");
        if (!data.get("liferuss_nonce")) {
          data.set("liferuss_nonce", window.liferussTheme.nonce);
        }

        var button = form.querySelector("button[type='submit']");
        if (button) {
          button.disabled = true;
        }

        fetch(window.liferussTheme.ajaxUrl, {
          method: "POST",
          credentials: "same-origin",
          body: data
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (json) {
            var payload = json.data || {};
            var ok = Boolean(json.success);
            var labels = window.liferussTheme.strings || {};
            showStatus(ok, payload.message || (ok ? (labels.formOk || "OK") : (labels.formErr || "Error")));
            if (ok) {
              form.reset();
            }
          })
          .catch(function () {
            var labels = window.liferussTheme.strings || {};
            showStatus(false, labels.formNet || "Network error");
          })
          .finally(function () {
            if (button) {
              button.disabled = false;
            }
          });
      });
    });
  }
})();
