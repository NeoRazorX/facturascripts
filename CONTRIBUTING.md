# Cómo contribuir

Este proyecto es software libre y todos los desarrolladores son bienvenidos.
Puedes consultar la lista de tareas a realizar, la documentación y el chat
para programadores en nuestra página web:
https://www.facturascripts.com/tareas

# Cómo contribuir

Grácias por tu interés en FacturaScripts!

Este documento trata acerca de las Issues (Problemas) y los Pull Request (Peticiones para incorporar cambios).

## Sumario

* [Issues (Problemas)](#issues)
* [Pull Request (Peticiones para incorporar cambios)](#pull-requests)
* [Code Reviews (Revisiones de código)](#code-reviews)

## Issues (Problemas)

En primer lugar, es necesario comprobar si tu código está actualizado: 
* Todavía está soportada tu versión?
* Estás utilizando la última versión?

Las Issues de GitHub son para los **problemas**, lo opuesto a preguntar como 
utilizar FacturaScripts. Si no estás seguro de si se trata de un bug, o 
simplemente quieres preguntarlo, puedes hacerlo desde [La comunidad de FacturaScripts](https://www.facturascripts.com/feedback).

Entonces, si realmente se trata de un fallo, puedes reportarlo a través de Github 
siguiendo estos puntos y prestando atención con ellos:

* Comprueba que el fallo no haya sido reportado ya!
* El título debe resumir el problema con claridad.
* Una descripción de como reproducir el problema. Intentando hacer frases 
concretas e indicando punto a punto el proceso. Pegar un mensaje de error 
no suele ser suficiente.
* Si el problema es una página de error, puedes adjuntarnos el registro que genera 
FacturaScripts mediante la PHPDebugBar. **No te limítes a hacer una captura de pantalla**, 
los buscadores indexan texto pero no imágenes, y eso no ayudará a otros a encontrar 
el mismo problema.
* Las capturas de pantalla pueden considerarse datos adicionales, pero de por si, 
no son suficientes y por eso se requiere una descripción. Es muy recomendable 
proporcionar capturas de pantalla cuando se reportan fallos relacionados con la 
interfaz de usuario.
* Si necesitas introducir código, asegurate que sabes como conseguir coloración 
sintáctica, en particular con [los bloques de código cercado](https://help.github.com/articles/creating-and-highlighting-code-blocks/).
Si crees que el código es demasiado largo, puedes utilizar servicios externos como
https://pastebin.com/ o https://gist.github.com/ . Y si esto no fuera suficiente, 
también puedes crear un repositorio para mostrar el fallo.

> _NOTA:_ No dudes en proporcionar tanta información como sea posible: 
> Sistema operativo, versión de PHP, extensiones de PHP, plugins de FacturaScripts...


## Pull Requests (Peticiones para incorporar cambios)

Todos los colaboradores de FacturaScripts estarán encantados de revisar tus peticiones! :smile:

Pero por favor, lee lo siguiente antes:

### El contenido

#### Estilo de código

Cada proyecto sigue los estándares [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/)
y [Symfony Coding Standards](http://symfony.com/doc/current/contributing/code/standards.html) para el estilo de código,
[PSR-4](http://www.php-fig.org/psr/psr-4/) para el autoloading.

Por favor, [instala PHP Coding Standard Fixer](http://cs.sensiolabs.org/#installation) y 
ejecutalo con este comando antes de commitear tus modificaciones:

```bash
php-cs-fixer fix --verbose
```

O puedes utilizar los plugins existentes para los diferentes IDEs, que lo hacen en tiempo real:
* [NetBeans](http://plugins.netbeans.org/plugin/49042/php-cs-fixer)
* [Atom](https://atom.io/packages/php-cs-fixer)
* [PHPStorm](https://www.jetbrains.com/help/phpstorm/code-sniffer.html)

#### La documentación

La documentación es algo que nos resulta imprescindible a todos para entender mejor como utilizar funciones de código 
realizado por otros, o incluso para entender que hicimos nosotros mismos hace algún tiempo. 
Entonces, porqué no documentar tus aportes para que los demás también lo tengan más sencillo?

En nuestro caso particular puede revisarse por ejemplo cualquier clase en [la base del núcleo de FacturaScripts](https://github.com/NeoRazorX/facturascripts/tree/master/Core/Base) 
para comprobar que prácticamente todo, por no decir todo, está comentado. De modo que cualquiera 
puede documentarse rápidamente y saber que y como debe utilizarlo.

A medida que un proyecto crece y hay más y más contribuidores, esto se vuelve esencial, porqué 
es lo que nos permite conseguir una mejor integración entre distintas partes sin tener que estar 
consultando directamente a quien realizó dicho código, a menos que se requiera algún tipo de 
aclaración, que en dicho caso sería indicativo de que es pertienente mejorar la documentación 
en cuestión.

Como puedo generarme la documentación?

Hay varias formas/alternativas aunque termina siendo lo mismo:
- Con (ApiGen)[https://netbeans.org/kb/docs/php/screencast-apigen.html]:
- Con (phpDocumentor)[https://netbeans.org/kb/docs/php/screencast-phpdoc.html]: 



#### Los tests (Comprobaciones)

Si tu PR (Pull Request) contiene una corrección, las pruebes deben ser añadidas para probar que el error no se reproduzca.

Si tu PR contiene una adición, una nueva característica, éste debe quedar totalmente cubierto por las pruebas.

Algunas reglas que tienen que ser respetadas sobre las pruebas:

* Todos los métodos de prueba deben ser prefijados con `test`. Por ejemplo: `public function testItReturnsNull()`.
* Todos los métodos de prueba deben estar en formato camel case.
* La mayoría de las veces, las clases de prueba deben tener el mismo nombre que 
la clase a la que va dirigida y estar sufijadas con `Test`.

### Escribiendo un Pull Request

#### El título

Idealmente, un Pull Request debe referirse a **sólo un** objetivo, así los cambios 
independendientes se pueden combinar con rapidez.

Si quieres por ejemplo, corregir un error tipográfico y mejorar el rendimiento de un 
proceso, debes intentar en lo posible hacerlo en PR **separados**, así podemos 
incorporar uno rápidamente mientras el otro puede que se discuta.

El objetivo es obtener un registro de cambios limpio y hacer que una reversión sea fácil.

Si has encontrado un fallo/error tipográfico al escribir tus cambios que no están 
relacionados con tu trabajo, por favor haz otro Pull Request para ello. En algunos 
casos raros, te verás forzado a hacerlo en el mismo PR. En este tipo de situaciones,  
por favor añade un comentario en tu PR explicando porque debe ser así.


#### El registro de cambios

Por cada PR, se debe proporcionar un registro de cambios.

Hay muy pocas cosas en las que no sea necesario escribirlo:
* Cuando corriges un fallo en una característica no liberada.
* Cuando tu PR es relativo a sólo la documentación (corrección o mejora).

**No** edites el archivo `CHANGELOG.md` directamente si existe, el registro de 
cambios puede utilizarse al momento de liberar una nueva versión para indicar 
todos los cambios acumulados.

En las notas se pueden utilizar las siguientes secciones:
* `Añadido` para nuevas características.
* `Cambiado` para indicar cambios en funcionalidades existentes.
* `Obsoleto` para características que han pasado a estar obsoletas y que serán eliminadas.
* `Eliminado` para características obsoletas que han sido eliminadas.
* `Corregido` para cualquier corrección de errores.
* `Seguridad` para invitar a los usuarios a actualizar en caso de vulnerabilidades.

Esto facilita que cualquier usuario entienda facilmente todos los cambios que le 
ofrece la actualización, y así tener más claro si le resulta urgente o no actualizar.

Para más información acerca del formato de changelog seguido: [keepachangelog.com](http://keepachangelog.com/)

#### La rama base

Antes de escribir un PR, debes comprobar a que branch (rama) van dirigídos tus cambios.
 
Cada proyecto sigue el convenio [semver](http://semver.org/) para administrar los lanzamientos.

Por ahora se está utilizando principalmente la rama master, pero es posible que esto cambie.

En algunos casos, puede que sea necesario advertir a los usuarios y/o programadores que 
algunas cosas van a cambiar, y recomendar una nueva forma de hacerlo. Puedes hacerlo 
lanzando un trigger para ese tipo de error de la siguiente forma:

```php
<?php
if (/* Alguna condición para mostrar al usuario que está utilizando la forma anterior */) {
    @trigger_error(
        'El metodo '.__METHOD__.' está obsoleto desde 2.x, será eliminado en 3.0. '.
        'En su lugar utiliza FooClass::barMethod().',
        E_USER_DEPRECATED
    );
} else {
    // Nueva forma de hacer las cosas
}
```

Adicionalmente, y cuando sea aplicable, debes utilizar la etiqueta `@deprecated` en las 
clases o métodos que quieras marcar como obsoletos, con un mensaje directo a los usuarios 
finales (en comparación con otros colaboradores).

```php
/**
 * NEXT_MAJOR: Eliminar este método
 *
 * @deprecated desde 3.x, será eliminado en 4.0. En su lugar utiliza Foo::bar.
 */
public function baz()
{
}
```

En este caso, los tests unitarios mostrarán una advertencia. 

Si no estás seguro de lo que debes hacer, no dudes en abrir un Issue acerca de tu PR.

#### El mensaje de commit

FacturaScripts es un proyecto que está creciendo y cada vez llegan más contribuidores, 
y una gran parte de este trabajo es ser capaz de entender el código en todo momento, 
ya sea en el momento de revisar un PR o mirando el historial. Buenos mensajes de commit 
resultan cruciales para lograr este objetivo.

También hay algunos artículos (o incluso webs de propósito único) sobre esto,
no podemos recomendar suficiente los siguientes:

* http://rakeroutes.com/blog/deliberate-git
* http://stopwritingramblingcommitmessages.com
* http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html

Como resúmen de todos ellos, el mensaje del commit debe ser claro y conciso y como no, 
relacionado con el contenido del PR.

La primera línea del commit debe ser corta, mantenla por debajo de los 50 carácteres. 
Debe ser concisa pero *precisa* con lo que dices. El resto de líneas, si las necesitas, 
pueden contener una descripción detallada de *porqué* lo has hecho.

Mensaje de ejemplo malo:

```
Actualizado README.md
```

Mensaje de ejemplo bueno:

```
Documento de como instalar el proyecto
```

También, cuando especifícas que has hecho omite mensajes del tipo "Corregido fallo 
en tal y cual característica". Indicando que has corregido algo implica que lo que 
había antes estaba mal y lo que has hecho está bien, y no tiene porqué ser siempre 
cierto. En su lugar, intenta realizar mensajes que no sean cuestionables acerca de 
los cambios que realices, sin opiniones. Entonces, en la descripción del commit, 
puedes explicar porqué lo has hecho y como soluciona algo.

```
Llamar a foo::bar() en lugar de bar::baz()

Esto corregi un error que surge cuando se hace esto o aquello, porque 
baz() necesita un condesador de flujo objeto que puede no ser definido.
Corrige #42
```

La descripción es opcional, pero muy recomendable. Puede ser preguntada por el 
equipo si resulta necesária. Un PR puede derivar en conversaciones complicadas, 
difíciles de leer, con muchos enlaces a otras webs.

El mensaje debe ser capaz de vivir sin lo que hayas dicho en el PR, e idealmente 
debe resumir de forma clara, de forma que la gente no necesite abrir el navegador 
para entender lo que has dicho.
Enlaces a PRs/Issues y referencias externas son bienvenidos, pero pueden no ser 
considerados suficientes. Cuando referencias a una Issue, asegurate de utilizar 
una de las palabras claves descritas en [el artículo dedicado de github](https://help.github.com/articles/closing-issues-via-commit-messages/).

Buen mensajes de commit con descripción:

```
Cambiado el color de fondo de la web UI a rosa

Esto es un consenso realizado en #4242 en adición a #1337.

Estamos de acuerdo que el color blanco es aburrido y es un deja vu. Rosa es la nueva forma de hacer.
```
(Obviamente, este commit es falso. :wink:)

## Code Reviews (Revisiones de código)

Preparar un PR hasta que esté listo para unirse es una contribución por si misma.
De hecho, ¿Porqué contribuir con otro PR si puede que ya haya cientos en espera de ser revisados y aprobados?
Al realizar esta tarea, tratarás de acelerar este proceso, asegurándote de que la unión puede hacerse con tranquilidad.

### Comentando en un PR

Antes de hacer nada y exponer los detalles del PR, se debe tratar de verlo de forma general, 
para expresar mejor de que trata el PR. Si el PR consiste en corregir un error, lee el error primero.
Esto es para evitar que el revisor tenga que reelaborar el PR y luego incorporarlo.

Cosas a buscar:

- Documentos que faltan: Esto es lo primero que se debe buscar. Si crees que el PR carece de
documentos, pregunta por ellos, ya que será mejor en el momento de la revisión si lo entiendes
mejor, y la documentación ayuda mucho.
- Tests que faltan:  Anima a la gente a realizar tests, aunque lo ideal es que se hagan tests 
para todo, en algunas situaciones no todo es sencillor de testear, mantén esto en mente.
- Partes de código poco claras: haz código claro, usa variables o nombres de clases apropiadas, 
o usas nombre como `data`, `result`, `LoqueseaManager`, `LoqueseaService`? Los nombres de excepciones 
siguen siendo significativos si quitas el sufijo `Exception`? Tienen todas las excepciones un 
mensaje personalizado?
Está intentando el contribuyente a ser inteligente o claro?
- Violaciones de los principios [SOLID][solid]:
    - S: Si una clase tiene unas 3000 líneas, puede que haga demasiadas cosas?
    - O: ¿Hay una sentencia swith grande que podría crecer en un futuro?
    - L: ¿El programa se comporta razonable al cambiar una clase con una clase hija?
    - I: ¿Son las interfaces pequeñas y fáciles de implementar? Si no es así, ¿Pueden dividirse en interfaces más pequeñas?
    - D: Esta el nombre de una clase hardcodeado en otra clase, con la palabra clave `new` o una llamada estática?
- Faltas gramaticales/ortográficas, incluidos en los mensajes de commits o las notas de UPGRADE/CHANGELOG.
- Modificaciones de dependencias: se introdujo algo nuevo, si es así ¿merece la pena?

[solid]: https://en.wikipedia.org/wiki/SOLID_(object-oriented_design)

No se debe dejar una piedra sin mover. Cuando tengas una duda, pregunta por una aclaración. 
Si la aclaración parece útil, y no aparece en un comentario en el código o en un mensaje de 
commit, indicalo y/o haz uso de squash-merge para personalizar el mensaje del commit.
Idealmente, el historial del proyecto debe ser entendible sin una conexión a internet, y 
el PR debe ser suficientemente claro sin tener que hechar un vistazo a los cambios.

Además, asegúrate de que tu feedback es útil, es importante mantener las cosas en marcha, por 


### Revisando PRs con varios commits

Si hay varias entregas para un PR, asegúrate de revisarlos commit a commit, de forma que 
puedas comprobar que los mensajes del commit, y asegurate de que son cambios independientes y 
atómicos.

### Merging

No unas código que has escrito tu mismo. No unas un código que has revisado tu sólo, en 
su lugar, aprueba código que también haya sido revisado y aprobado por otros revisores. 
Si sólo hay un commit en el PR, es preferible la función squash, de lo contrario, utiliza 
un merge.
Y finalmente, utiliz el sentido común: si ves un PR de un error tipográfico, o si hay una 
situación (commit defectuooso, requiere revertir) tal vez se pueda combinar directamente.

### Se agradable con el contribuidor

Agradeceles sus contribuciones. Animales si crees que va a ser un proceso largo.
En resumen, intenta que quieran contribuir de nuevo. Si se encuentran bloqueados, 
intenta proporcionar ayuda con una solución, o contacta con alguien que pueda ayudar.