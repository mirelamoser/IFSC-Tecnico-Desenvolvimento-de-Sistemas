<?php
$showSearch = $showSearch ?? true;
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$emailUsuario = $_SESSION['usuario_email'] ?? '';

if ($emailUsuario === '') {
    $defaults = [
        'ADMIN' => 'admin@philo.edu.br',
        'PROFESSOR' => 'professor@philo.edu.br',
        'ALUNO' => 'aluno@philo.edu.br',
    ];
    $emailUsuario = $defaults[$_SESSION['usuario_tipo'] ?? ''] ?? '';
}
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

    <div class="d-flex align-items-center gap-3">
        <div class="text-end d-none d-sm-block">
            <div class="fw-semibold small"><?= htmlspecialchars($nomeUsuario) ?></div>
            <div class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($emailUsuario) ?></div>
        </div>
        <a href="<?= htmlspecialchars(philoquest_web_root() . 'logout.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</header>
