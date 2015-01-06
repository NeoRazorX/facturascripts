<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

class linea_presupuesto_proveedor extends fs_model
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
   
   private static $presupuestos;
   
   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspresupuestosprov', 'plugins/presupuestos_y_pedidos_proveedores/');
      
      if( !isset(self::$presupuestos) )
         self::$presupuestos = array();
      
      if($l)
      {
         $this->cantidad = floatval($l['cantidad']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->descripcion = $l['descripcion'];
         $this->dtolineal = floatval($l['dtolineal']);
         $this->dtopor = floatval($l['dtopor']);
         $this->idlinea = intval($l['idlinea']);
         $this->idpresupuesto = intval($l['idpresupuesto']);
         $this->irpf = floatval($l['irpf']);
         $this->iva = floatval($l['iva']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->recargo = floatval($l['recargo']);
         $this->referencia = $l['referencia'];
      }
      else
      {
         $this->cantidad = 0;
         $this->codimpuesto = NULL;
         $this->descripcion = NULL;
         $this->dtolineal = 0;
         $this->dtopor = 0;
         $this->idlinea = NULL;
         $this->idpresupuesto = NULL;
         $this->irpf = 0;
         $this->iva = 0;
         $this->pvpsindto = 0;
         $this->pvptotal = 0;
         $this->pvpunitario = 0;
         $this->recargo = 0;
         $this->referencia = '';
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function pvp_iva()
   {
      return $this->pvpunitario*(100+$this->iva)/100;
   }
   
   public function total_iva()
   {
      return $this->pvptotal*(100+$this->iva-$this->irpf+$this->recargo)/100;
   }
   
   public function show_codigo()
   {
      $codigo = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$presupuestos as $p)
      {
         if($p->idpresupuesto == $this->idpresupuesto)
         {
            $codigo = $p->codigo;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new presupuesto_proveedor();
         self::$presupuestos[] = $pre->get($this->idpresupuesto);
         $codigo = self::$presupuestos[ count(self::$presupuestos)-1 ]->codigo;
      }
      
      return $codigo;
   }
   
   public function show_fecha()
   {
      $fecha = 'desconocida';
      
      $encontrado = FALSE;
      foreach(self::$presupuestos as $p)
      {
         if($p->idpresupuesto == $this->idpresupuesto)
         {
            $fecha = $p->fecha;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new presupuesto_proveedor();
         self::$presupuestos[] = $pre->get($this->idpresupuesto);
         $fecha = self::$presupuestos[ count(self::$presupuestos)-1 ]->fecha;
      }
      
      return $fecha;
   }
   
   public function show_nombre()
   {
      $nombre = 'desconocido';
      
      $encontrado = FALSE;
      foreach(self::$presupuestos as $p)
      {
         if($p->idpresupuesto == $this->idpresupuesto)
         {
            $nombre = $p->nombre;
            $encontrado = TRUE;
            break;
         }
      }
      
      if( !$encontrado )
      {
         $pre = new presupuesto_proveedor();
         self::$presupuestos[] = $pre->get($this->idpresupuesto);
         $nombre = self::$presupuestos[ count(self::$presupuestos)-1 ]->nombre;
      }
      
      return $nombre;
   }
   
   public function url()
   {
      if( is_null($this->idpresupuesto) )
         return 'index.php?page=compras_presupuestos';
      else
         return 'index.php?page=compras_presupuesto&id='.$this->idpresupuesto;
   }
   
   public function articulo_url()
   {
      if( is_null($this->referencia) OR $this->referencia == ' ')
         return "index.php?page=compras_articulos";
      else
         return "index.php?page=compras_articulo&ref=".urlencode($this->referencia);
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( !$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".
                 $this->referencia." del ".FS_PRESUPUESTO.". Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".
                 $this->referencia." del ".FS_PRESUPUESTO.". Valor correcto: ".$totalsindto);
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET cantidad = ".$this->var2str($this->cantidad).",
               codimpuesto = ".$this->var2str($this->codimpuesto).", descripcion = ".$this->var2str($this->descripcion).",
               dtolineal = ".$this->var2str($this->dtolineal).", dtopor = ".$this->var2str($this->dtopor).", idpresupuesto = ".$this->var2str($this->idpresupuesto).",
               irpf = ".$this->var2str($this->irpf).", iva = ".$this->var2str($this->iva).",
               pvpsindto = ".$this->var2str($this->pvpsindto).", pvptotal = ".$this->var2str($this->pvptotal).",
               pvpunitario = ".$this->var2str($this->pvpunitario).", recargo = ".$this->var2str($this->recargo).",
               referencia = ".$this->var2str($this->referencia)." WHERE idlinea = ".$this->var2str($this->idlinea).";";
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,codimpuesto,descripcion,dtolineal,dtopor,
               idpresupuesto,irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia)
               VALUES (".$this->var2str($this->cantidad).",".$this->var2str($this->codimpuesto).",
               ".$this->var2str($this->descripcion).",".$this->var2str($this->dtolineal).",".$this->var2str($this->dtopor).",
               ".$this->var2str($this->idpresupuesto).",".$this->var2str($this->irpf).",".$this->var2str($this->iva).",
               ".$this->var2str($this->pvpsindto).",".$this->var2str($this->pvptotal).",".$this->var2str($this->pvpunitario).",
               ".$this->var2str($this->recargo).",".$this->var2str($this->referencia).");";
            if( $this->db->exec($sql) )
            {
               $this->idlinea = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function all_from_presupuesto($idp)
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($idp)." ORDER BY referencia ASC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new linea_presupuesto_proveedor($d);
      }
      
      return $plist;
   }
   
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref)." ORDER BY idpresupuesto DESC", $limit, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_presupuesto_proveedor($l);
      }
      
      return $linealist;
   }
   
   public function search($query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $sql .= "referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%'";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%'";
      }
      $sql .= " ORDER BY idpresupuesto DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_presupuesto_proveedor($l);
      }
      
      return $linealist;
   }
}
