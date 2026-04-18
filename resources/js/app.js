import Chart from "chart.js/auto";
import QRCode from "qrcode";
import "./bootstrap";

window.Chart = Chart;

(() => {
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const enableReveal = !prefersReducedMotion;

  if (enableReveal) {
    document.documentElement.classList.add("motion-reveal-enabled");
  }

  const revealNodes = document.querySelectorAll("[data-reveal]");
  if (revealNodes.length > 0) {
    if (enableReveal) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add("is-visible");
            }
          });
        },
        { rootMargin: "0px 0px -32px 0px", threshold: 0.06 }
      );
      revealNodes.forEach((el) => observer.observe(el));
    } else {
      revealNodes.forEach((el) => el.classList.add("is-visible"));
    }
  }

  const siteNav = document.getElementById("site-navbar");
  if (siteNav) {
    const updateNavShadow = () => {
      siteNav.classList.toggle("scrolled", window.scrollY > 6);
    };
    window.addEventListener("scroll", updateNavShadow, { passive: true });
    updateNavShadow();
  }

  const setFormLoading = (form, loading) => {
    if (!form) return;
    form.classList.toggle("is-loading", loading);
    form.setAttribute("aria-busy", loading ? "true" : "false");
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = loading;
      if (loading) {
        if (!submitBtn.dataset.smOriginalHtml) {
          submitBtn.dataset.smOriginalHtml = submitBtn.innerHTML;
        }
        const label = submitBtn.textContent.trim();
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${label}`;
      } else if (submitBtn.dataset.smOriginalHtml) {
        submitBtn.innerHTML = submitBtn.dataset.smOriginalHtml;
        delete submitBtn.dataset.smOriginalHtml;
      }
    }
  };

  document.querySelectorAll("form[data-sm-form-loading]").forEach((form) => {
    form.addEventListener("submit", () => {
      setFormLoading(form, true);
    });
  });

  // reCAPTCHA sulla registrazione: vedi partial Blade `register-recaptcha-inline` (non dipende da Vite).

  const contactForm = document.getElementById("contact-form");
  if (contactForm) {
    contactForm.addEventListener("submit", (event) => {
      const email = contactForm.querySelector("input[name='email']");
      const message = contactForm.querySelector("textarea[name='messaggio']");
      if (!email.value.includes("@") || message.value.trim().length < 10) {
        event.preventDefault();
        alert("Compila correttamente email e messaggio (minimo 10 caratteri).");
        return;
      }
      setFormLoading(contactForm, true);
    });
  }

  const surveyForm = document.querySelector('form[action*="/compila"]');
  if (surveyForm && !surveyForm.hasAttribute("data-survey-take-closed")) {
    const questions = Array.from(surveyForm.querySelectorAll("[data-question-id]"));
    const progressBar = document.getElementById("survey-progress-bar");
    const progressText = document.getElementById("survey-progress-text");
    const validationAlert = document.getElementById("survey-validation-alert");
    const submitBtn = surveyForm.querySelector('button[type="submit"]');

    const answeredCount = () => {
      return questions.reduce((acc, fieldset) => {
        const hasRadio = fieldset.querySelector('input[type="radio"]');
        const hasCheckbox = fieldset.querySelector('input[type="checkbox"]');
        const answered = hasRadio
          ? !!fieldset.querySelector('input[type="radio"]:checked')
          : hasCheckbox
            ? !!fieldset.querySelector('input[type="checkbox"]:checked')
            : false;
        return acc + (answered ? 1 : 0);
      }, 0);
    };

    const updateProgress = () => {
      if (!progressBar || !progressText) return;
      const total = questions.length || 1;
      const answered = answeredCount();
      const pct = Math.round((answered / total) * 100);
      progressBar.style.width = pct + "%";
      progressBar.setAttribute("aria-valuenow", String(pct));
      progressText.textContent = `${answered}/${questions.length} domande`;
    };

    surveyForm.addEventListener("change", () => {
      updateProgress();
      if (validationAlert && !validationAlert.classList.contains("d-none")) {
        validationAlert.classList.add("d-none");
        validationAlert.textContent = "";
      }
      questions.forEach((fieldset) => {
        fieldset.classList.remove("border-danger", "border-2");
        fieldset.removeAttribute("aria-invalid");
      });
    });

    updateProgress();

    surveyForm.addEventListener("submit", (event) => {
      const total = questions.length;
      const answered = answeredCount();
      if (answered < total) {
        event.preventDefault();
        if (validationAlert) {
          validationAlert.classList.remove("d-none");
          validationAlert.textContent = "Compila tutte le domande prima di inviare.";
        }
        let firstInvalid = null;
        questions.forEach((fieldset) => {
          const hasRadio = fieldset.querySelector('input[type="radio"]');
          const hasCheckbox = fieldset.querySelector('input[type="checkbox"]');
          const answeredField = hasRadio
            ? !!fieldset.querySelector('input[type="radio"]:checked')
            : hasCheckbox
              ? !!fieldset.querySelector('input[type="checkbox"]:checked')
              : false;
          if (!answeredField) {
            fieldset.classList.add("border-danger", "border-2");
            fieldset.setAttribute("aria-invalid", "true");
            if (!firstInvalid) firstInvalid = fieldset;
          }
        });
        const focusable = firstInvalid ? firstInvalid.querySelector("input") : null;
        if (focusable && typeof focusable.focus === "function") focusable.focus();
        return;
      }

      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent.trim();
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${originalText}`;
      }
      surveyForm.classList.add("is-loading");
      surveyForm.setAttribute("aria-busy", "true");
    });
  }

  const deleteModal = document.getElementById("deleteSurveyModal");
  const deleteForm = document.getElementById("deleteSurveyForm");
  const deleteModalText = document.getElementById("deleteSurveyModalText");
  if (deleteModal && deleteForm) {
    deleteModal.addEventListener("show.bs.modal", (event) => {
      const trigger = event.relatedTarget;
      if (!trigger) return;
      const url = trigger.getAttribute("data-delete-url");
      const title = trigger.getAttribute("data-survey-title") || "";
      if (url) deleteForm.setAttribute("action", url);
      if (deleteModalText) {
        deleteModalText.textContent = title
          ? `Eliminare il sondaggio "${title}"? L'azione non può essere annullata.`
          : "Eliminare questo sondaggio? L'azione non può essere annullata.";
      }
    });
  }

  const container = document.getElementById("questions-container");
  const addQuestionBtn = document.getElementById("add-question");
  if (container && addQuestionBtn) {
    const initial = Array.isArray(window.__initialQuestions) ? window.__initialQuestions : [];
    let qIndex = 0;

    const optionPlaceholders = ["Es. Opzione A", "Es. Opzione B", "Es. Opzione C", "Es. Opzione D"];

    /* SVG = stesso disegno di bi-trash (Bootstrap Icons), senza dipendere dal font su HTML inserito via JS */
    const trashIconSvg = () =>
      `<svg class="sm-builder-trash-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>`;

    const optionHtml = (q, value = "", phIndex = 0) => {
      const ph = optionPlaceholders[phIndex] || "Testo dell’opzione";
      return `<div class="sm-option-row">
          <input class="form-control flex-grow-1" type="text" name="questions[${q}][options][]" placeholder="${ph}" required value="${value.replace(/"/g, "&quot;")}">
          <button type="button" class="btn btn-sm btn-outline-danger sm-question-card__action sm-option-row__remove flex-shrink-0" data-action="remove-option" title="Rimuovi opzione" aria-label="Rimuovi opzione">${trashIconSvg()}</button>
        </div>`;
    };

    const refreshOptionRemoveButtons = (optsEl) => {
      if (!optsEl) return;
      const rows = optsEl.querySelectorAll(".sm-option-row");
      const showRemove = rows.length > 2;
      rows.forEach((row) => {
        const btn = row.querySelector("[data-action='remove-option']");
        if (!btn) return;
        btn.hidden = !showRemove;
        btn.removeAttribute("disabled");
        if (showRemove) {
          btn.title = "Rimuovi opzione";
          btn.setAttribute("aria-label", "Rimuovi opzione");
        } else {
          btn.removeAttribute("title");
          btn.removeAttribute("aria-label");
        }
      });
    };

    const refreshQuestionLabels = () => {
      container.querySelectorAll(".sm-question-card").forEach((card, idx) => {
        const label = card.querySelector(".sm-question-card__label");
        if (label) label.textContent = `Domanda ${idx + 1}`;
      });
    };

    const runQuestionEnterAnimation = (wrapper) => {
      if (prefersReducedMotion) return;
      wrapper.classList.add("sm-question-card--enter");
      const done = () => wrapper.classList.remove("sm-question-card--enter");
      wrapper.addEventListener("animationend", done, { once: true });
    };

    const runOptionEnterAnimation = (row) => {
      if (prefersReducedMotion || !row) return;
      row.classList.add("sm-option-row--enter");
      const done = () => row.classList.remove("sm-option-row--enter");
      row.addEventListener("animationend", done, { once: true });
    };

    const addQuestion = (question = null, addOpts = {}) => {
      const skipEnter = addOpts.skipEnter === true;
      const i = qIndex++;
      const type = question?.tipo || "singola";
      const text = question?.testo || "";
      const optionValues = question?.options?.length
        ? question.options.map((o) => (typeof o === "string" ? o : o.testo ?? ""))
        : ["", ""];
      const wrapper = document.createElement("div");
      wrapper.className = "sm-question-card card-elevated";
      wrapper.innerHTML = `
        <div class="sm-question-card__header">
          <span class="sm-question-card__label fw-semibold text-body">Domanda</span>
          <div class="sm-question-card__actions d-flex gap-1 flex-shrink-0">
            <button type="button" class="btn btn-sm btn-outline-secondary sm-question-card__action d-inline-flex align-items-center gap-1" data-action="duplicate" title="Duplica domanda" aria-label="Duplica domanda">
              <i class="bi bi-files" aria-hidden="true"></i><span class="d-none d-sm-inline">Duplica</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger sm-question-card__action sm-question-card__remove d-inline-flex align-items-center gap-1" data-action="remove" title="Rimuovi domanda" aria-label="Rimuovi domanda">
              ${trashIconSvg()}<span class="d-none d-sm-inline">Rimuovi</span>
            </button>
          </div>
        </div>
        <div class="sm-question-card__body">
          <label class="form-label" for="q-text-${i}">Testo domanda</label>
          <input class="form-control mb-3" id="q-text-${i}" type="text" name="questions[${i}][text]" required placeholder="Scrivi la domanda che vuoi porre ai partecipanti" value="${text.replace(/"/g, "&quot;")}">
          <label class="form-label" for="q-type-${i}">Tipo di risposta</label>
          <div class="sm-form-select-wrap mb-3">
            <select class="form-select" id="q-type-${i}" name="questions[${i}][type]">
              <option value="singola" ${type === "singola" ? "selected" : ""}>Scelta singola (una opzione)</option>
              <option value="multipla" ${type === "multipla" ? "selected" : ""}>Scelta multipla (più opzioni)</option>
            </select>
          </div>
          <p class="small text-muted mb-2">Opzioni di risposta</p>
          <div class="options">${optionValues.map((o, opi) => optionHtml(i, o, opi)).join("")}</div>
          <button type="button" class="btn btn-outline-secondary btn-sm add-option mt-2 d-inline-flex align-items-center gap-1">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>Aggiungi opzione
          </button>
        </div>
      `;

      const onDuplicate = () => {
        const textInp = wrapper.querySelector(`input[name="questions[${i}][text]"]`);
        const typeSel = wrapper.querySelector(`select[name="questions[${i}][type]"]`);
        const optionInputs = wrapper.querySelectorAll(".options .sm-option-row input");
        const textVal = textInp ? textInp.value : "";
        const typeVal = typeSel ? typeSel.value : "singola";
        const optionVals = Array.from(optionInputs).map((inp) => inp.value);
        addQuestion({
          testo: textVal,
          tipo: typeVal,
          options: optionVals.length ? optionVals.map((t) => ({ testo: t })) : [{ testo: "" }, { testo: "" }]
        });
      };

      const onRemove = () => {
        wrapper.remove();
        refreshQuestionLabels();
      };

      wrapper.querySelector("[data-action='duplicate']").addEventListener("click", onDuplicate);
      wrapper.querySelector("[data-action='remove']").addEventListener("click", onRemove);

      wrapper.querySelector(".add-option").addEventListener("click", () => {
        const optsEl = wrapper.querySelector(".options");
        const n = optsEl.querySelectorAll(".sm-option-row").length;
        optsEl.insertAdjacentHTML("beforeend", optionHtml(i, "", n));
        runOptionEnterAnimation(optsEl.lastElementChild);
        refreshOptionRemoveButtons(optsEl);
      });

      wrapper.querySelector(".options").addEventListener("click", (e) => {
        const btn = e.target.closest("[data-action='remove-option']");
        if (!btn || btn.hidden) return;
        const optsEl = wrapper.querySelector(".options");
        if (optsEl.querySelectorAll(".sm-option-row").length <= 2) return;
        const row = btn.closest(".sm-option-row");
        if (row) row.remove();
        refreshOptionRemoveButtons(optsEl);
      });

      container.appendChild(wrapper);
      refreshQuestionLabels();
      refreshOptionRemoveButtons(wrapper.querySelector(".options"));
      if (!skipEnter) runQuestionEnterAnimation(wrapper);
    };

    if (initial.length > 0) {
      initial.forEach((q) => addQuestion(q, { skipEnter: true }));
    } else {
      addQuestion(null, { skipEnter: true });
    }
    addQuestionBtn.addEventListener("click", () => addQuestion(null, { skipEnter: false }));
  }

  const chartPrimary = () => {
    const v = getComputedStyle(document.documentElement).getPropertyValue("--color-primary").trim();
    return v || "#2563eb";
  };

  const statsPayloadToChartSpecs = (stats) => {
    if (!stats || !Array.isArray(stats.questions)) return [];
    return stats.questions.map((q) => ({
      id: `chart-${q.id}`,
      labels: (q.options || []).map((o) => o.testo),
      values: (q.options || []).map((o) => Number(o.votes) || 0)
    }));
  };

  const applySurveyStatsToDom = (stats) => {
    if (!stats) return;
    const totalEl = document.querySelector("[data-sm-stats-total]");
    if (totalEl) totalEl.textContent = String(Number(stats.total_responses) || 0);
    if (!Array.isArray(stats.questions)) return;
    stats.questions.forEach((q) => {
      const card = document.querySelector(`article.site-stats-q-card[data-question-id="${q.id}"]`);
      if (!card) return;
      (q.options || []).forEach((opt) => {
        const row = card.querySelector(`[data-option-id="${opt.id}"]`);
        if (!row) return;
        const pct = Number(opt.percentuale) || 0;
        const votes = Number(opt.votes) || 0;
        const meta = row.querySelector("[data-sm-stats-option-meta]");
        if (meta) meta.textContent = `${votes} · ${pct}%`;
        const bar = row.querySelector(".progress[role='progressbar']");
        if (bar) {
          bar.setAttribute("aria-valuenow", String(Math.round(pct)));
          const labelText = typeof opt.testo === "string" ? opt.testo : "";
          bar.setAttribute("aria-label", `${labelText}: ${pct}%`);
        }
        const fill = row.querySelector(".progress-bar");
        if (fill) fill.style.width = `${Math.min(100, Math.max(0, pct))}%`;
      });
    });
  };

  const initOrUpdateSurveyBarCharts = (specs) => {
    if (!window.Chart || !Array.isArray(specs)) return;
    const primary = chartPrimary();

    specs.forEach((chartData) => {
      const canvas = document.getElementById(chartData.id);
      if (!canvas) return;
      const values = Array.isArray(chartData.values) ? chartData.values.map((v) => Number(v) || 0) : [];
      const labels = Array.isArray(chartData.labels) ? chartData.labels : [];
      const maxVote = values.length ? Math.max(...values) : 0;
      const yMax = maxVote === 0 ? 1 : undefined;
      const wrap = canvas.closest(".sm-chart-wrap");

      const existing = window.Chart.getChart(canvas);
      if (
        existing &&
        existing.data?.datasets?.[0] &&
        Array.isArray(existing.data.labels) &&
        existing.data.labels.length === labels.length
      ) {
        existing.data.labels = [...labels];
        existing.data.datasets[0].data = values;
        existing.data.datasets[0].backgroundColor = primary;
        if (existing.options?.scales?.y) {
          if (yMax === undefined) {
            delete existing.options.scales.y.max;
          } else {
            existing.options.scales.y.max = yMax;
          }
        }
        existing.update(prefersReducedMotion ? { duration: 0 } : { duration: 450 });
        if (wrap) wrap.classList.add("is-chart-ready");
        return;
      }

      if (existing) existing.destroy();

      new window.Chart(canvas, {
        type: "bar",
        data: {
          labels,
          datasets: [{ label: "Voti", data: values, backgroundColor: primary }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          animation: prefersReducedMotion ? false : { duration: 450 },
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              max: yMax,
              ticks: { precision: 0 }
            }
          }
        }
      });
      if (wrap) wrap.classList.add("is-chart-ready");
    });
  };

  if (window.__initialSurveyStats && window.Chart) {
    const initialStats = window.__initialSurveyStats;
    if (Number(initialStats.total_responses) > 0) {
      initOrUpdateSurveyBarCharts(statsPayloadToChartSpecs(initialStats));
    }
    delete window.__initialSurveyStats;
  }

  const statsPollRoot = document.querySelector("[data-sm-stats-poll]");
  if (statsPollRoot) {
    const dataUrl = statsPollRoot.getAttribute("data-stats-data-url");
    const intervalSec = parseInt(statsPollRoot.getAttribute("data-stats-refresh-interval-seconds") || "15", 10);
    const initialTotal = parseInt(statsPollRoot.getAttribute("data-stats-initial-total") || "0", 10);
    if (dataUrl && intervalSec >= 1) {
      let isFetching = false;
      let pollPaused = document.hidden;
      const intervalMs = Math.max(1000, intervalSec * 1000);

      const tick = async () => {
        if (pollPaused || document.hidden) return;
        if (isFetching) return;
        isFetching = true;
        try {
          const res = await fetch(dataUrl, {
            credentials: "same-origin",
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
          });
          if (!res.ok) return;
          const stats = await res.json();
          const nowTotal = Number(stats.total_responses) || 0;
          if (
            (initialTotal === 0 && nowTotal > 0) ||
            (initialTotal > 0 && nowTotal === 0)
          ) {
            window.location.reload();
            return;
          }
          applySurveyStatsToDom(stats);
          if (nowTotal > 0) {
            initOrUpdateSurveyBarCharts(statsPayloadToChartSpecs(stats));
          }
        } catch {
          /* polling: errori di rete ignorati */
        } finally {
          isFetching = false;
        }
      };

      document.addEventListener("visibilitychange", () => {
        pollPaused = document.hidden;
      });

      window.setInterval(() => {
        void tick();
      }, intervalMs);
    }
  }

  const participantsFilterRoot = document.querySelector("[data-sm-participants-filter-root]");
  if (participantsFilterRoot) {
    const input = participantsFilterRoot.querySelector("[data-sm-participants-search-input]");
    const countEl = participantsFilterRoot.querySelector("[data-sm-participants-visible-count]");
    const allRows = participantsFilterRoot.querySelectorAll("[data-sm-participant-row]");

    const visibleListRoot = () =>
      window.matchMedia("(min-width: 992px)").matches
        ? participantsFilterRoot.querySelector(".sm-participants-desktop")
        : participantsFilterRoot.querySelector(".sm-participants-mobile");

    const applyNeedle = (raw) => {
      const needle = String(raw || "")
        .trim()
        .toLowerCase();
      allRows.forEach((el) => {
        const hay = (el.getAttribute("data-sm-participant-haystack") || "").toLowerCase();
        const match = !needle || hay.includes(needle);
        el.classList.toggle("d-none", !match);
      });
      let shown = 0;
      const wrap = visibleListRoot();
      if (wrap) {
        wrap.querySelectorAll("[data-sm-participant-row]").forEach((el) => {
          if (!el.classList.contains("d-none")) shown += 1;
        });
      }
      if (countEl) countEl.textContent = String(shown);
    };

    if (input) {
      input.addEventListener("input", () => applyNeedle(input.value));
      input.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          input.value = "";
          applyNeedle("");
        }
      });
    }

    window.addEventListener("resize", () => {
      if (!input) return;
      applyNeedle(input.value);
    });
  }

  const qrShareRoot = document.querySelector("[data-sm-qr-share]");
  if (qrShareRoot) {
    const urlInput = qrShareRoot.querySelector("[data-sm-qr-url-input]");
    const copyBtn = qrShareRoot.querySelector("[data-sm-qr-copy]");
    const statusEl = qrShareRoot.querySelector("[aria-live='polite']");
    const canvas = qrShareRoot.querySelector("canvas.sm-share-qr__canvas");

    const rawShareUrl = qrShareRoot.getAttribute("data-share-url");
    const url = rawShareUrl != null ? rawShareUrl.trim() : "";

    const setStatus = (text) => {
      if (!statusEl) return;
      statusEl.textContent = text;
    };

    if (!url) {
      console.warn(
        "[sm-qr-share] Attributo data-share-url assente o vuoto: non viene generato un QR né un URL dedotto dal pathname."
      );
      if (urlInput) urlInput.value = "";
      if (import.meta.env.DEV) {
        setStatus(
          "Sviluppo: imposta data-share-url nel partial (URL canonico dal server) per QR e copia."
        );
      } else {
        setStatus("Link di condivisione non disponibile. Ricarica la pagina o contatta il supporto.");
      }
    } else if (urlInput) {
      urlInput.value = url;
    }

    const copyLink = async () => {
      if (!url) {
        console.warn("[sm-qr-share] Copia richiesta senza data-share-url valido.");
        setStatus("Nessun link da copiare. Ricarica la pagina.");
        return;
      }
      const labelReset = copyBtn ? copyBtn.innerHTML : null;
      const originalText = copyBtn ? copyBtn.textContent.trim() : "";
      if (copyBtn) {
        copyBtn.disabled = true;
        copyBtn.innerHTML = `<i class="bi bi-check2 me-1" aria-hidden="true"></i> Copiato!`;
      }
      try {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
          await navigator.clipboard.writeText(url);
        } else {
          const tmp = document.createElement("input");
          tmp.value = url;
          document.body.appendChild(tmp);
          tmp.select();
          document.execCommand("copy");
          document.body.removeChild(tmp);
        }
        setStatus("Link copiato negli appunti.");
      } catch (e) {
        setStatus("Impossibile copiare automaticamente. Seleziona e copia il link.");
      } finally {
        if (copyBtn) {
          setTimeout(() => {
            copyBtn.disabled = false;
            copyBtn.innerHTML = labelReset || `<i class="bi bi-clipboard me-1" aria-hidden="true"></i> Copia link`;
          }, 2000);
        }
        if (!statusEl && copyBtn) {
          copyBtn.textContent = originalText;
        }
      }
    };

    if (copyBtn) copyBtn.addEventListener("click", copyLink);

    const drawQr = async () => {
      if (!canvas || !url) return;
      try {
        await QRCode.toCanvas(canvas, url, {
          errorCorrectionLevel: "M",
          margin: 1,
          width: 220
        });
      } catch (e) {
        setStatus("Impossibile generare il QR Code.");
      }
    };

    void drawQr();
  }

  const statsCopyBtn = document.querySelector("[data-sm-stats-copy-link]");
  if (statsCopyBtn) {
    const shareUrl = statsCopyBtn.getAttribute("data-share-url") || "";
    const originalHtml = statsCopyBtn.innerHTML;
    statsCopyBtn.addEventListener("click", async () => {
      if (!shareUrl) {
        console.warn("[sm-stats-copy-link] data-share-url assente o vuoto.");
        return;
      }
      statsCopyBtn.disabled = true;
      statsCopyBtn.setAttribute("aria-busy", "true");
      statsCopyBtn.innerHTML =
        '<i class="bi bi-check2" aria-hidden="true"></i> Link copiato';
      try {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
          await navigator.clipboard.writeText(shareUrl);
        } else {
          const tmp = document.createElement("input");
          tmp.value = shareUrl;
          document.body.appendChild(tmp);
          tmp.select();
          document.execCommand("copy");
          document.body.removeChild(tmp);
        }
      } catch {
        /* fallback: utente può usare "Apri sondaggio" */
      } finally {
        window.setTimeout(() => {
          statsCopyBtn.disabled = false;
          statsCopyBtn.removeAttribute("aria-busy");
          statsCopyBtn.innerHTML = originalHtml;
        }, 2000);
      }
    });
  }

  const publicRoot = document.getElementById("sm-public-surveys-root");
  if (publicRoot) {
    const searchUrl = publicRoot.getAttribute("data-search-url");
    const qInput = document.getElementById("sm-public-surveys-q");
    const cardsEl = document.getElementById("sm-public-surveys-cards");
    const pagEl = document.getElementById("sm-public-surveys-pagination");
    const wrapEl = document.getElementById("sm-public-surveys-results-wrap");
    const fetchErrorEl = document.getElementById("sm-public-surveys-fetch-error");
    const activeFiltersRoot = document.getElementById("sm-public-surveys-active-filters");
    const activeFiltersChips = document.getElementById("sm-public-surveys-active-filters-chips");
    const resetBtn = document.getElementById("sm-public-surveys-reset");
    const tagInputs = () => Array.from(publicRoot.querySelectorAll(".sm-tag-filter-input"));

    const setFetchError = (message) => {
      if (!fetchErrorEl) return;
      if (!message) {
        fetchErrorEl.classList.add("d-none");
        fetchErrorEl.textContent = "";
        return;
      }
      fetchErrorEl.textContent = message;
      fetchErrorEl.classList.remove("d-none");
    };

    const syncTagLabelState = () => {
      tagInputs().forEach((inp) => {
        const lbl = inp.closest(".sm-tag-filter-label");
        if (lbl) lbl.classList.toggle("active", inp.checked);
      });
    };

    const updateResetDisabled = () => {
      if (!resetBtn) return;
      const hasQ = Boolean(qInput && qInput.value.trim() !== "");
      const hasTags = tagInputs().some((inp) => inp.checked);
      resetBtn.disabled = !hasQ && !hasTags;
    };

    const renderActiveFilters = () => {
      if (!activeFiltersRoot || !activeFiltersChips) {
        updateResetDisabled();
        return;
      }
      activeFiltersChips.replaceChildren();
      const q = qInput ? qInput.value.trim() : "";

      tagInputs()
        .filter((inp) => inp.checked)
        .forEach((inp) => {
          const lbl = inp.closest(".sm-tag-filter-label");
          const rawName = lbl?.getAttribute("data-tag-name");
          const name = rawName && rawName.trim() !== "" ? rawName.trim() : `Tag ${inp.value}`;
          const pill = document.createElement("span");
          pill.className = "sm-active-filter-pill";
          const text = document.createElement("span");
          text.className = "sm-active-filter-pill__text";
          text.textContent = name;
          const rm = document.createElement("button");
          rm.type = "button";
          rm.className = "sm-active-filter-pill__remove";
          rm.setAttribute("aria-label", `Rimuovi filtro ${name}`);
          const rmIcon = document.createElement("i");
          rmIcon.className = "bi bi-x-lg";
          rmIcon.setAttribute("aria-hidden", "true");
          rm.appendChild(rmIcon);
          rm.addEventListener("click", () => {
            inp.checked = false;
            syncTagLabelState();
            renderActiveFilters();
            void runFetch();
          });
          pill.append(text, rm);
          activeFiltersChips.appendChild(pill);
        });

      if (q !== "") {
        const pill = document.createElement("span");
        pill.className = "sm-active-filter-pill sm-active-filter-pill--query";
        const text = document.createElement("span");
        text.className = "sm-active-filter-pill__text";
        const preview = q.length > 48 ? `${q.slice(0, 45)}…` : q;
        text.textContent = `Ricerca: ${preview}`;
        const rm = document.createElement("button");
        rm.type = "button";
        rm.className = "sm-active-filter-pill__remove";
        rm.setAttribute("aria-label", "Rimuovi ricerca");
        const rmIcon = document.createElement("i");
        rmIcon.className = "bi bi-x-lg";
        rmIcon.setAttribute("aria-hidden", "true");
        rm.appendChild(rmIcon);
        rm.addEventListener("click", () => {
          if (qInput) qInput.value = "";
          renderActiveFilters();
          void runFetch();
        });
        pill.append(text, rm);
        activeFiltersChips.appendChild(pill);
      }

      const hasAny = activeFiltersChips.childNodes.length > 0;
      activeFiltersRoot.classList.toggle("d-none", !hasAny);
      updateResetDisabled();
    };

    const buildParams = () => {
      const params = new URLSearchParams();
      if (qInput && qInput.value.trim() !== "") {
        params.set("q", qInput.value.trim());
      }
      tagInputs().forEach((inp) => {
        if (inp.checked) params.append("tags[]", inp.value);
      });
      return params;
    };

    let debounceTimer = null;
    const runFetch = async () => {
      if (!searchUrl || !cardsEl || !pagEl) return;
      wrapEl?.classList.add("is-loading");
      setFetchError("");
      const qs = buildParams().toString();
      const url = qs ? `${searchUrl}?${qs}` : searchUrl;
      try {
        const res = await fetch(url, {
          headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }
        });
        const contentType = res.headers.get("content-type") || "";
        const isJson = contentType.includes("application/json");
        let data = null;
        if (isJson) {
          try {
            data = await res.json();
          } catch (parseErr) {
            console.error("[sondaggi pubblici] JSON non valido", parseErr);
            setFetchError("Risposta dal server non valida. Ricarica la pagina.");
            return;
          }
        } else {
          const text = await res.text();
          console.error("[sondaggi pubblici] risposta non JSON", res.status, text.slice(0, 300));
          setFetchError("Risposta dal server non valida. Ricarica la pagina.");
          return;
        }

        if (!res.ok) {
          const firstErr =
            data && typeof data === "object" && data.message
              ? String(data.message)
              : data && typeof data === "object" && data.errors
                ? Object.values(data.errors)
                    .flat()
                    .filter(Boolean)
                    .join(" ")
                : "";
          const msg = firstErr || `Impossibile aggiornare l'elenco (errore ${res.status}).`;
          console.error("[sondaggi pubblici] HTTP", res.status, data);
          setFetchError(msg);
          return;
        }

        cardsEl.innerHTML = data.cards_html ?? "";
        pagEl.innerHTML = data.pagination_html ?? "";
        syncTagLabelState();
      } catch (err) {
        console.error("[sondaggi pubblici] fetch fallita", err);
        setFetchError("Connessione non riuscita. Controlla la rete e riprova.");
      } finally {
        wrapEl?.classList.remove("is-loading");
      }
    };

    const scheduleFetch = (delayMs) => {
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(() => {
        debounceTimer = null;
        void runFetch();
      }, delayMs);
    };

    syncTagLabelState();
    renderActiveFilters();

    publicRoot.addEventListener("click", (event) => {
      const lbl = event.target.closest(".sm-tag-filter-label");
      if (!lbl || !publicRoot.contains(lbl) || prefersReducedMotion) return;
      lbl.classList.add("is-pressed");
      window.setTimeout(() => lbl.classList.remove("is-pressed"), 180);
    });

    publicRoot.addEventListener("change", (event) => {
      const t = event.target;
      if (t && t.classList && t.classList.contains("sm-tag-filter-input")) {
        syncTagLabelState();
        renderActiveFilters();
        void runFetch();
      }
    });

    if (resetBtn) {
      resetBtn.addEventListener("click", () => {
        if (qInput) qInput.value = "";
        tagInputs().forEach((inp) => {
          inp.checked = false;
        });
        syncTagLabelState();
        renderActiveFilters();
        void runFetch();
      });
    }

    if (qInput) {
      qInput.addEventListener("input", () => {
        renderActiveFilters();
        scheduleFetch(300);
      });
      qInput.addEventListener("search", () => {
        if (debounceTimer) clearTimeout(debounceTimer);
        renderActiveFilters();
        void runFetch();
      });
    }
  }

  const avatarRoot = document.getElementById("profile-avatar-root");
  const profilePhotoInput = document.getElementById("profile-photo-input");
  if (avatarRoot && profilePhotoInput) {
    const circle = document.getElementById("profile-avatar-circle");
    const alertEl = document.getElementById("profile-photo-alert");
    const uploadUrl = avatarRoot.getAttribute("data-upload-url");
    const maxBytes = parseInt(avatarRoot.getAttribute("data-max-bytes") || "2097152", 10);
    const allowedMime = new Set(["image/jpeg", "image/jpg", "image/png", "image/webp", "image/gif"]);

    const setPhotoAlert = (msg) => {
      if (!alertEl) return;
      if (!msg) {
        alertEl.classList.add("d-none");
        alertEl.textContent = "";
        return;
      }
      alertEl.textContent = msg;
      alertEl.classList.remove("d-none");
    };

    const setAvatarBusy = (busy) => {
      if (circle) {
        circle.classList.toggle("is-busy", busy);
        circle.setAttribute("aria-busy", busy ? "true" : "false");
      }
      profilePhotoInput.disabled = busy;
    };

    const applyPhotoFromServer = (url) => {
      if (!circle || !url) return;
      const sep = url.includes("?") ? "&" : "?";
      const busted = `${url}${sep}t=${Date.now()}`;
      circle.classList.add("site-profile-avatar--has-photo");
      const hoverEl = circle.querySelector(".site-profile-avatar__hover");
      let img = circle.querySelector(".site-profile-avatar__img");
      if (!img) {
        img = document.createElement("img");
        img.className = "site-profile-avatar__img";
        img.alt = "";
        img.width = 72;
        img.height = 72;
        img.decoding = "async";
        if (hoverEl) {
          circle.insertBefore(img, hoverEl);
        } else {
          circle.appendChild(img);
        }
      }
      img.removeAttribute("hidden");
      img.classList.remove("d-none");
      img.src = busted;
      const initials = circle.querySelector(".site-profile-avatar__initials");
      if (initials) initials.classList.add("visually-hidden");
    };

    const parseUploadError = async (res) => {
      try {
        const data = await res.json();
        const photoErr = data?.errors?.photo;
        if (Array.isArray(photoErr) && photoErr[0]) return String(photoErr[0]);
        if (data?.message) return String(data.message);
      } catch {
        /* risposta non JSON: messaggio generico per l’utente */
      }
      return "Non è stato possibile aggiornare la foto. Verifica il file e riprova.";
    };

    profilePhotoInput.addEventListener("change", async () => {
      setPhotoAlert("");
      const file = profilePhotoInput.files?.[0];
      if (!file || !uploadUrl) {
        profilePhotoInput.value = "";
        return;
      }

      const extOk = /\.(jpe?g|png|gif|webp)$/i.test(file.name);
      const mimeOk =
        allowedMime.has(file.type) ||
        (extOk && (!file.type || file.type === "application/octet-stream"));
      if (!mimeOk) {
        setPhotoAlert("Formato non valido. Sono accettati JPEG (JPG), PNG, WebP o GIF.");
        profilePhotoInput.value = "";
        return;
      }
      if (file.size > maxBytes) {
        setPhotoAlert("Il file supera la dimensione massima consentita (2 MB).");
        profilePhotoInput.value = "";
        return;
      }

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
      const formData = new FormData();
      formData.append("photo", file);

      setAvatarBusy(true);
      try {
        const res = await fetch(uploadUrl, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            ...(token ? { "X-CSRF-TOKEN": token } : {})
          }
        });

        if (!res.ok) {
          setPhotoAlert(await parseUploadError(res));
          return;
        }

        const data = await res.json();
        if (data?.url) {
          applyPhotoFromServer(String(data.url));
        }
      } catch {
        setPhotoAlert("Connessione non riuscita. Riprova tra poco.");
      } finally {
        setAvatarBusy(false);
        profilePhotoInput.value = "";
      }
    });
  }

  const countUpEls = document.querySelectorAll("[data-count-up][data-count-target]");
  if (countUpEls.length > 0) {
    const durationMs = 800;
    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
    countUpEls.forEach((el) => {
      const raw = el.getAttribute("data-count-target");
      const target = raw !== null ? parseInt(raw, 10) : NaN;
      if (Number.isNaN(target) || target < 0) {
        el.textContent = "0";
        return;
      }
      if (prefersReducedMotion) {
        el.textContent = String(target);
        return;
      }
      el.textContent = "0";
      const start = performance.now();
      const tick = (now) => {
        const t = Math.min(1, (now - start) / durationMs);
        el.textContent = String(Math.round(target * easeOutCubic(t)));
        if (t < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  }
})();
