document.addEventListener("DOMContentLoaded", function () {
    const script = document.querySelector('script[data-endpoint*="memory-notification.php"]');
    const overlay = document.getElementById("memory-overlay");
    const board = document.getElementById("memory-grid");

    if (!script || !overlay || !board) {
        return;
    }

    const endpoint = script.dataset.endpoint;
    const returnUrl = script.dataset.returnUrl;
    const tickets = JSON.parse(script.dataset.tickets || "[]");
    const medalLabel = document.getElementById("memory-medal");
    const movesLabel = document.getElementById("memory-moves");
    const timeLabel = document.getElementById("memory-time");
    const progressLabel = document.getElementById("memory-progress");
    const errorMessage = document.getElementById("memory-error");
    const retryButton = document.getElementById("memory-retry");
    const exitButton = document.getElementById("memory-exit");
    const continueButton = document.getElementById("memory-continue");
    const completionPanel = document.getElementById("memory-completion");
    const cardSymbols = {
        number: { label: "Número sete", content: "7" },
        divide: { label: "Divisão", content: "÷" },
        multiply: { label: "Multiplicação", content: "×" },
        sum: { label: "Somatório", content: "∑" },
        magnifier: {
            label: "Lupa de investigação",
            content: '<svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><circle cx="20" cy="20" r="12"/><path d="m29 29 11 11"/></svg>'
        },
        star: {
            label: "Estrela de conquista",
            content: '<svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path class="memory-star-shape" d="m24 4 5.8 12 13.2 1.9-9.5 9.3 2.2 13.1L24 34.1l-11.7 6.2 2.2-13.1L5 17.9 18.2 16z"/></svg>'
        }
    };
    let csrfToken = "";
    let activeNotification = null;
    let activeGame = null;
    let locked = false;
    let previousFocus = null;
    let retryAction = null;
    let mismatchTimer = null;
    let returningToProfile = false;
    let gameElapsedBase = 0;
    let gameElapsedStartedAt = 0;
    let timerInterval = null;

    async function sendRequest(action, extra) {
        const response = await fetch(endpoint, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken
            },
            body: JSON.stringify(Object.assign({
                action: action,
                notificationId: activeNotification.id
            }, extra || {}))
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || "Não foi possível atualizar o jogo.");
        }
        return result;
    }

    function setError(message, action) {
        errorMessage.textContent = message;
        errorMessage.hidden = false;
        retryAction = action || null;
        retryButton.hidden = !retryAction;
    }

    function clearError() {
        errorMessage.textContent = "";
        errorMessage.hidden = true;
        retryButton.hidden = true;
        retryAction = null;
    }

    function updateBoard(game) {
        activeGame = game;
        gameElapsedBase = Number(game.elapsedSeconds) || gameElapsedBase;
        gameElapsedStartedAt = Date.now();
        const matched = new Set(game.matched);
        const open = new Set(game.open);
        const totalPairs = game.cards.length / 2;
        const foundPairs = matched.size / 2;

        movesLabel.textContent = "Movimentos: " + game.moves;
        renderElapsedTime();
        progressLabel.textContent = "Pares: " + foundPairs + " de " + totalPairs;
        if (game.complete) {
            window.clearInterval(timerInterval);
        }

        Array.from(board.children).forEach(function (card, index) {
            const isVisible = matched.has(index) || open.has(index);
            const symbol = cardSymbols[game.cards[index]];
            if (!symbol) {
                throw new Error("Símbolo de carta desconhecido.");
            }
            card.disabled = locked || game.complete || matched.has(index) || (open.size > 1);
            card.classList.toggle("is-revealed", isVisible && !matched.has(index));
            card.classList.toggle("is-matched", matched.has(index));
            card.innerHTML = isVisible ? symbol.content : '<span aria-hidden="true">?</span>';
            card.setAttribute(
                "aria-label",
                isVisible ? symbol.label + (matched.has(index) ? ", par encontrado" : "") : "Carta fechada"
            );
        });
    }

    function renderElapsedTime() {
        const seconds = gameElapsedBase + Math.floor((Date.now() - gameElapsedStartedAt) / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainder = seconds % 60;
        timeLabel.textContent = "Tempo: " + String(minutes).padStart(2, "0") + ":" + String(remainder).padStart(2, "0");
    }

    function createBoard(game) {
        board.replaceChildren();
        game.cards.forEach(function (_, index) {
            const card = document.createElement("button");
            card.type = "button";
            card.className = "memory-card";
            card.dataset.index = String(index);
            card.addEventListener("click", function () {
                flipCard(index);
            });
            board.appendChild(card);
        });
        updateBoard(game);
    }

    function showNotification(notification, game) {
        activeNotification = notification;
        previousFocus = document.activeElement;
        medalLabel.textContent = notification.name + ": " + notification.description;
        completionPanel.hidden = true;
        exitButton.hidden = false;
        continueButton.hidden = true;
        clearError();
        overlay.hidden = false;
        overlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("memory-game-active");
        window.dispatchEvent(new CustomEvent("site:reward-open"));
        gameElapsedBase = Number(game.elapsedSeconds) || 0;
        gameElapsedStartedAt = Date.now();
        window.clearInterval(timerInterval);
        timerInterval = window.setInterval(renderElapsedTime, 1000);
        createBoard(game);
        if (game.complete) {
            recordCompletion();
        } else if (game.open.length === 2) {
            scheduleMismatchReset();
        }
        const firstAvailable = board.querySelector("button:not(:disabled)");
        (firstAvailable || continueButton).focus();
    }

    async function flipCard(index) {
        if (locked || !activeNotification || activeGame.complete) {
            return;
        }
        locked = true;
        updateBoard(activeGame);
        clearError();
        try {
            const result = await sendRequest("flip", { index: index });
            updateBoard(result.game);
            if (result.game.open.length === 2) {
                const first = result.game.open[0];
                const second = result.game.open[1];
                if (result.game.cards[first] !== result.game.cards[second]) {
                    scheduleMismatchReset();
                } else {
                    locked = false;
                    updateBoard(result.game);
                    if (result.game.complete) {
                        await recordCompletion();
                    }
                }
            } else {
                locked = false;
                updateBoard(result.game);
                if (result.game.complete) {
                    await recordCompletion();
                }
            }
        } catch (error) {
            console.error("Falha ao virar carta do jogo da memória:", error);
            locked = false;
            updateBoard(activeGame);
            setError(error.message, function () {
                flipCard(index);
            });
        }
    }

    function scheduleMismatchReset() {
        locked = true;
        updateBoard(activeGame);
        mismatchTimer = window.setTimeout(async function () {
            try {
                const result = await sendRequest("hide");
                updateBoard(result.game);
                locked = false;
                updateBoard(result.game);
            } catch (error) {
                console.error("Falha ao fechar o par incorreto:", error);
                setError(error.message, scheduleMismatchReset);
            }
        }, 900);
    }

    async function recordCompletion() {
        locked = true;
        updateBoard(activeGame);
        clearError();
        try {
            const result = await sendRequest("complete");
            updateBoard(result.game);
            completionPanel.hidden = false;
            continueButton.hidden = false;
            progressLabel.textContent = "Todos os pares encontrados.";
            continueButton.focus();
        } catch (error) {
            console.error("Falha ao registrar a conclusão do jogo da memória:", error);
            setError(error.message, recordCompletion);
        }
    }

    async function continueAfterReward() {
        continueButton.disabled = true;
        try {
            await sendRequest("continue");
            activeNotification = null;
            activeGame = null;
            if (!await loadNextReward()) {
                returnToProfile();
            }
        } catch (error) {
            console.error("Falha ao liberar a próxima recompensa:", error);
            setError(error.message, continueAfterReward);
        } finally {
            continueButton.disabled = false;
        }
    }

    async function exitGame() {
        if (returningToProfile) {
            return;
        }
        if (!activeNotification) {
            returnToProfile();
            return;
        }
        exitButton.disabled = true;
        try {
            await sendRequest("leave");
            returnToProfile();
        } catch (error) {
            console.error("Falha ao sair do jogo da memória:", error);
            setError(error.message, exitGame);
            exitButton.disabled = false;
        }
    }

    function returnToProfile() {
        if (!returnUrl) {
            throw new Error("Não foi possível localizar o perfil do usuário.");
        }
        returningToProfile = true;
        window.clearInterval(timerInterval);
        window.location.replace(returnUrl);
    }

    retryButton.addEventListener("click", function () {
        if (retryAction) {
            retryAction();
        }
    });
    continueButton.addEventListener("click", continueAfterReward);
    exitButton.addEventListener("click", exitGame);

    async function loadNextReward() {
        while (tickets.length) {
            const ticket = tickets.shift();
            const url = new URL(endpoint, window.location.href);
            url.searchParams.set("ticket", ticket);
            const response = await fetch(url, {
                credentials: "same-origin",
                cache: "no-store"
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || "Não foi possível carregar a recompensa.");
            }
            csrfToken = result.csrfToken;
            if (result.notification && result.game) {
                showNotification(result.notification, result.game);
                return true;
            }
        }
        return false;
    }

    loadNextReward().catch(function (error) {
        console.error("Falha ao carregar a recompensa de conquista:", error);
        errorMessage.textContent = "Não foi possível abrir a recompensa. Saia para voltar ao perfil.";
        errorMessage.hidden = false;
        exitButton.hidden = false;
        overlay.hidden = false;
        overlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("memory-game-active");
        window.dispatchEvent(new CustomEvent("site:reward-open"));
        exitButton.focus();
    });
});
