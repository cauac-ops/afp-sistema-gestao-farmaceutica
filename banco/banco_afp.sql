SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS funcionario (
    id_func     INT AUTO_INCREMENT PRIMARY KEY,
    nome_func   VARCHAR(150) NOT NULL,
    cpf_func    VARCHAR(14),
    email_func  VARCHAR(150) NOT NULL UNIQUE,
    telefone    VARCHAR(20),
    cargo       ENUM('Farmacêutico','Atendente','Gerente','Auxiliar','Administrador') NOT NULL DEFAULT 'Atendente',
    senha_hash  VARCHAR(255) NOT NULL,
    ativo       TINYINT(1) DEFAULT 1,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categoria (
    id_cat   INT AUTO_INCREMENT PRIMARY KEY,
    nome_cat VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS produto (
    id_prod            INT AUTO_INCREMENT PRIMARY KEY,
    nome_prod          VARCHAR(150) NOT NULL,
    cod_bar_prod       VARCHAR(60),
    id_cat             INT,
    descricao          TEXT,
    preco_compra       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    preco_venda        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estoque_atual      INT NOT NULL DEFAULT 0,
    estoque_minimo     INT NOT NULL DEFAULT 5,
    necessita_receita  TINYINT(1) DEFAULT 0,
    data_validade      DATE,
    fornecedor         VARCHAR(150),
    criado_em          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cat) REFERENCES categoria(id_cat) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cliente (
    id_cli        INT AUTO_INCREMENT PRIMARY KEY,
    nome_cli      VARCHAR(150) NOT NULL,
    cpf_cli       VARCHAR(14),
    email_cli     VARCHAR(150),
    telefone_cli  VARCHAR(20),
    data_nasc     DATE,
    endereco      VARCHAR(255),
    observacoes   TEXT,
    criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS venda (
    id_venda         INT AUTO_INCREMENT PRIMARY KEY,
    id_cli           INT NOT NULL,
    id_func          INT NOT NULL,
    valor_total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    desconto         DECIMAL(10,2) DEFAULT 0.00,
    forma_pagamento  ENUM('Dinheiro','Débito','Crédito','PIX') NOT NULL DEFAULT 'Dinheiro',
    observacoes      TEXT,
    status           VARCHAR(30) DEFAULT 'Concluída',
    data_venda       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cli)  REFERENCES cliente(id_cli),
    FOREIGN KEY (id_func) REFERENCES funcionario(id_func)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS produto_venda (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_venda        INT NOT NULL,
    id_prod         INT NOT NULL,
    quantidade      INT NOT NULL DEFAULT 1,
    preco_unitario  DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venda) REFERENCES venda(id_venda) ON DELETE CASCADE,
    FOREIGN KEY (id_prod)  REFERENCES produto(id_prod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS agendamento (
    id_agenda      INT AUTO_INCREMENT PRIMARY KEY,
    id_cli         INT NOT NULL,
    id_func        INT NOT NULL,
    tipo_servico   VARCHAR(100) NOT NULL,
    data_agenda    DATETIME NOT NULL,
    observacoes    TEXT,
    valor_servico  DECIMAL(10,2) DEFAULT 0.00,
    status         ENUM('Agendado','Concluído','Cancelado','Faltou') DEFAULT 'Agendado',
    criado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cli)  REFERENCES cliente(id_cli),
    FOREIGN KEY (id_func) REFERENCES funcionario(id_func)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS receita (
    id_receita    INT AUTO_INCREMENT PRIMARY KEY,
    id_cli        INT NOT NULL,
    id_func       INT NOT NULL,
    medico        VARCHAR(150),
    crm_medico    VARCHAR(30),
    data_emissao  DATE NOT NULL,
    data_validade DATE,
    descricao     TEXT,
    status        ENUM('Válida','Vencida','Cancelada') DEFAULT 'Válida',
    criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cli)  REFERENCES cliente(id_cli),
    FOREIGN KEY (id_func) REFERENCES funcionario(id_func)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimentacao_estoque (
    id_mov    INT AUTO_INCREMENT PRIMARY KEY,
    id_prod   INT NOT NULL,
    id_func   INT NOT NULL,
    tipo      ENUM('Entrada','Saída') NOT NULL,
    quantidade INT NOT NULL,
    motivo    VARCHAR(255),
    data_mov  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_prod) REFERENCES produto(id_prod),
    FOREIGN KEY (id_func) REFERENCES funcionario(id_func)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;


INSERT IGNORE INTO categoria (nome_cat) VALUES
('Medicamentos'),('Higiene'),('Cosméticos'),('Suplementos'),('Material Hospitalar');


INSERT IGNORE INTO funcionario (nome_func, email_func, cargo, senha_hash) VALUES
('Administrador', 'admin@suafarmacia.com', 'Administrador', 'SUBSTITUA_POR_HASH_GERADO_LOCALMENTE_____________');
