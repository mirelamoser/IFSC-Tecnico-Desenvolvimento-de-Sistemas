<?php
use PhiloQuest\Services\AlunoService;

$tipoUsuario = $_SESSION['usuario_tipo'] ?? '';
$menuItems = philoquest_get_menu_items($tipoUsuario);
$paginaAtual = $paginaAtual ?? basename($_SERVER['PHP_SELF']);
$subEtapasCiclo = [
    1 => 'Etapa 1',
    2 => 'Etapa 2',
    3 => 'Etapa 3',
    4 => 'Etapa 4',
    5 => 'Etapa 5',
];
$exibirSubEtapasCiclo = $tipoUsuario === 'ALUNO' && $paginaAtual === 'ciclo.php';
$progressoSidebar = null;
$limiteEtapaDesbloqueada = 0;
$etapaCorrenteSidebar = 1;
$concluidasSidebar = 0;
$totalEtapasSidebar = 5;

if ($exibirSubEtapasCiclo && isset($_SESSION['usuario_id'])) {
    $alunoServiceSidebar = new AlunoService();
    $progressoSidebar = $alunoServiceSidebar->obterProgressoCiclo((int) $_SESSION['usuario_id']);
    $totalEtapasSidebar = (int) ($progressoSidebar['total_etapas'] ?? 5);
    $concluidasSidebar = (int) ($progressoSidebar['concluidas'] ?? 0);
    $limiteEtapaDesbloqueada = min($concluidasSidebar + 1, $totalEtapasSidebar);
    $etapaCorrenteSidebar = $concluidasSidebar < $totalEtapasSidebar
        ? $concluidasSidebar + 1
        : $totalEtapasSidebar;
}
?>
<aside class="sidebar-col position-fixed top-0 start-0 vh-100 bg-white border-end d-flex flex-column flex-shrink-0 p-3 p-lg-4 overflow-auto"
       style="width: var(--philo-sidebar-width); z-index: 1030;">
    <a href="<?= htmlspecialchars(philoquest_web_root() . 'painel.php') ?>"
       class="d-flex align-items-center gap-2 text-decoration-none text-dark fw-bold fs-5 mb-4 px-2">
        <i class="fas fa-brain text-primary fs-4"></i>
        <span>PhiloQuest</span>
    </a>

    <nav class="nav nav-sidebar flex-column flex-grow-1 gap-1 px-1">
        <?php foreach ($menuItems as $item):
            $active = philoquest_is_menu_active($item['href'], $paginaAtual);
            $variant = $item['variant'] ?? '';
            $classes = 'nav-link d-flex align-items-center gap-3 rounded-3 px-3 py-2';
            if ($active) {
                $classes .= ' active';
            }
            if ($variant === 'voltar') {
                $classes .= ' nav-voltar';
            }
        ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $classes ?>"<?= $item['href'] === '#' ? ' aria-disabled="true"' : '' ?>>
            <i class="fas <?= htmlspecialchars($item['icon']) ?> fa-fw" aria-hidden="true"></i>
            <span class="nav-label-full"><?= htmlspecialchars($item['label']) ?></span>
            <span class="nav-label-short"><?= htmlspecialchars($item['short'] ?? $item['label']) ?></span>
        </a>
        <?php if ($exibirSubEtapasCiclo && basename($item['href']) === 'ciclo.php'): ?>
        <div class="nav-sub-etapas d-flex flex-column gap-1 mb-2">
            <?php foreach ($subEtapasCiclo as $numeroEtapa => $rotuloEtapa):
                $desbloqueada = $numeroEtapa <= $limiteEtapaDesbloqueada;
                $subActive = $desbloqueada
                    && $concluidasSidebar < $totalEtapasSidebar
                    && $numeroEtapa === $etapaCorrenteSidebar;
                if ($desbloqueada):
                    $subClasses = 'nav-link rounded-3 py-1 px-3 small text-decoration-none';
                    if ($subActive) {
                        $subClasses .= ' active';
                    }
            ?>
            <a href="ciclo.php" class="<?= $subClasses ?>"><?= htmlspecialchars($rotuloEtapa) ?></a>
            <?php else: ?>
            <span class="nav-link nav-link-bloqueado rounded-3 py-1 px-3 small">
                <i class="fas fa-lock fa-fw me-1" style="font-size: 0.7rem;"></i><?= htmlspecialchars($rotuloEtapa) ?>
            </span>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <?php
    $ajudaHref = match ($tipoUsuario) {
        'ALUNO' => 'ajuda.php',
        'PROFESSOR' => 'ajuda.php',
        'ADMIN' => 'ajuda.php',
        default => '#',
    };
    $ajudaActive = $paginaAtual === 'ajuda.php';
    $ajudaClasses = 'nav-link d-flex align-items-center gap-3 rounded-3 px-3 py-2 text-decoration-none mt-auto';
    $ajudaClasses .= $ajudaActive ? ' active' : ' text-dark';
    ?>
    <a href="<?= htmlspecialchars($ajudaHref) ?>" class="<?= $ajudaClasses ?> nav-ajuda-sidebar">
        <i class="fas fa-question-circle fa-fw text-primary" aria-hidden="true"></i>
        <span class="nav-label-full">Ajuda</span>
        <span class="nav-label-short">Ajuda</span>
    </a>
</aside>
