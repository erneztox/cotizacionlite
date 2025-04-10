<?php
require_once 'config.php';

// Procesar cambio de estado si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cotizacion_id'])) {
    $stmt = $db->prepare('UPDATE cotizaciones SET estado = "Procesada" WHERE id = :id');
    $stmt->bindValue(':id', $_POST['cotizacion_id'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: lista_cotizaciones.php');
    exit;
}

$search = $_GET['search'] ?? '';

// Verificar si es una petición AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Construir la consulta SQL
$sql = 'SELECT c.*, cl.nombre as cliente_nombre, cl.rut as cliente_rut 
        FROM cotizaciones c 
        LEFT JOIN clientes cl ON c.cliente_id = cl.id';

if ($search) {
    $sql .= ' WHERE c.numero LIKE :search 
              OR cl.nombre LIKE :search 
              OR cl.rut LIKE :search';
}

$sql .= ' ORDER BY c.fecha DESC';

$stmt = $db->prepare($sql);
if ($search) {
    $search_param = "%$search%";
    $stmt->bindValue(':search', $search_param, SQLITE3_TEXT);
}

$result = $stmt->execute();

if ($is_ajax) {
    // Si es AJAX, solo devolver la tabla
    include 'tabla_cotizaciones.php';
    exit;
}

include 'header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Lista de Cotizaciones</h1>
        </div>
        <div class="col text-end">
            <a href="index.php" class="btn btn-primary">Nueva Cotización</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="input-group">
                <input type="text" class="form-control" id="search-input" placeholder="Buscar por número, cliente o RUT..." value="<?= htmlspecialchars($search) ?>">
                <?php if ($search): ?>
                    <a href="lista_cotizaciones.php" class="btn btn-outline-secondary">Limpiar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'tabla_cotizaciones.php'; ?>
</div>

<script>
document.getElementById('search-input').addEventListener('input', function(e) {
    const search = e.target.value;
    const url = new URL(window.location.href);
    url.searchParams.set('search', search);
    
    // Actualizar la URL sin recargar la página
    history.pushState({}, '', url);
    
    // Hacer la petición AJAX
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        document.querySelector('table').outerHTML = html;
    });
});
</script>

<?php include 'footer.php'; ?> 