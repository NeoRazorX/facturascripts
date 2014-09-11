<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

/**
 * Recibos de clientes.
 */
class recibo_cliente extends fs_model
{
   public $apartado;
   public $cifnif;
   public $ciudad;
   public $codcliente;
   public $codcuenta;
   
   /**
    * Código de la dirección del cliente, también está guardado en
    * albaranes y facturas.
    * @var type 
    */
   public $coddir;
   public $coddivisa;
   
   /**
    * Código de la factura - número de recibo (dos dígitos)
    * @var type 
    */
   public $codigo;
   public $codpais;
   public $codpostal;
   
   /**
    * Datos de la cuenta bancaria.
    * OBSOLETO, YO LO SUSTITUIRÍA POR EL IBAN.
    * @var type 
    */
   public $ctaagencia;
   public $ctaentidad;
   public $cuenta;
   public $dc;
   
   public $descripcion;
   public $direccion;
   
   /**
    * Emitido / Pagado
    * @var type 
    */
   public $estado;
   public $fecha;
   
   /**
    * fecha de vencimiento
    * @var type 
    */
   public $fechav;
   public $idfactura;
   public $idrecibo;
   public $idremesa;
   
   /**
    * Total de la factura
    * @var type 
    */
   public $importe;
   
   /**
    * Totaleuros de la factura
    * @var type 
    */
   public $importeeuros;
   public $nombrecliente;
   
   /**
    * Número de recibo.
    * @var type 
    */
   public $numero;
   public $provincia;
   
   /**
    * Importe en palabras:
    * "CUATROCIENTOS DIEZ EUROS CON DIECINUEVE CÉNTIMOS"
    * @var type 
    */
   public $texto;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('reciboscli', 'plugins/recibos_para_pagos_y_cobros/');
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idrecibo) )
         return 'index.php?page=recibos_clientes';
      else
         return 'index.php?page=recibo_cliente&id='.$this->idrecibo;
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=recibos_clientes";
      else
         return "index.php?page=recibos_cliente&cliente=".$this->codcliente;
   }
   
   public function get($id)
   {
      $recibo = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idrecibo = ".$this->var2str($id).";");
      if($recibo)
         return new recibo_cliente($recibo[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $recibo = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($cod).";");
      if($recibo)
         return new recibo_cliente($recibo[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idrecibo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idrecibo = ".$this->var2str($this->idrecibo).";");
   }
   
   public function new_idrecibo()
   {
      $newid = $this->db->nextval($this->table_name.'_idrecibo_seq');
      if($newid)
         $this->idrecibo = intval($newid);
   }
   
   public function test()
   {
      /* Todavía no implementado */
   }
   
   public function full_test($duplicados = TRUE)
   {
      /* Todavía no implementado */
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET 
            apartado = ".$this->var2str($this->apartado).", 
            cifnif = ".$this->var2str($this->cifnif).", 
            ciudad = ".$this->var2str($this->ciudad).", 
            codcliente = ".$this->var2str($this->codcliente).", 
            codcuenta = ".$this->var2str($this->codcuenta).", 
            coddir = ".$this->var2str($this->coddir).", 
            coddivisa = ".$this->var2str($this->coddivisa).", 
            codpais = ".$this->var2str($this->codpais).",
            codpostal = ".$this->var2str($this->codpostal).",
            ctaagencia = ".$this->var2str($this->ctaagencia).",
            ctaentidad = ".$this->var2str($this->ctaentidad).",
            cuenta = ".$this->var2str($this->cuenta).",
            dc = ".$this->var2str($this->dc).",
            descripcion = ".$this->var2str($this->descripcion).",
            direccion = ".$this->var2str($this->direccion).",
            estado = ".$this->var2str($this->estado).",
            fecha = ".$this->var2str($this->fecha).",
            fechav = ".$this->var2str($this->fechav).",
            idfactura = ".$this->var2str($this->idfactura).",
            idrecibo = ".$this->var2str($this->idrecibo).",
            idremesa = ".$this->var2str($this->idremesa).",
            importe = ".$this->var2str($this->importe).",
            importeeuros = ".$this->var2str($this->importeeuros).",
            nombrecliente = ".$this->var2str($this->nombrecliente).",
            numero = ".$this->var2str($this->numero).",
            provincia = ".$this->var2str($this->provincia).",
            texto = ".$this->var2str($this->texto)." 
            WHERE codigo = ".$this->var2str($this->codigo).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (apartado, cifnif, ciudad, codcliente, 
            codcuenta, coddir, coddivisa, codigo, codpais, codpostal, ctaagencia, ctaentidad, 
            cuenta, dc, descripcion, direccion, estado, fecha, fechav, idfactura, idrecibo, 
            idremesa, importe, importeeuros, nombrecliente, numero, provincia, texto) VALUES (
            ".$this->var2str($this->apartado).",".$this->var2str($this->cifnif).","
            .$this->var2str($this->ciudad).",".$this->var2str($this->codcliente).",
            ".$this->var2str($this->codcuenta).",".$this->var2str($this->coddir).",
            ".$this->var2str($this->coddivisa).",".$this->var2str($this->codigo).",
            ".$this->var2str($this->codpais).",".$this->var2str($this->codpostal).",
            ".$this->var2str($this->ctaagencia).",".$this->var2str($this->ctaentidad).",
            ".$this->var2str($this->cuenta).",".$this->var2str($this->dc).",
            ".$this->var2str($this->descripcion).",".$this->var2str($this->estado).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->fechav).",
            ".$this->var2str($this->idfactura).",".$this->var2str($this->idrecibo).",
            ".$this->var2str($this->idremesa).",".$this->var2str($this->importe).",
            ".$this->var2str($this->importeeuros).",".$this->var2str($this->nombrecliente).",
            ".$this->var2str($this->numero).",".$this->var2str($this->provincia).",
            ".$this->var2str($this->texto).");";
         
         if( $this->db->exec($sql) )
         {
            $this->recibo = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      /* Todavía no implementado */
   }
   
   private function clean_cache()
   {
      /* Todavía no implementado */
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      /* Falta utilizar offset y limit */
      $reciboslist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." ORDER BY codigo DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $reciboslist[] = new recibo_cliente($d);
      }
      
      return $reciboslist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      /* Falta utilizar offset */
      $reciboslist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente)." ORDER BY codigo DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $reciboslist[] = new recibo_cliente($d);
      }
      
      return $reciboslist;
   }
   
   public function search($query, $offset=0)
   {
      /* Todavía no implementado */
   }
}
