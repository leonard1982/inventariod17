Fecha (America/Bogota): 2026-02-17
Hora: 14:52:40
Autor: Codex (GPT-5)

Descripcion corta:
Correccion de caracter FEFF visible en inicio y ajuste de habilitacion KPI movil conductor.

Detalle tecnico:
- Se detecto BOM UTF-8 al inicio de `conecta.php` e `index.php`, que generaba caracter invisible (`&#xFEFF;`) renderizado al cargar `Principal`.
- Se removio BOM binario de ambos archivos para eliminar el espacio/texto fantasma encima del header.
- Se mantuvo el ajuste de `dashboard_inicio_ajax.php` para que el bloque KPI movil conductor quede habilitado para usuario autenticado, con mensaje contextual si falta relacion usuario-conductor.

Archivos afectados:
- conecta.php
- index.php
- dashboard_inicio_ajax.php

Motivo del cambio:
Resolver espacio visual sobre el header causado por BOM y mejorar visibilidad del bloque KPI movil conductor.

Impacto:
- El caracter `&#xFEFF;` ya no debe aparecer en el `body`.
- Inicio queda sin espacio fantasma superior asociado a ese caracter.
- El bloque KPI movil conductor puede mostrarse aunque no exista vinculo TERID (en cero con mensaje).

Pendientes:
- Verificar en navegador del usuario con recarga fuerte.
