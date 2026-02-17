# Bitácoras de Cambios

## 1. Objetivo
Registrar en formato técnico y trazable todos los cambios del proyecto.

## 2. Formato obligatorio por entrada
- Fecha (America/Bogota)
- Hora
- Autor
- Descripción corta
- Detalle técnico
- Archivos afectados
- Motivo del cambio
- Impacto
- Pendientes

## 3. Plantilla sugerida
```
Fecha (America/Bogota): YYYY-MM-DD
Hora: HH:MM:SS
Autor: Nombre o equipo

Descripción corta:
Texto breve del cambio.

Detalle técnico:
Resumen técnico con decisiones y alcance.

Archivos afectados:
- ruta/archivo1
- ruta/archivo2

Motivo del cambio:
Razón funcional/técnica del ajuste.

Impacto:
Qué mejora, corrige o habilita.

Pendientes:
- Punto 1
- Punto 2
```

## 4. Reglas futuras de trabajo (obligatorias)
1. Todo cambio genera bitácora.
2. Todo commit debe:
   - Estar en español.
   - Ser descriptivo.
   - Incluir fecha (en el mensaje o en la bitácora).
   - Explicar motivo real del cambio.
3. Prohibidos commits genéricos: “Update”, “Fix”, “Cambios”.
4. No exponer credenciales.
5. No hardcodear rutas; todo debe pasar por TXT de configuración.

