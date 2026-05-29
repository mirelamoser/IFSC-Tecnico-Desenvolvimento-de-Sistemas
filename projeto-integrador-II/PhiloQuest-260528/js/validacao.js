document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCadastro');
    const inputCodigoTurma = document.getElementById('codigo_turma');
    const divTurma = document.getElementById('grupo_turma');
    const erroTurmaMsg = document.getElementById('erroTurma');
    const radiosTipo = document.querySelectorAll('input[name="tipo_usuario"]');

    // Segurança: se não estiver na página de cadastro, não executa o script e evita erros no console
    if (!form) return;

    // Regra: 3 números + 2 letras + 4 números (Ex: 103HS2026)
    const regexTurma = /^\d{3}[A-Za-z]{2}\d{4}$/;

    // Oculta/Mostra o campo de turma baseado na escolha do usuário
    if (radiosTipo.length > 0) {
        radiosTipo.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'PROFESSOR') {
                    divTurma.classList.add('hidden'); // Oculta o container
                    inputCodigoTurma.removeAttribute('required'); // Tira obrigatoriedade
                    inputCodigoTurma.value = ''; // Limpa qualquer valor digitado
                    erroTurmaMsg.style.display = 'none';
                    inputCodigoTurma.classList.remove('is-invalid');
                } else {
                    divTurma.classList.remove('hidden'); // Exibe o container
                    inputCodigoTurma.setAttribute('required', 'required'); // Torna obrigatório
                }
            });
        });
    }

    // Valida no momento de enviar o formulário
    form.addEventListener('submit', function(event) {
        // Captura o rádio selecionado de forma segura
        const radioSelecionado = document.querySelector('input[name="tipo_usuario"]:checked');
        const tipoSelecionado = radioSelecionado ? radioSelecionado.value : 'ALUNO';
        
        if (tipoSelecionado === 'ALUNO' && inputCodigoTurma) {
            const codigo = inputCodigoTurma.value.trim();

            if (!regexTurma.test(codigo)) {
                event.preventDefault(); // Impede o envio para o PHP
                inputCodigoTurma.classList.add('is-invalid');
                erroTurmaMsg.style.display = 'block';
                erroTurmaMsg.textContent = "Formato inválido. Use 3 números, 2 letras e o ano (Ex: 103HS2026).";
            }
        }
    });

    // Remove a mensagem de erro enquanto o usuário tenta consertar
    if (inputCodigoTurma) {
        inputCodigoTurma.addEventListener('input', function() {
            inputCodigoTurma.classList.remove('is-invalid');
            if (erroTurmaMsg) erroTurmaMsg.style.display = 'none';
        });
    }
});