<?php
/** @var string $pageTitle */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - PhiloQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= htmlspecialchars(philoquest_asset('css/philoquest-theme.css')) ?>">
</head>
<body class="bg-app min-vh-100 d-flex flex-column align-items-center justify-content-center p-4">
    <div class="text-center mb-4">
        <a href="<?= htmlspecialchars(philoquest_web_root() . 'login.php') ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark fw-bold fs-4">
            <i class="fas fa-brain text-primary fs-2"></i>
            <span>PhiloQuest</span>
        </a>
    </div>
    <div class="card border-0 shadow-sm rounded-4 w-100" style="max-width: 420px;">
