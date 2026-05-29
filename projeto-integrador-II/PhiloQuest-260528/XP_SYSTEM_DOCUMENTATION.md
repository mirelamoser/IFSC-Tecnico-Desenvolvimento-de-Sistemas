# 🎮 Sistema de Gamificação - XP (Experiência)

## 📋 Visão Geral

O sistema de XP foi implementado para recompensar alunos com pontos de experiência quando o professor aprova suas submissões de etapas. O aluno recebe diferentes quantidades de XP baseado na qualidade da aprovação.

---

## 🎯 Níveis de Aprovação e Recompensas

| Status | XP Ganho | Cor UI | Descrição |
|--------|---------|--------|-----------|
| **APROVADO** | 150 XP | Verde | Completou o requisito básico |
| **APROVADO_BEM_FEITO** | 300 XP | Azul | Trabalho de qualidade superior |
| **APROVADO_EXCELENTE** | 500 XP | Ouro | Trabalho excepcional e criativo |
| **NECESSITA_REVISAO** | 0 XP | Vermelho | Não recebe XP, precisa refazer |
| **AGUARDANDO_VALIDACAO** | 0 XP | Cinza | Ainda não foi validado |

---

## 🗄️ Estrutura do Banco de Dados

### 1. Coluna adicionada em `usuarios`
```sql
ALTER TABLE usuarios ADD COLUMN experiencia_total INT DEFAULT 0;
```

### 2. Novas Tabelas

#### `etapas`
Armazena as etapas de aprendizagem do curso:
```sql
CREATE TABLE etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT UNIQUE NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    instrucoes TEXT,
    criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `submissoes_etapa`
Rastreia as submissões dos alunos:
```sql
CREATE TABLE submissoes_etapa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    etapa_id INT NOT NULL,
    turma_id INT NOT NULL,
    arquivo_submissao VARCHAR(255),
    status ENUM('AGUARDANDO_VALIDACAO', 'NECESSITA_REVISAO', 'APROVADO', 'APROVADO_BEM_FEITO', 'APROVADO_EXCELENTE'),
    feedback TEXT,
    data_submissao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_validacao TIMESTAMP NULL,
    validado_por INT NULL,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id),
    FOREIGN KEY (etapa_id) REFERENCES etapas(id),
    FOREIGN KEY (turma_id) REFERENCES turmas(id),
    FOREIGN KEY (validado_por) REFERENCES usuarios(id),
    UNIQUE KEY unique_submissao (aluno_id, etapa_id, turma_id)
);
```

#### `historico_xp`
Registra todos os ganhos de XP do aluno:
```sql
CREATE TABLE historico_xp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    xp_ganho INT NOT NULL,
    status_submissao VARCHAR(50),
    etapa_id INT NULL,
    data_ganho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id),
    FOREIGN KEY (etapa_id) REFERENCES etapas(id),
    INDEX idx_aluno_data (aluno_id, data_ganho)
);
```

---

## 🔧 Usando o XPService

### Importação
```php
use PhiloQuest\Services\XPService;
use PhiloQuest\Enum\StatusSubmissao;

$xpService = new XPService();
```

### 1. Adicionar XP ao Aluno

```php
$resultado = $xpService->adicionarXP(
    $alunoId,                              // ID do aluno
    StatusSubmissao::APROVADO_EXCELENTE,   // Status da aprovação
    $etapaId                               // ID da etapa (opcional)
);

// Resultado:
// [
//     'sucesso' => true/false,
//     'mensagem' => "✓ 500 XP adicionados ao aluno! Total: 1050 XP",
//     'xp_ganho' => 500,
//     'xp_total' => 1050
// ]
```

### 2. Obter XP Total do Aluno

```php
$xpTotal = $xpService->obterXPTotalAluno($alunoId);
echo "XP Total: $xpTotal"; // Output: XP Total: 1050
```

### 3. Calcular Nível (baseado em milestones de 1000 XP)

```php
$nivel = $xpService->calcularNivel($xpTotal);
echo "Nível: $nivel"; // Output: Nível 2 (1050 XP / 1000)
```

### 4. Calcular XP Faltante para Próximo Nível

```php
$xpFaltante = $xpService->calcularXPParaProximoNivel($xpTotal);
echo "XP faltante: $xpFaltante"; // Output: XP faltante: 950
```

### 5. Calcular Progresso da Barra de Nível (0-100%)

```php
$progresso = $xpService->calcularProgressoNivel($xpTotal);
echo "Progresso: $progresso%"; // Output: Progresso: 5%
```

### 6. Obter Histórico de XP

```php
$historico = $xpService->obterHistoricoXP($alunoId, 10);
// Retorna as 10 últimas submissões aprovadas com XP ganho
```

### 7. Obter Ranking de XP (Gamificação)

```php
// Ranking global
$ranking = $xpService->obterRankingXP(null, 10);

// Ranking da turma
$ranking = $xpService->obterRankingXP($turmaId, 10);

// Exemplo de resultado:
// [
//     [
//         'id' => 5,
//         'nome_completo' => 'João Silva',
//         'matricula' => '2026001',
//         'xp_total' => 1500,
//         'codigo_turma' => '103HS2026'
//     ],
//     ...
// ]
```

---

## 📱 Exemplo de Integração no Painel do Professor

### Formulário de Aprovação

```php
<form method="POST" action="processar_aprovacao.php">
    <input type="hidden" name="aluno_id" value="<?php echo $alunoId; ?>">
    <input type="hidden" name="etapa_id" value="<?php echo $etapaId; ?>">
    
    <h3>Aprovar Etapa - Selecione o nível:</h3>
    
    <label style="border: 2px solid #27AE60; padding: 10px; cursor: pointer;">
        <input type="radio" name="nivel_aprovacao" value="aprovado" required>
        <strong>✓ Aprovado (150 XP)</strong>
        <p>Aluno completou o requisito básico</p>
    </label>
    
    <label style="border: 2px solid #3498DB; padding: 10px; cursor: pointer;">
        <input type="radio" name="nivel_aprovacao" value="bem_feito">
        <strong>★ Bem Feito (300 XP)</strong>
        <p>Trabalho de qualidade superior</p>
    </label>
    
    <label style="border: 2px solid #F39C12; padding: 10px; cursor: pointer;">
        <input type="radio" name="nivel_aprovacao" value="excelente">
        <strong>★★★ Excelente (500 XP)</strong>
        <p>Trabalho excepcional e criativo</p>
    </label>
    
    <textarea name="feedback" placeholder="Feedback para o aluno..."></textarea>
    
    <button type="submit">Aprovar e Ganhar XP</button>
</form>
```

### Processamento da Aprovação

```php
<?php
require_once '../autoload.php';

use PhiloQuest\Services\XPService;
use PhiloQuest\Enum\StatusSubmissao;

$xpService = new XPService();
$alunoId = $_POST['aluno_id'];
$etapaId = $_POST['etapa_id'];
$nivelAprovacao = $_POST['nivel_aprovacao'];

// Mapear para enum
$statusMap = [
    'aprovado' => StatusSubmissao::APROVADO,
    'bem_feito' => StatusSubmissao::APROVADO_BEM_FEITO,
    'excelente' => StatusSubmissao::APROVADO_EXCELENTE,
];

$status = $statusMap[$nivelAprovacao];
$resultado = $xpService->adicionarXP($alunoId, $status, $etapaId);

if ($resultado['sucesso']) {
    $_SESSION['mensagem'] = $resultado['mensagem'];
    $_SESSION['tipo_mensagem'] = 'sucesso';
} else {
    $_SESSION['mensagem'] = $resultado['mensagem'];
    $_SESSION['tipo_mensagem'] = 'erro';
}

header("Location: validar_submissoes.php");
?>
```

---

## 📊 Exibir XP e Nível do Aluno (Dashboard)

```php
<?php
$xpService = new XPService();
$xpTotal = $xpService->obterXPTotalAluno($alunoId);
$nivel = $xpService->calcularNivel($xpTotal);
$xpParaProximo = $xpService->calcularXPParaProximoNivel($xpTotal);
$progresso = $xpService->calcularProgressoNivel($xpTotal);
?>

<div class="xp-card">
    <div class="xp-info">
        <div class="xp-level">Nível <?php echo $nivel; ?></div>
        <div class="xp-total"><?php echo $xpTotal; ?> XP</div>
        <div class="xp-next">Faltam <?php echo $xpParaProximo; ?> XP para o próximo nível</div>
    </div>
    
    <div class="xp-progress-bar">
        <div class="xp-progress-fill" style="width: <?php echo $progresso; ?>%"></div>
    </div>
    
    <p class="xp-percentage"><?php echo $progresso; ?>% até o próximo nível</p>
</div>
```

---

## 🏆 Exibir Ranking da Turma

```php
<?php
$xpService = new XPService();
$ranking = $xpService->obterRankingXP($turmaId, 10);

echo "<h3>Ranking de XP da Turma</h3>";
echo "<table>";
echo "<thead><tr><th>Posição</th><th>Aluno</th><th>XP</th><th>Nível</th></tr></thead>";
echo "<tbody>";

foreach ($ranking as $posicao => $aluno) {
    $xp = $aluno['xp_total'];
    $nivel = $xpService->calcularNivel($xp);
    $medalha = ['🥇', '🥈', '🥉'][$posicao] ?? ($posicao + 1);
    
    echo "<tr>";
    echo "<td>$medalha</td>";
    echo "<td>" . htmlspecialchars($aluno['nome_completo']) . "</td>";
    echo "<td><strong>$xp XP</strong></td>";
    echo "<td>Nível $nivel</td>";
    echo "</tr>";
}

echo "</tbody></table>";
?>
```

---

## 🔄 Enum StatusSubmissao - Métodos Úteis

```php
use PhiloQuest\Enum\StatusSubmissao;

// Obter XP de um status
$xp = StatusSubmissao::APROVADO_EXCELENTE->obterXP(); // 500

// Obter cor para UI
$cor = StatusSubmissao::APROVADO_BEM_FEITO->obterCor(); // #3498DB

// Iteração
foreach (StatusSubmissao::cases() as $status) {
    echo $status->value . ": " . $status->obterXP() . " XP";
}
```

---

## ✅ Checklist de Implementação

- [x] Enum `StatusSubmissao` atualizado com 3 níveis de aprovação
- [x] Coluna `experiencia_total` adicionada em `usuarios`
- [x] Tabelas `etapas`, `submissoes_etapa`, `historico_xp` criadas
- [x] Service `XPService` implementado
- [ ] Página do professor para validar/aprovar submissões (precisa ser criada)
- [ ] Dashboard do aluno para exibir XP/Nível (precisa ser criada)
- [ ] Página de Ranking de XP (precisa ser criada)
- [ ] Testes em banco de dados real

---

## 💡 Próximos Passos

1. **Executar o schema SQL** para criar as novas tabelas
2. **Criar página de validação** (`professor/validar_submissoes.php`)
3. **Criar dashboard do aluno** para exibir XP
4. **Criar página de ranking** de XP
5. **Integrar notificações** quando aluno ganhar XP
6. **Adicionar badges/achievements** baseados em milestones de XP

---

## 📝 Notas Importantes

- **Transações**: O `adicionarXP()` usa transações MySQL para garantir consistência
- **Validação**: Verifica se o usuário é realmente ALUNO antes de adicionar XP
- **Milestones**: Sistema de nível baseado em 1000 XP = 1 nível (pode ser ajustado)
- **Histórico**: Todas as transações de XP são registradas em `historico_xp` para auditoria
- **Performance**: Índices criados em `historico_xp` (aluno_id, data_ganho) para queries rápidas

---

## 🎨 Cores Recomendadas para UI

```
APROVADO: #27AE60 (Verde)
APROVADO_BEM_FEITO: #3498DB (Azul)
APROVADO_EXCELENTE: #F39C12 (Ouro)
NECESSITA_REVISAO: #E74C3C (Vermelho)
AGUARDANDO_VALIDACAO: #95A5A6 (Cinza)
```

---

Arquivos modificados/criados:
- ✅ `src/Enum/StatusSubmissao.php` (atualizado)
- ✅ `src/Services/XPService.php` (novo)
- ✅ `database/PhiloQuest_Schema.sql` (atualizado)
- ✅ `EXEMPLO_XP_IMPLEMENTATION.php` (novo - documentação)
