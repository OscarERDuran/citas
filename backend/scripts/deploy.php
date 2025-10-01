<?php
/**
 * Script de despliegue para Famicitas
 */

class DeploymentScript {
    
    private $projectPath;
    private $backupPath;
    
    public function __construct() {
        $this->projectPath = dirname(__DIR__);
        $this->backupPath = $this->projectPath . '/backups';
    }
    
    /**
     * Ejecutar despliegue completo
     */
    public function run() {
        echo "=== SCRIPT DE DESPLIEGUE FAMICITAS ===\n";
        
        try {
            $this->checkRequirements();
            $this->createDirectories();
            $this->setPermissions();
            $this->checkDatabase();
            $this->optimizeSettings();
            $this->createLogFiles();
            
            echo "\n✅ Despliegue completado exitosamente\n";
            $this->showAccessInfo();
            
        } catch (Exception $e) {
            echo "\n❌ Error en el despliegue: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Verificar requisitos del sistema
     */
    private function checkRequirements() {
        echo "\n🔍 Verificando requisitos del sistema...\n";
        
        // Verificar PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            throw new Exception('PHP 7.4.0 o superior requerido. Versión actual: ' . PHP_VERSION);
        }
        echo "  ✓ PHP " . PHP_VERSION . " OK\n";
        
        // Verificar extensiones PHP
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                throw new Exception("Extensión PHP '$extension' requerida pero no está instalada");
            }
            echo "  ✓ Extensión $extension OK\n";
        }
        
        // Verificar Apache/servidor web
        if (function_exists('apache_get_version')) {
            echo "  ✓ Apache " . apache_get_version() . " OK\n";
        } else {
            echo "  ⚠️  Servidor web no detectado (se asume compatibilidad)\n";
        }
    }
    
    /**
     * Crear directorios necesarios
     */
    private function createDirectories() {
        echo "\n📁 Creando directorios necesarios...\n";
        
        $directories = [
            'logs',
            'backups',
            'uploads',
            'temp',
            'cache'
        ];
        
        foreach ($directories as $dir) {
            $path = $this->projectPath . '/' . $dir;
            
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    echo "  ✓ Directorio creado: $dir\n";
                } else {
                    throw new Exception("No se pudo crear el directorio: $dir");
                }
            } else {
                echo "  ✓ Directorio existe: $dir\n";
            }
        }
    }
    
    /**
     * Configurar permisos de archivos
     */
    private function setPermissions() {
        echo "\n🔐 Configurando permisos...\n";
        
        $writableDirectories = [
            'logs',
            'backups',
            'uploads',
            'temp',
            'cache'
        ];
        
        foreach ($writableDirectories as $dir) {
            $path = $this->projectPath . '/' . $dir;
            
            if (is_dir($path)) {
                if (chmod($path, 0755)) {
                    echo "  ✓ Permisos configurados: $dir (755)\n";
                } else {
                    echo "  ⚠️  No se pudieron configurar permisos para: $dir\n";
                }
            }
        }
        
        // Archivos de configuración (solo lectura)
        $configFiles = [
            'config/config.php',
            'config/database.php'
        ];
        
        foreach ($configFiles as $file) {
            $path = $this->projectPath . '/' . $file;
            
            if (file_exists($path)) {
                if (chmod($path, 0644)) {
                    echo "  ✓ Permisos configurados: $file (644)\n";
                } else {
                    echo "  ⚠️  No se pudieron configurar permisos para: $file\n";
                }
            }
        }
    }
    
    /**
     * Verificar configuración de base de datos
     */
    private function checkDatabase() {
        echo "\n🗄️  Verificando base de datos...\n";
        
        try {
            require_once $this->projectPath . '/config/config.php';
            require_once $this->projectPath . '/config/database.php';
            
            $db = new Database();
            $connection = $db->getConnection();
            
            echo "  ✓ Conexión a base de datos establecida\n";
            
            // Verificar si las tablas existen
            $stmt = $connection->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredTables = ['usuarios', 'pacientes', 'medicos', 'especialidades', 'citas'];
            $missingTables = array_diff($requiredTables, $tables);
            
            if (empty($missingTables)) {
                echo "  ✓ Todas las tablas requeridas existen\n";
            } else {
                echo "  ⚠️  Tablas faltantes: " . implode(', ', $missingTables) . "\n";
                echo "  💡 Ejecuta: php scripts/init_db.php\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error de base de datos: " . $e->getMessage() . "\n";
            echo "  💡 Verifica la configuración en config/database.php\n";
        }
    }
    
    /**
     * Optimizar configuraciones
     */
    private function optimizeSettings() {
        echo "\n⚡ Optimizando configuraciones...\n";
        
        // Crear archivo de configuración de PHP optimizado
        $phpIniContent = "; Configuración optimizada para Famicitas\n";
        $phpIniContent .= "memory_limit = 256M\n";
        $phpIniContent .= "max_execution_time = 30\n";
        $phpIniContent .= "max_input_time = 30\n";
        $phpIniContent .= "post_max_size = 16M\n";
        $phpIniContent .= "upload_max_filesize = 16M\n";
        $phpIniContent .= "max_file_uploads = 20\n";
        $phpIniContent .= "date.timezone = America/Bogota\n";
        $phpIniContent .= "log_errors = On\n";
        $phpIniContent .= "error_log = " . $this->projectPath . "/logs/php_errors.log\n";
        
        $phpIniPath = $this->projectPath . '/.user.ini';
        
        if (file_put_contents($phpIniPath, $phpIniContent)) {
            echo "  ✓ Configuración PHP optimizada\n";
        } else {
            echo "  ⚠️  No se pudo crear archivo de configuración PHP\n";
        }
        
        // Verificar configuración de opcache si está disponible
        if (extension_loaded('opcache')) {
            echo "  ✓ OPcache disponible\n";
        } else {
            echo "  💡 OPcache no disponible (recomendado para producción)\n";
        }
    }
    
    /**
     * Crear archivos de log
     */
    private function createLogFiles() {
        echo "\n📝 Configurando archivos de log...\n";
        
        $logFiles = [
            'api.log',
            'auth.log',
            'errors.log',
            'php_errors.log'
        ];
        
        foreach ($logFiles as $logFile) {
            $path = $this->projectPath . '/logs/' . $logFile;
            
            if (!file_exists($path)) {
                if (touch($path)) {
                    echo "  ✓ Log creado: $logFile\n";
                } else {
                    echo "  ⚠️  No se pudo crear log: $logFile\n";
                }
            } else {
                echo "  ✓ Log existe: $logFile\n";
            }
        }
    }
    
    /**
     * Mostrar información de acceso
     */
    private function showAccessInfo() {
        echo "\n=== INFORMACIÓN DE ACCESO ===\n";
        echo "\n🌐 URLs de la aplicación:\n";
        echo "  Frontend: http://localhost/citas/\n";
        echo "  API: http://localhost/citas/backend/api/\n";
        echo "  Health Check: http://localhost/citas/backend/api/health\n";
        
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
        echo "  Inicializar DB: php backend/scripts/init_db.php\n";
        echo "  Ejecutar tests: php backend/scripts/run_tests.php\n";
        echo "  Ver logs: tail -f backend/logs/api.log\n";
        
        echo "\n📚 Documentación API:\n";
        echo "  Endpoints principales:\n";
        echo "    POST /api/auth/login - Iniciar sesión\n";
        echo "    GET  /api/citas - Listar citas\n";
        echo "    POST /api/citas - Crear cita\n";
        echo "    GET  /api/pacientes - Listar pacientes\n";
        echo "    GET  /api/medicos - Listar médicos\n";
        
        echo "\n✅ Sistema listo para usar!\n";
    }
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli') {
    $deployment = new DeploymentScript();
    $deployment->run();
} else {
    // Si se accede desde el navegador
    header('Content-Type: text/plain');
    $deployment = new DeploymentScript();
    $deployment->run();
}
?>
