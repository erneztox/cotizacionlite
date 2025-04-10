<?php
require_once 'config.php';

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo '';
    exit;
}

$stmt = $db->prepare('SELECT * FROM productos WHERE descripcion LIKE :q LIMIT 5');
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

foreach ($suggestions as $producto) {
    echo "<div class='autocomplete-suggestion' 
          data-id='{$producto['id']}'
          data-descripcion='{$producto['descripcion']}'
          data-unidad='{$producto['unidad']}'
          data-precio='{$producto['precio']}'
          data-largo='{$producto['largo']}'
          onclick='seleccionarProducto(this)'>
          {$producto['descripcion']}
          </div>";
} 