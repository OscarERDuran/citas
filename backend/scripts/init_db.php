<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/DatabaseMySQLi.php';

/**
 * Script de inicialización de la base de datos
 */

class DatabaseInitializer {
    
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Ejecutar inicialización completa
     */
    public function run() {
        echo "=== INICIALIZANDO BASE DE DATOS FAMICITAS ===\n";
        
        try {
            $this->createTables();
            $this->insertDefaultData();
            echo "\n✅ Base de datos inicializada correctamente\n";
        } catch (Exception $e) {
            echo "\n❌ Error al inicializar base de datos: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Crear tablas
     */
    private function createTables() {
        echo "\n🔧 Creando tablas...\n";
        
        $connection = $this->db->getConnection();
        
        // Leer y ejecutar el archivo de schema
        $sqlFile = __DIR__ . '/../config/schema.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("Archivo schema.sql no encontrado");
        }
        
        // Usar el método executeSqlFile de DatabaseMySQLi
        $this->db->executeSqlFile($sqlFile);
        echo "  ✓ Schema ejecutado correctamente\n";
        
        echo "✅ Tablas creadas correctamente\n";
    }
    
    /**
     * Insertar datos por defecto
     */
    private function insertDefaultData() {
        echo "\n📝 Insertando datos por defecto...\n";
        
        $this->insertEspecialidades();
        $this->insertAdminUser();
        $this->insertSampleMedicos();
        $this->insertSamplePacientes();
        
        echo "✅ Datos por defecto insertados\n";
    }
    
    /**
     * Insertar especialidades médicas
     */
    private function insertEspecialidades() {
        $connection = $this->db->getConnection();
        
        $especialidades = [
            ['Medicina General', 'Atención médica general y consultas básicas'],
            ['Cardiología', 'Especialidad médica dedicada al diagnóstico y tratamiento de enfermedades del corazón'],
            ['Dermatología', 'Especialidad médica dedicada al estudio y tratamiento de enfermedades de la piel'],
            ['Ginecología', 'Especialidad médica dedicada al cuidado de la salud femenina'],
            ['Pediatría', 'Especialidad médica dedicada al cuidado de la salud de niños y adolescentes'],
            ['Neurología', 'Especialidad médica que trata trastornos del sistema nervioso'],
            ['Oftalmología', 'Especialidad médica dedicada al diagnóstico y tratamiento de enfermedades de los ojos'],
            ['Traumatología', 'Especialidad médica dedicada al diagnóstico y tratamiento de lesiones del sistema músculo-esquelético'],
            ['Psiquiatría', 'Especialidad médica dedicada al diagnóstico y tratamiento de trastornos mentales'],
            ['Endocrinología', 'Especialidad médica que estudia las glándulas endocrinas y sus hormonas']
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO especialidades (nombre, descripcion, activo, fecha_creacion) 
            VALUES (?, ?, 1, NOW())
        ");
        
        foreach ($especialidades as $especialidad) {
            $stmt->bind_param('ss', $especialidad[0], $especialidad[1]);
            $stmt->execute();
            echo "  ✓ Especialidad: {$especialidad[0]}\n";
        }
        
        $stmt->close();
    }
    
    /**
     * Insertar usuario administrador por defecto
     */
    private function insertAdminUser() {
        $connection = $this->db->getConnection();
        
        // Verificar si ya existe un admin
        $result = $connection->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'administrador'");
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            echo "  ℹ️  Usuario administrador ya existe\n";
            return;
        }
        
        $nombre = 'Administrador';
        $apellido = 'Sistema';
        $email = 'admin@famicitas.com';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $rol = 'administrador';
        $activo = 1;
        $fecha_creacion = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_creacion) 
                VALUES ('$nombre', '$apellido', '$email', '$password', '$rol', $activo, '$fecha_creacion')";
        
        if ($connection->query($sql)) {
            echo "  ✓ Usuario administrador creado (admin@famicitas.com / admin123)\n";
        } else {
            throw new Exception("Error creando admin: " . $connection->error);
        }
    }
    
    /**
     * Insertar médicos de ejemplo
     */
    private function insertSampleMedicos() {
        $connection = $this->db->getConnection();
        
        // Obtener algunas especialidades
        $result = $connection->query("SELECT id FROM especialidades LIMIT 5");
        $especialidades = [];
        while ($row = $result->fetch_assoc()) {
            $especialidades[] = $row['id'];
        }
        
        $medicos = [
            [
                'nombre' => 'Dr. Juan',
                'apellido' => 'Pérez',
                'email' => 'dr.perez@famicitas.com',
                'cedula' => '12345678',
                'telefono' => '555-0001',
                'especialidad_id' => $especialidades[0] ?? 1
            ],
            [
                'nombre' => 'Dra. María',
                'apellido' => 'García',
                'email' => 'dra.garcia@famicitas.com',
                'cedula' => '87654321',
                'telefono' => '555-0002',
                'especialidad_id' => $especialidades[1] ?? 2
            ],
            [
                'nombre' => 'Dr. Carlos',
                'apellido' => 'López',
                'email' => 'dr.lopez@famicitas.com',
                'cedula' => '11223344',
                'telefono' => '555-0003',
                'especialidad_id' => $especialidades[2] ?? 3
            ]
        ];
        
        foreach ($medicos as $medico) {
            // Crear usuario médico
            $password = password_hash('medico123', PASSWORD_DEFAULT);
            $fecha = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_creacion) 
                    VALUES ('{$medico['nombre']}', '{$medico['apellido']}', '{$medico['email']}', 
                    '$password', 'medico', 1, '$fecha')";
            
            if ($connection->query($sql)) {
                $userId = $connection->insert_id;
                
                // Crear perfil de médico
                $sqlMedico = "INSERT INTO medicos (usuario_id, cedula, telefono, especialidad_id, activo, fecha_registro) 
                             VALUES ($userId, '{$medico['cedula']}', '{$medico['telefono']}', 
                             {$medico['especialidad_id']}, 1, '$fecha')";
                
                if ($connection->query($sqlMedico)) {
                    echo "  ✓ Médico: {$medico['nombre']} {$medico['apellido']}\n";
                } else {
                    echo "  ❌ Error creando perfil médico: " . $connection->error . "\n";
                }
            } else {
                echo "  ❌ Error creando usuario médico: " . $connection->error . "\n";
            }
        }
    }
    
    /**
     * Insertar pacientes de ejemplo
     */
    private function insertSamplePacientes() {
        $connection = $this->db->getConnection();
        
        $pacientes = [
            [
                'nombre' => 'Ana',
                'apellido' => 'Rodríguez',
                'email' => 'ana.rodriguez@email.com',
                'cedula' => '98765432',
                'telefono' => '555-1001',
                'fecha_nacimiento' => '1985-03-15'
            ],
            [
                'nombre' => 'Luis',
                'apellido' => 'Martínez',
                'email' => 'luis.martinez@email.com',
                'cedula' => '56789012',
                'telefono' => '555-1002',
                'fecha_nacimiento' => '1990-07-22'
            ]
        ];
        
        foreach ($pacientes as $paciente) {
            // Crear usuario paciente
            $password = password_hash('paciente123', PASSWORD_DEFAULT);
            $fecha = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_creacion) 
                    VALUES ('{$paciente['nombre']}', '{$paciente['apellido']}', '{$paciente['email']}', 
                    '$password', 'paciente', 1, '$fecha')";
            
            if ($connection->query($sql)) {
                $userId = $connection->insert_id;
                
                // Crear perfil de paciente
                $sqlPaciente = "INSERT INTO pacientes (usuario_id, cedula, telefono, fecha_nacimiento, activo, fecha_registro) 
                               VALUES ($userId, '{$paciente['cedula']}', '{$paciente['telefono']}', 
                               '{$paciente['fecha_nacimiento']}', 1, '$fecha')";
                
                if ($connection->query($sqlPaciente)) {
                    echo "  ✓ Paciente: {$paciente['nombre']} {$paciente['apellido']}\n";
                } else {
                    echo "  ❌ Error creando perfil paciente: " . $connection->error . "\n";
                }
            } else {
                echo "  ❌ Error creando usuario paciente: " . $connection->error . "\n";
            }
        }
    }
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli') {
    $initializer = new DatabaseInitializer();
    $initializer->run();
}
?>
