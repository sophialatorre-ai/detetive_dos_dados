<?php

require_once "includes/config.php";

$mensagem = "";
$tipoMensagem = "";

// Mensagem após cadastro
if (isset($_GET["cadastro"]) && $_GET["cadastro"] === "sucesso") {
    $mensagem = "Conta criada com sucesso! Agora entre para começar.";
    $tipoMensagem = "sucesso";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($email === "" || $senha === "") {

        $mensagem = "Preencha todos os campos.";
        $tipoMensagem = "erro";

    } else {

        $sql = "SELECT *
                FROM usuarios
                WHERE email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {

            $usuario = $resultado->fetch_assoc();

            if (password_verify($senha, $usuario["senha"])) {

                // Cria a sessão do usuário
                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nome"] = $usuario["nome"];

                // Entra no Dashboard
                header("Location: dashboard.php");
                exit;

            } else {

                $mensagem = "Senha incorreta.";
                $tipoMensagem = "erro";
            }

        } else {

            $mensagem = "E-mail não encontrado.";
            $tipoMensagem = "erro";
        }
    }
}

require "includes/header.php";

?>

<section class="auth-layout">
<div class="auth-intro">
    <span class="eyebrow">CENTRAL DE ACESSO</span>
    <h1>Pronto para investigar?</h1>
    <p>Entre para acompanhar seu nível, suas pistas e suas conquistas.</p>
</div>
<div class="form-box auth-box">

    <h1>Entrar</h1>

    <p>
        Entre para continuar suas missões.
    </p>

    <?php if ($mensagem): ?>

        <div class="<?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

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
            placeholder="Digite sua senha"
            required
        >

        <button type="submit">
            Entrar
        </button>

    </form>

    <p>
        <a href="reset.php">Esqueci minha senha</a><br>
        Ainda não possui conta?
        <a href="cadastro.php">
            Criar conta
        </a>
    </p>

</div>
</section>

<?php require "includes/footer.php"; ?>