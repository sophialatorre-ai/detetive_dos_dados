<?php

require_once "includes/config.php";
require_once "includes/auth.php";

exigirLogin();

$usuarioId = (int) $_SESSION["usuario_id"];
$stmt = $conn->prepare("SELECT nome, email, nivel, xp, criado_em FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) AS partidas, COALESCE(SUM(acertos), 0) AS acertos, COALESCE(SUM(erros), 0) AS erros, COALESCE(SUM(pontuacao), 0) AS pontos FROM partidas WHERE usuario_id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$estatisticas = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT c.nome, c.descricao, c.icone FROM usuario_conquistas uc JOIN conquistas c ON c.id = uc.conquista_id WHERE uc.usuario_id = ? ORDER BY uc.conquistado_em DESC");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$conquistas = $stmt->get_result();

require "includes/header.php";
?>

<section class="page-frame profile-page">
    <div class="profile-hero"><div class="profile-avatar">DD</div><div><span class="eyebrow">ARQUIVO DO DETETIVE</span><h1><?= htmlspecialchars($usuario["nome"] ?? "") ?></h1><p><?= htmlspecialchars($usuario["email"] ?? "") ?></p></div><div class="profile-level"><small>NÍVEL ATUAL</small><strong><?= (int) ($usuario["nivel"] ?? 1) ?></strong></div></div>
    <div class="profile-xp panel"><div><span class="eyebrow">PROGRESSO DA EXPERIÊNCIA</span><h2><?= (int) ($usuario["xp"] ?? 0) ?> <small>XP acumulado</small></h2></div><strong class="xp-next"><?= (int) ($usuario["xp"] ?? 0) % 100 ?>/100</strong><div class="profile-progress"><span style="width: <?= (int) ($usuario["xp"] ?? 0) % 100 ?>%"></span></div></div>
    <div class="profile-stats"><div class="panel profile-stat"><span>◈</span><strong><?= (int) $estatisticas["partidas"] ?></strong><small>Partidas</small></div><div class="panel profile-stat"><span>✓</span><strong><?= (int) $estatisticas["acertos"] ?></strong><small>Acertos</small></div><div class="panel profile-stat"><span>★</span><strong><?= (int) $estatisticas["pontos"] ?></strong><small>Pontos ganhos</small></div></div>
    <section class="panel achievements-panel"><div class="panel-heading"><div><span class="eyebrow">COLEÇÃO</span><h2>Conquistas desbloqueadas</h2></div><span><?= $conquistas->num_rows ?> MEDALHAS</span></div><div class="achievement-grid"><?php if ($conquistas->num_rows === 0): ?><p class="empty-state">Resolva seu primeiro caso para desbloquear uma medalha.</p><?php else: ?><?php while ($conquista = $conquistas->fetch_assoc()): ?><article class="achievement-item"><span><?= htmlspecialchars($conquista["icone"]) ?></span><div><strong><?= htmlspecialchars($conquista["nome"]) ?></strong><p><?= htmlspecialchars($conquista["descricao"]) ?></p></div></article><?php endwhile; ?><?php endif; ?></div></section>
</section>

<?php require "includes/footer.php"; ?>
