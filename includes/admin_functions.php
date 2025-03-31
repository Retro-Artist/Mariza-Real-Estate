<?php
// Make sure database connection is available
if (!isset($databaseConnection)) {
    require_once __DIR__ . '/database.php';
}

// ===================================
// Admin Authentication Functions
// ===================================

/**
 * Authenticate admin user
 * 
 * @param string $email Admin email
 * @param string $password Admin password
 * @return array|false User data if authenticated, false otherwise
 */
function authenticateAdmin(string $email, string $password)
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT id, nome, email, senha, nivel FROM sistema_usuarios 
             WHERE email = :email LIMIT 1"
        );
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['senha'])) {
            return $user;
        }

        return false;
    } catch (PDOException $e) {
        logError("Authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has admin privileges
 * 
 * @param int $userId User ID
 * @return bool True if user is admin, false otherwise
 */
function isAdminUser(int $userId): bool
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT nivel FROM sistema_usuarios WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        $user = $stmt->fetch();

        return $user && ($user['nivel'] === 'Administrador' || $user['nivel'] === 'Corretor');
    } catch (PDOException $e) {
        logError("Error checking admin privileges: " . $e->getMessage());
        return false;
    }
}

// ===================================
// Property Admin Functions
// ===================================

/**
 * Get property list with pagination and filters
 * 
 * @param array $filters Filter options
 * @param int $page Current page number
 * @param int $perPage Items per page
 * @return array Contains 'properties', 'total' and 'totalPages'
 */
function getAdminProperties(array $filters = [], int $page = 1, int $perPage = 10): array
{
    global $databaseConnection;

    $result = [
        'properties' => [],
        'total' => 0,
        'totalPages' => 0
    ];

    try {
        $whereConditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['tipo'])) {
            $whereConditions[] = "i.para = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['categoria'])) {
            $whereConditions[] = "i.id_categoria = :categoria";
            $params[':categoria'] = $filters['categoria'];
        }

        if (!empty($filters['busca'])) {
            $whereConditions[] = "(i.titulo LIKE :busca OR i.codigo LIKE :busca OR i.ref LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        $where = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total FROM sistema_imoveis i " . $where;
        $countStmt = $databaseConnection->prepare($countSql);

        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $result['total'] = $countStmt->fetch()['total'];
        $result['totalPages'] = ceil($result['total'] / $perPage);

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get paginated records
        $sql = "SELECT i.*, c.categoria 
                FROM sistema_imoveis i
                LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
                $where
                ORDER BY i.data DESC, i.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $databaseConnection->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $result['properties'] = $stmt->fetchAll();

        return $result;
    } catch (PDOException $e) {
        logError("Error fetching admin properties: " . $e->getMessage());
        return $result;
    }
}

/**
 * Get property details for admin area
 * 
 * @param int $propertyId Property ID
 * @return array|null Property data or null if not found
 */
function getAdminPropertyById(int $propertyId): ?array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_imoveis WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $propertyId);
        $stmt->execute();

        $property = $stmt->fetch();

        if (!$property) {
            return null;
        }

        return $property;
    } catch (PDOException $e) {
        logError("Error fetching admin property: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new property
 * 
 * @param array $propertyData Property data
 * @return int|false New property ID or false on failure
 */
function createProperty(array $propertyData): int|false
{
    global $databaseConnection;

    try {
        // Check if property code already exists
        $stmt = $databaseConnection->prepare(
            "SELECT id FROM sistema_imoveis WHERE codigo = :codigo LIMIT 1"
        );
        $stmt->bindParam(':codigo', $propertyData['codigo']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false;
        }

        // Current date and admin user
        $data = date('Y-m-d');
        $hora = date('H:i:s');
        $id_usuario = $_SESSION['admin_id'];

        // Prepare keywords if not provided
        if (empty($propertyData['palavras_chaves'])) {
            $propertyData['palavras_chaves'] = $propertyData['titulo'] . ' ' . $propertyData['descricao'];
        }

        // Insert new property
        $sql = "INSERT INTO sistema_imoveis (
                    titulo, para, id_categoria, id_estado, id_cidade, id_bairro,
                    valor, quartos, suites, banheiros, salas, cozinhas, garagem, area_servico,
                    area_total, area_construida, und_medida, endereco, descricao, ref,
                    codigo, status, data, hora, id_usuario, palavras_chaves, destaque,
                    classificados, quadra_lote, medida_frente, medida_fundo, medida_laterais,
                    latitude, longitude, corretor_responsavel, nome_anunciante, telefone_anunciante
                ) VALUES (
                    :titulo, :para, :id_categoria, :id_estado, :id_cidade, :id_bairro,
                    :valor, :quartos, :suites, :banheiros, :salas, :cozinhas, :garagem, :area_servico,
                    :area_total, :area_construida, :und_medida, :endereco, :descricao, :ref,
                    :codigo, :status, :data, :hora, :id_usuario, :palavras_chaves, :destaque,
                    :classificados, :quadra_lote, :medida_frente, :medida_fundo, :medida_laterais,
                    :latitude, :longitude, :corretor_responsavel, :nome_anunciante, :telefone_anunciante
                )";

        $stmt = $databaseConnection->prepare($sql);

        // Bind all parameters
        $stmt->bindParam(':titulo', $propertyData['titulo']);
        $stmt->bindParam(':para', $propertyData['para']);
        $stmt->bindParam(':id_categoria', $propertyData['id_categoria']);
        $stmt->bindParam(':id_estado', $propertyData['id_estado']);
        $stmt->bindParam(':id_cidade', $propertyData['id_cidade']);
        $stmt->bindParam(':id_bairro', $propertyData['id_bairro']);
        $stmt->bindParam(':valor', $propertyData['valor']);
        $stmt->bindParam(':quartos', $propertyData['quartos']);
        $stmt->bindParam(':suites', $propertyData['suites']);
        $stmt->bindParam(':banheiros', $propertyData['banheiros']);
        $stmt->bindParam(':salas', $propertyData['salas']);
        $stmt->bindParam(':cozinhas', $propertyData['cozinhas']);
        $stmt->bindParam(':garagem', $propertyData['garagem']);
        $stmt->bindParam(':area_servico', $propertyData['area_servico']);
        $stmt->bindParam(':area_total', $propertyData['area_total']);
        $stmt->bindParam(':area_construida', $propertyData['area_construida']);
        $stmt->bindParam(':und_medida', $propertyData['und_medida']);
        $stmt->bindParam(':endereco', $propertyData['endereco']);
        $stmt->bindParam(':descricao', $propertyData['descricao']);
        $stmt->bindParam(':ref', $propertyData['ref']);
        $stmt->bindParam(':codigo', $propertyData['codigo']);
        $stmt->bindParam(':status', $propertyData['status']);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->bindParam(':palavras_chaves', $propertyData['palavras_chaves']);
        $stmt->bindParam(':destaque', $propertyData['destaque']);
        $stmt->bindParam(':classificados', $propertyData['classificados']);
        $stmt->bindParam(':quadra_lote', $propertyData['quadra_lote']);
        $stmt->bindParam(':medida_frente', $propertyData['medida_frente']);
        $stmt->bindParam(':medida_fundo', $propertyData['medida_fundo']);
        $stmt->bindParam(':medida_laterais', $propertyData['medida_laterais']);
        $stmt->bindParam(':latitude', $propertyData['latitude']);
        $stmt->bindParam(':longitude', $propertyData['longitude']);
        $stmt->bindParam(':corretor_responsavel', $propertyData['corretor_responsavel']);
        $stmt->bindParam(':nome_anunciante', $propertyData['nome_anunciante']);
        $stmt->bindParam(':telefone_anunciante', $propertyData['telefone_anunciante']);

        $stmt->execute();

        return $databaseConnection->lastInsertId();
    } catch (PDOException $e) {
        logError("Error creating property: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing property
 * 
 * @param int $propertyId Property ID
 * @param array $propertyData Updated property data
 * @return bool Success or failure
 */
function updateProperty(int $propertyId, array $propertyData): bool
{
    global $databaseConnection;

    try {
        // Check if property code already exists (except for this property)
        $stmt = $databaseConnection->prepare(
            "SELECT id FROM sistema_imoveis WHERE codigo = :codigo AND id != :id LIMIT 1"
        );
        $stmt->bindParam(':codigo', $propertyData['codigo']);
        $stmt->bindParam(':id', $propertyId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false;
        }

        // Prepare keywords if not provided
        if (empty($propertyData['palavras_chaves'])) {
            $propertyData['palavras_chaves'] = $propertyData['titulo'] . ' ' . $propertyData['descricao'];
        }

        // Update property
        $sql = "UPDATE sistema_imoveis SET
                    titulo = :titulo,
                    para = :para,
                    id_categoria = :id_categoria,
                    id_estado = :id_estado,
                    id_cidade = :id_cidade,
                    id_bairro = :id_bairro,
                    valor = :valor,
                    quartos = :quartos,
                    suites = :suites,
                    banheiros = :banheiros,
                    salas = :salas,
                    cozinhas = :cozinhas,
                    garagem = :garagem,
                    area_servico = :area_servico,
                    area_total = :area_total,
                    area_construida = :area_construida,
                    und_medida = :und_medida,
                    endereco = :endereco,
                    descricao = :descricao,
                    ref = :ref,
                    codigo = :codigo,
                    status = :status,
                    destaque = :destaque,
                    classificados = :classificados,
                    quadra_lote = :quadra_lote,
                    medida_frente = :medida_frente,
                    medida_fundo = :medida_fundo,
                    medida_laterais = :medida_laterais,
                    latitude = :latitude,
                    longitude = :longitude,
                    corretor_responsavel = :corretor_responsavel,
                    nome_anunciante = :nome_anunciante,
                    telefone_anunciante = :telefone_anunciante,
                    palavras_chaves = :palavras_chaves
                WHERE id = :id";

        $stmt = $databaseConnection->prepare($sql);

        // Bind all parameters
        $stmt->bindParam(':titulo', $propertyData['titulo']);
        $stmt->bindParam(':para', $propertyData['para']);
        $stmt->bindParam(':id_categoria', $propertyData['id_categoria']);
        $stmt->bindParam(':id_estado', $propertyData['id_estado']);
        $stmt->bindParam(':id_cidade', $propertyData['id_cidade']);
        $stmt->bindParam(':id_bairro', $propertyData['id_bairro']);
        $stmt->bindParam(':valor', $propertyData['valor']);
        $stmt->bindParam(':quartos', $propertyData['quartos']);
        $stmt->bindParam(':suites', $propertyData['suites']);
        $stmt->bindParam(':banheiros', $propertyData['banheiros']);
        $stmt->bindParam(':salas', $propertyData['salas']);
        $stmt->bindParam(':cozinhas', $propertyData['cozinhas']);
        $stmt->bindParam(':garagem', $propertyData['garagem']);
        $stmt->bindParam(':area_servico', $propertyData['area_servico']);
        $stmt->bindParam(':area_total', $propertyData['area_total']);
        $stmt->bindParam(':area_construida', $propertyData['area_construida']);
        $stmt->bindParam(':und_medida', $propertyData['und_medida']);
        $stmt->bindParam(':endereco', $propertyData['endereco']);
        $stmt->bindParam(':descricao', $propertyData['descricao']);
        $stmt->bindParam(':ref', $propertyData['ref']);
        $stmt->bindParam(':codigo', $propertyData['codigo']);
        $stmt->bindParam(':status', $propertyData['status']);
        $stmt->bindParam(':destaque', $propertyData['destaque']);
        $stmt->bindParam(':classificados', $propertyData['classificados']);
        $stmt->bindParam(':quadra_lote', $propertyData['quadra_lote']);
        $stmt->bindParam(':medida_frente', $propertyData['medida_frente']);
        $stmt->bindParam(':medida_fundo', $propertyData['medida_fundo']);
        $stmt->bindParam(':medida_laterais', $propertyData['medida_laterais']);
        $stmt->bindParam(':latitude', $propertyData['latitude']);
        $stmt->bindParam(':longitude', $propertyData['longitude']);
        $stmt->bindParam(':corretor_responsavel', $propertyData['corretor_responsavel']);
        $stmt->bindParam(':nome_anunciante', $propertyData['nome_anunciante']);
        $stmt->bindParam(':telefone_anunciante', $propertyData['telefone_anunciante']);
        $stmt->bindParam(':palavras_chaves', $propertyData['palavras_chaves']);
        $stmt->bindParam(':id', $propertyId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error updating property: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a property and its images
 * 
 * @param int $propertyId Property ID
 * @return bool Success or failure
 */
function deleteProperty(int $propertyId): bool
{
    global $databaseConnection;

    try {
        // Get property code before deleting
        $stmt = $databaseConnection->prepare("SELECT codigo FROM sistema_imoveis WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $propertyId);
        $stmt->execute();

        $property = $stmt->fetch();

        if (!$property) {
            return false;
        }

        $propertyCode = $property['codigo'];

        // Delete property from database
        $stmt = $databaseConnection->prepare("DELETE FROM sistema_imoveis WHERE id = :id");
        $stmt->bindParam(':id', $propertyId);
        $result = $stmt->execute();

        // Clean up images if property was deleted
        if ($result) {
            $uploadDir = __DIR__ . '/../uploads/imoveis/';

            // Delete all images with property code prefix
            for ($i = 1; $i <= 12; $i++) {
                $imageNumber = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, etc.
                $fileName = $propertyCode . $imageNumber . '.jpg';
                $filePath = $uploadDir . $fileName;

                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        return $result;
    } catch (PDOException $e) {
        logError("Error deleting property: " . $e->getMessage());
        return false;
    }
}

// ===================================
// Category Admin Functions
// ===================================

/**
 * Get all property categories
 * 
 * @return array List of all categories
 */
function getAdminCategories(): array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->query(
            "SELECT * FROM sistema_imoveis_categorias ORDER BY categoria ASC"
        );

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching admin categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get category by ID
 * 
 * @param int $categoryId Category ID
 * @return array|null Category data or null if not found
 */
function getAdminCategoryById(int $categoryId): ?array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_imoveis_categorias WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $categoryId);
        $stmt->execute();

        $category = $stmt->fetch();

        if (!$category) {
            return null;
        }

        return $category;
    } catch (PDOException $e) {
        logError("Error fetching admin category: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new category
 * 
 * @param string $tipo Category type
 * @param string $categoria Category name
 * @return int|false New category ID or false on failure
 */
function createCategory(string $tipo, string $categoria): int|false
{
    global $databaseConnection;

    try {
        // Check if category already exists
        $stmt = $databaseConnection->prepare(
            "SELECT id FROM sistema_imoveis_categorias 
             WHERE LOWER(categoria) = LOWER(:categoria) LIMIT 1"
        );
        $stmt->bindParam(':categoria', $categoria);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false;
        }

        // Insert new category
        $stmt = $databaseConnection->prepare(
            "INSERT INTO sistema_imoveis_categorias (tipo, categoria) 
             VALUES (:tipo, :categoria)"
        );
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->execute();

        return $databaseConnection->lastInsertId();
    } catch (PDOException $e) {
        logError("Error creating category: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing category
 * 
 * @param int $categoryId Category ID
 * @param string $tipo Category type
 * @param string $categoria Category name
 * @return bool Success or failure
 */
function updateCategory(int $categoryId, string $tipo, string $categoria): bool
{
    global $databaseConnection;

    try {
        // Check if category name already exists (excluding current category)
        $stmt = $databaseConnection->prepare(
            "SELECT id FROM sistema_imoveis_categorias 
             WHERE LOWER(categoria) = LOWER(:categoria) AND id != :id LIMIT 1"
        );
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':id', $categoryId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false;
        }

        // Update category
        $stmt = $databaseConnection->prepare(
            "UPDATE sistema_imoveis_categorias 
             SET tipo = :tipo, categoria = :categoria
             WHERE id = :id"
        );
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':id', $categoryId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error updating category: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a category if it's not in use
 * 
 * @param int $categoryId Category ID
 * @return bool Success or failure
 */
function deleteCategory(int $categoryId): bool
{
    global $databaseConnection;

    try {
        // Check if category is in use
        $stmt = $databaseConnection->prepare(
            "SELECT COUNT(*) as total FROM sistema_imoveis WHERE id_categoria = :id_categoria"
        );
        $stmt->bindParam(':id_categoria', $categoryId);
        $stmt->execute();

        $count = $stmt->fetch()['total'];

        if ($count > 0) {
            return false;
        }

        // Delete category
        $stmt = $databaseConnection->prepare(
            "DELETE FROM sistema_imoveis_categorias WHERE id = :id"
        );
        $stmt->bindParam(':id', $categoryId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error deleting category: " . $e->getMessage());
        return false;
    }
}

// ===================================
// Client Admin Functions
// ===================================

/**
 * Get clients with pagination and filters
 * 
 * @param array $filters Filter options
 * @param int $page Current page number
 * @param int $perPage Items per page
 * @return array Contains 'clients', 'total' and 'totalPages'
 */
function getAdminClients(array $filters = [], int $page = 1, int $perPage = 10): array
{
    global $databaseConnection;

    $result = [
        'clients' => [],
        'total' => 0,
        'totalPages' => 0
    ];

    try {
        $whereConditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['tipo'])) {
            $whereConditions[] = "c.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['cidade'])) {
            $whereConditions[] = "c.id_cidade = :cidade";
            $params[':cidade'] = $filters['cidade'];
        }

        if (!empty($filters['bairro'])) {
            $whereConditions[] = "c.id_bairro = :bairro";
            $params[':bairro'] = $filters['bairro'];
        }

        if (!empty($filters['busca'])) {
            $whereConditions[] = "(c.nome_completo LIKE :busca OR c.razao_social LIKE :busca OR c.email LIKE :busca OR c.telefone1 LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        $where = !empty($whereConditions) ? "AND " . implode(" AND ", $whereConditions) : "";

        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total FROM sistema_clientes c WHERE 1=1 " . $where;
        $countStmt = $databaseConnection->prepare($countSql);

        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $result['total'] = $countStmt->fetch()['total'];
        $result['totalPages'] = ceil($result['total'] / $perPage);

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get paginated records
        $sql = "SELECT c.*, 
                    e.nome as estado, 
                    e.uf as uf,
                    cid.nome as cidade, 
                    b.bairro as bairro
                FROM sistema_clientes c
                LEFT JOIN sistema_estados e ON c.id_estado = e.id
                LEFT JOIN sistema_cidades cid ON c.id_cidade = cid.id
                LEFT JOIN sistema_bairros b ON c.id_bairro = b.id
                WHERE 1=1 " . $where . " 
                ORDER BY c.data_cadastro DESC, c.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $databaseConnection->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $result['clients'] = $stmt->fetchAll();

        return $result;
    } catch (PDOException $e) {
        logError("Error fetching admin clients: " . $e->getMessage());
        return $result;
    }
}

/**
 * Get client by ID
 * 
 * @param int $clientId Client ID
 * @return array|null Client data or null if not found
 */
function getAdminClientById(int $clientId): ?array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT c.*, 
                    e.nome as estado_nome, 
                    e.uf as estado_uf,
                    cid.nome as cidade_nome, 
                    b.bairro as bairro_nome
             FROM sistema_clientes c
             LEFT JOIN sistema_estados e ON c.id_estado = e.id
             LEFT JOIN sistema_cidades cid ON c.id_cidade = cid.id
             LEFT JOIN sistema_bairros b ON c.id_bairro = b.id
             WHERE c.id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $clientId);
        $stmt->execute();

        $client = $stmt->fetch();

        if (!$client) {
            return null;
        }

        return $client;
    } catch (PDOException $e) {
        logError("Error fetching admin client: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new client
 * 
 * @param array $clientData Client data
 * @return int|false New client ID or false on failure
 */
function createClient(array $clientData): int|false
{
    global $databaseConnection;

    try {
        // Current date and admin user
        $data_cadastro = date('Y-m-d');
        $hora_cadastro = date('H:i:s');
        $id_usuario = $_SESSION['admin_id'];

        // Insert new client
        $sql = "INSERT INTO sistema_clientes (
                    tipo, nome_completo, razao_social, cpf, cnpj, rg, data_nascimento, profissao,
                    telefone1, telefone2, email, endereco, id_estado, id_cidade, id_bairro,
                    data_cadastro, hora_cadastro, id_usuario, observacoes, categoria, principal
                ) VALUES (
                    :tipo, :nome_completo, :razao_social, :cpf, :cnpj, :rg, :data_nascimento, :profissao,
                    :telefone1, :telefone2, :email, :endereco, :id_estado, :id_cidade, :id_bairro,
                    :data_cadastro, :hora_cadastro, :id_usuario, :observacoes, :categoria, :principal
                )";

        $stmt = $databaseConnection->prepare($sql);

        // Bind all parameters
        $stmt->bindParam(':tipo', $clientData['tipo']);
        $stmt->bindParam(':nome_completo', $clientData['nome_completo']);
        $stmt->bindParam(':razao_social', $clientData['razao_social']);
        $stmt->bindParam(':cpf', $clientData['cpf']);
        $stmt->bindParam(':cnpj', $clientData['cnpj']);
        $stmt->bindParam(':rg', $clientData['rg']);
        $stmt->bindParam(':data_nascimento', $clientData['data_nascimento'] ? $clientData['data_nascimento'] : null);
        $stmt->bindParam(':profissao', $clientData['profissao']);
        $stmt->bindParam(':telefone1', $clientData['telefone1']);
        $stmt->bindParam(':telefone2', $clientData['telefone2']);
        $stmt->bindParam(':email', $clientData['email']);
        $stmt->bindParam(':endereco', $clientData['endereco']);
        $stmt->bindParam(':id_estado', $clientData['id_estado'] ? $clientData['id_estado'] : null, PDO::PARAM_INT);
        $stmt->bindParam(':id_cidade', $clientData['id_cidade'] ? $clientData['id_cidade'] : null, PDO::PARAM_INT);
        $stmt->bindParam(':id_bairro', $clientData['id_bairro'] ? $clientData['id_bairro'] : null, PDO::PARAM_INT);
        $stmt->bindParam(':data_cadastro', $data_cadastro);
        $stmt->bindParam(':hora_cadastro', $hora_cadastro);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->bindParam(':observacoes', $clientData['observacoes']);
        $stmt->bindParam(':categoria', $clientData['categoria']);
        $stmt->bindParam(':principal', $clientData['principal']);

        $stmt->execute();

        return $databaseConnection->lastInsertId();
    } catch (PDOException $e) {
        logError("Error creating client: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing client
 * 
 * @param int $clientId Client ID
 * @param array $clientData Updated client data
 * @return bool Success or failure
 */
function updateClient(int $clientId, array $clientData): bool
{
    global $databaseConnection;

    try {
        // Update client
        $sql = "UPDATE sistema_clientes SET
                    tipo = :tipo,
                    nome_completo = :nome_completo,
                    razao_social = :razao_social,
                    cpf = :cpf,
                    cnpj = :cnpj,
                    rg = :rg,
                    data_nascimento = :data_nascimento,
                    profissao = :profissao,
                    telefone1 = :telefone1,
                    telefone2 = :telefone2,
                    email = :email,
                    endereco = :endereco,
                    id_estado = :id_estado,
                    id_cidade = :id_cidade,
                    id_bairro = :id_bairro,
                    observacoes = :observacoes,
                    categoria = :categoria,
                    principal = :principal
                WHERE id = :id";

        $stmt = $databaseConnection->prepare($sql);

        // Bind all parameters
        $stmt->bindParam(':tipo', $clientData['tipo']);
        $stmt->bindParam(':nome_completo', $clientData['nome_completo']);
        $stmt->bindParam(':razao_social', $clientData['razao_social']);
        $stmt->bindParam(':cpf', $clientData['cpf']);
        $stmt->bindParam(':cnpj', $clientData['cnpj']);
        $stmt->bindParam(':rg', $clientData['rg']);
        $stmt->bindParam(':data_nascimento', $clientData['data_nascimento'] ? $clientData['data_nascimento'] : null);
        $stmt->bindParam(':profissao', $clientData['profissao']);
        $stmt->bindParam(':telefone1', $clientData['telefone1']);
        $stmt->bindParam(':telefone2', $clientData['telefone2']);
        $stmt->bindParam(':email', $clientData['email']);
        $stmt->bindParam(':endereco', $clientData['endereco']);
        $stmt->bindParam(':id_estado', $clientData['id_estado'] ? $clientData['id_estado'] : null, PDO::PARAM_INT);
        $stmt->bindParam(':id_cidade', $clientData['id_cidade'] ? $clientData['id_cidade'] : null, PDO::PARAM_INT);
        $stmt->bindParam(':id_bairro', $clientData['id_bairro'] ? $clientData['id_bairro'] : null, PDO::PARAM_INT);
        $stmt->bindParam(':observacoes', $clientData['observacoes']);
        $stmt->bindParam(':categoria', $clientData['categoria']);
        $stmt->bindParam(':principal', $clientData['principal']);
        $stmt->bindParam(':id', $clientId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error updating client: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a client
 * 
 * @param int $clientId Client ID
 * @return bool Success or failure
 */
function deleteClient(int $clientId): bool
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare("DELETE FROM sistema_clientes WHERE id = :id");
        $stmt->bindParam(':id', $clientId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error deleting client: " . $e->getMessage());
        return false;
    }
}

// ===================================
// Location Functions
// ===================================

/**
 * Get all states
 * 
 * @return array List of all states
 */
function getStates(): array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->query("SELECT * FROM sistema_estados ORDER BY nome ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching states: " . $e->getMessage());
        return [];
    }
}

/**
 * Get cities by state ID
 * 
 * @param int $stateId State ID
 * @return array List of cities
 */
function getCitiesByState(int $stateId): array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_cidades WHERE id_estado = :id_estado ORDER BY nome ASC"
        );
        $stmt->bindParam(':id_estado', $stateId);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching cities: " . $e->getMessage());
        return [];
    }
}

/**
 * Get neighborhoods by city ID
 * 
 * @param int $cityId City ID
 * @return array List of neighborhoods
 */
function getNeighborhoodsByCity(int $cityId): array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_bairros WHERE id_cidade = :id_cidade ORDER BY bairro ASC"
        );
        $stmt->bindParam(':id_cidade', $cityId);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching neighborhoods: " . $e->getMessage());
        return [];
    }
}

// ===================================
// Service Request Admin Functions
// ===================================

// Add these functions to the admin_functions.php file

// ===================================
// Service Request Admin Functions
// ===================================

/**
 * Get service requests with pagination and filters
 * 
 * @param array $filters Filter options
 * @param int $page Current page number
 * @param int $perPage Items per page
 * @return array Contains 'requests', 'total' and 'totalPages'
 */
function getServiceRequests(array $filters = [], int $page = 1, int $perPage = 10): array
{
    global $databaseConnection;

    $result = [
        'requests' => [],
        'total' => 0,
        'totalPages' => 0
    ];

    try {
        $whereConditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $whereConditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['local'])) {
            $whereConditions[] = "local = :local";
            $params[':local'] = $filters['local'];
        }

        if (!empty($filters['busca'])) {
            $whereConditions[] = "(nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca OR mensagem LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        $where = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total FROM sistema_interacao " . $where;
        $countStmt = $databaseConnection->prepare($countSql);

        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $result['total'] = $countStmt->fetch()['total'];
        $result['totalPages'] = ceil($result['total'] / $perPage);

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get paginated records
        $sql = "SELECT * FROM sistema_interacao
                $where
                ORDER BY data DESC, hora DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $databaseConnection->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $result['requests'] = $stmt->fetchAll();

        return $result;
    } catch (PDOException $e) {
        logError("Error fetching service requests: " . $e->getMessage());
        return $result;
    }
}

/**
 * Get service request by ID
 * 
 * @param int $requestId Request ID
 * @return array|null Request data or null if not found
 */
function getServiceRequestById(int $requestId): ?array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_interacao WHERE id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $requestId);
        $stmt->execute();

        $request = $stmt->fetch();

        if (!$request) {
            return null;
        }

        return $request;
    } catch (PDOException $e) {
        logError("Error fetching service request: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new service request
 * 
 * @param array $requestData Request data
 * @return int|false New request ID or false on failure
 */
function createServiceRequest(array $requestData): int|false
{
    global $databaseConnection;

    try {
        // Get current date and time
        $data = date('Y-m-d');
        $hora = date('H:i:s');

        // Insert new request
        $stmt = $databaseConnection->prepare(
            "INSERT INTO sistema_interacao (
                nome, email, telefone, mensagem,
                data, hora, local, status
            ) VALUES (
                :nome, :email, :telefone, :mensagem,
                :data, :hora, :local, :status
            )"
        );

        $stmt->bindParam(':nome', $requestData['nome']);
        $stmt->bindParam(':email', $requestData['email']);
        $stmt->bindParam(':telefone', $requestData['telefone']);
        $stmt->bindParam(':mensagem', $requestData['mensagem']);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':local', $requestData['local']);
        $stmt->bindParam(':status', $requestData['status']);

        $stmt->execute();

        return $databaseConnection->lastInsertId();
    } catch (PDOException $e) {
        logError("Error creating service request: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing service request
 * 
 * @param int $requestId Request ID
 * @param array $requestData Updated request data
 * @return bool Success or failure
 */
function updateServiceRequest(int $requestId, array $requestData): bool
{
    global $databaseConnection;

    try {
        // Update request
        $stmt = $databaseConnection->prepare(
            "UPDATE sistema_interacao SET
                nome = :nome,
                email = :email,
                telefone = :telefone,
                mensagem = :mensagem,
                local = :local,
                status = :status
            WHERE id = :id"
        );

        $stmt->bindParam(':nome', $requestData['nome']);
        $stmt->bindParam(':email', $requestData['email']);
        $stmt->bindParam(':telefone', $requestData['telefone']);
        $stmt->bindParam(':mensagem', $requestData['mensagem']);
        $stmt->bindParam(':local', $requestData['local']);
        $stmt->bindParam(':status', $requestData['status']);
        $stmt->bindParam(':id', $requestId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error updating service request: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a service request
 * 
 * @param int $requestId Request ID
 * @return bool Success or failure
 */
function deleteServiceRequest(int $requestId): bool
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare("DELETE FROM sistema_interacao WHERE id = :id");
        $stmt->bindParam(':id', $requestId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error deleting service request: " . $e->getMessage());
        return false;
    }
}
// ===================================
// Dashboard Functions
// ===================================

/**
 * Get dashboard statistics
 * 
 * @return array Statistics for dashboard
 */
function getDashboardStats(): array
{
    global $databaseConnection;

    $stats = [
        'totalImoveis' => 0,
        'imoveisVenda' => 0,
        'imoveisAluguel' => 0,
        'totalCategorias' => 0,
        'totalClientes' => 0,
        'ultimosImoveis' => [],
        'ultimosLembretes' => [],
        'ultimosAtendimentos' => []
    ];

    try {
        // Count total properties
        $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_imoveis WHERE status = 'ativo'");
        $stats['totalImoveis'] = $stmt->fetch()['total'];

        // Count properties by type (venda/aluguel)
        $stmt = $databaseConnection->query(
            "SELECT para, COUNT(*) as total FROM sistema_imoveis 
             WHERE status = 'ativo' 
             GROUP BY para"
        );
        $imoveisPorTipo = $stmt->fetchAll();

        foreach ($imoveisPorTipo as $tipo) {
            if ($tipo['para'] === 'venda') {
                $stats['imoveisVenda'] = $tipo['total'];
            } elseif ($tipo['para'] === 'aluguel') {
                $stats['imoveisAluguel'] = $tipo['total'];
            }
        }

        // Count total categories
        $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_imoveis_categorias");
        $stats['totalCategorias'] = $stmt->fetch()['total'];

        // Count total clients
        $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_clientes");
        $stats['totalClientes'] = $stmt->fetch()['total'];

        // Get latest properties
        $stmt = $databaseConnection->query(
            "SELECT i.id, i.titulo, i.para, i.valor, i.data, c.categoria 
             FROM sistema_imoveis i
             LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
             WHERE i.status = 'ativo'
             ORDER BY i.data DESC, i.hora DESC
             LIMIT 5"
        );
        $stats['ultimosImoveis'] = $stmt->fetchAll();

        // Get latest calendar events
        $stmt = $databaseConnection->query(
            "SELECT * FROM sistema_avisos 
             WHERE status = 'Pendente' 
             ORDER BY data_inicio ASC
             LIMIT 5"
        );
        $stats['ultimosLembretes'] = $stmt->fetchAll();

        // Get latest service requests
        $stmt = $databaseConnection->query(
            "SELECT * FROM sistema_interacao
             WHERE status = 'Pendente' 
             ORDER BY data DESC, hora DESC
             LIMIT 5"
        );
        $stats['ultimosAtendimentos'] = $stmt->fetchAll();

        return $stats;
    } catch (PDOException $e) {
        logError("Dashboard stats error: " . $e->getMessage());
        return $stats;
    }
}

// ===================================
// Calendar Functions
// ===================================

/**
 * Get calendar events for a specific month
 * 
 * @param int $month Month number (1-12)
 * @param int $year Year (e.g. 2025)
 * @return array List of events for the month
 */
function getMonthEvents(int $month, int $year): array
{
    global $databaseConnection;

    try {
        $startDate = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
        $endDate = date('Y-m-d', mktime(0, 0, 0, $month + 1, 0, $year));

        $stmt = $databaseConnection->prepare(
            "SELECT * FROM sistema_avisos 
             WHERE (data_inicio BETWEEN :startDate AND :endDate) 
             OR (data_fim BETWEEN :startDate AND :endDate)
             OR (data_inicio <= :startDate AND data_fim >= :endDate)
             ORDER BY data_inicio ASC"
        );
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching month events: " . $e->getMessage());
        return [];
    }
}

/**
 * Get calendar event by ID
 * 
 * @param int $eventId Event ID
 * @return array|null Event data or null if not found
 */
function getCalendarEventById(int $eventId): ?array
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare(
            "SELECT a.*, u.nome as criador_nome 
             FROM sistema_avisos a
             LEFT JOIN sistema_usuarios u ON a.id_usuario = u.id
             WHERE a.id = :id LIMIT 1"
        );
        $stmt->bindParam(':id', $eventId);
        $stmt->execute();

        $event = $stmt->fetch();

        if (!$event) {
            return null;
        }

        return $event;
    } catch (PDOException $e) {
        logError("Error fetching calendar event: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new calendar event
 * 
 * @param array $eventData Event data
 * @return int|false New event ID or false on failure
 */
function createCalendarEvent(array $eventData): int|false
{
    global $databaseConnection;

    try {
        // Format dates and times for database
        $data_inicio_completa = $eventData['data_inicio'] . ' ' . $eventData['hora_inicio'] . ':00';
        $data_fim_completa = $eventData['data_fim'] . ' ' . $eventData['hora_fim'] . ':00';

        // Insert new event
        $stmt = $databaseConnection->prepare(
            "INSERT INTO sistema_avisos (
                id_usuario, para, prioridade, titulo, descricao, 
                data_inicio, data_fim, status
            ) VALUES (
                :id_usuario, :para, :prioridade, :titulo, :descricao, 
                :data_inicio, :data_fim, :status
            )"
        );

        $stmt->bindParam(':id_usuario', $_SESSION['admin_id']);
        $stmt->bindParam(':para', $eventData['para']);
        $stmt->bindParam(':prioridade', $eventData['prioridade']);
        $stmt->bindParam(':titulo', $eventData['titulo']);
        $stmt->bindParam(':descricao', $eventData['descricao']);
        $stmt->bindParam(':data_inicio', $data_inicio_completa);
        $stmt->bindParam(':data_fim', $data_fim_completa);
        $stmt->bindParam(':status', $eventData['status']);

        $stmt->execute();

        return $databaseConnection->lastInsertId();
    } catch (PDOException $e) {
        logError("Error creating calendar event: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing calendar event
 * 
 * @param int $eventId Event ID
 * @param array $eventData Updated event data
 * @return bool Success or failure
 */
function updateCalendarEvent(int $eventId, array $eventData): bool
{
    global $databaseConnection;

    try {
        // Format dates and times for database
        $data_inicio_completa = $eventData['data_inicio'] . ' ' . $eventData['hora_inicio'] . ':00';
        $data_fim_completa = $eventData['data_fim'] . ' ' . $eventData['hora_fim'] . ':00';

        // Update event
        $stmt = $databaseConnection->prepare(
            "UPDATE sistema_avisos SET
                para = :para,
                prioridade = :prioridade,
                titulo = :titulo,
                descricao = :descricao,
                data_inicio = :data_inicio,
                data_fim = :data_fim,
                status = :status
            WHERE id = :id"
        );

        $stmt->bindParam(':para', $eventData['para']);
        $stmt->bindParam(':prioridade', $eventData['prioridade']);
        $stmt->bindParam(':titulo', $eventData['titulo']);
        $stmt->bindParam(':descricao', $eventData['descricao']);
        $stmt->bindParam(':data_inicio', $data_inicio_completa);
        $stmt->bindParam(':data_fim', $data_fim_completa);
        $stmt->bindParam(':status', $eventData['status']);
        $stmt->bindParam(':id', $eventId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error updating calendar event: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a calendar event
 * 
 * @param int $eventId Event ID
 * @return bool Success or failure
 */
function deleteCalendarEvent(int $eventId): bool
{
    global $databaseConnection;

    try {
        $stmt = $databaseConnection->prepare("DELETE FROM sistema_avisos WHERE id = :id");
        $stmt->bindParam(':id', $eventId);

        return $stmt->execute();
    } catch (PDOException $e) {
        logError("Error deleting calendar event: " . $e->getMessage());
        return false;
    }
}


