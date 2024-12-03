// js/login.js
document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const messageDiv = document.querySelector(".message");
  const spinner = document.querySelector(".spinner");

  form.addEventListener("submit", function (event) {
    event.preventDefault();

    // Affiche le spinner
    spinner.style.display = "inline-block";
    messageDiv.style.display = "none";

    const formData = new FormData(form);

    fetch("request/login_handler.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        spinner.style.display = "none";
        messageDiv.style.display = "block";
        messageDiv.textContent = data.message;

        if (data.status === "success") {
          messageDiv.classList.remove("error");
          messageDiv.classList.add("success");
          setTimeout(() => (window.location.href = "dashboard.php"), 1000);
        } else {
          messageDiv.classList.remove("success");
          messageDiv.classList.add("error");
        }
      })
      .catch((error) => {
        spinner.style.display = "none";
        messageDiv.style.display = "block";
        messageDiv.classList.remove("success");
        messageDiv.classList.add("error");
        messageDiv.textContent = "Une erreur est survenue, veuillez réessayer.";
      });
  });
});
