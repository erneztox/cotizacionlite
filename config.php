<?php
// Configuración de la base de datos
$db_path = __DIR__ . '/database.sqlite';

// Crear conexión a SQLite
try {
    $db = new SQLite3($db_path);
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (Exception $e) {
    die('Error al conectar con la base de datos: ' . $e->getMessage());
}

// Crear tablas si no existen
$db->exec('CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    rut TEXT NOT NULL,
    direccion TEXT,
    telefono TEXT,
    email TEXT
)');

$db->exec('CREATE TABLE IF NOT EXISTS productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    descripcion TEXT NOT NULL,
    unidad TEXT,
    precio REAL,
    largo REAL
)');

$db->exec('CREATE TABLE IF NOT EXISTS cotizaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero TEXT NOT NULL,
    fecha TEXT NOT NULL,
    cliente_id INTEGER NOT NULL,
    items TEXT NOT NULL,
    valor_neto REAL NOT NULL,
    valor_iva REAL NOT NULL,
    total REAL NOT NULL,
    condiciones_pago TEXT,
    plazo_entrega TEXT,
    validez TEXT,
    notas TEXT,
    estado TEXT DEFAULT "Pendiente",
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
)');

$db->exec('CREATE TABLE IF NOT EXISTS empresa (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    rut TEXT NOT NULL,
    direccion TEXT NOT NULL,
    telefono TEXT NOT NULL,
    email TEXT NOT NULL,
    giro TEXT NOT NULL,
    logo_url TEXT NOT NULL
)');

// Insertar datos de empresa si no existen
$result = $db->query('SELECT COUNT(*) as count FROM empresa');
$count = $result->fetchArray(SQLITE3_ASSOC)['count'];

if ($count == 0) {
    $db->exec("INSERT INTO empresa (nombre, rut, direccion, telefono, email, giro, logo_url) 
               VALUES (
                   'CONSTRUCTORA ARAUCANA SPA',
                   '77.443.579-4',
                   'LORD COCHRANE 5175 PADRE LAS CASAS',
                   '+56 9 6327.9005',
                   'ventas@acerosaraucana.cl',
                   'VENTA MATERIALES DE CONSTRUCCION',
                   'static/img/logo.png'
               )");
}

// Función para generar el siguiente número de cotización
function get_next_cotizacion_number() {
    global $db;
    
    // Obtener el último número de cotización
    $result = $db->query('SELECT numero FROM cotizaciones ORDER BY id DESC LIMIT 1');
    $last_number = $result->fetchArray(SQLITE3_ASSOC)['numero'] ?? '00000';
    
    // Convertir a número, incrementar y formatear a 5 dígitos
    $next_number = str_pad((intval($last_number) + 1), 5, '0', STR_PAD_LEFT);
    
    return $next_number;
}

// Función para calcular totales
function calcular_totales($items) {
    $valor_neto = 0;
    
    foreach ($items as $item) {
        if (!empty($item['descripcion']) && !empty($item['unidad']) && !empty($item['cantidad']) && !empty($item['precio'])) {
            $total = $item['cantidad'] * $item['precio'] * ($item['largo'] ?? 1);
            $valor_neto += $total;
        }
    }
    
    $valor_iva = $valor_neto * 0.19;
    $total = $valor_neto + $valor_iva;
    
    return [
        'valor_neto' => $valor_neto,
        'valor_iva' => $valor_iva,
        'total' => $total
    ];
}
?> 