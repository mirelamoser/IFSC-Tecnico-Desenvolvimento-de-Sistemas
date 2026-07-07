<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\AdminService;
use PhiloQuest\Services\AuthService;

$adminService = new AdminService();
$selfUrl = 'gerenciar_usuarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect($selfUrl);
    }

    $acao = $_POST['acao'] ?? '';
    $idUsuario = (int) ($_POST['id'] ?? 0);
    $authService = new AuthService();

    if ($acao === 'bloquear' && $idUsuario > 0) {
        if ($adminService->alterarStatusAcesso($idUsuario, false)) {
            philoquest_flash_set('sucesso', 'Acesso bloqueado com sucesso.');
        } else {
            philoquest_flash_set('erro', 'Não foi possível bloquear o utilizador.');
        }
    } elseif ($acao === 'desbloquear' && $idUsuario > 0) {
        if ($adminService->alterarStatusAcesso($idUsuario, true)) {
            philoquest_flash_set('sucesso', 'Acesso liberado com sucesso.');
        } else {
            philoquest_flash_set('erro', 'Não foi possível liberar o utilizador.');
        }
    } elseif ($acao === 'resetar_senha' && $idUsuario > 0) {
        $matricula = trim($_POST['matricula'] ?? '');
        if ($matricula !== '' && $authService->resetarSenhaParaMatricula($idUsuario, $matricula)) {
            philoquest_flash_set('sucesso', "Senha da matrícula {$matricula} redefinida. O utilizador deve trocá-la no próximo acesso.");
        } else {
            philoquest_flash_set('erro', 'Erro ao redefinir senha.');
        }
    }

    header('Location: ' . $selfUrl);
    exit;
}

$usuarios = $adminService->listarUsuariosGestao();

philoquest_layout_start('Gestão de Acesso');
?>

<h1 class="fs-4 fw-semibold text-dark mb-2">Gestão de Acesso</h1>
<p class="text-muted small mb-4">Gerencie quem pode acessar a plataforma PhiloQuest no momento.</p>

<div class="card border-0 shadow-sm rounded-4 table-panel p-4">
    <h2 class="h5 fw-semibold text-dark mb-3">Alunos e Professores Cadastrados e Pendentes</h2>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Matrícula</th>
                <th>Nome Completo</th>
                <th>Perfil</th>
                <th class="text-center">Situação</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usr): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($usr['matricula']) ?></strong></td>
                    <td><?= htmlspecialchars($usr['nome_completo']) ?></td>
                    <td><span class="badge badge-papel"><?= htmlspecialchars($usr['tipo_usuario']) ?></span></td>
                    <td class="text-center">
                        <?php if ((int) $usr['ativo'] === 1): ?>
                            <span class="badge bg-ativo">Ativo</span>
                        <?php elseif ((int) $usr['ativo'] === 0): ?>
                            <span class="badge bg-inativo">Bloqueado</span>
                        <?php else: ?>
                            <span class="badge bg-inativo">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="acoes-usuario d-flex flex-wrap justify-content-center gap-1">
                        <?php if ((int) $usr['ativo'] === 1 || (int) $usr['ativo'] === 0): ?>
                            <?php if ((int) $usr['ativo'] === 1): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Bloquear acesso deste usuário?');">
                                <?= philoquest_csrf_field() ?>
                                <input type="hidden" name="acao" value="bloquear">
                                <input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                                <button type="submit" class="btn-acao btn-bloquear border-0">
                                    <i class="fas fa-lock"></i> Bloquear
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" class="d-inline">
                                <?= philoquest_csrf_field() ?>
                                <input type="hidden" name="acao" value="desbloquear">
                                <input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                                <button type="submit" class="btn-acao btn-desbloquear border-0">
                                    <i class="fas fa-unlock"></i> Liberar
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Resetar senha para a matrícula?');">
                                <?= philoquest_csrf_field() ?>
                                <input type="hidden" name="acao" value="resetar_senha">
                                <input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                                <input type="hidden" name="matricula" value="<?= htmlspecialchars($usr['matricula']) ?>">
                                <button type="submit" class="btn-reset border-0">
                                    <i class="fas fa-key"></i> Resetar senha
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted"><i class="fas fa-clock"></i> Pendente de Cadastro</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php philoquest_layout_end(); ?>
