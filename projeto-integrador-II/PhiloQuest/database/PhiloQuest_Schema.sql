-- Reformulação da Estrutura PhiloQuest para Entrega 21/06/26
CREATE DATABASE IF NOT EXISTS philoquest;
USE philoquest;

-- Desativa a checagem de chaves estrangeiras temporariamente para evitar conflito de dependência circular
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- TABELAS 
-- ==========================================

-- 1. Tabela de Turmas
CREATE TABLE IF NOT EXISTS turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_turma VARCHAR(20) UNIQUE NOT NULL,
    professor_id INT NULL,
    criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 2. Tabela de Matrículas Autorizadas (Permite controle de quem pode se cadastrar em quais turmas)
CREATE TABLE IF NOT EXISTS matriculas_autorizadas (
    matricula VARCHAR(50) PRIMARY KEY,
    tipo_usuario ENUM('ALUNO', 'PROFESSOR', 'ADMIN') NOT NULL DEFAULT 'ALUNO',
    status ENUM('DISPONIVEL', 'UTILIZADA') DEFAULT 'DISPONIVEL',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    turma_id VARCHAR(20) NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas(codigo_turma) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3. Tabela de Usuários (Alunos, Professores e Admins)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(50) UNIQUE NOT NULL,
    nome_completo VARCHAR(100) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('ALUNO', 'PROFESSOR', 'ADMIN') NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    forcar_troca_senha TINYINT(1) DEFAULT 0,
    turma_id INT NULL,
    experiencia_total INT DEFAULT 0,
    criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabela de Cartas Conceituais (Gamificação)
CREATE TABLE IF NOT EXISTS cartas_conceituais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    raridade ENUM('COMUM', 'RARA', 'EPICA', 'LENDARIA') DEFAULT 'COMUM',
    pontos_poder INT DEFAULT 10
) ENGINE=InnoDB;

-- 5. Relacionamento Alunos e Cartas (Inventário)
CREATE TABLE IF NOT EXISTS alunos_cartas (
    aluno_id INT NOT NULL,
    carta_id INT NOT NULL,
    data_aquisicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (aluno_id, carta_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (carta_id) REFERENCES cartas_conceituais(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabela de Etapas (O "Molde" do Ciclo de Aprendizagem)
CREATE TABLE IF NOT EXISTS etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    instrucoes TEXT,
    criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_numero (numero)
) ENGINE=InnoDB;

-- 7. Tabela de Submissões de Etapa (A resposta do Aluno)
-- REFATORADA: Adicionado os campos exatos do seu formulário PHP
CREATE TABLE IF NOT EXISTS submissoes_etapa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    etapa_id INT NOT NULL,
    turma_id INT NOT NULL,
    
    -- Dados preenchidos pelo aluno no formulário:
    titulo_submissao VARCHAR(200) NOT NULL,
    descricao_submissao TEXT NOT NULL,
    link_submissao VARCHAR(255) NULL,
    
    arquivo_submissao VARCHAR(255) NULL, -- Mantido para upload futuro de arquivos
    status ENUM('AGUARDANDO_VALIDACAO', 'NECESSITA_REVISAO', 'APROVADO', 'APROVADO_BEM_FEITO', 'APROVADO_EXCELENTE') DEFAULT 'AGUARDANDO_VALIDACAO',
    feedback TEXT,
    nota DECIMAL(4, 1) NULL COMMENT 'Nota 0-10 do Trabalho Final (Etapa 5)',
    data_submissao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_validacao TIMESTAMP NULL,
    validado_por INT NULL,
    
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (etapa_id) REFERENCES etapas(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (validado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY unique_submissao (aluno_id, etapa_id, turma_id)
) ENGINE=InnoDB;

-- 8. Tabela de Histórico de XP (Gamificação)
CREATE TABLE IF NOT EXISTS historico_xp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    xp_ganho INT NOT NULL,
    status_submissao VARCHAR(50),
    etapa_id INT NULL,
    data_ganho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (etapa_id) REFERENCES etapas(id) ON DELETE SET NULL,
    INDEX idx_aluno_data (aluno_id, data_ganho)
) ENGINE=InnoDB;

-- Etapa 3: Resposta Conceitual e Conceitos-Chave
-- Execute após o schema base (PhiloQuest_Schema.sql)


-- Etapas 2 e 3 no catálogo (se ainda não existirem)
INSERT IGNORE INTO etapas (id, numero, titulo, descricao, instrucoes) VALUES
(2, 2, 'Etapa 2: Questionamentos', 'Elaboração de perguntas filosóficas sobre o problema.', 'Registre pelo menos 6 questionamentos.'),
(3, 3, 'Etapa 3: Resposta Conceitual', 'Resposta conceitual e conceitos-chave.', 'Escolha uma pergunta da Etapa 2, responda e liste os conceitos-chave.'),
(4, 4, 'Etapa 4: Filósofos e Associação', 'Associação de filósofos aos conceitos-chave.', 'Para cada conceito da Etapa 3, cadastre um ou mais filósofos relacionados.'),
(5, 5, 'Etapa 5: Trabalho Final', 'Produção do trabalho completo integrando todas as etapas.', NULL);

-- Resposta conceitual vinculada à pergunta escolhida (Etapa 2)
CREATE TABLE IF NOT EXISTS respostas_etapa3 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    submissao_etapa_id INT NOT NULL,
    pergunta_numero INT UNSIGNED NOT NULL,
    pergunta_texto TEXT NOT NULL,
    resposta_conceitual TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_submissao_etapa3 (submissao_etapa_id),
    INDEX idx_resposta_aluno (aluno_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (submissao_etapa_id) REFERENCES submissoes_etapa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conceitos-chave (1 resposta : N conceitos)
CREATE TABLE IF NOT EXISTS conceitos_chave (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resposta_etapa3_id INT NOT NULL,
    termo VARCHAR(200) NOT NULL,
    ordem TINYINT UNSIGNED NOT NULL DEFAULT 1,
    FOREIGN KEY (resposta_etapa3_id) REFERENCES respostas_etapa3(id) ON DELETE CASCADE,
    INDEX idx_conceito_resposta (resposta_etapa3_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Etapa 4: Filósofos e Associação aos Conceitos-Chave (Etapa 3)
-- Execute após etapa3_resposta_conceitual.sql

USE philoquest;

INSERT IGNORE INTO etapas (id, numero, titulo, descricao, instrucoes) VALUES
(4, 4, 'Etapa 4: Filósofos e Associação', 'Associação de filósofos aos conceitos-chave.', 'Para cada conceito da Etapa 3, cadastre um ou mais filósofos relacionados.');

CREATE TABLE IF NOT EXISTS filosofos_etapa4 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    submissao_etapa_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    epoca ENUM('ANTIGA', 'MEDIEVAL', 'MODERNA', 'CONTEMPORANEA') NOT NULL,
    linha_pensamento VARCHAR(255) NOT NULL,
    ideias_principais TEXT NOT NULL,
    citacao TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_filosofo_submissao (submissao_etapa_id),
    INDEX idx_filosofo_aluno (aluno_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (submissao_etapa_id) REFERENCES submissoes_etapa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conceito_filosofo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conceito_chave_id INT NOT NULL,
    filosofo_id INT NOT NULL,
    UNIQUE KEY uk_conceito_filosofo (conceito_chave_id, filosofo_id),
    INDEX idx_cf_conceito (conceito_chave_id),
    INDEX idx_cf_filosofo (filosofo_id),
    FOREIGN KEY (conceito_chave_id) REFERENCES conceitos_chave(id) ON DELETE CASCADE,
    FOREIGN KEY (filosofo_id) REFERENCES filosofos_etapa4(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Missões Extras
CREATE TABLE IF NOT EXISTS missoes_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    link_referencia VARCHAR(255) NULL,
    turma_id INT NOT NULL,
    professor_id INT NOT NULL,
    xp_recompensa INT NOT NULL DEFAULT 80,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_missao_turma (turma_id),
    INDEX idx_missao_professor (professor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entregas_missoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    missao_id INT NOT NULL,
    aluno_id INT NOT NULL,
    resposta_texto TEXT NOT NULL,
    status ENUM('PENDENTE', 'APROVADO', 'REVISAR') NOT NULL DEFAULT 'PENDENTE',
    feedback_professor TEXT NULL,
    data_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_avaliacao TIMESTAMP NULL,
    xp_atribuido INT NULL DEFAULT 0,
    avaliado_por INT NULL,
    UNIQUE KEY uk_entrega_missao_aluno (missao_id, aluno_id),
    FOREIGN KEY (missao_id) REFERENCES missoes_extras(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (avaliado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_entrega_status (missao_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Reativa a checagem de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;


-- ==========================================
-- SEEDERS (DADOS INICIAIS)
-- ==========================================

-- Administrador: crie após o deploy com scripts/criar_admin.php (não incluir senha padrão aqui).

-- CRÍTICO: Cria a "Etapa 1" no sistema para o aluno ter onde submeter sua primeira atividade
INSERT IGNORE INTO etapas (id, numero, titulo, descricao, instrucoes) 
VALUES (1, 1, 'Etapa 1: Identificação do Problema', 'Fase inicial do Ciclo de Aprendizagem.', 'Preencha o título, a descrição do seu problema e, se desejar, adicione um link de referência.');