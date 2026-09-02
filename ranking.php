<?php

require_once "includes/config.php";

$ranking = $conn->query("SELECT id, nome, nivel, xp FROM usuarios ORDER BY xp DESC, nivel DESC, nome ASC LIMIT 20");
$meuId = isset($_SESSION["usuario_id"]) ? (int) $_SESSION["usuario_id"] : 0;

require "includes/header.php";
?>

<section class="page-frame leaderboard-page">
	<div class="page-heading"><span class="eyebrow">PLACAR GLOBAL</span><h1>Quem está no caso?</h1><p>Os detetives que mais avançaram nas investigações.</p></div>
	<div class="leaderboard-layout">
		<section class="panel leaderboard-panel">
			<div class="panel-heading"><h2>Ranking geral</h2><span>TOP 20</span></div>
			<?php if ($ranking && $ranking->num_rows > 0): ?>
				<div class="leaderboard-list">
				<?php $posicao = 1; while ($jogador = $ranking->fetch_assoc()): $classe = $posicao <= 3 ? " podium-" . $posicao : ""; ?>
					<div class="leader-row<?= $jogador["id"] === $meuId ? " is-me" : "" ?>">
						<strong class="rank-number<?= $classe ?>"><?= $posicao++ ?></strong><span class="avatar avatar-<?= (($posicao - 1) % 5) + 1 ?>"><?= strtoupper(substr($jogador["nome"], 0, 1)) ?></span><span class="player-name"><b><?= htmlspecialchars($jogador["nome"]) ?></b><small>Nível <?= (int) $jogador["nivel"] ?></small></span><strong class="player-xp"><?= number_format((int) $jogador["xp"], 0, ",", ".") ?> <small>XP</small></strong>
					</div>
				<?php endwhile; ?>
				</div>
			<?php else: ?><p class="empty-state">Nenhum jogador cadastrado ainda.</p><?php endif; ?>
		</section>
		<aside class="panel ranking-callout"><span class="callout-icon">★</span><span class="eyebrow">SEU PRÓXIMO OBJETIVO</span><h2>Resolva mais casos.</h2><p>Cada partida concluída aumenta seu XP e aproxima você do topo.</p><a class="btn btn-primary" href="jogos/index.php">Jogar agora <span>→</span></a></aside>
	</div>
</section>

<?php require "includes/footer.php"; ?>
