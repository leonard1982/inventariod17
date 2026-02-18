Fecha (America/Bogota): 2026-02-17
Hora: 17:18:45
Autor: Codex (GPT-5)

Descripcion corta:
Se ajusto el selector de remisiones en Guias (Despachos) con filtros por prefijo y zona multiple, ademas de fecha sin hora en la grilla de candidatas.

Detalle tecnico:
- En UI se agrego filtro de prefijo con opciones fijas: Todos, 00, 01 y 50.
- En UI se agrego selector multiple de zonas para filtrar remisiones candidatas.
- Se creo carga dinamica de zonas desde backend segun prefijo seleccionado.
- En el listado de candidatas la columna Fecha ahora muestra solo fecha (sin hora).
- Se amplio la seccion de candidatas para que el formulario quede menos angosto.
- En backend se proceso el nuevo parametro zonas_json y se aplico filtro IN por zona normalizada por TRIM.
- Se habilito endpoint listar_zonas_filtro_remision para poblar zonas disponibles.

Archivos afectados:
- guias_despachos.php
- guias_despachos_ajax.php

Motivo del cambio:
Implementar filtros operativos solicitados para seleccionar remisiones de forma mas rapida y precisa por prefijo y zona.

Impacto:
Mejora la usabilidad del modulo de guias y reduce tiempo de busqueda de remisiones candidatas sin tocar la logica transaccional principal.

Pendientes:
- Validar en datos reales que las zonas de terceros esten completas y consistentes.
- Ajustar orden o nombres de zonas visibles si negocio lo requiere.
