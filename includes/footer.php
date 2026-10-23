<?php
$memoryRewardTickets = [];
$memoryRewardPage = in_array(basename($_SERVER["SCRIPT_NAME"] ?? ""), ["media.php", "palavras.php"], true);

if (isset($_SESSION["usuario_id"])) {
    if ($memoryRewardPage && $_SERVER["REQUEST_METHOD"] === "POST") {
        unset($_SESSION["memory_reward_tickets"]);
        $newNotificationIds = array_values(array_unique(array_map(
            "intval",
            $_SESSION["memory_reward_new_ids"] ?? []
        )));
        unset($_SESSION["memory_reward_new_ids"]);

        if ($newNotificationIds && isset($conn)) {
            $statement = $conn->prepare(
                "SELECT mn.id
                 FROM memoria_notificacoes mn
                 INNER JOIN usuario_conquistas uc
                     ON uc.usuario_id = mn.usuario_id AND uc.conquista_id = mn.conquista_id
                 WHERE mn.id = ? AND mn.usuario_id = ? AND mn.concluida_em IS NULL
                 LIMIT 1"
            );
            foreach ($newNotificationIds as $notificationId) {
                if ($notificationId < 1) {
                    continue;
                }
                $userId = (int) $_SESSION["usuario_id"];
                $statement->bind_param("ii", $notificationId, $userId);
                $statement->execute();
                $notification = $statement->get_result()->fetch_assoc();
                if ($notification) {
                    $ticket = bin2hex(random_bytes(24));
                    $_SESSION["memory_reward_tickets"][$ticket] = (int) $notification["id"];
                    $memoryRewardTickets[] = $ticket;
                }
            }
            $statement->close();
        }
    } else {
        unset($_SESSION["memory_reward_new_ids"], $_SESSION["memory_reward_tickets"]);
    }
}
?>
</main>

<footer>

    <div>

        <h3>MentePlay</h3>

        <p>
            A lógica leva à verdade.
        </p>

    </div>

    <p class="copyright">
        Copyright 2026 MathPlay Solutions
    </p>

</footer>

<script src="<?= APP_BASE_URL ?>/js/script.js"></script>
<?php if ($memoryRewardTickets): ?>
<div class="memory-overlay" id="memory-overlay" hidden aria-hidden="true">
    <section class="memory-modal" role="dialog" aria-modal="true" aria-labelledby="memory-title" aria-describedby="memory-description">
        <span class="memory-eyebrow">RECOMPENSA POR CONQUISTA</span>
        <h2 id="memory-title">Desafio da memória</h2>
        <p id="memory-medal"></p>
        <p id="memory-description">Encontre os seis pares de símbolos para concluir sua recompensa.</p>
        <div class="memory-stats" aria-live="polite">
            <span id="memory-moves">Movimentos: 0</span>
            <span id="memory-time">Tempo: 00:00</span>
            <span id="memory-progress">Pares: 0 de 6</span>
        </div>
        <div class="memory-grid" id="memory-grid" role="group" aria-label="Cartas do jogo da memória"></div>
        <section class="memory-completion" id="memory-completion" aria-live="polite" hidden>
            <h3>Parabéns, detetive!</h3>
            <p>Você encontrou todos os pares e concluiu sua recompensa.</p>
        </section>
        <p class="memory-error" id="memory-error" role="alert" hidden></p>
        <button class="memory-retry" id="memory-retry" type="button" hidden>Tentar novamente</button>
        <button class="memory-exit" id="memory-exit" type="button" hidden>Sair do jogo</button>
        <button class="memory-continue" id="memory-continue" type="button" hidden>Continuar</button>
    </section>
</div>
<script
    src="<?= APP_BASE_URL ?>/js/memory-game.js?v=<?= (int) filemtime(__DIR__ . "/../js/memory-game.js") ?>"
    defer
    data-endpoint="<?= APP_BASE_URL ?>/memory-notification.php"
    data-return-url="<?= htmlspecialchars(APP_BASE_URL . "/perfil.php", ENT_QUOTES, "UTF-8") ?>"
    data-tickets="<?= htmlspecialchars(json_encode($memoryRewardTickets, JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8") ?>"></script>
<?php endif; ?>

<aside class="voice-reader" id="voice-reader">
    <button class="voice-reader-toggle" id="voice-reader-toggle" type="button" aria-expanded="false" aria-controls="voice-reader-controls" aria-label="Ler o conteúdo da página em voz alta">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9v6h4l5 4V5L8 9H4zm12.5 3a4.5 4.5 0 0 0-2.5-4.03v8.05A4.5 4.5 0 0 0 16.5 12zm-2.5-9v2.06a7 7 0 0 1 0 13.88V21a9 9 0 0 0 0-18z"/></svg>
        <span>Ler página</span>
    </button>
    <div class="voice-reader-controls" id="voice-reader-controls" hidden>
        <p class="voice-reader-status" id="voice-reader-status" role="status" aria-live="polite">Pronto para ler o conteúdo desta página.</p>
        <div class="voice-reader-actions">
            <button id="voice-reader-pause" type="button" disabled>Pausar</button>
            <button id="voice-reader-resume" type="button" disabled>Continuar leitura</button>
            <button id="voice-reader-stop" type="button" disabled>Interromper</button>
        </div>
    </div>
</aside>
<script src="<?= APP_BASE_URL ?>/js/voice-reader.js?v=<?= (int) filemtime(__DIR__ . "/../js/voice-reader.js") ?>" defer></script>

</body>

</html>