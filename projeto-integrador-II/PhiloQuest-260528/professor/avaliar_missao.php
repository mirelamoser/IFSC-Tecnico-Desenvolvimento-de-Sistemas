<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Enum\StatusEntregaMissao;
use PhiloQuest\Enum\StatusSubmissao;
use PhiloQuest\Services\MissaoExtraService;

$professorId = (int) $_SESSION['usuario_id'];
$missaoService = new MissaoExtraService();

$missaoId = isset($_GET['missao_id']) ? (int) $_GET['missao_id'] : 0;
if ($missaoId <= 0) {
    philoquest_flash_set('erro', 'Missão inválida.');
    header('Location: missoes_extras.php');
    exit;
}

$missao = $missaoService->obterMissaoDoProfessor($missaoId, $professorId);
if ($missao === null) {
    philoquest_flash_set('erro', 'Missão não encontrada.');
    header('Location: missoes_extras.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect('avaliar_missao.php?missao_id=' . $missaoId);
    }
    $entregaId = (int) ($_POST['entrega_id'] ?? 0);
    $acao = $_POST['acao_avaliar'] ?? '';

    try {
        if ($acao === 'aprovar') {
            $statusXp = $_POST['status_xp'] ?? '';
            $feedback = trim((string) ($_POST['feedback'] ?? ''));
            $missaoService->aprovarEntrega($entregaId, $professorId, $statusXp, $feedback);
            philoquest_flash_set('sucesso', 'Entrega aprovada e XP atribuído ao aluno.');
        } elseif ($acao === 'revisar') {
            $feedback = trim((string) ($_POST['feedback'] ?? ''));
            $missaoService->solicitarRevisaoEntrega($entregaId, $professorId, $feedback);
            philoquest_flash_set('sucesso', 'Revisão solicitada ao aluno.');
        }
    } catch (Exception $e) {
        philoquest_flash_set('erro', $e->getMessage());
    }
    header('Location: avaliar_missao.php?missao_id=' . $missaoId);
    exit;
}

$entregas = $missaoService->listarEntregasDaMissao($missaoId, $professorId);

philoquest_layout_start('Avaliar Missão', null, false, ['css/philoquest-missoes.css'], 'missoes_extras.php');
?>

<div class="mb-3">
    <a href="missoes_extras.php" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left"></i> Voltar às missões
    </a>
</div>

<div class="card border-0 rounded-4 missao-header-banner text-white mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="missao-icone-raio missao-icone-raio--claro rounded-3 d-flex align-items-center justify-content-center">
                <i class="fas fa-bolt fs-4"></i>
            </div>
            <div>
                <span class="badge bg-white text-primary mb-2"><?= htmlspecialchars($missao['codigo_turma']) ?></span>
                <h1 class="h4 fw-semibold mb-2"><?= htmlspecialchars($missao['titulo']) ?></h1>
                <p class="small mb-0 opacity-90"><?= nl2br(htmlspecialchars($missao['descricao'])) ?></p>
            </div>
        </div>
        <?php if (!empty($missao['link_referencia'])): ?>
            <a href="<?= htmlspecialchars($missao['link_referencia']) ?>" target="_blank" rel="noopener"
               class="badge rounded-pill bg-white text-primary text-decoration-none px-3 py-2">
                <i class="fas fa-external-link-alt me-1"></i> Material de referência
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($entregas)): ?>
    <div class="card border-0 rounded-4 bg-stat-lilac text-center p-5">
        <p class="text-muted mb-0">Nenhum aluno enviou resposta para esta missão ainda.</p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($entregas as $entrega):
            $statusEnum = StatusEntregaMissao::tryFrom((string) $entrega['status']) ?? StatusEntregaMissao::PENDENTE;
            $pendente = $statusEnum === StatusEntregaMissao::PENDENTE;
        ?>
        <div class="card border-0 rounded-4 bg-white shadow-none">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-semibold text-dark mb-1"><?= htmlspecialchars($entrega['aluno_nome']) ?></h2>
                        <p class="small text-muted mb-0">
                            Entregue em <?= date('d/m/Y H:i', strtotime($entrega['data_entrega'])) ?>
                        </p>
                    </div>
                    <span class="badge rounded-pill px-3 py-2"
                          style="background-color: <?= $statusEnum->corFundoBadge() ?>; color: <?= $statusEnum->corBadge() ?>;">
                        <?= htmlspecialchars($statusEnum->rotulo()) ?>
                    </span>
                </div>

                <div class="bg-light border rounded-3 p-3 mb-3">
                    <?= nl2br(htmlspecialchars($entrega['resposta_texto'])) ?>
                </div>

                <?php if ($pendente): ?>
                <form method="POST" class="missao-form-avaliacao">
                    <?= philoquest_csrf_field() ?>
                    <input type="hidden" name="entrega_id" value="<?= (int) $entrega['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Feedback (opcional)</label>
                        <textarea name="feedback" class="form-control" rows="3"
                                  placeholder="Comentário para o aluno..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nível de aprovação (XP)</label>
                        <select name="status_xp" class="form-select border-primary" required>
                            <option value="">Selecione o XP...</option>
                            <option value="<?= StatusSubmissao::APROVADO->value ?>">
                                Aprovado (<?= StatusSubmissao::APROVADO->obterXP() ?> XP)
                            </option>
                            <option value="<?= StatusSubmissao::APROVADO_BEM_FEITO->value ?>">
                                Bem Feito (<?= StatusSubmissao::APROVADO_BEM_FEITO->obterXP() ?> XP)
                            </option>
                            <option value="<?= StatusSubmissao::APROVADO_EXCELENTE->value ?>">
                                Excelente! (<?= StatusSubmissao::APROVADO_EXCELENTE->obterXP() ?> XP)
                            </option>
                        </select>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="acao_avaliar" value="aprovar" class="btn btn-primary">
                            <i class="fas fa-check"></i> Aprovar entrega
                        </button>
                        <button type="submit" name="acao_avaliar" value="revisar" class="btn btn-outline-secondary philo-btn-action"
                                onclick="return confirm('Solicitar revisão? O aluno precisará reenviar a resposta.');">
                            <i class="fas fa-redo text-primary"></i> Solicitar revisão
                        </button>
                    </div>
                </form>
                <?php elseif (!empty($entrega['feedback_professor'])): ?>
                    <p class="small fw-semibold text-muted mb-1">Seu feedback</p>
                    <div class="bg-stat-lilac rounded-3 p-3 small mb-0">
                        <?= nl2br(htmlspecialchars($entrega['feedback_professor'])) ?>
                    </div>
                    <?php if ($statusEnum === StatusEntregaMissao::APROVADO && (int) $entrega['xp_atribuido'] > 0): ?>
                        <p class="small text-primary fw-semibold mt-2 mb-0">
                            <i class="fas fa-star"></i> <?= (int) $entrega['xp_atribuido'] ?> XP atribuídos
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php philoquest_layout_end(); ?>
