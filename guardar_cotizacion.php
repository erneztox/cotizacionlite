<?php
require_once 'config.php';

try {
    // Procesar cliente
    $cliente_id = $_POST['cliente_id'] ?? null;
    if (!$cliente_id) {
        // Insertar nuevo cliente
        $stmt = $db->prepare('INSERT INTO clientes (nombre, rut, direccion, telefono, email) VALUES (:nombre, :rut, :direccion, :telefono, :email)');
        $stmt->bindValue(':nombre', $_POST['nombre'], SQLITE3_TEXT);
        $stmt->bindValue(':rut', $_POST['rut'], SQLITE3_TEXT);
        $stmt->bindValue(':direccion', $_POST['direccion'], SQLITE3_TEXT);
        $stmt->bindValue(':telefono', $_POST['telefono'], SQLITE3_TEXT);
        $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
        $stmt->execute();
        $cliente_id = $db->lastInsertRowID();
    }

    // Procesar items
    $items = [];
    $descripciones = $_POST['descripcion'] ?? [];
    $unidades = $_POST['unidad'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio'] ?? [];
    $largos = $_POST['largo'] ?? [];
    $producto_ids = $_POST['producto_id'] ?? [];

    // Asegurarse de que todos los arrays tengan la misma longitud
    $count = max(
        is_array($descripciones) ? count($descripciones) : 0,
        is_array($unidades) ? count($unidades) : 0,
        is_array($cantidades) ? count($cantidades) : 0,
        is_array($precios) ? count($precios) : 0,
        is_array($largos) ? count($largos) : 0,
        is_array($producto_ids) ? count($producto_ids) : 0
    );

    for ($i = 0; $i < $count; $i++) {
        if (!empty($descripciones[$i])) {
            // Si el producto existe, actualizarlo
            if (!empty($producto_ids[$i])) {
                $stmt = $db->prepare('UPDATE productos SET descripcion = :descripcion, unidad = :unidad, precio = :precio, largo = :largo WHERE id = :id');
                $stmt->bindValue(':id', $producto_ids[$i], SQLITE3_INTEGER);
                $stmt->bindValue(':descripcion', $descripciones[$i], SQLITE3_TEXT);
                $stmt->bindValue(':unidad', $unidades[$i] ?? '', SQLITE3_TEXT);
                $stmt->bindValue(':precio', floatval($precios[$i] ?? 0), SQLITE3_FLOAT);
                $stmt->bindValue(':largo', floatval($largos[$i] ?? 0), SQLITE3_FLOAT);
                $stmt->execute();
            } 
            // Si el producto no existe, crearlo
            else {
                $stmt = $db->prepare('INSERT INTO productos (descripcion, unidad, precio, largo) VALUES (:descripcion, :unidad, :precio, :largo)');
                $stmt->bindValue(':descripcion', $descripciones[$i], SQLITE3_TEXT);
                $stmt->bindValue(':unidad', $unidades[$i] ?? '', SQLITE3_TEXT);
                $stmt->bindValue(':precio', floatval($precios[$i] ?? 0), SQLITE3_FLOAT);
                $stmt->bindValue(':largo', floatval($largos[$i] ?? 0), SQLITE3_FLOAT);
                $stmt->execute();
                $producto_ids[$i] = $db->lastInsertRowID();
            }

            $items[] = [
                'id' => $producto_ids[$i],
                'descripcion' => $descripciones[$i],
                'unidad' => $unidades[$i] ?? '',
                'cantidad' => floatval($cantidades[$i] ?? 0),
                'precio' => floatval($precios[$i] ?? 0),
                'largo' => floatval($largos[$i] ?? 0)
            ];
        }
    }

    // Calcular totales
    $totales = calcular_totales($items);

    // Generar número de cotización
    $numero = get_next_cotizacion_number();

    // Insertar cotización
    $stmt = $db->prepare('
        INSERT INTO cotizaciones (
            numero, fecha, cliente_id, items, valor_neto, valor_iva, total,
            condiciones_pago, plazo_entrega, validez, notas, estado
        ) VALUES (
            :numero, :fecha, :cliente_id, :items, :valor_neto, :valor_iva, :total,
            :condiciones_pago, :plazo_entrega, :validez, :notas, "Pendiente"
        )
    ');

    $stmt->bindValue(':numero', $numero, SQLITE3_TEXT);
    $stmt->bindValue(':fecha', date('Y-m-d'), SQLITE3_TEXT);
    $stmt->bindValue(':cliente_id', $cliente_id, SQLITE3_INTEGER);
    $stmt->bindValue(':items', json_encode($items), SQLITE3_TEXT);
    $stmt->bindValue(':valor_neto', $totales['valor_neto'], SQLITE3_FLOAT);
    $stmt->bindValue(':valor_iva', $totales['valor_iva'], SQLITE3_FLOAT);
    $stmt->bindValue(':total', $totales['total'], SQLITE3_FLOAT);
    $stmt->bindValue(':condiciones_pago', $_POST['condiciones_pago'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':plazo_entrega', $_POST['plazo_entrega'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':validez', $_POST['validez'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':notas', $_POST['notas'] ?? '', SQLITE3_TEXT);

    $stmt->execute();
    $cotizacion_id = $db->lastInsertRowID();

    // Redirigir a la página de detalles
    header("Location: ver_cotizacion.php?id=$cotizacion_id");
    exit;

} catch (Exception $e) {
    die("Error al crear cotización: " . $e->getMessage());
}
?> 