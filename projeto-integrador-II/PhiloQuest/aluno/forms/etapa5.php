<h2 class="h5 fw-semibold text-primary mb-2">Etapa 5: Trabalho Final</h2>
<p class="text-muted small mb-4">Produza um trabalho completo integrando todas as etapas do seu ciclo de aprendizado.</p>

<?php if (!empty($dadosSubmissaoAtual['feedback'])): ?>
<div class="alert alert-danger">
    <strong><i class="fas fa-exclamation-triangle"></i> O professor solicitou revisão:</strong><br>
    <span class="small d-block mt-1"><?= nl2br(htmlspecialchars($dadosSubmissaoAtual['feedback'])) ?></span>
</div>
<?php endif; ?>

<form method="POST" action="ciclo.php">
    <?= philoquest_csrf_field() ?>
    <input type="hidden" name="acao" value="enviar_etapa">
    <input type="hidden" name="etapa_numero" value="5">

    <div class="mb-4">
        <label class="form-label" for="trabalho_final">Trabalho Final</label>
        <textarea name="trabalho_final" id="trabalho_final" class="form-control" rows="12"
            placeholder="Escreva aqui o seu trabalho final, integrando problema, questionamentos, conceitos e filósofos estudados..."><?= htmlspecialchars($dadosSubmissaoAtual['descricao_submissao'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="fas fa-paper-plane"></i>
        <?= !empty($dadosSubmissaoAtual) && ($statusSubmissao ?? '') === 'NECESSITA_REVISAO' ? 'Reenviar Trabalho Final' : 'Enviar Trabalho Final' ?>
    </button>
</form>
