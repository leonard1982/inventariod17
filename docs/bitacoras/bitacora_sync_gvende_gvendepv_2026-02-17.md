Fecha (America/Bogota): 2026-02-17
Hora: 18:54:15
Autor: Codex (GPT-5)

Descripcion corta:
Sincronizacion de variables GVENDE y GVENDEPV al guardar vinculo conductor por usuario.

Detalle tecnico:
- En permisos_menu_ajax.php, accion guardar_vende_usuario ahora guarda el mismo valor en:
  - GVENDE<USUARIO>
  - GVENDEPV<USUARIO>
- En limpiar_vende_usuario ahora elimina ambas variables.
- En obtener_vende_usuario se mantiene GVENDE como principal y si esta vacia toma fallback de GVENDEPV.

Archivos afectados:
- permisos_menu_ajax.php

Motivo del cambio:
Requerimiento de configuracion para que ambos prefijos de variable queden alineados automaticamente.

Impacto:
Evita inconsistencias entre modulos que consultan GVENDE y modulos heredados que consultan GVENDEPV.

Pendientes:
- Opcional: mostrar en UI el valor de ambas variables para auditoria visual.
