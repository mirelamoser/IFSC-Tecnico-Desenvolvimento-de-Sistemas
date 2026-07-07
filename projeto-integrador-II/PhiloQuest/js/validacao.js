document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCadastro');
    const inputCodigoTurma = document.getElementById('codigo_turma');
    const erroTurmaMsg = document.getElementById('erroTurma');

    // Segurança: se não estiver na página de cadastro, não executa o script e evita erros no console
    if (!form) return;

    // Regra: 3 números + 2 letras + 4 números (Ex: 103HS2026)
    const regexTurma = /^\d{3}[A-Za-z]{2}\d{4}$/;

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
                if (erroTurmaMsg) {
                    erroTurmaMsg.classList.remove('d-none');
                    erroTurmaMsg.textContent = "Formato inválido. Use 3 números, 2 letras e o ano (Ex: 103HS2026).";
                }
            }
        }
    });

    // Remove a mensagem de erro enquanto o usuário tenta consertar
    if (inputCodigoTurma) {
        inputCodigoTurma.addEventListener('input', function() {
            inputCodigoTurma.classList.remove('is-invalid');
            if (erroTurmaMsg) erroTurmaMsg.classList.add('d-none');
        });
    }
});