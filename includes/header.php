<?php

require_once __DIR__ . "/config.php";

$isGamePage = strpos($_SERVER["SCRIPT_NAME"] ?? "", "/jogos/") !== false;
$isWordsPage = basename($_SERVER["SCRIPT_NAME"] ?? "") === "palavras.php" && $isGamePage;
$isHomePage = basename($_SERVER["SCRIPT_NAME"] ?? "") === "index.php" && !$isGamePage;
$userAccessibility = null;
if (isset($_SESSION["usuario_id"])) {
    $accessibilityStmt = $conn->prepare("SELECT problema_visao, tamanho_letra FROM usuarios WHERE id = ?");
    $accessibilityStmt->bind_param("i", $_SESSION["usuario_id"]);
    $accessibilityStmt->execute();
    $userAccessibility = $accessibilityStmt->get_result()->fetch_assoc();
}
$visionSupport = (bool) ($userAccessibility["problema_visao"] ?? false);
$userFontSize = max(80, min(160, (int) ($userAccessibility["tamanho_letra"] ?? ($visionSupport ? 120 : 100))));

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= SITE_NAME ?></title>

        <link rel="stylesheet"
            href="<?= APP_BASE_URL ?>/css/style.css?v=<?= (int) filemtime(__DIR__ . "/../css/style.css") ?>">

</head>

<body<?= $isWordsPage ? ' class="words-page-body' . ($visionSupport ? ' vision-support' : '') . '"' : ($visionSupport ? ' class="vision-support"' : '') ?> style="--user-zoom:<?= $userFontSize / 100 ?>">

<header class="navbar">

    <a class="logo"
    href="<?= APP_BASE_URL ?>/index.php">

        <span class="logo-mark" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><path d="M23 8c-2-4-8-4-10 1-5-1-8 4-6 8-4 3-3 9 1 11-1 5 3 9 8 8 2 4 7 4 9 1V9m2-1c2-4 8-4 10 1 5-1 8 4 6 8 4 3 3 9-1 11 1 5-3 9-8 8-2 4-7 4-9 1V9M15 15c4 0 5 3 5 5m-10 8c4-2 7 0 8 3m20-16c-4 0-5 3-5 5m10 8c-4-2-7-3-8 3M24 14v23m-7-17 6 3m8-3-6 3m-8 7 6 2m8-2-6 2"/></svg></span><span class="logo-wordmark"><span>Mente<b>Play</b></span><small>A LÓGICA LEVA À VERDADE</small></span>

    </a>

    <nav>

        <?php if (!isset($_SESSION["usuario_id"])): ?>
            <a class="nav-login" href="<?= APP_BASE_URL ?>/login.php">Entrar</a>
            <a class="nav-signup" href="<?= APP_BASE_URL ?>/cadastro.php">Criar conta</a>
        <?php elseif ($isHomePage): ?>
            <a href="#sobre">Sobre</a>
            <a href="<?= APP_BASE_URL ?>/conteudos.php">Conteúdos</a>
            <a href="<?= APP_BASE_URL ?>/ranking.php">Ranking</a>
            <a href="<?= APP_BASE_URL ?>/recompensas.php">Recompensas</a>
            <a href="#contato">Estatísticas</a>
            <a class="home-login" href="<?= APP_BASE_URL ?>/perfil.php">Perfil</a><a class="nav-exit" href="<?= APP_BASE_URL ?>/logout.php">Sair</a>
        <?php else: ?>
            <a href="<?= APP_BASE_URL ?>/conteudos.php">Conteúdos</a>
            <a href="<?= APP_BASE_URL ?>/ranking.php">
                Ranking
            </a>
            <a href="<?= APP_BASE_URL ?>/recompensas.php">
                Recompensas
            </a>

            <a class="nav-user" href="<?= APP_BASE_URL ?>/perfil.php"><?= htmlspecialchars($_SESSION["usuario_nome"] ?? "Perfil") ?></a>
            <a class="nav-exit" href="<?= APP_BASE_URL ?>/logout.php" aria-label="Sair">Sair</a>
        <?php endif; ?>

    </nav>

</header>

<main class="container<?= $isGamePage ? " game-container-page" : "" ?><?= $isWordsPage ? " words-page-container" : "" ?><?= $isHomePage ? " home-container-page" : "" ?>">
