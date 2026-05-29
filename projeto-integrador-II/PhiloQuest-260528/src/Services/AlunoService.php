<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Enum\StatusSubmissao;
use PhiloQuest\Etapa2AvaliacaoHelper;
use PhiloQuest\Etapa4FilosofoHelper;
use PhiloQuest\Exception\Etapa3Exception;
use PhiloQuest\Exception\Etapa4Exception;
use PhiloQuest\Repositories\ConexaoBanco;
use PDO;
use Exception;
use PDOException;

class AlunoService
{
    private const ETAPA_QUESTIONAMENTOS = 2;
    private const ETAPA_RESPOSTA_CONCEITUAL = 3;
    private const ETAPA_FILOSOFOS = 4;
    private const MIN_CONCEITOS_ETAPA3 = 1;
    private const MAX_CONCEITOS_ETAPA3 = 10;
    private const MIN_TAMANHO_RESPOSTA_ETAPA3 = 20;

    private PDO $conexao;

    public function __construct() {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

   /**
     * Submete uma etapa dinamicamente (Serve para as etapas de 1 a 5)
     * Agora com suporte a REENVIO (Update) caso o professor solicite revisão.
     */
    public function submeterEtapa(int $alunoId, int $etapaId, string $titulo, string $descricao, string $link): bool {
        if ($etapaId === 5) {
            if (trim($descricao) === '') {
                throw new Exception('Escreva o seu Trabalho Final antes de enviar.');
            }
            $titulo = 'Trabalho Final';
        } elseif (empty($titulo) || empty($descricao)) {
            throw new Exception("O título e a descrição são obrigatórios.");
        }

        try {
            // 1. Descobre a turma do aluno
            $stmtTurma = $this->conexao->prepare("SELECT turma_id FROM usuarios WHERE id = :id");
            $stmtTurma->bindValue(':id', $alunoId, PDO::PARAM_INT);
            $stmtTurma->execute();
            $turmaId = $stmtTurma->fetchColumn();

            if (!$turmaId) {
                throw new Exception("Você precisa estar vinculado a uma turma para enviar etapas.");
            }

            // 2. VERIFICA SE JÁ EXISTE UMA SUBMISSÃO DESTE ALUNO NESTA ETAPA
            $stmtCheck = $this->conexao->prepare("SELECT id, status FROM submissoes_etapa WHERE aluno_id = :aluno_id AND etapa_id = :etapa_id AND turma_id = :turma_id");
            $stmtCheck->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmtCheck->bindValue(':etapa_id', $etapaId, PDO::PARAM_INT);
            $stmtCheck->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $submissaoExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($submissaoExistente) {
                $statusAtual = (string) $submissaoExistente['status'];

                if ($statusAtual === StatusSubmissao::NECESSITA_REVISAO->value) {
                    $sqlUpdate = "UPDATE submissoes_etapa 
                                  SET titulo_submissao = :titulo, 
                                      descricao_submissao = :descricao, 
                                      link_submissao = :link, 
                                      status = 'AGUARDANDO_VALIDACAO',
                                      nota = NULL,
                                      data_submissao = NOW()
                                  WHERE id = :id";
                    $stmtUpdate = $this->conexao->prepare($sqlUpdate);
                    $stmtUpdate->bindValue(':titulo', $titulo, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':descricao', $descricao, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':link', $link, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':id', $submissaoExistente['id'], PDO::PARAM_INT);

                    return $stmtUpdate->execute();
                }

                if ($statusAtual === StatusSubmissao::AGUARDANDO_VALIDACAO->value) {
                    throw new Exception('Você já enviou esta etapa. Aguarde a avaliação do professor.');
                }

                if ($this->statusEhAprovado($statusAtual)) {
                    throw new Exception('Esta etapa já foi aprovada. Avance para a próxima etapa do ciclo.');
                }

                throw new Exception('Não é possível enviar esta etapa no momento.');
            } else {
                // 4. SE NÃO EXISTE, FAZ O INSERT NORMAL (PRIMEIRO ENVIO)
                $sqlInsert = "INSERT INTO submissoes_etapa 
                              (aluno_id, etapa_id, turma_id, titulo_submissao, descricao_submissao, link_submissao, status, data_submissao) 
                              VALUES 
                              (:aluno_id, :etapa_id, :turma_id, :titulo, :descricao, :link, 'AGUARDANDO_VALIDACAO', NOW())";
                
                $stmtInsert = $this->conexao->prepare($sqlInsert);
                $stmtInsert->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':etapa_id', $etapaId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':titulo', $titulo, PDO::PARAM_STR);
                $stmtInsert->bindValue(':descricao', $descricao, PDO::PARAM_STR);
                $stmtInsert->bindValue(':link', $link, PDO::PARAM_STR);

                return $stmtInsert->execute();
            }

        } catch (PDOException $e) {
            throw new Exception("Erro interno ao salvar no banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Verifica o status de uma etapa específica enviada pelo aluno
     */
    public function verificarStatusSubmissao(int $alunoId, int $etapaId): ?string
    {
        try {
            $sql = "SELECT status FROM submissoes_etapa 
                    WHERE aluno_id = :aluno_id AND etapa_id = :etapa_id 
                    ORDER BY data_submissao DESC LIMIT 1";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':etapa_id', $etapaId, PDO::PARAM_INT);
            $stmt->execute();

            $status = $stmt->fetchColumn();
            return $status !== false ? (string) $status : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function etapaAguardandoAvaliacao(int $alunoId, int $etapaId): bool
    {
        return $this->verificarStatusSubmissao($alunoId, $etapaId) === StatusSubmissao::AGUARDANDO_VALIDACAO->value;
    }

    /** Primeiro envio ou reenvio após NECESSITA_REVISAO */
    public function podeEnviarEtapa(int $alunoId, int $etapaId): bool
    {
        $status = $this->verificarStatusSubmissao($alunoId, $etapaId);

        if ($status === null) {
            return true;
        }

        if ($status === StatusSubmissao::NECESSITA_REVISAO->value) {
            return true;
        }

        return false;
    }

    private function statusEhAprovado(string $status): bool
    {
        return in_array($status, [
            StatusSubmissao::APROVADO->value,
            StatusSubmissao::APROVADO_BEM_FEITO->value,
            StatusSubmissao::APROVADO_EXCELENTE->value,
        ], true);
    }

    public function obterHistorico(int $alunoId): array {
        try {
            $sql = "SELECT etapa_id, titulo_submissao AS titulo, descricao_submissao AS descricao, status 
                    FROM submissoes_etapa 
                    WHERE aluno_id = :aluno_id 
                    ORDER BY data_submissao DESC 
                    LIMIT 5";
                    
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obterProgresso(int $alunoId): int {
        try {
            $sql = "SELECT experiencia_total FROM usuarios WHERE id = :id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function calcularNivel(int $xp): string {
        if ($xp < 500) return 'Aprendiz Filosófico';
        if ($xp < 1500) return 'Pensador em Ascensão';
        if ($xp < 3000) return 'Mestre da Lógica';
        return 'Sábio Supremo';
    }

    public function obterRankingTurma(int $alunoId): array {
        try {
            $stmtTurma = $this->conexao->prepare("SELECT turma_id FROM usuarios WHERE id = :id");
            $stmtTurma->bindValue(':id', $alunoId, PDO::PARAM_INT);
            $stmtTurma->execute();
            $turmaId = $stmtTurma->fetchColumn();

            if (!$turmaId) return [];

            $sql = "SELECT nome_completo, experiencia_total 
                    FROM usuarios 
                    WHERE turma_id = :turma_id AND tipo_usuario = 'ALUNO' 
                    ORDER BY experiencia_total DESC 
                    LIMIT 5";
                    
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obterProgressoCiclo(int $alunoId, int $totalEtapas = 5): array {
        try {
            $sql = "SELECT COUNT(DISTINCT etapa_id) FROM submissoes_etapa 
                    WHERE aluno_id = :aluno_id 
                    AND status IN ('APROVADO', 'APROVADO_BEM_FEITO', 'APROVADO_EXCELENTE')";
                    
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            
            $concluidas = (int)$stmt->fetchColumn();
            
            return [
                'concluidas' => $concluidas,
                'total_etapas' => $totalEtapas
            ];
        } catch (PDOException $e) {
            return ['concluidas' => 0, 'total_etapas' => $totalEtapas];
        }
    }

public function obterDadosSubmissao(int $alunoId, int $etapaId): ?array {
        try {
            // CORREÇÃO AQUI: Adicionado a coluna 'feedback' no SELECT
            $sql = "SELECT titulo_submissao, descricao_submissao, link_submissao, feedback, nota, status 
                    FROM submissoes_etapa 
                    WHERE aluno_id = :aluno_id AND etapa_id = :etapa_id 
                    ORDER BY data_submissao DESC LIMIT 1";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':etapa_id', $etapaId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
}

public function obterHistoricoAvaliacoes(int $alunoId): array {
        try {
            $sql = "SELECT se.id, se.data_submissao, se.data_validacao, se.status, se.feedback, se.nota,
                           se.etapa_id AS numero_etapa, se.titulo_submissao,
                           CASE se.etapa_id
                               WHEN 1 THEN 'Identificação do Problema'
                               WHEN 2 THEN 'Questionamentos'
                               WHEN 3 THEN 'Resposta Conceitual'
                               WHEN 4 THEN 'Filósofos e Associação'
                               WHEN 5 THEN 'Trabalho Final'
                               ELSE COALESCE(e.titulo, 'Atividade do Ciclo')
                           END AS etapa_titulo,
                           COALESCE(u_prof.nome_completo, 'Professor') AS professor_nome
                    FROM submissoes_etapa se
                    LEFT JOIN etapas e ON se.etapa_id = e.id
                    LEFT JOIN usuarios u_prof ON se.validado_por = u_prof.id
                    WHERE se.aluno_id = :aluno_id AND se.status != 'AGUARDANDO_VALIDACAO'
                    ORDER BY se.data_validacao DESC";
            
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function etapa2EstaAprovada(int $alunoId): bool
    {
        $status = $this->verificarStatusSubmissao($alunoId, self::ETAPA_QUESTIONAMENTOS);

        return $status !== null && $this->statusEhAprovado($status);
    }

    public function etapa3EstaAprovada(int $alunoId): bool
    {
        $status = $this->verificarStatusSubmissao($alunoId, self::ETAPA_RESPOSTA_CONCEITUAL);

        return $status !== null && $this->statusEhAprovado($status);
    }

    public function etapa4EstaAprovada(int $alunoId): bool
    {
        $status = $this->verificarStatusSubmissao($alunoId, self::ETAPA_FILOSOFOS);

        return $status !== null && $this->statusEhAprovado($status);
    }

    /**
     * Perguntas válidas da Etapa 2 (submissão aprovada do aluno).
     *
     * @return list<array{numero: int, texto: string}>
     */
    public function listarPerguntasEtapa2(int $alunoId): array
    {
        if (!$this->etapa2EstaAprovada($alunoId)) {
            return [];
        }

        $dados = $this->obterDadosSubmissao($alunoId, self::ETAPA_QUESTIONAMENTOS);
        if ($dados === null) {
            return [];
        }

        $textos = Etapa2AvaliacaoHelper::extrairPerguntas((string) $dados['descricao_submissao']);
        $resultado = [];
        foreach ($textos as $indice => $texto) {
            $texto = trim($texto);
            if ($texto === '') {
                continue;
            }
            $resultado[] = [
                'numero' => $indice + 1,
                'texto' => $texto,
            ];
        }

        return $resultado;
    }

    /**
     * Dados da resposta estruturada da Etapa 3 (para reenvio após revisão).
     *
     * @return array{pergunta_numero: int, pergunta_texto: string, resposta_conceitual: string, conceitos: list<string>}|null
     */
    public function obterRespostaEtapa3(int $alunoId): ?array
    {
        try {
            $sql = "SELECT r.pergunta_numero, r.pergunta_texto, r.resposta_conceitual, r.id AS resposta_id
                    FROM respostas_etapa3 r
                    INNER JOIN submissoes_etapa se ON se.id = r.submissao_etapa_id
                    WHERE r.aluno_id = :aluno_id AND se.etapa_id = :etapa_id
                    ORDER BY r.data_atualizacao DESC, r.data_criacao DESC
                    LIMIT 1";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':etapa_id', self::ETAPA_RESPOSTA_CONCEITUAL, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                return null;
            }

            $stmtConceitos = $this->conexao->prepare(
                "SELECT termo FROM conceitos_chave WHERE resposta_etapa3_id = :id ORDER BY ordem ASC"
            );
            $stmtConceitos->bindValue(':id', (int) $row['resposta_id'], PDO::PARAM_INT);
            $stmtConceitos->execute();
            $conceitos = array_column($stmtConceitos->fetchAll(PDO::FETCH_ASSOC), 'termo');

            return [
                'pergunta_numero' => (int) $row['pergunta_numero'],
                'pergunta_texto' => (string) $row['pergunta_texto'],
                'resposta_conceitual' => (string) $row['resposta_conceitual'],
                'conceitos' => $conceitos !== [] ? $conceitos : [''],
            ];
        } catch (PDOException $e) {
            error_log('AlunoService::obterRespostaEtapa3: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Salva submissão da Etapa 3, resposta conceitual e conceitos-chave em transação.
     *
     * @param list<string> $conceitos
     */
    public function submeterEtapa3(
        int $alunoId,
        int $perguntaNumero,
        string $respostaConceitual,
        array $conceitos
    ): bool {
        if (!$this->etapa2EstaAprovada($alunoId)) {
            throw new Etapa3Exception('A Etapa 2 precisa estar aprovada antes de enviar a Etapa 3.');
        }

        if (!$this->podeEnviarEtapa($alunoId, self::ETAPA_RESPOSTA_CONCEITUAL)) {
            throw new Etapa3Exception(
                'Esta etapa já foi enviada. Aguarde a avaliação do professor ou a solicitação de revisão.'
            );
        }

        $perguntas = $this->listarPerguntasEtapa2($alunoId);
        $perguntaEscolhida = null;
        foreach ($perguntas as $p) {
            if ((int) $p['numero'] === $perguntaNumero) {
                $perguntaEscolhida = $p;
                break;
            }
        }

        if ($perguntaEscolhida === null) {
            throw new Etapa3Exception('Selecione uma pergunta válida da sua Etapa 2.');
        }

        $respostaConceitual = trim($respostaConceitual);
        if (mb_strlen($respostaConceitual) < self::MIN_TAMANHO_RESPOSTA_ETAPA3) {
            throw new Etapa3Exception(
                'A resposta conceitual deve ter pelo menos ' . self::MIN_TAMANHO_RESPOSTA_ETAPA3 . ' caracteres.'
            );
        }

        $conceitosLimpos = $this->normalizarConceitosChave($conceitos);

        $descricaoSubmissao = $this->montarDescricaoSubmissaoEtapa3(
            $perguntaEscolhida,
            $respostaConceitual,
            $conceitosLimpos
        );

        $transacaoAtiva = false;
        try {
            if ($this->conexao->inTransaction()) {
                throw new Etapa3Exception('Há uma transação pendente. Atualize a página e tente novamente.');
            }
            $this->conexao->beginTransaction();
            $transacaoAtiva = true;

            $submissaoId = $this->persistirSubmissaoEtapa3(
                $alunoId,
                $descricaoSubmissao
            );

            $this->persistirRespostaEConceitos(
                $alunoId,
                $submissaoId,
                $perguntaNumero,
                (string) $perguntaEscolhida['texto'],
                $respostaConceitual,
                $conceitosLimpos
            );

            if ($this->conexao->inTransaction()) {
                $this->conexao->commit();
            }
            $transacaoAtiva = false;

            return true;
        } catch (Etapa3Exception $e) {
            $this->reverterTransacaoEtapa3($transacaoAtiva);
            throw $e;
        } catch (PDOException $e) {
            $this->reverterTransacaoEtapa3($transacaoAtiva);

            // MyISAM ou commit implícito: dados podem ter sido gravados mesmo com falha no commit()
            if ($this->etapa3FoiPersistida($alunoId)) {
                error_log('AlunoService::submeterEtapa3: commit com aviso, dados confirmados — ' . $e->getMessage());
                return true;
            }

            error_log('AlunoService::submeterEtapa3: ' . $e->getMessage());
            throw new Etapa3Exception('Erro ao gravar a Etapa 3 no banco de dados. Tente novamente.');
        }
    }

    private function etapa3FoiPersistida(int $alunoId): bool
    {
        return $this->verificarStatusSubmissao($alunoId, self::ETAPA_RESPOSTA_CONCEITUAL)
            === StatusSubmissao::AGUARDANDO_VALIDACAO->value
            && $this->obterRespostaEtapa3($alunoId) !== null;
    }

    private function reverterTransacaoEtapa3(bool $transacaoAtiva): void
    {
        if (!$transacaoAtiva || !$this->conexao->inTransaction()) {
            return;
        }
        try {
            $this->conexao->rollBack();
        } catch (PDOException $e) {
            error_log('AlunoService::reverterTransacaoEtapa3: ' . $e->getMessage());
        }
    }

    /**
     * @param list<string> $conceitos
     * @return list<string>
     */
    private function normalizarConceitosChave(array $conceitos): array
    {
        $unicos = [];
        foreach ($conceitos as $termo) {
            $termo = trim((string) $termo);
            if ($termo === '') {
                continue;
            }
            $chave = mb_strtolower($termo);
            if (!isset($unicos[$chave])) {
                $unicos[$chave] = $termo;
            }
        }

        $lista = array_values($unicos);

        if (count($lista) < self::MIN_CONCEITOS_ETAPA3) {
            throw new Etapa3Exception('Informe pelo menos um conceito-chave.');
        }

        if (count($lista) > self::MAX_CONCEITOS_ETAPA3) {
            throw new Etapa3Exception('Máximo de ' . self::MAX_CONCEITOS_ETAPA3 . ' conceitos-chave por resposta.');
        }

        return $lista;
    }

    /**
     * @param array{numero: int, texto: string} $pergunta
     * @param list<string> $conceitos
     */
    private function montarDescricaoSubmissaoEtapa3(
        array $pergunta,
        string $respostaConceitual,
        array $conceitos
    ): string {
        $texto = "Resposta Conceitual — Etapa 3\n\n";
        $texto .= 'Pergunta escolhida (#' . $pergunta['numero'] . "):\n";
        $texto .= $pergunta['texto'] . "\n\n";
        $texto .= "Resposta:\n" . $respostaConceitual . "\n\n";
        $texto .= "Conceitos-Chave:\n";
        foreach ($conceitos as $c) {
            $texto .= '- ' . $c . "\n";
        }

        return $texto;
    }

    private function persistirSubmissaoEtapa3(int $alunoId, string $descricaoSubmissao): int
    {
        $turmaId = $this->obterTurmaIdAluno($alunoId);
        if ($turmaId === null) {
            throw new Etapa3Exception('Você precisa estar vinculado a uma turma para enviar etapas.');
        }

        $stmtCheck = $this->conexao->prepare(
            "SELECT id, status FROM submissoes_etapa
             WHERE aluno_id = :aluno_id AND etapa_id = :etapa_id AND turma_id = :turma_id"
        );
        $stmtCheck->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
        $stmtCheck->bindValue(':etapa_id', self::ETAPA_RESPOSTA_CONCEITUAL, PDO::PARAM_INT);
        $stmtCheck->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
        $stmtCheck->execute();
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $stmtCheck->closeCursor();

        $titulo = 'Etapa 3 - Resposta Conceitual';

        if ($existente) {
            $statusAtual = (string) $existente['status'];
            if ($statusAtual !== StatusSubmissao::NECESSITA_REVISAO->value) {
                throw new Etapa3Exception('Não é possível reenviar esta etapa no momento.');
            }

            $stmtUpdate = $this->conexao->prepare(
                "UPDATE submissoes_etapa
                 SET titulo_submissao = :titulo,
                     descricao_submissao = :descricao,
                     link_submissao = '',
                     status = 'AGUARDANDO_VALIDACAO',
                     data_submissao = NOW()
                 WHERE id = :id"
            );
            $stmtUpdate->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':descricao', $descricaoSubmissao, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':id', (int) $existente['id'], PDO::PARAM_INT);
            if (!$stmtUpdate->execute()) {
                throw new Etapa3Exception('Falha ao atualizar a submissão da Etapa 3.');
            }

            return (int) $existente['id'];
        }

        $stmtInsert = $this->conexao->prepare(
            "INSERT INTO submissoes_etapa
             (aluno_id, etapa_id, turma_id, titulo_submissao, descricao_submissao, link_submissao, status, data_submissao)
             VALUES
             (:aluno_id, :etapa_id, :turma_id, :titulo, :descricao, '', 'AGUARDANDO_VALIDACAO', NOW())"
        );
        $stmtInsert->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':etapa_id', self::ETAPA_RESPOSTA_CONCEITUAL, PDO::PARAM_INT);
        $stmtInsert->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':titulo', $titulo, PDO::PARAM_STR);
        $stmtInsert->bindValue(':descricao', $descricaoSubmissao, PDO::PARAM_STR);

        if (!$stmtInsert->execute()) {
            throw new Etapa3Exception('Falha ao registrar a submissão da Etapa 3.');
        }

        return (int) $this->conexao->lastInsertId();
    }

    /**
     * @param list<string> $conceitos
     */
    private function persistirRespostaEConceitos(
        int $alunoId,
        int $submissaoId,
        int $perguntaNumero,
        string $perguntaTexto,
        string $respostaConceitual,
        array $conceitos
    ): void {
        $turmaId = $this->obterTurmaIdAluno($alunoId);
        if ($turmaId === null) {
            throw new Etapa3Exception('Turma do aluno não encontrada.');
        }

        $stmtExiste = $this->conexao->prepare(
            "SELECT id FROM respostas_etapa3 WHERE submissao_etapa_id = :submissao_id LIMIT 1"
        );
        $stmtExiste->bindValue(':submissao_id', $submissaoId, PDO::PARAM_INT);
        $stmtExiste->execute();
        $respostaId = $stmtExiste->fetchColumn();
        $stmtExiste->closeCursor();

        if ($respostaId !== false) {
            $respostaId = (int) $respostaId;
            $stmtUp = $this->conexao->prepare(
                "UPDATE respostas_etapa3
                 SET pergunta_numero = :numero,
                     pergunta_texto = :pergunta_texto,
                     resposta_conceitual = :resposta
                 WHERE id = :id"
            );
            $stmtUp->bindValue(':numero', $perguntaNumero, PDO::PARAM_INT);
            $stmtUp->bindValue(':pergunta_texto', $perguntaTexto, PDO::PARAM_STR);
            $stmtUp->bindValue(':resposta', $respostaConceitual, PDO::PARAM_STR);
            $stmtUp->bindValue(':id', $respostaId, PDO::PARAM_INT);
            if (!$stmtUp->execute()) {
                throw new Etapa3Exception('Falha ao atualizar a resposta conceitual.');
            }

            $stmtDel = $this->conexao->prepare(
                "DELETE FROM conceitos_chave WHERE resposta_etapa3_id = :id"
            );
            $stmtDel->bindValue(':id', $respostaId, PDO::PARAM_INT);
            $stmtDel->execute();
        } else {
            $stmtIns = $this->conexao->prepare(
                "INSERT INTO respostas_etapa3
                 (aluno_id, turma_id, submissao_etapa_id, pergunta_numero, pergunta_texto, resposta_conceitual)
                 VALUES
                 (:aluno_id, :turma_id, :submissao_id, :numero, :pergunta_texto, :resposta)"
            );
            $stmtIns->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmtIns->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmtIns->bindValue(':submissao_id', $submissaoId, PDO::PARAM_INT);
            $stmtIns->bindValue(':numero', $perguntaNumero, PDO::PARAM_INT);
            $stmtIns->bindValue(':pergunta_texto', $perguntaTexto, PDO::PARAM_STR);
            $stmtIns->bindValue(':resposta', $respostaConceitual, PDO::PARAM_STR);
            if (!$stmtIns->execute()) {
                throw new Etapa3Exception('Falha ao salvar a resposta conceitual.');
            }
            $respostaId = (int) $this->conexao->lastInsertId();
        }

        $stmtConceito = $this->conexao->prepare(
            "INSERT INTO conceitos_chave (resposta_etapa3_id, termo, ordem) VALUES (:resposta_id, :termo, :ordem)"
        );
        foreach ($conceitos as $ordem => $termo) {
            if (!$stmtConceito->execute([
                ':resposta_id' => $respostaId,
                ':termo' => $termo,
                ':ordem' => $ordem + 1,
            ])) {
                throw new Etapa3Exception('Falha ao salvar os conceitos-chave.');
            }
        }
    }

    private function obterTurmaIdAluno(int $alunoId): ?int
    {
        $stmt = $this->conexao->prepare('SELECT turma_id FROM usuarios WHERE id = :id');
        $stmt->bindValue(':id', $alunoId, PDO::PARAM_INT);
        $stmt->execute();
        $turmaId = $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($turmaId === false || $turmaId === null) {
            return null;
        }

        return (int) $turmaId;
    }

    /**
     * Conceitos-chave da Etapa 3 aprovada (para associação na Etapa 4).
     *
     * @return list<array{id: int, termo: string, ordem: int}>
     */
    public function listarConceitosEtapa3Aprovados(int $alunoId): array
    {
        if (!$this->etapa3EstaAprovada($alunoId)) {
            return [];
        }

        try {
            $sql = "SELECT ck.id, ck.termo, ck.ordem
                    FROM conceitos_chave ck
                    INNER JOIN respostas_etapa3 r ON r.id = ck.resposta_etapa3_id
                    INNER JOIN submissoes_etapa se ON se.id = r.submissao_etapa_id
                    WHERE r.aluno_id = :aluno_id
                      AND se.etapa_id = :etapa_id
                      AND se.status IN ('APROVADO', 'APROVADO_BEM_FEITO', 'APROVADO_EXCELENTE')
                    ORDER BY ck.ordem ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':etapa_id', self::ETAPA_RESPOSTA_CONCEITUAL, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $resultado = [];
            foreach ($rows as $row) {
                $resultado[] = [
                    'id' => (int) $row['id'],
                    'termo' => (string) $row['termo'],
                    'ordem' => (int) $row['ordem'],
                ];
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log('AlunoService::listarConceitosEtapa3Aprovados: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Filósofos já cadastrados por conceito (reenvio após revisão).
     *
     * @return array<int, list<array<string, string>>>
     */
    public function obterFilosofosEtapa4PorConceito(int $alunoId): array
    {
        try {
            $sql = "SELECT cf.conceito_chave_id, f.nome, f.epoca, f.linha_pensamento,
                           f.ideias_principais, f.citacao
                    FROM filosofos_etapa4 f
                    INNER JOIN conceito_filosofo cf ON cf.filosofo_id = f.id
                    INNER JOIN submissoes_etapa se ON se.id = f.submissao_etapa_id
                    WHERE f.aluno_id = :aluno_id AND se.etapa_id = :etapa_id
                    ORDER BY cf.conceito_chave_id, f.id ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':etapa_id', self::ETAPA_FILOSOFOS, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $agrupado = [];
            foreach ($rows as $row) {
                $cid = (int) $row['conceito_chave_id'];
                $agrupado[$cid][] = [
                    'nome' => (string) $row['nome'],
                    'epoca' => (string) $row['epoca'],
                    'linha_pensamento' => (string) $row['linha_pensamento'],
                    'ideias_principais' => (string) $row['ideias_principais'],
                    'citacao' => (string) $row['citacao'],
                ];
            }

            return $agrupado;
        } catch (PDOException $e) {
            error_log('AlunoService::obterFilosofosEtapa4PorConceito: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array<int|string, mixed> $filosofosPorConceito POST: [conceito_id => [ [nome, epoca, ...], ... ]]
     */
    public function submeterEtapa4(int $alunoId, array $filosofosPorConceito): bool
    {
        if (!$this->etapa3EstaAprovada($alunoId)) {
            throw new Etapa4Exception('A Etapa 3 precisa estar aprovada antes de enviar a Etapa 4.');
        }

        if (!$this->podeEnviarEtapa($alunoId, self::ETAPA_FILOSOFOS)) {
            throw new Etapa4Exception(
                'Esta etapa já foi enviada. Aguarde a avaliação do professor ou a solicitação de revisão.'
            );
        }

        $conceitosValidos = $this->listarConceitosEtapa3Aprovados($alunoId);
        if ($conceitosValidos === []) {
            throw new Etapa4Exception('Nenhum conceito-chave da Etapa 3 encontrado.');
        }

        $idsConceitos = array_column($conceitosValidos, 'id');
        $mapaTermos = [];
        foreach ($conceitosValidos as $c) {
            $mapaTermos[(int) $c['id']] = (string) $c['termo'];
        }

        $dadosValidados = $this->validarFilosofosPorConceito($filosofosPorConceito, $idsConceitos);
        $descricaoSubmissao = $this->montarDescricaoSubmissaoEtapa4($dadosValidados, $mapaTermos);

        $transacaoAtiva = false;
        try {
            if ($this->conexao->inTransaction()) {
                throw new Etapa4Exception('Há uma transação pendente. Atualize a página e tente novamente.');
            }
            $this->conexao->beginTransaction();
            $transacaoAtiva = true;

            $submissaoId = $this->persistirSubmissaoEtapa4($alunoId, $descricaoSubmissao);
            $this->limparFilosofosEtapa4DaSubmissao($submissaoId);
            $this->persistirFilosofosEAssociacoes($alunoId, $submissaoId, $dadosValidados);

            if ($this->conexao->inTransaction()) {
                $this->conexao->commit();
            }
            $transacaoAtiva = false;

            return true;
        } catch (Etapa4Exception $e) {
            $this->reverterTransacaoEtapa4($transacaoAtiva);
            throw $e;
        } catch (PDOException $e) {
            $this->reverterTransacaoEtapa4($transacaoAtiva);
            if ($this->etapa4FoiPersistida($alunoId)) {
                error_log('AlunoService::submeterEtapa4: commit com aviso — ' . $e->getMessage());
                return true;
            }
            error_log('AlunoService::submeterEtapa4: ' . $e->getMessage());
            throw new Etapa4Exception('Erro ao gravar a Etapa 4 no banco de dados. Tente novamente.');
        }
    }

    /**
     * @param array<int|string, mixed> $filosofosPorConceito
     * @param list<int> $idsConceitosValidos
     * @return array<int, list<array{nome: string, epoca: string, linha_pensamento: string, ideias_principais: string, citacao: string}>>
     */
    private function validarFilosofosPorConceito(array $filosofosPorConceito, array $idsConceitosValidos): array
    {
        $resultado = [];

        foreach ($idsConceitosValidos as $conceitoId) {
            $lista = $filosofosPorConceito[$conceitoId] ?? $filosofosPorConceito[(string) $conceitoId] ?? [];
            if (!is_array($lista)) {
                throw new Etapa4Exception('Dados de filósofos inválidos para um dos conceitos.');
            }

            $filosofosLimpos = [];
            foreach ($lista as $filosofo) {
                if (!is_array($filosofo)) {
                    continue;
                }
                $nome = trim((string) ($filosofo['nome'] ?? ''));
                $epoca = trim((string) ($filosofo['epoca'] ?? ''));
                $linha = trim((string) ($filosofo['linha_pensamento'] ?? ''));
                $ideias = trim((string) ($filosofo['ideias_principais'] ?? ''));
                $citacao = trim((string) ($filosofo['citacao'] ?? ''));

                if ($nome === '' && $epoca === '' && $linha === '' && $ideias === '' && $citacao === '') {
                    continue;
                }

                if ($nome === '' || $linha === '' || $ideias === '' || $citacao === '') {
                    throw new Etapa4Exception('Preencha todos os campos de cada filósofo cadastrado.');
                }
                if (!Etapa4FilosofoHelper::epocaValida($epoca)) {
                    throw new Etapa4Exception('Selecione uma época válida para cada filósofo.');
                }

                $filosofosLimpos[] = [
                    'nome' => $nome,
                    'epoca' => $epoca,
                    'linha_pensamento' => $linha,
                    'ideias_principais' => $ideias,
                    'citacao' => $citacao,
                ];
            }

            if ($filosofosLimpos === []) {
                throw new Etapa4Exception('Cadastre pelo menos um filósofo para cada conceito-chave.');
            }

            $resultado[$conceitoId] = $filosofosLimpos;
        }

        return $resultado;
    }

    /**
     * @param array<int, list<array{nome: string, epoca: string, linha_pensamento: string, ideias_principais: string, citacao: string}>> $dados
     * @param array<int, string> $mapaTermos
     */
    private function montarDescricaoSubmissaoEtapa4(array $dados, array $mapaTermos): string
    {
        $texto = "Filósofos e Associação — Etapa 4\n\n";
        foreach ($dados as $conceitoId => $filosofos) {
            $termo = $mapaTermos[$conceitoId] ?? 'Conceito';
            $texto .= "Conceito: {$termo}\n";
            foreach ($filosofos as $idx => $f) {
                $n = $idx + 1;
                $texto .= "  Filósofo {$n}: {$f['nome']} ({$f['epoca']})\n";
                $texto .= "    Linha: {$f['linha_pensamento']}\n";
                $texto .= "    Ideias: {$f['ideias_principais']}\n";
                $texto .= "    Citação: {$f['citacao']}\n";
            }
            $texto .= "\n";
        }

        return $texto;
    }

    private function persistirSubmissaoEtapa4(int $alunoId, string $descricaoSubmissao): int
    {
        $turmaId = $this->obterTurmaIdAluno($alunoId);
        if ($turmaId === null) {
            throw new Etapa4Exception('Você precisa estar vinculado a uma turma para enviar etapas.');
        }

        $stmtCheck = $this->conexao->prepare(
            "SELECT id, status FROM submissoes_etapa
             WHERE aluno_id = :aluno_id AND etapa_id = :etapa_id AND turma_id = :turma_id"
        );
        $stmtCheck->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
        $stmtCheck->bindValue(':etapa_id', self::ETAPA_FILOSOFOS, PDO::PARAM_INT);
        $stmtCheck->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
        $stmtCheck->execute();
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $stmtCheck->closeCursor();

        $titulo = 'Etapa 4 - Filósofos e Associação';

        if ($existente) {
            if ((string) $existente['status'] !== StatusSubmissao::NECESSITA_REVISAO->value) {
                throw new Etapa4Exception('Não é possível reenviar esta etapa no momento.');
            }
            $stmtUpdate = $this->conexao->prepare(
                "UPDATE submissoes_etapa
                 SET titulo_submissao = :titulo, descricao_submissao = :descricao, link_submissao = '',
                     status = 'AGUARDANDO_VALIDACAO', data_submissao = NOW()
                 WHERE id = :id"
            );
            $stmtUpdate->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricaoSubmissao,
                ':id' => (int) $existente['id'],
            ]);

            return (int) $existente['id'];
        }

        $stmtInsert = $this->conexao->prepare(
            "INSERT INTO submissoes_etapa
             (aluno_id, etapa_id, turma_id, titulo_submissao, descricao_submissao, link_submissao, status, data_submissao)
             VALUES (:aluno_id, :etapa_id, :turma_id, :titulo, :descricao, '', 'AGUARDANDO_VALIDACAO', NOW())"
        );
        $stmtInsert->execute([
            ':aluno_id' => $alunoId,
            ':etapa_id' => self::ETAPA_FILOSOFOS,
            ':turma_id' => $turmaId,
            ':titulo' => $titulo,
            ':descricao' => $descricaoSubmissao,
        ]);

        return (int) $this->conexao->lastInsertId();
    }

    private function limparFilosofosEtapa4DaSubmissao(int $submissaoId): void
    {
        $stmtIds = $this->conexao->prepare(
            "SELECT id FROM filosofos_etapa4 WHERE submissao_etapa_id = :sid"
        );
        $stmtIds->execute([':sid' => $submissaoId]);
        $ids = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
        $stmtIds->closeCursor();

        if ($ids !== []) {
            $stmtDelLinks = $this->conexao->prepare(
                "DELETE FROM conceito_filosofo WHERE filosofo_id = :fid"
            );
            foreach ($ids as $fid) {
                $stmtDelLinks->execute([':fid' => (int) $fid]);
            }
        }

        $stmtDel = $this->conexao->prepare("DELETE FROM filosofos_etapa4 WHERE submissao_etapa_id = :sid");
        $stmtDel->execute([':sid' => $submissaoId]);
    }

    /**
     * @param array<int, list<array{nome: string, epoca: string, linha_pensamento: string, ideias_principais: string, citacao: string}>> $dados
     */
    private function persistirFilosofosEAssociacoes(int $alunoId, int $submissaoId, array $dados): void
    {
        $stmtFil = $this->conexao->prepare(
            "INSERT INTO filosofos_etapa4
             (aluno_id, submissao_etapa_id, nome, epoca, linha_pensamento, ideias_principais, citacao)
             VALUES (:aluno_id, :sid, :nome, :epoca, :linha, :ideias, :citacao)"
        );
        $stmtLink = $this->conexao->prepare(
            "INSERT INTO conceito_filosofo (conceito_chave_id, filosofo_id) VALUES (:cid, :fid)"
        );

        foreach ($dados as $conceitoId => $filosofos) {
            foreach ($filosofos as $f) {
                $stmtFil->execute([
                    ':aluno_id' => $alunoId,
                    ':sid' => $submissaoId,
                    ':nome' => $f['nome'],
                    ':epoca' => $f['epoca'],
                    ':linha' => $f['linha_pensamento'],
                    ':ideias' => $f['ideias_principais'],
                    ':citacao' => $f['citacao'],
                ]);
                $filosofoId = (int) $this->conexao->lastInsertId();
                $stmtLink->execute([
                    ':cid' => $conceitoId,
                    ':fid' => $filosofoId,
                ]);
            }
        }
    }

    private function etapa4FoiPersistida(int $alunoId): bool
    {
        if ($this->verificarStatusSubmissao($alunoId, self::ETAPA_FILOSOFOS) !== StatusSubmissao::AGUARDANDO_VALIDACAO->value) {
            return false;
        }
        $stmt = $this->conexao->prepare(
            "SELECT COUNT(*) FROM filosofos_etapa4 f
             INNER JOIN submissoes_etapa se ON se.id = f.submissao_etapa_id
             WHERE f.aluno_id = :aluno_id AND se.etapa_id = :etapa_id"
        );
        $stmt->execute([':aluno_id' => $alunoId, ':etapa_id' => self::ETAPA_FILOSOFOS]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function reverterTransacaoEtapa4(bool $transacaoAtiva): void
    {
        if (!$transacaoAtiva || !$this->conexao->inTransaction()) {
            return;
        }
        try {
            $this->conexao->rollBack();
        } catch (PDOException $e) {
            error_log('AlunoService::reverterTransacaoEtapa4: ' . $e->getMessage());
        }
    }
}