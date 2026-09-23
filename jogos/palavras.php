<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";
exigirLogin();

function gerarQuestaoPalavras($numero)
{
    $nivel = intdiv($numero - 1, 10) + 1;
    $ordem = (($numero - 1) % 10) + 1;
    $xp = 12 + ($nivel * 4);
    $bases = ["feliz", "legal", "real", "leal", "capaz", "regular", "possível", "honesto", "ativo", "moral", "natural", "central", "comum", "certo", "justo", "claro", "forte", "calmo", "útil", "igual", "humano", "formal", "visível", "social", "história", "terra", "pedra", "casa", "livro", "jardim"];
    $base = $bases[($numero - 1) % count($bases)];
    $codigo = "Pista linguística {$numero}";

    if ($nivel <= 5) {
        $prefixos = ["in", "i", "des", "re", "im"];
        $prefixo = $prefixos[($ordem - 1) % count($prefixos)];
        $resposta = $prefixo . $base;
        $texto = "{$codigo}: aplique o prefixo '{$prefixo}-' à palavra '{$base}'. Qual palavra é formada?";
        $opcoes = [$resposta, $base . "mente", "des" . $base, $base . "dade"];
    } elseif ($nivel <= 10) {
        $sufixos = ["eiro", "ista", "dade", "ção", "oso", "mente", "ura", "inho", "al", "ismo"];
        $sufixo = $sufixos[($ordem - 1) % count($sufixos)];
        $resposta = $base . $sufixo;
        $texto = "{$codigo}: acrescente o sufixo '-{$sufixo}' à base '{$base}'. Qual derivada corresponde?";
        $opcoes = [$resposta, "in" . $base, $base . "mente", "des" . $base];
    } elseif ($nivel <= 15) {
        $resposta = $base . "mente";
        $texto = "{$codigo}: qual advérbio é formado a partir de '{$base}' com o sufixo '-mente'?";
        $opcoes = [$resposta, "in" . $base, $base . "dade", "des" . $base];
    } elseif ($nivel <= 20) {
        $verbos = ["anoitecer", "envelhecer", "entristecer", "endurecer", "enriquecer", "amadurecer", "apodrecer", "esverdear", "avermelhar", "empobrecer"];
        $resposta = $verbos[($numero - 1) % count($verbos)];
        $texto = "{$codigo}: identifique o verbo formado por parassíntese relacionado a '{$base}'.";
        $opcoes = [$resposta, $base . "mente", "des" . $base, $base . "dade"];
    } elseif ($nivel <= 25) {
        $processos = [["infelizmente", "derivação prefixal e sufixal", "composição por aglutinação", "sigla", "abreviação"], ["deslealdade", "derivação prefixal e sufixal", "derivação regressiva", "onomatopeia", "hibridismo"], ["guarda-chuva", "composição por justaposição", "derivação parassintética", "abreviação", "sigla"], ["planalto", "composição por aglutinação", "composição por justaposição", "derivação sufixal", "onomatopeia"], ["foto", "abreviação", "derivação prefixal", "composição", "parassíntese"], ["ONU", "sigla", "neologismo", "derivação imprópria", "composição"], ["tic-tac", "onomatopeia", "aglutinação", "derivação prefixal", "abreviação"], ["envelhecer", "derivação parassintética", "composição por justaposição", "sufixação", "sigla"], ["couve-flor", "composição por justaposição", "derivação regressiva", "abreviação", "hibridismo"], ["aguardente", "composição por aglutinação", "composição por justaposição", "prefixação", "onomatopeia"]];
        $processo = $processos[($numero - 1) % count($processos)];
        $resposta = $processo[1];
        $texto = "{$codigo}: observe a formação da palavra '{$processo[0]}'. Qual processo foi usado?";
        $opcoes = [$resposta, $processo[2], $processo[3], $processo[4]];
    } else {
        $pares = [["guarda", "chuva", "guarda-chuva"], ["passa", "tempo", "passatempo"], ["segunda", "feira", "segunda-feira"], ["bem", "te", "bem-te-vi"], ["couve", "flor", "couve-flor"], ["beija", "flor", "beija-flor"], ["alto", "falante", "alto-falante"], ["porta", "retrato", "porta-retrato"], ["livre", "iro", "livreiro"], ["água", "ardente", "aguardente"]];
        $par = $pares[($numero - 1) % count($pares)];
        $resposta = $par[2];
        $texto = "{$codigo}: una '{$par[0]}' e '{$par[1]}'. Qual palavra resulta dessa formação?";
        $opcoes = [$resposta, $par[0] . "mente", "in" . $par[0], $par[1] . "dade"];
    }

    return ["nome" => "Nível {$nivel} · Etapa {$ordem}", "frase" => $texto, "instrucao" => "Escolha a alternativa correta.", "opcoes" => array_values(array_unique($opcoes)), "resposta" => $resposta, "nivel" => $nivel, "ordem" => $ordem, "xp" => $xp, "id" => "palavras-" . str_pad((string) $numero, 3, "0", STR_PAD_LEFT)];
}

$questoes = [];
for ($numero = 1; $numero <= 300; $numero++) {
    $questoes[] = gerarQuestaoPalavras($numero);
}

$usuarioAtual = usuarioId();
$fase = (int) ($_SESSION["palavras_fase"] ?? 0);
while ($fase < count($questoes) && questaoJaRespondida($conn, $usuarioAtual, $questoes[$fase]["id"])) {
    $fase++;
}
$pontos = (int) ($_SESSION["palavras_pontos"] ?? 0);
$mensagem = "";
$classe = "";
$finalizado = false;

if (isset($_GET["reiniciar"])) {
    unset($_SESSION["palavras_fase"], $_SESSION["palavras_pontos"]);
    header("Location: palavras.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($questoes[$fase])) {
    $questaoAtual = $questoes[$fase];
    if (($_POST["resposta"] ?? "") === $questaoAtual["resposta"]) {
        $pontos += $questaoAtual["xp"];
        adicionarXP($conn, $usuarioAtual, $questaoAtual["xp"]);
        marcarQuestaoRespondida($conn, $usuarioAtual, $questaoAtual["id"], "Laboratório das Palavras");
        $fase++;
        $_SESSION["palavras_fase"] = $fase;
        $_SESSION["palavras_pontos"] = $pontos;
        $mensagem = "Descoberta correta. Você ganhou {$questaoAtual["xp"]} XP.";
        $classe = "success";
    } else {
        $mensagem = "Essa combinação ainda não forma a palavra certa.\n" . mensagemMotivacionalErro();
        $classe = "error";
    }
    if ($fase >= count($questoes)) {
        registrarPartida($conn, $usuarioAtual, "Laboratório das Palavras", "Nível 30", $pontos, 300, 0, false);
        $finalizado = true;
        unset($_SESSION["palavras_fase"], $_SESSION["palavras_pontos"]);
    }
}

require "../includes/header.php";
$questao = $questoes[$fase] ?? null;
$recompensa = null;
?>
<section class="game-shell words-shell"><aside class="game-sidebar"><a class="side-brand" href="../index.php">DETETIVE<br><b>DOS DADOS</b></a><a class="side-link" href="media.php">Detetive dos Dados</a><a class="side-link active" href="palavras.php">Laboratório</a><a class="side-link" href="../conteudos.php">Videoaulas</a><a class="side-link" href="../perfil.php">Recompensas</a></aside><main class="game-main"><div class="game-topline"><span>LABORATÓRIO · NÍVEL <?= $questao["nivel"] ?? 30 ?></span><strong><?= $pontos ?> XP</strong></div><div class="game-title"><span class="section-tag">FORMAÇÃO DE PALAVRAS · 30 NÍVEIS</span><h1>Laboratório das Palavras</h1><p>Leia, misture e descubra o sentido de cada palavra.</p></div><div class="game-progress-label"><span>PROGRESSO DO EXPERIMENTO</span><b><?= min($fase + 1, 300) ?> / 300</b></div><div class="case-path case-path-compact"><span class="path-node current">N<?= $questao["nivel"] ?? 30 ?></span><i></i><span class="path-node"><?= $questao["ordem"] ?? 10 ?>/10</span></div><?php if ($finalizado): ?><section class="case-board result-panel"><span class="result-mark">OK</span><h2>Todos os 30 níveis concluídos</h2><p>Você dominou as 300 questões exclusivas.</p><strong class="big-score"><?= $pontos ?> XP</strong><a class="action-button" href="palavras.php?reiniciar=1">Revisar laboratório</a></section><?php elseif ($questao): ?><section class="case-board words-case-board"><div class="case-note"><span>NÍVEL <?= $questao["nivel"] ?></span><strong>O ENIGMA DAS PALAVRAS</strong><p>Experimento <?= $fase + 1 ?> de 300<br>A dificuldade aumenta a cada nível.</p></div><div class="evidence-board"><div class="clue-heading"><span><?= htmlspecialchars($questao["nome"]) ?></span><span class="difficulty-badge">XP <?= $questao["xp"] ?></span></div><div class="word-equation"><?= htmlspecialchars($questao["frase"]) ?></div><p><?= htmlspecialchars($questao["instrucao"]) ?></p><form method="post"><div class="answer-grid"><?php foreach ($questao["opcoes"] as $opcao): ?><label><input type="radio" name="resposta" value="<?= htmlspecialchars($opcao) ?>" required><span><?= htmlspecialchars($opcao) ?></span></label><?php endforeach; ?></div><button class="action-button" type="submit">CONFIRMAR DESCOBERTA</button></form><?php if ($mensagem): ?><div class="game-feedback <?= $classe ?>"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?></div></section><?php endif; ?></main></section>
<?php if ($recompensa): ?><div class="reward-modal" role="dialog" aria-modal="true"><div class="reward-card"><button class="reward-close" type="button" onclick="this.closest('.reward-modal').remove()">×</button><p class="reward-title">RECOMPENSA DO NÍVEL <?= min(30, intdiv($fase, 10)) ?>!</p><div class="reward-chest">✦</div><div class="reward-item-preview" style="color: <?= htmlspecialchars($recompensa["cor"]) ?>"><?= htmlspecialchars($recompensa["simbolo"]) ?></div><h2><?= htmlspecialchars($recompensa["nome"]) ?></h2><strong class="reward-xp">NOVA PEÇA</strong><p>Item desbloqueado para o seu personagem.</p><a class="action-button" href="../perfil.php">VER NO GUARDA-ROUPA</a></div></div><?php endif; ?>
<?php require "../includes/footer.php"; ?>
