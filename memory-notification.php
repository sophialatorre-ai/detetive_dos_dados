<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . "/includes/db.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, private");

$respond = static function (array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (!isset($_SESSION["usuario_id"])) {
    $respond(["error" => "Entre na sua conta para acessar a recompensa."], 401);
}

$userId = (int) $_SESSION["usuario_id"];
$_SESSION["csrf_token"] ??= bin2hex(random_bytes(32));
$csrfToken = $_SESSION["csrf_token"];
$gameSessionKey = "memory_reward_game";
$ticketSessionKey = "memory_reward_tickets";
$pairTypes = ["number", "divide", "multiply", "sum", "magnifier", "star"];

$findPendingNotification = static function (int $notificationId) use ($conn, $userId): ?array {
    $statement = $conn->prepare(
        "SELECT mn.id, c.nome, c.descricao
         FROM memoria_notificacoes mn
         INNER JOIN conquistas c ON c.id = mn.conquista_id
         INNER JOIN usuario_conquistas uc
             ON uc.usuario_id = mn.usuario_id AND uc.conquista_id = mn.conquista_id
         WHERE mn.id = ? AND mn.usuario_id = ? AND mn.concluida_em IS NULL
         LIMIT 1"
    );
    $statement->bind_param("ii", $notificationId, $userId);
    $statement->execute();
    $notification = $statement->get_result()->fetch_assoc();
    $statement->close();

    return $notification ?: null;
};

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $ticket = $_GET["ticket"] ?? "";
    $tickets = $_SESSION[$ticketSessionKey] ?? [];
    if (!is_string($ticket) || !preg_match('/\A[a-f0-9]{48}\z/', $ticket) || !isset($tickets[$ticket])) {
        $respond(["csrfToken" => $csrfToken, "notification" => null]);
    }

    $notificationId = (int) $tickets[$ticket];
    $notification = $findPendingNotification($notificationId);
    unset($_SESSION[$ticketSessionKey][$ticket]);
    if (!$notification) {
        $respond(["csrfToken" => $csrfToken, "notification" => null]);
    }

    $game = $_SESSION[$gameSessionKey] ?? null;
    if (!is_array($game) || (int) ($game["notificationId"] ?? 0) !== $notificationId) {
        $cards = array_merge($pairTypes, $pairTypes);
        shuffle($cards);
        $game = [
            "notificationId" => $notificationId,
            "cards" => $cards,
            "moves" => 0,
            "matched" => [],
            "open" => [],
            "complete" => false,
            "startedAt" => time(),
        ];
        $_SESSION[$gameSessionKey] = $game;
    }

    $respond([
        "csrfToken" => $csrfToken,
        "notification" => [
            "id" => $notificationId,
            "name" => $notification["nome"],
            "description" => $notification["descricao"],
        ],
        "game" => [
            "cards" => $game["cards"],
            "moves" => (int) $game["moves"],
            "matched" => array_values($game["matched"]),
            "open" => array_values($game["open"]),
            "complete" => (bool) $game["complete"],
            "elapsedSeconds" => max(0, time() - (int) ($game["startedAt"] ?? time())),
        ],
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $respond(["error" => "Método não permitido."], 405);
}

$providedToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
if (!is_string($providedToken) || !hash_equals($csrfToken, $providedToken)) {
    $respond(["error" => "A sessão expirou. Atualize a página e tente novamente."], 403);
}

$payload = json_decode(file_get_contents("php://input"), true);
if (!is_array($payload) || !isset($payload["action"], $payload["notificationId"])) {
    $respond(["error" => "Requisição inválida."], 400);
}

$notificationId = filter_var($payload["notificationId"], FILTER_VALIDATE_INT);
if (!$notificationId || $notificationId < 1) {
    $respond(["error" => "Recompensa inválida."], 400);
}

$action = $payload["action"];
$game = $_SESSION[$gameSessionKey] ?? null;

if ($action === "continue") {
    $statement = $conn->prepare(
        "SELECT id FROM memoria_notificacoes
         WHERE id = ? AND usuario_id = ? AND concluida_em IS NOT NULL
         LIMIT 1"
    );
    $statement->bind_param("ii", $notificationId, $userId);
    $statement->execute();
    $completedNotification = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$completedNotification) {
        $respond(["error" => "A recompensa ainda não foi concluída."], 409);
    }

    if (is_array($game) && (int) ($game["notificationId"] ?? 0) === $notificationId) {
        unset($_SESSION[$gameSessionKey]);
    }
    $respond(["success" => true]);
}

if (
    $action === "complete"
    && is_array($game)
    && (int) ($game["notificationId"] ?? 0) === (int) $notificationId
    && !empty($game["complete"])
) {
    $statement = $conn->prepare(
        "SELECT id FROM memoria_notificacoes
         WHERE id = ? AND usuario_id = ? AND concluida_em IS NOT NULL
         LIMIT 1"
    );
    $statement->bind_param("ii", $notificationId, $userId);
    $statement->execute();
    $alreadyCompleted = (bool) $statement->get_result()->fetch_assoc();
    $statement->close();
    if ($alreadyCompleted) {
        $game["completed"] = true;
        $_SESSION[$gameSessionKey] = $game;
        $respond(["success" => true, "game" => $game]);
    }
}

if ($action === "leave") {
    unset($_SESSION[$ticketSessionKey], $_SESSION[$gameSessionKey]);
    $respond(["success" => true]);
}

$notification = $findPendingNotification((int) $notificationId);
if (!$notification) {
    $respond(["error" => "Esta recompensa não está mais pendente."], 409);
}

if (
    !is_array($game)
    || (int) ($game["notificationId"] ?? 0) !== (int) $notificationId
    || !isset($game["cards"], $game["moves"], $game["matched"], $game["open"], $game["complete"])
) {
    $respond(["error" => "O tabuleiro expirou. Atualize a página para continuar."], 409);
}

$cardCount = count($game["cards"]);
$matched = array_map("intval", $game["matched"]);
$open = array_map("intval", $game["open"]);

if ($action === "flip") {
    if ($game["complete"]) {
        $respond(["error" => "O jogo já foi concluído."], 409);
    }
    $index = filter_var($payload["index"] ?? null, FILTER_VALIDATE_INT);
    if ($index === false || $index < 0 || $index >= $cardCount) {
        $respond(["error" => "Carta inválida."], 400);
    }
    if (in_array($index, $matched, true) || in_array($index, $open, true) || count($open) > 1) {
        $respond(["error" => "Essa carta não pode ser virada agora."], 409);
    }

    $open[] = $index;
    if (count($open) === 2) {
        $game["moves"]++;
        if ($game["cards"][$open[0]] === $game["cards"][$open[1]]) {
            $matched = array_merge($matched, $open);
            $open = [];
        }
    }

    $game["matched"] = array_values(array_unique($matched));
    $game["open"] = array_values($open);
    $game["complete"] = count($game["matched"]) === $cardCount;
    $_SESSION[$gameSessionKey] = $game;
} elseif ($action === "hide") {
    if (count($open) !== 2 || $game["cards"][$open[0]] === $game["cards"][$open[1]]) {
        $respond(["error" => "Não há um par incorreto para fechar."], 409);
    }
    $game["open"] = [];
    $_SESSION[$gameSessionKey] = $game;
} elseif ($action === "complete") {
    if (!$game["complete"] || count($matched) !== $cardCount) {
        $respond(["error" => "Encontre todos os pares antes de continuar."], 409);
    }

    $statement = $conn->prepare(
        "UPDATE memoria_notificacoes
         SET concluida_em = CURRENT_TIMESTAMP
         WHERE id = ? AND usuario_id = ? AND concluida_em IS NULL"
    );
    $statement->bind_param("ii", $notificationId, $userId);
    $statement->execute();
    $wasUpdated = $statement->affected_rows === 1;
    $statement->close();

    if (!$wasUpdated) {
        $statement = $conn->prepare(
            "SELECT id FROM memoria_notificacoes
             WHERE id = ? AND usuario_id = ? AND concluida_em IS NOT NULL
             LIMIT 1"
        );
        $statement->bind_param("ii", $notificationId, $userId);
        $statement->execute();
        $wasUpdated = (bool) $statement->get_result()->fetch_assoc();
        $statement->close();
    }
    if (!$wasUpdated) {
        $respond(["error" => "Não foi possível registrar a conclusão."], 500);
    }

    $game["completed"] = true;
    $_SESSION[$gameSessionKey] = $game;
} else {
    $respond(["error" => "Ação não reconhecida."], 400);
}

$respond([
    "success" => true,
    "game" => [
        "cards" => $game["cards"],
        "moves" => (int) $game["moves"],
        "matched" => array_values($game["matched"]),
        "open" => array_values($game["open"]),
        "complete" => (bool) $game["complete"],
        "completed" => (bool) ($game["completed"] ?? false),
        "elapsedSeconds" => max(0, time() - (int) ($game["startedAt"] ?? time())),
    ],
]);
