<?php
// Arquivo de teste para verificar conexão e configuração
session_start();

echo "<h2>Teste de Configuração - Sistema de Mapeamento</h2>";
echo "<hr>";

// 1. Teste de Sessão
echo "<h3>1. Teste de Sessão</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Sessão ativa<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Role: " . ($_SESSION['user_role'] ?? 'não definido') . "<br>";
} else {
    echo "❌ Sessão não ativa - Você precisa fazer login como admin<br>";
}
echo "<hr>";

// 2. Teste de Conexão com Banco de Dados
echo "<h3>2. Teste de Conexão com Banco de Dados</h3>";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "✅ Arquivo database.php carregado com sucesso<br>";
    
    if (isset($pdo)) {
        echo "✅ Variável \$pdo está definida<br>";
        
        // Testar conexão
        $stmt = $pdo->query("SELECT 1");
        echo "✅ Conexão com banco de dados funcionando<br>";
        
        // Verificar se a tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'hotmart_lecture_mapping'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela 'hotmart_lecture_mapping' existe<br>";
            
            // Contar registros existentes
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM hotmart_lecture_mapping");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Número de mapeamentos existentes: " . $result['count'] . "<br>";
        } else {
            echo "❌ Tabela 'hotmart_lecture_mapping' NÃO existe<br>";
            echo "<strong>AÇÃO NECESSÁRIA:</strong> Crie a tabela com o seguinte SQL:<br>";
            echo "<pre>";
            echo "CREATE TABLE `hotmart_lecture_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotmart_title` varchar(500) NOT NULL,
  `lecture_id` varchar(36) NOT NULL,
  `lecture_title` varchar(500) NOT NULL,
  `hotmart_page_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            echo "</pre>";
        }
    } else {
        echo "❌ Variável \$pdo NÃO está definida<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao conectar com banco de dados: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// 3. Teste de Arquivos de Dados
echo "<h3>3. Teste de Arquivos de Dados</h3>";

// Teste data_hotmart.php
if (file_exists(__DIR__ . '/data_hotmart.php')) {
    echo "✅ Arquivo data_hotmart.php encontrado<br>";
    try {
        $hotmart_data = require __DIR__ . '/data_hotmart.php';
        echo "✅ Arquivo data_hotmart.php carregado<br>";
        echo "📊 Número de palestras Hotmart: " . count($hotmart_data) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao carregar data_hotmart.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Arquivo data_hotmart.php NÃO encontrado<br>";
}

// Teste data_lectures.php
if (file_exists(__DIR__ . '/data_lectures.php')) {
    echo "✅ Arquivo data_lectures.php encontrado<br>";
    try {
        $lectures_data = require __DIR__ . '/data_lectures.php';
        echo "✅ Arquivo data_lectures.php carregado<br>";
        echo "📊 Número de palestras do sistema: " . count($lectures_data) . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao carregar data_lectures.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Arquivo data_lectures.php NÃO encontrado<br>";
}
echo "<hr>";

// 4. Teste de Arquivos AJAX
echo "<h3>4. Teste de Arquivos AJAX</h3>";
$ajax_files = ['save_mapping_ajax.php', 'delete_mapping_ajax.php'];
foreach ($ajax_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ Arquivo $file encontrado<br>";
    } else {
        echo "❌ Arquivo $file NÃO encontrado<br>";
    }
}
echo "<hr>";

// 5. Teste de Permissões
echo "<h3>5. Teste de Permissões PHP</h3>";
echo "Versão PHP: " . phpversion() . "<br>";
echo "Suporte a JSON: " . (function_exists('json_encode') ? "✅ Sim" : "❌ Não") . "<br>";
echo "Suporte a PDO: " . (class_exists('PDO') ? "✅ Sim" : "❌ Não") . "<br>";
echo "<hr>";

echo "<h3>✅ Resumo</h3>";
echo "<p>Se todos os testes acima passaram, o sistema está pronto para uso!</p>";
echo "<p><a href='map_lectures_interface.php' class='btn btn-primary'>Ir para Interface de Mapeamento</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
h2, h3 { color: #333; }
pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
.btn:hover { background: #0056b3; }
</style>";
?>
