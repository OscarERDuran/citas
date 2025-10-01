<?php
// Script para limpiar duplicados de especialidades

try {
    // Conectar a la base de datos
    $connection = new mysqli('localhost', 'root', 'root', 'famicitas_db');
    
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    echo "Conectado a la base de datos\n";
    
    // Mostrar especialidades actuales
    echo "\n=== ESPECIALIDADES ANTES DE LIMPIAR ===\n";
    $result = $connection->query("SELECT id, nombre FROM especialidades ORDER BY nombre, id");
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['nombre']}\n";
    }
    
    // Encontrar y eliminar duplicados, manteniendo solo el ID más bajo de cada nombre
    echo "\n=== ELIMINANDO DUPLICADOS ===\n";
    
    // Obtener especialidades únicas con el ID mínimo
    $uniqueQuery = "
        SELECT MIN(id) as min_id, nombre 
        FROM especialidades 
        GROUP BY nombre
    ";
    
    $uniqueResult = $connection->query($uniqueQuery);
    $keepIds = [];
    
    while ($row = $uniqueResult->fetch_assoc()) {
        $keepIds[] = $row['min_id'];
        echo "Manteniendo: ID {$row['min_id']} - {$row['nombre']}\n";
    }
    
    // Convertir array a string para la consulta
    $keepIdsStr = implode(',', $keepIds);
    
    // Eliminar registros duplicados
    $deleteQuery = "DELETE FROM especialidades WHERE id NOT IN ($keepIdsStr)";
    
    if ($connection->query($deleteQuery)) {
        echo "\nDuplicados eliminados exitosamente!\n";
        echo "Registros afectados: " . $connection->affected_rows . "\n";
    } else {
        echo "Error al eliminar duplicados: " . $connection->error . "\n";
    }
    
    // Mostrar especialidades después de limpiar
    echo "\n=== ESPECIALIDADES DESPUÉS DE LIMPIAR ===\n";
    $result = $connection->query("SELECT id, nombre FROM especialidades ORDER BY nombre");
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - {$row['nombre']}\n";
        $count++;
    }
    
    echo "\nTotal de especialidades únicas: $count\n";
    
    $connection->close();
    echo "\nLimpieza completada exitosamente!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
