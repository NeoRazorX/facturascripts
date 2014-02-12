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

class cotizacion extends fs_model
{
   public $apartado;
   public $cifnif;
   public $ciudad;
   public $codagente;
   public $codalmacen;
   public $codcliente;
   public $coddir;
   public $coddivisa;
   public $codejercicio;
   public $codigo;
   public $codoportunidad;
   public $codpago;
   public $codpais;
   public $codpostal;
   public $codserie;
   public $direccion;
   public $editable;
   public $estado;
   public $fecha;
   public $fechasalida;
   public $finoferta;
   public $idpresupuesto;
   public $idprovincia;
   public $irpf;
   public $neto;
   public $nombrecliente;
   public $numero;
   public $observaciones;
   public $porcomision;
   public $provincia;
   public $recfinanciero;
   public $tasaconv;
   public $total;
   public $totaleuros;
   public $totalirpf;
   public $totaliva;
   public $totalrecargo;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('presupuestoscli', 'plugins/cotizaciones/');
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