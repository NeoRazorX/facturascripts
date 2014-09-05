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

/// cargamos las constantes de configuración
require_once 'config.php';
require_once 'base/config2.php';

$tiempo = explode(' ', microtime());
$uptime = $tiempo[1] + $tiempo[0];

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
require_model('fs_var.php');

require_once 'extras/libromayor.php';
require_once 'extras/inventarios_balances.php';

if( $db->connect() )
{
   $fsvar = new fs_var();
   $cron_vars = $fsvar->array_get( array('cron_exists' => FALSE, 'cron_lock' => FALSE, 'cron_error' => FALSE) );
   
   if($cron_vars['cron_lock'])
   {
      echo "ERROR: Ya hay un cron en ejecución. Si crees que es un error,"
      . " elimina la entrada cron_lock en la tabla fs_vars de la base de datos.";
      
      /// marcamos el error en el cron
      $cron_vars['cron_error'] = 'TRUE';
   }
   else
   {
      /**
       * He detectado que a veces, con el plugin kiwimaru,
       * el proceso cron tarda más de una hora, y por tanto se encadenan varios
       * procesos a la vez. Para evitar esto, uso la entrada cron_lock.
       * Además uso la entrada cron_exists para marcar que alguna vez se ha ejecutado el cron,
       * y cron_error por si hubiese algún fallo.
       */
      $cron_vars['cron_lock'] = 'TRUE';
      $cron_vars['cron_exists'] = 'TRUE';
      
      /// guardamos las variables
      $fsvar->array_save($cron_vars);
      
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
      echo "Ejecutando tareas para los artículos...";
      $articulo->cron_job();
      
      $asiento = new asiento();
      echo "\nEjecutando tareas para los asientos...\n";
      $asiento->cron_job();
      
      $libro = new libro_mayor();
      echo "Generamos el libro mayor para cada subcuenta y el libro diario para cada ejercicio...";
      $libro->cron_job();
      
      $inventarios_balances = new inventarios_balances();
      echo "\nGeneramos el libro de inventarios y balances para cada ejercicio...";
      $inventarios_balances->cron_job();
      
      
      /*
       * Ahora ejecutamos el cron de cada plugin que tenga cron y esté activado
       */
      if( file_exists('tmp/enabled_plugins') )
      {
         foreach( scandir(getcwd().'/tmp/enabled_plugins') as $f)
         {
            if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
            {
               if( file_exists('plugins/'.$f) )
               {
                  if( file_exists('plugins/'.$f.'/cron.php') )
                  {
                     echo "\n\n***********************\nEjecutamos el cron.php del plugin ".$f."\n";
                     
                     include 'plugins/'.$f.'/cron.php';
                     
                     echo "\n***********************\n";
                  }
               }
               else
               {
                  unlink('tmp/enabled_plugins/'.$f);
               }
            }
         }
      }
      
      /// Eliminamos la variable cron_lock puesto que ya hemos terminado
      $cron_vars['cron_lock'] = FALSE;
   }
   
   /// guardamos las variables
   $fsvar->array_save($cron_vars);
   
   $db->close();
}
else
{
   echo "¡Imposible conectar a la base de datos!\n";
   
   foreach($db->get_errors() as $err)
      echo $err."\n";
}

$tiempo = explode(' ', microtime());
echo "\nTiempo de ejecución: ".number_format($tiempo[1] + $tiempo[0] - $uptime, 3)." s\n";