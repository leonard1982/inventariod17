Fecha (America/Bogota): 2026-02-17
Hora: 21:03:09
Autor: Codex GPT-5

Descripcion corta:
Se migro el guardado de ubicacion POD para usar columnas dedicadas en KARDEX.

Detalle tecnico:
Se retiro el intento de persistir GPS en NOMEMPRESA.
En `guardar_estado_entrega` ahora se valida existencia de columnas `KARDEX.SN_LONGITUD` y `KARDEX.SN_LATITUD`.
Si el estado es ENTREGADO y las columnas no existen, se informa ejecutar el script SQL.
Si existen, se actualiza KARDEX por remision con longitud y latitud en columnas separadas.
Se creo script `06_alter_kardex_geo.sql` para agregar columnas e indices.

Archivos afectados:
- despachos_conductor_ajax.php
- 06_alter_kardex_geo.sql

Motivo del cambio:
NOMEMPRESA pertenece a TERCEROS y no es el destino correcto para coordenadas de entrega.

Impacto:
La ubicacion queda normalizada por remision en KARDEX, lista para consulta/reportes sin parseo de texto.

Pendientes:
- Ejecutar `06_alter_kardex_geo.sql` en cada BD anual activa.
