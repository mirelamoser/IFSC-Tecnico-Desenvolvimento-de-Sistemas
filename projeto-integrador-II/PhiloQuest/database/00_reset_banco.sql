-- Zera o banco philoquest (executar como root MySQL).
-- O usuário philoquest_app já deve existir (criado no painel/hosting).
-- Uso: mysql -u root -p < database/00_reset_banco.sql

DROP DATABASE IF EXISTS philoquest;

CREATE DATABASE philoquest
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
