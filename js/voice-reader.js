document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("voice-reader-toggle");
    const controls = document.getElementById("voice-reader-controls");
    const status = document.getElementById("voice-reader-status");
    const pauseButton = document.getElementById("voice-reader-pause");
    const resumeButton = document.getElementById("voice-reader-resume");
    const stopButton = document.getElementById("voice-reader-stop");

    if (!toggleButton || !controls || !status || !pauseButton || !resumeButton || !stopButton) {
        return;
    }

    const supported = "speechSynthesis" in window && "SpeechSynthesisUtterance" in window;
    let chunks = [];
    let chunkIndex = 0;
    let generation = 0;
    let state = "idle";

    function setState(nextState, message) {
        state = nextState;
        status.textContent = message;
        pauseButton.disabled = !supported || state !== "reading";
        resumeButton.disabled = !supported || state !== "paused";
        stopButton.disabled = !supported || (state !== "reading" && state !== "paused");
        toggleButton.classList.toggle("is-speaking", state === "reading");
        toggleButton.setAttribute("aria-pressed", state === "reading" || state === "paused" ? "true" : "false");
    }

    function collectPageText() {
        const main = document.querySelector("main");
        if (!main) {
            return "";
        }

        const selector = [
            "h1", "h2", "h3", "h4", "p", "li", "legend", "blockquote",
            ".eyebrow", ".section-tag", ".words-kicker", ".clue-heading",
            ".game-progress-label", ".game-feedback", ".stage-feedback",
            ".case-note strong", ".words-case-note strong"
        ].join(",");
        const excluded = "nav, footer, button, script, style, pre, code, svg, [hidden], [aria-hidden='true'], [inert], .memory-overlay, .reference-benefits, .voice-reader";
        const seen = new Set();

        return Array.from(main.querySelectorAll(selector)).map(function (element) {
            if (element.closest(excluded) || element.getClientRects().length === 0) {
                return "";
            }
            const copy = element.cloneNode(true);
            copy.querySelectorAll(excluded + ", a, input, select, textarea").forEach(function (item) {
                item.remove();
            });
            return (copy.innerText || copy.textContent || "").replace(/\s+/g, " ").trim();
        }).filter(function (text) {
            if (!text || seen.has(text)) {
                return false;
            }
            seen.add(text);
            return true;
        }).join(". ");
    }

    function splitIntoChunks(text) {
        const sentences = text.match(/[^.!?]+[.!?]*/g) || [];
        const result = [];
        sentences.forEach(function (sentence) {
            let remaining = sentence.trim();
            while (remaining.length > 220) {
                let boundary = remaining.lastIndexOf(" ", 220);
                if (boundary < 80) {
                    boundary = 220;
                }
                result.push(remaining.slice(0, boundary).trim());
                remaining = remaining.slice(boundary).trim();
            }
            if (remaining) {
                result.push(remaining);
            }
        });
        return result;
    }

    function speakNext(currentGeneration) {
        if (currentGeneration !== generation || state !== "reading") {
            return;
        }
        if (chunkIndex >= chunks.length) {
            setState("completed", "Leitura concluída.");
            return;
        }

        const utterance = new SpeechSynthesisUtterance(chunks[chunkIndex]);
        utterance.lang = "pt-BR";
        utterance.rate = 0.96;
        const voices = window.speechSynthesis.getVoices();
        utterance.voice = voices.find(function (voice) {
            return /^pt-BR$/i.test(voice.lang);
        }) || voices.find(function (voice) {
            return /^pt(-|_)/i.test(voice.lang);
        }) || null;
        utterance.onend = function () {
            if (currentGeneration !== generation) {
                return;
            }
            chunkIndex += 1;
            speakNext(currentGeneration);
        };
        utterance.onerror = function (event) {
            if (currentGeneration === generation) {
                console.error("Falha na síntese de voz:", event.error);
                setState("idle", "Não foi possível reproduzir a leitura. Tente novamente.");
            }
        };
        window.speechSynthesis.speak(utterance);
    }

    function startReading() {
        controls.hidden = false;
        toggleButton.setAttribute("aria-expanded", "true");
        if (!supported) {
            setState("idle", "Este navegador não oferece suporte à leitura por voz.");
            return;
        }

        const text = collectPageText();
        if (!text) {
            setState("idle", "Não encontrei conteúdo textual para ler nesta página.");
            return;
        }

        generation += 1;
        window.speechSynthesis.cancel();
        chunks = splitIntoChunks(text);
        chunkIndex = 0;
        setState("reading", "Lendo o conteúdo da página.");
        speakNext(generation);
    }

    function stopReading(message) {
        generation += 1;
        if (supported) {
            window.speechSynthesis.cancel();
        }
        chunks = [];
        chunkIndex = 0;
        setState("idle", message || "Leitura interrompida.");
    }

    toggleButton.addEventListener("click", function () {
        if (state === "idle" || state === "completed") {
            startReading();
            return;
        }
        controls.hidden = !controls.hidden;
        toggleButton.setAttribute("aria-expanded", String(!controls.hidden));
    });

    pauseButton.addEventListener("click", function () {
        if (supported && state === "reading") {
            window.speechSynthesis.pause();
            setState("paused", "Leitura pausada.");
        }
    });

    resumeButton.addEventListener("click", function () {
        if (supported && state === "paused") {
            window.speechSynthesis.resume();
            setState("reading", "Leitura em andamento.");
        }
    });

    stopButton.addEventListener("click", function () {
        stopReading("Leitura interrompida.");
    });

    window.addEventListener("site:reward-open", function () {
        if (state === "reading" || state === "paused") {
            stopReading("Leitura interrompida para abrir a recompensa.");
        }
    });

    window.addEventListener("pagehide", function () {
        if (supported) {
            window.speechSynthesis.cancel();
        }
    });
});
