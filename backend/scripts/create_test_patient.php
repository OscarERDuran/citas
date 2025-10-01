<?php
require_once __DIR__ . '/../config/DatabaseMySQLi.php';

try {
    echo "<h2>Creando Paciente de Prueba</h2>";
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Verificar si ya existe un paciente con usuario_id = 1
    $checkQuery = "SELECT * FROM pacientes WHERE usuario_id = 1";
    $result = $connection->query($checkQuery);
    
    if ($result->num_rows > 0) {
        echo "<p>✅ Ya existe un paciente con usuario_id = 1</p>";
        $paciente = $result->fetch_assoc();
        echo "<pre>" . print_r($paciente, true) . "</pre>";
    } else {
        echo "<p>Creando nuevo paciente...</p>";
        
        // Insertar paciente de prueba
        $insertQuery = "INSERT INTO pacientes (usuario_id, documento, tipo_documento, fecha_nacimiento, telefono, direccion, genero, contacto_emergencia, telefono_emergencia, activo, fecha_registro) 
                        VALUES (1, '12345678', 'CC', '1990-01-01', '555-0001', 'Calle 123', 'M', 'Contacto Emergencia', '555-0002', 1, NOW())";
        
        if ($connection->query($insertQuery)) {
            echo "<p>✅ Paciente creado exitosamente con ID: " . $connection->insert_id . "</p>";
        } else {
            echo "<p>❌ Error creando paciente: " . $connection->error . "</p>";
        }
    }
    
    // Mostrar todos los pacientes
    echo "<h3>Todos los Pacientes:</h3>";
    $allQuery = "SELECT p.*, u.nombre, u.apellido, u.email FROM pacientes p 
                 INNER JOIN usuarios u ON p.usuario_id = u.id 
                 WHERE p.activo = 1";
    $result = $connection->query($allQuery);
    
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Usuario ID</th><th>Nombre</th><th>Email</th><th>Documento</th><th>Teléfono</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['usuario_id'] . "</td>";
            echo "<td>" . $row['nombre'] . " " . $row['apellido'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['documento'] . "</td>";
            echo "<td>" . $row['telefono'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay pacientes registrados</p>";
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>