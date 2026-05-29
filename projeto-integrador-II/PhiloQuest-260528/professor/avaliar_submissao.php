<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Etapa2AvaliacaoHelper;
use PhiloQuest\Enum\StatusSubmissao;
use PhiloQuest\Services\ProfessorService;

$professorId = (int) $_SESSION['usuario_id'];
$professorService = new ProfessorService();

$paginaRetorno = 'validacao.php';
$tituloPagina = 'Avaliar Atividade';

if (!isset($_GET['id'])) {
    header('Location: validacao.php');
    exit;
}

$submissaoId = (int) $_GET['id'];
if ($submissaoId <= 0) {
    philoquest_flash_set('erro', 'Identificador de submissão inválido.');
    header('Location: validacao.php');
    exit;
}

$submissao = $professorService->obterSubmissaoDoProfessor($submissaoId, $professorId);

if ($submissao === null) {
    philoquest_flash_set('erro', 'Submissão não encontrada ou você não tem permissão para avaliá-la.');
    header('Location: validacao.php');
    exit;
}

$mensagemErro = '';
$numeroEtapa = (int) ($submissao['numero_etapa'] ?? $submissao['etapa_id'] ?? 0);

if ($numeroEtapa === 5) {
    $paginaRetorno = 'trabalhos.php';
    $tituloPagina = 'Avaliar Trabalho Final';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect('avaliar_submissao.php?id=' . $submissaoId);
    }
    $statusFinal = $_POST['status'] ?? '';
    $feedbackGeral = trim((string) ($_POST['feedback'] ?? ''));
    $notasIndividuais = $_POST['notas'] ?? [];

    if ($statusFinal === '') {
        $mensagemErro = 'Selecione a avaliação final válida.';
    } else {
        try {
            $statusEnum = StatusSubmissao::tryFrom($statusFinal);
            if ($statusEnum === null) {
                throw new \InvalidArgumentException('Status de avaliação inválido.');
            }

            $feedbackAnteriorPost = (string) ($submissao['feedback'] ?? '');

            if ($numeroEtapa === 2 && !empty($notasIndividuais)) {
                $notasAnteriores = Etapa2AvaliacaoHelper::parseNotasPorPergunta($feedbackAnteriorPost);
                if ($notasAnteriores !== []) {
                    $notasIndividuais = Etapa2AvaliacaoHelper::mesclarNotasComAnteriores($notasIndividuais, $notasAnteriores);
                }
            }

            $feedbackFormatado = $numeroEtapa === 2
                ? Etapa2AvaliacaoHelper::montarFeedback($notasIndividuais, $feedbackGeral)
                : $feedbackGeral;

            $notaTrabalho = null;
            if ($numeroEtapa === 5 && $statusEnum->obterXP() > 0) {
                $notaRaw = trim((string) ($_POST['nota'] ?? ''));
                if ($notaRaw === '') {
                    throw new \InvalidArgumentException('Informe a nota do Trabalho Final (0 a 10).');
                }
                $notaTrabalho = (float) str_replace(',', '.', $notaRaw);
                if ($notaTrabalho < 0 || $notaTrabalho > 10) {
                    throw new \InvalidArgumentException('A nota deve estar entre 0 e 10.');
                }
            }

            $professorService->avaliarSubmissao(
                $submissaoId,
                $professorId,
                $statusEnum->value,
                $feedbackFormatado,
                $notaTrabalho
            );
            header('Location: ' . $paginaRetorno . '?sucesso=avaliacao_concluida');
            exit;
        } catch (\InvalidArgumentException $e) {
            $mensagemErro = $e->getMessage();
        } catch (\Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }
}

$conteudoEtapa3 = $numeroEtapa === 3
    ? $professorService->obterConteudoEtapa3PorSubmissao($submissaoId)
    : null;

$conteudoEtapa4 = $numeroEtapa === 4
    ? $professorService->obterConteudoEtapa4PorSubmissao($submissaoId)
    : null;

$perguntasArray = [];
$feedbackAnterior = (string) ($submissao['feedback'] ?? '');
$notasAnterioresPorPergunta = [];
$ehReavaliacaoEtapa2 = false;

if ($numeroEtapa === 2) {
    $perguntasArray = Etapa2AvaliacaoHelper::extrairPerguntas((string) ($submissao['descricao_submissao'] ?? ''));
    $notasAnterioresPorPergunta = Etapa2AvaliacaoHelper::parseNotasPorPergunta($feedbackAnterior);
    $ehReavaliacaoEtapa2 = $notasAnterioresPorPergunta !== [];
}

philoquest_layout_start($tituloPagina, null, false, [], $paginaRetorno);
?>

<div class="mb-3">
    <a href="<?= htmlspecialchars($paginaRetorno) ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left"></i> Voltar para a lista
    </a>
</div>

<?php if ($mensagemErro !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<form method="POST">
    <?= philoquest_csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 rounded-4 p-4 mb-4">
                <h1 class="h4 fw-semibold text-primary mb-3">
                    <?= $numeroEtapa === 5 ? 'Avaliação do Trabalho Final' : 'Avaliação de Etapa' ?>
                </h1>
                <div class="bg-light rounded-3 p-3 mb-4 small">
                    <div><strong>Aluno:</strong> <?= htmlspecialchars((string) $submissao['aluno_nome']) ?></div>
                    <div><strong>Atividade:</strong>
                        <?= $numeroEtapa === 5
                            ? 'Trabalho Final'
                            : 'Etapa ' . $numeroEtapa . ' — ' . htmlspecialchars((string) $submissao['etapa_titulo']) ?>
                    </div>
                    <div><strong>Data de Envio:</strong> <?= date('d/m/Y H:i', strtotime((string) $submissao['data_submissao'])) ?></div>
                </div>

                <?php if ($numeroEtapa === 3): ?>
                    <h3 class="h6 fw-semibold text-dark mb-3">Resposta Conceitual do Aluno</h3>
                    <?php if ($conteudoEtapa3 === null): ?>
                        <p class="text-danger mb-0">Não foi possível carregar os dados estruturados desta Etapa 3.</p>
                    <?php else: ?>
                        <div class="card border-0 bg-stat-lilac rounded-3 mb-3">
                            <div class="card-body">
                                <div class="small text-muted text-uppercase fw-semibold mb-1">Pergunta selecionada</div>
                                <p class="fw-semibold text-primary mb-0">
                                    #<?= (int) $conteudoEtapa3['pergunta_numero'] ?> —
                                    <?= htmlspecialchars($conteudoEtapa3['pergunta_texto']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Resposta Conceitual</label>
                            <div class="bg-light border rounded-3 p-3">
                                <?= nl2br(htmlspecialchars($conteudoEtapa3['resposta_conceitual'])) ?>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted text-uppercase">Conceitos-Chave</label>
                            <?php if ($conteudoEtapa3['conceitos'] === []): ?>
                                <p class="text-muted mb-0">Nenhum conceito-chave registrado.</p>
                            <?php else: ?>
                                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                                    <?php foreach ($conteudoEtapa3['conceitos'] as $termo): ?>
                                    <li>
                                        <span class="badge rounded-pill bg-stat-lilac text-primary border px-3 py-2 fs-6 fw-semibold">
                                            <?= htmlspecialchars((string) $termo) ?>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($numeroEtapa === 4): ?>
                    <h3 class="h6 fw-semibold text-dark mb-3">Filósofos e Associação</h3>
                    <?php if ($conteudoEtapa4 === []): ?>
                        <p class="text-danger mb-0">Não foi possível carregar os dados estruturados desta Etapa 4.</p>
                    <?php else: ?>
                        <?php foreach ($conteudoEtapa4 as $bloco): ?>
                        <div class="card border-0 bg-stat-lilac rounded-4 mb-4">
                            <div class="card-body">
                                <div class="small text-muted text-uppercase fw-semibold mb-2">Conceito-Chave</div>
                                <p class="h6 fw-semibold text-primary mb-4">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    <?= htmlspecialchars($bloco['termo']) ?>
                                </p>

                                <?php foreach ($bloco['filosofos'] as $fil): ?>
                                <div class="card border-0 rounded-3 mb-3">
                                    <div class="card-body">
                                        <h4 class="h6 fw-semibold text-primary mb-3">
                                            <?= htmlspecialchars($fil['nome']) ?>
                                            <span class="badge bg-white text-primary border ms-1">
                                                <?= htmlspecialchars($fil['epoca_rotulo']) ?>
                                            </span>
                                        </h4>
                                        <p class="mb-2">
                                            <span class="small text-muted text-uppercase fw-semibold">Linha de Pensamento</span><br>
                                            <?= htmlspecialchars($fil['linha_pensamento']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <span class="small text-muted text-uppercase fw-semibold">Ideias Principais</span>
                                        </p>
                                        <div class="bg-light border rounded-3 p-3 mb-2">
                                            <?= nl2br(htmlspecialchars($fil['ideias_principais'])) ?>
                                        </div>
                                        <p class="mb-1">
                                            <span class="small text-muted text-uppercase fw-semibold">Citação / Trecho</span>
                                        </p>
                                        <div class="bg-light border rounded-3 p-3 mb-0 fst-italic">
                                            <?= nl2br(htmlspecialchars($fil['citacao'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php elseif ($numeroEtapa === 2): ?>
                    <h3 class="h6 fw-semibold text-dark mb-3">Avaliação por pergunta</h3>
                    <?php if ($perguntasArray === []): ?>
                        <p class="text-danger">Não foi possível separar as perguntas. O texto enviado está vazio ou mal formatado.</p>
                    <?php endif; ?>
                    <?php foreach ($perguntasArray as $indice => $textoPergunta):
                        $numPergunta = $indice + 1;
                        $notaAntiga = $notasAnterioresPorPergunta[$numPergunta] ?? '';
                        $bloquearProfessor = Etapa2AvaliacaoHelper::notaDeveSerMantida($notaAntiga);
                    ?>
                    <div class="card card-pergunta border-0 rounded-3 mb-3 <?= $bloquearProfessor ? 'bloqueada' : '' ?>">
                        <div class="card-body">
                            <h4 class="h6 fw-semibold text-primary">Pergunta <?= $numPergunta ?></h4>
                            <p class="mb-3"><?= nl2br(htmlspecialchars($textoPergunta)) ?></p>
                            <label class="form-label fw-semibold small">Conceito da Pergunta</label>
                            <?php if ($bloquearProfessor): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($notaAntiga) ?>
                                    </span>
                                    <span class="text-muted small fst-italic">(Mantido)</span>
                                </div>
                                <input type="hidden" name="notas[<?= $indice ?>]" value="<?= htmlspecialchars($notaAntiga) ?>">
                            <?php else: ?>
                                <select name="notas[<?= $indice ?>]" class="form-select" required>
                                    <option value="" disabled <?= $notaAntiga === '' ? 'selected' : '' ?>>Avalie esta pergunta...</option>
                                    <option value="Refazer (Não atendeu)" <?= $notaAntiga === 'Refazer (Não atendeu)' ? 'selected' : '' ?>>Refazer (Não atendeu)</option>
                                    <option value="Regular (Pode melhorar)" <?= $notaAntiga === 'Regular (Pode melhorar)' ? 'selected' : '' ?>>Regular (Pode melhorar)</option>
                                    <option value="Bom (Atendeu)" <?= $notaAntiga === 'Bom (Atendeu)' ? 'selected' : '' ?>>Bom (Atendeu)</option>
                                    <option value="Excelente (Muito criativo)" <?= $notaAntiga === 'Excelente (Muito criativo)' ? 'selected' : '' ?>>Excelente (Muito criativo)</option>
                                </select>
                                <?php if ($ehReavaliacaoEtapa2 && $notaAntiga !== ''): ?>
                                    <div class="small text-warning mt-2">
                                        <i class="fas fa-info-circle"></i> Nota anterior: <?= htmlspecialchars($notaAntiga) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <h3 class="h6 fw-semibold text-dark mb-3">
                        <?= $numeroEtapa === 5 ? 'Trabalho Final enviado' : 'Conteúdo enviado' ?>
                    </h3>
                    <?php if ($numeroEtapa !== 5 && !empty($submissao['titulo_submissao'])): ?>
                        <p class="mb-2"><strong>Título:</strong> <?= htmlspecialchars((string) $submissao['titulo_submissao']) ?></p>
                    <?php endif; ?>
                    <div class="bg-light border rounded-3 p-3">
                        <?= nl2br(htmlspecialchars((string) ($submissao['descricao_submissao'] ?? ''))) ?>
                    </div>
                    <?php if (!empty($submissao['link_submissao'])): ?>
                        <p class="mt-3 mb-0">
                            <strong>Link:</strong>
                            <a href="<?= htmlspecialchars((string) $submissao['link_submissao']) ?>" target="_blank" class="text-primary">
                                <?= htmlspecialchars((string) $submissao['link_submissao']) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 rounded-4 form-panel p-4 sticky-top" style="top: 1rem;">
                <h2 class="h5 fw-semibold text-dark mb-3">Parecer Final</h2>
                <div class="mb-3">
                    <label class="form-label">Comentário para o aluno</label>
                    <textarea name="feedback" class="form-control" rows="5" required
                        placeholder="Escreva aqui seu comentário geral orientando o aluno..."></textarea>
                    <?php if ($numeroEtapa === 2): ?>
                        <div class="form-text">As notas individuais de cada pergunta serão anexadas automaticamente a este feedback.</div>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="form-label">Status Final (XP da Atividade)</label>
                    <select name="status" id="status-final" class="form-select border-primary fw-semibold" required>
                        <option value="">Selecione o resultado final...</option>
                        <option value="<?= StatusSubmissao::NECESSITA_REVISAO->value ?>">Reprovar - Necessita Revisão (0 XP)</option>
                        <option value="<?= StatusSubmissao::APROVADO->value ?>">Aprovado (<?= StatusSubmissao::APROVADO->obterXP() ?> XP)</option>
                        <option value="<?= StatusSubmissao::APROVADO_BEM_FEITO->value ?>">Bem Feito (<?= StatusSubmissao::APROVADO_BEM_FEITO->obterXP() ?> XP)</option>
                        <option value="<?= StatusSubmissao::APROVADO_EXCELENTE->value ?>">Excelente! (<?= StatusSubmissao::APROVADO_EXCELENTE->obterXP() ?> XP)</option>
                    </select>
                </div>
                <?php if ($numeroEtapa === 5): ?>
                <div class="mb-4" id="campo-nota-etapa5" style="display: none;">
                    <label class="form-label" for="nota-trabalho">Nota do Trabalho Final (0 a 10)</label>
                    <input type="number" name="nota" id="nota-trabalho" class="form-control"
                           min="0" max="10" step="0.1"
                           placeholder="Ex: 8.5"
                           value="<?= htmlspecialchars((string) ($_POST['nota'] ?? '')) ?>">
                    <div class="form-text">Obrigatória ao aprovar o trabalho. Não é necessária ao solicitar revisão.</div>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-check"></i> Salvar e Enviar Avaliação
                </button>
            </div>
        </div>
    </div>
</form>

<?php if ($numeroEtapa === 5): ?>
<script>
(function () {
    const statusSelect = document.getElementById('status-final');
    const campoNota = document.getElementById('campo-nota-etapa5');
    const inputNota = document.getElementById('nota-trabalho');
    if (!statusSelect || !campoNota || !inputNota) return;

    const statusRevisao = <?= json_encode(StatusSubmissao::NECESSITA_REVISAO->value) ?>;

    function atualizarCampoNota() {
        const exigeNota = statusSelect.value !== '' && statusSelect.value !== statusRevisao;
        campoNota.style.display = exigeNota ? 'block' : 'none';
        inputNota.required = exigeNota;
        if (!exigeNota) inputNota.value = '';
    }

    statusSelect.addEventListener('change', atualizarCampoNota);
    atualizarCampoNota();
})();
</script>
<?php endif; ?>

<?php philoquest_layout_end(); ?>
