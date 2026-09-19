(function ($) {
  "use strict";

  function showTab(id) {
    $(".liferuss-tab").removeClass("is-active");
    $("#" + id).addClass("is-active");
    $(".liferuss-options .nav-tab").removeClass("nav-tab-active");
    $('.liferuss-options .nav-tab[href="#' + id + '"]').addClass("nav-tab-active");
    if (window.history && history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set("tab", id.replace("tab-", ""));
      history.replaceState({}, "", url.toString());
    }
  }

  $(document).on("click", ".liferuss-options .nav-tab", function (e) {
    e.preventDefault();
    var id = ($(this).attr("href") || "").replace("#", "");
    if (id) {
      showTab(id);
    }
  });

  var start = $(".liferuss-options").data("tab") || "general";
  if ($("#tab-" + start).length) {
    showTab("tab-" + start);
  }

  $(document).on("click", ".liferuss-upload", function (e) {
    e.preventDefault();
    var $row = $(this).closest(".liferuss-media-row");
    var frame = wp.media({
      title: "انتخاب تصویر",
      button: { text: "استفاده از تصویر" },
      multiple: false
    });
    frame.on("select", function () {
      var att = frame.state().get("selection").first().toJSON();
      var src = (att.sizes && (att.sizes.medium || att.sizes.thumbnail || att.sizes.full)) || att;
      $row.find('input[type="hidden"]').val(att.id);
      $row.find(".liferuss-preview").html('<img src="' + (src.url || att.url) + '" alt="">');
    });
    frame.open();
  });

  $(document).on("click", ".liferuss-remove", function (e) {
    e.preventDefault();
    var $row = $(this).closest(".liferuss-media-row");
    $row.find('input[type="hidden"]').val("0");
    $row.find(".liferuss-preview").empty();
  });

  if ($.fn.wpColorPicker) {
    $(".liferuss-color").wpColorPicker();
  }

  $(document).on("click", "[data-i18n-lang]", function (e) {
    e.preventDefault();
    var lang = $(this).data("i18n-lang");
    $(".liferuss-i18n-switch [data-i18n-lang]").removeClass("button-primary");
    $('.liferuss-i18n-switch [data-i18n-lang="' + lang + '"]').addClass("button-primary");
    $(".liferuss-i18n-panel").attr("hidden", true);
    $('.liferuss-i18n-panel[data-i18n-lang="' + lang + '"]').removeAttr("hidden");
  });
})(jQuery);
