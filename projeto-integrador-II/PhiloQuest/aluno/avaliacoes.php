<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\AlunoService;
use PhiloQuest\Enum\StatusSubmissao;

$alunoId = (int) $_SESSION['usuario_id'];
$alunoService = new AlunoService();

$avaliacoes = $alunoService->obterHistoricoAvaliacoes($alunoId);

philoquest_layout_start('Minhas Avaliações');
?>

<div class="aluno-avaliacoes">
<div class="mb-4">
    <h1 class="fs-4 fw-semibold text-dark mb-1">Minhas Avaliações</h1>
    <p class="text-muted small mb-0">Acompanhe o feedback das suas etapas e o XP conquistado.</p>
</div>

<?php if (empty($avaliacoes)): ?>
    <div class="card border-0 rounded-4 text-center p-5">
        <i class="fas fa-inbox fs-1 text-secondary mb-3"></i>
        <h3 class="h5 text-secondary">Nenhuma avaliação disponível</h3>
        <p class="text-muted mb-0">As avaliações do seu professor aparecerão aqui.</p>
    </div>
<?php else: ?>
    <?php foreach ($avaliacoes as $av):
        $enumObj = StatusSubmissao::tryFrom($av['status']);
        $xpGanho = $enumObj ? $enumObj->obterXP() : 0;
        $cores = StatusSubmissao::coresBadgePorValor((string) $av['status']);
        $nomeStatus = StatusSubmissao::rotuloPorValor((string) $av['status']);
        $dataValidacao = $av['data_validacao'] ?? null;
        if ($av['status'] === 'AGUARDANDO_VALIDACAO' && $dataValidacao !== null && $dataValidacao !== '') {
            $nomeStatus = 'Reavaliação pendente';
            $cores = ['cor' => '#718096', 'bg' => '#EDF2F7'];
        }
        $dataExibicao = ($dataValidacao !== null && $dataValidacao !== '')
            ? date('d/m/Y H:i', strtotime((string) $dataValidacao))
            : date('d/m/Y H:i', strtotime((string) $av['data_submissao']));
    ?>
    <?php
        $numeroEtapaAv = (int) $av['numero_etapa'];
        $nomeEtapaAv = philoquest_nome_etapa_ciclo($numeroEtapaAv);
    ?>
    <div class="card card-avaliacao border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h3 class="h5 fw-semibold text-dark mb-1">Etapa <?= $numeroEtapaAv ?> — <?= htmlspecialchars($nomeEtapaAv) ?></h3>
                <p class="text-muted small mb-0">Enviado em: <?= date('d/m/Y', strtotime($av['data_submissao'])) ?> | Avaliado em: <?= $dataExibicao ?></p>
            </div>
            <?php if ($av['status'] === 'NECESSITA_REVISAO'): ?>
                <a href="ciclo.php" class="badge rounded-pill text-decoration-none"
                   style="background-color: <?= htmlspecialchars($cores['bg']) ?>; color: <?= htmlspecialchars($cores['cor']) ?>;">
                    <?= htmlspecialchars($nomeStatus) ?> <i class="fas fa-edit ms-1"></i>
                </a>
            <?php else: ?>
                <span class="badge rounded-pill" style="background-color: <?= htmlspecialchars($cores['bg']) ?>; color: <?= htmlspecialchars($cores['cor']) ?>;">
                    <?= htmlspecialchars($nomeStatus) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="fw-bold fs-4" style="color: <?= htmlspecialchars($cores['cor']) ?>;">
                        <i class="fas fa-star"></i> <?= $xpGanho ?> XP
                    </div>
                    <?php
                    $notaExibicao = isset($av['nota']) && $av['nota'] !== null && $av['nota'] !== ''
                        ? (string) $av['nota']
                        : null;
                    ?>
                    <?php if ($notaExibicao !== null): ?>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-graduation-cap"></i> Nota: <?= htmlspecialchars($notaExibicao) ?>/10
                        </p>
                    <?php endif; ?>
                    <p class="mb-0"><i class="fas fa-chalkboard-teacher text-primary"></i> Prof. <?= htmlspecialchars($av['professor_nome']) ?></p>
                </div>
                <div class="col-md-9">
                    <div class="bg-light border rounded-3 p-3">
                        <?= nl2br(htmlspecialchars($av['feedback'] ?? 'O professor não deixou nenhum comentário adicional.')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php philoquest_layout_end(); ?>
