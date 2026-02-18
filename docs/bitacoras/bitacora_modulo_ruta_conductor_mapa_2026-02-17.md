Fecha (America/Bogota): 2026-02-17
Hora: 21:10:48
Autor: Codex GPT-5

Descripcion corta:
Se implemento nuevo modulo de informe en mapa para visualizar la ruta del conductor por puntos de entrega.

Detalle tecnico:
Se creo pantalla `ruta_conductor_mapa.php` con filtros por fecha y conductor, mapa Leaflet y tabla de detalle de remisiones.
Se creo endpoint `ruta_conductor_mapa_ajax.php` para listar conductores y puntos georreferenciados del dia.
El mapa pinta puntos clickeables con popup de remision (guia, cliente, direccion, estado, fecha) y traza lineas por conductor entre puntos consecutivos.
Se agregaron indicadores en pantalla: puntos, remisiones, conductores y distancia aproximada.
Se integro al menu principal y al sistema de permisos mediante alta en catalogo y mapeo de acceso.

Archivos afectados:
- ruta_conductor_mapa.php
- ruta_conductor_mapa_ajax.php
- Principal.php
- conecta.php
- js/scripts.js

Motivo del cambio:
Habilitar reporte visual de ruta entre puntos de entrega y consulta operativa diaria por conductor.

Impacto:
Nuevo item en menu: `Ruta conductor (Mapa)`, configurable desde permisos de menu.
Permite seguimiento geografico diario de entregas y acceso directo a detalle por remision.

Pendientes:
- Ejecutar `06_alter_kardex_geo.sql` en la BD si no existen columnas `SN_LONGITUD/SN_LATITUD`.
