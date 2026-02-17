Fecha (America/Bogota): 2026-02-17
Hora: 13:59:01
Autor: Codex (GPT-5)

Descripcion corta:
Ajustes de layout responsive en panel principal y modulo Despachos conductor.

Detalle tecnico:
- Se forzo el layout de filtros en `despachos_conductor.php` para que los selectores de Guia y Estado remision permanezcan en la misma linea.
- Se compacto tamano de labels/controles para mejorar ajuste horizontal.
- Se elimino el margen visual superior del area de trabajo principal ajustando `padding-top` en `content-shell`.
- Se agrego normalizacion de margenes/paddings globales (`html`, `body`) para reducir espacios blancos no deseados.
- Se configuro titulo responsive en topbar:
  - Desktop: `GESTION DE INVENTARIOS Y DESPACHOS`
  - Movil: `DESPACHOS`

Archivos afectados:
- Principal.php
- css/menu_profesional.css
- js/scripts.js
- despachos_conductor.php

Motivo del cambio:
Corregir visualizacion solicitada por usuario: selectores alineados, sin margen blanco superior y titulo corto en celular.

Impacto:
- Interfaz mas compacta y consistente en escritorio y movil.
- Mejor legibilidad/utilidad operativa en modulo de conductores.

Pendientes:
- Verificar en dispositivos moviles reales (ancho menor a 390px) la comodidad de uso de ambos selectores en una sola linea.
