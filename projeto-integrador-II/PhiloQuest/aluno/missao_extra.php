<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Enum\StatusEntregaMissao;
use PhiloQuest\Services\MissaoExtraService;

$alunoId = (int) $_SESSION['usuario_id'];
$missaoService = new MissaoExtraService();
$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'enviar_resposta') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect('missao_extra.php');
    }
    try {
        $missaoId = (int) ($_POST['missao_id'] ?? 0);
        $resposta = (string) ($_POST['resposta'] ?? '');
        $missaoService->submeterResposta($alunoId, $missaoId, $resposta);
        header('Location: missao_extra.php?sucesso=1');
        exit;
    } catch (Exception $e) {
        $mensagemErro = philoquest_user_error_message($e);
    }
}

$missoes = [];
if ($missaoService->alunoTemTurma($alunoId)) {
    $missoes = $missaoService->listarMissoesDaTurmaDoAluno($alunoId);
}

$mensagemSucesso = isset($_GET['sucesso']) ? 'Resposta enviada com sucesso! Aguarde a avaliação do professor.' : '';

philoquest_layout_start('Missão Extra', null, false, ['css/philoquest-missoes.css'], 'missao_extra.php');
?>

<div class="mb-4">
    <h1 class="fs-4 fw-semibold text-dark mb-1">Missão Extra</h1>
    <p class="text-muted small mb-0">Atividades especiais enviadas pelo seu professor.</p>
</div>

<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<?php if (!$missaoService->alunoTemTurma($alunoId)): ?>
    <div class="card border-0 rounded-4 bg-white text-center p-5">
        <i class="fas fa-users fs-1 text-primary mb-3"></i>
        <h2 class="h5 text-dark">Turma não vinculada</h2>
        <p class="text-muted mb-0">Você precisa estar em uma turma para receber Missões Extras.</p>
    </div>
<?php elseif (empty($missoes)): ?>
    <div class="card border-0 rounded-4 bg-white text-center p-5">
        <i class="fas fa-bolt fs-1 text-primary mb-3"></i>
        <h2 class="h5 text-dark">Nenhuma missão disponível</h2>
        <p class="text-muted mb-0">
            Seu professor ainda não criou uma Missão Extra para a sua turma.
            Volte mais tarde para conferir novidades!
        </p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($missoes as $missao):
            $missaoId = (int) $missao['id'];
            $statusEntrega = $missao['status_entrega'] ?? null;
            $statusEnum = $statusEntrega !== null
                ? (StatusEntregaMissao::tryFrom((string) $statusEntrega) ?? StatusEntregaMissao::PENDENTE)
                : null;
            $podeEnviar = $statusEnum === null || $statusEnum === StatusEntregaMissao::REVISAR;
            $aguardando = $statusEnum === StatusEntregaMissao::PENDENTE;
            $aprovado = $statusEnum === StatusEntregaMissao::APROVADO;
            $respostaAtual = (string) ($missao['resposta_texto'] ?? '');
        ?>
        <article class="card border-0 rounded-4 overflow-hidden shadow-none bg-white">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="missao-icone-raio bg-white rounded-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-bolt fs-4 text-primary"></i>
                        </div>
                        <div>
                            <p class="small text-muted mb-1">
                                Missão enviada por Prof. <?= htmlspecialchars($missao['professor_nome']) ?>
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($statusEnum === null): ?>
                                    <span class="badge rounded-pill" style="background:#e2e8f0;color:#475569;">Não enviada</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill"
                                          style="background:<?= $statusEnum->corFundoBadge() ?>;color:<?= $statusEnum->corBadge() ?>;">
                                        <?= htmlspecialchars($statusEnum->rotulo()) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <h2 class="h5 fw-semibold text-dark mb-3"><?= htmlspecialchars($missao['titulo']) ?></h2>
                <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($missao['descricao'])) ?></p>

                <?php
                $linkMissao = philoquest_url_segura($missao['link_referencia'] ?? null);
                if ($linkMissao !== null):
                ?>
                <div class="missao-ref-box rounded-3 p-3 mb-4">
                    <p class="small fw-semibold text-muted text-uppercase mb-2">Material de referência</p>
                    <a href="<?= htmlspecialchars($linkMissao) ?>" target="_blank" rel="noopener noreferrer"
                       class="text-primary text-decoration-none">
                        <i class="fas fa-external-link-alt me-1"></i>
                        <?= htmlspecialchars($linkMissao) ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($aguardando): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-hourglass-half me-1"></i>
                        Sua resposta foi enviada e está aguardando avaliação do professor.
                    </div>
                    <div class="bg-light border rounded-3 p-3 mt-3 mb-0">
                        <?= nl2br(htmlspecialchars($respostaAtual)) ?>
                    </div>
                <?php elseif ($aprovado): ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle me-1"></i>
                        Missão aprovada!
                        <?php if ((int) ($missao['xp_atribuido'] ?? 0) > 0): ?>
                            Você ganhou <strong><?= (int) $missao['xp_atribuido'] ?> XP</strong>.
                        <?php endif; ?>
                    </div>
                    <div class="bg-light border rounded-3 p-3 mb-3">
                        <?= nl2br(htmlspecialchars($respostaAtual)) ?>
                    </div>
                    <?php if (!empty($missao['feedback_professor'])): ?>
                        <p class="small fw-semibold text-muted mb-1">Parecer do professor</p>
                        <div class="bg-stat-lilac rounded-3 p-3 small">
                            <?= nl2br(htmlspecialchars($missao['feedback_professor'])) ?>
                        </div>
                    <?php endif; ?>
                <?php elseif ($podeEnviar): ?>
                    <?php if ($statusEnum === StatusEntregaMissao::REVISAR && !empty($missao['feedback_professor'])): ?>
                        <div class="alert alert-danger mb-3">
                            <strong>Revisão solicitada:</strong><br>
                            <span class="small"><?= nl2br(htmlspecialchars($missao['feedback_professor'])) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="missao_extra.php">
                        <?= philoquest_csrf_field() ?>
                        <input type="hidden" name="acao" value="enviar_resposta">
                        <input type="hidden" name="missao_id" value="<?= $missaoId ?>">
                        <label class="form-label fw-semibold" for="resposta-<?= $missaoId ?>">Sua resposta *</label>
                        <textarea name="resposta" id="resposta-<?= $missaoId ?>" class="form-control mb-1" rows="8"
                                  required data-contador="contador-<?= $missaoId ?>"
                                  placeholder="Escreva aqui sua resposta para a missão..."><?= htmlspecialchars($respostaAtual) ?></textarea>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="small text-muted">Mínimo recomendado: 3 linhas</span>
                            <span class="small text-muted"><span id="contador-<?= $missaoId ?>">0</span> caracteres</span>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-paper-plane"></i>
                                <?= $statusEnum === StatusEntregaMissao::REVISAR ? 'Reenviar resposta ao professor' : 'Enviar resposta ao professor' ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('[data-contador]').forEach(function (textarea) {
    const alvo = document.getElementById(textarea.getAttribute('data-contador'));
    if (!alvo) return;
    function atualizar() { alvo.textContent = String(textarea.value.length); }
    textarea.addEventListener('input', atualizar);
    atualizar();
});
</script>

<?php philoquest_layout_end(); ?>
