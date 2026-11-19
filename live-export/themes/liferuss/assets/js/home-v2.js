/* Homepage v2: hero video. Nothing is loaded until the visitor presses play. */
(function () {
  "use strict";
  var button = document.querySelector("[data-hv2-play]");
  if (!button) {
    return;
  }
  button.addEventListener("click", function () {
    var media = button.closest("[data-hv2-media]");
    var src = button.getAttribute("data-src");
    var type = button.getAttribute("data-type");
    if (!media || !src) {
      return;
    }
    var node;
    if (type === "file") {
      node = document.createElement("video");
      node.src = src;
      node.controls = true;
      node.autoplay = true;
      node.playsInline = true;
    } else {
      node = document.createElement("iframe");
      node.src = src;
      node.allow = "autoplay; fullscreen; picture-in-picture";
      node.allowFullscreen = true;
      node.title = button.getAttribute("aria-label") || "";
      node.referrerPolicy = "strict-origin-when-cross-origin";
    }
    media.appendChild(node);
    button.hidden = true;
    var caption = media.querySelector(".hv2-media__cap");
    if (caption) {
      caption.hidden = true;
    }
  });
}());

/* "View all" page: filter offer cards by category. Without JS every card stays visible. */
(function () {
  "use strict";
  var bar = document.querySelector("[data-hv2-filter]");
  var grid = document.querySelector("[data-hv2-grid]");
  if (!bar || !grid) {
    return;
  }
  var cards = grid.querySelectorAll("[data-tag]");
  bar.addEventListener("click", function (event) {
    var btn = event.target.closest("button[data-tag]");
    if (!btn) {
      return;
    }
    var tag = btn.getAttribute("data-tag");
    bar.querySelectorAll("button").forEach(function (b) {
      var on = b === btn;
      b.classList.toggle("is-on", on);
      b.setAttribute("aria-pressed", on ? "true" : "false");
    });
    cards.forEach(function (card) {
      card.hidden = tag !== "" && card.getAttribute("data-tag") !== tag;
    });
  });
})();
