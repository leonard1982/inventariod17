Fecha (America/Bogota): 2026-02-17
Hora: 20:48:42
Autor: Codex GPT-5

Descripcion corta:
Se completo la Prueba de Entrega (POD) en flujo conductor y se habilito evidencia en PDF por remision.

Detalle tecnico:
Se conectaron los controles del modal de estado en despachos conductor para adjuntar foto, capturar firma en canvas y geolocalizacion.
Se ajusto el envio AJAX a FormData para enviar binarios (foto) y firma base64 junto con lat/lng.
En backend se reforzo validacion POD para estado ENTREGADO y se limito formato de foto a JPG/PNG para compatibilidad de render en PDF.
Se rediseño remision_entrega_pdf.php para incluir estado de la remision, datos POD (fecha/hora, geo, usuario) y anexar imagen de foto/firma cuando existan.
Se agrego fallback de peso total usando SN_GUIAS_DETALLE.PESO cuando la suma por detalle material sea 0.

Archivos afectados:
- despachos_conductor.php
- despachos_conductor_ajax.php
- remision_entrega_pdf.php

Motivo del cambio:
Implementar completamente el punto 3 solicitado (POD con foto, firma, geolocalizacion y PDF final con evidencia).

Impacto:
El conductor ahora puede registrar entrega con evidencia completa y el PDF de remision muestra trazabilidad POD.
Se reduce riesgo de PDF sin soporte de imagen por formatos no compatibles.

Pendientes:
- Probar en dispositivo movil real permisos de geolocalizacion/camara.
- Validar flujo de PDF para remisiones antiguas sin evidencia POD.
