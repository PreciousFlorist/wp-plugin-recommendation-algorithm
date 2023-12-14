document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".elo-recommendation").forEach(function (el) {
    el.addEventListener("click", function () {
      // Get Reccomendation and Context ID
      var recommendationId = this.getAttribute("data-recommendation-id");
      var contextId = this.getAttribute("data-context-id");
      var eloAdjustments = this.getAttribute("data-elo-adjustment");
      var rivalAdjustments = this.getAttribute("data-rival-adjustments");

      // Initialize an AJAX request
      var xhr = new XMLHttpRequest();
      // Set up the request as a POST to the AJAX URL provided by WordPress
      xhr.open("POST", ajax_object.ajax_url);
      // Set the content type header for URL encoded form data
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

      // Send the AJAX request with the data
      xhr.send(
        "action=update_elo_rating&" +
          "recommendation_id=" +
          recommendationId + // Winner ID
          "&context_id=" +
          contextId + // Context ID
          "&elo_adjustment=" +
          eloAdjustments + // Winner Elo Adjustment
          "&rival_adjustments=" +
          rivalAdjustments // Rival Elo Adjustments
      );
    });
  });
});
