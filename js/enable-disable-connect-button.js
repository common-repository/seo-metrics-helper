jQuery(document).ready(function ($) {
  // Enable or disable connect button
  $("#seo-metrics-welcome-privacy-terms-check").on("change", function () {
    if ($(this).prop("checked") == true) {
      $("#seo-metrics-connect-button").prop("disabled", false);
    } else {
      $("#seo-metrics-connect-button").prop("disabled", true);
    }
  });
});
