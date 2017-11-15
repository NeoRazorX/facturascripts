# Translations


Translations will be centralized from [http://i18n.facturascripts.com](http://i18n.facturascripts.com).

The format chosen is a JSON file, with "key" style: "value", where **key** is the reference to the translation and **value** its translation.

```JSON
    {
        "common-error": "Error",
        "facturascripts": "FacturaScripts",
        "file": "File",
        "files": "File",
        "check-table": "Error checking table '0'"
    }
```

At the moment, the base language is **Core/Translation/es_ES.json**, but possibly very soon it will become **Core/Translation/en_EN.json**. So it is highly advisable to add any new phrase or word in both files. In the translations that are made from the web, only the new available phrases that have been inserted from the base language will be seen when doing a commit.

From the debug bar, a new **Translations** tab has been added, showing a list of translations used and red ones that have not been translated. So it simplifies looking for the use of the terms on each page, without having to know exactly where it is used.

It is preferable that when adding new code that uses phrases that will need to be translated, add them directly to the es_ES.json and en_EN.json files to make it easier for any translator to have them available with the maximum possible availability and can be translated sooner.

## Translation files

To facilitate interaction with translation files in other languages, a web platform will be used, where each interested user can register and contribute in the language of his choice.

It is strongly discouraged to add new phrases in other languages, since that is centralized in the web to avoid possible later conflicts.


## How to translate strings

To translate phrases from PHP you only need to do:

```PHP
    $i18n->trans('common-error');
    $this->i18n->trans('common-error');
    static::i18n->trans('check-table', ['clientes']);
```

Giving back:

```
    Error
    Error
    Error al comprobar la tabla 'clientes'
```

To translate sentences from Twig:

```Twig
    {% i18n.trans('common-error') %}
    {% i18n.trans('check-table', ['clientes']) %}
```

Giving back:

```
    Error
    Error al comprobar la tabla 'clientes'
```

Any phrase used during PHP code execution and rendering of HTML will be collected and displayed in the debug bar, except for now, those that run in AJAX queries.