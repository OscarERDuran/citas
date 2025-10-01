<?php
require_once __DIR__ . '/../config/DatabaseMySQLi.php';

try {
    echo "<h2>Prueba de Inserción de Paciente</h2>";
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Datos de prueba
    $testData = [
        'nombres' => 'Test',
        'apellidos' => 'Usuario',
        'documento' => '999999999',
        'tipo_documento' => 'cedula',
        'email' => 'test@test.com',
        'telefono' => '555-9999',
        'fecha_nacimiento' => '1990-01-01',
        'genero' => 'masculino',
        'direccion' => 'Calle Test 123',
        'ciudad' => 'Bogotá',
        'departamento' => 'Bogotá D.C.',
        'eps' => 'Sura',
        'tipo_sangre' => 'O+',
        'alergias' => 'Ninguna',
        'observaciones_medicas' => 'Test'
    ];
    
    echo "<h3>Datos a insertar:</h3>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Insertar
    $insertQuery = "INSERT INTO pacientes (
        nombres, apellidos, documento, tipo_documento, email, telefono, 
        fecha_nacimiento, genero, direccion, ciudad, departamento,
        eps, tipo_sangre, alergias, observaciones_medicas, activo, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = $connection->prepare($insertQuery);
    
    if (!$stmt) {
        echo "<p>❌ Error preparando consulta: " . $connection->error . "</p>";
        exit;
    }
    
    $stmt->bind_param("sssssssssssssss", 
        $testData['nombres'], 
        $testData['apellidos'], 
        $testData['documento'], 
        $testData['tipo_documento'], 
        $testData['email'], 
        $testData['telefono'],
        $testData['fecha_nacimiento'], 
        $testData['genero'], 
        $testData['direccion'], 
        $testData['ciudad'], 
        $testData['departamento'],
        $testData['eps'], 
        $testData['tipo_sangre'], 
        $testData['alergias'], 
        $testData['observaciones_medicas']
    );
    
    if ($stmt->execute()) {
        $pacienteId = $connection->insert_id;
        echo "<p>✅ Paciente insertado con ID: " . $pacienteId . "</p>";
        echo "<p>Filas afectadas: " . $connection->affected_rows . "</p>";
        
        // Verificar que se insertó
        $checkQuery = "SELECT * FROM pacientes WHERE id = ?";
        $checkStmt = $connection->prepare($checkQuery);
        $checkStmt->bind_param("i", $pacienteId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<h3>Datos insertados:</h3>";
            $paciente = $result->fetch_assoc();
            echo "<pre>" . print_r($paciente, true) . "</pre>";
        }
        
    } else {
        echo "<p>❌ Error insertando: " . $stmt->error . "</p>";
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>