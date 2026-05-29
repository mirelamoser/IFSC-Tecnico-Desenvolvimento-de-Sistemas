<?php
/**
 * EXEMPLO: Como usar o XPService para aprovar etapas com diferentes níveis de XP
 * 
 * Este arquivo é um guia de implementação. Para integrar no painel do professor,
 * copie a lógica para o seu arquivo de processamento.
 */

namespace PhiloQuest\Services;

use PhiloQuest\Enum\StatusSubmissao;

// ============================================
// EXEMPLO 1: Aprovar uma submissão
// ============================================
/*
// No seu arquivo PHP do professor (ex: professor/validar_etapa.php)

require_once '../autoload.php';

$xpService = new XPService();
$alunoId = $_POST['aluno_id'];
$etapaId = $_POST['etapa_id'];
$nivelAprovacao = $_POST['nivel_aprovacao']; // 'aprovado', 'bem_feito', 'excelente'

// Mapear para o enum correto
$statusMap = [
    'aprovado' => StatusSubmissao::APROVADO,
    'bem_feito' => StatusSubmissao::APROVADO_BEM_FEITO,
    'excelente' => StatusSubmissao::APROVADO_EXCELENTE,
];

$status = $statusMap[$nivelAprovacao];
$resultado = $xpService->adicionarXP($alunoId, $status, $etapaId);

if ($resultado['sucesso']) {
    // ✓ 150 XP adicionados ao aluno! Total: 450 XP
    echo $resultado['mensagem'];
} else {
    echo "Erro: " . $resultado['mensagem'];
}
*/

// ============================================
// EXEMPLO 2: Exibir formulário de aprovação
// ============================================
?>

<!-- FORMULÁRIO: Aprovar Etapa com 3 Níveis -->
<form method="POST" action="processar_aprovacao.php">
    <input type="hidden" name="aluno_id" value="<?php echo $alunoId; ?>">
    <input type="hidden" name="etapa_id" value="<?php echo $etapaId; ?>">
    
    <h3>Aprovar Etapa - Selecione o nível:</h3>
    
    <!-- Opção 1: Aprovado (150 XP) -->
    <label style="display: flex; gap: 10px; padding: 10px; border: 2px solid #27AE60; border-radius: 8px; cursor: pointer; margin-bottom: 10px;">
        <input type="radio" name="nivel_aprovacao" value="aprovado" required>
        <div>
            <strong>✓ Aprovado</strong>
            <span style="color: #27AE60; font-weight: bold;">150 XP</span>
            <p style="margin: 5px 0; font-size: 0.9em; color: #666;">Aluno completou o requisito básico</p>
        </div>
    </label>
    
    <!-- Opção 2: Bem Feito (300 XP) -->
    <label style="display: flex; gap: 10px; padding: 10px; border: 2px solid #3498DB; border-radius: 8px; cursor: pointer; margin-bottom: 10px;">
        <input type="radio" name="nivel_aprovacao" value="bem_feito">
        <div>
            <strong>★ Bem Feito</strong>
            <span style="color: #3498DB; font-weight: bold;">300 XP</span>
            <p style="margin: 5px 0; font-size: 0.9em; color: #666;">Trabalho de qualidade superior</p>
        </div>
    </label>
    
    <!-- Opção 3: Excelente (500 XP) -->
    <label style="display: flex; gap: 10px; padding: 10px; border: 2px solid #F39C12; border-radius: 8px; cursor: pointer; margin-bottom: 10px;">
        <input type="radio" name="nivel_aprovacao" value="excelente">
        <div>
            <strong>★★★ Excelente</strong>
            <span style="color: #F39C12; font-weight: bold;">500 XP</span>
            <p style="margin: 5px 0; font-size: 0.9em; color: #666;">Trabalho excepcional, criativo e bem executado</p>
        </div>
    </label>
    
    <!-- Feedback opcional -->
    <textarea name="feedback" placeholder="Feedback opcional para o aluno..." style="width: 100%; height: 100px; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px;"></textarea>
    
    <button type="submit" style="padding: 10px 20px; background-color: #9E39F7; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
        Aprovar e Ganhar XP
    </button>
</form>

<?php
// ============================================
// EXEMPLO 3: Exibir XP e Nível do Aluno
// ============================================
/*
$xpTotal = $xpService->obterXPTotalAluno($alunoId);
$nivel = $xpService->calcularNivel($xpTotal);
$xpParaProximo = $xpService->calcularXPParaProximoNivel($xpTotal);
$progresso = $xpService->calcularProgressoNivel($xpTotal);

echo "
    <div style='padding: 20px; background-color: #EBE4FA; border-radius: 10px;'>
        <h3>XP e Nível: $alunoNome</h3>
        <p><strong>Nível:</strong> $nivel</p>
        <p><strong>XP Total:</strong> $xpTotal</p>
        <p><strong>XP para próximo nível:</strong> $xpParaProximo</p>
        <div style='background-color: #ddd; height: 20px; border-radius: 10px; overflow: hidden;'>
            <div style='background-color: #9E39F7; height: 100%; width: $progresso%;'></div>
        </div>
        <p style='font-size: 0.9em; color: #666;'>$progresso% até o próximo nível</p>
    </div>
";
*/
?>

<!-- ============================================ -->
<!-- EXEMPLO 4: Ranking de XP da Turma -->
<!-- ============================================ -->
<?php
/*
$xpService = new XPService();
$ranking = $xpService->obterRankingXP($turmaId, 10);

echo "<h3>Ranking de XP</h3>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<thead style='background-color: #9E39F7; color: white;'>";
echo "<tr><th>Posição</th><th>Aluno</th><th>XP Total</th><th>Nível</th></tr>";
echo "</thead>";
echo "<tbody>";

foreach ($ranking as $posicao => $aluno) {
    $xp = $aluno['xp_total'];
    $nivel = $xpService->calcularNivel($xp);
    $posStr = ($posicao + 1) === 1 ? '🥇' : (($posicao + 1) === 2 ? '🥈' : (($posicao + 1) === 3 ? '🥉' : ($posicao + 1)));
    
    echo "<tr>";
    echo "<td style='padding: 10px; text-align: center;'><strong>$posStr</strong></td>";
    echo "<td style='padding: 10px;'>" . htmlspecialchars($aluno['nome_completo']) . "</td>";
    echo "<td style='padding: 10px;'><strong>$xp XP</strong></td>";
    echo "<td style='padding: 10px;'>Nível $nivel</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
*/
?>
