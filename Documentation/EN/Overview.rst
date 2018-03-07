.. title:: Overview
.. highlight:: rst

########
Overview
########

Requirements
============

For installation and use
    1. Apache2 Server web server or similar
    2. PHP 7.0
    3. Database engine MySql, MariaDB or Postgresql

For development
    1. Requirements for use
    2. PHP development environment: NetBeens, PHPStorm or similar
    3. Composer and NPM: For the installation of requirements.


.. _installation:


Installation
============
Access the website of `FacturaScripts 2018 <https://beta.facturascripts.com/descargar>`_,
download the beta and decompress the file in your hosting or in the Apache or XAMP folder of your PC.
Then open the browser and type the appropriate url, that is, the domain
of your web or http://localhost/facturascripts (if you have installed it in local).


Installation for development
============================

To install a development environment, the steps to follow may vary depending on the operating system
where you want to work Below is a generic method.

.. code-block:: bash
    # Installing Facturascripts
    git clone https://github.com/NeoRazorX/facturascripts.git
    cd invoicescripts
    composer install
    npm install

If the Composer package is not installed correctly, we can install it manually

.. code-block:: bash
    # Install Composer
    curl -sS https://getcomposer.org/installer | php



License
=======

Licensed using the `MIT license <http://opensource.org/licenses/MIT>`_.

    Copyright (C) 2013-2018 Carlos Garcia Gomez <carlos@facturascripts.com>

    This program is free software: you can redistribute it and / or modify
    it under the terms of the GNU Lesser General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.


.. _contribute:

Contribute
============

This project is free software and all developers are welcome.
You can check the list of tasks to be done, the documentation and the chat for programmers
on our website: https://www.facturascripts.com/foro/quieres-colaborar-en-el-desarrollo-de-facturascripts-964.html


Guidelines
-----------

1. Facturascripts uses PSR-1 and PSR-2.

2. Facturascripts is designed to use the least possible dependencies and simplicity.
This means that not all feature requests will be accepted.

3. Facturascripts has a minimum PHP version requirement of 7.0. PR applications must respect
this requirement. Integration with other PHP version requirements will be denied.

4. All PR requests must include unit tests to ensure that the change works as
expected and to avoid regressions.


Issues (Problems)
------------------

Any questions, questions or errors you may find you can comment in the chat: https://facturascripts.slack.com
or create the corresponding topic at https://github.com/NeoRazorX/facturascripts/issues


Pull Requests
-------------

All contributions are well received in FacturaScripts, but please read the following before:

Content
    Check that your code complies with the standards `PSR-1 <http://www.php-fig.org/psr/psr-1>`__ and `PSR-2 <http://www.php-fig.org/ psr / psr-2>`__.

Documentation
    Documentation is something that is essential for everyone to better understand how to use
    the code made by others, or even to understand what we did ourselves some time ago.


Writing a Pull Request
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Title
    Ideally, a Pull Request should refer to only one objective, so independent changes can be combined quickly.
    If you want, for example, to correct a typographical error and improve the performance of a process, you should try as much as possible to do it
    in separate PR, so we can incorporate one quickly while the other one can be discussed.
    The objective is to obtain a clean change record and make a reversal easy.
    If you have found a typo / bug when writing your changes that are not related to your work, please do another
    Pull Request for it. In some rare cases, you will be forced to do it in the same PR. In these types of situations,
    please add a comment in your PR explaining why it should be like this.

Change Log
    For each PR, a change log must be provided.
    In the notes you can use the following sections:

    #. ``Added`` for new features.
    #. ``Changed`` to indicate changes in existing functionalities.
    #. ``Obsolete`` for features that have become obsolete and will be eliminated.
    #. ``Removed`` for obsolete features that have been removed.
    #. ``Fixed`` for any error correction.
    #. ``Security`` to invite users to update in case of vulnerabilities.

    This makes it easy for any user
