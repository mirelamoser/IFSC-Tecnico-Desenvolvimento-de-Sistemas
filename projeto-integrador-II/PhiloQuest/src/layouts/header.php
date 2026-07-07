<?php
$showSearch = $showSearch ?? true;
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipoUsuarioHeader = $_SESSION['usuario_tipo'] ?? '';
$matriculaUsuario = $_SESSION['usuario_matricula'] ?? '';
$ajudaHrefHeader = match ($tipoUsuarioHeader) {
    'ALUNO', 'PROFESSOR', 'ADMIN' => 'ajuda.php',
    default => '#',
};
$ajudaAtivaHeader = basename($_SERVER['PHP_SELF'] ?? '') === 'ajuda.php';
?>
<header class="bg-white border-bottom shadow-sm d-flex align-items-center justify-content-between px-4 py-3 flex-shrink-0">
    <?php if ($showSearch): ?>
    <div class="position-relative flex-grow-1 me-4" style="max-width: 320px;">
        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted z-1"></i>
        <input type="search" class="form-control rounded-pill ps-5 bg-light border" placeholder="Buscar..." aria-label="Buscar">
    </div>
    <?php else: ?>
    <div class="flex-grow-1"></div>
    <?php endif; ?>

    <div class="d-flex align-items-center gap-2 gap-sm-3">
        <a href="<?= htmlspecialchars($ajudaHrefHeader) ?>"
           class="btn btn-outline-secondary btn-ajuda-header rounded-circle d-lg-none<?= $ajudaAtivaHeader ? ' active' : '' ?>"
           aria-label="Ajuda"
           title="Ajuda">
            <i class="fas fa-question-circle"></i>
        </a>
        <div class="text-end d-none d-sm-block">
            <div class="fw-semibold small"><?= htmlspecialchars($nomeUsuario) ?></div>
            <?php if ($tipoUsuarioHeader !== 'ADMIN' && $matriculaUsuario !== ''): ?>
            <div class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($matriculaUsuario) ?></div>
            <?php endif; ?>
        </div>
        <a href="<?= htmlspecialchars(philoquest_web_root() . 'logout.php') ?>" class="btn btn-outline-primary btn-logout-header rounded-pill px-3">
            <i class="fas fa-sign-out-alt"></i><span class="d-none d-sm-inline"> Sair</span>
        </a>
    </div>
</header>
