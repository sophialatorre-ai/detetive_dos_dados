<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "detetive_dados";
$porta = 3308;

$conn = @new mysqli(
    $host,
    $usuario,
    $senha,
    $banco,
    $porta
);

if ($conn->connect_error) {
    // Tenta a porta padrão 3306 se a 3308 falhar
    $conn = new mysqli($host, $usuario, $senha, $banco, 3306);
    if ($conn->connect_error) {
        die("Erro ao conectar com o banco: " . $conn->connect_error);
    }
}

$conn->set_charset("utf8mb4");

?>