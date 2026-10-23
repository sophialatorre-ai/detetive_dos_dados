<?php

require_once "includes/config.php";
require_once "includes/auth.php";
require_once "includes/functions.php";



exigirLogin();

$usuarioId = usuarioId();


// =============================
// DADOS DO USUÁRIO
// =============================

$sql = "SELECT nome, email, nivel, xp
        FROM usuarios
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();


// =============================
// ESTATÍSTICAS
// =============================

$sql = "SELECT
            COUNT(*) AS partidas,
            COALESCE(SUM(acertos), 0) AS acertos,
            COALESCE(SUM(erros), 0) AS erros,
            COALESCE(SUM(pontuacao), 0) AS pontos
        FROM partidas
        WHERE usuario_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();

$estatisticas = $stmt->get_result()->fetch_assoc();


// =============================
// CONQUISTAS
// =============================

$sql = "SELECT c.nome, c.descricao, c.icone
        FROM usuario_conquistas uc
        INNER JOIN conquistas c
            ON c.id = uc.conquista_id
        WHERE uc.usuario_id = ?
        ORDER BY uc.conquistado_em DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();

$conquistas = $stmt->get_result();


// =============================
// PROGRESSO DO NÍVEL
// =============================

$xp = (int)$usuario["xp"];
$nivel = (int)$usuario["nivel"];

$xpAtual = $xp % 100;
$progresso = $xpAtual;

?>

<?php require "includes/header.php"; ?>


<section class="dashboard">

    <!-- ============================= -->
    <!-- BOAS-VINDAS -->
    <!-- ============================= -->

    <div class="welcome">

        <div>

            <span class="eyebrow">
                CENTRAL DO DETETIVE
            </span>

            <h1>
                Olá, <?= htmlspecialchars($usuario["nome"]) ?>!
            </h1>

            <p>
                Prepare-se para resolver novos casos matemáticos.
            </p>

        </div>

        <div class="level-card">

            <span>NÍVEL</span>

            <strong>
                <?= $nivel ?>
            </strong>

        </div>

    </div>


    <!-- ============================= -->
    <!-- XP -->
    <!-- ============================= -->

    <div class="xp-card">

        <div class="xp-header">

            <div>
                <strong>
                    <?= $xp ?> XP
                </strong>

                <span>
                    Progresso para o nível <?= $nivel + 1 ?>
                </span>
            </div>

            <strong>
                <?= $xpAtual ?>/100
            </strong>

        </div>

        <div class="progress">

            <div
                class="progress-bar"
                style="width: <?= $progresso ?>%;"
            ></div>

        </div>

        <p>
            Continue jogando para ganhar XP e desbloquear novas medalhas!
        </p>

    </div>


    <!-- ============================= -->
    <!-- ESTATÍSTICAS -->
    <!-- ============================= -->

    <div class="section-title">

        <span class="eyebrow">
            SEU DESEMPENHO
        </span>

        <h2>
            Estatísticas da investigação
        </h2>

    </div>


    <div class="stats-grid">

        <div class="stat-card">

            <span class="stat-icon">
                J
            </span>

            <strong>
                <?= $estatisticas["partidas"] ?>
            </strong>

            <span>
                Partidas
            </span>

        </div>


        <div class="stat-card">

            <span class="stat-icon">
                A
            </span>

            <strong>
                <?= $estatisticas["acertos"] ?>
            </strong>

            <span>
                Acertos
            </span>

        </div>


        <div class="stat-card">

            <span class="stat-icon">
                E
            </span>

            <strong>
                <?= $estatisticas["erros"] ?>
            </strong>

            <span>
                Erros
            </span>

        </div>


        <div class="stat-card">

            <span class="stat-icon">
                P
            </span>

            <strong>
                <?= $estatisticas["pontos"] ?>
            </strong>

            <span>
                Pontos
            </span>

        </div>

    </div>


    <!-- ============================= -->
    <!-- JOGOS -->
    <!-- ============================= -->

    <div class="section-title">

        <span class="eyebrow">
            CASOS DISPONÍVEIS
        </span>

        <h2>
            Escolha sua investigação
        </h2>

    </div>


    <div class="games-grid">

        <a href="jogos/palavras.php" class="game-card robot-game-card">

            <div class="game-icon">
                LP
            </div>

            <h3>
                Laboratório das Palavras
            </h3>

            <p>
                Misture prefixos e sufixos para formar novas palavras.
            </p>

            <span>
                Experimentar agora →
            </span>

        </a>

        <a href="jogos/media.php" class="game-card">

            <div class="game-icon">
                DD
            </div>

            <h3>
                Detetive dos Dados
            </h3>

            <p>
                Analise tabelas, gráficos e medidas estatísticas para solucionar o caso.
            </p>

            <span>
                Investigar →
            </span>

        </a>


    </div>


    <!-- ============================= -->
    <!-- MEDALHAS -->
    <!-- ============================= -->

    <div class="section-title">

        <span class="eyebrow">
            CONQUISTAS
        </span>

        <h2>
            Suas medalhas
        </h2>

    </div>


    <div class="achievements">

        <?php if ($conquistas->num_rows > 0): ?>

            <?php while ($conquista = $conquistas->fetch_assoc()): ?>

                <div class="achievement-card">

                    <div>

                        <h3>
                            <?= htmlspecialchars($conquista["nome"]) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($conquista["descricao"]) ?>
                        </p>

                    </div>

                    <div class="achievement-medal" aria-hidden="true">🏅</div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-card">

                <span>
                    —
                </span>

                <p>
                    Você ainda não possui medalhas.
                    Resolva seu primeiro caso!
                </p>

            </div>

        <?php endif; ?>

    </div>

</section>


<?php require "includes/footer.php"; ?>