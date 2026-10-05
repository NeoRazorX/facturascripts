# Cómo contribuir a FacturaScripts

Gracias por tu interés en mejorar FacturaScripts. Puedes colaborar corrigiendo errores, añadiendo funcionalidades,
escribiendo tests, mejorando la documentación o revisando traducciones.

## Canales de colaboración

- Para notificar un error, utiliza la [página de incidencias](https://facturascripts.com/crear-issue).
- Para dudas de uso, consulta la [documentación](https://facturascripts.com/ayuda) o la
  [comunidad de Discord](https://discord.gg/qKm7j9AaJT).
- Para colaborar con los idiomas, utiliza la [página de traducciones](https://facturascripts.com/traducciones).
- Para informar de una vulnerabilidad, sigue las instrucciones de [SECURITY.md](SECURITY.md). No publiques
  información sensible en una incidencia pública.

Antes de comenzar un cambio, comprueba que no exista ya una incidencia o un pull request que lo resuelva. Si se trata
de una modificación grande, conviene comentarla primero para acordar el enfoque y evitar trabajo duplicado.

## Preparar el entorno

Necesitas PHP 8.1 o superior, Composer y una base de datos MySQL, MariaDB o PostgreSQL. Sigue las instrucciones de
instalación del [README](README.md) y descarga las dependencias:

```bash
composer install
```

Para ejecutar los tests debes tener un `config.php` válido y una base de datos dedicada a pruebas. No utilices una base
de datos de producción, ya que los tests crean, modifican y eliminan datos.

## Flujo de trabajo

1. Haz un fork del repositorio y crea una rama desde `master`.
2. Utiliza una rama descriptiva, por ejemplo `fix/static-files-windows` o `feat/customer-filter`.
3. Limita cada rama y pull request a un único objetivo.
4. Mantén la rama actualizada con `master` y resuelve los conflictos antes de solicitar la revisión.
5. No incluyas archivos generados, dependencias, credenciales ni cambios de formato ajenos al objetivo.

## Código y documentación

- El código PHP sigue PSR-12 con las excepciones definidas en `phpcs.xml`.
- Usa nombres claros y conserva el estilo del código que estés modificando.
- Documenta las clases, contratos y métodos públicos nuevos o modificados.
- Evita romper la API pública. Si el cambio es incompatible, explícalo y acuerda antes cómo realizar la migración.
- No mezcles refactorizaciones generales con una corrección o funcionalidad concreta.
- Las cadenas visibles para el usuario deben utilizar el sistema de traducciones de FacturaScripts.

Puedes comprobar el estilo y corregir automáticamente gran parte de sus problemas con:

```bash
composer cs-check
composer cs-fix
```

Revisa siempre los cambios producidos por `composer cs-fix` antes de incluirlos en el commit.

## Tests y análisis estático

Toda corrección debería incluir un test que reproduzca el error y evite que vuelva a aparecer. Las funcionalidades nuevas
deben cubrir, al menos, su comportamiento principal y los casos límite relevantes.

Antes de enviar el pull request, ejecuta:

```bash
composer test
composer cs-check
composer phpstan
```

Si no puedes ejecutar alguna comprobación, indícalo en la descripción del pull request junto con el motivo y las pruebas
manuales realizadas. No modifiques ni elimines tests únicamente para ocultar un fallo.

## Commits

Escribe commits pequeños, coherentes y con mensajes que expliquen el cambio. Utiliza un prefijo que identifique su tipo:

- `feat`: nueva funcionalidad.
- `fix`: corrección de un error.
- `docs`: documentación.
- `test`: tests.
- `refactor`: reestructuración sin cambiar el comportamiento.
- `chore`: mantenimiento interno.

Por ejemplo:

```text
fix(files): normaliza las rutas estáticas en Windows
```

Cuando el motivo no resulte evidente, añádelo en el cuerpo del commit. No incluyas cambios independientes en un mismo
commit.

## Pull requests

Dirige el pull request a `master` y utiliza un título claro. La descripción debe incluir:

- El problema o necesidad que resuelve.
- La solución aplicada y las decisiones importantes.
- Las comprobaciones automáticas y manuales realizadas.
- Capturas o vídeos cuando cambie la interfaz.
- Posibles incompatibilidades, migraciones o efectos secundarios.
- La incidencia relacionada, si existe.

Revisa tu propio diff antes de enviarlo. Responde a los comentarios de revisión y evita añadir cambios no relacionados
mientras el pull request esté siendo evaluado. Los mantenedores pueden solicitar ajustes o rechazar cambios que no
encajen con el diseño, la compatibilidad o los objetivos del proyecto.

## Licencia y convivencia

Al contribuir aceptas que tus cambios se distribuyan bajo la licencia LGPL-3.0-or-later del proyecto. Mantén una
comunicación respetuosa, céntrate en los aspectos técnicos y ayuda a que la colaboración resulte constructiva para todas
las personas participantes.
