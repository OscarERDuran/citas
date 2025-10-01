<?php
/**
 * Script de despliegue simplificado para Famicitas
 */

echo "=== DESPLIEGUE FAMICITAS (MySQLi) ===\n";

try {
    // Verificar PHP
    echo "\n🔍 Verificando PHP...\n";
    echo "  ✓ PHP " . PHP_VERSION . " OK\n";
    
    // Verificar mysqli
    if (!extension_loaded('mysqli')) {
        throw new Exception('MySQLi extension no encontrada');
    }
    echo "  ✓ MySQLi extension OK\n";
    
    // Crear directorios
    echo "\n📁 Creando directorios...\n";
    $baseDir = dirname(__DIR__);
    $directories = ['logs', 'uploads', 'temp'];
    
    foreach ($directories as $dir) {
        $path = $baseDir . '/' . $dir;
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                echo "  ✓ Directorio creado: $dir\n";
            }
        } else {
            echo "  ✓ Directorio existe: $dir\n";
        }
    }
    
    // Verificar base de datos
    echo "\n🗄️  Verificando base de datos...\n";
    require_once __DIR__ . '/../config/DatabaseMySQLi.php';
    
    $db = new Database();
    $connection = $db->getConnection();
    echo "  ✓ Conexión a base de datos OK\n";
    
    // Verificar tablas
    $result = $connection->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $requiredTables = ['usuarios', 'especialidades', 'medicos', 'pacientes', 'citas'];
    $missingTables = array_diff($requiredTables, $tables);
    
    if (empty($missingTables)) {
        echo "  ✓ Todas las tablas requeridas existen\n";
    } else {
        echo "  ⚠️  Tablas faltantes: " . implode(', ', $missingTables) . "\n";
    }
    
    // Crear archivos de log
    echo "\n📝 Configurando logs...\n";
    $logFiles = ['api.log', 'auth.log', 'errors.log'];
    
    foreach ($logFiles as $logFile) {
        $path = $baseDir . '/logs/' . $logFile;
        if (!file_exists($path)) {
            if (touch($path)) {
                echo "  ✓ Log creado: $logFile\n";
            }
        } else {
            echo "  ✓ Log existe: $logFile\n";
        }
    }
    
    echo "\n✅ Despliegue completado exitosamente\n";
    
    // Información de acceso
    echo "\n=== INFORMACIÓN DE ACCESO ===\n";
    echo "\n🌐 URLs de la aplicación:\n";
    echo "  Frontend: http://localhost/citas/\n";
    echo "  API: http://localhost/citas/backend/api/\n";
    
    echo "\n👤 Usuarios por defecto:\n";
    echo "  Administrador:\n";
    echo "    Email: admin@famicitas.com\n";
    echo "    Password: admin123\n";
    echo "  \n";
    echo "  Médico de ejemplo:\n";
    echo "    Email: dr.perez@famicitas.com\n";
    echo "    Password: medico123\n";
    echo "  \n";
    echo "  Paciente de ejemplo:\n";
    echo "    Email: ana.rodriguez@email.com\n";
    echo "    Password: paciente123\n";
    
    echo "\n🛠️  Comandos útiles:\n";
    echo "  Reinicializar DB: php backend/scripts/init_db.php\n";
    echo "  Ver logs: type backend\\logs\\api.log\n";
    
    echo "\n📚 Endpoints API principales:\n";
    echo "  POST /api/auth/login - Iniciar sesión\n";
    echo "  GET  /api/citas - Listar citas\n";
    echo "  POST /api/citas - Crear cita\n";
    echo "  GET  /api/pacientes - Listar pacientes\n";
    echo "  GET  /api/medicos - Listar médicos\n";
    
    echo "\n✅ Sistema listo para usar!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error en el despliegue: " . $e->getMessage() . "\n";
}
?>
