Fecha (America/Bogota): 2026-02-17
Hora: 21:00:54
Autor: Codex GPT-5

Descripcion corta:
Se habilito guardado de coordenadas GPS en NOMEMPRESA del estado de entrega (si existe la columna).

Detalle tecnico:
En el flujo `guardar_estado_entrega` se construye el valor `longitud;latitud` para estado ENTREGADO.
Antes del INSERT se valida metadato de columna `SN_GUIAS_DETALLE_ESTADO.NOMEMPRESA`.
Si existe, se inserta junto con el estado; si no existe, se mantiene insercion anterior sin romper compatibilidad.

Archivos afectados:
- despachos_conductor_ajax.php

Motivo del cambio:
Permitir almacenar coordenadas en el campo NOMEMPRESA con el formato solicitado.

Impacto:
Cuando la estructura tiene columna NOMEMPRESA, la ubicacion queda persistida en ese campo.
No afecta instalaciones donde la columna no existe.

Pendientes:
- Validar en BD de produccion si `SN_GUIAS_DETALLE_ESTADO` ya tiene la columna `NOMEMPRESA`.
