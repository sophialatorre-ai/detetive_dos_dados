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
    verificarConquistas($conn, $usuarioId);
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

    verificarConquistas($conn, $usuarioId);
}

function catalogoConquistas()
{
    $marcosQuestoes = [
        1 => "Primeira pista",
        2 => "Dupla de pistas",
        3 => "Trio investigativo",
        5 => "Cinco pistas",
        10 => "Dez pistas",
        15 => "Investigador atento",
        20 => "Analista iniciante",
        25 => "Um quarto de centena",
        30 => "Detetive dedicado",
        40 => "Especialista em pistas",
        50 => "Meia centena",
        75 => "Mestre da análise",
        100 => "Centena de desafios",
        150 => "Investigador experiente",
        200 => "Lenda investigativa",
        250 => "Quase imparável",
        300 => "Mestre dos 300"
    ];
    $marcosPartidas = [
        1 => "Primeira partida",
        2 => "Duas partidas",
        3 => "Três partidas",
        5 => "Cinco partidas",
        10 => "Dez partidas",
        15 => "Jogador frequente",
        20 => "Ritmo constante",
        25 => "Vinte e cinco partidas",
        30 => "Trinta partidas",
        40 => "Dedicação exemplar",
        50 => "Meia centena de partidas",
        75 => "Veterano de jogo",
        100 => "Centena de partidas",
        150 => "Jogador incansável",
        200 => "Lenda das partidas",
        250 => "Dedicação máxima",
        300 => "Mestre das 300 partidas"
    ];

    $catalogo = [
        [
            "nome" => "Primeiro Caso",
            "descricao" => "Conclua sua primeira partida em qualquer jogo.",
            "metrica" => "partidas_total",
            "limite" => 1,
            "jogo" => null
        ],
        [
            "nome" => "Detetive dos Dados",
            "descricao" => "Resolva sua primeira questão de análise de dados.",
            "metrica" => "questoes",
            "limite" => 1,
            "jogo" => "Detetive dos Dados"
        ],
        [
            "nome" => "Detetive Veterano",
            "descricao" => "Acumule 500 XP.",
            "metrica" => "xp",
            "limite" => 500,
            "jogo" => null
        ],
        [
            "nome" => "MentePlay Lendário",
            "descricao" => "Acumule 10.000 XP.",
            "metrica" => "xp",
            "limite" => 10000,
            "jogo" => null
        ]
    ];

    $jogos = [
        ["nome" => "Detetive dos Dados", "rotulo" => "Dados"],
        ["nome" => "Laboratório das Palavras", "rotulo" => "Palavras"]
    ];

    foreach ($jogos as $jogo) {
        foreach ($marcosQuestoes as $limite => $rotulo) {
            $catalogo[] = [
                "nome" => "{$jogo["rotulo"]} - {$rotulo}",
                "descricao" => "Resolva {$limite} questões no jogo {$jogo["nome"]}.",
                "metrica" => "questoes",
                "limite" => $limite,
                "jogo" => $jogo["nome"]
            ];
        }

        foreach ($marcosPartidas as $limite => $rotulo) {
            $catalogo[] = [
                "nome" => "{$jogo["rotulo"]} - {$rotulo}",
                "descricao" => "Conclua {$limite} partidas de {$jogo["nome"]}.",
                "metrica" => "partidas",
                "limite" => $limite,
                "jogo" => $jogo["nome"]
            ];
        }
    }

    return $catalogo;
}

function garantirCatalogoConquistas($conn)
{
    static $catalogoGarantido = false;
    if ($catalogoGarantido) {
        return;
    }

    $catalogo = catalogoConquistas();
    $resultado = $conn->query("SELECT nome FROM conquistas");
    $nomesExistentes = [];
    while ($linha = $resultado->fetch_assoc()) {
        $nomesExistentes[$linha["nome"]] = true;
    }

    $stmt = $conn->prepare("INSERT INTO conquistas (nome, descricao, icone) VALUES (?, ?, 'MEDALHA')");
    foreach ($catalogo as $conquista) {
        if (isset($nomesExistentes[$conquista["nome"]])) {
            continue;
        }

        $stmt->bind_param("ss", $conquista["nome"], $conquista["descricao"]);
        $stmt->execute();
        $nomesExistentes[$conquista["nome"]] = true;
    }

    $catalogoGarantido = true;
}

function verificarConquistas($conn, $usuarioId)
{
    garantirCatalogoConquistas($conn);

    $stmt = $conn->prepare("SELECT xp FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if (!$usuario) {
        return;
    }

    $stmt = $conn->prepare(
        "SELECT jogo, COUNT(*) AS total
         FROM partidas
         WHERE usuario_id = ?
         GROUP BY jogo"
    );
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $partidasPorJogo = [];
    $partidasTotais = 0;
    $resultadoPartidas = $stmt->get_result();
    while ($linha = $resultadoPartidas->fetch_assoc()) {
        $partidasPorJogo[$linha["jogo"]] = (int) $linha["total"];
        $partidasTotais += (int) $linha["total"];
    }

    $stmt = $conn->prepare(
        "SELECT jogo, COUNT(*) AS total
         FROM questoes_resolvidas
         WHERE usuario_id = ?
         GROUP BY jogo"
    );
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $questoesPorJogo = [];
    $resultadoQuestoes = $stmt->get_result();
    while ($linha = $resultadoQuestoes->fetch_assoc()) {
        $questoesPorJogo[$linha["jogo"]] = (int) $linha["total"];
    }

    $stmtMedalha = $conn->prepare("SELECT id FROM conquistas WHERE nome = ?");
    $stmtConceder = $conn->prepare("INSERT IGNORE INTO usuario_conquistas (usuario_id, conquista_id) VALUES (?, ?)");
    $stmtNotificar = $conn->prepare("INSERT IGNORE INTO memoria_notificacoes (usuario_id, conquista_id) VALUES (?, ?)");

    foreach (catalogoConquistas() as $conquista) {
        $metrica = $conquista["metrica"];
        $jogo = $conquista["jogo"];
        if ($metrica === "xp") {
            $progresso = (int) $usuario["xp"];
        } elseif ($metrica === "partidas_total") {
            $progresso = $partidasTotais;
        } elseif ($metrica === "partidas") {
            $progresso = $partidasPorJogo[$jogo] ?? 0;
        } else {
            $progresso = $questoesPorJogo[$jogo] ?? 0;
        }

        if ($progresso < $conquista["limite"]) {
            continue;
        }

        $stmtMedalha->bind_param("s", $conquista["nome"]);
        $stmtMedalha->execute();
        $medalha = $stmtMedalha->get_result()->fetch_assoc();

        if (!$medalha) {
            continue;
        }

        $conquistaId = (int) $medalha["id"];
        $stmtConceder->bind_param("ii", $usuarioId, $conquistaId);
        $stmtConceder->execute();

        if ($stmtConceder->affected_rows === 1) {
            $stmtNotificar->bind_param("ii", $usuarioId, $conquistaId);
            $stmtNotificar->execute();

            if (
                isset($_SESSION["usuario_id"])
                && (int) $_SESSION["usuario_id"] === (int) $usuarioId
            ) {
                $stmtNotificacao = $conn->prepare(
                    "SELECT id
                     FROM memoria_notificacoes
                     WHERE usuario_id = ? AND conquista_id = ? AND concluida_em IS NULL
                     LIMIT 1"
                );
                $stmtNotificacao->bind_param("ii", $usuarioId, $conquistaId);
                $stmtNotificacao->execute();
                $notificacao = $stmtNotificacao->get_result()->fetch_assoc();
                $stmtNotificacao->close();

                if ($notificacao) {
                    $_SESSION["memory_reward_new_ids"] ??= [];
                    $_SESSION["memory_reward_new_ids"][] = (int) $notificacao["id"];
                }
            }
        }
    }
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
    } else {
        verificarConquistas($conn, $usuarioId);
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