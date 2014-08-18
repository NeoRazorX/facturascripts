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

class admin_config2 extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Configuración avanzada', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $guardar = FALSE;
      
      foreach($GLOBALS['config2'] as $i => $value)
      {
         if( isset($_POST[$i]) )
         {
            $GLOBALS['config2'][$i] = $_POST[$i];
            $guardar = TRUE;
         }
      }
      
      if($guardar)
      {
         $file = fopen('tmp/config2.ini', 'w');
         if($file)
         {
            foreach($GLOBALS['config2'] as $i => $value)
            {
               fwrite($file, $i.' = '.$value.";\n");
            }
            
            fclose($file);
         }
         
         $this->new_message('Datos guardados correctamente.');
      }
   }
   
   public function claves()
   {
      $clist = array();
      
      foreach($GLOBALS['config2'] as $i => $value)
         $clist[] = array('nombre' => $i, 'valor' => $value);
      
      return $clist;
   }

   /**
   * Timezones list with GMT offset
   * 
   * @return array
   * @link http://stackoverflow.com/a/9328760
   */
   public function get_timezone_list() {
      $zones_array = array();
      $timestamp = time();
      foreach(timezone_identifiers_list() as $key => $zone) {
         date_default_timezone_set($zone);
         $zones_array[$key]['zone'] = $zone;
         $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
      }
      return $zones_array;
   }
}
