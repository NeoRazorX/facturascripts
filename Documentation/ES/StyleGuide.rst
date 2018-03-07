.. title:: Guía de Estilo
.. highlight:: rst

##############
Guía de Estilo
##############

Esta página cubre las convenciones para la escritura y el uso de la sintaxis de
marcado reStructuredText (RST) y más concretamente para la creación y mantenimiento
de la documentación del proyecto Facturascripts 2018.


Convenciones
============

- La identación se realizará mediante cuatro (4) espacios en blanco.
- Las líneas no deberán superar una longitud de ciento veinte carácteres (120).
- Se deberá usar fuente itálica para los nombres de botones y/o menús.
- Se evitará el uso de carácteres Unicode.


Encabezados
===========

El encabezado de "Título de documento" debe ser usado para páginas de contenido o índices.

Cada uno de los archivos ``.rst`` deben contener un encabezado ``*``.

.. code-block:: rst

     ####################
     Título del documento
     ####################

     **********************
     Capítulo del documento
     **********************

     Sección del documento
     =====================

     Subsección del documento
     ------------------------

     Subsubsection del documento
     ^^^^^^^^^^^^^^^^^^^^^^^^^^^

     Párrafo del documento
     """""""""""""""""""""



Estilo del texto
================

Las siguientes son marcas útiles para el diseño de texto::

   *italic*
   **bold**
   ``literal``


Puede encontrar más información sobre cómo diseñar los diversos elementos de la documentación y sobre cómo agregar listas, tablas,
imágenes y bloques de código en el sitio oficial de sphinx:

`Overview on ReStructured Text <http://www.sphinx-doc.org/en/stable/rest.html>`__

`Sphinx reference <http://www.sphinx-doc.org/en/stable/markup/>`__


Elementos UI
============

- ``:kbd:`LMB``` -- accesos directos de teclado y ratón.
- ``*Mirror*`` -- etiquetas identificativas.
- ``:menuselection:`3D View --> Add --> Mesh --> Monkey``` -- menus.


Código de ejemplo
=================

Se admite el resaltado de sintaxis si se proporciona el lenguaje de programación,
y los números de línea se pueden mostrar incluyendo la opción ``:linenos:``::

   .. code-block:: php
      :linenos:

      use MyModule
      public function myFunction()
      {
          ...
      }


Imágenes
========

Usaremos la directiva ``.. image::`` seguida de la url de la imágen que deseamos incluir.
También podemos configurar el destino en caso de que el usuario haga click sobre la imágen
y el texto alternativo o explicatorio::

   .. image:: /Images/image_demo1.jpg
       :target: http://www.facturascripts.com
       :alt: Facturascripts Website

Files
-----

Formato
   Usar ``.png`` para imágenes que contienen colores sólidos,
   y ``.jpg`` para imágenes con muchas variaciones de colores, como fotos o imágenes de alta resolución.

   No usar archivos animados como ``.gif`` o similares. Si es necesario utilizar videos.

Nombrado
   Para nombrar los archivos usar subrayado para separar los capítulos y secciones,
   y usar el guión para separar las secciones que contengan dos o más palabras en el título.

   No usar carácteres especiales o espacios en ningún caso.


Guía de uso
-----------

- Evite especificar la resolución de la imagen o su alineación, para que el sitio web pueda manejar las imágenes de forma coherente,
   y proporcionar la mejor distribución en diferentes tamaños de pantalla.
- Al documentar un panel o sección de la IU, es mejor usar una sola imagen que muestre todas las áreas
   relevantes (en lugar de varias imágenes para cada icono o botón)
   ubicado en la parte superior de la sección que está escribiendo,
   y luego explica las características en el orden en que aparecen en la imagen.

  .. note::

     Es importante que el manual pueda mantenerse a largo plazo.
     La interfaz de usuario y las opciones de herramienta cambian, así que trate de evitar
     tener muchas imágenes (cuando no son especialmente necesarias).
     De lo contrario, esto se convierte en una gran carga de mantenimiento.


Vídeos
======

Los vídeos con origen en YouTube\ :sup:`â„¢` y Vimeo\ :sup:`â„¢` pueden ser incluidos usando las directrices::

   .. youtube:: ID

   .. vimeo:: ID

El identificador o ``ID`` del vídeo se puede encontrar dentro de la URL:

- El ID para ``https://www.youtube.com/watch?v=Ge2Kwy5EGE0`` is ``Ge2Kwy5EGE0``
- El ID para ``https://vimeo.com/15837189`` is ``15837189``


Guía de uso
-----------

- Evite agregar videos que dependen de la voz, ya que es difícil de traducir.
- No incruste videos tutoriales como medio para explicar una característica,
la escritura misma debe explicarla adecuadamente (aunque puede incluir un enlace
al video en la parte inferior de la página, bajo el encabezado ``Tutoriales``).


Referencias cruzadas y vinculación
==================================

Puede vincular a otro documento o parte del manual con::

   :doc:`El título </section/path/to/file>`

Para vincular a una sección específica en otro documento (o el mismo), las etiquetas explícitas están disponibles ::

   .. _sample-label:

   [section or image to reference]

   Some text :ref:`Optional Title <sample-label>`

Enlazando a un título en el mismo archivo::

   Titles are Targets
   ==================

   Body text.

   Implicit references, like `Titles are Targets`_

Vinculación con el mundo exterior ::

   `Blender Website <https://www.blender.org>`__
