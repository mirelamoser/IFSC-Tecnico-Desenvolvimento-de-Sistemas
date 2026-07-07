<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\MuralService;

$alunoId = (int) $_SESSION['usuario_id'];
$muralService = new MuralService();

$temTurma = $muralService->alunoTemTurma($alunoId);
$codigoTurma = $muralService->obterCodigoTurma($alunoId);
$feed = $temTurma ? $muralService->listarFeedTurma($alunoId) : [];

philoquest_layout_start('Mural da Turma', null, false, [], 'mural.php');
?>

<div class="aluno-mural mb-4">
    <h1 class="fs-4 fw-semibold text-dark mb-1">Mural da Turma</h1>
    <p class="text-muted small mb-0">
        <?php if ($codigoTurma !== null): ?>
            Acompanhe os colegas que concluíram o ciclo filosófico na turma <?= htmlspecialchars($codigoTurma) ?>.
        <?php else: ?>
            Veja as novidades da sua turma assim que estiver vinculado a uma.
        <?php endif; ?>
    </p>
</div>

<?php if (!$temTurma): ?>
    <div class="card border-0 rounded-4 text-center p-5">
        <i class="fas fa-users fs-1 text-secondary mb-3"></i>
        <h2 class="h5 text-secondary">Turma não vinculada</h2>
        <p class="text-muted mb-0">Peça ao professor o código da turma ou conclua o seu cadastro.</p>
    </div>
<?php elseif ($feed === []): ?>
    <div class="card border-0 rounded-4 text-center p-5 bg-white">
        <i class="fas fa-desktop fs-1 text-primary mb-3"></i>
        <h2 class="h5 text-dark">O mural está quieto por agora</h2>
        <p class="text-muted mb-0">
            Quando um colega concluir o ciclo filosófico completo, a conquista aparecerá aqui.
        </p>
    </div>
<?php else: ?>
    <div class="mural-feed d-flex flex-column gap-3">
        <?php foreach ($feed as $post): ?>
            <article class="card border-0 rounded-4 mural-post">
                <div class="card-body p-4">
                    <div class="d-flex gap-3">
                        <div class="mural-avatar rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                             style="background-color: <?= htmlspecialchars($post['bg']) ?>; color: <?= htmlspecialchars($post['cor']) ?>;">
                            <?= htmlspecialchars($post['autor_inicial']) ?>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($post['autor_nome']) ?></span>
                                    <span class="badge rounded-pill ms-2 mural-badge"
                                          style="background-color: <?= htmlspecialchars($post['bg']) ?>; color: <?= htmlspecialchars($post['cor']) ?>;">
                                        <i class="fas <?= htmlspecialchars($post['icone']) ?> me-1"></i>
                                        <?= htmlspecialchars($post['rotulo_tipo']) ?>
                                    </span>
                                </div>
                                <time class="text-muted small text-nowrap" datetime="<?= htmlspecialchars($post['data_evento']) ?>">
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($post['data_evento']))) ?>
                                </time>
                            </div>
                            <p class="mb-0 text-secondary"><?= htmlspecialchars($post['mensagem']) ?></p>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php philoquest_layout_end(); ?>
