<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\AdminService;
use PhiloQuest\Services\MatriculaService;

$adminService = new AdminService();
$matriculaService = new MatriculaService();
$selfUrl = 'cadastrar_professor.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect($selfUrl);
    }

    if (isset($_POST['excluir_matricula'])) {
        $mat = trim($_POST['excluir_matricula']);
        if ($matriculaService->excluirAutorizacaoProfessor($mat)) {
            philoquest_flash_set('sucesso', "Autorização do professor {$mat} removida.");
        } else {
            philoquest_flash_set('erro', 'Não foi possível remover a autorização.');
        }
    } elseif (isset($_POST['matricula'])) {
        $matricula = trim($_POST['matricula']);
        if ($matricula === '') {
            philoquest_flash_set('erro', 'Informe a matrícula do professor.');
        } else {
            try {
                if ($adminService->autorizarMatriculaProfessor($matricula, '')) {
                    philoquest_flash_set('sucesso', "Professor autorizado. Matrícula: {$matricula}");
                }
            } catch (Exception $e) {
                philoquest_flash_set('erro', philoquest_user_error_message($e));
            }
        }
    }

    header('Location: ' . $selfUrl);
    exit;
}

$professoresRecentes = $matriculaService->listarProfessoresAutorizados();

philoquest_layout_start('Autorizar Novo Professor');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Autorizar Novo Professor</h1>

<div class="card border-0 shadow-sm rounded-4 form-panel p-4">
    <form method="POST">
        <?= philoquest_csrf_field() ?>
        <div class="mb-3">
            <label for="matricula" class="form-label">Matrícula do Professor</label>
            <input type="text" class="form-control" id="matricula" name="matricula" placeholder="Ex: PROF202401" required>
        </div>
        <div class="d-flex gap-3 align-items-center mt-4">
            <button type="submit" class="btn btn-primary">Autorizar Professor</button>
            <a href="dashboard.php" class="text-muted text-decoration-none">Cancelar</a>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 table-panel p-4">
    <h3 class="h5 fw-semibold text-dark mb-3">Professores Autorizados</h3>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Matrícula</th>
                <th>Status</th>
                <th>Data de Autorização</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($professoresRecentes)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum professor autorizado.</td></tr>
            <?php else: ?>
                <?php foreach ($professoresRecentes as $prof): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($prof['matricula']) ?></strong></td>
                    <td>
                        <?php if ($prof['status'] === 'DISPONIVEL'): ?>
                            <span class="badge disponivel">Aguardando Cadastro</span>
                        <?php else: ?>
                            <span class="badge utilizada">Ativo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= isset($prof['data_cadastro']) ? date('d/m/Y', strtotime($prof['data_cadastro'])) : 'N/A' ?></td>
                    <td class="text-center">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Excluir autorização deste professor?');">
                            <?= philoquest_csrf_field() ?>
                            <input type="hidden" name="excluir_matricula" value="<?= htmlspecialchars($prof['matricula']) ?>">
                            <button type="submit" class="action-icon border-0 bg-transparent"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php philoquest_layout_end(); ?>
