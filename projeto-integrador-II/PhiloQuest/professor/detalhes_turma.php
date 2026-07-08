<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\ProfessorService;

$professorService = new ProfessorService();
$turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
$turma = [];
$alunos = [];

if ($turmaId > 0) {
    $turma = $professorService->obterTurmaPorId($turmaId);
}

if (empty($turma) || (int)$turma['professor_id'] !== $_SESSION['usuario_id']) {
    header('Location: gerenciar_turmas.php');
    exit;
}

$alunos = $professorService->listarAlunosDaTurma($turmaId);
$progressoPorAluno = $professorService->obterProgressoAlunosEmLote(array_column($alunos, 'id'));
philoquest_layout_start('Detalhes da Turma', null, null, [], 'gerenciar_turmas.php');
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fs-4 fw-semibold text-dark mb-1">Detalhes da Turma</h1>
        <p class="text-muted small mb-0">Acompanhe o andamento dos alunos da turma <?= htmlspecialchars($turma['codigo_turma']) ?>.</p>
    </div>
    <a href="gerenciar_turmas.php" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Código</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="fs-3 fw-bold text-primary mb-0 lh-1"><?= htmlspecialchars($turma['codigo_turma']) ?></span>
                    <i class="fas fa-hashtag fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Alunos</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= count($alunos) ?></span>
                    <i class="fas fa-user-graduate fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="card border-0 rounded-4 table-panel p-4">
    <h3 class="h5 fw-semibold text-dark mb-3">Alunos da Turma</h3>
    <?php if (empty($alunos)): ?>
        <p class="text-muted mb-0">Não há alunos vinculados a esta turma ainda.</p>
    <?php else: ?>
        <div class="list-group list-group-flush">
        <?php foreach ($alunos as $aluno):
            $progresso = $progressoPorAluno[(int) $aluno['id']] ?? [
                'nivel' => null,
                'percentual_progresso' => 0,
                'etapa_atual' => 0,
                'total_etapas' => 5,
            ];
        ?>
            <div class="list-group-item px-0 py-3 border-bottom">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($aluno['nome_completo']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($aluno['matricula']) ?></div>
                    </div>
                    <div class="col-md-7">
                        <?php if ($progresso['nivel'] !== null && $progresso['total_etapas'] > 0):
                            $totalEtapas = (int)$progresso['total_etapas'];
                            $etapaAtual = (int)$progresso['etapa_atual'];
                            if ($etapaAtual > $totalEtapas) {
                                $totalEtapas = $etapaAtual;
                            }
                            if ($totalEtapas < 5) {
                                $totalEtapas = 5;
                            }
                        ?>
                        <div class="d-flex justify-content-between small fw-semibold text-secondary mb-2">
                            <span>Progresso do Ciclo</span>
                            <span class="text-primary"><?= $etapaAtual ?>/<?= $totalEtapas ?> Etapas Concluídas</span>
                        </div>
                        <div class="d-flex gap-1 cycle-bar">
                            <?php for ($i = 1; $i <= $totalEtapas; $i++): ?>
                                <div class="progress-segment <?= $i <= $etapaAtual ? 'done' : '' ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted small fst-italic mb-0 text-center">Nenhuma etapa iniciada/aprovada</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <span class="badge bg-ativo">Ativo</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php philoquest_layout_end(); ?>
