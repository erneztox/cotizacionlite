<?php
require_once 'config.php';

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo '';
    exit;
}

$stmt = $db->prepare('SELECT * FROM clientes WHERE nombre LIKE :q OR rut LIKE :q LIMIT 5');
$stmt->bindValue(':q', "%$q%", SQLITE3_TEXT);
$result = $stmt->execute();

$suggestions = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $suggestions[] = $row;
}

if (empty($suggestions)) {
    echo '';
    exit;
}

foreach ($suggestions as $cliente) {
    echo "<div class='autocomplete-suggestion' 
          data-id='{$cliente['id']}'
          data-nombre='{$cliente['nombre']}'
          data-rut='{$cliente['rut']}'
          data-direccion='{$cliente['direccion']}'
          data-telefono='{$cliente['telefono']}'
          data-email='{$cliente['email']}'
          onclick='seleccionarCliente(this)'>
          {$cliente['nombre']}
          </div>";
} 