Fecha (America/Bogota): 2026-02-17
Hora: 14:45:23
Autor: Codex (GPT-5)

Descripcion corta:
Se habilitan KPI de conductor en Inicio movil y se corrige espacio blanco superior en encabezado.

Detalle tecnico:
- En `dashboard_inicio_ajax.php` se ajusto `mobile_conductor`:
  - El bloque queda `habilitado` cuando el usuario tiene permiso de menu `despachosconductor`.
  - Si no existe relacion usuario-conductor, devuelve KPI en cero y un mensaje de contexto.
  - Si es admin, muestra mensaje de vista general.
- En `js/scripts.js` se incluye `movilConductor.mensaje` debajo del subtitulo del bloque movil para explicar por que puede venir en cero.
- En `css/menu_profesional.css` se aplico soporte de safe-area:
  - variable `--safe-top`
  - altura y padding de topbar ajustados a `env(safe-area-inset-top)`
  - recalculo de `sidebar` y `content-shell` para evitar franja blanca superior.
- En `Principal.php` se versiono `menu_profesional.css` para forzar recarga en navegador.

Archivos afectados:
- dashboard_inicio_ajax.php
- js/scripts.js
- css/menu_profesional.css
- Principal.php

Motivo del cambio:
Resolver que en movil no aparecia el panel KPI de conductor en Inicio y eliminar margen/espacio blanco visible sobre el titulo.

Impacto:
- Inicio movil muestra KPI conductor para usuarios con permiso al modulo.
- Si falta parametrizacion de conductor, se informa en subtitulo en lugar de ocultar el bloque.
- El encabezado ocupa correctamente el area superior sin banda blanca.

Pendientes:
- Validar en dispositivo movil real con y sin notch.
- Confirmar que usuarios conductor con TERID ya vinculado muestran valores distintos de cero.
