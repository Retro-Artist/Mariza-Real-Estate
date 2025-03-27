<?php
// ===================================
// Utility Functions
// ===================================

// Format currency values
function formatCurrency(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Format date 
function formatDate($dateString, $formatPattern = 'd/m/Y'): string {
    return date($formatPattern, strtotime($dateString));
}

// Truncate text to specified length
function truncateText($fullText, $maxLength = 150): string {
    if (strlen($fullText) <= $maxLength) {
        return $fullText;
    }
    
    return substr($fullText, 0, $maxLength) . '...';
}

// Get page title
function getPageTitle($pageTitle = ''): string {
    if (empty($pageTitle)) {
        return SITE_NAME;
    }
    
    return $pageTitle . ' | ' . SITE_NAME;
}

// Logging errors
function logError($errorMessage, $errorLevel = 'ERROR'): void {
    // Ensure logs directory exists
    $logsDir = __DIR__ . '/../config/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    $logFilePath = $logsDir . '/errors.log';
    $currentTimestamp = date('Y-m-d H:i:s');
    $formattedLogMessage = "[$currentTimestamp] [$errorLevel] $errorMessage" . PHP_EOL;
    
    error_log($formattedLogMessage, 3, $logFilePath);
}

// ===================================
// Property Functions
// ===================================

function getFeaturedProperties($limit = 6): array {
    global $databaseConnection;
    
    try {
        // Adicionei um log para debug
        logError("Buscando imóveis em destaque com limite: " . $limit);
        
        $statement = $databaseConnection->prepare(
            "SELECT i.*, c.categoria, b.bairro, cid.nome as cidade, e.uf 
            FROM sistema_imoveis i
            LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
            LEFT JOIN sistema_bairros b ON i.id_bairro = b.id
            LEFT JOIN sistema_cidades cid ON i.id_cidade = cid.id
            LEFT JOIN sistema_estados e ON i.id_estado = e.id
            WHERE i.destaque = 1 AND i.status = 'ativo'
            ORDER BY i.data DESC
            LIMIT :limit"
        );
        
        $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        
        $result = $statement->fetchAll();
        
        // Log para debug - quantos imóveis foram encontrados
        logError("Encontrados " . count($result) . " imóveis em destaque");
        
        return $result;
    } catch (PDOException $e) {
        logError("Error fetching featured properties: " . $e->getMessage());
        return [];
    }
}

// Get all property categories
function getAllCategories(): array {
    global $databaseConnection;
    
    try {
        $statement = $databaseConnection->query(
            "SELECT * FROM sistema_imoveis_categorias ORDER BY categoria ASC"
        );
        
        return $statement->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

// Get all cities
function getAllCities(): array {
    global $databaseConnection;
    
    try {
        $statement = $databaseConnection->query(
            "SELECT c.*, e.uf 
            FROM sistema_cidades c 
            LEFT JOIN sistema_estados e ON c.id_estado = e.id 
            ORDER BY c.nome ASC"
        );
        
        return $statement->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching cities: " . $e->getMessage());
        return [];
    }
}

// Get property details by ID
function getPropertyById(int $propertyId): ?array {
    global $databaseConnection;
    
    try {
        $statement = $databaseConnection->prepare(
            "SELECT i.*, c.categoria, b.bairro, cid.nome as cidade, e.uf 
            FROM sistema_imoveis i
            LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
            LEFT JOIN sistema_bairros b ON i.id_bairro = b.id
            LEFT JOIN sistema_cidades cid ON i.id_cidade = cid.id
            LEFT JOIN sistema_estados e ON i.id_estado = e.id
            WHERE i.id = :id"
        );
        
        $statement->bindParam(':id', $propertyId, PDO::PARAM_INT);
        $statement->execute();
        
        $result = $statement->fetch();
        
        if (!$result) {
            logError("Property not found: ID $propertyId");
            return null;
        }
        
        return $result;
    } catch (PDOException $e) {
        logError("Error fetching property details: " . $e->getMessage());
        return null;
    }
}

// Obter todos os bairros
function getAllBairros(): array {
    global $databaseConnection;
    
    try {
        $statement = $databaseConnection->query(
            "SELECT b.*, c.nome as cidade, e.uf 
            FROM sistema_bairros b 
            LEFT JOIN sistema_cidades c ON b.id_cidade = c.id 
            LEFT JOIN sistema_estados e ON b.id_estado = e.id 
            ORDER BY b.bairro ASC"
        );
        
        return $statement->fetchAll();
    } catch (PDOException $e) {
        logError("Error fetching bairros: " . $e->getMessage());
        return [];
    }
}

// Buscar imóveis com filtros
function searchImoveis(array $filters = []): array {
    global $databaseConnection;
    
    try {
        $conditions = [];
        $params = [];
        
        // Base da consulta SQL
        $sql = "SELECT i.*, c.categoria, b.bairro, cid.nome as cidade, e.uf 
                FROM sistema_imoveis i
                LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
                LEFT JOIN sistema_bairros b ON i.id_bairro = b.id
                LEFT JOIN sistema_cidades cid ON i.id_cidade = cid.id
                LEFT JOIN sistema_estados e ON i.id_estado = e.id
                WHERE i.status = 'ativo'";
        
        // Tipo (venda/aluguel)
        if (!empty($filters['tipo'])) {
            $conditions[] = "i.para = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        
        // Categoria
        if (!empty($filters['categoria'])) {
            $conditions[] = "i.id_categoria = :categoria";
            $params[':categoria'] = $filters['categoria'];
        }
        
        // Cidade
        if (!empty($filters['cidade'])) {
            $conditions[] = "i.id_cidade = :cidade";
            $params[':cidade'] = $filters['cidade'];
        }
        
        // Bairro
        if (!empty($filters['bairro'])) {
            $conditions[] = "i.id_bairro = :bairro";
            $params[':bairro'] = $filters['bairro'];
        }
        
        // Quartos
        if (!empty($filters['quartos'])) {
            $conditions[] = "i.quartos >= :quartos";
            $params[':quartos'] = $filters['quartos'];
        }
        
        // Suítes
        if (!empty($filters['suites'])) {
            $conditions[] = "i.suites >= :suites";
            $params[':suites'] = $filters['suites'];
        }
        
        // Banheiros
        if (!empty($filters['banheiros'])) {
            $conditions[] = "i.banheiros >= :banheiros";
            $params[':banheiros'] = $filters['banheiros'];
        }
        
        // Vagas de Garagem
        if (!empty($filters['garagem'])) {
            $conditions[] = "i.garagem >= :garagem";
            $params[':garagem'] = $filters['garagem'];
        }
        
        // Valor máximo
        if (!empty($filters['valor'])) {
            $conditions[] = "i.valor <= :valor";
            $params[':valor'] = $filters['valor'];
        }
        
        // Busca por palavra-chave ou código
        if (!empty($filters['busca'])) {
            $conditions[] = "(i.titulo LIKE :busca OR i.descricao LIKE :busca OR i.palavras_chaves LIKE :busca OR i.codigo = :codigo)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
            $params[':codigo'] = $filters['busca'];
        }
        
        // Adicionar condições à consulta
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Ordenação
        $sql .= " ORDER BY i.data DESC, i.hora DESC";
        
        // Paginação
        $limit = $filters['limit'] ?? 12;
        $offset = $filters['offset'] ?? 0;
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Preparar e executar a consulta
        $statement = $databaseConnection->prepare($sql);
        
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $statement->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $statement->bindValue($key, $value);
            }
        }
        
        $statement->execute();
        
        return $statement->fetchAll();
    } catch (PDOException $e) {
        logError("Error searching properties: " . $e->getMessage());
        return [];
    }
}

// Contar total de imóveis com filtros (para paginação)
function countImoveis(array $filters = []): int {
    global $databaseConnection;
    
    try {
        $conditions = [];
        $params = [];
        
        // Base da consulta SQL
        $sql = "SELECT COUNT(*) as total
                FROM sistema_imoveis i
                LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
                LEFT JOIN sistema_bairros b ON i.id_bairro = b.id
                LEFT JOIN sistema_cidades cid ON i.id_cidade = cid.id
                LEFT JOIN sistema_estados e ON i.id_estado = e.id
                WHERE i.status = 'ativo'";
        
        // Aplicar os mesmos filtros da função searchImoveis
        // Tipo (venda/aluguel)
        if (!empty($filters['tipo'])) {
            $conditions[] = "i.para = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        
        // Categoria
        if (!empty($filters['categoria'])) {
            $conditions[] = "i.id_categoria = :categoria";
            $params[':categoria'] = $filters['categoria'];
        }
        
        // Cidade
        if (!empty($filters['cidade'])) {
            $conditions[] = "i.id_cidade = :cidade";
            $params[':cidade'] = $filters['cidade'];
        }
        
        // Bairro
        if (!empty($filters['bairro'])) {
            $conditions[] = "i.id_bairro = :bairro";
            $params[':bairro'] = $filters['bairro'];
        }
        
        // Quartos
        if (!empty($filters['quartos'])) {
            $conditions[] = "i.quartos >= :quartos";
            $params[':quartos'] = $filters['quartos'];
        }
        
        // Suítes
        if (!empty($filters['suites'])) {
            $conditions[] = "i.suites >= :suites";
            $params[':suites'] = $filters['suites'];
        }
        
        // Banheiros
        if (!empty($filters['banheiros'])) {
            $conditions[] = "i.banheiros >= :banheiros";
            $params[':banheiros'] = $filters['banheiros'];
        }
        
        // Vagas de Garagem
        if (!empty($filters['garagem'])) {
            $conditions[] = "i.garagem >= :garagem";
            $params[':garagem'] = $filters['garagem'];
        }
        
        // Valor máximo
        if (!empty($filters['valor'])) {
            $conditions[] = "i.valor <= :valor";
            $params[':valor'] = $filters['valor'];
        }
        
        // Busca por palavra-chave ou código
        if (!empty($filters['busca'])) {
            $conditions[] = "(i.titulo LIKE :busca OR i.descricao LIKE :busca OR i.palavras_chaves LIKE :busca OR i.codigo = :codigo)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
            $params[':codigo'] = $filters['busca'];
        }
        
        // Adicionar condições à consulta
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Preparar e executar a consulta
        $statement = $databaseConnection->prepare($sql);
        
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        
        $statement->execute();
        
        $result = $statement->fetch();
        return (int) $result['total'];
    } catch (PDOException $e) {
        logError("Error counting properties: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obter o caminho da imagem principal de um imóvel
 * 
 * @param array $imovel Dados do imóvel
 * @return string Caminho para a imagem principal
 */
function getPropertyMainImage($imovel): string {
    // Se não tiver o código do imóvel
    if (empty($imovel['codigo'])) {
        return BASE_URL . '/assets/img/placeholder.png';
    }
    
    // Caminho do arquivo de imagem principal (01.jpg)
    $image_path = __DIR__ . '/../uploads/imoveis/' . $imovel['codigo'] . '01.jpg';
    
    // Verifica se o arquivo existe
    if (file_exists($image_path)) {
        return UPLOADS_URL . '/imoveis/' . $imovel['codigo'] . '01.jpg';
    }
    
    // Se não existir, retorna a imagem placeholder
    return BASE_URL . '/assets/img/placeholder.png';
}

/**
 * Obter imagens de um imóvel específico
 * 
 * @param int $property_id ID do imóvel
 * @return array Array de imagens do imóvel
 */
function getPropertyImages(int $property_id): array {
    global $databaseConnection;
    
    try {
        // Primeiro pegamos o código do imóvel no banco de dados
        $statement = $databaseConnection->prepare(
            "SELECT codigo FROM sistema_imoveis WHERE id = :id_imovel LIMIT 1"
        );
        
        $statement->bindParam(':id_imovel', $property_id, PDO::PARAM_INT);
        $statement->execute();
        
        $result = $statement->fetch();
        
        // Se não encontrou o imóvel ou não tem código
        if (!$result || empty($result['codigo'])) {
            return [];
        }
        
        $codigo = $result['codigo'];
        $images = [];
        
        // Primeiro, vamos tentar procurar na pasta do novo site
        $new_upload_dir = __DIR__ . '/../uploads/imoveis/';
        
        // Se o diretório não existir, vamos tentar usar o diretório do site antigo
        // Isso vai ajudar durante a migração
        $old_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Imagens/imagens_imoveis/';
        
        // Verificar imagens tanto no novo quanto no antigo diretório
        for ($i = 1; $i <= 12; $i++) {
            $number = str_pad($i, 2, '0', STR_PAD_LEFT); // Transforma 1 em 01, 2 em 02, etc.
            $image_name = $codigo . $number . '.jpg';
            
            // Verifica primeiro no novo diretório de uploads
            $new_image_path = $new_upload_dir . $image_name;
            
            // Depois verifica no diretório antigo
            $old_image_path = $old_upload_dir . $image_name;
            
            // Determina qual caminho usar
            if (file_exists($new_image_path)) {
                // Se encontrar no novo diretório, usa ele
                $images[] = [
                    'id' => $i,
                    'id_imovel' => $property_id,
                    'imagem' => $image_name,
                    'is_principal' => ($i === 1) ? 1 : 0
                ];
            } elseif (file_exists($old_image_path)) {
                // Se não encontrar no novo mas encontrar no antigo, copia para o novo
                // Certifica-se de que o diretório existe
                if (!is_dir($new_upload_dir)) {
                    mkdir($new_upload_dir, 0755, true);
                }
                
                // Tenta copiar a imagem para o novo diretório
                if (copy($old_image_path, $new_image_path)) {
                    $images[] = [
                        'id' => $i,
                        'id_imovel' => $property_id,
                        'imagem' => $image_name,
                        'is_principal' => ($i === 1) ? 1 : 0
                    ];
                } else {
                    // Se não conseguir copiar, pelo menos adiciona a referência à imagem antiga
                    logError("Não foi possível copiar imagem: $old_image_path para $new_image_path");
                    
                    // Aqui você pode decidir se quer usar a imagem no caminho antigo
                    // Mas isso exigiria modificar o single.php para usar o caminho correto
                }
            }
        }
        
        // Se não encontrou nenhuma imagem nos diretórios padrões, tenta buscar em mais lugares
        if (empty($images)) {
            // Outros locais possíveis onde as imagens podem estar
            $other_locations = [
                $_SERVER['DOCUMENT_ROOT'] . '/imagens/imagens_imoveis/', // variação em minúsculas
                $_SERVER['DOCUMENT_ROOT'] . '/images/properties/',        // pasta em inglês
                $_SERVER['DOCUMENT_ROOT'] . '/assets/img/imoveis/'        // outra possível localização
            ];
            
            foreach ($other_locations as $location) {
                for ($i = 1; $i <= 12; $i++) {
                    $number = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $image_name = $codigo . $number . '.jpg';
                    $image_path = $location . $image_name;
                    
                    if (file_exists($image_path)) {
                        // Se encontrou neste local, copia para o diretório novo
                        if (!is_dir($new_upload_dir)) {
                            mkdir($new_upload_dir, 0755, true);
                        }
                        
                        if (copy($image_path, $new_upload_dir . $image_name)) {
                            $images[] = [
                                'id' => $i,
                                'id_imovel' => $property_id,
                                'imagem' => $image_name,
                                'is_principal' => ($i === 1) ? 1 : 0
                            ];
                        }
                    }
                }
            }
        }
        
        // Retorna o array de imagens
        return $images;
    } catch (PDOException $e) {
        logError("Error fetching property images: " . $e->getMessage());
        return [];
    }
}