<?php
require_once "includes/config.php";
$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $novaSenha = $_POST["nova_senha"] ?? "";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($novaSenha) < 6) {
        $mensagem = "Informe um e-mail válido e uma senha com pelo menos 6 caracteres.";
        $tipoMensagem = "erro";
    } else {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expira = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();
        $mensagem = $stmt->affected_rows > 0 ? "Senha atualizada. Você já pode entrar." : "Não encontramos uma conta com esse e-mail.";
        $tipoMensagem = $stmt->affected_rows > 0 ? "sucesso" : "erro";
    }
}
require "includes/header.php";
?>
<section class="auth-layout"><div class="auth-intro"><span class="eyebrow">ACESSO À CONTA</span><h1>Retome sua investigação.</h1><p>Crie uma nova senha e volte para suas missões.</p></div><div class="form-box auth-box"><div class="form-icon">🔐</div><h2>Redefinir senha</h2><?php if ($mensagem): ?><div class="<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?><form method="POST"><label for="email">E-mail</label><input type="email" id="email" name="email" required><label for="nova_senha">Nova senha</label><input type="password" id="nova_senha" name="nova_senha" minlength="6" required><button type="submit">Atualizar senha</button></form><p><a href="login.php">Voltar para entrar</a></p></div></section>
<?php require "includes/footer.php"; ?>