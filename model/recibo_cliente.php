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

require_once 'base/fs_model.php';

class recibo_cliente extends fs_model
{
   public $numero;
   public $texto;
   public $codpais;
   public $apartado;
   public $provincia;
   public $ciudad;
   public $codpostal;
   public $direccion;
   public $coddir;
   public $cuenta;
   public $dc;
   public $ctaagencia;
   public $ctaentidad;
   public $descripcion;
   public $codcuenta;
   public $coddivisa;
   public $importeeuros;
   public $cifnif;
   public $idfactura;
   public $idremesa;
   public $nombrecliente;
   public $codcliente;
   public $fechav;
   public $fecha;
   public $importe;
   public $estado;
   public $codigo;
   public $idrecibo; /// pkey
   
   public function __construct($r = FALSE)
   {
      parent::__construct('reciboscli');
      if($r)
      {
         $this->idrecibo = $this->intval($r['idrecibo']);
         $this->codigo = $r['codigo'];
         $this->estado = $r['estado'];
         $this->importe = floatval($r['importe']);
         $this->fecha = $r['fecha'];
         $this->fechav = $r['fechav'];
         $this->codcliente = $r['codcliente'];
         $this->nombrecliente = $r['nombrecliente'];
         $this->idremesa = $this->intval($r['idremesa']);
         $this->idfactura = $this->intval($r['idfactura']);
         $this->cifnif = $r['cifnif'];
         $this->importeeuros = floatval($r['importeeuros']);
         $this->coddivisa = $r['coddivisa'];
         $this->codcuenta = $r['codcuenta'];
         $this->descripcion = $r['descripcion'];
         $this->ctaentidad = $r['ctaentidad'];
         $this->ctaagencia = $r['ctaagencia'];
         $this->dc = $r['dc'];
         $this->cuenta = $r['cuenta'];
         $this->coddir = $r['coddir'];
         $this->direccion = $r['direccion'];
         $this->codpostal = $r['codpostal'];
         $this->ciudad = $r['ciudad'];
         $this->provincia = $r['provincia'];
         $this->apartado = $r['apartado'];
         $this->codpais = $r['codpais'];
         $this->texto = $r['texto'];
         $this->numero = $r['numero'];
      }
      else
      {
         $this->idrecibo = NULL;
         $this->codigo = NULL;
         $this->estado = NULL;
         $this->importe = NULL;
         $this->fecha = NULL;
         $this->fechav = NULL;
         $this->codcliente = NULL;
         $this->nombrecliente = NULL;
         $this->idremesa = NULL;
         $this->idfactura = NULL;
         $this->cifnif = NULL;
         $this->importeeuros = NULL;
         $this->coddivisa = NULL;
         $this->codcuenta = NULL;
         $this->descripcion = NULL;
         $this->ctaentidad = NULL;
         $this->ctaagencia = NULL;
         $this->dc = NULL;
         $this->cuenta = NULL;
         $this->coddir = NULL;
         $this->direccion = NULL;
         $this->codpostal = NULL;
         $this->ciudad = NULL;
         $this->provincia = NULL;
         $this->apartado = NULL;
         $this->codpais = NULL;
         $this->texto = NULL;
         $this->numero = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idrecibo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
            " WHERE idrecibo = ".$this->var2str($this->idrecibo).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "";
         }
         else
         {
            $sql = "";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
         " WHERE idrecibo = ".$this->var2str($this->idrecibo).";");
   }
}

?>