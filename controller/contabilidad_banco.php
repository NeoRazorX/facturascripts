<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'model/banco.php';

class contabilidad_banco extends fs_controller
{
   public $banco;
   
   public function __construct()
   {
      parent::__construct('contabilidad_banco', 'Banco', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_bancos');
      if( isset($_GET['entidad']) )
      {
         $this->banco = new banco();
         $this->banco = $this->banco->get($_GET['entidad']);
      }
      else
         $this->banco = FALSE;
      
      if($this->banco)
      {
         $this->page->title = $this->banco->entidad;
         $this->buttons[] = new fs_button_img('b_eliminar', 'eliminar', 'trash.png', '#', TRUE);
      }
      else
         $this->new_error_msg('Banco no encontrado.');
   }
   
   public function version()
   {
      return parent::version().'-2';
   }
   
   public function url()
   {
      if( !isset($this->banco) )
         return parent::url();
      else if($this->banco)
         return $this->banco->url();
      else
         return $this->page->url();
   }
}

?>