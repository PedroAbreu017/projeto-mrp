-- ================================================
-- Sistema MRP - Schema do Banco de Dados
-- ================================================

-- Criar database se não existir
CREATE DATABASE IF NOT EXISTS sistema_mrp 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sistema_mrp;

-- ================================================
-- Tabela: componentes
-- Armazena os componentes disponíveis e seu estoque atual
-- ================================================
CREATE TABLE componentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255) DEFAULT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    unidade_medida VARCHAR(20) DEFAULT 'unidade',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================
-- Tabela: produtos
-- Define os produtos que podem ser fabricados
-- ================================================
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao VARCHAR(255) DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================
-- Tabela: bom (Bill of Materials)
-- Define quais componentes são necessários para cada produto
-- ================================================
CREATE TABLE bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    componente_id INT NOT NULL,
    quantidade_necessaria INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_produto_componente (produto_id, componente_id)
);

-- ================================================
-- Inserir dados iniciais
-- ================================================

-- Inserir componentes básicos
INSERT INTO componentes (nome, descricao, quantidade_estoque) VALUES
('Rodas', 'Rodas para bicicleta', 10),
('Quadros', 'Quadro de bicicleta', 5),
('Guidões', 'Guidão de bicicleta', 10),
('Gabinetes', 'Gabinete para computador', 2),
('Placas-mãe', 'Placa-mãe para computador', 5),
('Memórias RAM', 'Pente de memória RAM', 6);

-- Inserir produtos
INSERT INTO produtos (nome, descricao) VALUES
('Bicicleta', 'Bicicleta completa'),
('Computador', 'Computador completo');

-- Inserir estrutura dos produtos (BOM)
-- Bicicleta (id=1): 2 rodas, 1 quadro, 1 guidão
INSERT INTO bom (produto_id, componente_id, quantidade_necessaria) VALUES
(1, 1, 2), -- Bicicleta precisa de 2 rodas
(1, 2, 1), -- Bicicleta precisa de 1 quadro  
(1, 3, 1); -- Bicicleta precisa de 1 guidão

-- Computador (id=2): 1 gabinete, 1 placa-mãe, 2 memórias RAM
INSERT INTO bom (produto_id, componente_id, quantidade_necessaria) VALUES
(2, 4, 1), -- Computador precisa de 1 gabinete
(2, 5, 1), -- Computador precisa de 1 placa-mãe
(2, 6, 2); -- Computador precisa de 2 memórias RAM

-- ================================================
-- Índices para performance
-- ================================================
CREATE INDEX idx_componentes_nome ON componentes(nome);
CREATE INDEX idx_produtos_nome ON produtos(nome);
CREATE INDEX idx_bom_produto ON bom(produto_id);
CREATE INDEX idx_bom_componente ON bom(componente_id);

-- ================================================
-- Views úteis para relatórios
-- ================================================

-- View para ver estrutura completa dos produtos
CREATE VIEW view_estrutura_produtos AS
SELECT 
    p.id as produto_id,
    p.nome as produto_nome,
    c.id as componente_id,
    c.nome as componente_nome,
    b.quantidade_necessaria,
    c.quantidade_estoque
FROM produtos p
JOIN bom b ON p.id = b.produto_id
JOIN componentes c ON b.componente_id = c.id
WHERE p.ativo = TRUE
ORDER BY p.nome, c.nome;

-- ================================================
-- Dados de exemplo para testes
-- ================================================

-- Verificar se os dados foram inseridos corretamente
SELECT 'Componentes inseridos:' as info;
SELECT * FROM componentes;

SELECT 'Produtos inseridos:' as info;
SELECT * FROM produtos;

SELECT 'Estrutura dos produtos (BOM):' as info;
SELECT * FROM view_estrutura_produtos;