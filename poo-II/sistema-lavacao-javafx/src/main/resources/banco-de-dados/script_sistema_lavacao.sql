DROP DATABASE IF EXISTS db_lavacao;
CREATE DATABASE IF NOT EXISTS db_lavacao;
USE db_lavacao;

CREATE TABLE cor (
    id int NOT NULL auto_increment,
    nome varchar(30) NOT NULL,
    CONSTRAINT pk_cor PRIMARY KEY (id)
) engine = InnoDB;

CREATE TABLE marca (
    id int NOT NULL auto_increment,
    nome varchar(30) NOT NULL,
    CONSTRAINT pk_marca PRIMARY KEY (id)
) engine = InnoDB;

CREATE TABLE servico (
    id int NOT NULL auto_increment,
    descricao varchar(100) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    categoria VARCHAR(30) NOT NULL,
    CONSTRAINT pk_servico PRIMARY KEY (id)
) engine = InnoDB;

CREATE TABLE configuracoes (
    id int NOT NULL,
    pontos_servico int NOT NULL,
    CONSTRAINT pk_configuracoes PRIMARY KEY (id)
) engine = InnoDB;

/*TABELA MODELO COM RELACIONAMENTO 1:1 PARA MOTOR*/
CREATE TABLE modelo (
    id int NOT NULL auto_increment,
    descricao varchar(50) NOT NULL,
    id_marca int NOT NULL,
    categoria ENUM ('PEQUENO', 'MÉDIO', 'GRANDE','MOTO', 'PADRÃO') NOT NULL DEFAULT 'PEQUENO',
    CONSTRAINT pk_modelo PRIMARY KEY (id),
    CONSTRAINT fk_modelo_marca FOREIGN KEY (id_marca) REFERENCES marca (id)
) engine = InnoDB;

/*TABELA MOTOR COM RELACIONAMENTO 1:1 PARA MODELO*/
CREATE TABLE motor (
     id_modelo INT NOT NULL REFERENCES modelo (id),
     tipoCombustivel ENUM ('GASOLINA', 'ETANOL', 'FLEX','DIESEL', 'OUTRO') NOT NULL DEFAULT 'GASOLINA',
     potencia INT NOT NULL,
     CONSTRAINT pk_motor PRIMARY KEY (id_modelo),
     CONSTRAINT fk_motor_modelo FOREIGN KEY (id_modelo) REFERENCES modelo (id) ON DELETE CASCADE
) engine = InnoDB;

/*HERANÇA*/
CREATE TABLE cliente (
    id int NOT NULL auto_increment,
    nome varchar(500) NOT NULL,
    celular varchar(50) NOT NULL,
    email varchar(100) NOT NULL,
    data_cadastro date NOT NULL,
    CONSTRAINT pk_cliente PRIMARY KEY (id)
) engine = InnoDB;

CREATE TABLE pessoa_fisica (
    id_cliente int NOT NULL,
    cpf varchar(14) NOT NULL,
    data_nascimento date NOT NULL,
    CONSTRAINT pk_pf PRIMARY KEY (id_cliente),
    CONSTRAINT fk_pf_cliente FOREIGN KEY (id_cliente) REFERENCES cliente (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) engine = InnoDB;

CREATE TABLE pessoa_juridica (
    id_cliente int NOT NULL,
    cnpj varchar(18) NOT NULL,
    inscricao_estadual varchar(20) NOT NULL,
    CONSTRAINT pk_pj PRIMARY KEY (id_cliente),
    CONSTRAINT fk_pj_cliente FOREIGN KEY (id_cliente) REFERENCES cliente (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) engine = InnoDB;

/*COMPOSIÇÃO COM CLIENTE*/
CREATE TABLE pontuacao (
    id_cliente int NOT NULL,
    quantidade int NOT NULL DEFAULT 0,
    CONSTRAINT pk_pontuacao PRIMARY KEY (id_cliente),
    CONSTRAINT fk_pontuacao_cliente FOREIGN KEY (id_cliente) REFERENCES cliente (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) engine = InnoDB;

/*ASSOCIAÇÃO BIDIRECIONAL - O veículo é de um cliente - Um cliente tem muitos veículos (por isso o id_cliente está aqui*/
CREATE TABLE veiculo (
    id int NOT NULL auto_increment,
    placa varchar(10) NOT NULL,
    observacoes varchar(200),
    id_modelo int NOT NULL,
    id_cor int NOT NULL,
    id_cliente int NOT NULL,
    CONSTRAINT pk_veiculo PRIMARY KEY (id),
    CONSTRAINT fk_veiculo_modelo FOREIGN KEY (id_modelo) REFERENCES modelo (id),
    CONSTRAINT fk_veiculo_cor FOREIGN KEY (id_cor) REFERENCES cor (id),
    CONSTRAINT fk_veiculo_cliente FOREIGN KEY (id_cliente) REFERENCES cliente (id)
) engine = InnoDB;

/*ASSOCIAÇÃO COM VEÍCULO - ORDEM DE SERVICO - Aqui centralizamos as informações da transação (a partir da OS e possível descobrir tudo).
 Tendo o 'id_veiculo' aqui, conseguimos fazer um JOIN com veiculo e, em seguida, um JOIN com cliente para recuperar todos os dados*/
CREATE TABLE ordem_servico (
     numero BIGINT AUTO_INCREMENT PRIMARY KEY,
     total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
     agenda DATE NOT NULL,
     desconto DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
     status ENUM('ABERTA', 'FECHADA', 'CANCELADA') NOT NULL DEFAULT 'ABERTA',
     id_veiculo INT NOT NULL,
     CONSTRAINT fk_os_veiculo FOREIGN KEY (id_veiculo) REFERENCES veiculo (id)
         ON DELETE RESTRICT -- Evita deletar um veículo se ele tiver OS atrelada
) engine = InnoDB;


/*ASSOCIAÇÃO MUITOS PARA MUITOS - ITEM_OS - Um serviço pode estar em várias OS e uma OS tem vários serviços, criamos tabela intermediária*/
CREATE TABLE item_os (
      id_ordem_servico BIGINT NOT NULL,
      id_servico INT NOT NULL,
      valor_servico DECIMAL(10, 2) NOT NULL,
      observacoes VARCHAR(255),
      CONSTRAINT pk_item_os PRIMARY KEY (id_ordem_servico, id_servico), -- O 'id_ordem_servico' e 'id_servico' juntos formam a Chave Primária

    -- Se a OS for deletada, os itens dela TAMBÉM serão deletados.
      CONSTRAINT fk_item_os_ordem FOREIGN KEY (id_ordem_servico) REFERENCES ordem_servico (numero)
          ON DELETE CASCADE,

      CONSTRAINT fk_item_os_servico FOREIGN KEY (id_servico) REFERENCES servico (id)
           ON DELETE RESTRICT -- Evita apagar um serviço do catálogo se já foi usado numa OS
) engine = InnoDB;

-- 1. TABELAS INDEPENDENTES
INSERT INTO cor(nome)VALUES ('AZUL');
INSERT INTO cor(nome)VALUES ('PRETO');
INSERT INTO cor(nome)VALUES ('BRANCO');

INSERT INTO marca(nome)VALUES ('VOLKSWAGEN');
INSERT INTO marca(nome)VALUES ('JEEP');
INSERT INTO marca(nome)VALUES ('FIAT');

INSERT INTO servico(descricao, valor, categoria) VALUES ('LAVAÇÃO VEÍCULO PEQUENO', 90.00, 'PEQUENO');
INSERT INTO servico(descricao, valor, categoria) VALUES ('LAVAÇÃO VEÍCULO MÉDIO', 120.00, 'MEDIO');
INSERT INTO servico(descricao, valor, categoria) VALUES ('LAVAÇÃO VEÍCULO GRANDE', 150.00, 'GRANDE');
INSERT INTO servico(descricao, valor, categoria) VALUES ('APLICAÇÃO DE CERA', 30.00, 'PADRAO');
INSERT INTO servico(descricao, valor, categoria) VALUES ('POLIMENTO', 50.00, 'PADRAO');

INSERT INTO configuracoes(id, pontos_servico)VALUES (1, 10);

-- 2. TABELAS DEPENDENTES (Dependem de Marca)
INSERT INTO modelo(descricao, id_marca)VALUES ('T-Cross', 1);
INSERT INTO motor(id_modelo, potencia, tipoCombustivel) VALUES (1, 150, 'FLEX');
INSERT INTO modelo(descricao, id_marca)VALUES ('Renegade', 2);
INSERT INTO motor(id_modelo, potencia, tipoCombustivel) VALUES (2, 185, 'FLEX');
INSERT INTO modelo(descricao, id_marca)VALUES ('Kombi', 1);
INSERT INTO motor(id_modelo, potencia, tipoCombustivel) VALUES (3, 75, 'GASOLINA');

-- 3. CLIENTES, SUAS HERANÇAS E PONTUAÇÃO
-- Maurício (PF)
INSERT INTO cliente (id, nome, celular, email, data_cadastro) VALUES (111, 'Maurício', '(48)999999999', 'mauricio@gmail.com', '2025-10-11');
INSERT INTO pessoa_fisica (id_cliente, cpf, data_nascimento) VALUES (111, '111.222.333-44', '1980-10-24');
INSERT INTO pontuacao (id_cliente, quantidade) VALUES (111, 0);

-- Loja de Ferramentas (PJ)
INSERT INTO cliente (id, nome, celular, email, data_cadastro) VALUES (222, 'Loja de Ferramentas', '(48)888888888', 'ferramentas@gmail.com', '2025-10-12');
INSERT INTO pessoa_juridica (id_cliente, cnpj, inscricao_estadual) VALUES (222, '11.222.333/0001-55', '333333333');
INSERT INTO pontuacao (id_cliente, quantidade) VALUES (222, 0);

-- Carla (PF)
INSERT INTO cliente (id, nome, celular, email, data_cadastro) VALUES (333, 'Carla', '(48)777777777', 'carla@gmail.com', '2025-03-12');
INSERT INTO pessoa_fisica (id_cliente, cpf, data_nascimento) VALUES (333, '222.333.444-55', '1975-11-07');
INSERT INTO pontuacao (id_cliente, quantidade) VALUES (333, 0);

-- 4. VEÍCULOS (Dependem de Modelo, Cor e Cliente)
INSERT INTO veiculo(placa, observacoes, id_modelo, id_cor, id_cliente) VALUES ('AAA1212', 'Carro Usado', 1, 1, 111);
INSERT INTO veiculo(placa, observacoes, id_modelo, id_cor, id_cliente) VALUES ('BBB3434', 'Carro Novo', 2, 2, 222);
INSERT INTO veiculo(placa, observacoes, id_modelo, id_cor, id_cliente) VALUES ('CCC5656', 'Carro Usado', 3, 2, 333);

-- 5. ORDEM DE SERVIÇO E ITENS (Conforme a regra de negócio)

-- Criando uma Ordem de Serviço para o veículo do Maurício (id_veiculo = 1, placa AAA1212)
INSERT INTO ordem_servico (agenda, desconto, status, id_veiculo)
VALUES ('2026-05-30', 0.00, 'ABERTA', 1);

-- Inserindo o PRIMEIRO item na OS 1 (Lavação Veículo Pequeno - id_servico 1)
INSERT INTO item_os (id_ordem_servico, id_servico, valor_servico, observacoes)
VALUES (1, 1, 90.00, 'Atenção aos tapetes');

-- Inserindo o SEGUNDO item na OS 1 (Aplicação de Cera - id_servico 4)
INSERT INTO item_os (id_ordem_servico, id_servico, valor_servico, observacoes)
VALUES (1, 4, 30.00, 'Cera premium');

-- Deixamos o 'total' da OS zerado na inserção, pois o 'total' é calculado dinamicamente somando os valores dos itens
-- Podemos atualizar esse campo no banco depois de fazer o cálculo na sua classe Java.