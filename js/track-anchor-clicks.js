(function ($) {
  $(document).ready(function () {
    function handleAnchorClick(event) {
      var linkUrl = $(this).attr("href");
      var target = $(this).attr("target");
      var anchorText = $(this).text();
      var pageUrl = window.location.href;

      if (linkUrl) {
        // Create a new entry
        $.ajax({
          url: seo_metrics_ajax_object.ajaxurl, // WordPress AJAX endpoint
          type: "POST",
          data: {
            action: "seo_metrics_create_click_entry",
            nonce: seo_metrics_ajax_object.ajaxnonce,
            link_url: linkUrl,
            anchor_text: anchorText,
            page_url: pageUrl,
          },
          success: function () {
            // Proceed with the link click
            // window.location.href = linkUrl;
            if (!target) {
              target = "_self";
            }
            window.open(linkUrl, target);
          },
        });
      }

      // Prevent the default link behavior
      event.preventDefault();
    }

    // Attach the click event to all anchor links
    $("a").on("click", handleAnchorClick);
  });
})(jQuery);
