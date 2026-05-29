<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\ProfessorService;

$professorId = (int) $_SESSION['usuario_id'];

$professorService = new ProfessorService();

// Lógica do Filtro: Captura o ID da turma via GET, se existir
$turmaFiltro = isset($_GET['turma_id']) && !empty($_GET['turma_id']) ? (int)$_GET['turma_id'] : null;

// Busca as turmas para preencher a combobox (select)
$turmasDoProfessor = $professorService->listarTurmasProf($professorId);

// Instancia e busca a lista filtrada do banco de dados
$listaParaValidar = $professorService->listarValidacoesPendentes($professorId, $turmaFiltro);

if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'avaliacao_concluida') {
    philoquest_flash_set('sucesso', 'Avaliação registrada com sucesso.');
}

philoquest_layout_start('Validar Atividades');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Validar Atividades</h1>

<div class="card border-0 rounded-4 p-3 mb-4">
    <form method="GET" action="validacao.php" class="row g-2 align-items-end">
        <div class="col-auto">
            <label for="turma_id" class="form-label fw-semibold mb-0">
                <i class="fas fa-filter text-primary"></i> Filtrar por Turma
            </label>
        </div>
        <div class="col-sm-6 col-md-4">
            <select name="turma_id" id="turma_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas as Turmas</option>
                        <?php foreach($turmasDoProfessor as $turma): ?>
                            <option value="<?= $turma['id'] ?>" <?= $turmaFiltro === (int)$turma['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turma['codigo_turma']) ?>
                            </option>
                        <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card border-0 rounded-4 table-panel p-4">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Turma</th>
                            <th>Etapa</th>
                            <th>Data</th>
                            <th>Conteúdo Parcial</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($listaParaValidar)): foreach($listaParaValidar as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['aluno_nome']) ?></td>
                            <td><span class="badge badge-papel"><?= htmlspecialchars($item['codigo_turma']) ?></span></td>
                            <td>Etapa <?= $item['numero_etapa'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($item['data'])) ?></td>
                            <td><small><?= htmlspecialchars(substr($item['texto'], 0, 50)) ?>...</small></td>
                            <td>
                                <a href="avaliar_submissao.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-validar">Analisar</a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-check-double fs-1 text-secondary d-block mb-3"></i>
                                Nenhuma atividade pendente para esta seleção.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
    </table>
    </div>
</div>

<?php philoquest_layout_end(); ?>