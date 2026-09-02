<?php

require_once "includes/config.php";

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($nome === "" || $email === "" || $senha === "") {

        $mensagem = "Preencha todos os campos.";
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
                    (nome, email, senha, nivel, xp)
                    VALUES (?, ?, ?, 1, 0)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sss",
                $nome,
                $email,
                $senhaHash
            );

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

<div class="form-box">

    <div class="form-icon">
        DETETIVE
    </div>

    <h1>Criar conta</h1>

    <p>
        Crie seu perfil e comece sua investigação matemática.
    </p>

    <?php if ($mensagem): ?>

        <div class="<?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

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

        <button type="submit">
            Criar minha conta
        </button>

    </form>

    <p>
        Já possui uma conta?
        <a href="login.php">Entrar</a>
    </p>

</div>

<?php require "includes/footer.php"; ?>