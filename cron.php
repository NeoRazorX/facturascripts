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

require_once 'config.php';
require_once 'base/fs_db.php';
require_once 'model/articulo.php';
require_once 'model/asiento.php';
require_once 'extras/libromayor.php';

$db = new fs_db();
if( $db->connect() )
{
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
