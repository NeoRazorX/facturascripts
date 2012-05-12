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
require_once 'model/familia.php';
require_once 'model/impuesto.php';

class stock extends fs_model
{
   public $idstock;
   public $codalmacen;
   public $referencia;
   public $nombre;
   public $cantidad;
   public $reservada;
   public $disponible;
   public $pterecibir;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('stocks');
      if($s)
      {
         $this->idstock = intval($s['idstock']);
         $this->codalmacen = $s['codalmacen'];
         $this->referencia = $s['referencia'];
         $this->nombre = $s['nombre'];
         $this->cantidad = floatval($s['cantidad']);
         $this->reservada = floatval($s['reservada']);
         $this->disponible = floatval($s['disponible']);
         $this->pterecibir = floatval($s['pterecibir']);
      }
      else
      {
         $this->idstock = NULL;
         $this->codalmacen = NULL;
         $this->referencia = NULL;
         $this->nombre = '';
         $this->cantidad = 0;
         $this->reservada = 0;
         $this->disponible = 0;
         $this->pterecibir = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idstock) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idstock = '".$this->idstock."';");
   }
   
   public function get_new_idstock()
   {
      return $this->db->select("SELECT nextval('".$this->table_name."_idstock_seq;');");
   }

   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codalmacen = ".$this->var2str($this->codalmacen).",
            referencia = ".$this->var2str($this->referencia).", nombre = ".$this->var2str($this->nombre).",
            cantidad = ".$this->var2str($this->cantidad).", reservada = ".$this->var2str($this->reservada).",
            disponible = ".$this->var2str($this->disponible).", pterecibir = ".$this->var2str($this->pterecibir)."
            WHERE idstock = '".$this->idstock."';";
      }
      else
      {
         $this->idstock = $this->get_new_idstock();
         $sql = "INSERT INTO ".$this->table_name." (idstock,codalmacen,referencia,nombre,cantidad,reservada,
            disponible,pterecibir) VALUES (".$this->var2str($this->idstock).",".$this->var2str($this->codalmacen).",
            ".$this->var2str($this->referencia).",".$this->var2str($this->nombre).",".$this->var2str($this->cantidad).",
            ".$this->var2str($this->reservada).",".$this->var2str($this->disponible).",".$this->var2str($this->pterecibir).");";
      }
      $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idstock = '".$this->idstock."';");
   }
   
   public function all_from_articulo($ref)
   {
      $stocklist = array();
      $stocks = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = '".$ref."';");
      if($stocks)
      {
         foreach($stocks as $s)
            $stocklist[] = new stock($s);
      }
      return $stocklist;
   }
}


class articulo extends fs_model
{
   public $referencia;
   public $codfamilia;
   public $descripcion;
   public $pvp;
   public $pvp_ant;
   public $factualizado;
   public $codimpuesto;
   public $iva;
   public $destacado;
   public $bloqueado;
   public $secompra;
   public $sevende;
   public $equivalencia;
   public $stockfis;
   public $stockmin;
   public $stockmax;
   public $controlstock; /// permitir ventas sin stock
   public $codbarras;
   public $observaciones;
   public $imagen;
   
   private static $impuestos;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('articulos');
      
      if( !isset(self::$impuestos) )
         self::$impuestos = array();
      
      if($a)
      {
         $this->referencia = $a['referencia'];
         $this->codfamilia = $a['codfamilia'];
         $this->descripcion = $a['descripcion'];
         $this->pvp = floatval($a['pvp']);
         $this->factualizado = $a['factualizado'];
         $this->codimpuesto = $a['codimpuesto'];
         $this->stockfis = floatval($a['stockfis']);
         $this->stockmin = floatval($a['stockmin']);
         $this->stockmax = floatval($a['stockmax']);
         $this->controlstock = ($a['controlstock'] == 't');
         $this->destacado = ($a['destacado'] == 't');
         $this->bloqueado = ($a['bloqueado'] == 't');
         $this->secompra = ($a['secompra'] == 't');
         $this->sevende = ($a['sevende'] == 't');
         $this->equivalencia = $a['equivalencia'];
         $this->codbarras = $a['codbarras'];
         $this->observaciones = $a['observaciones'];
         /// no cargamos la imágen al principio por cuestiones de rendiemiento
         ///$this->imagen = $this->str2bin($a['imagen']);
      }
      else
      {
         $this->referencia = NULL;
         $this->codfamilia = NULL;
         $this->descripcion = '';
         $this->pvp = 0;
         $this->factualizado = Date('d-m-Y');
         $this->codimpuesto = NULL;
         $this->stockfis = 0;
         $this->stockmin = 0;
         $this->stockmax = 0;
         $this->controlstock = TRUE;
         $this->destacado = FALSE;
         $this->bloqueado = FALSE;
         $this->secompra = TRUE;
         $this->sevende = TRUE;
         $this->equivalencia = NULL;
         $this->codbarras = '';
         $this->observaciones = '';
         $this->imagen = NULL;
      }
      $this->pvp_ant = 0;
      $this->iva = NULL;
   }
   
   public function show_pvp()
   {
      return number_format($this->pvp, 2, ',', '.');
   }
   
   public function show_pvp_iva($coma=TRUE)
   {
      if($coma)
         return number_format($this->pvp + ($this->pvp * $this->get_iva() / 100), 2, ',', '.');
      else
         return number_format($this->pvp + ($this->pvp * $this->get_iva() / 100), 2, '.', '');
   }
   
   public function show_factualizado()
   {
      return Date('d-m-Y', strtotime($this->factualizado));
   }
   
   public function url()
   {
      return "index.php?page=general_articulo&ref=".$this->referencia;
   }
   
   public function get_familia()
   {
      $fam = new familia();
      return $fam->get($this->codfamilia);
   }
   
   public function get_stock()
   {
      $stock = new stock();
      return $stock->all_from_articulo($this->referencia);
   }
   
   public function get_impuesto()
   {
      $imp = new impuesto();
      return $imp->get($this->codimpuesto);
   }
   
   public function get_iva()
   {
      if( isset($this->iva) )
         return $this->iva;
      else
      {
         $encontrado = FALSE;
         foreach(self::$impuestos as $i)
         {
            if($i->codimpuesto == $this->codimpuesto)
            {
               $this->iva = floatval($i->iva);
               $encontrado = TRUE;
               break;
            }
         }
         if( !$encontrado )
         {
            $imp = new impuesto();
            $imp = $imp->get($this->codimpuesto);
            if($imp)
            {
               $this->iva = floatval($imp->iva);
               self::$impuestos[] = $imp;
            }
            else
               $this->iva = 0;
         }
         return $this->iva;
      }
   }
   
   public function imagen_url()
   {
      if( file_exists('tmp/articulos/'.$this->referencia.'.png') )
         return '../tmp/articulos/'.$this->referencia.'.png';
      else if( !is_null($this->imagen) )
      {
         $imagen = $this->db->select("SELECT imagen FROM ".$this->table_name." WHERE referencia = '".$this->referencia."';");
         if($imagen)
         {
            $this->imagen = $this->str2bin($imagen[0]['imagen']);
            $f = fopen('tmp/articulos/'.$this->referencia.'.png', 'a');
            fwrite($f, $this->imagen);
            fclose($f);
            return '../tmp/articulos/'.$this->referencia.'.png';
         }
         else
         {
            $this->imagen = NULL;
            return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function set_referencia($ref)
   {
      $ref = str_replace(' ', '_', $ref);
      if( preg_match("/^[A-Z0-9_\+\.\*\/\-]{1,18}$/i", $ref) )
         $this->referencia = $ref;
      else
         $this->new_error_msg("¡Referencia no valida!");
   }
   
   public function set_descripcion($desc)
   {
      $desc = trim($desc);
      if(strlen($desc) > 100)
         $this->descripcion = substr($desc, 0, 99);
      else
         $this->descripcion = $desc;
   }
   
   public function set_pvp($p)
   {
      $this->pvp_ant = $this->pvp;
      $this->pvp = floatval($p);
      $this->factualizado = Date('j-n-Y');
   }
   
   public function set_pvp_iva($p)
   {
      $this->pvp_ant = $this->pvp;
      $pvpi = floatval($p);
      $iva = $this->get_iva();
      $this->pvp = (100*$pvpi)/(100+$iva);
      $this->factualizado = Date('j-n-Y');
   }

   protected function install()
   {
      $fam = new familia();
      $imp = new impuesto();
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->referencia) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = '".$this->referencia."';");
   }
   
   public function save()
   {
      /// cargamos la imágen si todavía no lo habíamos hecho
      if( !isset($this->imagen) )
      {
         $imagen = $this->db->select("SELECT imagen FROM ".$this->table_name." WHERE referencia = '".$this->referencia."';");
         if($imagen)
            $this->imagen = $this->str2bin($imagen[0]['imagen']);
         else
            $this->imagen = NULL;
      }
      /// eliminamos la imágen del directorio para actualizarla
      if( file_exists('tmp/articulos/'.$this->referencia.'.png') )
         unlink('tmp/articulos/'.$this->referencia.'.png');
      
      
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).",
            codfamilia = ".$this->var2str($this->codfamilia).", pvp = ".$this->var2str($this->pvp).",
            factualizado = ".$this->var2str($this->factualizado).", codimpuesto = ".$this->var2str($this->codimpuesto).",
            stockfis = ".$this->var2str($this->stockfis).", stockmin = ".$this->var2str($this->stockmin).",
            stockmax = ".$this->var2str($this->stockmax).",
            controlstock = ".$this->var2str($this->controlstock).", destacado = ".$this->var2str($this->destacado).",
            bloqueado = ".$this->var2str($this->bloqueado).", sevende = ".$this->var2str($this->sevende).",
            secompra = ".$this->var2str($this->secompra).", equivalencia = ".$this->var2str($this->equivalencia).",
            codbarras = ".$this->var2str($this->codbarras).", observaciones = ".$this->var2str($this->observaciones).",
            imagen = ".$this->bin2str($this->imagen)." WHERE referencia = '".$this->referencia."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (referencia,codfamilia,descripcion,pvp,factualizado,codimpuesto,stockfis,
            stockmin,stockmax,controlstock,destacado,bloqueado,secompra,sevende,equivalencia,codbarras,observaciones,imagen)
            VALUES (".$this->var2str($this->referencia).",".$this->var2str($this->codfamilia).",".$this->var2str($this->descripcion).",
            ".$this->var2str($this->pvp).",".$this->var2str($this->factualizado).",".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->stockfis).",".$this->var2str($this->stockmin).",".$this->var2str($this->stockmax).",
            ".$this->var2str($this->controlstock).",".$this->var2str($this->destacado).",".$this->var2str($this->bloqueado).",
            ".$this->var2str($this->secompra).",".$this->var2str($this->sevende).",".$this->var2str($this->equivalencia).",
            ".$this->var2str($this->codbarras).",".$this->var2str($this->observaciones).",".$this->bin2str($this->imagen).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      if( file_exists('tmp/articulos/'.$this->referencia.'.png') )
         unlink('tmp/articulos/'.$this->referencia.'.png');
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE referencia = '".$this->referencia."';");
   }
   
   public function get($ref)
   {
      $art = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = '".$ref."';");
      if($art)
         return new articulo($art[0]);
      else
         return FALSE;
   }
   
   public function search($text, $offset=0)
   {
      $artilist = array();
      if( isset($text) )
      {
         /// búsqueda por referencia y código de barras
         $sql = "SELECT * FROM ".$this->table_name;
         $text = strtolower($text);
         if( is_numeric($text) )
            $sql .= " WHERE (referencia ~~ '%".$text."%' OR codbarras = '".$text."')";
         else
            $sql .= " WHERE lower(referencia) ~~ '%".$text."%'";
         $sql .= " AND bloqueado = FALSE ORDER BY referencia ASC";
         $articulos = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
         if($articulos)
         {
            foreach($articulos as $a)
               $artilist[] = new articulo($a);
         }
         /// añadimos las búsquedas por descripción
         $sql = "SELECT * FROM ".$this->table_name." WHERE descripcion ~~ '%".$text."%' AND bloqueado = FALSE ORDER BY descripcion ASC";
         $articulos = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
         if($articulos)
         {
            foreach($articulos as $a)
            {
               if( !in_array($a, $artilist) )
                  $artilist[] = new articulo($a);
            }
         }
      }
      else
      {
         $sql = "SELECT * FROM ".$this->table_name." ORDER BY referencia ASC";
         $articulos = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
         if($articulos)
         {
            foreach($articulos as $a)
               $artilist[] = new articulo($a);
         }
      }
      return $artilist;
   }
   
   public function multiplicar_precios($codfam,$m=1)
   {
      if(isset($codfam) AND $m != 1)
         return $this->db->exec("UPDATE ".$this->table_name." SET pvp = (pvp*".$m.") WHERE codfamilia = ".$this->var2str($codfam).";");
      else
         return TRUE;
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $artilist = array();
      $articulos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY referencia ASC", $limit, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
            $artilist[] = new articulo($a);
      }
      return $artilist;
   }
   
   public function all_from_familia($codfamilia, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $artilist = array();
      $articulos = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codfamilia = '".$codfamilia."' ORDER BY referencia ASC",
                                           $limit, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
         {
            $ao = new articulo($a);
            $artilist[] = $ao;
         }
      }
      return $artilist;
   }
}

?>
