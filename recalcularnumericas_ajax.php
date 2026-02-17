<?php
header('Content-Type: application/json');
$mes = isset($_POST['mes']) ? (int)$_POST['mes'] : 0;

if ($mes < 1 || $mes > 12) {
    echo json_encode(['error' => 'Mes inválido.']);
    exit;
}

$url1 = "http://190.144.105.186:7332/indicadores/api/r_numerica_general.php?mes=$mes";
$url2 = "http://190.144.105.186:7332/indicadores/api/r_numerica.php?mes=$mes";
$url3 = "http://190.144.105.186:7332/indicadores/api/r_numerica_lineas.php?mes=$mes";

$resultado1 = @file_get_contents($url1);
$resultado2 = @file_get_contents($url2);
$resultado3 = @file_get_contents($url3);

echo json_encode([
    'success' => true,
    'resultado1' => $resultado1,
    'resultado2' => $resultado2
]);
