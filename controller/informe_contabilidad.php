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

require_model('ejercicio.php');
require_once 'extras/inventarios_balances.php';

class informe_contabilidad extends fs_controller
{
   public $ejercicio;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Contabilidad', 'informes', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->ejercicio = new ejercicio();
      
      if( isset($_GET['balance']) AND isset($_GET['eje']) )
      {
         $this->template = FALSE;
         $iba = new inventarios_balances();
         
         if($_GET['balance'] == 'pyg')
            $iba->generar_pyg($_GET['eje']);
         else
            $iba->generar_sit($_GET['eje']);
      }
   }
   
   public function existe_libro_diario($codeje)
   {
      return file_exists('tmp/'.FS_TMP_NAME.'libro_diario/'.$codeje.'.pdf');
   }
   
   public function existe_libro_inventarios($codeje)
   {
      return file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$codeje.'.pdf');
   }
}
