Fecha (America/Bogota): 2026-02-17
Hora: 18:39:43
Autor: Codex (GPT-5)

Descripcion corta:
Correccion del calculo de peso por remision usando DEKARDEX y MATERIAL.PESO.

Detalle tecnico:
- Se agrego funcion sqlPesoRemision() en guias_despachos_ajax.php.
- El peso ahora se calcula como SUM(COALESCE(DEKARDEX.CANMAT, DEKARDEX.CANLISTA, 0) * COALESCE(MATERIAL.PESO, 0)) por KARDEXID.
- Se reemplazo uso de KARDEXSELF.PESO en:
  1) listar_candidatas_remision
  2) agregar_remision_guia
- En listar_detalle_guia se prioriza el peso recalculado por remision y se deja fallback a SN_GUIAS_DETALLE.PESO para compatibilidad.

Archivos afectados:
- guias_despachos_ajax.php

Motivo del cambio:
El peso mostrado/no guardado no correspondia al total por articulos de la remision.

Impacto:
El peso por remision en candidatas, detalle y guardado de guia queda alineado con MATERIAL.PESO y cantidades del detalle.

Pendientes:
- Revisar guias historicas creadas antes del ajuste si requieren recalculo persistente de SN_GUIAS_DETALLE.PESO.
