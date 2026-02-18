Fecha (America/Bogota): 2026-02-17
Hora: 21:14:33
Autor: Codex GPT-5

Descripcion corta:
Se agrego exportacion PDF al modulo Ruta conductor con mini-mapa y tabla de puntos.

Detalle tecnico:
En `ruta_conductor_mapa.php` se agrego boton PDF que abre `ruta_conductor_mapa_pdf.php` con filtros actuales (fecha y conductor).
Se implemento `ruta_conductor_mapa_pdf.php` usando FPDF para generar:
- Encabezado de reporte por fecha/filtro
- Mini-mapa trazado (lineas y puntos por conductor)
- Resumen por conductor (puntos y km aproximados)
- Tabla detallada de remisiones y coordenadas
Se agrego control de acceso al PDF en `conecta.php` asociado al menu `rutaconductor`.

Archivos afectados:
- ruta_conductor_mapa.php
- ruta_conductor_mapa_pdf.php
- conecta.php

Motivo del cambio:
Permitir evidencia imprimible/exportable del recorrido diario de entregas por conductor.

Impacto:
El usuario puede generar informe PDF desde el mismo modulo de mapa sin perder filtros.

Pendientes:
- Ajustar formato visual del PDF segun feedback de operacion (logos, tamanos, columnas).
