<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';


use PhiloQuest\Services\MatriculaService;

$matriculaService = new MatriculaService();
$selfUrl = 'gerenciar_matriculas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect($selfUrl);
    }

    if (isset($_POST['excluir_matricula'])) {
        $mat = trim($_POST['excluir_matricula']);
        if ($matriculaService->excluirMatriculaAluno($mat)) {
            philoquest_flash_set('sucesso', "Matrícula {$mat} removida.");
        } else {
            philoquest_flash_set('erro', 'Não foi possível remover a matrícula.');
        }
    } elseif (isset($_POST['limpar_tudo'])) {
        $matriculaService->limparMatriculasAlunos();
        philoquest_flash_set('sucesso', 'Base de alunos limpa com sucesso.');
    } elseif (isset($_POST['nova_matricula']) && !isset($_FILES['planilha_csv'])) {
        $matricula = trim($_POST['nova_matricula']);
        $turma = trim($_POST['nova_turma'] ?? '');
        try {
            if ($matriculaService->adicionarMatriculaAluno($matricula, $turma)) {
                philoquest_flash_set('sucesso', 'Matrícula adicionada.');
            } else {
                philoquest_flash_set('erro', 'Matrícula já existe no sistema.');
            }
        } catch (InvalidArgumentException $e) {
            philoquest_flash_set('erro', philoquest_user_error_message($e));
        }
    } elseif (isset($_FILES['planilha_csv'])) {
        $validacao = philoquest_validar_upload_csv($_FILES['planilha_csv']);
        if (!$validacao['ok']) {
            philoquest_flash_set('erro', $validacao['erro']);
        } else {
            $resultado = $matriculaService->importarCsvAlunos($validacao['path']);
            philoquest_flash_set(
                'sucesso',
                "Importação finalizada. Inseridos: {$resultado['inseridos']} | Já existentes: {$resultado['duplicados']}"
            );
        }
    }

    header('Location: ' . $selfUrl);
    exit;
}

$stats = $matriculaService->obterEstatisticasAlunos();
$matriculasRecentes = $matriculaService->listarMatriculasAlunos(50);

philoquest_layout_start('Gerenciamento de Matrículas');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Gerenciamento de Matrículas</h1>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Total</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= (int) ($stats['total'] ?? 0) ?></span>
                    <i class="fas fa-id-card fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Disponíveis</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= (int) ($stats['disponiveis'] ?? 0) ?></span>
                    <i class="fas fa-user-check fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 form-panel p-4 h-100">
            <h3 class="h5 fw-semibold text-dark mb-3">Individual</h3>
            <form method="POST">
                <?= philoquest_csrf_field() ?>
                <div class="mb-3">
                    <input type="text" name="nova_matricula" placeholder="Matrícula" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="nova_turma" class="form-label">Turma</label>
                    <input type="text" name="nova_turma" id="nova_turma" placeholder="Ex: 303HS2026" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Adicionar</button>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 form-panel p-4 h-100">
            <h3 class="h5 fw-semibold text-dark mb-3">CSV (Matrícula; Turma)</h3>
            <p class="text-muted small mb-2">Formato: <code>2026008;303HS2026</code> — matrícula e turma obrigatórias, separadas por <strong>;</strong></p>
            <form method="POST" enctype="multipart/form-data">
                <?= philoquest_csrf_field() ?>
                <div class="upload-area mb-3" onclick="document.getElementById('planilha_csv').click()">
                    <i class="fas fa-file-csv text-primary fs-3 mb-2"></i>
                    <p id="file-name" class="mb-0 small text-muted">Clique para selecionar o CSV</p>
                    <input type="file" name="planilha_csv" id="planilha_csv" accept=".csv" hidden>
                </div>
                <button type="submit" class="btn btn-primary">Importar</button>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 table-panel p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="h5 fw-semibold text-dark mb-0">Matrículas Cadastradas</h3>
        <form method="POST" onsubmit="return confirm('Apagar todas as matrículas de alunos?');">
            <?= philoquest_csrf_field() ?>
            <button type="submit" name="limpar_tudo" class="btn btn-outline-danger btn-sm">Limpar Base</button>
        </form>
    </div>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Matrícula</th>
                <th>Status</th>
                <th>Turma</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matriculasRecentes as $mat): ?>
            <tr>
                <td><strong><?= htmlspecialchars($mat['matricula']) ?></strong></td>
                <td>
                    <span class="badge <?= $mat['status'] === 'DISPONIVEL' ? 'bg-inativo' : 'bg-ativo' ?>">
                        <?= htmlspecialchars($mat['status']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($mat['turma_id'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($mat['data_cadastro'])) ?></td>
                <td>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta matrícula?');">
                        <?= philoquest_csrf_field() ?>
                        <input type="hidden" name="excluir_matricula" value="<?= htmlspecialchars($mat['matricula']) ?>">
                        <button type="submit" class="btn btn-link text-danger p-0"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
document.getElementById('planilha_csv')?.addEventListener('change', function(e) {
    if (e.target.files.length > 0) {
        document.getElementById('file-name').innerText = e.target.files[0].name;
    }
});
</script>

<?php philoquest_layout_end(); ?>
