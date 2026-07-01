// js/routes.js

let telaAnterior = 'tela-home';
let telaAtual = 'tela-home';

/**
 * Controla a navegação da SPA aplicando as classes com microinterações
 */
function navegar(destino) {
    const telas = document.querySelectorAll('.tela-spa');
    
    telas.forEach(tela => {
        tela.classList.remove('show');
        // Pequeno atraso para ocultar fisicamente permitindo a animação de saída
        setTimeout(() => {
            if (!tela.classList.contains('show')) {
                tela.classList.remove('d-block');
                tela.classList.add('d-none');
            }
        }, 150);
    });

    setTimeout(() => {
        const telaDestino = document.getElementById(destino);
        if (telaDestino) {
            telaDestino.classList.remove('d-none');
            telaDestino.classList.add('d-block');
            
            // Força o reflow do navegador para engatilhar a transição de opacidade do CSS
            window.getComputedStyle(telaDestino).opacity;
            telaDestino.classList.add('show');
        }
    }, 160);

    telaAnterior = telaAtual;
    telaAtual = destino;

    if (destino === 'tela-ranking') {
        window.dispatchEvent(new Event('abrirRanking'));
    }
    
    window.scrollTo(0, 0);
}

function voltar() {
    navegar(telaAnterior);
}

function confirmarNavegacao(destino) {
    if (telaAtual === 'tela-quiz') {
        const modalElement = document.getElementById('modalConfirmacao');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
        const btnSair = document.getElementById('btn-confirmar-sair');
        
        const novoBtnSair = btnSair.cloneNode(true);
        btnSair.parentNode.replaceChild(novoBtnSair, btnSair);
        
        novoBtnSair.addEventListener('click', () => {
            modalInstance.hide();
            navegar(destino);
        });
        
        modalInstance.show();
    } else {
        navegar(destino);
    }
}