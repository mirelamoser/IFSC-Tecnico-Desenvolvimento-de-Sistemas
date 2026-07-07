<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use PhiloQuest\LoginThrottle;
use PhiloQuest\Services\AuthService;

$mensagem = '';
$tipoMensagem = '';

if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso') {
    $mensagem = 'Operação realizada com sucesso! Faça o seu login para continuar.';
    $tipoMensagem = 'msg-success';
}

if (isset($_SESSION['usuario_id'])) {
    header('Location: painel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        $mensagem = 'Sessão expirada ou formulário inválido. Recarregue a página e tente novamente.';
        $tipoMensagem = 'msg-error';
    } else {
    $retryAfter = 0;
    if (LoginThrottle::isBlocked($retryAfter)) {
        $mensagem = LoginThrottle::blockedMessage($retryAfter);
        $tipoMensagem = 'msg-error';
    } else {
    $matricula = trim($_POST['matricula'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($matricula === '' || $senha === '') {
        $mensagem = 'Por favor, preencha a matrícula e a senha.';
        $tipoMensagem = 'msg-error';
    } else {
        $authService = new AuthService();
        $usuario = $authService->login($matricula, $senha);

        if ($usuario) {
            LoginThrottle::clear();

            if (isset($usuario['forcar_troca_senha']) && (int) $usuario['forcar_troca_senha'] === 1) {
                $authService->iniciarSessaoRedefinicaoSenha((int) $usuario['id']);
                header('Location: definir_nova_senha.php');
                exit;
            }

            $authService->iniciarSessaoPosLogin($usuario);
            header('Location: painel.php');
            exit;
        }

        LoginThrottle::recordFailure();
        $mensagem = 'Matrícula ou senha incorretos.';
        $tipoMensagem = 'msg-error';
    }
    }
    }
}

$pageTitle = 'Entrar';
require __DIR__ . '/src/layouts/auth_shell_start.php';
?>
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 fw-normal text-dark mb-1 text-center">Bem-vindo de volta</h1>
            <p class="text-muted small mb-4 text-center">Aceda à sua conta para continuar a jornada</p>

            <ul class="nav nav-tabs nav-justified border-bottom mb-4">
                <li class="nav-item">
                    <a class="nav-link active fw-semibold text-primary border-0 border-bottom border-primary border-2" href="login.php">Entrar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-muted" href="cadastrar.php">Cadastrar</a>
                </li>
            </ul>

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
                    <label for="matricula" class="form-label fw-semibold small">Matrícula</label>
                    <input type="text" class="form-control rounded-3" id="matricula" name="matricula"
                           placeholder="A sua matrícula" required
                           value="<?= htmlspecialchars($_POST['matricula'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3 text-start">
                    <label for="senha" class="form-label fw-semibold small">Senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control rounded-start-3" id="senha" name="senha"
                               placeholder="••••••••" required>
                        <button type="button" class="btn btn-outline-secondary rounded-end-3" id="toggle-senha"
                                aria-label="Mostrar senha" aria-pressed="false">
                            <i class="fas fa-eye" id="icone-senha"></i>
                        </button>
                    </div>
                    <div class="text-end mt-2">
                        <a href="#" class="small text-primary text-decoration-none"
                           onclick="alert('Por favor, procure o administrador do sistema para solicitar a redefinição da sua senha. A sua nova senha provisória será a sua matrícula.'); return false;">
                            Esqueci a minha senha
                        </a>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 mt-2">Entrar</button>
            </form>
        </div>
<script>
(function () {
    var inputSenha = document.getElementById('senha');
    var botaoToggle = document.getElementById('toggle-senha');
    var iconeSenha = document.getElementById('icone-senha');
    if (!inputSenha || !botaoToggle || !iconeSenha) return;

    botaoToggle.addEventListener('click', function () {
        var visivel = inputSenha.type === 'text';
        inputSenha.type = visivel ? 'password' : 'text';
        iconeSenha.classList.toggle('fa-eye', visivel);
        iconeSenha.classList.toggle('fa-eye-slash', !visivel);
        botaoToggle.setAttribute('aria-label', visivel ? 'Mostrar senha' : 'Ocultar senha');
        botaoToggle.setAttribute('aria-pressed', visivel ? 'false' : 'true');
    });
})();
</script>
<?php require __DIR__ . '/src/layouts/auth_shell_end.php'; ?>
