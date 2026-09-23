<?php
require_once "../includes/config.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";
exigirLogin();

function gerarQuestaoDados($numero)
{
    $nivel = intdiv($numero - 1, 10) + 1;
    $ordem = (($numero - 1) % 10) + 1;
    $base = 8 + ($numero * 3);
    $xp = 12 + ($nivel * 4);
    $tema = ($numero - 1) % 7;
    switch ($tema) {
        case 0:
            $valores = [$base, $base + 4 + $ordem, $base + 8 + ($ordem * 2), $base + 12 + ($ordem * 3)];
            $media = array_sum($valores) / 4;
            $resposta = fmod($media, 1) === 0.0 ? (string) (int) $media : number_format($media, 1, ",", "");
            $texto = "Registro {$numero}: uma equipe anotou " . implode(", ", $valores) . " ocorrências. Qual é a média aritmética?";
            $opcoes = [$resposta, number_format($media + 5, 1, ",", ""), number_format($media - 5, 1, ",", ""), number_format($media + 10, 1, ",", "")];
            break;
        case 1:
            $resposta = (string) ($base + $ordem);
            $texto = "Tabela {$numero}: os valores observados foram {$resposta}, " . ($base + 2) . ", {$resposta}, " . ($base + 5) . ", {$resposta} e " . ($base + 8) . ". Qual é a moda?";
            $opcoes = [$resposta, (string) ($base + 1), (string) ($base + 2), (string) ($base + 4)];
            break;
        case 2:
            $centro = $base + $ordem;
            $valores = [$centro - 9, $centro - 5, $centro - 2, $centro, $centro + 3, $centro + 7, $centro + 11];
            $resposta = (string) $centro;
            $texto = "Arquivo {$numero}: a sequência ordenada é " . implode(", ", $valores) . ". Qual é a mediana?";
            $opcoes = [$resposta, (string) ($centro - 2), (string) ($centro + 3), (string) ($centro + 7)];
            break;
        case 3:
            $menor = 10 + ($numero % 17); $maior = $menor + 18 + $nivel + $ordem;
            $resposta = (string) ($maior - $menor);
            $texto = "Gráfico {$numero}: o menor registro foi {$menor} e o maior foi {$maior}. Qual é a amplitude?";
            $opcoes = [$resposta, (string) ($maior + $menor), (string) ($maior - $menor + 5), (string) ($maior - $menor - 4)];
            break;
        case 4:
            $parte = 20 + (($numero * 7) % 61); $resposta = $parte . "%";
            $texto = "Pesquisa {$numero}: de 100 participantes, {$parte} escolheram a pista azul. Qual porcentagem isso representa?";
            $opcoes = [$resposta, ($parte + 5) . "%", max(1, $parte - 10) . "%", min(99, $parte + 12) . "%"];
            break;
        case 5:
            $grupoA = 2 + ($ordem % 4); $grupoB = 3 + ($nivel % 5); $notaA = 4 + ($numero % 6); $notaB = 6 + (($numero + 3) % 5); $notaC = 8 + (($numero + 1) % 4);
            $media = (($grupoA * $notaA) + ($grupoB * $notaB) + (2 * $notaC)) / ($grupoA + $grupoB + 2);
            $resposta = fmod($media, 1) === 0.0 ? (string) (int) $media : number_format($media, 1, ",", "");
            $texto = "Dossiê {$numero}: {$grupoA} alunos tiraram {$notaA}, {$grupoB} alunos tiraram {$notaB} e 2 alunos tiraram {$notaC}. Qual é a média ponderada?";
            $opcoes = [$resposta, number_format($media + 1, 1, ",", ""), number_format(max(0, $media - 1), 1, ",", ""), number_format($media + 2, 1, ",", "")];
            break;
        default:
            $primeiro = 15 + (($numero * 3) % 40); $segundo = $primeiro + 5 + ($ordem % 6); $terceiro = $segundo + 7 + ($nivel % 5);
            $resposta = (string) $terceiro;
            $texto = "Painel {$numero}: um gráfico mostra {$primeiro} visitas na primeira semana, {$segundo} na segunda e {$terceiro} na terceira. Qual semana teve o maior valor?";
            $opcoes = [$resposta, (string) $primeiro, (string) $segundo, (string) ($terceiro - 3)];
            break;
    }
    return ["texto" => $texto, "opcoes" => array_values(array_unique($opcoes)), "resposta" => $resposta, "nivel" => $nivel, "ordem" => $ordem, "xp" => $xp, "id" => "dados-" . str_pad((string) $numero, 3, "0", STR_PAD_LEFT)];
}

$questoes = [];
for ($numero = 1; $numero <= 300; $numero++) { $questoes[] = gerarQuestaoDados($numero); }
$usuarioAtual = usuarioId();
$indice = (int) ($_SESSION["dados_indice"] ?? 0);
while ($indice < 300 && questaoJaRespondida($conn, $usuarioAtual, $questoes[$indice]["id"])) { $indice++; }
$pontos = (int) ($_SESSION["dados_pontos"] ?? 0); $feedback = ""; $tipo = "";
if (isset($_GET["reiniciar"])) { unset($_SESSION["dados_indice"], $_SESSION["dados_pontos"]); header("Location: media.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($questoes[$indice])) {
    $atual = $questoes[$indice];
    if (($_POST["resposta"] ?? "") === $atual["resposta"]) {
        $pontos += $atual["xp"]; adicionarXP($conn, $usuarioAtual, $atual["xp"]); marcarQuestaoRespondida($conn, $usuarioAtual, $atual["id"], "Detetive dos Dados"); $indice++;
        $_SESSION["dados_indice"] = $indice; $_SESSION["dados_pontos"] = $pontos; $feedback = "Pista encontrada. Você ganhou {$atual["xp"]} XP."; $tipo = "success";
    } else { $feedback = "Essa não é a pista correta. Analise os dados novamente.\n" . mensagemMotivacionalErro(); $tipo = "error"; }
}
require "../includes/header.php";
$questao = $questoes[$indice] ?? null;
?>
<section class="game-shell detective-shell"><aside class="game-sidebar"><a class="side-brand" href="../index.php">DETETIVE<br><b>DOS DADOS</b></a><a class="side-link active" href="media.php">Investigações</a><a class="side-link" href="palavras.php">Laboratório</a><a class="side-link" href="../conteudos.php">Videoaulas</a><a class="side-link" href="../perfil.php">Recompensas</a></aside><main class="game-main"><div class="game-topline"><span>CASO <?= str_pad((string) min($indice + 1, 300), 3, "0", STR_PAD_LEFT) ?> · NÍVEL <?= $questao["nivel"] ?? 30 ?></span><strong><?= $pontos ?> XP</strong></div><div class="game-title"><span class="section-tag">TRILHA MATEMÁTICA · 30 NÍVEIS</span><h1>Detetive dos Dados</h1><p>Analise os dados. Encontre a pista. Resolva o caso.</p></div><div class="game-progress-label"><span>PROGRESSO DA INVESTIGAÇÃO</span><b><?= min($indice + 1, 300) ?> / 300</b></div><div class="case-path case-path-compact"><span class="path-node current">N<?= $questao["nivel"] ?? 30 ?></span><i></i><span class="path-node"><?= $questao["ordem"] ?? 10 ?>/10</span></div><?php if (!$questao): ?><section class="case-board result-panel"><span class="result-mark">OK</span><h2>Todos os 30 níveis concluídos</h2><p>Você solucionou as 300 pistas exclusivas.</p><strong class="big-score"><?= $pontos ?> XP</strong></section><?php else: ?><section class="case-board"><div class="case-note"><span>NÍVEL <?= $questao["nivel"] ?></span><strong>O MISTÉRIO DOS DADOS</strong><p>Questão <?= $indice + 1 ?> de 300<br>A dificuldade aumenta a cada nível.</p></div><div class="evidence-board"><div class="clue-heading"><span>PISTA EXCLUSIVA <?= str_pad((string) ($indice + 1), 3, "0", STR_PAD_LEFT) ?></span><span class="difficulty-badge">XP <?= $questao["xp"] ?></span></div><h2><?= htmlspecialchars($questao["texto"]) ?></h2><form method="post"><div class="answer-grid"><?php foreach ($questao["opcoes"] as $opcao): ?><label><input type="radio" name="resposta" value="<?= htmlspecialchars($opcao) ?>" required><span><?= htmlspecialchars($opcao) ?></span></label><?php endforeach; ?></div><button class="action-button" type="submit">CONFIRMAR PISTA</button></form><?php if ($feedback): ?><div class="game-feedback <?= $tipo ?>"><?= htmlspecialchars($feedback) ?></div><?php endif; ?></div></section><?php endif; ?></main></section>
<?php require "../includes/footer.php"; ?>
