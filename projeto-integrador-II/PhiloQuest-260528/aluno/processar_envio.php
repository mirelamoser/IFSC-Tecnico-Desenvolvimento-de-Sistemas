<?php
session_start();
require_once '../autoload.php';

use PhiloQuest\Services\AlunoService;

// 1. Segurança: Verifica se é um aluno logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'ALUNO') {
    header("Location: ../login.php");
    exit;
}

// 2. Verifica se a requisição veio do formulário (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $alunoId = $_SESSION['usuario_id'];

    try {
        // 3. Instancia o Service (sem precisar passar a $conexao, igual ao ProfessorService)
        $alunoService = new AlunoService();
        
        // 4. Aciona a regra de negócio que salva no banco
        $alunoService->submeterEtapa1($alunoId, $titulo, $descricao, $link);
        
        // 5. Redireciona para o painel com sucesso
        header("Location: dashboard.php?sucesso=envio_etapa");
        exit;

    } catch (Exception $e) {
        // Se der erro, volta para a tela de etapa mostrando a mensagem
        header("Location: ciclo.php?erro=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Se tentarem acessar direto pela URL
    header("Location: ciclo.php");
    exit;
}
?>