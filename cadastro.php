<?php

require_once "includes/config.php";

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $problemaVisao = $_POST["problema_visao"] ?? "";

    if ($nome === "" || $email === "" || $senha === "" || !in_array($problemaVisao, ["0", "1"], true)) {

        $mensagem = "Preencha todos os campos e responda à pergunta sobre visão.";
        $tipoMensagem = "erro";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = "Digite um e-mail válido.";
        $tipoMensagem = "erro";

    } elseif (strlen($senha) < 6) {

        $mensagem = "A senha precisa ter pelo menos 6 caracteres.";
        $tipoMensagem = "erro";

    } else {

        // Verifica se o e-mail já existe
        $sql = "SELECT id FROM usuarios WHERE email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {

            $mensagem = "Este e-mail já está cadastrado.";
            $tipoMensagem = "erro";

        } else {

            // Criptografa a senha
            $senhaHash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            // Cadastra o usuário
            $sql = "INSERT INTO usuarios
                    (nome, email, senha, nivel, xp, problema_visao, tamanho_letra)
                    VALUES (?, ?, ?, 1, 0, ?, ?)";

            $stmt = $conn->prepare($sql);
            $visao = (int) $problemaVisao;
            $tamanhoLetra = $visao ? 120 : 100;
            $stmt->bind_param("sssii", $nome, $email, $senhaHash, $visao, $tamanhoLetra);

            if ($stmt->execute()) {

                header("Location: login.php?cadastro=sucesso");
                exit;

            } else {

                $mensagem = "Não foi possível criar sua conta.";
                $tipoMensagem = "erro";
            }
        }
    }
}

require "includes/header.php";

?>

<section class="auth-layout signup-layout">
<div class="auth-intro">
    <span class="eyebrow">MENTEPLAY</span>
    <h1>Crie seu perfil.</h1>
    <p>Personalize sua experiência e comece a explorar os desafios.</p>
</div>
<div class="form-box signup-box">

    <h1>Criar conta</h1>

    <p>
        Crie seu perfil e comece sua investigação matemática.
    </p>

    <?php if ($mensagem): ?>

        <div class="<?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>

    <form method="POST" class="signup-form">

        <label for="nome">
            Nome
        </label>

        <input
            type="text"
            id="nome"
            name="nome"
            placeholder="Como devemos chamar você?"
            value="<?= htmlspecialchars($_POST["nome"] ?? "") ?>"
            required
        >

        <label for="email">
            E-mail
        </label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Digite seu e-mail"
            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
            required
        >

        <label for="senha">
            Senha
        </label>

        <input
            type="password"
            id="senha"
            name="senha"
            placeholder="Mínimo de 6 caracteres"
            minlength="6"
            required
        >

        <fieldset class="vision-question">
            <legend>Você tem alguma dificuldade de visão?</legend>
            <p>Usaremos sua resposta para deixar os textos e elementos maiores. Você poderá ajustar isso no seu perfil.</p>
            <label><input type="radio" name="problema_visao" value="1" <?= ($_POST["problema_visao"] ?? "") === "1" ? "checked" : "" ?> required> Sim, gostaria de elementos maiores</label>
            <label><input type="radio" name="problema_visao" value="0" <?= ($_POST["problema_visao"] ?? "") === "0" ? "checked" : "" ?> required> Não</label>
        </fieldset>

        <button type="submit">
            Criar minha conta
        </button>

    </form>

    <p>
        Já possui uma conta?
        <a href="login.php">Entrar</a>
    </p>

</div>
</section>

<?php require "includes/footer.php"; ?>