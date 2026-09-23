<?php

require_once __DIR__ . "/config.php";

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= SITE_NAME ?></title>

        <link rel="stylesheet"
            href="<?= APP_BASE_URL ?>/css/style.css">

</head>

<body>
<?php $isGamePage = strpos($_SERVER["SCRIPT_NAME"] ?? "", "/jogos/") !== false; ?>
<?php $isHomePage = basename($_SERVER["SCRIPT_NAME"] ?? "") === "index.php" && !$isGamePage; ?>

<header class="navbar<?= $isGamePage ? " site-navbar-hidden" : "" ?>">

    <a class="logo"
    href="<?= APP_BASE_URL ?>/index.php">

        <span class="logo-mark"></span><span> <b>MentePlay</b><small>A LÓGICA LEVA À VERDADE</small></span>

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

            <a class="nav-user" href="<?= APP_BASE_URL ?>/perfil.php"><span>●</span> <?= htmlspecialchars($_SESSION["usuario_nome"] ?? "Perfil") ?></a>
            <a class="nav-exit" href="<?= APP_BASE_URL ?>/logout.php" aria-label="Sair">↗</a>

        <?php endif; ?>

    </nav>

</header>

<main class="container<?= $isGamePage ? " game-container-page" : "" ?><?= $isHomePage ? " home-container-page" : "" ?>">