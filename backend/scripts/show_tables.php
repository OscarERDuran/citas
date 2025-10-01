<?php
require_once __DIR__ . '/../config/DatabaseMySQLi.php';

try {
    echo "<h2>Estructura de las Tablas</h2>";
    
    $db = new Database();
    $connection = $db->getConnection();
    
    // Mostrar estructura de tabla usuarios
    echo "<h3>Tabla: usuarios</h3>";
    $result = $connection->query("DESCRIBE usuarios");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Mostrar estructura de tabla pacientes
    echo "<h3>Tabla: pacientes</h3>";
    $result = $connection->query("DESCRIBE pacientes");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Error: " . $connection->error . "</p>";
    }
    
    // Mostrar estructura de tabla citas
    echo "<h3>Tabla: citas</h3>";
    $result = $connection->query("DESCRIBE citas");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Mostrar datos de usuarios existentes
    echo "<h3>Usuarios Existentes:</h3>";
    $result = $connection->query("SELECT id, nombre, apellido, email, rol FROM usuarios WHERE activo = 1");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['nombre'] . " " . $row['apellido'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['rol'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay usuarios registrados</p>";
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>