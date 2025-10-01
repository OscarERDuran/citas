<?php
// Script de prueba de conexión
try {
    echo "=== PRUEBA DE CONEXIÓN ===\n";
    
    // Intentar conexión con puerto 3306
    echo "Probando puerto 3306...\n";
    $pdo1 = new PDO("mysql:host=193.203.166.24;port=3306;dbname=u573706850_famicitas;charset=utf8mb4", 'u573706850_famicitas', 'Famicitas123');
    echo "✅ Conexión exitosa en puerto 3306\n";
    $pdo1 = null;
    
} catch (Exception $e) {
    echo "❌ Error puerto 3306: " . $e->getMessage() . "\n";
    
    try {
        echo "Probando puerto 8889...\n";
        $pdo2 = new PDO("mysql:host=localhost;port=8889;dbname=famicitas_db;charset=utf8mb4", 'root', 'root');
        echo "✅ Conexión exitosa en puerto 8889\n";
        $pdo2 = null;
        
    } catch (Exception $e2) {
        echo "❌ Error puerto 8889: " . $e2->getMessage() . "\n";
        
        try {
            echo "Probando puerto 3307...\n";
            $pdo3 = new PDO("mysql:host=193.203.166.24;port=3307;dbname=u573706850_famicitas;charset=utf8mb4", 'u573706850_famicitas', 'Famicitas123');
            echo "✅ Conexión exitosa en puerto 3307\n";
            $pdo3 = null;
            
        } catch (Exception $e3) {
            echo "❌ Error puerto 3307: " . $e3->getMessage() . "\n";
            echo "❌ No se pudo conectar en ningún puerto\n";
        }
    }
}
?>
