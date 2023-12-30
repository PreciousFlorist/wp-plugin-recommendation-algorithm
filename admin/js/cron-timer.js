document.addEventListener("DOMContentLoaded", function () {
  // Iterate over each post type's data and initialize a countdown for each
  Object.keys(window).forEach(function (globalVar) {
    if (globalVar.startsWith("postEloData_")) {
      let postType = globalVar.split("_")[1];
      let timeLeft = window[globalVar].timeRemaining;

      function updateCountdown() {
        // Decrement timeLeft at the start of the function
        timeLeft--;
        let countdownTimerElement = document.getElementById(
          "countdownTimer_" + postType
        );

        if (!countdownTimerElement) {
          return; // Skip if the element doesn't exist
        }

        if (timeLeft < 0) {
          clearInterval(countdownInterval);
          countdownTimerElement.innerHTML = "Time for update!";
          return;
        }

        let days = Math.floor(timeLeft / (60 * 60 * 24));
        let hours = Math.floor((timeLeft % (60 * 60 * 24)) / (60 * 60));
        let minutes = Math.floor((timeLeft % (60 * 60)) / 60);
        let seconds = timeLeft % 60;

        countdownTimerElement.innerHTML = `${days > 0 ? days + " days " : ""}${
          hours > 0 ? hours + " hours " : ""
        }${minutes > 0 ? minutes + " minutes " : ""}${seconds} seconds`;
      }

      var countdownInterval = setInterval(updateCountdown, 1000);
    }
  });
});
