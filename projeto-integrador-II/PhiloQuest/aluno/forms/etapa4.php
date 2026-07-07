<?php

declare(strict_types=1);

use PhiloQuest\Enum\EpocaFilosofo;
use PhiloQuest\Services\AlunoService;

/** @var AlunoService $alunoService */
/** @var int $alunoId */
/** @var string|null $statusSubmissao */
/** @var array|null $dadosSubmissaoAtual */

$conceitosEtapa3 = $alunoService->listarConceitosEtapa3Aprovados($alunoId);
$filosofosSalvos = ($statusSubmissao ?? '') === 'NECESSITA_REVISAO'
    ? $alunoService->obterFilosofosEtapa4PorConceito($alunoId)
    : [];
?>

<h2 class="h5 fw-semibold text-primary mb-2">Etapa 4: Filósofos e Associação</h2>
<p class="text-muted small mb-4">
    Para cada conceito-chave aprovado na Etapa 3, associe um ou mais filósofos com suas ideias e citações.
</p>

<?php if ($conceitosEtapa3 === []): ?>
    <div class="alert alert-warning mb-0">
        <i class="fas fa-lock me-1"></i>
        Conclua e obtenha aprovação na <strong>Etapa 3</strong> antes de enviar esta fase.
    </div>
<?php else: ?>

    <?php if (!empty($dadosSubmissaoAtual['feedback'] ?? '')): ?>
        <div class="alert alert-danger mb-4">
            <strong><i class="fas fa-exclamation-triangle"></i> O professor solicitou revisão:</strong><br>
            <span class="small d-block mt-1"><?= nl2br(htmlspecialchars((string) $dadosSubmissaoAtual['feedback'])) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="ciclo.php" id="form-etapa4">
        <?= philoquest_csrf_field() ?>
        <input type="hidden" name="acao" value="enviar_etapa">
        <input type="hidden" name="etapa_numero" value="4">

        <?php foreach ($conceitosEtapa3 as $conceito):
            $cid = (int) $conceito['id'];
            $listaFil = $filosofosSalvos[$cid] ?? [['nome' => '', 'epoca' => '', 'linha_pensamento' => '', 'ideias_principais' => '', 'citacao' => '']];
            if ($listaFil === []) {
                $listaFil = [['nome' => '', 'epoca' => '', 'linha_pensamento' => '', 'ideias_principais' => '', 'citacao' => '']];
            }
        ?>
        <div class="card border-0 bg-stat-lilac rounded-4 mb-4 conceito-bloco" data-conceito-id="<?= $cid ?>">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h3 class="h6 fw-semibold text-primary mb-0">
                        <i class="fas fa-lightbulb me-1"></i>
                        <?= htmlspecialchars($conceito['termo']) ?>
                    </h3>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-filosofo"
                            data-conceito="<?= $cid ?>">
                        <i class="fas fa-plus"></i> Adicionar Filósofo
                    </button>
                </div>

                <div class="filosofos-lista" data-conceito-lista="<?= $cid ?>">
                    <?php foreach ($listaFil as $idx => $fil): ?>
                    <div class="card border-0 rounded-3 mb-3 filosofo-item">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-white text-primary border">Filósofo</span>
                                <?php if ($idx > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-filosofo" title="Remover">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome do Filósofo *</label>
                                    <input type="text" class="form-control" required maxlength="200"
                                        name="filosofos[<?= $cid ?>][<?= $idx ?>][nome]"
                                        value="<?= htmlspecialchars($fil['nome']) ?>"
                                        placeholder="Ex: Platão, Aristóteles...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Época *</label>
                                    <select class="form-select" required name="filosofos[<?= $cid ?>][<?= $idx ?>][epoca]">
                                        <option value="" disabled <?= ($fil['epoca'] ?? '') === '' ? 'selected' : '' ?>>Selecione...</option>
                                        <?php foreach (EpocaFilosofo::cases() as $epocaCase): ?>
                                        <option value="<?= $epocaCase->value ?>"
                                            <?= ($fil['epoca'] ?? '') === $epocaCase->value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($epocaCase->rotulo()) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Linha de Pensamento *</label>
                                    <input type="text" class="form-control" required maxlength="255"
                                        name="filosofos[<?= $cid ?>][<?= $idx ?>][linha_pensamento]"
                                        value="<?= htmlspecialchars($fil['linha_pensamento']) ?>"
                                        placeholder="Ex: Idealismo, Existencialismo...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Ideias Principais *</label>
                                    <textarea class="form-control" rows="3" required
                                        name="filosofos[<?= $cid ?>][<?= $idx ?>][ideias_principais]"
                                        placeholder="Resuma as ideias centrais deste filósofo..."><?= htmlspecialchars($fil['ideias_principais']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Citação / Trecho *</label>
                                    <textarea class="form-control" rows="2" required
                                        name="filosofos[<?= $cid ?>][<?= $idx ?>][citacao]"
                                        placeholder="Trecho ou citação representativa..."><?= htmlspecialchars($fil['citacao']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-paper-plane"></i>
            <?= ($statusSubmissao ?? '') === 'NECESSITA_REVISAO' ? 'Reenviar Etapa 4' : 'Enviar Etapa 4' ?>
        </button>
    </form>

    <template id="tpl-filosofo-card">
        <div class="card border-0 rounded-3 mb-3 filosofo-item">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-white text-primary border">Filósofo</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-filosofo" title="Remover">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <div class="row g-3 filosofo-fields"></div>
            </div>
        </div>
    </template>

    <script>
    (function() {
        const epocasOptions = <?= json_encode(array_map(
            static fn (EpocaFilosofo $e) => ['value' => $e->value, 'label' => $e->rotulo()],
            EpocaFilosofo::cases()
        ), JSON_THROW_ON_ERROR) ?>;

        function buildFields(conceitoId, index) {
            const epocaSelect = epocasOptions.map(e =>
                `<option value="${e.value}">${e.label}</option>`
            ).join('');
            return `
                <div class="col-md-6">
                    <label class="form-label">Nome do Filósofo *</label>
                    <input type="text" class="form-control" required maxlength="200"
                        name="filosofos[${conceitoId}][${index}][nome]" placeholder="Ex: Platão, Aristóteles...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Época *</label>
                    <select class="form-select" required name="filosofos[${conceitoId}][${index}][epoca]">
                        <option value="" disabled selected>Selecione...</option>${epocaSelect}
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Linha de Pensamento *</label>
                    <input type="text" class="form-control" required maxlength="255"
                        name="filosofos[${conceitoId}][${index}][linha_pensamento]" placeholder="Ex: Idealismo...">
                </div>
                <div class="col-12">
                    <label class="form-label">Ideias Principais *</label>
                    <textarea class="form-control" rows="3" required
                        name="filosofos[${conceitoId}][${index}][ideias_principais]"
                        placeholder="Resuma as ideias centrais..."></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Citação / Trecho *</label>
                    <textarea class="form-control" rows="2" required
                        name="filosofos[${conceitoId}][${index}][citacao]"
                        placeholder="Trecho ou citação..."></textarea>
                </div>`;
        }

        function reindexLista(lista, conceitoId) {
            lista.querySelectorAll('.filosofo-item').forEach((item, index) => {
                item.querySelectorAll('[name^="filosofos"]').forEach(el => {
                    const field = el.name.match(/\[([^\]]+)\]$/);
                    if (field) {
                        el.name = `filosofos[${conceitoId}][${index}][${field[1]}]`;
                    }
                });
            });
        }

        document.querySelectorAll('.btn-add-filosofo').forEach(btn => {
            btn.addEventListener('click', () => {
                const conceitoId = btn.dataset.conceito;
                const lista = document.querySelector(`[data-conceito-lista="${conceitoId}"]`);
                const tpl = document.getElementById('tpl-filosofo-card');
                const clone = tpl.content.cloneNode(true);
                const item = clone.querySelector('.filosofo-item');
                const index = lista.querySelectorAll('.filosofo-item').length;
                item.querySelector('.filosofo-fields').innerHTML = buildFields(conceitoId, index);
                lista.appendChild(clone);
            });
        });

        document.getElementById('form-etapa4').addEventListener('click', e => {
            if (!e.target.closest('.btn-remove-filosofo')) return;
            const item = e.target.closest('.filosofo-item');
            const lista = item.closest('.filosofos-lista');
            const conceitoId = lista.dataset.conceitoLista;
            if (lista.querySelectorAll('.filosofo-item').length <= 1) return;
            item.remove();
            reindexLista(lista, conceitoId);
        });
    })();
    </script>

<?php endif; ?>
