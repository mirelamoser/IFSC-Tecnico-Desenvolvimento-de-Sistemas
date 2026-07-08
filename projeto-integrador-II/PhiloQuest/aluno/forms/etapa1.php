<h2 class="h5 fw-semibold text-primary mb-2">Etapa 1: Identificação do Problema</h2>
<p class="text-muted small mb-4">Descreva o problema filosófico que você deseja investigar.</p>

<?php if (!empty($dadosSubmissaoAtual['feedback'])): ?>
<div class="alert alert-danger">
    <strong><i class="fas fa-exclamation-triangle"></i> O professor solicitou revisão:</strong><br>
    <span class="small d-block mt-1"><?= nl2br(htmlspecialchars($dadosSubmissaoAtual['feedback'])) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="ciclo.php">
    <?= philoquest_csrf_field() ?>
    <input type="hidden" name="acao" value="enviar_etapa">
    <input type="hidden" name="etapa_numero" value="1">

    <div class="mb-3">
        <label class="form-label">Título da Reflexão *</label>
        <input type="text" name="titulo" class="form-control"
            value="<?= htmlspecialchars($dadosSubmissaoAtual['titulo_submissao'] ?? '') ?>"
            required placeholder="Ex: O dilema da ética digital">
    </div>

    <div class="mb-3">
        <label class="form-label">Descrição Detalhada *</label>
        <textarea name="descricao" class="form-control" rows="5" required
            placeholder="Explique o problema..."><?= htmlspecialchars($dadosSubmissaoAtual['descricao_submissao'] ?? '') ?></textarea>
    </div>

    <div class="mb-4">
        <label class="form-label">Link de Referência (Opcional)</label>
        <input type="url" name="link" class="form-control"
            value="<?= htmlspecialchars($dadosSubmissaoAtual['link_submissao'] ?? '') ?>"
            placeholder="https://...">
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="fas fa-paper-plane"></i>
        <?= !empty($dadosSubmissaoAtual) && ($statusSubmissao ?? '') === 'NECESSITA_REVISAO' ? 'Reenviar Etapa 1' : 'Enviar Etapa 1' ?>
    </button>
</form>
