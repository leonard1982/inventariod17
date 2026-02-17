Fecha (America/Bogota): 2026-02-17
Hora: 14:31:47
Autor: Codex (GPT-5)

Descripcion corta:
Se preparo el login para instalacion tipo app en Android y Windows (PWA).

Detalle tecnico:
- Se agrego configuracion PWA en `index.php`:
  - `manifest.webmanifest`
  - meta tags de app movil (`theme-color`, `mobile-web-app-capable`, `apple-mobile-web-app-capable`)
  - `apple-touch-icon`
  - boton `Instalar app` oculto por defecto.
- Se implemento en `js/index.js`:
  - captura de `beforeinstallprompt`
  - disparo de instalacion manual desde el boton
  - manejo de `appinstalled`
  - registro de `service worker` (`sw.js`).
- Se creo `sw.js` con cache basico de recursos de login y actualizacion de cache por version.
- Se creo `manifest.webmanifest` con nombre, iconos, scope y `display: standalone`.
- Se generaron iconos PWA:
  - `imagenes/pwa-icon-192.png`
  - `imagenes/pwa-icon-512.png`

Archivos afectados:
- index.php
- css/index.css
- js/index.js
- manifest.webmanifest
- sw.js
- imagenes/pwa-icon-192.png
- imagenes/pwa-icon-512.png

Motivo del cambio:
Permitir instalacion de la app en Android y Windows desde el navegador.

Impacto:
- La pantalla de login queda lista para flujo de instalacion PWA.
- El navegador puede ofrecer instalacion cuando cumpla condiciones del entorno.

Pendientes:
- Validar instalacion real en Android (Chrome) y Windows (Edge/Chrome).
- Confirmar acceso bajo HTTPS o localhost, requisito de PWA para instalacion completa.
