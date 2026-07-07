-- Patch para servidores com schema antigo (avaliação do professor / XP)
-- Execute: mysql -u philoquest_app -p philoquest < database/patches/001_avaliacao_professor.sql
-- Erros "Duplicate column" podem ser ignorados.

USE philoquest;

ALTER TABLE usuarios
    ADD COLUMN experiencia_total INT NOT NULL DEFAULT 0;

ALTER TABLE submissoes_etapa
    ADD COLUMN nota DECIMAL(4, 1) NULL COMMENT 'Nota 0-10 do Trabalho Final (Etapa 5)';

ALTER TABLE submissoes_etapa
    ADD COLUMN data_validacao TIMESTAMP NULL;

ALTER TABLE submissoes_etapa
    ADD COLUMN validado_por INT NULL;

ALTER TABLE submissoes_etapa
    ADD COLUMN feedback TEXT NULL;

ALTER TABLE submissoes_etapa
    MODIFY COLUMN status ENUM(
        'AGUARDANDO_VALIDACAO',
        'NECESSITA_REVISAO',
        'APROVADO',
        'APROVADO_BEM_FEITO',
        'APROVADO_EXCELENTE'
    ) NOT NULL DEFAULT 'AGUARDANDO_VALIDACAO';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
