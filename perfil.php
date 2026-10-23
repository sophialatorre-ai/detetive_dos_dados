<?php

require_once "includes/config.php";
require_once "includes/auth.php";

exigirLogin();

$usuarioId = (int) $_SESSION["usuario_id"];
$_SESSION["profile_csrf"] ??= bin2hex(random_bytes(32));
$mensagem = $_SESSION["profile_message"] ?? "";
$tipoMensagem = $_SESSION["profile_message_type"] ?? "success";
unset($_SESSION["profile_message"], $_SESSION["profile_message_type"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf = $_POST["csrf_token"] ?? "";

    if (!is_string($csrf) || !hash_equals($_SESSION["profile_csrf"], $csrf)) {
        http_response_code(403);
        $mensagem = "A sessão expirou. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";
    } elseif (($_POST["action"] ?? "") === "save_accessibility") {
        $vision = $_POST["problema_visao"] ?? "";
        $fontSize = filter_input(INPUT_POST, "tamanho_letra", FILTER_VALIDATE_INT);

        if (!in_array($vision, ["0", "1"], true) || $fontSize === false || !in_array($fontSize, [80, 90, 100, 110, 120, 130, 140, 150, 160], true)) {
            $mensagem = "Escolha uma opção de visão e um tamanho de letra válido.";
            $tipoMensagem = "erro";
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET problema_visao = ?, tamanho_letra = ? WHERE id = ?");
            $visionValue = (int) $vision;
            $stmt->bind_param("iii", $visionValue, $fontSize, $usuarioId);
            $stmt->execute();
            $_SESSION["profile_message"] = "Preferências de acessibilidade atualizadas.";
            $_SESSION["profile_message_type"] = "success";
            header("Location: perfil.php");
            exit;
        }
    } elseif (($_POST["action"] ?? "") === "upload_photo") {
        $file = $_FILES["foto_perfil"] ?? null;
        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        if (!$file || $file["error"] !== UPLOAD_ERR_OK) {
            $mensagem = "Selecione uma foto válida para enviar.";
            $tipoMensagem = "erro";
        } elseif ($file["size"] > 3 * 1024 * 1024) {
            $mensagem = "A foto deve ter no máximo 3 MB.";
            $tipoMensagem = "erro";
        } else {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($file["tmp_name"]);
            $imageSize = @getimagesize($file["tmp_name"]);

            if (!isset($allowedTypes[$mimeType]) || !$imageSize || $imageSize[0] > 4000 || $imageSize[1] > 4000) {
                $mensagem = "Use uma imagem JPG, PNG ou WEBP com até 4000 × 4000 pixels.";
                $tipoMensagem = "erro";
            } else {
                $uploadDirectory = __DIR__ . "/assets/uploads/profiles";
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
                    $mensagem = "Não foi possível preparar o armazenamento da foto.";
                    $tipoMensagem = "erro";
                } else {
                    $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
                    $destination = $uploadDirectory . "/" . $filename;
                    $relativePath = "assets/uploads/profiles/" . $filename;

                    if (!move_uploaded_file($file["tmp_name"], $destination)) {
                        $mensagem = "Não foi possível salvar a foto. Tente novamente.";
                        $tipoMensagem = "erro";
                    } else {
                        try {
                            $stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
                            $stmt->bind_param("i", $usuarioId);
                            $stmt->execute();
                            $oldPhoto = $stmt->get_result()->fetch_assoc()["foto_perfil"] ?? "";

                            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                            $stmt->bind_param("si", $relativePath, $usuarioId);
                            $stmt->execute();

                            if (preg_match("#^assets/uploads/profiles/[a-f0-9]{32}\\.(jpg|png|webp)$#", $oldPhoto) === 1) {
                                $oldPhotoPath = __DIR__ . "/" . $oldPhoto;
                                if (is_file($oldPhotoPath)) {
                                    unlink($oldPhotoPath);
                                }
                            }

                            $_SESSION["profile_message"] = "Foto de perfil atualizada.";
                            $_SESSION["profile_message_type"] = "success";
                            header("Location: perfil.php");
                            exit;
                        } catch (Throwable $exception) {
                            if (is_file($destination)) {
                                unlink($destination);
                            }
                            error_log("Falha ao atualizar foto de perfil: " . $exception->getMessage());
                            $mensagem = "Não foi possível atualizar sua foto agora.";
                            $tipoMensagem = "erro";
                        }
                    }
                }
            }
        }
    }
}

$stmt = $conn->prepare("SELECT nome, email, nivel, xp, criado_em, problema_visao, tamanho_letra, foto_perfil FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc() ?? [];

$stmt = $conn->prepare("SELECT COUNT(*) AS partidas, COALESCE(SUM(acertos), 0) AS acertos, COALESCE(SUM(erros), 0) AS erros, COALESCE(SUM(pontuacao), 0) AS pontos FROM partidas WHERE usuario_id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$estatisticas = $stmt->get_result()->fetch_assoc();
$totalRespostas = (int) $estatisticas["acertos"] + (int) $estatisticas["erros"];
$precisao = $totalRespostas > 0 ? (int) round(((int) $estatisticas["acertos"] / $totalRespostas) * 100) : 0;
$xpAtual = (int) ($usuario["xp"] ?? 0);
$xpNoNivel = $xpAtual % 100;
$fotoPerfil = $usuario["foto_perfil"] ?? "";
$fotoValida = $fotoPerfil !== "" && preg_match("#^assets/uploads/profiles/[a-f0-9]{32}\\.(jpg|png|webp)$#", $fotoPerfil) === 1;

$stmt = $conn->prepare("SELECT jogo, dificuldade, pontuacao, acertos, jogado_em FROM partidas WHERE usuario_id = ? ORDER BY jogado_em DESC LIMIT 8");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$partidas = $stmt->get_result();

$stmt = $conn->prepare("SELECT c.nome, c.descricao, uc.conquistado_em FROM conquistas c LEFT JOIN usuario_conquistas uc ON uc.conquista_id = c.id AND uc.usuario_id = ? ORDER BY uc.conquistado_em DESC, c.id ASC");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$medalhas = $stmt->get_result();

require "includes/header.php";
?>

<section class="page-frame profile-page">
    <header class="profile-page-heading">
        <span class="eyebrow">SEU ESPAÇO</span>
        <h1>Perfil e progresso</h1>
        <p>Acompanhe sua jornada no MentePlay e personalize sua experiência.</p>
    </header>

    <?php if ($mensagem): ?>
        <div class="profile-message <?= htmlspecialchars($tipoMensagem, ENT_QUOTES, "UTF-8") ?>" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <section class="profile-overview panel" aria-label="Informações da conta">
        <div class="profile-photo-column">
            <div class="profile-avatar">
                <?php if ($fotoValida): ?>
                    <img src="<?= htmlspecialchars(APP_BASE_URL . "/" . $fotoPerfil, ENT_QUOTES, "UTF-8") ?>" alt="Foto de perfil de <?= htmlspecialchars($usuario["nome"] ?? "", ENT_QUOTES, "UTF-8") ?>">
                <?php else: ?>
                    <span aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($usuario["nome"] ?? "MP", 0, 1)), ENT_QUOTES, "UTF-8") ?></span>
                <?php endif; ?>
            </div>
            <span class="profile-photo-caption">FOTO DE PERFIL</span>
        </div>
        <div class="profile-identity">
            <span class="eyebrow">CONTA MENTEPLAY</span>
            <h2><?= htmlspecialchars($usuario["nome"] ?? "", ENT_QUOTES, "UTF-8") ?></h2>
            <p><?= htmlspecialchars($usuario["email"] ?? "", ENT_QUOTES, "UTF-8") ?></p>
            <p>Na equipe desde <?= htmlspecialchars(date("d/m/Y", strtotime($usuario["criado_em"] ?? "now")), ENT_QUOTES, "UTF-8") ?></p>
        </div>
        <div class="profile-level">
            <span>NÍVEL ATUAL</span>
            <strong><?= (int) ($usuario["nivel"] ?? 1) ?></strong>
            <small><?= $xpAtual ?> XP no total</small>
        </div>
    </section>

    <section class="profile-settings-grid" aria-label="Personalização do perfil">
        <form class="panel profile-photo-form" method="POST" enctype="multipart/form-data" id="profile-photo-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["profile_csrf"], ENT_QUOTES, "UTF-8") ?>">
            <input type="hidden" name="action" value="upload_photo">
            <h2>Foto de perfil</h2>
            <p>Escolha uma imagem JPG, PNG ou WEBP de até 3 MB.</p>
            <label class="photo-dropzone" id="photo-dropzone" for="foto_perfil">
                <span class="photo-preview" id="photo-preview" aria-hidden="true">+</span>
                <span><strong>Arraste uma foto até aqui</strong><small>ou clique para escolher um arquivo</small></span>
                <input type="file" name="foto_perfil" id="foto_perfil" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <span class="selected-photo-name" id="selected-photo-name" aria-live="polite">Nenhum arquivo escolhido</span>
            <button class="profile-action-button" type="submit">Salvar foto</button>
        </form>

        <form class="panel accessibility-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["profile_csrf"], ENT_QUOTES, "UTF-8") ?>">
            <input type="hidden" name="action" value="save_accessibility">
            <h2>Acessibilidade visual</h2>
            <p>Altere estas opções quando quiser; elas acompanham sua conta em todas as páginas.</p>
            <fieldset>
                <legend>Deseja manter os elementos ampliados?</legend>
                <label><input type="radio" name="problema_visao" value="1" <?= (int) ($usuario["problema_visao"] ?? 0) === 1 ? "checked" : "" ?>> Sim, ampliar a interface</label>
                <label><input type="radio" name="problema_visao" value="0" <?= (int) ($usuario["problema_visao"] ?? 0) === 0 ? "checked" : "" ?>> Não, usar o tamanho padrão</label>
            </fieldset>
            <label for="tamanho_letra">Tamanho da interface</label>
            <div class="font-size-controls" aria-label="Ajustar tamanho da interface">
                <button type="button" class="font-size-step" data-step="-10" aria-label="Diminuir tamanho da letra">A−</button>
            <select name="tamanho_letra" id="tamanho_letra">
                <?php foreach ([80, 90, 100, 110, 120, 130, 140, 150, 160] as $fontSize): ?>
                    <option value="<?= $fontSize ?>" <?= (int) ($usuario["tamanho_letra"] ?? 100) === $fontSize ? "selected" : "" ?>><?= $fontSize ?>%</option>
                <?php endforeach; ?>
            </select>
                <button type="button" class="font-size-step" data-step="10" aria-label="Aumentar tamanho da letra">A+</button>
            </div>
            <button class="profile-action-button" type="submit">Salvar acessibilidade</button>
        </form>
    </section>

    <section class="profile-progress-section" aria-labelledby="profile-progress-title">
        <div class="section-title">
            <span class="eyebrow">SUA JORNADA</span>
            <h2 id="profile-progress-title">Progresso nos jogos</h2>
            <p>Veja sua experiência, desempenho e partidas recentes.</p>
        </div>
        <div class="profile-xp panel">
            <div><span class="eyebrow">PROGRESSO DO NÍVEL <?= (int) ($usuario["nivel"] ?? 1) ?></span><h2><?= $xpAtual ?> <small>XP acumulados</small></h2></div>
            <strong class="xp-next"><?= $xpNoNivel ?>/100</strong>
            <div class="profile-progress" role="progressbar" aria-label="Progresso até o próximo nível" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $xpNoNivel ?>"><span style="width:<?= $xpNoNivel ?>%"></span></div>
            <small class="profile-xp-hint"><?= 100 - $xpNoNivel ?> XP para o próximo nível</small>
        </div>
        <div class="profile-stats">
            <article class="panel profile-stat"><span>PARTIDAS</span><strong><?= (int) $estatisticas["partidas"] ?></strong><small>jogadas</small></article>
            <article class="panel profile-stat"><span>ACERTOS</span><strong><?= (int) $estatisticas["acertos"] ?></strong><small>respostas corretas</small></article>
            <article class="panel profile-stat"><span>PRECISÃO</span><strong><?= $precisao ?>%</strong><small>de respostas corretas</small></article>
            <article class="panel profile-stat"><span>PONTOS</span><strong><?= (int) $estatisticas["pontos"] ?></strong><small>XP conquistados em partidas</small></article>
        </div>
        <section class="panel recent-games">
            <div class="panel-heading"><div><span class="eyebrow">HISTÓRICO</span><h2>Partidas recentes</h2></div><a href="medalhas.php">Ver medalhas</a></div>
            <?php if ($partidas->num_rows > 0): ?>
                <div class="recent-games-table-wrap">
                    <table class="recent-games-table">
                        <thead><tr><th>Jogo</th><th>Dificuldade</th><th>Resultado</th><th>Data</th></tr></thead>
                        <tbody>
                        <?php while ($partida = $partidas->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($partida["jogo"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars($partida["dificuldade"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= (int) $partida["pontuacao"] ?> XP · <?= (int) $partida["acertos"] ?> acertos</td>
                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($partida["jogado_em"])), ENT_QUOTES, "UTF-8") ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Suas partidas aparecerão aqui quando você concluir seu primeiro jogo.</p>
            <?php endif; ?>
        </section>
    </section>

    <section class="panel achievements-panel">
        <div class="panel-heading"><div><span class="eyebrow">COLEÇÃO</span><h2>Conquistas desbloqueadas</h2></div><a href="medalhas.php">Ver todas as medalhas</a></div>
        <?php if ($medalhas->num_rows > 0): ?>
            <div class="profile-medal-list">
                <?php while ($medalha = $medalhas->fetch_assoc()): ?>
                    <?php if ($medalha["conquistado_em"] ?? false): ?>
                        <article><img src="<?= APP_BASE_URL ?>/assets/medalha-conquista.png" alt="" aria-hidden="true"><div><strong><?= htmlspecialchars($medalha["nome"], ENT_QUOTES, "UTF-8") ?></strong><p><?= htmlspecialchars($medalha["descricao"], ENT_QUOTES, "UTF-8") ?></p></div></article>
                    <?php endif; ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">Você ainda não conquistou medalhas. Continue jogando para desbloqueá-las.</p>
        <?php endif; ?>
    </section>
</section>

<script src="<?= APP_BASE_URL ?>/js/profile-photo.js" defer></script>
<?php require "includes/footer.php"; ?>
