<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

philoquest_layout_start('Ajuda', activePage: 'ajuda.php');
?>

<div class="professor-ajuda">
    <div class="mb-4">
        <h1 class="fs-4 fw-semibold text-dark mb-1">Ajuda</h1>
        <p class="text-muted small mb-0">
            Bem-vindo ao PhiloQuest! Este guia fornecerá informações sobre como usar o painel do professor.
        </p>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-chalkboard-teacher fa-fw me-2"></i>Turmas
            </h2>
            <ul class="small text-muted mb-3 ps-3">
                <li class="mb-2">Vincule e acompanhe o andamento das suas turmas.</li>
                <li class="mb-0">O sistema disponibilizará um Código de Turma para você clicar e assumir a responsabilidade.</li>
            </ul>
            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">
                <i class="fas fa-lightbulb text-primary mt-1"></i>
                <p class="small mb-0">
                    <strong>DICA:</strong> Em "Ver detalhes da turma", acesse a listagem completa de estudantes matriculados
                    em uma turma específica para acompanhamento do progresso e desempenho.
                </p>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-check-circle fa-fw me-2"></i>Validar
            </h2>
            <ul class="small text-muted mb-3 ps-3">
                <li class="mb-2">
                    Valide as entregas dos alunos para liberar o avanço no ciclo, revisando os conteúdos,
                    aprovando ou rejeitando e concedendo XP.
                </li>
                <li class="mb-0">
                    Os alunos avançam em um ciclo linear de 5 etapas. O avanço fica bloqueado até que você aprove a etapa atual.
                </li>
            </ul>
            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">
                <i class="fas fa-lightbulb text-primary mt-1"></i>
                <p class="small mb-0">
                    <strong>Dica:</strong> Em ações rápidas, clique em "Validar Entregas" para revisar Problemas,
                    Questionamentos, Respostas e Filósofos.
                </p>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-file-lines fa-fw me-2"></i>Trabalhos
            </h2>
            <ul class="small text-muted mb-3 ps-3">
                <li class="mb-2">Avalie os trabalhos finais dos alunos e atribua notas.</li>
                <li class="mb-0">
                    É obrigatório atribuir uma nota de 0 a 10 e escrever um feedback textual.
                    Após o envio, o ciclo é concluído e o XP final é computado.
                </li>
            </ul>
            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">
                <i class="fas fa-file-lines text-primary mt-1"></i>
                <p class="small mb-0">
                    <strong>Dica:</strong> Em ações rápidas, clique em "Avaliar Trabalhos" para ler as produções
                    com status "Aguardando Avaliação".
                </p>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-bolt fa-fw me-2"></i>Missões Extras
            </h2>
            <ul class="small text-muted mb-0 ps-3">
                <li class="mb-2">
                    Você pode criar trabalhos extras além do ciclo através da missão extra.
                    Escolha uma turma, crie um título e descreva as instruções para os alunos.
                    É opcional colocar um link de referência.
                </li>
                <li class="mb-0">Após a entrega dos alunos, avalie e conceda XP a eles.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-none mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-semibold text-primary mb-3">
                <i class="fas fa-question-circle fa-fw me-2"></i>Precisa de mais ajuda?
            </h2>
            <p class="text-muted small mb-3">Entre em contato com o suporte técnico:</p>
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
