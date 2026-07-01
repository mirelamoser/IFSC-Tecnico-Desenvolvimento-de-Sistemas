/**
 * js/api.js
 * Módulo responsável por simular o consumo de APIs lendo arquivos JSON locais.
 */

/**
 * Função assíncrona para buscar as perguntas do JSON local usando Axios.
 * @param {string} nivel - O nível de dificuldade ('facil', 'media', 'dificil')
 * @returns {Array} - Retorna um array de objetos com as perguntas
 */
async function buscarPerguntas(nivel) {
    try {
        // Mapeia o nível recebido para o nome do arquivo JSON correspondente
        let arquivo = '';
        if (nivel === 'facil') arquivo = 'faceis.json';
        else if (nivel === 'media') arquivo = 'medias.json';
        else if (nivel === 'dificil') arquivo = 'dificeis.json';

        // O Axios faz a requisição GET e já nos devolve o JSON pronto no .data
        const response = await axios.get(`data/${arquivo}`);
        
        return response.data;

    } catch (error) {
        console.error("Erro na API ao carregar as perguntas:", error);
        alert("Ops! Não foi possível carregar as perguntas. Tente novamente.");
        return []; // Retorna array vazio em caso de erro para não quebrar o app
    }
}

/**
 * Busca o ranking inicial (mock) do JSON local.
 * Utilizada na primeira vez que o usuário abrir o ranking, caso não haja dados locais.
 * @returns {Array} - Retorna um array com o ranking padrão
 */
async function buscarRankingInicial() {
    try {
        // Faz o GET especificamente no arquivo JSON exigido pelo professor
        const response = await axios.get('data/ranking.json');
        
        return response.data;

    } catch (error) {
        console.error("Erro na API ao carregar o ranking inicial:", error);
        // Retorna array vazio silenciosamente para não interromper a UX
        return []; 
    }
}