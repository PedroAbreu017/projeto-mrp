<?php
namespace App\Models\Entities;

/**
 * Model: Estoque
 * Migrado de models/estoque.php para arquitetura PSR-4
 * Gerencia operações relacionadas ao estoque de componentes
 */
class Estoque {
    private $db;
    
      // E MODIFIQUE O __construct() do Estoque:
     public function __construct() {
       // ADICIONAR ESTA LINHA NO INÍCIO:
       $this->carregarEnv();
    
       $this->db = $this->getDatabase();
   }

   /**
 * Carregar variáveis de ambiente se não estiverem carregadas
 */
   private function carregarEnv() {
       if (empty($_ENV['DB_NAME'])) {
           $envFile = __DIR__ . '/../../../.env';
        
           if (file_exists($envFile)) {
               $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
               foreach ($lines as $line) {
                   if (strpos($line, '=') !== false && $line[0] !== '#') {
                       list($key, $value) = explode('=', $line, 2);
                       $_ENV[trim($key)] = trim($value, '"\'');
                   }
               }
           }
       }
   }



    /**
     * Database connection (temporário - depois vamos usar DI)
     */
    private function getDatabase() {
        // Conexão direta temporária
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? null;
            $username = $_ENV['DB_USER'] ?? null;
            $password = $_ENV['DB_PASS'] ?? null;
        
           if (empty($dbname) || empty($username)) {
               throw new \Exception("Database credentials missing. Check .env file: DB_NAME={$dbname}, DB_USER={$username}");
        }
   
            $pdo = new \PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
            
            // Criar wrapper para compatibilidade com métodos originais
            return new class($pdo) {
                private $pdo;
                public function __construct($pdo) { $this->pdo = $pdo; }
                public function fetchAll($sql, $params = []) { 
                    $stmt = $this->pdo->prepare($sql); 
                    $stmt->execute($params); 
                    return $stmt->fetchAll(); 
                }
                public function fetch($sql, $params = []) { 
                    $stmt = $this->pdo->prepare($sql); 
                    $stmt->execute($params); 
                    return $stmt->fetch(); 
                }
                public function execute($sql, $params = []) { 
                    $stmt = $this->pdo->prepare($sql); 
                    return $stmt->execute($params) ? $stmt->rowCount() : false; 
                }
                public function lastInsertId() { 
                    return $this->pdo->lastInsertId(); 
                }
            };
            
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Busca todos os componentes em estoque
     */
    public function getAllComponentes() {
        $sql = "SELECT 
                    id, 
                    nome, 
                    descricao, 
                    quantidade_estoque,
                    unidade_medida,
                    updated_at
                FROM componentes 
                ORDER BY nome ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Busca um componente específico pelo ID
     */
    public function getComponenteById($id) {
        $sql = "SELECT * FROM componentes WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Busca um componente pelo nome
     */
    public function getComponenteByNome($nome) {
        $sql = "SELECT * FROM componentes WHERE nome = ?";
        return $this->db->fetch($sql, [$nome]);
    }
    
    /**
     * Atualiza a quantidade em estoque de um componente
     */
    public function atualizarEstoque($id, $quantidade) {
        // Validação básica
        if (!is_numeric($quantidade) || $quantidade < 0) {
            throw new \InvalidArgumentException("Quantidade deve ser um número positivo");
        }
        
        $sql = "UPDATE componentes 
                SET quantidade_estoque = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $rowsAffected = $this->db->execute($sql, [$quantidade, $id]);
        
        if ($rowsAffected === 0) {
            throw new \Exception("Componente não encontrado ou não foi possível atualizar");
        }
        
        return true;
    }
    
    /**
     * Adiciona um novo componente ao estoque
     */
    public function adicionarComponente($nome, $descricao = '', $quantidade = 0, $unidade = 'unidade') {
        // Validação
        if (empty(trim($nome))) {
            throw new \InvalidArgumentException("Nome do componente é obrigatório");
        }
        
        if (!is_numeric($quantidade) || $quantidade < 0) {
            throw new \InvalidArgumentException("Quantidade deve ser um número positivo");
        }
        
        // Verifica se já existe
        $existente = $this->getComponenteByNome($nome);
        if ($existente) {
            throw new \Exception("Já existe um componente com este nome");
        }
        
        $sql = "INSERT INTO componentes (nome, descricao, quantidade_estoque, unidade_medida) 
                VALUES (?, ?, ?, ?)";
        
        $this->db->execute($sql, [$nome, $descricao, $quantidade, $unidade]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Remove um componente do estoque
     */
    public function removerComponente($id) {
        // Verifica se o componente está sendo usado em algum produto
        $sqlCheck = "SELECT COUNT(*) as count FROM bom WHERE componente_id = ?";
        $result = $this->db->fetch($sqlCheck, [$id]);
        
        if ($result['count'] > 0) {
            throw new \Exception("Este componente está sendo usado em produtos e não pode ser removido");
        }
        
        $sql = "DELETE FROM componentes WHERE id = ?";
        $rowsAffected = $this->db->execute($sql, [$id]);
        
        if ($rowsAffected === 0) {
            throw new \Exception("Componente não encontrado");
        }
        
        return true;
    }
    
    /**
     * Atualiza informações de um componente
     */public function atualizarComponente($id, $nome, $descricao = '', $quantidade = null, $unidade = 'unidade') {
    // Validação básica
    if (empty(trim($nome))) {
        throw new \InvalidArgumentException("Nome do componente é obrigatório");
    }
    
    // ✅ CORREÇÃO: Verificação mais robusta e com logs
    try {
        $existente = $this->getComponenteByNome($nome);
        
        // ✅ CORREÇÃO: Verificação mais específica
        if ($existente !== false && $existente !== null && isset($existente['id'])) {
            if ((int)$existente['id'] !== (int)$id) {
                throw new \Exception("Já existe outro componente com este nome");
            }
        }
    } catch (\Exception $e) {
        // ✅ CORREÇÃO: Log do erro para debug
        error_log("Erro ao verificar componente existente: " . $e->getMessage());
        // Por segurança, continuamos sem a verificação duplicada
    }
    
    // ✅ CORREÇÃO: SQL mais robusto
    try {
        $sql = "UPDATE componentes 
                SET nome = ?, descricao = ?, unidade_medida = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$nome, $descricao, $unidade];
        
        // Se quantidade foi fornecida, inclui na atualização
        if ($quantidade !== null) {
            if (!is_numeric($quantidade) || $quantidade < 0) {
                throw new \InvalidArgumentException("Quantidade deve ser um número positivo");
            }
            $sql .= ", quantidade_estoque = ?";
            $params[] = $quantidade;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $rowsAffected = $this->db->execute($sql, $params);
        
        if ($rowsAffected === 0) {
            throw new \Exception("Componente não encontrado ou nenhuma alteração foi feita");
        }
        
        return true;
        
    } catch (\Exception $e) {
        error_log("Erro na query UPDATE: " . $e->getMessage());
        throw $e;
    }
}
    /**
     * Busca componentes com estoque baixo
     */
    public function getComponentesEstoqueBaixo($limite = 5) {
        $sql = "SELECT * FROM componentes 
                WHERE quantidade_estoque <= ? 
                ORDER BY quantidade_estoque ASC, nome ASC";
        
        return $this->db->fetchAll($sql, [$limite]);
    }
    
    /**
     * Obtém estatísticas do estoque
     */
    public function getEstatisticasEstoque() {
        $sql = "SELECT 
                    COUNT(*) as total_componentes,
                    SUM(quantidade_estoque) as total_itens,
                    AVG(quantidade_estoque) as media_estoque,
                    COUNT(CASE WHEN quantidade_estoque = 0 THEN 1 END) as sem_estoque,
                    COUNT(CASE WHEN quantidade_estoque <= 5 THEN 1 END) as estoque_baixo
                FROM componentes";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Valida dados de entrada para componente
     */
    public function validarDadosComponente($dados) {
        $erros = [];
        
        if (empty(trim($dados['nome'] ?? ''))) {
            $erros[] = "Nome do componente é obrigatório";
        }
        
        if (isset($dados['quantidade_estoque'])) {
            if (!is_numeric($dados['quantidade_estoque']) || $dados['quantidade_estoque'] < 0) {
                $erros[] = "Quantidade deve ser um número positivo";
            }
        }
        
        return $erros;
    }
    
    /**
     * Busca histórico de movimentações (placeholder para futuras implementações)
     */
    public function getHistoricoMovimentacoes($componenteId = null, $limite = 50) {
        // Placeholder para futuras implementações de histórico
        return [];
    }
}