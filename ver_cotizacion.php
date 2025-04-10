<?php
require_once 'config.php';

// Procesar eliminación si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $db->prepare('DELETE FROM cotizaciones WHERE id = :id');
    $stmt->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: lista_cotizaciones.php');
    exit;
}

// Obtener datos de la empresa
$result = $db->query('SELECT * FROM empresa LIMIT 1');
$empresa = $result->fetchArray(SQLITE3_ASSOC);

// Obtener la cotización
$stmt = $db->prepare('SELECT c.*, cl.nombre as cliente_nombre, cl.rut as cliente_rut, cl.direccion as cliente_direccion, cl.telefono as cliente_telefono, cl.email as cliente_email 
                      FROM cotizaciones c 
                      LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                      WHERE c.id = :id');
$stmt->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$cotizacion = $result->fetchArray(SQLITE3_ASSOC);

if (!$cotizacion) {
    header('Location: lista_cotizaciones.php');
    exit;
}

$items = json_decode($cotizacion['items'], true);

include 'header.php';
?>

<div class="container py-4">
    <!-- Encabezado de la empresa -->
    <div class="border-bottom pb-3 mb-4">
        <div class="row align-items-center">
            <div class="col-9">
                <h1 class="h4"><?php echo htmlspecialchars($empresa['nombre']); ?></h1>
                <p class="mb-0">GIRO: <?php echo htmlspecialchars($empresa['giro']); ?></p>
                <p class="mb-0">RUT: <?php echo htmlspecialchars($empresa['rut']); ?></p>
                <p class="mb-0"><?php echo htmlspecialchars($empresa['direccion']); ?></p>
                <p class="mb-0">TEL: <?php echo htmlspecialchars($empresa['telefono']); ?> / EMAIL: <?php echo htmlspecialchars($empresa['email']); ?></p>
            </div>
            <div class="col-3 text-end">
                <img src="<?php echo htmlspecialchars($empresa['logo_url']); ?>" alt="Logo" style="max-width: 200px;">
            </div>
        </div>
        <h2 class="text-center h4 mb-4">COTIZACIÓN <?php echo htmlspecialchars($cotizacion['numero']); ?></h2>
    </div>

    <!-- Información del cliente -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h3 class="h5">Cliente</h3>
            <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($cotizacion['cliente_nombre']); ?></p>
            <p class="mb-1"><strong>RUT:</strong> <?php echo htmlspecialchars($cotizacion['cliente_rut']); ?></p>
            <p class="mb-1"><strong>Dirección:</strong> <?php echo htmlspecialchars($cotizacion['cliente_direccion']); ?></p>
            <p class="mb-1"><strong>Teléfono:</strong> <?php echo htmlspecialchars($cotizacion['cliente_telefono']); ?></p>
            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($cotizacion['cliente_email']); ?></p>
        </div>
        <div class="col-md-6">
            <h3 class="h5">Detalles</h3>
            <p class="mb-1"><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cotizacion['fecha'])); ?></p>
            <p class="mb-1"><strong>Estado:</strong> 
                <?php if ($cotizacion['estado'] === 'Pendiente'): ?>
                    <span class="badge bg-warning">Pendiente</span>
                <?php else: ?>
                    <span class="badge bg-success">Procesada</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Tabla de Items -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Detalle</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Largo</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($item['unidad']); ?></td>
                        <td><?php echo number_format($item['cantidad'], 0, ',', '.'); ?></td>
                        <td>$<?php echo number_format($item['precio'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format($item['largo'], 2, ',', '.'); ?></td>
                        <td>$<?php echo number_format($item['cantidad'] * $item['precio'] * $item['largo'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Totales -->
    <div class="row mb-4">
        <div class="col-md-6 offset-md-6">
            <div class="text-end">
                <p class="mb-1"><strong>VALOR:</strong> <span style="font-weight: bold;">$<?php echo number_format($cotizacion['valor_neto'], 2); ?></span></p>
                <p class="mb-1"><strong>I.V.A. 19%:</strong> <span style="font-weight: bold;">$<?php echo number_format($cotizacion['valor_iva'], 2); ?></span></p>
                <p class="h4 mb-0"><strong>TOTAL:</strong> <span style="font-weight: bold;">$<?php echo number_format($cotizacion['total'], 2); ?></span></p>
            </div>
        </div>
    </div>

    <!-- Observaciones -->
    <div class="mb-4">
        <h3 class="h5">OBSERVACIONES</h3>
        <div class="border p-3">
            <p class="mb-2"><strong>Condiciones de Pago:</strong> <?php echo htmlspecialchars($cotizacion['condiciones_pago']); ?></p>
            <p class="mb-2"><strong>Plazo de Entrega:</strong> <?php echo htmlspecialchars($cotizacion['plazo_entrega']); ?></p>
            <p class="mb-2"><strong>Validez Cotización:</strong> <?php echo htmlspecialchars($cotizacion['validez']); ?></p>
            <p class="mb-2"><strong>Notas Adicionales:</strong> <?php echo htmlspecialchars($cotizacion['notas']); ?></p>
        </div>
    </div>

    <div class="text-end mb-4">
        <a href="generar_pdf.php?id=<?= $cotizacion['id'] ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-file-pdf"></i> Generar PDF
        </a>
        <a href="lista_cotizaciones.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <form method="post" class="d-inline">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar esta cotización?')">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?> 