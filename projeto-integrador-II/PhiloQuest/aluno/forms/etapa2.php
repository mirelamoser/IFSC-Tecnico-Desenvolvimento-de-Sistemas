<?php

use PhiloQuest\Etapa2AvaliacaoHelper;

$perguntasSalvas = !empty($dadosSubmissaoAtual['descricao_submissao'])
    ? Etapa2AvaliacaoHelper::extrairPerguntas((string) $dadosSubmissaoAtual['descricao_submissao'])
    : [];

$feedbackProfessor = (string) ($dadosSubmissaoAtual['feedback'] ?? '');
$notasProfessorPorPergunta = Etapa2AvaliacaoHelper::parseNotasPorPergunta($feedbackProfessor);
$totalCampos = max(6, count($perguntasSalvas));
$readonlyGeral = $readonly ?? false;
?>

<h2 class="h5 fw-semibold text-primary mb-4">Etapa 2: Questionamentos</h2>

<form method="POST" action="ciclo.php" id="form-etapa2">
    <?= philoquest_csrf_field() ?>
    <input type="hidden" name="acao" value="enviar_etapa">
    <input type="hidden" name="etapa_numero" value="2">

    <div id="perguntas-lista">
        <?php
        for ($i = 0; $i < $totalCampos; $i++):
            $numLabel = $i + 1;
            $textoDaPergunta = $perguntasSalvas[$i] ?? '';
            $notaDestaPergunta = $notasProfessorPorPergunta[$numLabel] ?? '';
            $bloquearEsta = $readonlyGeral
                || Etapa2AvaliacaoHelper::notaDeveSerMantida($notaDestaPergunta);
        ?>
            <div class="mb-3 pergunta-item">
                <label class="form-label">Pergunta <?= $numLabel ?> <?= $numLabel <= 6 ? '*' : '' ?></label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text"
                           name="perguntas[]"
                           value="<?= htmlspecialchars($textoDaPergunta) ?>"
                           class="form-control <?= $bloquearEsta ? 'bg-light' : '' ?>"
                           <?= $numLabel <= 6 ? 'required' : '' ?>
                           <?= $bloquearEsta ? 'readonly tabindex="-1"' : '' ?>
                           placeholder="Digite sua pergunta...">
                    <?php if ($numLabel > 6 && !$bloquearEsta && !$readonlyGeral): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm"
                            onclick="this.closest('.pergunta-item').remove();">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <?php if (!$readonlyGeral): ?>
        <button type="button" id="add-pergunta" class="btn btn-outline-primary w-100 mb-3 <?= $totalCampos >= 10 ? 'd-none' : '' ?>">
            <i class="fas fa-plus"></i> Adicionar Pergunta Opcional
        </button>
        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-paper-plane"></i> Enviar Etapa 2
        </button>
    <?php endif; ?>
</form>

<script>
(function() {
    const btnAdd = document.getElementById('add-pergunta');
    const lista = document.getElementById('perguntas-lista');
    let contador = <?= $totalCampos ?>;

    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            if (contador < 10) {
                contador++;
                const div = document.createElement('div');
                div.className = 'mb-3 pergunta-item';
                div.innerHTML = `
                    <label class="form-label">Pergunta ${contador}</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" name="perguntas[]" class="form-control" placeholder="Digite sua pergunta...">
                        <button type="button" class="btn btn-outline-danger btn-sm"
                            onclick="this.closest('.pergunta-item').remove();">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>`;
                lista.appendChild(div);
                if (contador >= 10) btnAdd.classList.add('d-none');
            }
        });
    }
})();
</script>
