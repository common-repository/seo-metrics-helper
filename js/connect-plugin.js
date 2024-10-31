jQuery(document).ready(function ($) {
  const baseApiUrl = "https://app.seometrics.net";
  $("#seo-metrics-connect-button").on("click", function () {
    // Trigger the AJAX request
    $.ajax({
      url: seo_metrics_ajax_object.ajax_url, // URL to WordPress AJAX handler
      type: "POST",
      data: {
        action: "seo_metrics_handle_connect_button_click", // AJAX action
        nonce: seo_metrics_ajax_object.ajax_nonce,
      },
      success: function (response) {
        if (response.success) {
          // Redirect to the specified URL with query parameters
          var redirectUrl = baseApiUrl + "/connect-wordpress-plugin";
          redirectUrl += "?domain=" + encodeURIComponent(response.data.domain);
          redirectUrl += "&token=" + encodeURIComponent(response.data.token);
          window.location.href = redirectUrl;
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function (error) {
        console.error(error.responseText);
      },
    });
  });
});
