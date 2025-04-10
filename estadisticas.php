<?php
require_once 'config.php';

// Función para formatear números al estilo chileno
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

// Función para formatear fechas en español
function formatDate($date) {
    $meses = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ];
    $fecha = date('F Y', strtotime($date . '-01'));
    return strtr($fecha, $meses);
}

// Obtener estadísticas de cotizaciones procesadas
$stats = [];

// Total de cotizaciones procesadas
$result = $db->query('SELECT COUNT(*) as total FROM cotizaciones WHERE estado = "Procesada"');
$stats['total_cotizaciones'] = $result->fetchArray(SQLITE3_ASSOC)['total'];

// Ingresos totales
$result = $db->query('SELECT SUM(total) as ingresos_totales FROM cotizaciones WHERE estado = "Procesada"');
$stats['ingresos_totales'] = $result->fetchArray(SQLITE3_ASSOC)['ingresos_totales'] ?? 0;

// Metros totales de todas las cotizaciones
$result = $db->query('SELECT items FROM cotizaciones WHERE estado = "Procesada"');
$stats['metros_totales'] = 0;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $items = json_decode($row['items'], true);
    foreach ($items as $item) {
        $stats['metros_totales'] += $item['cantidad'] * $item['largo'];
    }
}

// Top 5 clientes por monto total
$result = $db->query('
    SELECT cl.nombre, SUM(c.total) as monto_total
    FROM cotizaciones c
    JOIN clientes cl ON c.cliente_id = cl.id
    WHERE c.estado = "Procesada"
    GROUP BY c.cliente_id
    ORDER BY monto_total DESC
    LIMIT 5
');
$stats['top_clientes'] = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $stats['top_clientes'][] = $row;
}

// Métricas por mes
$result = $db->query('
    SELECT 
        strftime("%Y-%m", fecha) as mes,
        COUNT(*) as total_cotizaciones,
        SUM(total) as ingresos_mensuales
    FROM cotizaciones 
    WHERE estado = "Procesada"
    GROUP BY strftime("%Y-%m", fecha)
    ORDER BY mes DESC
    LIMIT 12
');
$stats['metricas_mensuales'] = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $stats['metricas_mensuales'][] = $row;
}

// Métricas por metros totales
$result = $db->query('
    SELECT c.*, cl.nombre as cliente_nombre
    FROM cotizaciones c
    JOIN clientes cl ON c.cliente_id = cl.id
    WHERE c.estado = "Procesada"
    ORDER BY c.total DESC
');
$stats['cotizaciones_por_metros'] = [];
while ($cotizacion = $result->fetchArray(SQLITE3_ASSOC)) {
    $items = json_decode($cotizacion['items'], true);
    $metros_totales = 0;
    foreach ($items as $item) {
        $metros_totales += $item['cantidad'] * $item['largo'];
    }
    $stats['cotizaciones_por_metros'][] = [
        'numero' => $cotizacion['numero'],
        'cliente' => $cotizacion['cliente_nombre'],
        'metros_totales' => $metros_totales,
        'total' => $cotizacion['total']
    ];
}

include 'header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Estadísticas de Cotizaciones Procesadas</h1>
        </div>
    </div>

    <!-- Resumen general -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Cotizaciones</h5>
                    <p class="card-text display-6"><?= formatNumber($stats['total_cotizaciones']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ingresos Totales</h5>
                    <p class="card-text display-6">$<?= formatNumber($stats['ingresos_totales'], 2) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Metros Totales</h5>
                    <p class="card-text display-6"><?= formatNumber($stats['metros_totales'], 2) ?> m</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 5 clientes -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Top 5 Clientes por Monto</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['top_clientes'] as $cliente): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                                        <td>$<?= number_format($cliente['monto_total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas mensuales -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Cotizaciones por Mes</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['metricas_mensuales'] as $mes): ?>
                                    <tr>
                                        <td><?= formatDate($mes['mes']) ?></td>
                                        <td><?= formatNumber($mes['total_cotizaciones']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ingresos por Mes</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['metricas_mensuales'] as $mes): ?>
                                    <tr>
                                        <td><?= formatDate($mes['mes']) ?></td>
                                        <td>$<?= formatNumber($mes['ingresos_mensuales'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cotizaciones por metros totales -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Cotizaciones por Metros Totales</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Metros Totales</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['cotizaciones_por_metros'] as $cotizacion): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cotizacion['numero']) ?></td>
                                        <td><?= htmlspecialchars($cotizacion['cliente']) ?></td>
                                        <td><?= formatNumber($cotizacion['metros_totales'], 2) ?> m</td>
                                        <td>$<?= formatNumber($cotizacion['total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 