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

/**
 * Línea de un pedido de cliente
 */
class linea_pedido_cliente extends fs_model
{
   public $idlinea; /// pkey
   public $idpedido;
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
   private $pedido_url;
   
   private static $pedidos;

   public function __construct($l = FALSE)
   {
      parent::__construct('lineaspedidoscli', 'plugins/presupuestos_y_pedidos/');
      
      if( !isset(self::$pedidos) )
         self::$pedidos = array();
      
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idpedido = $this->intval($l['idpedido']);
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
         $this->idpedido = NULL;
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
      foreach(self::$pedidos as $p)
      {
         if($p->idpedido == $this->idpedido)
         {
            $this->codigo = $p->codigo;
            $this->fecha = $p->fecha;
            $this->pedido_url = $p->url();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $ped = new pedido_cliente();
         $ped = $ped->get($this->idpedido);
         if( $ped )
         {
            $this->codigo = $ped->codigo;
            $this->fecha = $ped->fecha;
            $this->pedido_url = $ped->url();
            self::$pedidos[] = $ped;
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
      if( !isset($this->pedido_url) )
         $this->fill();
      return $this->pedido_url;
   }
   
   public function articulo_url()
   {
      if( is_null($this->referencia) AND $this->referencia == ' ')
         return "index.php?page=ventas_pedido";
      else
         return "index.php?page=ventas_pedido&id=".$this->idpedido;
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = ".$this->var2str($this->idlinea).";");
   }
   
   public function new_idlinea()
   {
      $newid = $this->db->nextval($this->table_name.'_idlinea_seq');
      if($newid)
         $this->idlinea = intval($newid);
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET cantidad = ".$this->var2str($this->idlinea).",
               codimpuesto = ".$this->var2str($this->codimpuesto).", descripcion = ".$this->var2str($this->descripcion).",
               dtopor = ".$this->var2str($this->dtopor).", idpedido = ".$this->var2str($this->idpedido).",
               irpf = ".$this->var2str($this->irpf).", iva = ".$this->var2str($this->iva).",
               pvpsindto = ".$this->var2str($this->pvpsindto).", pvptotal = ".$this->var2str($this->pvptotal).",
               pvpunitario = ".$this->var2str($this->pvpunitario).", recargo = ".$this->var2str($this->recargo).",
               referencia = ".$this->var2str($this->referencia)." WHERE idlinea = ".$this->var2str($this->idlinea).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (cantidad,codimpuesto,descripcion,dtopor,
               idpedido,irpf,iva,pvpsindto,pvptotal,pvpunitario,recargo,referencia)
               VALUES (".$this->var2str($this->cantidad).",".$this->var2str($this->codimpuesto).",
               ".$this->var2str($this->descripcion).",".$this->var2str($this->dtopor).",
               ".$this->var2str($this->idpedido).",".$this->var2str($this->irpf).",".$this->var2str($this->iva).",
               ".$this->var2str($this->pvpsindto).",".$this->var2str($this->pvptotal).",".$this->var2str($this->pvpunitario).",
               ".$this->var2str($this->recargo).",".$this->var2str($this->referencia).");";

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
      $this->cache->delete('pedcli_top_articulos');
   }

   public function all_from_pedido($idp)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idpedido = ".$this->var2str($idp)." ORDER BY referencia ASC;");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      return $linealist;
   }
   
   public function all_from_articulo($ref, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE referencia = ".$this->var2str($ref).
              " ORDER BY idpedido DESC", $limit, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
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
      $sql .= " ORDER BY idpedido DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      return $linealist;
   }
   
   public function search_from_cliente($codcliente, $query='', $offset=0)
   {
      $linealist = array();
      $query = strtolower( $this->no_html($query) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpedido IN
         (SELECT idpedido FROM pedidoscli WHERE codcliente = ".$this->var2str($codcliente).") AND ";
      if( is_numeric($query) )
      {
         $sql .= "(referencia LIKE '%".$query."%' OR descripcion LIKE '%".$query."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "(lower(referencia) LIKE '%".$buscar."%' OR lower(descripcion) LIKE '%".$buscar."%')";
      }
      $sql .= " ORDER BY idpedido DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      return $linealist;
   }
   
   public function search_from_cliente2($codcliente, $ref='', $obs='', $offset=0)
   {
      $linealist = array();
      $ref = strtolower( $this->no_html($ref) );
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpedido IN
         (SELECT idpedido FROM pedidosscli WHERE codcliente = ".$this->var2str($codcliente)."
         AND lower(observaciones) LIKE '".strtolower($obs)."%') AND ";
      if( is_numeric($ref) )
      {
         $sql .= "(referencia LIKE '%".$ref."%' OR descripcion LIKE '%".$ref."%')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $ref);
         $sql .= "(lower(referencia) LIKE '%".$ref."%' OR lower(descripcion) LIKE '%".$ref."%')";
      }
      $sql .= " ORDER BY idpedido DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      return $linealist;
   }
   
   public function last_from_cliente($codcliente, $offset=0)
   {
      $linealist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE idpedido IN
         (SELECT idpedido FROM pedidoscli WHERE codcliente = ".$this->var2str($codcliente).")
         ORDER BY idpedido DESC, idlinea ASC";
      
      $lineas = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_pedido_cliente($l);
      }
      return $linealist;
   }
   
   public function count_by_articulo()
   {
      $num = 0;
      $lineas = $this->db->select("SELECT COUNT(DISTINCT referencia) as total FROM ".
              $this->table_name.";");
      if($lineas)
         $num = intval($lineas[0]['total']);
      return $num;
   }
   
   public function top_by_articulo()
   {
      $toplist = $this->cache->get_array('pedcli_top_articulos');
      if( !$toplist )
      {
         $articulo = new articulo();
         $lineas = $this->db->select_limit("SELECT referencia, SUM(cantidad) as ventas FROM ".
                 $this->table_name." GROUP BY referencia ORDER BY ventas DESC", FS_ITEM_LIMIT, 0);
         if($lineas)
         {
            foreach($lineas as $l)
            {
               $art0 = $articulo->get($l['referencia']);
               if($art0)
                  $toplist[] = array($art0, intval($l['ventas']));
            }
         }
         $this->cache->set('pedcli_top_articulos', $toplist);
      }
      return $toplist;
   }
}
