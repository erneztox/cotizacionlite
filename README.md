# Sistema de Cotizaciones

Sistema de cotizaciones desarrollado en PHP con SQLite.

## Configuración en Replit

1. Clona este repositorio en Replit
2. Espera a que se instalen las dependencias (PHP, Composer, SQLite)
3. Ejecuta `composer install` para instalar las dependencias de PHP
4. El servidor se iniciará automáticamente en el puerto 8000

## Estructura del Proyecto

- `index.php` - Página principal para crear cotizaciones
- `lista_cotizaciones.php` - Lista de cotizaciones existentes
- `ver_cotizacion.php` - Vista detallada de una cotización
- `estadisticas.php` - Estadísticas de cotizaciones
- `config.php` - Configuración de la base de datos
- `static/` - Archivos estáticos (CSS, JS, imágenes)

## Características

- Creación de cotizaciones
- Gestión de clientes
- Gestión de productos
- Generación de PDF
- Estadísticas de cotizaciones
- Búsqueda de clientes y productos
- Cálculo automático de totales

## Requisitos

- PHP 7.4 o superior
- SQLite3
- Composer
- TCPDF (instalado vía Composer) 