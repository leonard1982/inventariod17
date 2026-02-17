Fecha (America/Bogota): 2026-02-17
Hora: 14:29:02
Autor: Codex (GPT-5)

Descripcion corta:
Ajuste de login para dejar solo icono y frase aleatoria, y fallback silencioso en indicadores de Inicio.

Detalle tecnico:
- En `index.php` se elimino el bloque de imagen/logo del login.
- Se reemplazo el texto fijo del subtitulo por un contenedor de frase dinamica (`#frase-productividad`).
- En `js/index.js` se implemento `cargarFraseProductividad()` con 30 frases aleatorias orientadas a productividad.
- Se mantuvo intacta la logica de autenticacion, recordatorio de credenciales y mostrar/ocultar contrasena.
- En `dashboard_inicio_ajax.php` se ajusto el DSN PDO Firebird quitando `;charset=UTF8` para mayor compatibilidad con entorno actual.
- En `js/scripts.js` se elimino el mensaje visible "No fue posible cargar indicadores." y se deja fallback neutro en blanco si falla la carga.

Archivos afectados:
- index.php
- css/index.css
- js/index.js
- dashboard_inicio_ajax.php
- js/scripts.js

Motivo del cambio:
Cumplir solicitud de UI mas generica en login y evitar mensaje de error visible en Inicio mientras se estabiliza la carga de indicadores.

Impacto:
- Login mas limpio y reutilizable sin imagen fija.
- Mensaje de acceso cambia en cada carga con frases de productividad.
- Inicio ya no muestra error visual si el endpoint de indicadores falla.

Pendientes:
- Si desea, puedo mostrar las frases por dia (no aleatorias) para consistencia en jornada.
- Si quiere diagnostico completo del endpoint, puedo dejar log tecnico controlado en `log/` cuando falle.
