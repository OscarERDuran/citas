<?php
echo "<h2>Información de PHP y Extensiones</h2>";
echo "<p><strong>Versión PHP:</strong> " . phpversion() . "</p>";

echo "<h3>Extensiones de Base de Datos:</h3>";
echo "<ul>";
echo "<li>MySQLi: " . (extension_loaded('mysqli') ? "✅ HABILITADA" : "❌ NO HABILITADA") . "</li>";
echo "<li>PDO: " . (extension_loaded('pdo') ? "✅ HABILITADA" : "❌ NO HABILITADA") . "</li>";
echo "<li>PDO MySQL: " . (extension_loaded('pdo_mysql') ? "✅ HABILITADA" : "❌ NO HABILITADA") . "</li>";
echo "</ul>";

echo "<h3>Prueba de Conexión MySQLi:</h3>";
if (extension_loaded('mysqli')) {
    try {
        $mysqli = new mysqli('193.203.166.24', 'u573706850_famicitas', 'Famicitas123', 'u573706850_famicitas', 3306);
        if ($mysqli->connect_error) {
            echo "❌ Error de conexión: " . $mysqli->connect_error;
        } else {
            echo "✅ Conexión MySQLi exitosa<br>";
            echo "Versión MySQL: " . $mysqli->server_info . "<br>";
            
            // Probar consulta
            $result = $mysqli->query("SELECT COUNT(*) as total FROM citas");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "Total de citas en BD: " . $row['total'];
            } else {
                echo "Error en consulta: " . $mysqli->error;
            }
            
            $mysqli->close();
        }
    } catch (Exception $e) {
        echo "❌ Excepción: " . $e->getMessage();
    }
} else {
    echo "❌ MySQLi no está disponible";
}
?>