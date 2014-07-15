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

/**
 * Línea de un presupuesto de proveedor.
 */
class linea_presupuesto_proveedor extends fs_model
{
   public $idlinea;
   public $idpresupuesto;
   public $referencia;
   public $descripcion;
   public $cantidad;
   public $dtopor;
   public $dtolineal;
   public $codimpuesto;
   public $iva;
   public $pvptotal;
   public $pvpsindto;
   public $pvpunitario;
   public $irpf;
   public $recargo;
   
   private $codigo;
   private $fecha;
   private $presupuesto_url;
   
   private static $presupuestos;
   
   public function __construct($l=FALSE)
   {
      parent::__construct('lineaspresupuestosprov');
      
      if( !isset(self::$presupuestos) )
         self::$presupuestos = array();
      
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idpresupuesto = $this->intval($l['idpresupuesto']);
         $this->referencia = $l['referencia'];
         $this->descripcion = $l['descripcion'];
         $this->cantidad = floatval($l['cantidad']);
         $this->dtopor = floatval($l['dtopor']);
         $this->dtolineal = floatval($l['dtolineal']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->irpf = floatval($l['irpf']);
         $this->recargo = floatval($l['recargo']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idpresupuesto = NULL;
         $this->referencia = '';
         $this->descripcion = '';
         $this->cantidad = 0;
         $this->dtopor = 0;
         $this->dtolineal = 0;
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->pvptotal = 0;
         $this->pvpsindto = 0;
         $this->pvpunitario = 0;
         $this->irpf = 0;
         $this->recargo = 0;
      }
   }

   protected function install()
   {
      return '';
   }
   
   private function fill()
   {
      $encontrado = FALSE;
      foreach(self::$presupuestos as $a)
      {
         if($a->idpresupuesto == $this->idpresupuesto)
         {
            $this->codigo = $a->codigo;
            $this->fecha = $a->fecha;
            $this->presupuesto_url = $a->url();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $presu = new presupuesto_proveedor();
         $presu = $presu->get($this->idpresupuesto);
         if( $presu )
         {
            $this->codigo = $presu->codigo;
            $this->fecha = $presu->fecha;
            $this->presupuesto_url = $presu->url();
            self::$presupuestos[] = $presu;
         }
      }
   }
   
   public function pvp_iva()
   {
      return $this->pvpunitario*(100+$this->iva)/100;
   }
   
   public function total_iva()
   {
      return $this->pvptotal*(100+$this->iva)/100;
   }
   
   /// Devuelve el precio total por unidad (con descuento incluido e iva aplicado)
   public function total_iva2()
   {
      if($this->cantidad == 0)
         return 0;
      else
         return $this->pvptotal*(100+$this->iva)/100/$this->cantidad;
   }
   
   public function show_codigo()
   {
      if( !isset($this->codigo) )
         $this->fill();
      return $this->codigo;
   }
   
   public function show_fecha()
   {
      if( !isset($this->fecha) )
         $this->fill();
      return $this->fecha;
   }
   
   public function url()
   {
      if( !isset($this->presupuesto_url) )
         $this->fill();
      return $this->presupuesto_url;
   }
   
   public function articulo_url()
   {
      if( is_null($this->referencia) AND $this->referencia == ' ')
         return "index.php?page=general_articulos";
      else
         return "index.php?page=general_articulo&ref=".urlencode($this->referencia);
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
         return false;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function new_idlinea()
   {
      $newid = $this->db->nextval($this->table_name.'_idlinea_seq');
      if($newid)
         $this->idlinea = intval($newid);
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
      $totalsindto = $this->pvpunitario * $this->cantidad;
      
      if( !$this->floatcmp($this->pvptotal, $total, 2, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvptotal de la línea ".$this->referencia.
            " del presupuesto. Valor correcto: ".$total);
         return FALSE;
      }
      else if( !$this->floatcmp($this->pvpsindto, $totalsindto, 2, TRUE) )
      {
         $this->new_error_msg("Error en el valor de pvpsindto de la línea ".$this->referencia.
            " del presupuesto. Valor correcto: ".$totalsindto);
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idpresupuesto = ".$this->var2str($this->idpresupuesto).",
               referencia = ".$this->var2str($this->referencia).",
               descripcion = ".$this->var2str($this->descripcion).",
               cantidad = ".$this->var2str($this->cantidad).", dtopor = ".$this->var2str($this->dtopor).",
               dtolineal = ".$this->var2str($this->dtolineal).",
               codimpuesto = ".$this->var2str($this->codimpuesto).",
               iva = ".$this->var2str($this->iva).", pvptotal = ".$this->var2str($this->pvptotal).",
               pvpsindto = ".$this->var2str($this->pvpsindto).",
               pvpunitario = ".$this->var2str($this->pvpunitario).",
               irpf = ".$this->var2str($this->irpf).", recargo = ".$this->var2str($this->recargo).
               " WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $this->new_idlinea();
            $sql = "INSERT INTO ".$this->table_name." (idlinea,idpresupuesto,referencia,descripcion,
               cantidad,dtopor,dtolineal,codimpuesto,iva,pvptotal,pvpsindto,pvpunitario,irpf,recargo)
               VALUES (".$this->var2str($this->idlinea).",".$this->var2str($this->idpresupuesto).",
               ".$this->var2str($this->referencia).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->cantidad).",".$this->var2str($this->dtopor).",
               ".$this->var2str($this->dtolineal).",".$this->var2str($this->codimpuesto).",
               ".$this->var2str($this->iva).",".$this->var2str($this->pvptotal).",
               ".$this->var2str($this->pvpsindto).",".$this->var2str($this->pvpunitario).",
               ".$this->var2str($this->irpf).",".$this->var2str($this->recargo).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function clean_cache()
   {
      $this->cache->delete('prepro_top_articulos');
   }
   
   public function all_from_presupuesto($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idpresupuesto = ".$this->var2str($id)." ORDER BY idlinea ASC;");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_presupuesto_proveedor($l);
      }
      return $linealist;
   }
   
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE referencia = ".$this->var2str($ref).
              " ORDER BY idpresupuesto DESC", $limit, $offset);
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
   
   public function search_from_proveedor($codproveedor, $query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpresupuesto IN
         (SELECT idpresupuesto FROM presupuestosprov WHERE codproveedor = ".$this->var2str($codproveedor).") AND ";
      if( is_numeric($query) )
      {
         $sql .= "(referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "(lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%')";
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
   
   public function count_by_articulo()
   {
      $num = 0;
      $lineas = $this->db->select("SELECT COUNT(DISTINCT referencia) as total
         FROM ".$this->table_name.";");
      if($lineas)
         $num = intval($lineas[0]['total']);
      return $num;
   }
   
   public function top_by_articulo()
   {
      $toplist = $this->cache->get('prepro_top_articulos');
      if( !$toplist )
      {
         $articulo = new articulo();
         $lineas = $this->db->select_limit("SELECT referencia, SUM(cantidad) as compras
            FROM ".$this->table_name." GROUP BY referencia ORDER BY compras DESC", FS_ITEM_LIMIT, 0);
         if($lineas)
         {
            foreach($lineas as $l)
            {
               $art0 = $articulo->get($l['referencia']);
               if($art0)
                  $toplist[] = array($art0, intval($l['compras']));
            }
         }
         $this->cache->set('prepro_top_articulos', $toplist);
      }
      return $toplist;
   }
}
