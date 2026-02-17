Fecha (America/Bogota): 2026-02-17  
Hora: 07:49:33  
Autor: Codex (GPT-5)

Descripción corta:
Auditoría técnica integral del proyecto Inventario D17, creación de documentación base en `docs/`, definición formal de bitácoras y preparación para control de cambios con Git.

Detalle técnico:
- Se auditó estructura completa del proyecto desde la raíz real `C:\facilweb\htdocs\evento_inventario`.
- Se identificaron entradas principales (`index.php`, `ValidaUser.php`, `Principal.php`) y carga modular por AJAX.
- Se revisó el núcleo de conexión (`conecta.php`, `php/baseDeDatos.php`) y la estrategia de configuración por TXT en raíz.
- Se cruzaron consultas PHP con `metadata/METADATA_INVENTARIOS.sql` y `metadata/METADATA_CMDISTRI17-2026.sql` para mapear tablas y relaciones principales.
- Se documentaron módulos funcionales, scripts batch (`*.cmd`) y procesos automáticos.
- Se creó estructura documental completa y lineamientos de seguridad/mantenibilidad.
- Se creó `.gitignore` con exclusiones obligatorias para `log/`, `obsoletos/` y `PDF/`.

Archivos afectados:
- `.gitignore`
- `docs/README_PROYECTO.md`
- `docs/ENTORNO.md`
- `docs/ARQUITECTURA.md`
- `docs/MAPA_DEL_PROYECTO.md`
- `docs/BASE_DE_DATOS.md`
- `docs/FLUJOS.md`
- `docs/SEGURIDAD.md`
- `docs/bitacoras/README.md`
- `docs/bitacoras/bitacora_inicial_2026-02-17.md`

Motivo del cambio:
Establecer una línea base técnica confiable para mantenimiento evolutivo del sistema, con trazabilidad documental y disciplina de control de cambios.

Impacto:
- Proyecto documentado de extremo a extremo para onboarding y soporte.
- Mayor claridad en arquitectura, entorno y dependencias críticas (Firebird/TXT).
- Mapa de datos consistente entre código y metadata SQL.
- Base formal de bitácoras para gobernanza de cambios.

Pendientes:
- Ejecutar inicialización Git y primer commit/push al remoto oficial.
- Estandarizar lectura de configuración en módulos con rutas absolutas heredadas.
- Fortalecer seguridad (parametrización SQL, control de sesión por endpoint, manejo de secretos).

