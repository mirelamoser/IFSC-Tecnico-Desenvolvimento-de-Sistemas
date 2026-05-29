<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

philoquest_layout_start('Ajuda', activePage: 'ajuda.php');
?>

<div class="aluno-ajuda">
    <div class="mb-4">
        <h1 class="fs-4 fw-semibold text-dark mb-1">Ajuda</h1>
        <p class="text-muted small mb-0">Encontre informações e dicas para ajudar no seu ciclo de aprendizado.</p>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-sync-alt fa-fw me-2"></i>Ciclo de Aprendizado
            </h2>
            <p class="text-muted small mb-3">
                O Ciclo de Aprendizado é composto por 5 etapas sequenciais que você deve completar:
            </p>
            <ol class="small mb-3 ps-3">
                <li class="mb-2">
                    <strong>Problema:</strong> Identifique e descreva um problema filosófico
                </li>
                <li class="mb-2">
                    <strong>Questionamentos:</strong> Crie perguntas instigantes sobre o problema
                </li>
                <li class="mb-2">
                    <strong>Respostas e Conceitos:</strong> Elabore respostas e defina conceitos filosóficos
                </li>
                <li class="mb-2">
                    <strong>Filósofos:</strong> Associe filósofos aos conceitos identificados
                </li>
                <li class="mb-0">
                    <strong>Trabalho Final:</strong> Produza um trabalho completo integrando todas as etapas
                </li>
            </ol>
            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">
                <i class="fas fa-lightbulb text-primary mt-1"></i>
                <p class="small mb-0">
                    <strong>Dica:</strong> Cada etapa precisa ser validada pelo professor antes de avançar para a próxima!
                </p>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-trophy fa-fw me-2"></i>Sistema de Gamificação
            </h2>
            <p class="text-muted small mb-3">
                Ganhe XP e suba de nível completando atividades:
            </p>
            <ul class="small mb-3 ps-3">
                <li class="mb-2">Complete etapas do ciclo de aprendizado</li>
                <li class="mb-0">Participe de missões extras que o seu professor criar</li>
            </ul>
            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">
                <i class="fas fa-trophy text-primary mt-1"></i>
                <p class="small mb-0">
                    O ranking é atualizado com base no XP acumulado!
                </p>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-desktop fa-fw me-2"></i>Mural da Turma
            </h2>
            <p class="text-muted small mb-3">
                Compartilhe e celebre suas conquistas com seus colegas de turma.
            </p>
            <ul class="small mb-0 ps-3">
                <li class="mb-2">Publique quando completar uma etapa</li>
                <li class="mb-0">Curta e comente nas conquistas dos colegas</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-question-circle fa-fw me-2"></i>Precisa de mais ajuda?
            </h2>
            <p class="text-muted small mb-3">
                Entre em contato com seu professor ou com o suporte técnico:
            </p>
            <p class="small mb-0">
                <a href="mailto:philoquest2026@gmail.com" class="text-primary text-decoration-none d-inline-flex align-items-center gap-2">
                    <i class="fas fa-envelope fa-fw"></i>
                    <span>Email: philoquest2026@gmail.com</span>
                </a>
            </p>
        </div>
    </div>
</div>

<?php philoquest_layout_end(); ?>
