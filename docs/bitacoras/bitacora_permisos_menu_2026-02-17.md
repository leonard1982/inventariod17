Fecha (America/Bogota): 2026-02-17
Hora: 13:14:04
Autor: Codex (GPT-5)

Descripcion corta:
Implementacion de permisos de menu por usuario con administracion exclusiva para admin.

Detalle tecnico:
Se agrego un sistema centralizado de control de acceso por menu en `conecta.php`, incluyendo:
- Catalogo unico de menus del sistema.
- Resolucion de permisos por usuario desde tabla de inventarios (`SN_MENU_PERMISOS`).
- Deteccion de usuario administrador (rol ADMIN/ADMINISTRADOR o usuario ADMIN).
- Filtro de menus permitidos para render en `Principal.php`.
- Bloqueo de acceso por archivo/modulo (incluyendo AJAX) para evitar ingreso directo sin permiso.

Se creo el modulo administrativo:
- `permisos_menu.php`: UI para seleccionar usuario y marcar permisos por menu.
- `permisos_menu_ajax.php`: API para cargar, guardar y restaurar permisos.

Se integro en menu de usuario:
- Nueva opcion `Permisos de menu` visible solo para admin.

Se creo script SQL:
- `02_create_permisos_menu.sql` para tabla `SN_MENU_PERMISOS` en INVENTARIOS.GDB.

Se actualizo `js/scripts.js` para bloquear apertura de modulos no permitidos desde frontend y registrar accion del nuevo modulo.

Archivos afectados:
- conecta.php
- Principal.php
- js/scripts.js
- permisos_menu.php
- permisos_menu_ajax.php
- 02_create_permisos_menu.sql

Motivo del cambio:
Habilitar gestion de accesos por seccion/menu para que el administrador defina que usuario entra o no entra a cada opcion del sistema.

Impacto:
- Control formal de permisos por menu.
- Menus ocultos para usuarios sin permiso.
- Acceso directo por URL bloqueado para modulos mapeados.
- Pantalla administrativa para mantenimiento operativo de permisos.

Pendientes:
- Ejecutar `02_create_permisos_menu.sql` en INVENTARIOS.GDB.
- Configurar permisos iniciales para usuarios operativos.
- Validar con pruebas funcionales por cada perfil.
