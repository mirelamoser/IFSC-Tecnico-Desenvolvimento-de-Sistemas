<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use PhiloQuest\Services\AuthService;

if (!isset($_SESSION['usuario_id_reset'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        $mensagem = 'Sessão expirada ou formulário inválido. Recarregue a página e tente novamente.';
        $tipoMensagem = 'msg-error';
    } else {
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if ($nova_senha === '' || $confirmar_senha === '') {
            $mensagem = 'Por favor, preencha ambos os campos.';
            $tipoMensagem = 'msg-error';
        } elseif ($nova_senha !== $confirmar_senha) {
            $mensagem = 'As senhas não coincidem. Tente novamente.';
            $tipoMensagem = 'msg-error';
        } elseif (strlen($nova_senha) < 6) {
            $mensagem = 'A senha deve ter pelo menos 6 caracteres para a sua segurança.';
            $tipoMensagem = 'msg-error';
        } else {
            $authService = new AuthService();
            $idUsuario = (int) $_SESSION['usuario_id_reset'];

            if ($authService->atualizarSenha($idUsuario, $nova_senha)) {
                unset($_SESSION['usuario_id_reset']);
                header('Location: login.php?cadastro=sucesso');
                exit;
            }

            $mensagem = 'Erro interno ao atualizar a senha. Contacte o administrador.';
            $tipoMensagem = 'msg-error';
        }
    }
}

$pageTitle = 'Definir Nova Senha';
require __DIR__ . '/src/layouts/auth_shell_start.php';
?>
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 fw-normal text-dark mb-1 text-center text-uppercase">Segurança da Conta</h1>
            <p class="text-danger small fw-medium mb-4 text-center">
                A sua senha foi redefinida pelo Administrador. Defina uma nova senha segura para continuar.
            </p>

            <?php if ($mensagem !== ''): ?>
                <?php
                $alertClass = match ($tipoMensagem) {
                    'msg-success' => 'alert-success',
                    default => 'alert-danger',
                };
                ?>
                <div class="alert <?= $alertClass ?> py-2 small text-center" role="alert">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= philoquest_csrf_field() ?>
                <div class="mb-3 text-start">
                    <label for="nova_senha" class="form-label fw-semibold small">Nova senha</label>
                    <input type="password" class="form-control rounded-3" id="nova_senha" name="nova_senha"
                           placeholder="No mínimo 6 caracteres" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="confirmar_senha" class="form-label fw-semibold small">Confirmar senha</label>
                    <input type="password" class="form-control rounded-3" id="confirmar_senha" name="confirmar_senha"
                           placeholder="Repita a senha" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2">Atualizar senha e entrar</button>
            </form>
        </div>
<?php require __DIR__ . '/src/layouts/auth_shell_end.php'; ?>
