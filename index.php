<?php
require_once 'config.php';

// Obtener datos de la empresa
$result = $db->query('SELECT * FROM empresa LIMIT 1');
$empresa = $result->fetchArray(SQLITE3_ASSOC);

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar el formulario
    require_once 'guardar_cotizacion.php';
    exit;
}

// Obtener items del formulario (si hay)
$items = isset($_GET['items']) ? json_decode($_GET['items'], true) : [['descripcion' => '', 'unidad' => '', 'cantidad' => 1, 'precio' => '', 'largo' => 1.00, 'total' => 0.00]];

include 'header.php';
?>

<style>
    .autocomplete-suggestions {
        position: absolute;
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: none;
        width: 100%;
    }

    .autocomplete-suggestions div {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .autocomplete-suggestions div:last-child {
        border-bottom: none;
    }

    .autocomplete-suggestions div:hover {
        background-color: #f8f9fa;
    }

    .producto-input, .cliente-input {
        position: relative;
        display: block;
    }

    .producto-input .autocomplete-suggestions, 
    .cliente-input .autocomplete-suggestions {
        width: 100%;
        top: 100%;
        left: 0;
        margin-top: 2px;
    }

    /* Asegurar que la tabla no interfiera con las sugerencias */
    table {
        position: relative;
    }

    /* Asegurar que las celdas de la tabla no interfieran con las sugerencias */
    td {
        position: static;
        padding: 0;
    }

    /* Asegurar que el input y las sugerencias estén correctamente posicionados */
    .producto-input input {
        width: 100%;
        position: relative;
        z-index: 1;
    }

    .producto-input .autocomplete-suggestions {
        position: absolute;
        z-index: 2;
    }
</style>

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
        <h2 class="text-center h4 mb-4">COTIZACIÓN <?= get_next_cotizacion_number() ?></h2>
    </div>
    
    <h1 class="mb-4">Nueva Cotización</h1>
    
    <form method="post" id="cotizacion-form">
        <!-- Información del cliente y fecha -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Cliente</label>
                    <div class="cliente-input">
                        <input type="text" class="form-control" id="cliente-nombre" name="nombre" placeholder="Nombre del cliente..." onkeyup="buscarClientes(this)">
                        <div class="autocomplete-suggestions"></div>
                    </div>
                    <input type="hidden" name="cliente_id" id="cliente-id">
                </div>
                <div class="mb-3">
                    <label class="form-label">RUT</label>
                    <input type="text" class="form-control" id="cliente-rut" name="rut" placeholder="RUT del cliente">
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="cliente-direccion" name="direccion" placeholder="Dirección del cliente">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="cliente-telefono" name="telefono" placeholder="Teléfono del cliente">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="cliente-email" name="email" placeholder="Email del cliente">
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="fecha" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        </div>
        
        <!-- Tabla de Items -->
        <div class="mb-4">
            <button type="button" class="btn btn-secondary mb-3" onclick="agregarItem()">+ Agregar Item</button>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="col-4">Detalle</th>
                        <th class="col-1">Unidad</th>
                        <th class="col-1">Cantidad</th>
                        <th class="col-2">Precio</th>
                        <th class="col-1">Largo</th>
                        <th class="col-2">TOTALES</th>
                        <th class="col-1"></th>
                    </tr>
                </thead>
                <tbody id="items-cotizacion">
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td>
                            <div class="producto-input">
                                <input type="text" class="form-control" name="descripcion[]" onkeyup="buscarProductos(this)">
                                <input type="hidden" name="producto_id[]">
                                <div class="autocomplete-suggestions"></div>
                            </div>
                        </td>
                        <td><input type="text" class="form-control unidad" name="unidad[]" value="<?php echo htmlspecialchars($item['unidad']); ?>"></td>
                        <td><input type="number" class="form-control cantidad" name="cantidad[]" min="1" value="<?php echo $item['cantidad']; ?>"></td>
                        <td><input type="number" class="form-control precio" name="precio[]" step="0.01" value="<?php echo $item['precio']; ?>"></td>
                        <td><input type="number" class="form-control largo" name="largo[]" step="0.01" value="<?php echo $item['largo']; ?>"></td>
                        <td><span class="total-item form-control-plaintext text-end" style="font-weight: bold;">$<?php echo number_format($item['total'], 2); ?></span></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarItem(this)">✕</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Totales -->
        <div class="mb-4">
            <div class="text-end">
                <p class="mb-1"><strong>VALOR:</strong> <span id="valor-neto" style="font-weight: bold;">$0.00</span></p>
                <p class="mb-1"><strong>I.V.A. 19%:</strong> <span id="valor-iva" style="font-weight: bold;">$0.00</span></p>
                <p class="h4 mb-0"><strong>TOTAL:</strong> <span id="total-cotizacion" style="font-weight: bold;">$0.00</span></p>
            </div>
        </div>
        
        <!-- Observaciones y condiciones -->
        <div class="mb-4">
            <h3 class="h5 mt-4">OBSERVACIONES</h3>
            <div class="border p-3">
                <div class="mb-2">
                    <label class="form-label">Condiciones de Pago</label>
                    <input type="text" class="form-control" name="condiciones_pago" value="Anticipo 50% saldo una vez cargado el camion y antes de salir a despacho">
                </div>
                <div class="mb-2">
                    <label class="form-label">Plazo de Entrega</label>
                    <input type="text" class="form-control" name="plazo_entrega" value="N/A">
                </div>
                <div class="mb-2">
                    <label class="form-label">Validez Cotización</label>
                    <input type="text" class="form-control" name="validez" value="5 días">
                </div>
                <div class="mb-2">
                    <label class="form-label">Notas Adicionales</label>
                    <textarea class="form-control" name="notas">Ninguna de las partidas no incluidas en la presente cotizacion</textarea>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg mt-4">Guardar Cotización</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Funciones para manejar items
    function agregarItem() {
        const tbody = document.getElementById('items-cotizacion');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <div class="producto-input">
                    <input type="text" class="form-control" name="descripcion[]" onkeyup="buscarProductos(this)">
                    <input type="hidden" name="producto_id[]">
                    <div class="autocomplete-suggestions"></div>
                </div>
            </td>
            <td><input type="text" class="form-control unidad" name="unidad[]"></td>
            <td><input type="number" class="form-control cantidad" name="cantidad[]" min="1" value="1"></td>
            <td><input type="number" class="form-control precio" name="precio[]" step="0.01"></td>
            <td><input type="number" class="form-control largo" name="largo[]" step="0.01" value="1.00"></td>
            <td><span class="total-item form-control-plaintext text-end" style="font-weight: bold;">$0.00</span></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarItem(this)">✕</button></td>
        `;
        tbody.appendChild(newRow);
    }

    function eliminarItem(button) {
        button.closest('tr').remove();
        calcularTotales();
    }

    // Funciones para autocompletado de clientes
    function buscarClientes(input) {
        const q = input.value;
        if (q.length < 2) {
            input.nextElementSibling.style.display = 'none';
            return;
        }
        
        fetch(`buscar_clientes.php?q=${encodeURIComponent(q)}`)
            .then(response => response.text())
            .then(html => {
                const suggestions = input.nextElementSibling;
                suggestions.innerHTML = html;
                suggestions.style.display = 'block';
            });
    }

    function seleccionarCliente(element) {
        const form = document.getElementById('cotizacion-form');
        form.querySelector('[name="cliente_id"]').value = element.dataset.id;
        form.querySelector('[name="nombre"]').value = element.dataset.nombre;
        form.querySelector('[name="rut"]').value = element.dataset.rut;
        form.querySelector('[name="direccion"]').value = element.dataset.direccion;
        form.querySelector('[name="telefono"]').value = element.dataset.telefono;
        form.querySelector('[name="email"]').value = element.dataset.email;
        element.parentElement.style.display = 'none';
    }

    // Funciones para autocompletado de productos
    function buscarProductos(input) {
        const q = input.value;
        console.log('Buscando productos con:', q);
        if (q.length < 2) {
            const suggestions = input.parentElement.querySelector('.autocomplete-suggestions');
            suggestions.style.display = 'none';
            return;
        }
        
        fetch(`buscar_productos.php?q=${encodeURIComponent(q)}`)
            .then(response => response.text())
            .then(html => {
                console.log('Respuesta recibida:', html);
                const suggestions = input.parentElement.querySelector('.autocomplete-suggestions');
                suggestions.innerHTML = html;
                suggestions.style.display = 'block';
            })
            .catch(error => {
                console.error('Error en la búsqueda:', error);
            });
    }

    function seleccionarProducto(element) {
        const row = element.closest('tr');
        row.querySelector('[name="descripcion[]"]').value = element.dataset.descripcion;
        row.querySelector('[name="unidad[]"]').value = element.dataset.unidad;
        row.querySelector('[name="precio[]"]').value = element.dataset.precio;
        row.querySelector('[name="largo[]"]').value = element.dataset.largo;
        row.querySelector('[name="producto_id[]"]').value = element.dataset.id;
        element.parentElement.style.display = 'none';
        calcularTotales();
    }

    // Calcular totales
    function calcularTotales() {
        let valorNeto = 0;
        document.querySelectorAll('#items-cotizacion tr').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
            const precio = parseFloat(row.querySelector('.precio').value) || 0;
            const largo = parseFloat(row.querySelector('.largo').value) || 0;
            const total = cantidad * precio * largo;
            row.querySelector('.total-item').textContent = `$${Math.round(total).toLocaleString('es-CL')}`;
            valorNeto += total;
        });

        const iva = valorNeto * 0.19;
        const total = valorNeto + iva;

        document.getElementById('valor-neto').textContent = `$${Math.round(valorNeto).toLocaleString('es-CL')}`;
        document.getElementById('valor-iva').textContent = `$${Math.round(iva).toLocaleString('es-CL')}`;
        document.getElementById('total-cotizacion').textContent = `$${Math.round(total).toLocaleString('es-CL')}`;
    }

    // Event listeners para calcular totales
    document.addEventListener('input', function(e) {
        if (e.target.matches('.cantidad, .precio, .largo')) {
            calcularTotales();
        }
    });

    // Función de validación adaptada a la estructura HTML existente
    function validarFormulario(e) {
        // Validar cliente
        const nombreCliente = document.querySelector('#cliente-nombre').value.trim();
        const rutCliente = document.querySelector('#cliente-rut').value.trim();
        
        if (!nombreCliente) {
            alert('Por favor, ingrese el nombre del cliente');
            e.preventDefault();
            return false;
        }
        
        if (!rutCliente) {
            alert('Por favor, ingrese el RUT del cliente');
            e.preventDefault();
            return false;
        }

        // Validar items
        const filas = document.querySelectorAll('#items-cotizacion tr');
        if (filas.length === 0) {
            alert('Debe agregar al menos un ítem a la cotización');
            e.preventDefault();
            return false;
        }

        // Validar cada item
        let itemsValidos = true;
        filas.forEach((fila, index) => {
            const descripcion = fila.querySelector('[name="descripcion[]"]').value.trim();
            const cantidad = parseFloat(fila.querySelector('[name="cantidad[]"]').value) || 0;
            const precio = parseFloat(fila.querySelector('[name="precio[]"]').value) || 0;
            const largo = parseFloat(fila.querySelector('[name="largo[]"]').value) || 0;
            
            if (!descripcion) {
                alert(`Por favor, ingrese la descripción del ítem ${index + 1}`);
                itemsValidos = false;
                e.preventDefault();
                return;
            }
            
            if (cantidad <= 0) {
                alert(`Por favor, ingrese una cantidad válida para el ítem ${index + 1}`);
                itemsValidos = false;
                e.preventDefault();
                return;
            }
            
            if (precio <= 0) {
                alert(`Por favor, ingrese un precio válido para el ítem ${index + 1}`);
                itemsValidos = false;
                e.preventDefault();
                return;
            }
            
            if (largo <= 0) {
                alert(`Por favor, ingrese un largo válido para el ítem ${index + 1}`);
                itemsValidos = false;
                e.preventDefault();
                return;
            }
        });

        if (!itemsValidos) {
            return false;
        }
    }

    // Agregar el evento submit al formulario existente
    document.getElementById('cotizacion-form').addEventListener('submit', validarFormulario);
</script>

<?php include 'footer.php'; ?>