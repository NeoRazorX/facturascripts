.. title:: Style Guide
.. highlight:: rst


###########
Style Guide
###########

This page covers the conventions for writing and using the syntax of
marked reStructuredText (RST) and more specifically for the creation and maintenance
of the documentation of the 2018 Facturascripts project.

Conventions
===========

- The indentation will be made through four (4) blank spaces.
- The lines must not exceed a length of one hundred and twenty characters (120).
- Italic font should be used for the names of buttons and / or menus.
- The use of Unicode characters will be avoided.


Headings
========

The heading of "Document Title" should be used for content pages or indexes.

Each of the `` .rst`` files must contain a `` * `` header.

.. code-block:: rst

   ################
    Document Part
   ################

   ****************
   Document Chapter
   ****************

   Document Section
   ================

   Document Subsection
   -------------------

   Document Subsubsection
   ^^^^^^^^^^^^^^^^^^^^^^

   Document Paragraph
   """"""""""""""""""



Text Styling
============

The following are useful marks for text design:

    * italic *
    ** bold **
    `literal`


You can find more information on how to design the various elements of the documentation and on how to add lists, tables,
images and code blocks on the official site of sphinx:

`Overview on ReStructured Text <http://www.sphinx-doc.org/en/stable/rest.html>`__

`Sphinx reference <http://www.sphinx-doc.org/en/stable/markup/>`__


Interface Elements
==================

- ``:kbd:`LMB``` -- keyboard and mouse shortcuts.
- ``*Mirror*`` -- interface labels.
- ``:menuselection:`3D View --> Add --> Mesh --> Monkey``` -- menus.


Code Samples
============

Syntax highlighting is supported if the programming language is provided,
and line numbers can be displayed including the option ``: linenos: `` ::

  .. code-block:: php
     :linenos:

     use MyModule
     public function myFunction()
     {
         ...
     }


Images
======

We will use the directive ``.. image::`` followed by the url of the image that we want to include.
We can also configure the destination in case the user clicks on the image and the alternative or explanatory text::

    .. image :: /Images/image_demo1.jpg
        : target: http://www.facturascripts.com
        : alt: Facturascripts Website

Files
-----

Format
    Use ``.png`` for images that contain solid colors,
    and ``.jpg`` for images with many color variations, such as photos or high-resolution images.

    Do not use animated files like ``.gif`` or similar ones. If it is necessary to use videos.
Naming
    To name the files use underline to separate the chapters and sections,
    and use the hyphen to separate sections that contain two or more words in the title.

    Do not use special characters or spaces in any case.



Usage Guides
------------

- Avoid specifying the resolution of the image or its alignment, so that the website can handle the images consistently,
and provide the best distribution in different screen sizes.
- When documenting a panel or section of the UI, it is better to use a single image that shows all the areas
relevant (instead of multiple images for each icon or button)
located at the top of the section you are writing,
and then explains the characteristics in the order in which they appear in the image.

   ..note::
        It is important that the manual can be maintained in the long term.
        The user interface and tool options change, so try to avoid have many images (when they are not especially necessary).
        Otherwise, this becomes a large maintenance burden.


Videos
======

Videos from YouTube\ :sup:`â„¢` and Vimeo\ :sup:`â„¢` can be embedded using::

   .. youtube:: ID

   .. vimeo:: ID

The ``ID`` is found in the video's URL, e.g:

- The ID for ``https://www.youtube.com/watch?v=Ge2Kwy5EGE0`` is ``Ge2Kwy5EGE0``
- The ID for ``https://vimeo.com/15837189`` is ``15837189``


Usage Guides
------------

- Avoid adding videos that depend on the voice, since it is difficult to translate.
- Do not embed tutorial videos as a means to explain a feature,
the writing itself must explain it properly (although it may include a link
to the video at the bottom of the page, under the heading `` Tutorials``).


Cross References and Linkage
============================

You can link to another document in the manual with::

   :doc:`The Title </section/path/to/file>`

To link to a specific section in another document (or the same one), explicit labels are available::

   .. _sample-label:

   [section or image to reference]

   Some text :ref:`Optional Title <sample-label>`

Linking to a title in the same file::

   Titles are Targets
   ==================

   Body text.

   Implicit references, like `Titles are Targets`_

Linking to the outside world::

   `Blender Website <https://www.blender.org>`__
