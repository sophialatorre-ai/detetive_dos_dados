<?php

require_once "includes/config.php";
require_once "includes/auth.php";
require_once "includes/functions.php";

exigirLogin();

$usuarioId = (int) usuarioId();
garantirCatalogoConquistas($conn);
$catalogoPorNome = [];
foreach (catalogoConquistas() as $conquista) {
    $catalogoPorNome[$conquista["nome"]] = $conquista;
}

$stmt = $conn->prepare("SELECT xp FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$xpUsuario = (int) ($stmt->get_result()->fetch_assoc()["xp"] ?? 0);

$stmt = $conn->prepare(
    "SELECT jogo, COUNT(*) AS total
     FROM questoes_resolvidas
     WHERE usuario_id = ?
     GROUP BY jogo"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$questoesPorJogo = [];
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) {
    $questoesPorJogo[$linha["jogo"]] = (int) $linha["total"];
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
$resultado = $stmt->get_result();
while ($linha = $resultado->fetch_assoc()) {
    $partidasPorJogo[$linha["jogo"]] = (int) $linha["total"];
    $partidasTotais += (int) $linha["total"];
}

$stmt = $conn->prepare(
    "SELECT c.nome, c.descricao, uc.conquistado_em
     FROM conquistas c
     LEFT JOIN usuario_conquistas uc
        ON uc.conquista_id = c.id AND uc.usuario_id = ?
     ORDER BY (uc.conquistado_em IS NULL), uc.conquistado_em DESC, c.id ASC"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$medalhas = $stmt->get_result();
$conquistadas = 0;
$medalhasLista = [];
$nomesExibidos = [];
while ($medalha = $medalhas->fetch_assoc()) {
    if (!isset($catalogoPorNome[$medalha["nome"]]) || isset($nomesExibidos[$medalha["nome"]])) {
        continue;
    }
    $nomesExibidos[$medalha["nome"]] = true;
    $dadosConquista = $catalogoPorNome[$medalha["nome"]];
    $progressoAtual = 0;
    $limite = 0;
    if ($dadosConquista) {
        $limite = (int) $dadosConquista["limite"];
        switch ($dadosConquista["metrica"]) {
            case "xp":
                $progressoAtual = $xpUsuario;
                break;
            case "partidas_total":
                $progressoAtual = $partidasTotais;
                break;
            case "partidas":
                $progressoAtual = $partidasPorJogo[$dadosConquista["jogo"]] ?? 0;
                break;
            case "questoes":
                $progressoAtual = $questoesPorJogo[$dadosConquista["jogo"]] ?? 0;
                break;
        }
    }
    $medalha["progresso"] = $progressoAtual;
    $medalha["limite"] = $limite;
    $medalha["percentual"] = $limite > 0 ? min(100, (int) floor($progressoAtual / $limite * 100)) : 0;
    $medalhasLista[] = $medalha;
    if ($medalha["conquistado_em"] !== null) {
        $conquistadas++;
    }
}

require "includes/header.php";
?>

<section class="page-frame medals-page">
    <header class="page-heading">
        <span class="eyebrow">SUA COLEÇÃO</span>
        <h1>Medalhas</h1>
        <p><?= $conquistadas ?> de <?= count($medalhasLista) ?> conquistas desbloqueadas. Complete desafios nos dois jogos para avançar na coleção.</p>
    </header>
    <?php if ($medalhasLista): ?>
        <div class="medals-grid">
            <?php foreach ($medalhasLista as $medalha): ?>
                <?php $earned = $medalha["conquistado_em"] !== null; ?>
                <article class="medal-card panel <?= $earned ? "earned" : "locked" ?>">
                    <img src="<?= APP_BASE_URL ?>/assets/medalha-conquista.png" alt="<?= $earned ? "Medalha conquistada" : "Medalha ainda não conquistada" ?>">
                    <div>
                        <span class="medal-status"><?= $earned ? "CONQUISTADA" : "BLOQUEADA" ?></span>
                        <h2><?= htmlspecialchars($medalha["nome"], ENT_QUOTES, "UTF-8") ?></h2>
                        <p><?= htmlspecialchars($medalha["descricao"], ENT_QUOTES, "UTF-8") ?></p>
                        <?php if ($medalha["limite"] > 0): ?>
                            <div class="medal-progress-label">
                                <span>Progresso</span>
                                <strong><?= number_format(min($medalha["progresso"], $medalha["limite"]), 0, ",", ".") ?> / <?= number_format($medalha["limite"], 0, ",", ".") ?></strong>
                            </div>
                            <div class="medal-progress" role="progressbar" aria-label="Progresso para <?= htmlspecialchars($medalha["nome"], ENT_QUOTES, "UTF-8") ?>" aria-valuemin="0" aria-valuemax="<?= $medalha["limite"] ?>" aria-valuenow="<?= min($medalha["progresso"], $medalha["limite"]) ?>">
                                <span style="width:<?= $earned ? 100 : $medalha["percentual"] ?>%"></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($earned): ?><small>Conquistada em <?= htmlspecialchars(date("d/m/Y", strtotime($medalha["conquistado_em"])), ENT_QUOTES, "UTF-8") ?></small><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel empty-card"><p>Ainda não há medalhas cadastradas.</p></div>
    <?php endif; ?>
</section>

<?php require "includes/footer.php"; ?>
