<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Exception\Etapa3Exception;
use PhiloQuest\Exception\Etapa4Exception;
use PhiloQuest\Services\AlunoService;

$alunoId = (int) $_SESSION['usuario_id'];
$alunoService = new AlunoService();
$mensagemErro = '';
$mensagemSucesso = '';

// 1. Busca o progresso do aluno
$progresso = $alunoService->obterProgressoCiclo($alunoId);
$etapaAtual = $progresso['concluidas'] + 1; 

// 2. Verifica o status da etapa atual (Aguardando, Revisão, etc)
$statusSubmissao = $alunoService->verificarStatusSubmissao($alunoId, $etapaAtual);

// 3. Busca dados da etapa atual se estiver em revisão (para preencher o form)
$dadosSubmissaoAtual = null;
if ($statusSubmissao === 'NECESSITA_REVISAO') {
    $dadosSubmissaoAtual = $alunoService->obterDadosSubmissao($alunoId, $etapaAtual);
}

// 4. Processamento de Envios (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar_etapa') {
    if (!philoquest_csrf_verify()) {
        philoquest_csrf_fail_redirect('ciclo.php');
    }
    try {
        $progresso = $alunoService->obterProgressoCiclo($alunoId);
        $etapaEnviar = (int) ($_POST['etapa_numero'] ?? ($progresso['concluidas'] + 1));

        if ($etapaEnviar !== $progresso['concluidas'] + 1) {
            throw new Exception('Etapa inválida para envio.');
        }

        if (!$alunoService->podeEnviarEtapa($alunoId, $etapaEnviar)) {
            throw new Exception('Esta etapa já foi enviada. Aguarde a avaliação do professor ou a solicitação de revisão.');
        }

        if ($etapaEnviar === 1) {
            $titulo = trim($_POST['titulo'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $link = trim($_POST['link'] ?? '');
            $alunoService->submeterEtapa($alunoId, 1, $titulo, $descricao, $link);
        } elseif ($etapaEnviar === 2) {
            $perguntas = array_filter(array_map('trim', $_POST['perguntas'] ?? []));
            if (count($perguntas) < 6) {
                throw new Exception('Preencha pelo menos as 6 perguntas obrigatórias.');
            }

            $textoFinal = "Questionamentos Elaborados:\n\n";
            foreach ($perguntas as $idx => $p) {
                $textoFinal .= ($idx + 1) . '. ' . $p . "\n";
            }

            $alunoService->submeterEtapa($alunoId, 2, 'Etapa 2 - Questionamentos', $textoFinal, '');
        } elseif ($etapaEnviar === 3) {
            $perguntaNumero = (int) ($_POST['pergunta_numero'] ?? 0);
            $respostaConceitual = trim((string) ($_POST['resposta_conceitual'] ?? ''));
            $conceitos = is_array($_POST['conceitos'] ?? null) ? $_POST['conceitos'] : [];
            $alunoService->submeterEtapa3($alunoId, $perguntaNumero, $respostaConceitual, $conceitos);
        } elseif ($etapaEnviar === 4) {
            $filosofos = is_array($_POST['filosofos'] ?? null) ? $_POST['filosofos'] : [];
            $alunoService->submeterEtapa4($alunoId, $filosofos);
        } elseif ($etapaEnviar === 5) {
            $trabalhoFinal = trim((string) ($_POST['trabalho_final'] ?? ''));
            $alunoService->submeterEtapa($alunoId, 5, 'Trabalho Final', $trabalhoFinal, '');
        } else {
            throw new Exception('Etapa não disponível para envio.');
        }

        header('Location: ciclo.php?sucesso=1');
        exit;
    } catch (Etapa3Exception | Etapa4Exception | Exception $e) {
        $mensagemErro = $e->getMessage();
        $progresso = $alunoService->obterProgressoCiclo($alunoId);
        $etapaAtual = $progresso['concluidas'] + 1;
        $statusSubmissao = $alunoService->verificarStatusSubmissao($alunoId, $etapaAtual);
        if ($statusSubmissao === 'NECESSITA_REVISAO') {
            $dadosSubmissaoAtual = $alunoService->obterDadosSubmissao($alunoId, $etapaAtual);
        }
    }
}

if (isset($_GET['sucesso'])) $mensagemSucesso = "Etapa enviada com sucesso! Aguarde a avaliação.";

philoquest_layout_start('Ciclo de Aprendizagem');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Ciclo de Aprendizagem</h1>

<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<?php if ($etapaAtual > 1): ?>
    <p class="text-uppercase text-muted small fw-semibold mb-3">Suas Etapas Concluídas</p>
    <div class="accordion mb-4" id="historicoEtapas">
        <?php
        for ($i = 1; $i < $etapaAtual; $i++):
            $dadosAntigos = $alunoService->obterDadosSubmissao($alunoId, $i);
            if (!$dadosAntigos) {
                continue;
            }
            $accId = 'hist' . $i;
            $tituloEtapaHistorico = match ($i) {
                1 => 'Identificação do Problema',
                5 => 'Trabalho Final',
                default => $dadosAntigos['titulo_submissao'] ?? 'Concluída',
            };
        ?>
        <div class="accordion-item border-0 rounded-3 mb-2 overflow-hidden">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse"
                    data-bs-target="#<?= $accId ?>" aria-expanded="false">
                    <div class="row align-items-center g-0 w-100 flex-grow-1">
                        <div class="col-4 text-start text-truncate pe-2">
                            Etapa <?= $i ?>: <?= htmlspecialchars($tituloEtapaHistorico) ?>
                        </div>
                        <div class="col-4 d-flex justify-content-center">
                            <span class="badge bg-success text-nowrap">
                                <i class="fas fa-check"></i> Aprovada
                            </span>
                        </div>
                        <div class="col-4"></div>
                    </div>
                </button>
            </h2>
            <div id="<?= $accId ?>" class="accordion-collapse collapse" data-bs-parent="#historicoEtapas">
                <div class="accordion-body">
                    <?php if ($i === 1): ?>
                        <p class="mb-2"><strong>Título da Reflexão:</strong></p>
                        <p class="mb-3"><?= htmlspecialchars($dadosAntigos['titulo_submissao'] ?? '') ?></p>
                        <p class="mb-2"><strong>Descrição Detalhada:</strong></p>
                        <div class="bg-light border rounded-3 p-3 mb-3">
                            <?= nl2br(htmlspecialchars($dadosAntigos['descricao_submissao'] ?? '')) ?>
                        </div>
                        <?php if (!empty($dadosAntigos['link_submissao'])): ?>
                            <p class="mb-0"><strong>Link de Referência:</strong>
                                <a href="<?= htmlspecialchars($dadosAntigos['link_submissao']) ?>" target="_blank" class="text-primary">
                                    <?= htmlspecialchars($dadosAntigos['link_submissao']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php elseif ($i === 2): ?>
                        <strong>Questionamentos Elaborados:</strong>
                        <div class="bg-light border rounded-3 p-3 mt-2">
                            <?= nl2br(htmlspecialchars($dadosAntigos['descricao_submissao'])) ?>
                        </div>
                    <?php elseif ($i === 3):
                        $detalheEtapa3 = $alunoService->obterRespostaEtapa3($alunoId);
                    ?>
                        <?php if ($detalheEtapa3): ?>
                            <p class="mb-2"><strong>Pergunta respondida:</strong>
                                <?= htmlspecialchars($detalheEtapa3['pergunta_texto']) ?></p>
                            <p class="mb-2"><strong>Resposta conceitual:</strong></p>
                            <div class="bg-light border rounded-3 p-3 mb-2">
                                <?= nl2br(htmlspecialchars($detalheEtapa3['resposta_conceitual'])) ?>
                            </div>
                            <?php if (!empty($detalheEtapa3['conceitos'])): ?>
                                <p class="mb-1"><strong>Conceitos-Chave:</strong></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($detalheEtapa3['conceitos'] as $termo): ?>
                                        <span class="badge bg-stat-lilac text-primary"><?= htmlspecialchars((string) $termo) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-light border rounded-3 p-3 mt-2">
                                <?= nl2br(htmlspecialchars($dadosAntigos['descricao_submissao'])) ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($i === 4):
                        $conceitosHist = $alunoService->listarConceitosEtapa3Aprovados($alunoId);
                        $filosofosHist = $alunoService->obterFilosofosEtapa4PorConceito($alunoId);
                    ?>
                        <?php foreach ($conceitosHist as $conceitoHist):
                            $cidHist = (int) $conceitoHist['id'];
                            $listaHist = $filosofosHist[$cidHist] ?? [];
                        ?>
                        <div class="mb-3">
                            <span class="badge bg-stat-lilac text-primary mb-2">
                                <?= htmlspecialchars($conceitoHist['termo']) ?>
                            </span>
                            <?php if ($listaHist === []): ?>
                                <p class="text-muted small mb-0">Nenhum filósofo registrado.</p>
                            <?php else: ?>
                                <?php foreach ($listaHist as $filHist): ?>
                                <div class="card border-0 rounded-3 mb-2">
                                    <div class="card-body small">
                                        <strong><?= htmlspecialchars($filHist['nome']) ?></strong>
                                        <span class="text-muted">— <?= htmlspecialchars(\PhiloQuest\Etapa4FilosofoHelper::rotuloEpoca($filHist['epoca'])) ?></span>
                                        <p class="mb-1 mt-2"><strong>Linha:</strong> <?= htmlspecialchars($filHist['linha_pensamento']) ?></p>
                                        <p class="mb-1"><strong>Ideias:</strong> <?= nl2br(htmlspecialchars($filHist['ideias_principais'])) ?></p>
                                        <p class="mb-0 fst-italic"><strong>Citação:</strong> <?= nl2br(htmlspecialchars($filHist['citacao'])) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php elseif ($i === 5): ?>
                        <p class="mb-2"><strong>Trabalho Final:</strong></p>
                        <div class="bg-light border rounded-3 p-3 mb-2">
                            <?= nl2br(htmlspecialchars($dadosAntigos['descricao_submissao'] ?? '')) ?>
                        </div>
                        <?php if (isset($dadosAntigos['nota']) && $dadosAntigos['nota'] !== null && $dadosAntigos['nota'] !== ''): ?>
                            <?php
                                $notaHist = (float) $dadosAntigos['nota'];
                                $notaHistExib = fmod($notaHist, 1.0) === 0.0
                                    ? (string) (int) $notaHist
                                    : number_format($notaHist, 1, ',', '.');
                            ?>
                            <p class="mb-0"><strong>Nota:</strong> <?= htmlspecialchars($notaHistExib) ?>/10</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($dadosAntigos['descricao_submissao'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <hr class="mb-4 text-muted">
<?php endif; ?>

<div class="card border-0 rounded-4 form-panel p-4">
<?php
if ($progresso['concluidas'] >= 5) {
    include 'forms/concluido.php';
} elseif ($alunoService->etapaAguardandoAvaliacao($alunoId, $etapaAtual)) {
    include 'forms/aguardando.php';
} elseif ($etapaAtual === 3 && !$alunoService->etapa2EstaAprovada($alunoId)) {
    echo '<div class="alert alert-warning mb-0"><i class="fas fa-lock me-1"></i> '
        . 'A <strong>Etapa 3</strong> só fica disponível após a aprovação da Etapa 2.</div>';
} elseif ($etapaAtual === 4 && !$alunoService->etapa3EstaAprovada($alunoId)) {
    echo '<div class="alert alert-warning mb-0"><i class="fas fa-lock me-1"></i> '
        . 'A <strong>Etapa 4</strong> só fica disponível após a aprovação da Etapa 3.</div>';
} elseif ($etapaAtual === 5 && !$alunoService->etapa4EstaAprovada($alunoId)) {
    echo '<div class="alert alert-warning mb-0"><i class="fas fa-lock me-1"></i> '
        . 'A <strong>Etapa 5</strong> só fica disponível após a aprovação da Etapa 4.</div>';
} elseif ($alunoService->podeEnviarEtapa($alunoId, $etapaAtual)) {
    $arquivo = "forms/etapa{$etapaAtual}.php";
    if (file_exists($arquivo)) {
        include $arquivo;
    } else {
        echo "<h3 class=\"h5\">Em breve: Etapa {$etapaAtual}</h3>";
    }
} else {
    echo '<div class="alert alert-info mb-0">Esta etapa já foi concluída. Atualize a página se não vir a próxima etapa.</div>';
}
?>
</div>

<?php philoquest_layout_end(); ?>