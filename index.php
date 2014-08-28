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

if( !file_exists('config.php') )
{
   header('Location: install.php');
}
else
{
   /// cargamos las constantes de configuración
   require_once 'config.php';
   require_once 'base/config2.php';
   
   require_once 'base/fs_controller.php';
   require_once 'raintpl/rain.tpl.class.php';
   
   /// Cargamos la lista de plugins activos
   $GLOBALS['plugins'] = array();
   if( file_exists('tmp/enabled_plugins') )
   {
      foreach( scandir(getcwd().'/tmp/enabled_plugins') as $f)
      {
         if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
         {
            if( file_exists('plugins/'.$f) )
            {
               $GLOBALS['plugins'][] = $f;
            }
            else
               unlink('tmp/enabled_plugins/'.$f);
         }
      }
   }
   
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
      raintpl::configure('path_replace', FALSE);
      
      /// ¿Se puede escribir sobre la carpeta temporal?
      if( is_writable('tmp') )
      {
         raintpl::configure('cache_dir', 'tmp/');
      }
      else
      {
         echo '<center>'
         . '<h1>No se puede escribir sobre la carpeta tmp de FacturaScripts</h1>'
         . '<p>Consulta la <a target="_blank" href="http://www.facturascripts.com/community/item.php?id=5215f20918c088832df79fe9">documentaci&oacute;n</a>.</p>'
         . '</center>';
         die('<center><iframe src="http://www.facturascripts.com/community/item.php?id=5215f20918c088832df79fe9" width="90%" height="800"></iframe></center>');
      }
      
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
      
      $tpl->draw( $fsc->template );
   }
   
   $fsc->close();
}
