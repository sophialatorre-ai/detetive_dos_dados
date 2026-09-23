<?php

function adicionarXP($conn, $usuarioId, $xp)
{
        $sql = "UPDATE usuarios
            SET xp = xp + ?, xp_total = xp_total + ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $xp, $xp, $usuarioId);
    $stmt->execute();

    atualizarNivel($conn, $usuarioId);
}

function atualizarNivel($conn, $usuarioId)
{
    $sql = "SELECT xp_total FROM usuarios WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    $xp = $usuario["xp_total"];

    $nivel = floor($xp / 100) + 1;

    $sql = "UPDATE usuarios
            SET nivel = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $nivel, $usuarioId);
    $stmt->execute();
}

function questaoJaRespondida($conn, $usuarioId, $questaoId)
{
    $stmt = $conn->prepare("SELECT id FROM questoes_resolvidas WHERE usuario_id = ? AND questao_id = ? LIMIT 1");
    $stmt->bind_param("is", $usuarioId, $questaoId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function marcarQuestaoRespondida($conn, $usuarioId, $questaoId, $jogo)
{
    $stmt = $conn->prepare("INSERT IGNORE INTO questoes_resolvidas (usuario_id, questao_id, jogo) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $usuarioId, $questaoId, $jogo);
    $stmt->execute();
}

function registrarPartida(
    $conn,
    $usuarioId,
    $jogo,
    $dificuldade,
    $pontuacao,
    $acertos,
    $erros,
    $adicionarExperiencia = true
) {

    $sql = "INSERT INTO partidas
            (usuario_id, jogo, dificuldade, pontuacao, acertos, erros)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "issiii",
        $usuarioId,
        $jogo,
        $dificuldade,
        $pontuacao,
        $acertos,
        $erros
    );

    $stmt->execute();

    if ($adicionarExperiencia) {
        adicionarXP($conn, $usuarioId, $pontuacao);
    }
}

function percentualAcerto($acertos, $erros)
{
    $total = $acertos + $erros;

    if ($total == 0) {
        return 0;
    }

    return round(($acertos / $total) * 100);
}

function mensagemMotivacionalErro()
{
    $mensagens = [
        "Cada erro é um passo a mais na direção do sonho.",
        "Errar não é fracassar, é descobrir o que ainda precisa ser aprendido.",
        "Não acertar agora não significa não ser capaz.",
        "O aprendizado também acontece nas respostas erradas.",
        "Um erro não define a capacidade, mas mostra onde melhorar.",
        "A evolução começa quando existe coragem para tentar novamente.",
        "Nem todo esforço traz acertos imediatos, mas todo aprendizado tem valor.",
        "Cada questão errada é uma chance de transformar o desconhecimento em conhecimento.",
        "O resultado de hoje não determina o sucesso de amanhã.",
        "Errar faz parte do caminho, desistir não precisa fazer."
    ];

    $tentativa = (int) ($_SESSION["mensagem_motivacional_tentativa"] ?? 0);
    $_SESSION["mensagem_motivacional_tentativa"] = $tentativa + 1;

    if ($tentativa % 2 === 0) {
        return "Não precisa acertar tudo hoje, só preciso continuar tentando.";
    }

    return $mensagens[intdiv($tentativa - 1, 2) % count($mensagens)];
}

?>