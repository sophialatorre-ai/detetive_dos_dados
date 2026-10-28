<?php

session_start();

require_once __DIR__ . "/db.php";

$conn->query("CREATE TABLE IF NOT EXISTS questoes_resolvidas (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, questao_id VARCHAR(100) NOT NULL, jogo VARCHAR(100) NOT NULL, respondida_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE, UNIQUE (usuario_id, questao_id))");
$conn->query("CREATE TABLE IF NOT EXISTS memoria_notificacoes (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, conquista_id INT NOT NULL, criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, concluida_em TIMESTAMP NULL DEFAULT NULL, FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE, FOREIGN KEY (conquista_id) REFERENCES conquistas(id) ON DELETE CASCADE, UNIQUE (usuario_id, conquista_id), INDEX (usuario_id, concluida_em))");
$userColumns = [
    "problema_visao" => "TINYINT(1) NOT NULL DEFAULT 0",
    "tamanho_letra" => "TINYINT UNSIGNED NOT NULL DEFAULT 100",
    "foto_perfil" => "VARCHAR(255) NULL"
];
foreach ($userColumns as $column => $definition) {
    $columnResult = $conn->query("SHOW COLUMNS FROM usuarios LIKE '{$column}'");
    if ($columnResult->num_rows === 0) {
        $conn->query("ALTER TABLE usuarios ADD COLUMN {$column} {$definition}");
    }
}
$conn->query("UPDATE conquistas SET icone = 'MEDALHA' WHERE icone NOT IN ('MEDALHA', 'CASO', 'DADOS', 'VETERANO')");

// Altere o nome exibido em todo o site nesta constante.
define("SITE_NAME", "MentePlay");

$scriptDirectory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "/"));
$baseUrl = preg_replace("#/(?:admin|jogos)$#", "", $scriptDirectory);
define("APP_BASE_URL", $baseUrl === "/" ? "" : rtrim($baseUrl, "/"));

?>