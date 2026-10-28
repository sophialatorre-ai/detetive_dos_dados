<?php

$localConfig = [
    "host" => "localhost",
    "name" => "detetive_dados",
    "user" => "root",
    "pass" => "",
    "port" => 3308,
];

$productionConfigPath = __DIR__ . "/db.production.php";
if (is_file($productionConfigPath)) {
    $productionConfig = require $productionConfigPath;
    $requiredConfigKeys = ["host", "name", "user", "pass", "port"];
    if (!is_array($productionConfig) || array_diff($requiredConfigKeys, array_keys($productionConfig))) {
        throw new RuntimeException("A configuração de produção do banco está incompleta.");
    }

    $databaseConfig = array_merge($localConfig, $productionConfig);
} else {
    $databaseConfig = $localConfig;
}

$conn = new mysqli(
    $databaseConfig["host"],
    $databaseConfig["user"],
    $databaseConfig["pass"],
    $databaseConfig["name"],
    (int) $databaseConfig["port"]
);

if ($conn->connect_error) {
    die("Erro ao conectar com o banco: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>