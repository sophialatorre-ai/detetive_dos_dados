<?php
require_once "includes/config.php";
require_once "includes/auth.php";
exigirLogin();

$usuarioId = (int) $_SESSION["usuario_id"];
$stmt = $conn->prepare("SELECT nome, email, nivel, xp, xp_total, criado_em FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) AS partidas, COALESCE(SUM(acertos), 0) AS acertos, COALESCE(SUM(erros), 0) AS erros, COALESCE(SUM(pontuacao), 0) AS pontos FROM partidas WHERE usuario_id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$estatisticas = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT c.nome, c.descricao, c.icone FROM conquistas c LEFT JOIN usuario_conquistas uc ON uc.conquista_id = c.id AND uc.usuario_id = ? ORDER BY uc.conquistado_em DESC, c.id ASC");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$conquistas = $stmt->get_result();

require "includes/header.php";
$xp = (int) ($usuario["xp"] ?? 0);
$xpTotal = (int) ($usuario["xp_total"] ?? $xp);
$nivel = (int) ($usuario["nivel"] ?? 1);
?>
<section class="page-frame profile-page medal-page">
    <div class="profile-hero"><div class="profile-avatar">DD</div><div><span class="eyebrow">ARQUIVO DO DETETIVE</span><h1><?= htmlspecialchars($usuario["nome"] ?? "") ?></h1><p><?= htmlspecialchars($usuario["email"] ?? "") ?></p></div><div class="profile-level"><small>NÍVEL ATUAL</small><strong><?= $nivel ?></strong></div></div>
    <section class="profile-xp panel"><div><span class="eyebrow">PROGRESSO DA INVESTIGAÇÃO</span><h2><?= $xp ?> <small>XP disponível</small></h2><p class="xp-earned"><?= $xpTotal ?> XP acumulados em toda a jornada</p></div><strong class="xp-next"><?= $xpTotal % 100 ?>/100</strong><div class="profile-progress"><span style="width: <?= $xpTotal % 100 ?>%"></span></div><div class="medal-level-note">Nível <?= $nivel ?> · faltam <?= max(0, 100 - ($xpTotal % 100)) ?> XP para o próximo nível</div></section>
    <div class="profile-stats"><div class="panel profile-stat"><span>◈</span><strong><?= (int) $estatisticas["partidas"] ?></strong><small>Casos resolvidos</small></div><div class="panel profile-stat"><span>✓</span><strong><?= (int) $estatisticas["acertos"] ?></strong><small>Acertos</small></div><div class="panel profile-stat"><span>★</span><strong><?= (int) $estatisticas["pontos"] ?></strong><small>Pontos conquistados</small></div></div>
    <section class="panel medals-panel"><div class="panel-heading"><div><span class="eyebrow">SALA DE HONRA</span><h2>Medalhas e conquistas</h2></div><span><?= $conquistas->num_rows ?> MARCOS</span></div><p class="medals-intro">Cada medalha registra uma descoberta importante na sua jornada.</p><div class="medal-grid"><?php while ($conquista = $conquistas->fetch_assoc()): ?><?php $icone = strtoupper((string) $conquista["icone"]); $simbolo = $icone === "VETERANO" ? "★" : ($icone === "DADOS" ? "◆" : "✓"); ?><article class="medal-card"><div class="medal-seal"><span class="medal-icon" aria-hidden="true"><?= $simbolo ?></span><small><?= htmlspecialchars($conquista["icone"]) ?></small></div><div class="medal-copy"><strong><?= htmlspecialchars($conquista["nome"]) ?></strong><p><?= htmlspecialchars($conquista["descricao"]) ?></p></div><span class="medal-ribbon">CONQUISTA</span></article><?php endwhile; ?></div></section>
</section>
<?php require "includes/footer.php"; ?>
