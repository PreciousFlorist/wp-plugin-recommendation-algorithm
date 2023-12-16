document.addEventListener("DOMContentLoaded", function () {
  let timeLeft = postEloData.timeRemaining;
  function updateCountdown() {
    // Decrement timeLeft at the start of the function
    timeLeft--;
    let countdownTimerElement = document.getElementById("countdownTimer");

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
});
