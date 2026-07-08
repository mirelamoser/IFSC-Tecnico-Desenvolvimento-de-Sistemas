<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\ProfessorService;

$professorService = new ProfessorService();
$professorId = (int) $_SESSION['usuario_id'];
$selfUrl = 'gerenciar_turmas.php';

philoquest_processar_vinculo_turma_post($professorService, $professorId, $selfUrl);

$minhasTurmas = $professorService->listarTurmasComContagemAlunos($professorId);
$turmasDisponiveis = $professorService->listarTurmasDisponiveis();

philoquest_layout_start('Gerenciamento de Turmas');
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fs-4 fw-semibold text-dark mb-1">Gerenciamento de Turmas</h1>
        <p class="text-muted small mb-0">Vincule e acompanhe o andamento das suas turmas.</p>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Turmas Vinculadas</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= count($minhasTurmas) ?></span>
                    <i class="fas fa-chalkboard-teacher fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Turmas Disponíveis</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= count($turmasDisponiveis) ?></span>
                    <i class="fas fa-link fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="card border-0 rounded-4 table-panel p-4 mb-4">
    <h3 class="h5 fw-semibold text-dark mb-3">Suas Turmas</h3>
    <?php if (empty($minhasTurmas)): ?>
        <p class="text-muted mb-0">Você ainda não vinculou nenhuma turma. Use o formulário abaixo para assumir uma turma existente.</p>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($minhasTurmas as $turma): ?>
            <?php $quantidadeAlunos = (int) ($turma['total_alunos'] ?? 0); ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="h6 fw-semibold text-primary mb-0">
                                <i class="fas fa-book me-1"></i><?= htmlspecialchars($turma['codigo_turma']) ?>
                            </h4>
                        </div>
                        <p class="text-muted small mb-3">Criada em <?= htmlspecialchars(date('d/m/Y', strtotime($turma['criacao']))) ?></p>
                        <div class="d-flex align-items-center gap-2 mb-3 p-2 bg-light rounded-3">
                            <code class="flex-grow-1 mb-0"><?= htmlspecialchars($turma['codigo_turma']) ?></code>
                            <button type="button" class="btn btn-outline-primary btn-sm btn-copiar-codigo"
                                data-codigo="<?= htmlspecialchars($turma['codigo_turma'], ENT_QUOTES) ?>"
                                title="Copiar código">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-3 text-primary">
                            <i class="fas fa-users fs-4"></i>
                            <div>
                                <div class="fw-bold fs-5 lh-1"><?= $quantidadeAlunos ?></div>
                                <div class="small text-muted">Total de Alunos</div>
                            </div>
                        </div>
                        <a href="detalhes_turma.php?turma_id=<?= (int) $turma['id'] ?>" class="btn ver-detalhes-btn mt-auto">
                            Ver Detalhes da Turma
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<section class="card border-0 rounded-4 form-panel p-4" id="vincular">
    <h3 class="h5 fw-semibold text-dark mb-2">Vincular Turma Existente</h3>
    <p class="text-muted small mb-3">Digite o código da turma que veio do CSV institucional e assuma sua responsabilidade.</p>
    <form method="POST" action="">
        <?= philoquest_csrf_field() ?>
        <div class="mb-3">
            <label for="codigo_turma" class="form-label">Código da Turma</label>
            <input type="text" id="codigo_turma" name="codigo_turma" class="form-control" placeholder="Ex: 103HS2026" required>
        </div>
        <button type="submit" name="vincular_turma" class="btn btn-primary">Vincular Turma</button>
    </form>
    <?php if (!empty($turmasDisponiveis)): ?>
        <div class="mt-4">
            <strong class="small text-muted d-block mb-2">Turmas disponíveis:</strong>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($turmasDisponiveis as $turma): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                        onclick="document.getElementById('codigo_turma').value = '<?= htmlspecialchars($turma['codigo_turma'], ENT_QUOTES) ?>'">
                        <?= htmlspecialchars($turma['codigo_turma']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    function copiarTextoFallback(texto) {
        var area = document.createElement('textarea');
        area.value = texto;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        document.body.appendChild(area);
        area.select();
        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(area);
        return ok;
    }

    async function copiarCodigoTurma(codigo, botao) {
        var icone = botao.querySelector('i');
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(codigo);
            } else if (!copiarTextoFallback(codigo)) {
                throw new Error('clipboard indisponível');
            }
            if (icone) {
                icone.classList.replace('fa-copy', 'fa-check');
                setTimeout(function () {
                    icone.classList.replace('fa-check', 'fa-copy');
                }, 1500);
            }
        } catch (e) {
            window.prompt('Copie o código da turma:', codigo);
        }
    }

    document.querySelectorAll('.btn-copiar-codigo').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var codigo = botao.getAttribute('data-codigo') || '';
            if (codigo !== '') {
                copiarCodigoTurma(codigo, botao);
            }
        });
    });
})();
</script>

<?php philoquest_layout_end(); ?>