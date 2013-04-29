<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

date_default_timezone_set('Europe/Madrid');

/// cargamos las constantes de configuración
require_once 'config.php';
if( !defined('FS_DB_TYPE') )
   define('FS_DB_TYPE', 'POSTGRESQL');
if( !defined('FS_DEMO') )
   define('FS_DEMO', FALSE);

require_once 'base/fs_default_items.php';
require_once 'model/articulo.php';
require_once 'model/asiento.php';
require_once 'model/empresa.php';
require_once 'extras/libromayor.php';

if(FS_DB_TYPE == 'MYSQL')
{
   require_once 'base/fs_mysql.php';
   $db = new fs_mysql();
}
else
{
   require_once 'base/fs_postgresql.php';
   $db = new fs_postgresql();
}

if( $db->connect() )
{
   /// establecemos los elementos por defecto
   $fs_default_items = new fs_default_items();
   $empresa = new empresa();
   $fs_default_items->set_codalmacen( $empresa->codalmacen );
   $fs_default_items->set_coddivisa( $empresa->coddivisa );
   $fs_default_items->set_codejercicio( $empresa->codejercicio );
   $fs_default_items->set_codpago( $empresa->codpago );
   $fs_default_items->set_codpais( $empresa->codpais );
   $fs_default_items->set_codserie( $empresa->codserie );
   
   $articulo = new articulo();
   echo "Ejecutando tareas para los artículos...\n";
   $articulo->cron_job();
   
   $asiento = new asiento();
   echo "Ejecutando tareas para los asientos...\n";
   $asiento->cron_job();
   
   $libro = new libro_mayor();
   echo "Generamos el libro mayor para cada subcuenta...\n";
   $libro->cron_job();
   
   $db->close();
}
else
   echo "¡Imposible conectar a la base de datos!\n";

?>
