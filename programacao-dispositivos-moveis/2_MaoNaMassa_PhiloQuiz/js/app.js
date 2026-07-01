// js/app.js

// 1. Registro do Service Worker (PWA)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.js')
            .then(reg => console.log('PWA Ativo!', reg.scope))
            .catch(err => console.log('Erro no PWA:', err));
    });
}

/**
 * ESCUTADOR DO RANKING
 * Este código roda sempre que navegar('tela-ranking') é chamado
 */
window.addEventListener('abrirRanking', async () => {
    console.log("Evento abrirRanking detectado. Montando tabela...");
    const tbody = document.getElementById('tabela-ranking');
    if (!tbody) return;

    // Feedback visual de carregamento
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Carregando recordes...</td></tr>';

    try {
        // 1. Tenta pegar do LocalStorage (storage.js)
        let dadosRanking = obterRanking();

        // 2. Se estiver vazio (primeiro acesso), busca do JSON (api.js)
        if (!dadosRanking || dadosRanking.length === 0) {
            console.log("LocalStorage vazio. Buscando do ranking.json...");
            dadosRanking = await buscarRankingInicial(); 
            
            if (dadosRanking && dadosRanking.length > 0) {
                // Salva no storage para os próximos acessos
                inicializarRankingDoJson(dadosRanking);
            }
        }

        // 3. Renderiza os dados na tabela
        if (dadosRanking && dadosRanking.length > 0) {
            const linhasHTML = dadosRanking.map((item, index) => {
                // Define a cor da badge de dificuldade
                let badgeCor = 'bg-success';
                const dif = item.dificuldade.toLowerCase();
                if (dif.includes('méd')) badgeCor = 'bg-warning text-dark';
                if (dif.includes('dif')) badgeCor = 'bg-danger';

                const iconePosicao = index === 0 ? '🥇' : (index === 1 ? '🥈' : (index === 2 ? '🥉' : `${index + 1}º`));
                const estiloTopo = index === 0 ? 'style="background-color: #fffbeb !important;"' : '';

                return `
                    <tr ${estiloTopo}>
                        <td class="fw-bold text-purple">${iconePosicao}</td>
                        <td class="fw-bold text-dark">${item.nome}</td>
                        <td><span class="badge ${badgeCor}" style="font-size: 0.7rem;">${item.dificuldade}</span></td>
                        <td class="text-end fw-extrabold text-purple">${item.pontuacao} <small class="fw-normal text-muted" style="font-size: 0.6rem">pts</small></td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = linhasHTML;
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Nenhum recorde encontrado.</td></tr>';
        }
    } catch (error) {
        console.error('Erro ao processar ranking:', error);
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4 small">Erro ao carregar ranking.</td></tr>';
    }
});