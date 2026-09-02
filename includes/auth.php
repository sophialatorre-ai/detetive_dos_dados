<?php

function usuarioLogado()
{
    return isset($_SESSION["usuario_id"]);
}

function exigirLogin()
{
    if (!usuarioLogado()) {
        $baseUrl = defined("APP_BASE_URL") ? APP_BASE_URL : "";
        header("Location: " . ($baseUrl ?: "") . "/login.php");
        exit;
    }
}

function usuarioId()
{
    return $_SESSION["usuario_id"] ?? null;
}

?>