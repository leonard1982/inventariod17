Fecha (America/Bogota): 2026-02-17
Hora: 20:52:14
Autor: Codex GPT-5

Descripcion corta:
Se ajustaron KPIs para mostrar remision legible (prefijo-numero) y se simplifico la vista de remisiones en guia.

Detalle tecnico:
En Centro KPI se reemplazo la visualizacion de "Remision ID" por "Remision" en secciones de Ruteo y Auditoria, usando datos reales CODPREFIJO+NUMERO desde KARDEX.
En Analitica historica se retiro la tabla "Top productos (MATID)" y se cambio por "Top codigos de prefijo" para analisis por codigo comercial.
En Guias (Despachos), dentro del modal de remisiones de guia, se oculto la columna Telefono y se paso la fecha a formato solo fecha (sin hora).
Se ajustaron colspans y mensajes de carga/vacio/error para mantener consistencia de tabla.

Archivos afectados:
- centro_kpi_ajax.php
- guias_despachos.php

Motivo del cambio:
Aplicar criterios operativos solicitados para legibilidad del negocio: evitar identificadores tecnicos y simplificar visualizacion al agregar remisiones.

Impacto:
KPIs mas comprensibles para usuarios finales y tabla de remisiones mas limpia.
Se mantiene funcionalidad de envio por WhatsApp/PDF sin exponer telefono en la grilla.

Pendientes:
- Validar en UI que exportaciones Excel/PDF del KPI reflejen los nuevos encabezados.
