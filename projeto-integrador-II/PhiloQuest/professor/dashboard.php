<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use PhiloQuest\Services\ProfessorService;

$professorService = new ProfessorService();
$professorId = (int) $_SESSION['usuario_id'];

philoquest_processar_vinculo_turma_post($professorService, $professorId, 'dashboard.php');

$estatisticas = $professorService->obterEstatisticasProf($professorId);

philoquest_layout_start('Painel do Professor');
?>

<h1 class="fs-4 fw-semibold text-dark mb-4">Painel do Professor</h1>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4 col-xl">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Turmas</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= htmlspecialchars((string) $estatisticas['total_turmas']) ?></span>
                    <i class="fas fa-chalkboard-teacher fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Total de Alunos</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= htmlspecialchars((string) $estatisticas['total_alunos']) ?></span>
                    <i class="fas fa-user-graduate fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Validações Pendentes</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= htmlspecialchars((string) $estatisticas['validacoes_pendentes']) ?></span>
                    <i class="fas fa-clipboard-list fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-6 col-xl">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Trabalhos p/ Avaliar</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= htmlspecialchars((string) $estatisticas['trabalhos_para_avaliar']) ?></span>
                    <i class="fas fa-file-lines fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-6 col-xl">
        <div class="card border-0 rounded-4 bg-stat-lilac h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="text-dark small mb-2">Ciclos Completados</div>
                <div class="d-flex justify-content-between align-items-end">
                    <span class="display-6 fw-bold text-primary mb-0 lh-1"><?= htmlspecialchars((string) $estatisticas['ciclos_completados']) ?></span>
                    <i class="fas fa-award fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 rounded-4 bg-stat-lilac">
    <div class="card-body p-4">
        <h2 class="h5 fw-semibold text-dark mb-4">Ações Rápidas</h2>
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <a href="gerenciar_turmas.php" class="card border-0 rounded-4 bg-white text-decoration-none text-dark h-100">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <i class="fas fa-chalkboard-teacher fs-2 text-primary mb-3"></i>
                        <span class="fw-semibold">Gerenciar Turmas</span>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="validacao.php" class="card border-0 rounded-4 bg-white text-decoration-none text-dark h-100">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <i class="fas fa-clipboard-check fs-2 text-primary mb-3"></i>
                        <span class="fw-semibold">Validar Entregas</span>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="trabalhos.php" class="card border-0 rounded-4 bg-white text-decoration-none text-dark h-100">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <i class="fas fa-file-lines fs-2 text-primary mb-3"></i>
                        <span class="fw-semibold">Avaliar Trabalhos</span>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="missoes_extras.php" class="card border-0 rounded-4 bg-white text-decoration-none text-dark h-100">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <i class="fas fa-bolt fs-2 text-primary mb-3"></i>
                        <span class="fw-semibold">Criar Missão Extra</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php philoquest_layout_end(); ?>
