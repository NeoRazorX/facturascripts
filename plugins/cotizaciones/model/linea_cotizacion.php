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

require_once 'base/fs_model.php';

class linea_cotizacion extends fs_model
{
   public $cantidad;
   public $codimpuesto;
   public $descripcion;
   public $dtolineal;
   public $dtopor;
   public $idlinea;
   public $idpresupuesto;
   public $irpf;
   public $iva;
   public $pvpsindto;
   public $pvptotal;
   public $pvpunitario;
   public $recargo;
   public $referencia;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspresupuestoscli', 'plugins/cotizaciones/');
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      ;
   }
   
   public function test()
   {
      ;
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      ;
   }
}

?>