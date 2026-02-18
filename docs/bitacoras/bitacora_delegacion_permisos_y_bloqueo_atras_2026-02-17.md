Fecha (America/Bogota): 2026-02-17
Hora: 16:51:53
Autor: Codex (GPT-5)

Descripcion corta:
Delegacion de "Permisos de menu", control masivo todos/ninguno y bloqueo de navegacion hacia atras.

Detalle tecnico:
- En `conecta.php`:
  - Menu `permisosmenu` queda marcado como `solo_admin` y `delegable_admin`.
  - Se agregaron funciones:
    - `usuarioTienePermisoExplicitoMenu(...)`
    - `usuarioPuedeAdministrarPermisosMenu(...)`
  - En `obtenerMenusPermitidosUsuario(...)`, para menus `solo_admin` delegables se exige permiso explicito (no aplica acceso por default sin configuracion).
- En `permisos_menu.php`:
  - Se reemplazo validacion estricta de admin por `usuarioPuedeAdministrarPermisosMenu(...)`.
  - Se actualizo etiqueta visual a `ADMIN/DELEGABLE` para menus delegables.
  - Se agregaron switches `Todos` y `Ninguno` para chequeo masivo.
  - Se ajusto JS para mantener sincronia entre botones masivos y switches.
- En `permisos_menu_ajax.php`:
  - Se reemplazo validacion estricta de admin por `usuarioPuedeAdministrarPermisosMenu(...)`.
  - Al guardar permisos, menus `solo_admin` delegables (caso `permisosmenu`) ya no se fuerzan a `N` para usuarios no admin.
- En `js/scripts.js`:
  - Se agrego `iniciarBloqueoNavegacionAtras()` con:
    - bloqueo de `popstate` (boton atras navegador / Android)
    - bloqueo de `Alt+Left`
    - bloqueo de `Backspace` fuera de campos editables
    - bloqueo de tecla `BrowserBack`
  - Se inicializa al cargar el panel principal.

Archivos afectados:
- conecta.php
- permisos_menu.php
- permisos_menu_ajax.php
- js/scripts.js

Motivo del cambio:
Permitir delegar administracion de permisos a otro usuario, facilitar marcado total/ninguno y evitar salida accidental por navegacion hacia atras en PC/Android.

Impacto:
- Un admin puede asignar `Permisos de menu` a otro usuario para que gestione permisos.
- La opcion no queda abierta por default a todos: requiere permiso explicito para no-admin.
- Mejor control masivo de checks en UI.
- Se reduce riesgo de perder contexto por retroceso del navegador/teclado.

Pendientes:
- Prueba funcional en Android real para confirmar experiencia del boton atras segun navegador/dispositivo.
