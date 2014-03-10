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

/// Si estas leyendo esto es porque no tienes PHP instalado !!!!!!!!!!!!!!!!!!!!

date_default_timezone_set('Europe/Madrid');

if( !file_exists('config.php') )
   include('view/no_config.html');
else
{
   /// cargamos las constantes de configuración
   require_once 'config.php';
   if( !defined('FS_COMMUNITY_URL') )
      define('FS_COMMUNITY_URL', 'http://www.facturascripts.com/community');
   if( !defined('FS_POS_DIVISA') )
      define('FS_POS_DIVISA', 'right');
   if( !defined('FS_ALBARAN') )
      define('FS_ALBARAN', 'albarán');
   if( !defined('FS_ALBARANES') )
      define('FS_ALBARANES', 'albaranes');
   
   require_once 'base/fs_controller.php';
   require_once 'raintpl/rain.tpl.class.php';
   
   /// Cargamos la lista de plugins activos
   $GLOBALS['plugins'] = array();
   if( file_exists('tmp/enabled_plugins') )
   {
      foreach(scandir('tmp/enabled_plugins') as $f)
      {
         if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
         {
            if( file_exists('plugins/'.$f) )
               $GLOBALS['plugins'][] = $f;
            else
               unlink('tmp/enabled_plugins/'.$f);
         }
      }
   }
   
   $tpl_dir2 = 'view/';
   
   /// ¿Qué controlador usar?
   if( isset($_GET['page']) )
   {
      /// primero buscamos en los plugins
      $found = FALSE;
      foreach($GLOBALS['plugins'] as $plugin)
      {
         if( file_exists('plugins/'.$plugin.'/controller/'.$_GET['page'].'.php') )
         {
            require_once 'plugins/'.$plugin.'/controller/'.$_GET['page'].'.php';
            $fsc = new $_GET['page']();
            $found = TRUE;
            
            /// seleccionamod la carpeta view del plugin como segundo directorio de templates
            $tpl_dir2 = 'plugins/'.$plugin.'/view/';
            
            break;
         }
      }
      
      if( !$found )
      {
         if( file_exists('controller/'.$_GET['page'].'.php') )
         {
            require_once 'controller/'.$_GET['page'].'.php';
            $fsc = new $_GET['page']();
         }
         else
            $fsc = new fs_controller();
      }
   }
   else
   {
      $fsc = new fs_controller();
      $fsc->select_default_page();
   }
   
   if($fsc->template)
   {
      /// configuramos rain.tpl
      raintpl::configure('base_url', NULL);
      raintpl::configure('tpl_dir', 'view/');
      raintpl::configure('tpl_dir2', $tpl_dir2);
      raintpl::configure('path_replace', FALSE);
      
      /// ¿Se puede escribir sobre la carpeta temporal?
      if( file_exists('tmp/test') )
         raintpl::configure('cache_dir', 'tmp/');
      else if( mkdir('tmp/test') )
         raintpl::configure('cache_dir', 'tmp/');
      else
         die('No se puede escribir sobre la carpeta temporal (la carpeta tmp de FacturaScripts). Consulta la
            <a target="_blank" href="http://www.facturascripts.com/community/item.php?id=5215f20918c088832df79fe9">documentaci&oacute;n</a>.');
      
      $tpl = new RainTPL();
      $tpl->assign('fsc', $fsc);
      
      if( isset($_POST['user']) )
         $tpl->assign('nlogin', $_POST['user']);
      else if( isset($_GET['nlogin']) )
         $tpl->assign('nlogin', $_GET['nlogin']);
      else if( isset($_COOKIE['user']) )
         $tpl->assign('nlogin', $_COOKIE['user']);
      else
         $tpl->assign('nlogin', '');
      
      $tpl->assign('db_history', FS_DB_HISTORY);
      $tpl->assign('demo', FS_DEMO);
      $tpl->assign('community_url', FS_COMMUNITY_URL);
      $tpl->assign('nf0', FS_NF0);
      $tpl->assign('nf1', FS_NF1);
      $tpl->assign('nf2', FS_NF2);
      $tpl->assign('pos_divisa', FS_POS_DIVISA);
      $tpl->assign('albaran', FS_ALBARAN);
      $tpl->assign('albaranes', FS_ALBARANES);
      
      $tpl->draw( $fsc->template );
   }
   
   $fsc->close();
}

?>