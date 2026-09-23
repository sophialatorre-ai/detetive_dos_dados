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
    xp_total INT DEFAULT 0,
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
('Detetive Veterano', 'Alcance 500 XP.', 'VETERANO'),
('Mente Persistente', 'Tente novamente depois de um erro.', 'MEDALHA'),
('Olhar Investigador', 'Resolva 10 casos com atenção aos detalhes.', 'MEDALHA'),
('Mestre da Jornada', 'Alcance 1.000 XP na investigação.', 'MEDALHA');

CREATE TABLE IF NOT EXISTS itens_avatar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(30) NOT NULL,
    nome VARCHAR(80) NOT NULL UNIQUE,
    simbolo VARCHAR(10) NOT NULL,
    preco_xp INT NOT NULL,
    cor VARCHAR(20) NOT NULL,
    imagem VARCHAR(120) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS usuario_itens (
    usuario_id INT NOT NULL,
    item_id INT NOT NULL,
    equipado TINYINT(1) NOT NULL DEFAULT 0,
    adquirido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, item_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES itens_avatar(id) ON DELETE CASCADE
);

INSERT IGNORE INTO itens_avatar (categoria, nome, simbolo, preco_xp, cor) VALUES
('cabeca', 'Boné de Investigador', '⌂', 80, '#d7a641'),
('cabeca', 'Chapéu de Detetive', '⌒', 140, '#8e5b2b'),
('cabeca', 'Capuz Noturno', '◒', 220, '#384c76'),
('corpo', 'Colete de Dados', '▥', 120, '#247b9d'),
('corpo', 'Sobretudo Azul', '◆', 240, '#1e385d'),
('corpo', 'Jaleco do Laboratório', '⚗', 300, '#4aa987'),
('calcado', 'Tênis de Pista', '⌁', 90, '#dce3e1'),
('calcado', 'Botas de Campo', '▰', 180, '#795133'),
('calcado', 'Botas Neon', '✦', 280, '#31d2dd'),
('acessorio', 'Lupa Dourada', '◯', 100, '#ffc43d'),
('acessorio', 'Óculos Analíticos', '◉', 160, '#79d6e6'),
('acessorio', 'Crachá Mestre', '★', 260, '#b58cff'),
('ferramenta', 'Caderno de Pistas', '▤', 110, '#e4d2a0'),
('ferramenta', 'Tablet Estatístico', '▣', 230, '#5bd0df'),
('ferramenta', 'Maleta Secreta', '▥', 360, '#d49a36');