<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\AlunoService;

$alunoId = (int) $_SESSION['usuario_id'];
$alunoService = new AlunoService();

$xpTotal        = $alunoService->obterProgresso($alunoId);
$nivelAtual     = $alunoService->calcularNivel($xpTotal);
$historico      = $alunoService->obterHistorico($alunoId);
$ranking        = $alunoService->obterRankingTurma($alunoId);
$progressoCiclo = $alunoService->obterProgressoCiclo($alunoId);

philoquest_layout_start('Painel do Aluno');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Painel do Aluno</h1>

<div class="card border-0 rounded-4 mb-4">
    <div class="card-body p-4">
        <h2 class="h5 mb-1">O Teu Progresso</h2>
        <p class="text-muted small mb-3">Acompanha o teu XP e evolução no ciclo filosófico.</p>

        <div class="row g-4 mb-3">
            <div class="col-auto">
                <span class="d-block text-muted small">XP total</span>
                <span class="fs-3 fw-semibold text-xp"><?= number_format($xpTotal, 0, ',', '.') ?> ◎</span>
            </div>
            <div class="col-auto">
                <span class="d-block text-muted small">Nível Atual</span>
                <span class="fs-3 fw-semibold text-xp"><?= htmlspecialchars($nivelAtual) ?> ↗</span>
            </div>
        </div>

        <div class="mt-2">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small fw-medium">Progresso do Ciclo</span>
                <span class="small fw-semibold text-xp">
                    <?= (int)$progressoCiclo['concluidas'] ?>/<?= (int)$progressoCiclo['total_etapas'] ?> Etapas Concluídas
                </span>
            </div>
            <div class="d-flex gap-2 cycle-bar">
                <?php for ($i = 1; $i <= $progressoCiclo['total_etapas']; $i++): ?>
                    <div class="progress-segment <?= ($i <= $progressoCiclo['concluidas']) ? 'done' : '' ?>"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold text-dark mb-3">🏆 Ranking</h2>
                <?php if (!empty($ranking)): ?>
                    <?php foreach ($ranking as $idx => $r): ?>
                    <div class="d-flex align-items-center py-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-bold" style="width: 25px;"><?= $idx + 1 ?>º</span>
                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                 style="width: 32px; height: 32px; font-size: 0.9rem;">
                                <?= htmlspecialchars(mb_substr($r['nome_completo'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($r['nome_completo']) ?></div>
                                <div class="text-muted small"><?= number_format($r['experiencia_total'], 0, ',', '.') ?> XP</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted mb-0">Sem dados de ranking.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold text-dark mb-3">Histórico de Atividade</h2>
                <?php if (!empty($historico)): ?>
                    <div class="d-flex flex-column gap-2">
                    <?php foreach ($historico as $h):
                        $tituloHistorico = ((int) ($h['etapa_id'] ?? 0) === 1)
                            ? 'Etapa 1 - Identificação do Problema'
                            : $h['titulo'];
                    ?>
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small"><?= htmlspecialchars($tituloHistorico) ?></strong>
                                <?php if (strpos($h['status'], 'APROVADO') !== false): ?>
                                    <span class="badge text-bg-success">Validado</span>
                                <?php elseif ($h['status'] === 'NECESSITA_REVISAO'): ?>
                                    <a href="avaliacoes.php" class="badge text-bg-danger text-decoration-none">
                                        Refazer <i class="fas fa-arrow-right" style="font-size: 0.65rem;"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Pendente</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-0"><?= htmlspecialchars(mb_strimwidth($h['descricao'], 0, 80, '...')) ?></p>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted mb-0">Ainda não tens atividades registadas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php philoquest_layout_end(); ?>
