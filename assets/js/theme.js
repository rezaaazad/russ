function latinDigits(value) {
  var fa = "۰۱۲۳۴۵۶۷۸۹";
  var ar = "٠١٢٣٤٥٦٧٨٩";
  return String(value).replace(/[۰-۹]/g, function (digit) {
    return String(fa.indexOf(digit));
  }).replace(/[٠-٩]/g, function (digit) {
    return String(ar.indexOf(digit));
  });
}

(function () {
  "use strict";

  var body = document.body;
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("site-nav");
  var searchToggle = document.querySelector(".header-search-toggle");
  var searchClose = document.querySelector(".header-search-close");
  var searchBox = document.getElementById("header-search");

  function setSearch(open) {
    if (!searchBox) {
      return;
    }
    searchBox.classList.toggle("is-open", open);
    body.classList.toggle("search-open", open);
    if (searchToggle) {
      searchToggle.setAttribute("aria-expanded", open ? "true" : "false");
    }
    if (open) {
      var input = searchBox.querySelector("input");
      if (input) {
        input.focus();
      }
    }
  }

  function setNav(open) {
    body.classList.toggle("nav-open", open);
    if (open) {
      setSearch(false);
    }
    if (toggle) {
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      var labels = window.liferussTheme && window.liferussTheme.strings ? window.liferussTheme.strings : {};
      toggle.setAttribute("aria-label", open ? (labels.closeMenu || "Close menu") : (labels.openMenu || "Open menu"));
    }
  }

  if (searchToggle && searchBox) {
    searchToggle.addEventListener("click", function () {
      setSearch(!searchBox.classList.contains("is-open"));
    });
  }
  if (searchClose) {
    searchClose.addEventListener("click", function () {
      setSearch(false);
      if (searchToggle) {
        searchToggle.focus();
      }
    });
  }

  document.querySelectorAll(".submenu-toggle").forEach(function (button) {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      var item = button.closest(".menu-item");
      if (!item) {
        return;
      }
      var open = !item.classList.contains("is-open");
      var parent = item.parentElement;
      if (parent && open) {
        parent.querySelectorAll(".menu-item.is-open").forEach(function (sibling) {
          if (sibling !== item) {
            sibling.classList.remove("is-open");
            var toggle = sibling.querySelector(".submenu-toggle");
            if (toggle) {
              toggle.setAttribute("aria-expanded", "false");
            }
          }
        });
      }
      item.classList.toggle("is-open", open);
      button.setAttribute("aria-expanded", open ? "true" : "false");
    });
  });

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
        if (searchBox && searchBox.classList.contains("is-open")) {
          setSearch(false);
          return;
        }
        setNav(false);
      }
    });
  }

  document.querySelectorAll(".story-avatar img").forEach(function (img) {
    function drop() {
      if (img.parentNode) {
        img.parentNode.removeChild(img);
      }
    }
    if (img.complete && img.naturalWidth === 0) {
      drop();
    } else {
      img.addEventListener("error", drop);
    }
  });

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

  function rememberCampaign() {
    try {
      var params = new URLSearchParams(window.location.search);
      ["utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term"].forEach(function (key) {
        var value = params.get(key);
        if (value) {
          window.sessionStorage.setItem("lr_" + key, value);
        }
      });
    } catch (err) {
      return;
    }
  }

  function applyTracking(form) {
    var params = new URLSearchParams(window.location.search);
    ["utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term"].forEach(function (key) {
      var input = form.querySelector("[name='" + key + "']");
      if (!input) {
        return;
      }
      var value = params.get(key) || "";
      if (!value) {
        try {
          value = window.sessionStorage.getItem("lr_" + key) || "";
        } catch (err) {
          value = "";
        }
      }
      if (value) {
        input.value = value;
      }
    });
    var landing = form.querySelector("[name='landing_page']");
    if (landing && !landing.value) {
      landing.value = window.location.href;
    }
    var referrer = form.querySelector("[name='referrer']");
    if (referrer && !referrer.value) {
      referrer.value = document.referrer || "";
    }
  }

  function pushAnalytics(payload) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(payload);
  }

  rememberCampaign();

  if (typeof window.liferussTheme !== "undefined") {
    document.querySelectorAll(".consult-form").forEach(function (form) {
      var status = form.parentElement ? form.parentElement.querySelector(".form-status") : document.querySelector(".form-status");
      var started = false;

      function showStatus(ok, message) {
        if (!status) {
          return;
        }
        status.textContent = message;
        status.classList.add("is-visible");
        status.classList.toggle("is-ok", ok);
        status.classList.toggle("is-err", !ok);
      }

      form.addEventListener("focusin", function () {
        if (started) {
          return;
        }
        started = true;
        var typeInput = form.querySelector("[name='consult_type']");
        pushAnalytics({
          event: "form_start",
          form_type: typeInput ? typeInput.value : ""
        });
      });

      form.addEventListener("submit", function (event) {
        event.preventDefault();
        var phone = form.querySelector("[name='consult_phone']");
        if (phone) {
          phone.value = latinDigits(phone.value).replace(/\s+/g, "");
          var valid = /^(\+?98|0)?9\d{9}$/.test(phone.value) || /^\+?\d{8,15}$/.test(phone.value);
          phone.setCustomValidity(valid ? "" : "شماره را با ارقام درست وارد کنید.");
          if (!valid) {
            phone.reportValidity();
            return;
          }
        }
        var visaYear = form.querySelector("[name='consult_visa_expiry_y']");
        var visaHidden = form.querySelector("[name='consult_visa_expiry']");
        if (visaYear && visaHidden && visaYear.value) {
          var visaMonth = form.querySelector("[name='consult_visa_expiry_m']");
          var visaDay = form.querySelector("[name='consult_visa_expiry_d']");
          visaHidden.value = visaYear.value + "/" + (visaMonth ? visaMonth.value : "") + "/" + (visaDay ? visaDay.value : "");
        }
        applyTracking(form);
        var data = new FormData(form);
        data.set("action", "liferuss_consult");
        if (!data.get("liferuss_nonce")) {
          data.set("liferuss_nonce", window.liferussTheme.nonce);
        }

        var button = form.querySelector("button[type='submit']");
        if (button) {
          button.disabled = true;
        }

        var endpoint = window.liferussTheme.leadUrl || window.liferussTheme.ajaxUrl;
        var headers = {};
        if (window.liferussTheme.leadUrl && window.liferussTheme.restNonce) {
          headers["X-WP-Nonce"] = window.liferussTheme.restNonce;
        }

        fetch(endpoint, {
          method: "POST",
          credentials: "same-origin",
          headers: headers,
          body: data
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (json) {
            var payload = json.data || {};
            var ok = Boolean(json.success);
            var labels = window.liferussTheme.strings || {};
            var typeInput = form.querySelector("[name='consult_type']");
            var formType = typeInput ? typeInput.value : "";
            showStatus(ok, payload.message || (ok ? (labels.formOk || "OK") : (labels.formErr || "Error")));
            if (ok) {
              pushAnalytics(payload.analytics || {
                event: "form_submit",
                form_type: formType,
                lead_id: payload.lead_id || ""
              });
              form.reset();
              applyTracking(form);
            } else {
              pushAnalytics({
                event: "form_error",
                form_type: formType
              });
            }
          })
          .catch(function () {
            var labels = window.liferussTheme.strings || {};
            var typeInput = form.querySelector("[name='consult_type']");
            showStatus(false, labels.formNet || "Network error");
            pushAnalytics({
              event: "form_error",
              form_type: typeInput ? typeInput.value : ""
            });
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

(function () {
  var mq = window.matchMedia("(max-width: 860px)");
  function syncFooter() {
    var items = document.querySelectorAll(".footer-acc");
    for (var i = 0; i < items.length; i++) {
      if (mq.matches) {
        items[i].removeAttribute("open");
      } else {
        items[i].setAttribute("open", "");
      }
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", syncFooter);
  } else {
    syncFooter();
  }
  if (mq.addEventListener) {
    mq.addEventListener("change", syncFooter);
  } else if (mq.addListener) {
    mq.addListener(syncFooter);
  }
})();

(function () {
  document.querySelectorAll(".lr-file-input").forEach(function (input) {
    input.addEventListener("change", function () {
      var name = input.parentElement ? input.parentElement.querySelector(".lr-file-name") : null;
      if (!name) {
        return;
      }
      name.textContent = input.files && input.files[0] ? input.files[0].name : name.getAttribute("data-empty") || name.textContent;
    });
  });

  document.querySelectorAll(".lr-filter-toggle").forEach(function (button) {
    button.addEventListener("click", function () {
      var form = button.closest("form");
      if (!form) {
        return;
      }
      var open = form.classList.toggle("is-open");
      button.setAttribute("aria-expanded", open ? "true" : "false");
    });
  });

  document.querySelectorAll(".lr-account-form select[name='channel']").forEach(function (select) {
    var form = select.closest("form");
    var field = form ? form.querySelector("[name='target']") : null;
    var label = form ? form.querySelector(".lr-otp-label") : null;
    if (!field) {
      return;
    }
    function sync() {
      if (select.value === "email") {
        field.type = "email";
        field.inputMode = "email";
        field.autocomplete = "email";
        if (label) {
          label.textContent = "ایمیل";
        }
        return;
      }
      field.type = "tel";
      field.inputMode = "tel";
      field.autocomplete = "tel";
      if (label) {
        label.textContent = "شماره موبایل";
      }
    }
    select.addEventListener("change", sync);
    sync();
  });
})();
