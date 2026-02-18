Fecha (America/Bogota): 2026-02-17
Hora: 19:02:46
Autor: Codex (GPT-5)

Descripcion corta:
Compatibilidad temporal para bases con CHECK legacy de estados (FINALIZADO) al marcar entregas.

Detalle tecnico:
- En despachos_conductor_ajax.php se implemento cierre de guia con fallback:
  1) intenta estado ENTREGADO
  2) si falla por CHECK legacy, reintenta con FINALIZADO
- En guias_despachos_ajax.php (accion cambiar_estado) se aplico la misma estrategia cuando el estado solicitado es ENTREGADO.
- Se agregaron detectores de error de constraint legacy (CK_SN_GUIAS_ESTADO / CHECK_297).

Archivos afectados:
- despachos_conductor_ajax.php
- guias_despachos_ajax.php

Motivo del cambio:
Error SQL por constraint antigua en SN_GUIAS al marcar entregado desde modulo conductor.

Impacto:
Se evita bloqueo operativo mientras la BD no ejecute la migracion de estados.

Pendientes:
- Ejecutar 04_create_estados_guias_y_vehiculo.sql en todas las bases para unificar estados en ENTREGADO.
