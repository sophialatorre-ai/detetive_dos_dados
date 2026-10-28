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

$stmtXp = $conn->prepare("SELECT nome, nivel, xp FROM usuarios WHERE id = ?");
$stmtXp->bind_param("i", $usuarioAtual);
$stmtXp->execute();
$aluno = $stmtXp->get_result()->fetch_assoc() ?? [];
$xpTotal = (int) ($aluno["xp"] ?? 0);
$questao = $questoes[$fase] ?? null;
$totalFases = count($questoes);
$etapaAtual = min($fase + 1, max(1, $totalFases));
$progressoExperimento = $totalFases > 0 ? min(100, ($fase / $totalFases) * 100) : 0;
require "../includes/header.php";
?>
<div class="words-app">
    <aside class="words-sidebar">
        <a class="words-brand" href="../index.php" aria-label="MentePlay, início">
            <span><span class="words-brand-title">MENTE <strong>PLAY</strong></span><small>A LÓGICA LEVA À VERDADE</small></span>
            <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M23 8c-2-4-8-4-10 1-5-1-8 4-6 8-4 3-3 9 1 11-1 5 3 9 8 8 2 4 7 4 9 1V9m2-1c2-4 8-4 10 1 5-1 8 4 6 8 4 3 3 9-1 11 1 5-3 9-8 8-2 4-7 4-9 1V9M15 15c4 0 5 3 5 5m-10 8c4-2 7 0 8 3m20-16c-4 0-5 3-5 5m10 8c-4-2-7-3-8 3M24 14v23m-7-17 6 3m8-3-6 3m-8 7 6 2m8-2-6 2"/></svg>
        </a>
        <nav class="words-nav" aria-label="Navegação principal">
            <a href="../index.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>Início</a>
            <a class="active" href="index.php" aria-current="page"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8 3 5m15 3 3-3M7 17l-3 3m13-3 3 3M5 11a7 7 0 0 1 14 0v4a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3z"/><path d="M9 12h.1M15 12h.1M9 15h6"/></svg>Jogos</a>
            <a href="../conteudos.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="m10 9 5 3-5 3z"/></svg>Videoaulas</a>
            <a href="../medalhas.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 8.6l6.2-.9z"/></svg>Medalhas</a>
            <a href="../perfil.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 13.5a7 7 0 0 0 0-3l2-1.5-2-3.4-2.3 1a7 7 0 0 0-2.6-1.5L13.7 3h-4l-.4 2.1a7 7 0 0 0-2.6 1.5l-2.3-1-2 3.4 2 1.5a7 7 0 0 0 0 3l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 2.6 1.5l.4 2.1h4l.4-2.1a7 7 0 0 0 2.6-1.5l2.3 1 2-3.4z"/></svg>Configurações</a>
        </nav>
    </aside>

    <div class="words-main">
        <header class="words-welcome">
            <div>
                <h1>Olá, <?= htmlspecialchars($aluno["nome"] ?? "estudante", ENT_QUOTES, "UTF-8") ?>!</h1>
                <p>Continue explorando, cada desafio te deixa mais forte!</p>
            </div>
            <div class="words-account">
                <span class="words-xp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 8.6l6.2-.9z"/></svg><?= $xpTotal ?> XP</span>
                <a class="words-avatar" href="../perfil.php" aria-label="Abrir perfil"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></a>
            </div>
        </header>

        <section class="words-hero" aria-labelledby="words-title">
            <div class="words-hero-copy">
                <span class="words-kicker">FORMAÇÃO DE PALAVRAS · DESAFIOS</span>
                <h2 id="words-title"><svg viewBox="0 0 48 52" aria-hidden="true"><path d="M17 4h14M20 4v13L7 39a6 6 0 0 0 5 9h24a6 6 0 0 0 5-9L28 17V4" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M13 37h22l5 7H8z" fill="#f4a36c"/></svg>Laboratório das Palavras</h2>
                <p>Leia, misture e descubra o sentido de cada palavra.</p>
            </div>
            <img class="words-hero-art" src="../assets/laboratorio-livros.svg" alt="Ilustração de livros empilhados com pequenos brilhos">
            <div class="words-progress">
                <div class="words-progress-heading"><span>PROGRESSO DO EXPERIMENTO</span><strong><?= $fase ?> / <?= $totalFases ?></strong></div>
                <div class="words-progress-row">
                    <span class="words-level-node">N<?= (int) ($aluno["nivel"] ?? 1) ?></span>
                    <div class="words-progress-track" role="progressbar" aria-label="Progresso do experimento" aria-valuemin="0" aria-valuemax="<?= $totalFases ?>" aria-valuenow="<?= min($fase, $totalFases) ?>"><span style="width:<?= $progressoExperimento ?>%"></span></div>
                    <span class="words-stage-node"><?= $finalizado ? $totalFases : $etapaAtual ?>/<?= $totalFases ?></span>
                </div>
            </div>
        </section>

        <section class="words-question-card" aria-label="Questão do laboratório">
            <aside class="words-case-note">
                <span>NÍVEL <?= (int) ($aluno["nivel"] ?? 1) ?></span>
                <strong>O ENIGMA DAS PALAVRAS</strong>
                <p>Experimento <?= min($fase + 1, $totalFases) ?> de <?= $totalFases ?><br>A dificuldade aumenta a cada nível.</p>
                <?php if ($fase > 0): ?><a href="palavras.php?reiniciar=1">Recomeçar experimento</a><?php endif; ?>
                <svg viewBox="0 0 100 76" aria-hidden="true"><path d="M8 65h84M18 61V38l13-10 13 10v23M50 61V20l15-9 15 9v41M55 28h20m-20 9h20m-20 9h13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="31" cy="48" r="4" fill="currentColor"/></svg>
            </aside>
            <div class="words-question-content">
                <?php if ($finalizado): ?>
                    <div class="words-question-heading"><strong>Laboratório concluído</strong><span class="words-question-xp">OK</span></div>
                    <div class="words-clue"><h2>Você terminou o experimento!</h2><p>Você dominou prefixos, sufixos e derivação.</p><strong><?= $pontos ?> XP conquistados</strong></div>
                    <a class="words-submit" href="palavras.php?reiniciar=1">Jogar novamente</a>
                <?php elseif ($questao): ?>
                    <div class="words-question-heading"><strong>Nível <?= (int) ($aluno["nivel"] ?? 1) ?> · Etapa <?= $etapaAtual ?></strong><span class="words-question-xp">XP <?= (int) $questao["xp"] ?></span></div>
                    <div class="words-clue"><h2><?= htmlspecialchars($questao["frase"], ENT_QUOTES, "UTF-8") ?></h2></div>
                    <p class="words-instruction"><?= htmlspecialchars($questao["instrucao"], ENT_QUOTES, "UTF-8") ?></p>
                    <?php if ($mensagem): ?><div class="stage-feedback <?= htmlspecialchars($classe, ENT_QUOTES, "UTF-8") ?>" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
                    <form method="POST" class="words-answer-form">
                        <div class="words-answer-grid">
                            <?php foreach ($questao["opcoes"] as $opcao): ?>
                                <label><input type="radio" name="resposta" value="<?= htmlspecialchars($opcao, ENT_QUOTES, "UTF-8") ?>" required><span><?= htmlspecialchars($opcao, ENT_QUOTES, "UTF-8") ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <button class="words-submit" type="submit">Confirmar resposta</button>
                    </form>
                <?php else: ?>
                    <div class="words-clue"><h2>Não há etapas disponíveis agora.</h2><p>Recomece o experimento para continuar praticando.</p></div>
                    <a class="words-submit" href="palavras.php?reiniciar=1">Recomeçar experimento</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
