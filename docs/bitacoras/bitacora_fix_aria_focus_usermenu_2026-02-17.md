Fecha (America/Bogota): 2026-02-17
Hora: 16:55:44
Autor: Codex (GPT-5)

Descripcion corta:
Correccion de warning ARIA en menu de usuario por foco retenido.

Detalle tecnico:
- En `js/scripts.js` se ajusto `alternarMenuUsuario(...)` para manejar accesibilidad correctamente:
  - Al abrir: remueve `inert`, `aria-hidden=false`, `aria-expanded=true`.
  - Al cerrar: si el foco esta dentro del dropdown, lo mueve al boton `#userMenuToggle` antes de ocultar.
  - Al cerrar: aplica `aria-hidden=true` + `inert` para evitar foco en elementos ocultos.
- Se agrego inicializacion al cargar la pagina para que `#userMenuDropdown` arranque con `inert` cuando este oculto.

Archivos afectados:
- js/scripts.js

Motivo del cambio:
Eliminar warning de accesibilidad: "Blocked aria-hidden on an element because its descendant retained focus" al cerrar el menu de usuario.

Impacto:
- Se elimina conflicto ARIA de foco retenido.
- Mejor comportamiento en lectores de pantalla y navegacion por teclado.

Pendientes:
- Validar en navegador movil Android y en escritorio que no reaparezca el warning al abrir/cerrar menu rapidamente.
