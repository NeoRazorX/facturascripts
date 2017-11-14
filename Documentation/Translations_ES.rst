Traducciones
============

Las traducciones van a estar centralizadas desde
http://i18n.facturascripts.com.

El formato escogido es un archivo JSON, con estilo “key”: “value”, donde
**key** es la referéncia a la traducción y el **value** su traducción.

.. code:: json

        {
            "common-error": "Error",
            "facturascripts": "FacturaScripts",
            "file": "Archivo",
            "files": "Archivos",
            "check-table": "Error al comprobar la tabla '0'"
        }

Por el momento, el idioma base es el **Core/Translation/es_ES.json**,
pero posiblemente muy pronto pasará a serlo
**Core/Translation/en_EN.json**. Así que es muy aconsejable añadir
cualquier nueva frase o palabra en ambos archivos. En las traducciones
que se realicen desde la web, sólo se verán las nuevas frases
disponibles que hayan sido insertadas desde el idioma base al hacer un
commit.

Desde la barra de depuración, se ha añadido una nueva pestaña
**Translations** que muestra un listado de las traducciones utilizadas y
en rojo las que no han sido traducidas. De modo que simplifica buscar el
uso de los términos en cada página, sin tener que saber con exactitud
donde se utiliza.

Es preferible que en el momento de añadir nuevo código que utilice
frases que vayan a necesitar ser traducidas, se añadan directamente a
los archivos es_ES.json y en_EN.json para facilitar que cualquier
traductor las tenga disponibles con la máxima disponibilidad posible y
puedan ser traducidas cuanto antes.

Archivos de traducción
----------------------

Para facilitar la interacción con los archivos de traducción a otros
idiomas, se usará una plataforma web, donde cada usuario interesado
puede registrarse y contribuir en el idioma que escoja.

Se desaconseja completamente añadir frases nuevas en otros idiomas, ya
que eso se centraliza en la web para evitar posibles conflictos
posteriores.

Como traducir frases
--------------------

Para traducir frases desde PHP sólo es necesario hacer:

.. code:: php

        $i18n->trans('common-error');
        $this->i18n->trans('common-error');
        static::i18n->trans('check-table', ['clientes']);

Devolviendo:

::

        Error
        Error
        Error al comprobar la tabla 'clientes'

Para traducir frases desde Twig:

.. code:: twig

        {% i18n.trans('common-error') %}
        {% i18n.trans('check-table', ['clientes']) %}

Devolviendo:

::

        Error
        Error al comprobar la tabla 'clientes'

Cualquier frase utilizada durante la ejecución del código PHP y el
renderizado del HTML, quedará recogida y mostrada en la barra de
depuración, exceptuando por ahora, las que se ejecuten en consultas
AJAX.
