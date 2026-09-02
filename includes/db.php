<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "detetive_dados";

$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);

if ($conn->connect_error) {
    die("Erro ao conectar com o banco: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>