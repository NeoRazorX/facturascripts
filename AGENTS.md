## Estructura de carpetas relevante
- Carpeta de código: `Code`
- Carpeta de pruebas: `Test`

Nota: Todas las implementaciones y cambios de lógica deben ubicarse bajo `Code`. Las pruebas unitarias e integraciones deben residir bajo `Test`.

## Carpetas que deben ignorar los agentes
Los agentes deben ignorar por completo el contenido de las siguientes carpetas (no leer, no modificar, no mover, no borrar y no usar como fuente de verdad):
- `Dinamic`
- `MyFiles`
- `Plugins`

Esto incluye subcarpetas y archivos en su interior. Cualquier artefacto temporal o de terceros debe permanecer fuera del alcance de modificación.

## Flujo de trabajo recomendado para agentes
1. Leer y comprender la tarea o issue asignado.
2. Localizar los módulos afectados dentro de `Code` y diseñar el cambio más pequeño posible que cumpla el objetivo.
3. Implementar los cambios únicamente dentro de `Code`.
4. Escribir o actualizar pruebas dentro de `Test` que validen el comportamiento esperado.
5. Ejecutar las pruebas localmente y asegurar que pasan.
6. Preparar un resumen de cambios y supuestos adoptados.

## Buenas prácticas
- Hacer cambios mínimos y atómicos.
- Mantener la compatibilidad retroactiva cuando sea posible.
- Evitar dependencias innecesarias.
- No exponer información sensible.
- Documentar brevemente decisiones y limitaciones dentro de comentarios o notas de commit.

## Estándares de código
- Seguir las convenciones existentes del proyecto.
- Mantener consistencia en nombres, formato y estructura.
- Agregar comentarios solo donde aclaren intención o decisiones no obvias.

## Pruebas
- Toda nueva funcionalidad en `Code` debe contar con pruebas en `Test`.
- Las pruebas deben cubrir casos felices, errores esperados y bordes relevantes.

## Restricciones
- No modificar ni depender del contenido en `Dinamic`, `Plugins` y `MyFiles`.
- No introducir cambios fuera de `Code` y `Test` salvo documentación.

## Comunicación
- Si un requerimiento contradice estas pautas, solicitar aclaración antes de proceder.
- Registrar cualquier ambigüedad encontrada y la resolución adoptada en la descripción del cambio.
