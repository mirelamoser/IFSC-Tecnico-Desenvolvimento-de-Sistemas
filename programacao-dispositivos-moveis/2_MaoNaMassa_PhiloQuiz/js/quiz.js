// js/quiz.js

let perguntasAtuais = [];
let indicePerguntaAtual = 0;
let pontuacaoAtual = 0;
let acertosAtuais = 0;
let dificuldadeAtual = '';
let pontosPorPergunta = 10;

async function iniciarQuiz(nivel) {
    navegar('tela-quiz');
    
    document.getElementById('texto-pergunta').innerHTML = `
        <div class="d-flex flex-column align-items-center py-4">
            <div class="spinner-border text-purple" role="status"></div>
            <p class="mt-2 text-muted small fw-bold">Carregando saberes...</p>
        </div>`;
    document.getElementById('container-alternativas').innerHTML = '';
    
    dificuldadeAtual = nivel;
    if (nivel === 'facil') pontosPorPergunta = 10;
    if (nivel === 'media') pontosPorPergunta = 20;
    if (nivel === 'dificil') pontosPorPergunta = 30;

    perguntasAtuais = await buscarPerguntas(nivel);

    if (!perguntasAtuais || perguntasAtuais.length === 0) {
        document.getElementById('texto-pergunta').innerHTML = `
            <div class="text-center p-2">
                <i class="bi bi-wifi-off text-danger fs-2 d-block mb-2"></i>
                <span class="fw-bold small text-dark">Dados indisponíveis offline. Verifique a conexão local.</span>
            </div>`;
        return;
    }

    indicePerguntaAtual = 0;
    pontuacaoAtual = 0;
    acertosAtuais = 0;
    atualizarPlacar();
    exibirPergunta();
}

function exibirPergunta() {
    if (indicePerguntaAtual >= perguntasAtuais.length) {
        finalizarQuiz();
        return;
    }

    const perguntaObjeto = perguntasAtuais[indicePerguntaAtual];
    
    // Tratamento de segurança contra incompatibilidades estruturais em JSONs acadêmicos (Anti-Undefined)
    const enunciadoLimpo = perguntaObjeto.pergunta || perguntaObjeto.enunciado || perguntaObjeto.texto || "Questão sem enunciado parametrizado.";
    const listaOpcoes = perguntaObjeto.alternativas || perguntaObjeto.opcoes || [];
    const indexCorreto = perguntaObjeto.resposta_correta !== undefined ? perguntaObjeto.resposta_correta : perguntaObjeto.resposta;
    
    document.getElementById('label-pergunta-atual').innerText = `Pergunta ${indicePerguntaAtual + 1} de ${perguntasAtuais.length}`;
    document.getElementById('texto-pergunta').innerText = enunciadoLimpo;
    
    const progresso = (indicePerguntaAtual / perguntasAtuais.length) * 100;
    document.getElementById('barra-progresso').style.width = `${progresso}%`;

    const container = document.getElementById('container-alternativas');
    container.innerHTML = '';

    listaOpcoes.forEach((textoAlternativa, index) => {
        const btn = document.createElement('button');
        btn.className = 'btn-quiz-option text-start';
        btn.innerText = textoAlternativa;
        
        const isCorreta = (index === indexCorreto);
        btn.onclick = () => validarResposta(btn, isCorreta, indexCorreto);
        
        container.appendChild(btn);
    });
}

function validarResposta(botaoClicado, isCorreta, indexCorreto) {
    const botoes = document.getElementById('container-alternativas').querySelectorAll('button');
    botoes.forEach(btn => btn.disabled = true);

    if (isCorreta) {
        botaoClicado.classList.add('option-correct');
        pontuacaoAtual += pontosPorPergunta;
        acertosAtuais++;
        atualizarPlacar();
    } else {
        botaoClicado.classList.add('option-wrong');
        if (botoes[indexCorreto]) {
            botoes[indexCorreto].classList.add('option-correct');
        }
    }

    setTimeout(() => {
        indicePerguntaAtual++;
        exibirPergunta();
    }, 1600);
}

function atualizarPlacar() {
    document.getElementById('label-pontuacao').innerText = `Pontos: ${pontuacaoAtual}`;
}

function finalizarQuiz() {
    document.getElementById('barra-progresso').style.width = '100%';
    document.getElementById('pontos-finais').innerText = pontuacaoAtual;
    document.getElementById('feedback-acertos').innerText = `Você acertou ${acertosAtuais} de ${perguntasAtuais.length} questões.`;

    salvarRecorde(pontuacaoAtual);
    
    if (pontuacaoAtual > 0) {
        setTimeout(() => {
            document.getElementById('nomeJogadorInput').value = ''; 
            const elementoModal = document.getElementById('modalRanking');
            const instanciaModal = new bootstrap.Modal(elementoModal);
            instanciaModal.show();
        }, 400);
    } else {
        navegar('tela-resultado');
    }
}

function confirmarSalvarRanking() {
    const inputNome = document.getElementById('nomeJogadorInput');
    const nomeJogador = inputNome.value.trim() || "Anônimo";
    const difFormatada = dificuldadeAtual === 'facil' ? 'Fácil' : (dificuldadeAtual === 'media' ? 'Média' : 'Difícil');
    
    // 1. Salva no LocalStorage permanentemente
    salvarNoRanking(nomeJogador, pontuacaoAtual, difFormatada);
    
    // 2. Fecha o Modal
    const elementoModal = document.getElementById('modalRanking');
    const instanciaModal = bootstrap.Modal.getInstance(elementoModal);
    if (instanciaModal) {
        instanciaModal.hide();
    }
    
    // 3. Redireciona para a tela de resultados
    navegar('tela-resultado');
}