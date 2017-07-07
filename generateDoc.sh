#!/usr/bin/env bash

command_exists () {
    type "$1" &> /dev/null ;
}

if command_exists phpdoc ; then
    # More info: https://www.phpdoc.org/docs/latest/guides/running-phpdocumentor.html
    phpdoc -d Core -t doc
else
    echo 'Instala phpdoc para ejecutar este comando.'
fi
