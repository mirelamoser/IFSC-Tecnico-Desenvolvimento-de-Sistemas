<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\MissaoExtraService;
use PhiloQuest\Services\ProfessorService;

$professorId = (int) $_SESSION['usuario_id'];
$missaoService = new MissaoExtraService();
$professorService = new ProfessorService();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_missao') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect('missoes_extras.php');
    }
    try {
        $turmaId = (int) ($_POST['turma_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $link = trim((string) ($_POST['link_referencia'] ?? ''));
        $missaoService->criarMissao($professorId, $turmaId, $titulo, $descricao, $link);
        philoquest_flash_set('sucesso', 'Missão Extra criada e enviada para a turma!');
    } catch (Exception $e) {
        philoquest_flash_set('erro', philoquest_user_error_message($e));
    }
    header('Location: missoes_extras.php');
    exit;
}

$missoes = $missaoService->listarMissoesDoProfessor($professorId);
$turmasProfessor = $professorService->listarTurmasProf($professorId);

philoquest_layout_start('Missões Extras', null, false, ['css/philoquest-missoes.css'], 'missoes_extras.php');
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fs-4 fw-semibold text-dark mb-1">Missões Extras</h1>
        <p class="text-muted small mb-0">Crie atividades especiais e acompanhe as entregas dos alunos.</p>
    </div>
    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCriarMissao">
        <i class="fas fa-bolt"></i> Criar Missão Extra
    </button>
</div>

<?php if (empty($missoes)): ?>
    <div class="card border-0 rounded-4 bg-stat-lilac text-center p-5">
        <i class="fas fa-bolt fs-1 text-primary mb-3"></i>
        <h2 class="h5 text-dark">Nenhuma missão criada ainda</h2>
        <p class="text-muted mb-4">Crie sua primeira Missão Extra para engajar a turma com uma atividade especial.</p>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCriarMissao">
            <i class="fas fa-bolt"></i> Criar Missão Extra
        </button>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($missoes as $missao):
            $totalAlunos = max(0, (int) ($missao['total_alunos'] ?? 0));
            $totalEntregas = (int) ($missao['total_entregas'] ?? 0);
            $pendentes = (int) ($missao['pendentes_avaliacao'] ?? 0);
            $descricaoCurta = mb_strlen($missao['descricao']) > 120
                ? mb_substr($missao['descricao'], 0, 120) . '...'
                : $missao['descricao'];
        ?>
        <div class="card border-0 rounded-4 bg-white shadow-none missao-card-prof">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div class="d-flex align-items-start gap-3 flex-grow-1">
                        <div class="missao-icone-raio rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="fas fa-bolt text-primary fs-4"></i>
                        </div>
                        <div>
                            <h2 class="h5 fw-semibold text-dark mb-2"><?= htmlspecialchars($missao['titulo']) ?></h2>
                            <span class="badge bg-stat-lilac text-primary border-0 px-3 py-2">
                                <?= htmlspecialchars($missao['codigo_turma']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-end">
                        <?php if ($pendentes > 0): ?>
                            <span class="badge rounded-pill missao-badge-pendente px-3 py-2">
                                <?= $pendentes ?> p/ avaliar
                            </span>
                        <?php elseif ($totalEntregas > 0): ?>
                            <span class="badge rounded-pill missao-badge-ok px-3 py-2">
                                Tudo avaliado
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill bg-light text-muted px-3 py-2">
                                Sem entregas
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($descricaoCurta)) ?></p>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted">
                        <i class="far fa-clock me-1"></i>
                        <?= date('d/m/Y H:i', strtotime($missao['data_criacao'])) ?>
                        <span class="mx-2">·</span>
                        <strong class="text-dark"><?= $totalEntregas ?>/<?= $totalAlunos ?></strong> Entregas
                    </div>
                    <a href="avaliar_missao.php?missao_id=<?= (int) $missao['id'] ?>" class="btn btn-sm btn-validar">
                        Ver entregas
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="modalCriarMissao" tabindex="-1" aria-labelledby="modalCriarMissaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header missao-modal-header border-0 text-white">
                <div>
                    <h2 class="modal-title h5 fw-semibold mb-1" id="modalCriarMissaoLabel">
                        <i class="fas fa-bolt me-2"></i>Missão Extra
                    </h2>
                    <p class="small mb-0 opacity-75">Crie uma atividade especial para sua turma</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="missoes_extras.php">
                <?= philoquest_csrf_field() ?>
                <input type="hidden" name="acao" value="criar_missao">
                <input type="hidden" name="turma_id" id="turma_id_selecionada" value="" required>
                <div class="modal-body p-4">
                    <?php if (empty($turmasProfessor)): ?>
                        <div class="alert alert-warning mb-0">
                            Você precisa vincular uma turma antes de criar missões.
                            <a href="gerenciar_turmas.php" class="alert-link">Gerenciar Turmas</a>
                        </div>
                    <?php else: ?>
                        <label class="form-label fw-semibold">Selecione a Turma</label>
                        <div class="row g-2 mb-4" id="turmas-selecao">
                            <?php foreach ($turmasProfessor as $turma): ?>
                            <div class="col-sm-6">
                                <button type="button"
                                        class="btn w-100 missao-turma-card text-start p-3 rounded-3 border"
                                        data-turma-id="<?= (int) $turma['id'] ?>">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($turma['codigo_turma']) ?></div>
                                    <div class="small text-muted"><?= (int) ($turma['total_alunos'] ?? 0) ?> alunos</div>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="titulo_missao">Título da missão</label>
                            <input type="text" name="titulo" id="titulo_missao" class="form-control" required
                                   maxlength="200" placeholder="Ex: Debate sobre ética digital">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="descricao_missao">O que os alunos devem fazer?</label>
                            <textarea name="descricao" id="descricao_missao" class="form-control" rows="5" required
                                      maxlength="2000" data-contador="contador-descricao-missao"></textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <span class="small text-muted"><span id="contador-descricao-missao">0</span>/2000</span>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold" for="link_missao">Link de referência (opcional)</label>
                            <input type="url" name="link_referencia" id="link_missao" class="form-control"
                                   placeholder="https://...">
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($turmasProfessor)): ?>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-enviar-missao" disabled>
                        <i class="fas fa-bolt"></i> Enviar Missão
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const inputTurma = document.getElementById('turma_id_selecionada');
    const btnEnviar = document.getElementById('btn-enviar-missao');
    const cardsTurma = document.querySelectorAll('.missao-turma-card');

    cardsTurma.forEach(function (card) {
        card.addEventListener('click', function () {
            cardsTurma.forEach(function (c) { c.classList.remove('active'); });
            card.classList.add('active');
            if (inputTurma) inputTurma.value = card.getAttribute('data-turma-id') || '';
            if (btnEnviar) btnEnviar.disabled = false;
        });
    });

    document.querySelectorAll('[data-contador]').forEach(function (textarea) {
        const alvoId = textarea.getAttribute('data-contador');
        const alvo = document.getElementById(alvoId);
        if (!alvo) return;
        function atualizar() { alvo.textContent = String(textarea.value.length); }
        textarea.addEventListener('input', atualizar);
        atualizar();
    });
})();
</script>

<?php philoquest_layout_end(); ?>
