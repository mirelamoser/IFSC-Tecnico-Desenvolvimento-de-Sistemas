<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\AdminService;

$estatisticas = (new AdminService())->obterEstatisticasGerais();

philoquest_layout_start('Painel do Administrador');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Painel do Administrador</h1>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Total de Usuários</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= (int)($estatisticas['total_usuarios'] ?? 0) ?></span>
                    <i class="fas fa-users fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Professores</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= (int)($estatisticas['total_professores'] ?? 0) ?></span>
                    <i class="fas fa-chalkboard-teacher fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Alunos</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= (int)($estatisticas['total_alunos'] ?? 0) ?></span>
                    <i class="fas fa-user-graduate fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Adesão de Alunos</div>
                <div class="d-flex justify-content-between align-items-end">
                    <div>
                        <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= (int)($estatisticas['total_alunos'] ?? 0) ?></span>
                        <span class="text-muted fs-5 fw-semibold">/ <?= (int)($estatisticas['total_matriculas'] ?? 0) ?></span>
                        <div class="small fw-bold text-primary mt-1">
                            <i class="fas fa-chart-pie"></i> <?= (int)($estatisticas['taxa_adesao'] ?? 0) ?>% cadastrados
                        </div>
                    </div>
                    <i class="fas fa-chart-line fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold text-dark mb-4">Banco de Dados de Conteúdo</h2>
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                    <div>
                        <h3 class="h6 fw-semibold mb-1">Filósofos Cadastrados</h3>
                        <div class="fs-3 fw-bold text-primary">12</div>
                    </div>
                    <i class="fas fa-brain fs-2 text-primary opacity-75"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h3 class="h6 fw-semibold mb-1">Conceitos Cadastrados</h3>
                        <div class="fs-3 fw-bold text-primary">45</div>
                    </div>
                    <i class="fas fa-book-open fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-semibold text-dark mb-4">Ações Rápidas</h2>
                <a href="gerenciar_usuarios.php" class="btn btn-action-warn w-100 mb-2 d-flex align-items-center gap-2 justify-content-start">
                    <i class="fas fa-user-lock text-primary"></i> Controle de Acesso (Bloquear/Desbloquear)
                </a>
                <a href="gerenciar_matriculas.php" class="btn btn-outline-secondary philo-btn-action w-100 mb-2 d-flex align-items-center gap-2 justify-content-start">
                    <i class="fas fa-file-csv text-primary"></i> Gerenciar Matrículas dos Alunos
                </a>
                <a href="cadastrar_professor.php" class="btn btn-primary w-100 mb-2 d-flex align-items-center gap-2 justify-content-start">
                    <i class="fas fa-chalkboard-teacher"></i> Cadastrar Novo Professor
                </a>
                <a href="#" class="btn btn-outline-secondary philo-btn-action w-100 d-flex align-items-center gap-2 justify-content-start">
                    <i class="fas fa-sync-alt text-primary"></i> Atualizar Conteúdo Base
                </a>
            </div>
        </div>
    </div>
</div>

<?php philoquest_layout_end(); ?>
