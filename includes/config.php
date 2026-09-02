<?php

session_start();

require_once __DIR__ . "/db.php";

$conn->query("CREATE TABLE IF NOT EXISTS questoes_resolvidas (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, questao_id VARCHAR(100) NOT NULL, jogo VARCHAR(100) NOT NULL, respondida_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE, UNIQUE (usuario_id, questao_id))");
$conn->query("UPDATE conquistas SET icone = 'MEDALHA' WHERE icone NOT IN ('MEDALHA', 'CASO', 'DADOS', 'VETERANO')");

// Altere o nome exibido em todo o site nesta constante.
define("SITE_NAME", "Detetive dos Dados");

$scriptDirectory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "/"));
$baseUrl = preg_replace("#/jogos$#", "", $scriptDirectory);
define("APP_BASE_URL", $baseUrl === "/" ? "" : rtrim($baseUrl, "/"));

?>