<?php

require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/auth.php";

exigirLogin();

$usuarioId = (int) $_SESSION["usuario_id"];

// Auto-garantir estrutura de banco para Itens & Recompensas
$conn->query("CREATE TABLE IF NOT EXISTS itens_avatar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    simbolo VARCHAR(50) DEFAULT '🧢',
    preco_xp INT NOT NULL DEFAULT 50,
    cor VARCHAR(30) DEFAULT '#00ffcc',
    descricao VARCHAR(255) NULL,
    imagem VARCHAR(120) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$colunaDescricao = $conn->query("SHOW COLUMNS FROM itens_avatar LIKE 'descricao'");
if ($colunaDescricao->num_rows === 0) {
    $conn->query("ALTER TABLE itens_avatar ADD COLUMN descricao VARCHAR(255) NULL");
}

$colunaImagem = $conn->query("SHOW COLUMNS FROM itens_avatar LIKE 'imagem'");
if ($colunaImagem->num_rows === 0) {
    $conn->query("ALTER TABLE itens_avatar ADD COLUMN imagem VARCHAR(120) NULL AFTER descricao");
}

$conn->query("CREATE TABLE IF NOT EXISTS usuario_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    item_id INT NOT NULL,
    equipado TINYINT(1) DEFAULT 0,
    adquirido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES itens_avatar(id) ON DELETE CASCADE,
    UNIQUE (usuario_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Popular itens padrão se a tabela estiver vazia
$checkItens = $conn->query("SELECT COUNT(*) AS total FROM itens_avatar");
$rowItens = $checkItens ? $checkItens->fetch_assoc() : ['total' => 0];

if ($rowItens['total'] == 0) {
    $itensIniciais = [
        // Cabeça
        ['Boné de Detetive', 'cabeca', '🧢', 50, '#3b82f6', 'Estilo clássico para investigações diárias.'],
        ['Cartola do Enigma', 'cabeca', '🎩', 120, '#8b5cf6', 'Para detetives que resolvem os mistérios mais profundos.'],
        ['Chapéu Sherlock', 'cabeca', '🕵️', 180, '#d97706', 'O clássico chapéu dos maiores investigadores.'],
        ['Coroa da Estatística', 'cabeca', '👑', 300, '#eab308', 'Recompensa para quem domina todos os dados.'],
        ['Headset Tecnológico', 'cabeca', '🎧', 150, '#06b6d4', 'Comunicação criptografada com a central.'],
        
        // Corpo
        ['Sobretudo de Campo', 'corpo', '🧥', 100, '#64748b', 'Sobretudo estiloso resistente a intempéries.'],
        ['Terno de Gala', 'corpo', '👔', 200, '#0284c7', 'Para apresentações de relatórios finais de caso.'],
        ['Jaleco de Analista', 'corpo', '🩺', 160, '#10b981', 'Para perícia e análise minuciosa de amostras.'],
        ['Colete Refletor', 'corpo', '🦺', 80, '#f97316', 'Equipamento de segurança para investigações noturnas.'],
        ['Quimono da Lógica', 'corpo', '🥋', 250, '#ec4899', 'Foco e equilíbrio mental supremo.'],

        // Calçados
        ['Tênis da Velocidade', 'calcado', '👟', 40, '#3b82f6', 'Para perseguir pistas em alta velocidade.'],
        ['Botas de Exploração', 'calcado', '🥾', 90, '#78350f', 'Resistente para qualquer terreno investigativo.'],
        ['Sapato Social Glossy', 'calcado', '👞', 130, '#1e293b', 'Elegância e firmeza a cada passo.'],
        ['Chinelo de Descanso', 'calcado', '🩴', 30, '#14b8a6', 'Conforto total após um longo dia de casos.'],

        // Acessórios
        ['Óculos Escuros VIP', 'acessorio', '🕶️', 70, '#0f172a', 'Para manter o disfarce sob luz forte.'],
        ['Monóculo do Cético', 'acessorio', '🧐', 110, '#eab308', 'Para examinar cada pequeno detalhe dos gráficos.'],
        ['Mochila Tática', 'acessorio', '🎒', 95, '#475569', 'Carregue pastas, evidências e ferramentas.'],
        ['Cachecol de Inverno', 'acessorio', '🧣', 60, '#ef4444', 'Aquece a mente nos mistérios gelados.'],
        ['Medalha de Ouro', 'acessorio', '🥇', 220, '#fbbf24', 'Símbolo de excelência em resolução de puzzles.'],

        // Ferramentas
        ['Lupa de Precisão', 'ferramenta', '🔍', 50, '#06b6d4', 'Aumenta os detalhes das evidências.'],
        ['Esquadro & Compasso', 'ferramenta', '📐', 85, '#3b82f6', 'Para medições geométricas exatas.'],
        ['Gráfico Holográfico', 'ferramenta', '📊', 140, '#10b981', 'Visualização tridimensional de estatísticas.'],
        ['Tablet de Análise', 'ferramenta', '📱', 210, '#8b5cf6', 'Computação instantânea de médias e medianas.'],
        ['Elixir do Raciocínio', 'ferramenta', '🧪', 175, '#f43f5e', 'Poção especial para surtos de genialidade.']
    ];

    $stmtIns = $conn->prepare("INSERT INTO itens_avatar (nome, categoria, simbolo, preco_xp, cor, descricao, imagem) VALUES (?, ?, ?, ?, ?, ?, NULL)");
    foreach ($itensIniciais as $it) {
        $stmtIns->bind_param("sssiss", $it[0], $it[1], $it[2], $it[3], $it[4], $it[5]);
        $stmtIns->execute();
    }
}

$vestuario = [
    ['Tênis Aqua', 'calcado', '👟', 55, '#4fd1c5', 'Tênis leve para começar a investigação.', 'calcado-01.png'],
    ['Tênis Azul Marinho', 'calcado', '👟', 68, '#4a7794', 'Conforto para longas jornadas.', 'calcado-02.png'],
    ['Sandália Esportiva', 'calcado', '👡', 82, '#d6b94c', 'Praticidade para pistas ao ar livre.', 'calcado-03.png'],
    ['Tênis Turquesa', 'calcado', '👟', 97, '#42bfc4', 'Visual vibrante para casos criativos.', 'calcado-04.png'],
    ['Tênis Coral', 'calcado', '👟', 113, '#f05c67', 'Energia para correr atrás das evidências.', 'calcado-05.png'],
    ['Tênis Vermelho', 'calcado', '👟', 129, '#d73545', 'Um clássico marcante do laboratório.', 'calcado-06.png'],
    ['Tênis Verde', 'calcado', '👟', 146, '#3b9a83', 'Trilha confortável para novas descobertas.', 'calcado-07.png'],
    ['Salto Dourado', 'calcado', '👠', 164, '#e7a42d', 'Elegância para apresentar o relatório final.', 'calcado-08.png'],
    ['Bota Rosa', 'calcado', '🥾', 181, '#c72d73', 'Atitude para enfrentar qualquer enigma.', 'calcado-09.png'],
    ['Tênis Salmão', 'calcado', '👟', 199, '#e89478', 'Leveza para conectar as pistas.', 'calcado-10.png'],
    ['Tênis Azul', 'calcado', '👟', 217, '#397ac3', 'Agilidade para solucionar desafios.', 'calcado-11.png'],
    ['Mocassim Turquesa', 'calcado', '👞', 236, '#35aeb0', 'Discrição para investigações silenciosas.', 'calcado-12.png'],
    ['Bota Turquesa', 'calcado', '🥾', 255, '#4fbcca', 'Resistência para casos difíceis.', 'calcado-13.png'],
    ['Bota Caramelo', 'calcado', '🥾', 274, '#bb7e39', 'Companheira de todas as expedições.', 'calcado-14.png'],
    ['Salto Vermelho', 'calcado', '👠', 293, '#df3348', 'Presença forte na sala de evidências.', 'calcado-15.png'],
    ['Bota Marrom', 'calcado', '🥾', 312, '#9a5137', 'Proteção para pistas fora da central.', 'calcado-16.png'],
    ['Sandália Azul', 'calcado', '👡', 331, '#3d6fad', 'Casual, mas pronta para investigar.', 'calcado-17.png'],
    ['Sapato Marrom', 'calcado', '👞', 350, '#a56b37', 'Sofisticação para casos especiais.', 'calcado-18.png'],
    ['Bota de Campo', 'calcado', '🥾', 369, '#9d7138', 'Firmeza para atravessar qualquer terreno.', 'calcado-19.png'],
    ['Tênis Rosa', 'calcado', '👟', 388, '#d63888', 'Coragem para tentar uma nova hipótese.', 'calcado-20.png'],
    ['Sandália Vermelha', 'calcado', '👡', 407, '#e64c42', 'Um toque ousado na investigação.', 'calcado-21.png'],
    ['Sandália Listrada', 'calcado', '👡', 426, '#4eb3b0', 'Estilo descontraído para revisar pistas.', 'calcado-22.png'],
    ['Bota Azul Jeans', 'calcado', '🥾', 445, '#7089ab', 'Conforto para maratonar desafios.', 'calcado-23.png'],
    ['Tênis Rosa Vibrante', 'calcado', '👟', 464, '#d9408f', 'Personalidade para sua coleção.', 'calcado-24.png'],
    ['Sandália Laranja', 'calcado', '👡', 483, '#e9a64b', 'Calor e movimento para a jornada.', 'calcado-25.png'],
    ['Sandália Turquesa', 'calcado', '👡', 502, '#55b9b2', 'Uma escolha fresca para novos casos.', 'calcado-26.png'],
    ['Bota Lilás', 'calcado', '🥾', 521, '#778daa', 'Mistério e estilo em cada passo.', 'calcado-27.png'],
    ['Bota Dourada', 'calcado', '🥾', 540, '#c18a3f', 'Recompensa para detetives persistentes.', 'calcado-28.png'],
    ['Óculos Rosa', 'acessorio', '👓', 155, '#d84a9a', 'Enxergue detalhes que passariam despercebidos.', 'acessorio-01.png'],
    ['Óculos Escuros', 'acessorio', '🕶️', 172, '#9b6b56', 'Disfarce ideal para missões reservadas.', 'acessorio-02.png'],
    ['Brincos Joia', 'acessorio', '💎', 189, '#e83c94', 'Brilho para celebrar cada descoberta.', 'acessorio-03.png'],
    ['Anel de Diamante', 'acessorio', '💍', 206, '#d684cc', 'Um marco para conquistas valiosas.', 'acessorio-04.png'],
    ['Colar de Cristal', 'acessorio', '📿', 223, '#9a6bd3', 'Elegância concentrada para analisar dados.', 'acessorio-05.png'],
    ['Chapéu Creme', 'acessorio', '🎩', 240, '#ead28e', 'Charme clássico de uma mente brilhante.', 'acessorio-06.png'],
    ['Cachecol Lilás', 'acessorio', '🧣', 257, '#c99ad9', 'Aconchego para enfrentar problemas complexos.', 'acessorio-07.png'],
    ['Luvas Lilás', 'acessorio', '🧤', 274, '#8952d0', 'Cuidado ao manusear evidências delicadas.', 'acessorio-08.png'],
    ['Espelho de Bolso', 'acessorio', '🪞', 291, '#9569b8', 'Confira cada detalhe antes da conclusão.', 'acessorio-09.png'],
    ['Blusa Amarela', 'corpo', '👚', 318, '#e9d447', 'Alegria para iluminar novas hipóteses.', 'corpo-01.png'],
    ['Vestido Vermelho', 'corpo', '👗', 337, '#ed6670', 'Confiança para apresentar suas descobertas.', 'corpo-02.png'],
    ['Shorts Jeans', 'corpo', '🩳', 356, '#54a4c4', 'Liberdade para investigar sem limites.', 'corpo-03.png'],
    ['Vestido Rosa', 'corpo', '👗', 375, '#ee9cac', 'Delicadeza e foco na análise.', 'corpo-04.png'],
    ['Vestido Verde', 'corpo', '👗', 394, '#72a36f', 'Estilo natural para uma mente observadora.', 'corpo-05.png']
];

$stmtVestuario = $conn->prepare("INSERT INTO itens_avatar (nome, categoria, simbolo, preco_xp, cor, descricao, imagem) SELECT ?, ?, ?, ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM itens_avatar WHERE nome = ?)");
foreach ($vestuario as $itemVestuario) {
    $stmtVestuario->bind_param("sssissss", $itemVestuario[0], $itemVestuario[1], $itemVestuario[2], $itemVestuario[3], $itemVestuario[4], $itemVestuario[5], $itemVestuario[6], $itemVestuario[0]);
    $stmtVestuario->execute();
}

$mensagem = "";
$tipoMensagem = "";

// Processar Ações (Comprar / Equipar / Desequipar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $itemId = isset($_POST["item_id"]) ? (int)$_POST["item_id"] : 0;
    $acao = $_POST["acao"] ?? "";

    // Buscar item
    $stmtItem = $conn->prepare("SELECT * FROM itens_avatar WHERE id = ?");
    $stmtItem->bind_param("i", $itemId);
    $stmtItem->execute();
    $item = $stmtItem->get_result()->fetch_assoc();

    // Buscar usuário atualizado
    $stmtUsr = $conn->prepare("SELECT xp FROM usuarios WHERE id = ?");
    $stmtUsr->bind_param("i", $usuarioId);
    $stmtUsr->execute();
    $usrData = $stmtUsr->get_result()->fetch_assoc();
    $xpAtual = (int)($usrData["xp"] ?? 0);

    if ($item) {
        // Checar se já possui o item
        $stmtPossui = $conn->prepare("SELECT * FROM usuario_itens WHERE usuario_id = ? AND item_id = ?");
        $stmtPossui->bind_param("ii", $usuarioId, $itemId);
        $stmtPossui->execute();
        $possuido = $stmtPossui->get_result()->fetch_assoc();

        if ($acao === "comprar") {
            if ($possuido) {
                $mensagem = "Você já possui o item '" . htmlspecialchars($item["nome"]) . "'!";
                $tipoMensagem = "erro";
            } elseif ($xpAtual < (int)$item["preco_xp"]) {
                $mensagem = "XP insuficiente! Você precisa de " . $item["preco_xp"] . " XP para comprar '" . htmlspecialchars($item["nome"]) . "'.";
                $tipoMensagem = "erro";
            } else {
                // Deduzir XP e salvar compra
                $conn->begin_transaction();
                try {
                    $novoXp = $xpAtual - (int)$item["preco_xp"];
                    $stmtUpdateXp = $conn->prepare("UPDATE usuarios SET xp = ? WHERE id = ?");
                    $stmtUpdateXp->bind_param("ii", $novoXp, $usuarioId);
                    $stmtUpdateXp->execute();

                    $stmtInsertUi = $conn->prepare("INSERT INTO usuario_itens (usuario_id, item_id, equipado) VALUES (?, ?, 0)");
                    $stmtInsertUi->bind_param("ii", $usuarioId, $itemId);
                    $stmtInsertUi->execute();

                    $conn->commit();
                    $mensagem = "🎉 Parabéns! Você comprou '" . htmlspecialchars($item["nome"]) . "' por " . $item["preco_xp"] . " XP!";
                    $tipoMensagem = "sucesso";
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensagem = "Erro ao processar compra. Tente novamente.";
                    $tipoMensagem = "erro";
                }
            }
        } elseif ($acao === "equipar") {
            if (!$possuido) {
                $mensagem = "Você não possui este item para equipar.";
                $tipoMensagem = "erro";
            } else {
                // Desequipar itens da mesma categoria do usuário
                $stmtDes = $conn->prepare("UPDATE usuario_itens ui JOIN itens_avatar ia ON ui.item_id = ia.id SET ui.equipado = 0 WHERE ui.usuario_id = ? AND ia.categoria = ?");
                $stmtDes->bind_param("is", $usuarioId, $item["categoria"]);
                $stmtDes->execute();

                // Equipar item selecionado
                $stmtEq = $conn->prepare("UPDATE usuario_itens SET equipado = 1 WHERE usuario_id = ? AND item_id = ?");
                $stmtEq->bind_param("ii", $usuarioId, $itemId);
                $stmtEq->execute();

                $mensagem = "✨ '" . htmlspecialchars($item["nome"]) . "' foi equipado no seu Mascote Capivara!";
                $tipoMensagem = "sucesso";
            }
        } elseif ($acao === "desequipar") {
            if ($possuido && $possuido["equipado"]) {
                $stmtUn = $conn->prepare("UPDATE usuario_itens SET equipado = 0 WHERE usuario_id = ? AND item_id = ?");
                $stmtUn->bind_param("ii", $usuarioId, $itemId);
                $stmtUn->execute();

                $mensagem = "Item '" . htmlspecialchars($item["nome"]) . "' foi desequipado.";
                $tipoMensagem = "sucesso";
            }
        }
    }
}

// Buscar dados atualizados do Usuário
$stmtUser = $conn->prepare("SELECT nome, email, nivel, xp FROM usuarios WHERE id = ?");
$stmtUser->bind_param("i", $usuarioId);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();

// Buscar todos os itens com indicação de posse e equipados pelo usuário
$queryCatalog = "
    SELECT 
        ia.*, 
        ui.item_id AS posse_id,
        COALESCE(ui.equipado, 0) AS equipado
    FROM itens_avatar ia
    LEFT JOIN usuario_itens ui ON ia.id = ui.item_id AND ui.usuario_id = ?
    ORDER BY 
        CASE ia.categoria 
            WHEN 'cabeca' THEN 1 
            WHEN 'corpo' THEN 2 
            WHEN 'calcado' THEN 3 
            WHEN 'acessorio' THEN 4 
            WHEN 'ferramenta' THEN 5 
            ELSE 6 
        END,
        ia.preco_xp ASC
";
$stmtCatalog = $conn->prepare($queryCatalog);
$stmtCatalog->bind_param("i", $usuarioId);
$stmtCatalog->execute();
$catalogRes = $stmtCatalog->get_result();

$todosItens = [];
$itensEquipados = [
    'cabeca' => null,
    'corpo' => null,
    'calcado' => null,
    'acessorio' => null,
    'ferramenta' => null
];
$totalAdquiridos = 0;

while ($row = $catalogRes->fetch_assoc()) {
    $todosItens[] = $row;
    if ($row["posse_id"]) {
        $totalAdquiridos++;
    }
    if ($row["equipado"] == 1) {
        $itensEquipados[$row["categoria"]] = $row;
    }
}

$totalItensBanco = count($todosItens);

require_once __DIR__ . "/includes/header.php";
?>

<section class="page-frame rewards-page">
    <!-- Top Hero Banner -->
    <div class="rewards-hero">
        <div class="rewards-hero-content">
            <span class="eyebrow">GUARDA-ROUPA &amp; RECOMPENSAS</span>
            <h1>Mascote Detetive</h1>
            <p>Ganhe XP resolvendo desafios e personalize o mascote Capivara com equipamentos e acessórios exclusivos!</p>
        </div>
        <div class="rewards-stats-bar">
            <div class="stat-pill xp-pill">
                <span class="stat-icon">⚡</span>
                <div class="stat-text">
                    <small>SEU XP DISPONÍVEL</small>
                    <strong><?= number_format((int)($usuario["xp"] ?? 0)) ?> XP</strong>
                </div>
            </div>
            <div class="stat-pill level-pill">
                <span class="stat-icon">⭐</span>
                <div class="stat-text">
                    <small>NÍVEL ATUAL</small>
                    <strong>Nível <?= (int)($usuario["nivel"] ?? 1) ?></strong>
                </div>
            </div>
            <div class="stat-pill collection-pill">
                <span class="stat-icon">🏆</span>
                <div class="stat-text">
                    <small>COLEÇÃO</small>
                    <strong><?= $totalAdquiridos ?> / <?= $totalItensBanco ?> Itens</strong>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="alert-box alert-<?= $tipoMensagem ?>">
            <span><?= $tipoMensagem === "sucesso" ? "✓" : "⚠️" ?></span>
            <p><?= $mensagem ?></p>
        </div>
    <?php endif; ?>

    <!-- Main Layout Grid: Left Mascot Showcase / Right Wardrobe Store -->
    <div class="rewards-layout">
        
        <!-- MASCOT SHOWCASE STAGE -->
        <aside class="mascot-stage-panel panel">
            <div class="stage-header">
                <h2>Seu Mascote</h2>
                <span class="stage-badge">Capivara Detetive</span>
            </div>

            <!-- Central Mascot Interactive Viewport -->
            <div class="mascot-viewport">
                <!-- Equipped visual badges overlays -->
                <div class="mascot-equipped-badges">
                    <?php foreach ($itensEquipados as $catKey => $eqItem): ?>
                        <?php if ($eqItem): ?>
                            <div class="badge-equipped-tag cat-<?= $catKey ?>" style="--accent-color: <?= htmlspecialchars($eqItem['cor']) ?>">
                                <span class="tag-icon"><?= htmlspecialchars($eqItem['simbolo']) ?></span>
                                <span class="tag-name"><?= htmlspecialchars($eqItem['nome']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- AI Generated Animal Mascot Image -->
                <div class="mascot-image-wrapper">
                    <img src="<?= APP_BASE_URL ?>/img/mascote.png" alt="Mascote Capivara Detetive" class="mascot-img">
                    <div class="mascot-shadow"></div>
                </div>
            </div>

            <!-- Equipped Slots Gear Summary -->
            <div class="equipped-slots-grid">
                <h3>Equipamentos Ativos</h3>

                <?php 
                $categoriasMeta = [
                    'cabeca' => ['nome' => 'Cabeça', 'icon' => '🧢'],
                    'corpo' => ['nome' => 'Corpo', 'icon' => '🧥'],
                    'calcado' => ['nome' => 'Calçados', 'icon' => '👟'],
                    'acessorio' => ['nome' => 'Acessório', 'icon' => '🕶️'],
                    'ferramenta' => ['nome' => 'Ferramenta', 'icon' => '🔍']
                ];
                ?>

                <div class="slots-list">
                    <?php foreach ($categoriasMeta as $catKey => $meta): ?>
                        <?php $itemEq = $itensEquipados[$catKey]; ?>
                        <div class="slot-row <?= $itemEq ? 'slot-filled' : 'slot-empty' ?>">
                            <div class="slot-icon-box" style="<?= $itemEq ? 'background:' . htmlspecialchars($itemEq['cor']) . '22; color:' . htmlspecialchars($itemEq['cor']) : '' ?>">
                                <?= $itemEq ? htmlspecialchars($itemEq['simbolo']) : $meta['icon'] ?>
                            </div>
                            <div class="slot-info">
                                <small><?= $meta['nome'] ?></small>
                                <strong><?= $itemEq ? htmlspecialchars($itemEq['nome']) : 'Vazio' ?></strong>
                            </div>
                            <?php if ($itemEq): ?>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="item_id" value="<?= $itemEq['id'] ?>">
                                    <input type="hidden" name="acao" value="desequipar">
                                    <button type="submit" class="btn-slot-remove" title="Desequipar item">&times;</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- ITEM CATALOG STORE -->
        <main class="store-catalog-panel panel">
            
            <!-- Category Navigation Tabs -->
            <div class="category-tabs">
                <button class="tab-btn active" data-category="todos">
                    <span class="tab-icon">✨</span> Todos (<?= count($todosItens) ?>)
                </button>
                <button class="tab-btn" data-category="cabeca">
                    <span class="tab-icon">🧢</span> Cabeça
                </button>
                <button class="tab-btn" data-category="corpo">
                    <span class="tab-icon">🧥</span> Corpo
                </button>
                <button class="tab-btn" data-category="calcado">
                    <span class="tab-icon">👟</span> Calçados
                </button>
                <button class="tab-btn" data-category="acessorio">
                    <span class="tab-icon">🕶️</span> Acessórios
                </button>
                <button class="tab-btn" data-category="ferramenta">
                    <span class="tab-icon">🔍</span> Ferramentas
                </button>
            </div>

            <!-- Items Grid -->
            <div class="items-grid" id="itemsGrid">
                <?php foreach ($todosItens as $it): ?>
                    <?php 
                    $temPosse = !empty($it["posse_id"]);
                    $estaEquipado = $it["equipado"] == 1;
                    $podeComprar = (int)$usuario["xp"] >= (int)$it["preco_xp"];
                    $catNome = $categoriasMeta[$it["categoria"]]['nome'] ?? $it["categoria"];
                    ?>
                    
                    <article class="item-card <?= $estaEquipado ? 'card-equipped' : ($temPosse ? 'card-owned' : '') ?>" 
                             data-category="<?= htmlspecialchars($it['categoria']) ?>">
                        
                        <!-- Header with Category & Price Badge -->
                        <div class="card-header">
                            <span class="category-badge cat-<?= htmlspecialchars($it['categoria']) ?>">
                                <?= htmlspecialchars($catNome) ?>
                            </span>
                            <span class="price-tag <?= $temPosse ? 'price-owned' : ($podeComprar ? 'price-affordable' : 'price-expensive') ?>">
                                <?= $temPosse ? 'Adquirido' : number_format($it['preco_xp']) . ' XP' ?>
                            </span>
                        </div>

                        <!-- Item Icon Preview Box -->
                        <div class="item-preview" style="--item-color: <?= htmlspecialchars($it['cor']) ?>">
                            <div class="item-icon-glow"></div>
                            <?php $imagemItem = !empty($it['imagem']) && is_file(__DIR__ . '/img/vestuario/' . $it['imagem']); ?>
                            <?php if ($imagemItem): ?>
                                <img class="item-image" src="<?= APP_BASE_URL ?>/img/vestuario/<?= rawurlencode($it['imagem']) ?>" alt="<?= htmlspecialchars($it['nome']) ?>">
                            <?php else: ?>
                                <span class="item-symbol"><?= htmlspecialchars($it['simbolo']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Title & Description -->
                        <div class="item-details">
                            <h3><?= htmlspecialchars($it['nome']) ?></h3>
                            <p><?= htmlspecialchars($it['descricao'] ?? 'Acessório para o mascote detetive.') ?></p>
                        </div>

                        <!-- Action Form Buttons -->
                        <div class="item-actions">
                            <?php if ($estaEquipado): ?>
                                <button class="btn-action btn-equipped" disabled>Equipado ✓</button>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                    <input type="hidden" name="acao" value="desequipar">
                                    <button type="submit" class="btn-action btn-unequip">Tirar</button>
                                </form>
                            <?php elseif ($temPosse): ?>
                                <form method="POST" class="w-100">
                                    <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                    <input type="hidden" name="acao" value="equipar">
                                    <button type="submit" class="btn-action btn-equip">Equipar</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="w-100">
                                    <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                    <input type="hidden" name="acao" value="comprar">
                                    <button type="submit" 
                                            class="btn-action <?= $podeComprar ? 'btn-buy' : 'btn-locked' ?>" 
                                            <?= !$podeComprar ? 'disabled' : '' ?>>
                                        <?= $podeComprar ? 'Comprar (' . $it['preco_xp'] . ' XP)' : 'Faltam ' . ($it['preco_xp'] - (int)$usuario['xp']) . ' XP' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

        </main>

    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabButtons = document.querySelectorAll(".category-tabs .tab-btn");
    const itemCards = document.querySelectorAll("#itemsGrid .item-card");

    tabButtons.forEach(btn => {
        btn.addEventListener("click", function () {
            tabButtons.forEach(b => b.classList.remove("active"));
            this.classList.add("active");

            const selectedCategory = this.getAttribute("data-category");

            itemCards.forEach(card => {
                const cardCategory = card.getAttribute("data-category");
                if (selectedCategory === "todos" || cardCategory === selectedCategory) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
