<?php
ob_start(); // Iniciar buffer de salida
try {
    require_once 'config.php';
    require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

    // Verificar si se proporcionó un ID de cotización
    if (!isset($_GET['id'])) {
        throw new Exception('ID de cotización no proporcionado');
    }

    $id = $_GET['id'];

    // Obtener datos de la cotización
    $stmt = $db->prepare('SELECT c.*, cl.nombre as cliente_nombre, cl.rut as cliente_rut FROM cotizaciones c JOIN clientes cl ON c.cliente_id = cl.id WHERE c.id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $cotizacion = $result->fetchArray(SQLITE3_ASSOC);

    if (!$cotizacion) {
        throw new Exception('Cotización no encontrada');
    }

    // Obtener datos de la empresa
    $result = $db->query('SELECT * FROM empresa LIMIT 1');
    $empresa = $result->fetchArray(SQLITE3_ASSOC);

    if (!$empresa) {
        throw new Exception('Datos de empresa no encontrados');
    }

    // Crear nuevo documento PDF
    class MYPDF extends TCPDF {
        public function Header() {
            // No header
        }
        
        public function Footer() {
            // Posición a 15 mm del final
            $this->SetY(-15);
            // Fuente
            $this->SetFont('helvetica', 'I', 8);
            // Número de página
            $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0);
        }
    }

    // Crear nuevo documento
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Establecer información del documento
    $pdf->SetCreator('Sistema de Cotizaciones');
    $pdf->SetAuthor($empresa['nombre']);
    $pdf->SetTitle('Cotización ' . $cotizacion['numero']);

    // Establecer márgenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Agregar página
    $pdf->AddPage();

    // Establecer fuente
    $pdf->SetFont('helvetica', '', 10);

    // Logo y datos de la empresa
    if (file_exists($empresa['logo_url'])) {
        $pdf->Image($empresa['logo_url'], 150, 15, 45);
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, $empresa['nombre'], 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'GIRO: ' . $empresa['giro'], 0, 1, 'L');
    $pdf->Cell(0, 6, 'RUT: ' . $empresa['rut'], 0, 1, 'L');
    $pdf->Cell(0, 6, $empresa['direccion'], 0, 1, 'L');
    $pdf->Cell(0, 6, 'TEL: ' . $empresa['telefono'] . ' / EMAIL: ' . $empresa['email'], 0, 1, 'L');

    // Número de cotización
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'COTIZACIÓN ' . $cotizacion['numero'], 0, 1, 'C');

    // Datos del cliente y fecha
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(30, 6, 'CLIENTE:', 0, 0);
    $pdf->Cell(0, 6, $cotizacion['cliente_nombre'], 0, 1);
    $pdf->Cell(30, 6, 'RUT:', 0, 0);
    $pdf->Cell(0, 6, $cotizacion['cliente_rut'], 0, 1);
    $pdf->Cell(30, 6, 'FECHA:', 0, 0);
    $pdf->Cell(0, 6, date('d/m/Y', strtotime($cotizacion['fecha'])), 0, 1);

    // Tabla de items
    $pdf->Ln(10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);

    // Encabezados de la tabla
    $pdf->Cell(70, 7, 'Descripción', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Unidad', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Precio', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Largo', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'TOTALES', 1, 1, 'C', true);

    // Items
    $pdf->SetFont('helvetica', '', 10);
    $items = json_decode($cotizacion['items'], true);
    foreach ($items as $item) {
        // Calcular altura necesaria para la descripción
        $height = max(7, $pdf->getStringHeight(70, $item['descripcion']));
        
        $pdf->MultiCell(70, $height, $item['descripcion'], 1, 'L', false, 0);
        $pdf->Cell(20, $height, $item['unidad'], 1, 0, 'C');
        $pdf->Cell(25, $height, number_format($item['cantidad'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(25, $height, '$ ' . number_format($item['precio'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(20, $height, number_format($item['largo'], 2, ',', '.'), 1, 0, 'R');
        $pdf->Cell(25, $height, '$ ' . number_format($item['cantidad'] * $item['precio'] * $item['largo'], 0, ',', '.'), 1, 1, 'R');
    }

    // Totales
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(160, 7, 'VALOR', 1, 0, 'R', true);
    $pdf->Cell(25, 7, '$ ' . number_format($cotizacion['valor_neto'], 0, ',', '.'), 1, 1, 'R');
    $pdf->Cell(160, 7, 'I.V.A. 19%', 1, 0, 'R', true);
    $pdf->Cell(25, 7, '$ ' . number_format($cotizacion['valor_iva'], 0, ',', '.'), 1, 1, 'R');
    $pdf->Cell(160, 7, 'TOTAL', 1, 0, 'R', true);
    $pdf->Cell(25, 7, '$ ' . number_format($cotizacion['total'], 0, ',', '.'), 1, 1, 'R');

    // Observaciones
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'OBSERVACIONES', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    $pdf->Cell(40, 6, 'Condiciones de Pago:', 0, 0);
    $pdf->Cell(0, 6, $cotizacion['condiciones_pago'], 0, 1);

    $pdf->Cell(40, 6, 'Plazo de Entrega:', 0, 0);
    $pdf->Cell(0, 6, $cotizacion['plazo_entrega'], 0, 1);

    $pdf->Cell(40, 6, 'Validez Cotización:', 0, 0);
    $pdf->Cell(0, 6, $cotizacion['validez'], 0, 1);

    if (!empty($cotizacion['notas'])) {
        $pdf->Ln(5);
        $pdf->MultiCell(0, 6, $cotizacion['notas'], 0, 'L');
    }

    // Datos bancarios
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 6, 'Datos Bancarios:', 0, 0);
    $pdf->Cell(0, 6, 'Banco santander N° 0-000-8544702-5 cuenta corriente Rut 77.443.579-4', 0, 1);

    ob_end_clean(); // Limpiar buffer de salida
    // Generar el PDF
    $pdf->Output('Cotizacion_' . $cotizacion['numero'] . '.pdf', 'I');
} catch (Exception $e) {
    ob_end_clean();
    die('Error al generar el PDF: ' . $e->getMessage());
} 