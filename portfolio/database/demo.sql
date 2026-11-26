-- Esquema mínimo, inteiramente fictício. Importe apenas em um banco descartável vazio.
SET NAMES utf8mb4;
CREATE TABLE usuario (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin (usuario_id INT PRIMARY KEY, nome VARCHAR(100) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuario(usuario_id));
CREATE TABLE contribuidor LIKE admin;
CREATE TABLE motoboy LIKE admin;

CREATE TABLE CATEGORIA (
    ID_CAT INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ITENS (
    ID_ITM INT AUTO_INCREMENT PRIMARY KEY,
    ID_CAT INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    unidade VARCHAR(30) NOT NULL,
    origem VARCHAR(30) NOT NULL,
    ativo TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY (ID_CAT) REFERENCES CATEGORIA(ID_CAT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE PED_REP (
    ID_PR INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    condicao VARCHAR(30) NOT NULL,
    data_PR DATETIME NOT NULL,
    observacao VARCHAR(100),
    FOREIGN KEY (usuario_id) REFERENCES usuario(usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE PED_ITM (
    ID_PR INT NOT NULL,
    ID_ITM INT NOT NULL,
    quantidade DECIMAL(9,3) NOT NULL,
    observacao VARCHAR(100),
    PRIMARY KEY (ID_PR, ID_ITM),
    FOREIGN KEY (ID_PR) REFERENCES PED_REP(ID_PR),
    FOREIGN KEY (ID_ITM) REFERENCES ITENS(ID_ITM)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO CATEGORIA (ID_CAT, categoria) VALUES (1, 'Produção'), (2, 'Pães');
INSERT INTO ITENS (ID_CAT, nome, unidade, origem) VALUES
    (1, 'Farinha de demonstração', 'kg', 'Fábrica'),
    (2, 'Pão de demonstração', 'un', 'Loja');
