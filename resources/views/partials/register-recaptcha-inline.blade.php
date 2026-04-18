{{-- reCAPTCHA v3: eseguito sempre, senza dipendere da @vite / public/build (in Docker dev il bind ./ sovrascrive spesso gli asset della build). --}}
<script>
(function () {
  const form = document.getElementById("register-form");
  if (!form) return;
  const siteKey = (form.getAttribute("data-recaptcha-site-key") || "").trim();
  if (!siteKey) return;

  const tokenInput = form.querySelector('input[name="recaptcha_token"]');
  const langInput = form.querySelector('input[name="client_accept_language"]');
  const tzInput = form.querySelector('input[name="client_timezone"]');
  const screenInput = form.querySelector('input[name="client_screen"]');
  const clientErrorEl = document.getElementById("register-client-error");

  function setRegisterClientError(message) {
    if (!clientErrorEl) return;
    if (message) {
      clientErrorEl.textContent = message;
      clientErrorEl.classList.remove("d-none");
    } else {
      clientErrorEl.textContent = "";
      clientErrorEl.classList.add("d-none");
    }
  }

  function setRegisterLoading(loading) {
    form.classList.toggle("is-loading", loading);
    form.setAttribute("aria-busy", loading ? "true" : "false");
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) return;
    submitBtn.disabled = loading;
    if (loading) {
      if (!submitBtn.dataset.smOriginalHtml) {
        submitBtn.dataset.smOriginalHtml = submitBtn.innerHTML;
      }
      const label = submitBtn.textContent.trim();
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + label;
    } else if (submitBtn.dataset.smOriginalHtml) {
      submitBtn.innerHTML = submitBtn.dataset.smOriginalHtml;
      delete submitBtn.dataset.smOriginalHtml;
    }
  }

  function loadRecaptchaV3() {
    return new Promise(function (resolve, reject) {
      if (window.grecaptcha && typeof window.grecaptcha.execute === "function") {
        resolve();
        return;
      }
      const existing = document.querySelector('script[data-sm-recaptcha="1"]');
      if (existing) {
        if (window.grecaptcha && typeof window.grecaptcha.execute === "function") {
          resolve();
          return;
        }
        existing.addEventListener("load", function () {
          resolve();
        }, { once: true });
        existing.addEventListener("error", function () {
          reject(new Error("recaptcha_script"));
        }, { once: true });
        return;
      }
      const script = document.createElement("script");
      script.src = "https://www.google.com/recaptcha/api.js?render=" + encodeURIComponent(siteKey);
      script.async = true;
      script.defer = true;
      script.setAttribute("data-sm-recaptcha", "1");
      script.addEventListener("load", function () {
        resolve();
      }, { once: true });
      script.addEventListener("error", function () {
        reject(new Error("recaptcha_script"));
      }, { once: true });
      document.head.appendChild(script);
    });
  }

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    setRegisterClientError("");

    if (langInput) {
      langInput.value =
        navigator.languages && navigator.languages.length
          ? navigator.languages.join(",")
          : navigator.language || "";
    }
    if (tzInput) {
      try {
        tzInput.value = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
      } catch (e) {
        tzInput.value = "";
      }
    }
    if (screenInput) {
      const w = window.screen && window.screen.width;
      const h = window.screen && window.screen.height;
      const dpr = window.devicePixelRatio;
      if (w && h) {
        screenInput.value = dpr ? w + "x" + h + "@" + dpr : w + "x" + h;
      } else {
        screenInput.value = "";
      }
    }

    function submitNative() {
      HTMLFormElement.prototype.submit.call(form);
    }

    setRegisterLoading(true);
    loadRecaptchaV3()
      .then(function () {
        return new Promise(function (resolve) {
          if (window.grecaptcha && typeof window.grecaptcha.ready === "function") {
            window.grecaptcha.ready(resolve);
          } else {
            resolve();
          }
        });
      })
      .then(function () {
        return window.grecaptcha.execute(siteKey, { action: "register" });
      })
      .then(function (token) {
        if (tokenInput) tokenInput.value = token || "";
        submitNative();
      })
      .catch(function () {
        setRegisterLoading(false);
        if (tokenInput) tokenInput.value = "";
        setRegisterClientError(
          "Servizio di verifica temporaneamente non disponibile. Ricarica la pagina e riprova."
        );
      });
  });
})();
</script>
