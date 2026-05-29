<?php

declare(strict_types=1);

/** URL base do projeto (ex: /PhiloQuest-260515/) */
function philoquest_web_root(): string
{
    if (defined('PHILOQUEST_WEB_ROOT')) {
        return PHILOQUEST_WEB_ROOT;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts = array_values(array_filter(explode('/', $script)));
    $roleDirs = ['admin', 'professor', 'aluno'];

    if (count($parts) >= 2 && in_array($parts[count($parts) - 2], $roleDirs, true)) {
        array_pop($parts);
        array_pop($parts);
    } else {
        array_pop($parts);
    }

    $root = '/' . implode('/', $parts);
    return ($root === '/') ? '/' : $root . '/';
}

function philoquest_asset(string $path): string
{
    return philoquest_web_root() . ltrim($path, '/');
}

function philoquest_require_role(string ...$roles): void
{
    $loginUrl = philoquest_web_root() . 'login.php';

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . $loginUrl);
        exit;
    }

    if ($roles !== [] && !in_array($_SESSION['usuario_tipo'] ?? '', $roles, true)) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

function philoquest_csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function philoquest_csrf_field(): string
{
    $token = philoquest_csrf_token();
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
}

function philoquest_csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    $expected = $_SESSION['_csrf_token'] ?? '';
    return $sent !== '' && hash_equals($expected, $sent);
}

function philoquest_csrf_fail_redirect(string $url): never
{
    philoquest_flash_set('erro', 'Sessão expirada ou formulário inválido. Tente novamente.');
    header('Location: ' . $url);
    exit;
}

function philoquest_flash_set(string $tipo, string $mensagem): void
{
    $_SESSION['_flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** @return array{tipo: string, mensagem: string}|null */
function philoquest_flash_get(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function philoquest_render_flash(): void
{
    $flash = philoquest_flash_get();
    if ($flash === null) {
        return;
    }
    $class = match ($flash['tipo']) {
        'sucesso' => 'alert alert-success',
        'erro' => 'alert alert-danger',
        default => 'alert alert-info',
    };
    echo '<div class="' . $class . ' mb-4" role="alert">' . htmlspecialchars($flash['mensagem']) . '</div>';
}

function philoquest_get_menu_items(string $tipoUsuario): array
{
    $menus = [
        'ADMIN' => [
            ['href' => 'gerenciar_usuarios.php', 'icon' => 'fa-users', 'label' => 'Usuários'],
            ['href' => 'gerenciar_matriculas.php', 'icon' => 'fa-user-graduate', 'label' => 'Alunos'],
            ['href' => 'cadastrar_professor.php', 'icon' => 'fa-chalkboard-teacher', 'label' => 'Professores'],
            ['href' => '#', 'icon' => 'fa-book', 'label' => 'Conteúdo'],
            ['href' => 'dashboard.php', 'icon' => 'fa-home', 'label' => 'Painel inicial'],
        ],
        'PROFESSOR' => [
            ['href' => 'gerenciar_turmas.php', 'icon' => 'fa-chalkboard-teacher', 'label' => 'Turmas'],
            ['href' => 'validacao.php', 'icon' => 'fa-check-circle', 'label' => 'Validações'],
            ['href' => 'trabalhos.php', 'icon' => 'fa-file-lines', 'label' => 'Trabalhos'],
            ['href' => 'missoes_extras.php', 'icon' => 'fa-bolt', 'label' => 'Missão Extra'],
            ['href' => 'dashboard.php', 'icon' => 'fa-home', 'label' => 'Painel inicial'],
        ],
        'ALUNO' => [
            ['href' => 'ciclo.php', 'icon' => 'fa-sync-alt', 'label' => 'Ciclo de Aprendizagem'],
            ['href' => 'avaliacoes.php', 'icon' => 'fa-check-circle', 'label' => 'Avaliações'],
            ['href' => 'missao_extra.php', 'icon' => 'fa-bolt', 'label' => 'Missão Extra'],
            ['href' => 'mural.php', 'icon' => 'fa-desktop', 'label' => 'Mural'],
            ['href' => 'dashboard.php', 'icon' => 'fa-home', 'label' => 'Painel inicial'],
        ],
    ];

    return $menus[$tipoUsuario] ?? [];
}

function philoquest_nome_etapa_ciclo(int $numeroEtapa): string
{
    return match ($numeroEtapa) {
        1 => 'Identificação do Problema',
        2 => 'Questionamentos',
        3 => 'Resposta Conceitual',
        4 => 'Filósofos e Associação',
        5 => 'Trabalho Final',
        default => 'Etapa ' . $numeroEtapa,
    };
}

function philoquest_is_menu_active(string $href, string $paginaAtual): bool
{
    if ($href === '#' || $href === '') {
        return false;
    }
    return basename($href) === $paginaAtual;
}

function philoquest_layout_start(
    string $pageTitle,
    ?string $basePath = null,
    ?bool $showSearch = null,
    array $extraCss = [],
    ?string $activePage = null
): void {
    $basePath = $basePath ?? (defined('PHILOQUEST_BASE') ? PHILOQUEST_BASE : philoquest_web_root());
    $paginaAtual = $activePage ?? basename($_SERVER['PHP_SELF']);
    $tipoUsuario = $_SESSION['usuario_tipo'] ?? '';
    if ($showSearch === null) {
        $showSearch = false;
    }
    $lang = ($tipoUsuario === 'ALUNO') ? 'pt-PT' : 'pt-br';
    $areaBodyClass = match ($tipoUsuario) {
        'PROFESSOR' => 'area-professor',
        'ALUNO' => 'area-aluno',
        'ADMIN' => 'area-admin',
        default => '',
    };

    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - PhiloQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= htmlspecialchars(philoquest_asset('css/philoquest-theme.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(philoquest_asset('css/philoquest-pages.css')) ?>">
    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(philoquest_asset($css)) ?>">
    <?php endforeach; ?>
</head>
<body class="bg-app overflow-hidden vh-100<?= $areaBodyClass !== '' ? ' ' . $areaBodyClass : '' ?>">
    <div class="layout-shell d-flex vh-100 overflow-hidden">
        <?php require __DIR__ . '/layouts/sidebar.php'; ?>
        <div class="main-col flex-grow-1 d-flex flex-column vh-100 overflow-hidden" style="margin-left: var(--philo-sidebar-width);">
            <?php require __DIR__ . '/layouts/header.php'; ?>
            <main class="flex-grow-1 overflow-auto p-4 p-lg-4 bg-app">
    <?php
    philoquest_render_flash();
}

function philoquest_layout_end(): void
{
    ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
    <?php
}
