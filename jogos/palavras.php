<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";
exigirLogin();
$bancos = [
 [["nome"=>"Prefixo","frase"=>"feliz + in = ?","instrucao"=>"Qual palavra completa a transformação?","opcoes"=>["infeliz","felizmente","infelizmente","felicidade"],"resposta"=>"infeliz"],["nome"=>"Prefixo","frase"=>"possível + im = ?","instrucao"=>"Qual palavra recebe o prefixo negativo?","opcoes"=>["impossível","possibilidade","possivelmente","despossível"],"resposta"=>"impossível"]],
 [["nome"=>"Sufixo","frase"=>"feliz + mente = ?","instrucao"=>"Escolha a palavra formada pelo sufixo.","opcoes"=>["infeliz","felizmente","felicidade","felizão"],"resposta"=>"felizmente"],["nome"=>"Sufixo","frase"=>"rápido + mente = ?","instrucao"=>"Que advérbio é formado pelo sufixo?","opcoes"=>["rapidez","rapidamente","enr rápido","irrapido"],"resposta"=>"rapidamente"]],
 [["nome"=>"Derivação","frase"=>"leal + dade = ?","instrucao"=>"Que substantivo nasce dessa base mais o sufixo?","opcoes"=>["lealdade","desleal","lealmente","leitor"],"resposta"=>"lealdade"],["nome"=>"Derivação","frase"=>"real + idade = ?","instrucao"=>"Qual palavra é formada por derivação sufixal?","opcoes"=>["realidade","irreal","realmente","realeza"],"resposta"=>"realidade"]],
 [["nome"=>"Prefixo negativo","frase"=>"feliz + in = ?","instrucao"=>"Use o prefixo que muda o sentido da palavra.","opcoes"=>["infeliz","felizmente","felicidade","refeliz"],"resposta"=>"infeliz"],["nome"=>"Prefixo negativo","frase"=>"legal + i = ?","instrucao"=>"Qual palavra expressa o sentido contrário?","opcoes"=>["ilegal","legalmente","legalidade","relegal"],"resposta"=>"ilegal"]],
 [["nome"=>"Parassíntese","frase"=>"noite + en + ecer = ?","instrucao"=>"Qual verbo indica tornar-se noite?","opcoes"=>["anoitecer","noiteiro","desnoite","noturno"],"resposta"=>"anoitecer"],["nome"=>"Parassíntese","frase"=>"triste + en + ecer = ?","instrucao"=>"Qual verbo indica tornar-se triste?","opcoes"=>["entristecer","tristeza","tristemente","destriste"],"resposta"=>"entristecer"]],
 [["nome"=>"Desafio final","frase"=>"feliz → infelizmente","instrucao"=>"Identifique a formação usada.","opcoes"=>["derivação prefixal e sufixal","composição por justaposição","abreviação","onomatopeia"],"resposta"=>"derivação prefixal e sufixal"],["nome"=>"Desafio final","frase"=>"des + leal + dade = ?","instrucao"=>"Qual processo combina prefixo e sufixo?","opcoes"=>["derivação prefixal e sufixal","composição por aglutinação","sigla","redução"],"resposta"=>"derivação prefixal e sufixal"]]
];
$usuarioAtual = usuarioId();
$questoesGeradas = $_SESSION["palavras_geradas"] ?? [];
foreach ($questoesGeradas as $nivelGerado => $questaoGerada) { $bancos[$nivelGerado][] = $questaoGerada; }
$selecionadas = $_SESSION["palavras_selecionadas"] ?? null;
if ($selecionadas === null) { $selecionadas = []; foreach ($bancos as $nivel => $banco) { $opcoes = array_keys($banco); shuffle($opcoes); foreach ($opcoes as $opcao) { if (!questaoJaRespondida($conn, $usuarioAtual, "portugues-{$nivel}-{$opcao}")) { $selecionadas[$nivel] = $opcao; break; } } if (!isset($selecionadas[$nivel])) { $variacoes = [["nome"=>"Prefixo","frase"=>"des + fazer = ?","instrucao"=>"Qual palavra é formada pelo prefixo?","opcoes"=>["desfazer","fazermente","refazer","fazedor"],"resposta"=>"desfazer"],["nome"=>"Sufixo","frase"=>"casa + eiro = ?","instrucao"=>"Qual palavra nasce com o sufixo indicado?","opcoes"=>["caseiro","casarão","descasa","casual"],"resposta"=>"caseiro"],["nome"=>"Derivação","frase"=>"pedra + eiro = ?","instrucao"=>"Identifique a palavra derivada.","opcoes"=>["pedreiro","pedregulho","pedraço","pedrinha"],"resposta"=>"pedreiro"],["nome"=>"Prefixo negativo","frase"=>"regular + ir = ?","instrucao"=>"Qual palavra expressa o sentido contrário?","opcoes"=>["irregular","regularidade","regularmente","regulador"],"resposta"=>"irregular"],["nome"=>"Parassíntese","frase"=>"velho + en + ecer = ?","instrucao"=>"Qual verbo indica tornar-se velho?","opcoes"=>["envelhecer","velhice","velhamente","desvelho"],"resposta"=>"envelhecer"],["nome"=>"Desafio final","frase"=>"des + leal + dade = ?","instrucao"=>"Qual processo combina prefixo e sufixo?","opcoes"=>["derivação prefixal e sufixal","composição","sigla","redução"],"resposta"=>"derivação prefixal e sufixal"]]; $questaoGerada = $variacoes[$nivel]; $bancos[$nivel][] = $questaoGerada; $questoesGeradas[$nivel] = $questaoGerada; $_SESSION["palavras_geradas"] = $questoesGeradas; $selecionadas[$nivel] = count($bancos[$nivel]) - 1; } } $_SESSION["palavras_selecionadas"] = $selecionadas; }
$dificuldades = ["Fácil", "Fácil", "Médio", "Médio", "Difícil", "Avançado"];
$fases = []; foreach ($bancos as $nivel => $banco) { $fases[] = array_merge($banco[$selecionadas[$nivel]], ["id" => "portugues-{$nivel}-{$selecionadas[$nivel]}", "xp" => 15 + ($nivel * 6), "dificuldade" => $dificuldades[$nivel]]); }
$fase = (int) ($_SESSION["palavras_fase"] ?? 0);
$pontos = (int) ($_SESSION["palavras_pontos"] ?? 0);
$mensagem = "";
$classe = "";
$finalizado = false;
$stmtXp = $conn->prepare("SELECT nome, nivel, xp FROM usuarios WHERE id = ?");
$stmtXp->bind_param("i", $usuarioAtual);
$stmtXp->execute();
$aluno = $stmtXp->get_result()->fetch_assoc() ?? [];
$xpTotal = (int) ($aluno["xp"] ?? 0);
if (isset($_GET["reiniciar"])) {
    unset(
        $_SESSION["palavras_fase"],
        $_SESSION["palavras_pontos"],
        $_SESSION["palavras_selecionadas"],
        $_SESSION["palavras_feedback"],
        $_SESSION["palavras_finalizado"],
        $_SESSION["palavras_finalizado_pontos"]
    );
    header("Location: palavras.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($fases[$fase])) {
    if (($_POST["resposta"] ?? "") === $fases[$fase]["resposta"]) {
        $xpFase = (int) $fases[$fase]["xp"];
        $pontos += $xpFase;
        adicionarXP($conn, $usuarioAtual, $xpFase);
        marcarQuestaoRespondida($conn, $usuarioAtual, $fases[$fase]["id"], "Laboratório das Palavras");
        $fase++;
        $_SESSION["palavras_fase"] = $fase;
        $mensagem = "Mistura correta. Você ganhou {$xpFase} XP.";
        $classe = "success";
    } else {
        $mensagem = "Essa combinação ainda não forma a palavra certa.";
        $classe = "error";
    }

    $_SESSION["palavras_pontos"] = $pontos;
    if ($fase >= count($fases)) {
        registrarPartida($conn, $usuarioAtual, "Laboratório das Palavras", "Avançado", $pontos, count($fases), 0, false);
        $finalizado = true;
        $_SESSION["palavras_finalizado"] = true;
        $_SESSION["palavras_finalizado_pontos"] = $pontos;
        unset($_SESSION["palavras_fase"], $_SESSION["palavras_pontos"], $_SESSION["palavras_selecionadas"]);
    }

    $_SESSION["palavras_feedback"] = ["mensagem" => $mensagem, "classe" => $classe];
    header("Location: palavras.php");
    exit;
}

$feedback = $_SESSION["palavras_feedback"] ?? [];
$mensagem = $feedback["mensagem"] ?? "";
$classe = $feedback["classe"] ?? "";
$finalizado = (bool) ($_SESSION["palavras_finalizado"] ?? false);
if ($finalizado) {
    $pontos = (int) ($_SESSION["palavras_finalizado_pontos"] ?? 0);
}
unset($_SESSION["palavras_feedback"], $_SESSION["palavras_finalizado"], $_SESSION["palavras_finalizado_pontos"]);

$questao = $fases[$fase] ?? null;
$totalFases = count($fases);
$fase = $finalizado ? $totalFases : $fase;
$etapaAtual = min($fase + 1, max(1, $totalFases));
$progressoExperimento = $totalFases > 0 ? min(100, ($fase / $totalFases) * 100) : 0;
?>
<div class="words-app">
    <aside class="words-sidebar">
        <a class="words-brand" href="../index.php" aria-label="MentePlay, início">
            <span><span class="words-brand-title">MENTE <strong>PLAY</strong></span><small>A LÓGICA LEVA À VERDADE</small></span>
            <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M23 8c-2-4-8-4-10 1-5-1-8 4-6 8-4 3-3 9 1 11-1 5 3 9 8 8 2 4 7 4 9 1V9m2-1c2-4 8-4 10 1 5-1 8 4 6 8 4 3 3 9-1 11 1 5-3 9-8 8-2 4-7 4-9 1V9M15 15c4 0 5 3 5 5m-10 8c4-2 7 0 8 3m20-16c-4 0-5 3-5 5m10 8c-4-2-7 0-8 3M24 14v23m-7-17 6 3m8-3-6 3m-8 7 6 2m8-2-6 2"/></svg>
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
