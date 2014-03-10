<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

$tiempo = explode(' ', microtime());
$uptime = $tiempo[1] + $tiempo[0];

/// cargamos las constantes de configuración
require_once 'config.php';

if(strtolower(FS_DB_TYPE) == 'mysql')
{
   require_once 'base/fs_mysql.php';
   $db = new fs_mysql();
}
else
{
   require_once 'base/fs_postgresql.php';
   $db = new fs_postgresql();
}

require_once 'base/fs_default_items.php';

require_once 'base/fs_model.php';
require_model('albaran_cliente.php');
require_model('albaran_proveedor.php');
require_model('articulo.php');
require_model('asiento.php');
require_model('empresa.php');

require_once 'extras/libromayor.php';
require_once 'extras/inventarios_balances.php';

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
   
   $alb_cli = new albaran_cliente();
   echo "Ejecutando tareas para los ".FS_ALBARANES." de cliente...\n";
   $alb_cli->cron_job();
   
   $alb_pro = new albaran_proveedor();
   echo "Ejecutando tareas para los ".FS_ALBARANES." de proveedor...\n";
   $alb_pro->cron_job();
   
   $articulo = new articulo();
   echo "Ejecutando tareas para los artículos...\n";
   $articulo->cron_job();
   
   $asiento = new asiento();
   echo "Ejecutando tareas para los asientos...\n";
   $asiento->cron_job();
   
   $libro = new libro_mayor();
   echo "Generamos el libro mayor para cada subcuenta y el libro diario para cada ejercicio...";
   $libro->cron_job();
   
   $inventarios_balances = new inventarios_balances();
   echo "\nGeneramos el libro de inventarios y balances para cada ejercicio...";
   $inventarios_balances->cron_job();
   
   $db->close();
}
else
   echo "¡Imposible conectar a la base de datos!\n";

$tiempo = explode(' ', microtime());
echo "\nTiempo de ejecución: ".number_format($tiempo[1] + $tiempo[0] - $uptime, 3)." s\n";

?>