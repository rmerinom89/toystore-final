document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");
  const email = document.getElementById("email");
  const password = document.getElementById("password");
  const emailFeedback = document.getElementById("emailFeedback");
  const passwordFeedback = document.getElementById("passwordFeedback");

  if (!loginForm || !email || !password) {
    return;
  }

  function validarEmail() {
    const patron = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!patron.test(email.value.trim())) {
      emailFeedback.textContent = "Ingrese un correo válido.";
      return false;
    }
    emailFeedback.textContent = "";
    return true;
  }

  function validarPassword() {
    const valor = password.value.trim();
    const segura =
      valor.length >= 8 &&
      /[A-Z]/.test(valor) &&
      /[a-z]/.test(valor) &&
      /[0-9]/.test(valor);

    if (!segura) {
      passwordFeedback.textContent =
        "La contraseña debe tener 8 caracteres, una mayúscula, una minúscula y un número.";
      return false;
    }

    passwordFeedback.textContent = "";
    return true;
  }

  email.addEventListener("input", validarEmail);
  password.addEventListener("input", validarPassword);

  loginForm.addEventListener("submit", function (e) {
    const okEmail = validarEmail();
    const okPassword = validarPassword();

    if (!okEmail || !okPassword) {
      e.preventDefault();
    }
  });
});