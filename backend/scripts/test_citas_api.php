<?php
// Script para probar la API de citas
echo "=== PRUEBA API CITAS ===\n";

// Simular datos de prueba
$testData = [
    'medico' => '4',
    'especialidad' => '4',
    'hora' => '10:30',
    'fecha' => '2025-10-01',
    'motivo' => 'Consulta de prueba'
];

echo "Datos de prueba: " . json_encode($testData) . "\n";

// Simular petición POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Guardar datos en variable global para simular php://input
$GLOBALS['test_input'] = json_encode($testData);

// Sobrescribir file_get_contents para prueba
function file_get_contents($filename) {
    if ($filename === 'php://input') {
        return $GLOBALS['test_input'];
    }
    return \file_get_contents($filename);
}

// Incluir y ejecutar la API
include __DIR__ . '/../api/citas_simple.php';
?>