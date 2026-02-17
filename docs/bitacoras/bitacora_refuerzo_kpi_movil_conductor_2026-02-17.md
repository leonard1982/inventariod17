Fecha (America/Bogota): 2026-02-17
Hora: 14:55:09
Autor: Codex (GPT-5)

Descripcion corta:
Refuerzo de calculo KPI movil conductor para evitar panel en "SIN DATOS".

Detalle tecnico:
- En `dashboard_inicio_ajax.php` se ajusto el bloque `mobile_conductor`:
  - Se agrega mensaje de vinculacion (`Vinculado por: ...`) cuando se identifica contexto de conductor.
  - La serie de estados ya no depende de consultas que pueden devolver vacio; ahora se construye siempre con etiquetas fijas:
    - PENDIENTE
    - ENTREGADO
    - NO_ENTREGADO
    - ENTREGA_PARCIAL
  - Se calculan conteos por estado para el conductor y se alimenta la grafica con esos valores.
  - La serie de "carga por guia" consulta primero guias del mes y si no encuentra, usa fallback historico del conductor.

Archivos afectados:
- dashboard_inicio_ajax.php

Motivo del cambio:
Corregir que en Inicio movil conductor apareciera "SIN DATOS" aun existiendo historial de guias/remisiones.

Impacto:
- El dashboard movil muestra series con estructura estable y menos probabilidad de quedar vacio.
- Mejora la lectura operativa del estado del conductor.

Pendientes:
- Verificacion visual final en dispositivo movil real del usuario.
