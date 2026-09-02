CREATE DATABASE IF NOT EXISTS detetive_dados
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE detetive_dados;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel INT DEFAULT 1,
    xp INT DEFAULT 0,
    reset_token VARCHAR(64) NULL,
    reset_expira DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    jogo VARCHAR(100) NOT NULL,
    dificuldade VARCHAR(30) NOT NULL,
    pontuacao INT DEFAULT 0,
    acertos INT DEFAULT 0,
    erros INT DEFAULT 0,
    jogado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE CASCADE
);

CREATE TABLE conquistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    icone VARCHAR(20) DEFAULT 'MEDALHA'
);

CREATE TABLE usuario_conquistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    conquista_id INT NOT NULL,
    conquistado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    FOREIGN KEY (conquista_id)
        REFERENCES conquistas(id)
        ON DELETE CASCADE,

    UNIQUE (usuario_id, conquista_id)
);

CREATE TABLE questoes_resolvidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    questao_id VARCHAR(100) NOT NULL,
    jogo VARCHAR(100) NOT NULL,
    respondida_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE (usuario_id, questao_id)
);

INSERT INTO conquistas (nome, descricao, icone) VALUES
('Primeiro Caso', 'Resolva seu primeiro caso.', 'CASO'),
('Detetive dos Dados', 'Acerte desafios de análise estatística.', 'DADOS'),
('Detetive Veterano', 'Alcance 500 XP.', 'VETERANO');