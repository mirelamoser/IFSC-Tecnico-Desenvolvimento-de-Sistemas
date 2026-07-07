<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// 1. Barreira de Segurança: Se não estiver logado, expulsa para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Pega o tipo de usuário que foi salvo na sessão na hora do login
$tipoUsuario = $_SESSION['usuario_tipo'];

// 3. O "Guarda de Trânsito" fazendo o redirecionamento
switch ($tipoUsuario) {
    case 'ALUNO':
        header("Location: aluno/dashboard.php");
        exit;
        
    case 'PROFESSOR':
        header("Location: professor/dashboard.php");
        exit;
        
    case 'ADMIN':
        header("Location: admin/dashboard.php");
        exit;
        
    default:
        // Se por algum motivo bizarro o tipo for desconhecido, destrói a sessão e manda pro login
        session_destroy();
        header("Location: login.php");
        exit;
}