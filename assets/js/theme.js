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
    var gapCache = 0;
    var resizeRaf = 0;

    function perView() {
      var width = window.innerWidth;
      if (width < 640) {
        return 1;
      }
      if (width < 1100) {
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
      if (!gapCache) {
        var styles = window.getComputedStyle(track);
        gapCache = parseFloat(styles.columnGap || styles.gap) || 18;
      }
      var width = card.getBoundingClientRect().width + gapCache;
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

    window.addEventListener("resize", function () {
      gapCache = 0;
      if (resizeRaf) {
        window.cancelAnimationFrame(resizeRaf);
      }
      resizeRaf = window.requestAnimationFrame(apply);
    });

    if ("IntersectionObserver" in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            apply();
            io.disconnect();
          }
        });
      }, { rootMargin: "200px 0px" });
      io.observe(root);
    } else {
      apply();
    }
  }

  document.querySelectorAll("[data-slider]").forEach(initSlider);

  function initFloatWidget() {
    var root = document.querySelector("[data-float-widget]");
    if (!root) {
      return;
    }
    var fab = root.querySelector("[data-float-toggle]");
    var backdrop = root.querySelector("[data-float-backdrop]");
    var panel = root.querySelector("[data-float-panel]");
    var labels = window.liferussTheme && window.liferussTheme.strings ? window.liferussTheme.strings : {};

    function focusables() {
      if (!panel) {
        return [];
      }
      return Array.prototype.slice.call(panel.querySelectorAll("a, button, [tabindex]:not([tabindex='-1'])"));
    }

    function setOpen(open) {
      root.classList.toggle("is-open", open);
      document.body.classList.toggle("float-open", open);
      if (fab) {
        fab.setAttribute("aria-expanded", open ? "true" : "false");
        fab.setAttribute("aria-label", open ? (labels.closeFloat || "Close") : (labels.openFloat || "Open"));
      }
      if (panel) {
        panel.setAttribute("aria-hidden", open ? "false" : "true");
        if (open) {
          panel.removeAttribute("hidden");
        } else {
          panel.setAttribute("hidden", "");
        }
      }
      if (backdrop) {
        if (open) {
          backdrop.removeAttribute("hidden");
        } else {
          backdrop.setAttribute("hidden", "");
        }
      }
      if (open) {
        var first = focusables()[0];
        if (first) {
          first.focus();
        }
      }
    }

    if (fab) {
      fab.addEventListener("click", function () {
        setOpen(!root.classList.contains("is-open"));
      });
    }
    if (backdrop) {
      backdrop.addEventListener("click", function () {
        setOpen(false);
        if (fab) {
          fab.focus();
        }
      });
    }

    document.addEventListener("keydown", function (event) {
      if (!root.classList.contains("is-open")) {
        return;
      }
      if (event.key === "Escape") {
        setOpen(false);
        if (fab) {
          fab.focus();
        }
        return;
      }
      if (event.key !== "Tab") {
        return;
      }
      var items = focusables();
      if (!items.length) {
        return;
      }
      items.push(fab);
      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    root.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        var href = link.getAttribute("href") || "";
        if (link.classList.contains("js-scroll-consult") || href.charAt(0) === "#" || href.indexOf("tel:") === 0) {
          setOpen(false);
        }
      });
    });
  }

  initFloatWidget();

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
