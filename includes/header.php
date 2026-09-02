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

        <span class="logo-mark">DD</span><span>Detetive <b>dos Dados</b><small>A LÓGICA LEVA À VERDADE</small></span>

    </a>

    <nav>

        <?php if ($isHomePage): ?>
            <a class="home-nav-active" href="<?= APP_BASE_URL ?>/index.php">Início</a>
            <a href="#sobre">Sobre</a>
            <a href="<?= APP_BASE_URL ?>/jogos/index.php">Jogos</a>
            <a href="<?= APP_BASE_URL ?>/conteudos.php">Conteúdos</a>
            <a href="<?= APP_BASE_URL ?>/ranking.php">Ranking</a>
            <a href="<?= APP_BASE_URL ?>/perfil.php">Conquistas</a>
            <a href="#contato">Contato</a>
            <?php if (isset($_SESSION["usuario_id"])): ?><a class="home-login" href="<?= APP_BASE_URL ?>/perfil.php">Perfil</a><a class="home-signup" href="<?= APP_BASE_URL ?>/logout.php">Sair</a><?php else: ?><a class="home-login" href="<?= APP_BASE_URL ?>/login.php">Entrar</a><a class="home-signup" href="<?= APP_BASE_URL ?>/cadastro.php">Criar conta</a><?php endif; ?>
        <?php else: ?>

        <a href="<?= APP_BASE_URL ?>/jogos/index.php">
            Jogos
        </a>
        <a href="<?= APP_BASE_URL ?>/conteudos.php">Conteúdos</a>

        <?php if (isset($_SESSION["usuario_id"])): ?>

            <a href="<?= APP_BASE_URL ?>/ranking.php">
                Ranking
            </a>

            <a class="nav-user" href="<?= APP_BASE_URL ?>/perfil.php"><span>●</span> <?= htmlspecialchars($_SESSION["usuario_nome"] ?? "Perfil") ?></a>
            <a class="nav-exit" href="<?= APP_BASE_URL ?>/logout.php" aria-label="Sair">↗</a>

        <?php else: ?>

            <a href="<?= APP_BASE_URL ?>/login.php">
                Entrar
            </a>

            <a class="nav-button"
               href="<?= APP_BASE_URL ?>/cadastro.php">

                Criar conta

            </a>

        <?php endif; ?>
        <?php endif; ?>

    </nav>

</header>

<main class="container<?= $isGamePage ? " game-container-page" : "" ?>">