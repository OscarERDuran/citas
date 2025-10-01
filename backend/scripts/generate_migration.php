<?php
// Script para generar dump completo de la base de datos
try {
    $connection = new mysqli('localhost', 'root', 'root', 'famicitas_db');
    
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    
    echo "-- =============================================\n";
    echo "-- FAMICITAS - SISTEMA DE CITAS MÉDICAS\n";
    echo "-- Script de migración completo\n";
    echo "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    echo "-- =============================================\n\n";
    
    // Crear base de datos
    echo "-- Crear base de datos\n";
    echo "CREATE DATABASE IF NOT EXISTS famicitas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    echo "USE famicitas_db;\n\n";
    
    // Obtener todas las tablas
    $tables = [];
    $result = $connection->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    // Orden correcto para crear tablas (respetando foreign keys)
    $tableOrder = ['usuarios', 'especialidades', 'pacientes', 'medicos', 'citas'];
    
    foreach ($tableOrder as $table) {
        if (!in_array($table, $tables)) continue;
        
        echo "-- =============================================\n";
        echo "-- Tabla: $table\n";
        echo "-- =============================================\n\n";
        
        // Obtener estructura de la tabla
        $createResult = $connection->query("SHOW CREATE TABLE $table");
        $createRow = $createResult->fetch_array();
        echo $createRow[1] . ";\n\n";
        
        // Obtener datos de la tabla
        $dataResult = $connection->query("SELECT * FROM $table");
        if ($dataResult->num_rows > 0) {
            echo "-- Datos para la tabla $table\n";
            
            // Obtener nombres de columnas
            $fields = [];
            $fieldInfo = $connection->query("DESCRIBE $table");
            while ($field = $fieldInfo->fetch_array()) {
                $fields[] = $field[0];
            }
            
            echo "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES\n";
            
            $rows = [];
            while ($row = $dataResult->fetch_array(MYSQLI_ASSOC)) {
                $values = [];
                foreach ($fields as $field) {
                    $value = $row[$field];
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $connection->real_escape_string($value) . "'";
                    }
                }
                $rows[] = "(" . implode(', ', $values) . ")";
            }
            
            echo implode(",\n", $rows) . ";\n\n";
        }
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "-- Error: " . $e->getMessage() . "\n";
}
?>