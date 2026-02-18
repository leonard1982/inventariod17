Fecha (America/Bogota): 2026-02-17
Hora: 18:49:31
Autor: Codex (GPT-5)

Descripcion corta:
Correccion del total de peso en la lista principal de guias.

Detalle tecnico:
- Se reemplazo el total por SUM(SN_GUIAS_DETALLE.PESO) en el listado de guias.
- Ahora el total se calcula en linea por detalle de remision: SUM(DEKARDEX.CANMAT/ CANLISTA * MATERIAL.PESO).
- Esto evita que el total dependa de valores historicos guardados en SN_GUIAS_DETALLE.PESO.

Archivos afectados:
- guias_despachos_ajax.php

Motivo del cambio:
En la columna Peso de la lista de guias no aparecia el total real esperado.

Impacto:
La columna Peso en listado de guias refleja el total real por articulos de las remisiones asociadas.

Pendientes:
- Opcional: alinear el mismo calculo en impresion (guia_despacho_print.php).
