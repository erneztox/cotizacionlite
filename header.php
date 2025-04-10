<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cotizaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .header {
            background-color: #f8f9fa;
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
        }
        .logo {
            max-height: 50px;
        }
        .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <img src="static/img/logo.png" alt="Logo" class="logo">
                </div>
                <div class="col-md-9">
                    <nav class="nav justify-content-end">
                        <a class="nav-link" href="index.php">Crear Cotización</a>
                        <a class="nav-link" href="lista_cotizaciones.php">Cotizaciones</a>
                        <a class="nav-link" href="estadisticas.php">Estadísticas</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <div class="container"> 