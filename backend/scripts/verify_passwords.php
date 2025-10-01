<?php
/**
 * Script para verificar y actualizar contraseñas
 */

require_once __DIR__ . '/../config/DatabaseMySQLi.php';

try {
    echo "=== VERIFICACIÓN DE CONTRASEÑAS ===\n";
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Obtener todos los usuarios
    $query = "SELECT id, email, password, nombre, apellido FROM usuarios WHERE activo = 1";
    $result = $connection->query($query);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $connection->error);
    }
    
    echo "Usuarios encontrados: " . $result->num_rows . "\n\n";
    
    while ($user = $result->fetch_assoc()) {
        echo "Usuario: {$user['email']}\n";
        echo "Nombre: {$user['nombre']} {$user['apellido']}\n";
        echo "Password hash: " . substr($user['password'], 0, 20) . "...\n";
        
        // Verificar si es un hash válido
        $password_info = password_get_info($user['password']);
        echo "Algoritmo: " . ($password_info['algo'] === null ? 'TEXTO PLANO' : $password_info['algoName']) . "\n";
        
        // Si no es un hash válido, asumir que es texto plano y hashear
        if ($password_info['algo'] === null) {
            echo "⚠️  Contraseña en texto plano, actualizando...\n";
            $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
            
            $update_query = "UPDATE usuarios SET password = ? WHERE id = ?";
            $stmt = $connection->prepare($update_query);
            $stmt->bind_param('si', $hashed, $user['id']);
            
            if ($stmt->execute()) {
                echo "✅ Contraseña actualizada con hash\n";
            } else {
                echo "❌ Error actualizando contraseña: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "✅ Contraseña ya está hasheada\n";
        }
        
        echo "-------------------\n";
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>