# Bitacora Centro KPI

- Fecha (America/Bogota): 2026-02-17
- Hora: 20:32
- Autor: Codex
- Descripcion corta: Creacion de modulo principal "Centro KPI" con submenus internos y permiso unico por item padre.
- Detalle tecnico: Se agrego el menu principal `centrokpi` al catalogo de permisos y al mapa de control de acceso. Se implemento `centro_kpi.php` con 6 submenus KPI (tiempo real, tiempos entre estados, entregadas vs despachadas, ruteo inteligente, auditoria, analitica historica), filtros por fecha y busqueda, cache local, atajos de teclado y exportacion Excel/PDF. Se creo `centro_kpi_ajax.php` con consultas Firebird 2.5 para los indicadores, incluyendo calculos de cumplimiento, tiempos de estado, trazabilidad y analitica por zona/cliente/producto (MATID).
- Archivos afectados: `conecta.php`, `Principal.php`, `js/scripts.js`, `centro_kpi.php`, `centro_kpi_ajax.php`
- Motivo del cambio: Incorporar un tablero profesional de gestion operativa en PC, manteniendo permisos por item principal y sin alterar funcionalidad de modulos existentes.
- Impacto: Nuevo item de menu disponible para usuarios autorizados; el modulo carga en pestana interna y permite analisis y exportes sin cambios en tablas ni procesos transaccionales existentes.
- Pendientes: Validar visual y rendimiento con datos reales de produccion, ajustar thresholds de alertas y priorizacion de ruteo segun criterio operativo final.
