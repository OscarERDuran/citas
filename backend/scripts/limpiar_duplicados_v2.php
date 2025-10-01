<?php
// Script para limpiar duplicados de especialidades con manejo de claves foráneas

try {
    // Conectar a la base de datos
    $connection = new mysqli('localhost', 'root', 'root', 'famicitas_db');
    
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    echo "Conectado a la base de datos\n";
    
    // Obtener especialidades únicas con el ID mínimo (que vamos a mantener)
    $uniqueQuery = "
        SELECT MIN(id) as keep_id, nombre 
        FROM especialidades 
        GROUP BY nombre
    ";
    
    $uniqueResult = $connection->query($uniqueQuery);
    $mappings = [];
    
    echo "\n=== CREANDO MAPEO DE IDs ===\n";
    while ($row = $uniqueResult->fetch_assoc()) {
        $nombre = $row['nombre'];
        $keepId = $row['keep_id'];
        
        // Obtener todos los IDs de esta especialidad
        $allIdsQuery = "SELECT id FROM especialidades WHERE nombre = '" . $connection->real_escape_string($nombre) . "'";
        $allIdsResult = $connection->query($allIdsQuery);
        
        $allIds = [];
        while ($idRow = $allIdsResult->fetch_assoc()) {
            $allIds[] = $idRow['id'];
        }
        
        // Crear mapeo: todos los IDs duplicados -> ID a mantener
        foreach ($allIds as $oldId) {
            if ($oldId != $keepId) {
                $mappings[$oldId] = $keepId;
            }
        }
        
        echo "Especialidad: $nombre -> Mantener ID $keepId, Eliminar IDs: " . implode(', ', array_filter($allIds, function($id) use ($keepId) { return $id != $keepId; })) . "\n";
    }
    
    // Actualizar referencias en la tabla medicos
    echo "\n=== ACTUALIZANDO REFERENCIAS EN MÉDICOS ===\n";
    foreach ($mappings as $oldId => $newId) {
        $updateQuery = "UPDATE medicos SET especialidad_id = $newId WHERE especialidad_id = $oldId";
        if ($connection->query($updateQuery)) {
            echo "Actualizado médicos: especialidad_id $oldId -> $newId (Afectados: {$connection->affected_rows})\n";
        } else {
            echo "Error actualizando médicos: " . $connection->error . "\n";
        }
    }
    
    // Actualizar referencias en la tabla citas (si las hay)
    echo "\n=== VERIFICANDO REFERENCIAS EN CITAS ===\n";
    $citasCheckQuery = "SELECT COUNT(*) as count FROM citas c INNER JOIN medicos m ON c.medico_id = m.id WHERE m.especialidad_id IN (" . implode(',', array_keys($mappings)) . ")";
    $citasCheckResult = $connection->query($citasCheckQuery);
    $citasCount = $citasCheckResult->fetch_assoc()['count'];
    echo "Citas que serán actualizadas indirectamente: $citasCount\n";
    
    // Ahora eliminar las especialidades duplicadas
    echo "\n=== ELIMINANDO ESPECIALIDADES DUPLICADAS ===\n";
    $duplicateIds = array_keys($mappings);
    if (!empty($duplicateIds)) {
        $deleteQuery = "DELETE FROM especialidades WHERE id IN (" . implode(',', $duplicateIds) . ")";
        
        if ($connection->query($deleteQuery)) {
            echo "Especialidades duplicadas eliminadas exitosamente!\n";
            echo "Registros eliminados: " . $connection->affected_rows . "\n";
        } else {
            echo "Error al eliminar especialidades duplicadas: " . $connection->error . "\n";
        }
    }
    
    // Mostrar especialidades finales
    echo "\n=== ESPECIALIDADES FINALES ===\n";
    $result = $connection->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['nombre']}\n";
        $count++;
    }
    
    echo "\nTotal de especialidades únicas: $count\n";
    
    // Verificar integridad
    echo "\n=== VERIFICANDO INTEGRIDAD ===\n";
    $integrityQuery = "
        SELECT m.id, m.cedula, e.nombre as especialidad 
        FROM medicos m 
        INNER JOIN especialidades e ON m.especialidad_id = e.id 
        ORDER BY e.nombre
    ";
    $integrityResult = $connection->query($integrityQuery);
    echo "Médicos con especialidades válidas:\n";
    while ($row = $integrityResult->fetch_assoc()) {
        echo "Médico {$row['cedula']} -> {$row['especialidad']}\n";
    }
    
    $connection->close();
    echo "\nLimpieza completada exitosamente!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
