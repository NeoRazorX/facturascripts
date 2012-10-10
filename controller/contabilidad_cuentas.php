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

require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';

class contabilidad_cuentas extends fs_controller
{
   public $cuenta;
   public $ejercicio;
   public $resultados;
   public $resultados2;
   public $offset;

   public function __construct()
   {
      parent::__construct('contabilidad_cuentas', 'Cuentas', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->cuenta = new cuenta();
      $this->ejercicio = new ejercicio();
      $this->custom_search = TRUE;
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query != '')
      {
         $this->resultados = $this->cuenta->search($this->query);
         $subc = new subcuenta();
         $this->resultados2 = $subc->search($this->query);
      }
      else if( isset($_POST['ejercicio']) )
      {
         $eje = $this->ejercicio->get($_POST['ejercicio']);
         if($eje)
            $eje->set_default();
         $this->resultados = $this->cuenta->all_from_ejercicio($_POST['ejercicio'], $this->offset);
      }
      else
         $this->resultados = $this->cuenta->all($this->offset);
   }
   
   public function version() {
      return parent::version().'-2';
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
}

?>
