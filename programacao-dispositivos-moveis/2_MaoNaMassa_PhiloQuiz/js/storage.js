// js/storage.js
const CHAVE_RANKING = 'philoquest_ranking_oficial';

function obterRanking() {
    // Busca os dados salvos no navegador. Se não tiver nada, retorna uma lista vazia.
    const dados = localStorage.getItem(CHAVE_RANKING);
    return dados ? JSON.parse(dados) : [];
}

function inicializarRankingDoJson(dadosJson) {
    const atual = obterRanking();
    // Só inicializa se o localStorage estiver vazio (para não apagar quem já jogou)
    if (atual.length === 0) {
        localStorage.setItem(CHAVE_RANKING, JSON.stringify(dadosJson));
    }
}

function salvarNoRanking(nome, pontuacao, dificuldade) {
    const rankingAtual = obterRanking();
    
    // Adiciona o novo jogador
    rankingAtual.push({
        nome: nome,
        pontuacao: pontuacao,
        dificuldade: dificuldade
    });

    // Ordena do maior para o menor (decrescente)
    rankingAtual.sort((a, b) => b.pontuacao - a.pontuacao);

    // Salva no navegador permanentemente
    localStorage.setItem(CHAVE_RANKING, JSON.stringify(rankingAtual));
}

function salvarRecorde(pontos) {
    localStorage.setItem('ultimo_recorde', pontos);
}