<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use PhiloQuest\Services\AuthService;

$mensagem = '';
$tipoMensagem = '';
$tipoUsuario = 'ALUNO';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeCompleto = trim($_POST['nome_completo'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $tipoPost = $_POST['tipo_usuario'] ?? 'ALUNO';
    $tipoUsuario = in_array($tipoPost, ['ALUNO', 'PROFESSOR'], true) ? $tipoPost : 'ALUNO';

    $codigoTurma = trim($_POST['codigo_turma'] ?? '');
    $padraoTurma = '/^\d{3}[a-zA-Z]{2}\d{4}$/';

    if ($nomeCompleto === '' || $matricula === '' || $senha === '') {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
        $tipoMensagem = 'msg-error';
    } else {
        $authService = new AuthService();
        $sucesso = false;
        $mensagemErroBanco = '';

        if ($tipoUsuario === 'ALUNO') {
            if ($codigoTurma === '') {
                $mensagem = 'O código da turma é obrigatório para alunos.';
                $tipoMensagem = 'msg-error';
            } elseif (!preg_match($padraoTurma, $codigoTurma)) {
                $mensagem = 'Código de turma inválido. Use o padrão Ex: 103HS2026.';
                $tipoMensagem = 'msg-error';
            } else {
                try {
                    $sucesso = $authService->registrarAluno($matricula, $nomeCompleto, $senha, $codigoTurma);
                } catch (Exception $e) {
                    $mensagemErroBanco = $e->getMessage();
                }
            }
        } elseif ($tipoUsuario === 'PROFESSOR') {
            try {
                $sucesso = $authService->registrarProfessor($matricula, $nomeCompleto, $senha);
            } catch (Exception $e) {
                $mensagemErroBanco = $e->getMessage();
            }
        }

        if ($sucesso) {
            header('Location: login.php?cadastro=sucesso');
            exit;
        } elseif ($mensagem === '' && $mensagemErroBanco !== '') {
            $mensagem = $mensagemErroBanco;
            $tipoMensagem = 'msg-error';
        } elseif ($mensagem === '') {
            $mensagem = 'Erro desconhecido ao tentar cadastrar. Tente novamente.';
            $tipoMensagem = 'msg-error';
        }
    }
}

$pageTitle = 'Cadastro';
require __DIR__ . '/src/layouts/auth_shell_start.php';
?>
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 fw-normal text-dark mb-1 text-center">Bem-vindo</h1>
            <p class="text-muted small mb-4 text-center">Cadastre-se para começar sua jornada</p>

            <ul class="nav nav-tabs nav-justified border-bottom mb-4">
                <li class="nav-item">
                    <a class="nav-link text-muted" href="login.php">Entrar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active fw-semibold text-primary border-0 border-bottom border-primary border-2" href="cadastrar.php">Cadastrar</a>
                </li>
            </ul>

            <?php if ($mensagem !== ''): ?>
                <?php
                $alertClass = match ($tipoMensagem) {
                    'msg-success' => 'alert-success',
                    default => 'alert-danger',
                };
                ?>
                <div class="alert <?= $alertClass ?> py-2 small text-center auth-alert" role="alert">
                    <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form id="formCadastro" method="POST">
                <div class="d-flex gap-2 mb-4 p-1 bg-light rounded-3">
                    <input type="radio" class="btn-check" name="tipo_usuario" id="tipo_aluno" value="ALUNO" autocomplete="off"
                        <?= $tipoUsuario === 'ALUNO' ? 'checked' : '' ?>>
                    <label class="btn btn-sm flex-fill <?= $tipoUsuario === 'ALUNO' ? 'btn-primary' : 'btn-outline-primary' ?> rounded-3" for="tipo_aluno">Sou Aluno</label>

                    <input type="radio" class="btn-check" name="tipo_usuario" id="tipo_prof" value="PROFESSOR" autocomplete="off"
                        <?= $tipoUsuario === 'PROFESSOR' ? 'checked' : '' ?>>
                    <label class="btn btn-sm flex-fill <?= $tipoUsuario === 'PROFESSOR' ? 'btn-primary' : 'btn-outline-primary' ?> rounded-3" for="tipo_prof">Sou Professor</label>
                </div>

                <div class="mb-3 text-start">
                    <label for="nome_completo" class="form-label fw-semibold small">Nome Completo</label>
                    <input type="text" class="form-control rounded-3" id="nome_completo" name="nome_completo"
                           placeholder="João Silva" required
                           value="<?= htmlspecialchars($_POST['nome_completo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3 text-start">
                    <label for="matricula" class="form-label fw-semibold small">Matrícula</label>
                    <input type="text" class="form-control rounded-3" id="matricula" name="matricula"
                           placeholder="Ex: 2024001" required
                           value="<?= htmlspecialchars($_POST['matricula'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3 text-start" id="grupo_turma">
                    <label for="codigo_turma" class="form-label fw-semibold small">Código da Turma</label>
                    <input type="text" class="form-control rounded-3" id="codigo_turma" name="codigo_turma"
                           placeholder="Ex: 103HS2026" required
                           value="<?= htmlspecialchars($_POST['codigo_turma'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <span id="erroTurma" class="text-danger small d-none text-center d-block"></span>
                </div>
                <div class="mb-3 text-start">
                    <label for="senha" class="form-label fw-semibold small">Senha</label>
                    <input type="password" class="form-control rounded-3" id="senha" name="senha"
                           placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2">Cadastrar</button>
            </form>
        </div>
<script src="js/validacao.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioProf = document.getElementById('tipo_prof');
    if (radioProf && radioProf.checked) {
        radioProf.dispatchEvent(new Event('change'));
    }
    const caixaMensagem = document.querySelector('.auth-alert');
    document.querySelectorAll('input[name="tipo_usuario"]').forEach(function(botao) {
        botao.addEventListener('change', function() {
            if (caixaMensagem) caixaMensagem.classList.add('d-none');
            document.querySelectorAll('[for="tipo_aluno"], [for="tipo_prof"]').forEach(function(lbl) {
                lbl.classList.remove('btn-primary');
                lbl.classList.add('btn-outline-primary');
            });
            const lbl = document.querySelector('label[for="' + botao.id + '"]');
            if (lbl) {
                lbl.classList.add('btn-primary');
                lbl.classList.remove('btn-outline-primary');
            }
        });
    });
});
</script>
<?php require __DIR__ . '/src/layouts/auth_shell_end.php'; ?>
