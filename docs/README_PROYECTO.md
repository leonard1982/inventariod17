# Inventario D17

## 1. Descripción
Inventario D17 es una aplicación web en PHP para análisis y operación de inventarios sobre Firebird 2.5. Centraliza reportes de rotación, productos sin movimiento, clasificación ABC, backorder, configuraciones operativas y procesos automáticos (scripts batch/cron en Windows).

## 2. Qué problema resuelve
- Monitoreo de inventario con enfoque en decisiones de compra y traslado.
- Identificación de productos sin rotación y productos con riesgo operativo.
- Gestión de parámetros de negocio por grupo/línea para automatizar pedidos.
- Control de estados de pedido y trazabilidad mediante logs en base de datos.

## 3. URL típica en local
- `http://localhost/evento_inventario/`

## 4. Flujo de uso básico
1. Abrir `index.php` y autenticar usuario.
2. El sistema valida credenciales en `ValidaUser.php`.
3. El usuario entra a `Principal.php`.
4. Desde el menú lateral se cargan módulos vía AJAX (sin recargar toda la página).

## 5. Requisitos técnicos
- Windows + Apache (XAMPP).
- PHP 7.3 (32 bits).
- Firebird 2.5.
- Extensión `interbase`/`ibase` habilitada en PHP.
- Composer/autoload disponible para envío de correos (`phpmailer`).

## 6. Configuración por TXT (sin hardcode de rutas de BD)
La configuración principal de rutas no está embebida en código de conexión central. Se lee desde TXT en la raíz:
- `bd_actual.txt`
- `bd_anterior.txt`
- `bd_inventarios.txt`
- `bd_actual_produccion.txt` (usado por script batch específico)
- `bd_admin.txt`
- `prefijos.txt`
- `servidor_smtp.txt`
- `fecha_inicio_inventario.txt`

Nota: existen módulos heredados con rutas absolutas hardcodeadas para localizar TXT o SMTP. La lógica no fue modificada en esta auditoría, solo documentada.

## 7. Documentación disponible
- `docs/ENTORNO.md`
- `docs/ARQUITECTURA.md`
- `docs/MAPA_DEL_PROYECTO.md`
- `docs/BASE_DE_DATOS.md`
- `docs/FLUJOS.md`
- `docs/SEGURIDAD.md`
- `docs/bitacoras/README.md`

