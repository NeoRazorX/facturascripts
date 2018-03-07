.. title:: Translations
.. highlight:: rst

############
Translations
############


Translations will be centralized from http://i18n.facturascripts.com.

The format chosen is a JSON file, with “key” style: “value”, where
**key** is the reference to the translation and **value** its
translation. The variable values are specified by parameter between '%'.

.. code:: json

        {
            "common-error": "Error",
            "facturascripts": "FacturaScripts",
            "file": "File",
            "files": "Files",
            "check-table": "Error checking table '%tablename%'"
        }

The base language is the **Core/Translation/en_EN.json **.
So it is necessary to add new phrases or words in the file.
In the translations that are made from the web, only the new phrases will be seen
available that have been inserted from the base language when making a
commit.

In the debug bar, a new tab has been added
**Translations** which shows a list of the translations used and
that could not be translated into the selected language.


Translation files
-----------------

To facilitate interaction with translation files in other languages, a
web platform will be used, where each interested user can register and
contribute in the language of his choice.

It is strongly discouraged to add new phrases in other languages, since
that is centralized in the web to avoid possible later conflicts.

How to translate strings
------------------------

To translate phrases from PHP you only need to do:

.. code:: php

        $i18n->trans('common-error');
        $this->i18n->trans('common-error');
        static::i18n->trans('check-table', ['clientes']);

Giving back:

::

        Error
        Error
        Error al comprobar la tabla 'clientes'

To translate sentences from Twig:

.. code:: twig

        {% i18n.trans('common-error') %}
        {% i18n.trans('check-table', ['clientes']) %}

Giving back:

::

        Error
        Error al comprobar la tabla 'clientes'

Any phrase used during PHP code execution and rendering of HTML will be
collected and displayed in the debug bar, except for now, those that run
in AJAX queries.
