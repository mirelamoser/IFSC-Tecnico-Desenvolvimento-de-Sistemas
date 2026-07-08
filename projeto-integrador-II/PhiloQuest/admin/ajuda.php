<?php



declare(strict_types=1);



require_once __DIR__ . '/init.php';



philoquest_layout_start('Ajuda', activePage: 'ajuda.php');

?>



<div class="admin-ajuda">

    <div class="mb-4">

        <h1 class="fs-4 fw-semibold text-dark mb-1">Ajuda</h1>

        <p class="text-muted small mb-0">

            Bem-vindo ao PhiloQuest! Este guia explica como usar o painel do administrador.

        </p>

    </div>



    <div class="card border-0 rounded-4 shadow-none mb-4">

        <div class="card-body p-4">

            <h2 class="h5 fw-semibold text-primary mb-3">

                <i class="fas fa-users fa-fw me-2"></i>Usuários

            </h2>

            <ul class="small text-muted mb-3 ps-3">

                <li class="mb-2">

                    Gerencie quem pode acessar a plataforma: alunos e professores já cadastrados,

                    além de matrículas ainda pendentes de registro.

                </li>

                <li class="mb-2">

                    Bloqueie ou libere o acesso de um utilizador quando necessário.

                </li>

                <li class="mb-0">

                    Use "Resetar senha" para definir uma senha provisória igual à matrícula;

                    o utilizador deverá trocá-la no próximo acesso.

                </li>

            </ul>

            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">

                <i class="fas fa-lightbulb text-primary mt-1"></i>

                <p class="small mb-0">

                    <strong>Dica:</strong> Em "Ações Rápidas" do painel inicial, clique em

                    "Controle de Acesso (Bloquear/Desbloquear)" para ir direto à gestão de utilizadores.

                </p>

            </div>

        </div>

    </div>



    <div class="card border-0 rounded-4 shadow-none mb-4">

        <div class="card-body p-4">

            <h2 class="h5 fw-semibold text-primary mb-3">

                <i class="fas fa-user-graduate fa-fw me-2"></i>Alunos

            </h2>

            <ul class="small text-muted mb-3 ps-3">

                <li class="mb-2">

                    Cadastre matrículas autorizadas para que os alunos possam se registrar na plataforma.

                </li>

                <li class="mb-2">

                    Adicione matrículas individualmente (com código da turma) ou importe uma planilha CSV em lote.

                </li>

                <li class="mb-0">

                    Acompanhe quais matrículas estão disponíveis e quais já foram utilizadas no cadastro.

                </li>

            </ul>

            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">

                <i class="fas fa-file-csv text-primary mt-1"></i>

                <p class="small mb-0">

                    <strong>Dica:</strong> No painel inicial, use "Gerenciar Matrículas dos Alunos" para importar

                    o CSV institucional com matrícula e turma de cada estudante.

                </p>

            </div>

        </div>

    </div>



    <div class="card border-0 rounded-4 shadow-none mb-4">

        <div class="card-body p-4">

            <h2 class="h5 fw-semibold text-primary mb-3">

                <i class="fas fa-chalkboard-teacher fa-fw me-2"></i>Professores

            </h2>

            <ul class="small text-muted mb-3 ps-3">

                <li class="mb-2">

                    Autorize a matrícula de um professor antes de ele poder criar a conta no sistema.

                </li>

                <li class="mb-2">

                    Após a autorização, o professor conclui o cadastro na página pública de registro.

                </li>

                <li class="mb-0">

                    Consulte e remova autorizações pendentes ou não utilizadas quando necessário.

                </li>

            </ul>

            <div class="d-flex align-items-start gap-2 bg-stat-lilac rounded-3 p-3 mb-0">

                <i class="fas fa-chalkboard-teacher text-primary mt-1"></i>

                <p class="small mb-0">

                    <strong>Dica:</strong> Em "Ações Rápidas", clique em "Cadastrar Novo Professor" para

                    liberar uma nova matrícula docente.

                </p>

            </div>

        </div>

    </div>



    <div class="card border-0 rounded-4 shadow-none mb-4">

        <div class="card-body p-4">

            <h2 class="h5 fw-semibold text-primary mb-3">

                <i class="fas fa-book fa-fw me-2"></i>Conteúdo

            </h2>

            <p class="text-muted small mb-3">

                O banco de conteúdo base reúne filósofos e conceitos utilizados nas atividades do ciclo.

            </p>

            <ul class="small mb-0 ps-3">

                <li class="mb-2">Consulte no painel inicial o total de filósofos e conceitos cadastrados.</li>

                <li class="mb-0">Use "Atualizar Conteúdo Base" para manter o material de referência da plataforma.</li>

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


