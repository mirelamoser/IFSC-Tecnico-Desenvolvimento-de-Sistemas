<?php

declare(strict_types=1);

use PhiloQuest\Services\AlunoService;

/** @var AlunoService $alunoService */
/** @var int $alunoId */
/** @var string|null $statusSubmissao */

$perguntasEtapa2 = $alunoService->listarPerguntasEtapa2($alunoId);
$dadosRespostaEtapa3 = ($statusSubmissao ?? '') === 'NECESSITA_REVISAO'
    ? $alunoService->obterRespostaEtapa3($alunoId)
    : null;

$perguntaSelecionada = (int) ($dadosRespostaEtapa3['pergunta_numero'] ?? 0);
$respostaSalva = (string) ($dadosRespostaEtapa3['resposta_conceitual'] ?? '');
$conceitosSalvos = $dadosRespostaEtapa3['conceitos'] ?? [''];
if ($conceitosSalvos === []) {
    $conceitosSalvos = [''];
}
$totalConceitos = max(1, count($conceitosSalvos));
?>

<h2 class="h5 fw-semibold text-primary mb-2">Etapa 3: Resposta Conceitual</h2>
<p class="text-muted small mb-4">
    Escolha uma das suas perguntas da Etapa 2, elabore a resposta conceitual e registre os conceitos-chave relacionados.
</p>

<?php if ($perguntasEtapa2 === []): ?>
    <div class="alert alert-warning mb-0">
        <i class="fas fa-lock me-1"></i>
        Conclua e obtenha aprovação na <strong>Etapa 2</strong> antes de enviar esta fase.
    </div>
<?php else: ?>

    <?php if (!empty($dadosSubmissaoAtual['feedback'] ?? '')): ?>
        <?php $fb = (string) $dadosSubmissaoAtual['feedback']; ?>
        <div class="alert alert-danger mb-4">
            <strong><i class="fas fa-exclamation-triangle"></i> O professor solicitou revisão:</strong><br>
            <span class="small d-block mt-1"><?= nl2br(htmlspecialchars($fb)) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="ciclo.php" id="form-etapa3">
        <?= philoquest_csrf_field() ?>
        <input type="hidden" name="acao" value="enviar_etapa">
        <input type="hidden" name="etapa_numero" value="3">

        <div class="mb-4">
            <label for="pergunta_numero" class="form-label fw-semibold">Pergunta da Etapa 2 *</label>
            <select name="pergunta_numero" id="pergunta_numero" class="form-select" required>
                <option value="" disabled <?= $perguntaSelecionada === 0 ? 'selected' : '' ?>>Selecione a pergunta que deseja responder...</option>
                <?php foreach ($perguntasEtapa2 as $pergunta): ?>
                    <option value="<?= (int) $pergunta['numero'] ?>"
                        <?= $perguntaSelecionada === (int) $pergunta['numero'] ? 'selected' : '' ?>>
                        <?= (int) $pergunta['numero'] ?>. <?= htmlspecialchars($pergunta['texto']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Apenas perguntas aprovadas na sua submissão da Etapa 2.</div>
        </div>

        <div class="mb-4">
            <label for="resposta_conceitual" class="form-label fw-semibold">Resposta Conceitual *</label>
            <textarea name="resposta_conceitual" id="resposta_conceitual" class="form-control" rows="8" required
                minlength="20" maxlength="10000"
                placeholder="Desenvolva sua resposta filosófica de forma argumentada..."><?= htmlspecialchars($respostaSalva) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Conceitos-Chave *</label>
            <p class="text-muted small">Adicione os termos ou conceitos centrais da sua resposta (mínimo 1).</p>
        </div>

        <div id="conceitos-lista" class="mb-3">
            <?php foreach ($conceitosSalvos as $idx => $termo): ?>
            <div class="mb-2 conceito-item d-flex gap-2 align-items-center">
                <input type="text" name="conceitos[]" class="form-control"
                    value="<?= htmlspecialchars((string) $termo) ?>"
                    placeholder="Ex: ética, liberdade, justiça..."
                    <?= $idx === 0 ? 'required' : '' ?>
                    maxlength="200">
                <?php if ($idx > 0): ?>
                <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0"
                    onclick="this.closest('.conceito-item').remove();" aria-label="Remover conceito">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="add-conceito" class="btn btn-outline-primary w-100 mb-3 <?= $totalConceitos >= 10 ? 'd-none' : '' ?>">
            <i class="fas fa-plus"></i> Adicionar Conceito-Chave
        </button>

        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-paper-plane"></i>
            <?= ($statusSubmissao ?? '') === 'NECESSITA_REVISAO' ? 'Reenviar Etapa 3' : 'Enviar Etapa 3' ?>
        </button>
    </form>

    <script>
    (function() {
        const btnAdd = document.getElementById('add-conceito');
        const lista = document.getElementById('conceitos-lista');
        let contador = <?= $totalConceitos ?>;

        if (btnAdd && lista) {
            btnAdd.addEventListener('click', () => {
                if (contador >= 10) return;
                contador++;
                const div = document.createElement('div');
                div.className = 'mb-2 conceito-item d-flex gap-2 align-items-center';
                div.innerHTML = `
                    <input type="text" name="conceitos[]" class="form-control" maxlength="200"
                        placeholder="Ex: ética, liberdade, justiça...">
                    <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0"
                        onclick="this.closest('.conceito-item').remove();" aria-label="Remover conceito">
                        <i class="fas fa-trash-alt"></i>
                    </button>`;
                lista.appendChild(div);
                if (contador >= 10) btnAdd.classList.add('d-none');
            });
        }
    })();
    </script>

<?php endif; ?>
