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
require_once 'model/albaran_cliente.php';
require_once 'model/albaran_proveedor.php';
require_once 'model/familia.php';
require_once 'model/impuesto.php';
require_once 'model/tarifa.php';

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
         $this->idstock = $this->intval($s['idstock']);
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
      $a = new articulo();
      return '';
   }
   
   public function set_cantidad($c=0)
   {
      $c = floatval($c);
      if($c > 0)
         $this->cantidad = $c;
      else
         $this->cantidad = 0;
      $this->disponible = ($this->cantidad - $this->reservada);
   }
   
   public function sum_cantidad($c=0)
   {
      $c = floatval($c);
      $this->cantidad += $c;
      if($this->cantidad < 0)
         $this->cantidad = 0;
      $this->disponible = ($this->cantidad - $this->reservada);
   }
   
   public function get($id)
   {
      $stock = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idstock = ".$this->var2str($id).";");
      if($stock)
         return new stock($stock[0]);
      else
         return FALSE;
   }
   
   public function get_by_referencia($ref)
   {
      $stock = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if($stock)
         return new stock($stock[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idstock) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idstock = ".$this->var2str($this->idstock).";");
   }
   
   public function new_idstock()
   {
      $id = $this->db->select("SELECT nextval('".$this->table_name."_idstock_seq');");
      if($id)
         $this->idstock = intval($id[0]['nextval']);
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codalmacen = ".$this->var2str($this->codalmacen).",
            referencia = ".$this->var2str($this->referencia).", nombre = ".$this->var2str($this->nombre).",
            cantidad = ".$this->var2str($this->cantidad).", reservada = ".$this->var2str($this->reservada).",
            disponible = ".$this->var2str($this->disponible).", pterecibir = ".$this->var2str($this->pterecibir)."
            WHERE idstock = ".$this->var2str($this->idstock).";";
      }
      else
      {
         $this->new_idstock();
         $sql = "INSERT INTO ".$this->table_name." (idstock,codalmacen,referencia,nombre,cantidad,reservada,
            disponible,pterecibir) VALUES (".$this->var2str($this->idstock).",".$this->var2str($this->codalmacen).",
            ".$this->var2str($this->referencia).",".$this->var2str($this->nombre).",".$this->var2str($this->cantidad).",
            ".$this->var2str($this->reservada).",".$this->var2str($this->disponible).",".$this->var2str($this->pterecibir).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idstock = ".$this->var2str($this->idstock).";");
   }
   
   public function all_from_articulo($ref)
   {
      $stocklist = array();
      $stocks = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if($stocks)
      {
         foreach($stocks as $s)
            $stocklist[] = new stock($s);
      }
      return $stocklist;
   }
   
   public function total_from_articulo($ref)
   {
      $num = 0;
      $stocks = $this->db->select("SELECT SUM(cantidad) as total FROM ".$this->table_name."
         WHERE referencia = ".$this->var2str($ref).";");
      if($stocks)
         $num = floatval($stocks[0]['total']);
      return $num;
   }
   
   public function count()
   {
      $num = 0;
      $stocks = $this->db->select("SELECT COUNT(*) as total FROM ".$this->table_name.";");
      if($stocks)
         $num = intval($stocks[0]['total']);
      return $num;
   }
   
   public function count_by_articulo()
   {
      $num = 0;
      $stocks = $this->db->select("SELECT COUNT(DISTINCT referencia) as total FROM ".$this->table_name.";");
      if($stocks)
         $num = intval($stocks[0]['total']);
      return $num;
   }
}


class tarifa_articulo extends fs_model
{
   public $id;
   public $referencia;
   public $codtarifa;
   public $nombre;
   public $pvp;
   public $descuento;
   public $iva;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('articulostarifas');
      if( $t )
      {
         $this->id = $this->intval( $t['id'] );
         $this->referencia = $t['referencia'];
         $this->codtarifa = $t['codtarifa'];
         $this->descuento = floatval($t['descuento']);
      }
      else
      {
         $this->id = NULL;
         $this->referencia = NULL;
         $this->codtarifa = NULL;
         $this->descuento = 0;
      }
      $this->nombre = NULL;
      $this->pvp = 0;
      $this->iva = 0;
   }
   
   protected function install()
   {
      $a = new articulo();
      $t = new tarifa();
      return '';
   }
   
   public function show_descuento()
   {
      return number_format($this->descuento, 2, '.', ' ');
   }
   
   public function show_pvp_iva($coma=TRUE)
   {
      if( $coma )
         return number_format($this->pvp*(100-$this->descuento)/100*(100+$this->iva)/100, 2, '.', ' ');
      else
         return number_format($this->pvp*(100-$this->descuento)/100*(100+$this->iva)/100, 2, '.', '');
   }
   
   public function set_pvp_iva($p)
   {
      $pvpi = floatval($p);
      if($this->pvp > 0)
         $this->descuento = 100 - 10000*$pvpi/($this->pvp*(100+$this->iva));
      else
         $this->descuento = 0;
   }
   
   public function get($id)
   {
      $tarifa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if( $tarifa )
         return new tarifa_articulo($tarifa[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET referencia = ".$this->var2str($this->referencia).",
            codtarifa = ".$this->var2str($this->codtarifa).", descuento = ".$this->var2str($this->descuento)."
            WHERE id = ".$this->var2str($this->id).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (referencia,codtarifa,descuento) VALUES
            (".$this->var2str($this->referencia).",".$this->var2str($this->codtarifa).",".$this->var2str($this->descuento).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_articulo($ref)
   {
      $tarlist = array();
      $tarifas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if( $tarifas )
      {
         foreach($tarifas as $t)
            $tarlist[] = new tarifa_articulo($t);
      }
      return $tarlist;
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
   
   private $imagen;
   private $has_imagen;
   
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
         $this->descripcion = $this->no_html($a['descripcion']);
         $this->pvp = floatval($a['pvp']);
         $this->factualizado = Date('d-m-Y', strtotime($a['factualizado']));
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
         $this->observaciones = $this->no_html($a['observaciones']);
         
         /// no cargamos la imágen directamente por cuestión de rendimiento
         $this->imagen = NULL;
         if( isset($a['imagen']) )
            $this->has_imagen = TRUE;
         else
            $this->has_imagen = FALSE;
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
         $this->has_imagen = FALSE;
      }
      $this->pvp_ant = 0;
      $this->iva = NULL;
   }
   
   protected function install()
   {
      $fam = new familia();
      $imp = new impuesto();
      return '';
   }
   
   public function get_descripcion_64()
   {
      return base64_encode($this->descripcion);
   }
   
   public function show_pvp()
   {
      return number_format($this->pvp, 2, '.', ' ');
   }
   
   public function show_pvp_iva($coma=TRUE)
   {
      if($coma)
         return number_format($this->pvp * (100 + $this->get_iva()) / 100, 2, '.', ' ');
      else
         return number_format($this->pvp * (100 + $this->get_iva()) / 100, 2, '.', '');
   }
   
   public function url()
   {
      if( is_null($this->referencia) )
         return "index.php?page=general_articulos";
      else
         return "index.php?page=general_articulo&ref=".$this->referencia;
   }
   
   public function get($ref)
   {
      $art = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if($art)
         return new articulo($art[0]);
      else
         return FALSE;
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
   
   public function get_equivalentes()
   {
      $artilist = array();
      if( !is_null($this->equivalencia) )
      {
         $articulos = $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE equivalencia = ".$this->var2str($this->equivalencia)." ORDER BY referencia ASC;");
         if($articulos)
         {
            foreach($articulos as $a)
            {
               if($a['referencia'] != $this->referencia)
                  $artilist[] = new articulo($a);
            }
         }
      }
      return $artilist;
   }
   
   /*
    * Devuelve un array con las tarifas asignadas a ese artículo.
    * Si todas = TRUE -> devuelve además las que no están asignadas.
    */
   public function get_tarifas($todas=FALSE)
   {
      $tarifa = new tarifa();
      $tarifas = $tarifa->all();
      $tarifa_articulo = new tarifa_articulo();
      $tas = $tarifa_articulo->all_from_articulo( $this->referencia );
      if( $todas )
      {
         foreach($tarifas as $t)
         {
            $encontrada = FALSE;
            foreach($tas as $ta)
            {
               if( $ta->codtarifa == $t->codtarifa )
               {
                  $encontrada = TRUE;
                  break;
               }
            }
            if( !$encontrada )
            {
               /// añadimos las tarifas que no tiene asignadas
               $tas[] = new tarifa_articulo( array('id' => NULL, 'codtarifa' => $t->codtarifa,
                   'referencia' => $this->referencia, 'descuento' => 0 - $t->incporcentual) );
            }
         }
      }
      /// rellenamos las tarifas
      foreach($tas as $ta)
      {
         foreach($tarifas as $t)
         {
            if( $t->codtarifa == $ta->codtarifa )
            {
               $ta->nombre = $t->nombre;
               $ta->pvp = $this->pvp;
               $ta->iva = $this->get_iva();
               break;
            }
         }
      }
      return $tas;
   }
   
   public function get_lineas_albaran_cli($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linea = new linea_albaran_cliente();
      return $linea->all_from_articulo($this->referencia, $offset, $limit);
   }
   
   public function get_lineas_albaran_prov($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $linea = new linea_albaran_proveedor();
      return $linea->all_from_articulo($this->referencia, $offset, $limit);
   }
   
   public function imagen_url()
   {
      if( $this->has_imagen )
      {
         if( file_exists('tmp/articulos/'.$this->referencia.'.png') )
            return '../tmp/articulos/'.$this->referencia.'.png';
         else
         {
            if( is_null($this->imagen) )
            {
               $imagen = $this->db->select("SELECT imagen FROM ".$this->table_name."
                  WHERE referencia = ".$this->var2str($this->referencia).";");
               if($imagen)
                  $this->imagen = $this->str2bin($imagen[0]['imagen']);
               else
               {
                  $this->imagen = NULL;
                  $this->has_imagen = FALSE;
               }
            }
            
            if( !is_null($this->imagen) )
            {
               if( !file_exists('tmp/articulos') )
                  mkdir('tmp/articulos');
               
               $f = fopen('tmp/articulos/'.$this->referencia.'.png', 'a');
               fwrite($f, $this->imagen);
               fclose($f);
               return '../tmp/articulos/'.$this->referencia.'.png';
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function set_imagen($img)
   {
      $this->imagen = $img;
      $this->has_imagen = TRUE;
   }
   
   public function set_pvp($p)
   {
      $this->pvp_ant = $this->pvp;
      $this->factualizado = Date('d-m-Y');
      
      $iva = $this->get_iva();
      /// redondeamos el pvp+iva para a continuación recalcular el pvp
      $pvpi = round(floatval($p)*(100+$iva)/100, 2);
      $this->pvp = (100*$pvpi)/(100+$iva);
   }
   
   public function set_pvp_iva($p)
   {
      $this->pvp_ant = $this->pvp;
      $this->factualizado = Date('d-m-Y');
      
      $pvpi = round(floatval($p), 2);
      $iva = $this->get_iva();
      $this->pvp = (100*$pvpi)/(100+$iva);
   }
   
   public function set_stock($almacen, $cantidad=1)
   {
      $result = FALSE;
      $stock = new stock();
      $encontrado = FALSE;
      $stocks = $stock->all_from_articulo($this->referencia);
      foreach($stocks as $k => $value)
      {
         if($value->codalmacen == $almacen)
         {
            $stocks[$k]->set_cantidad($cantidad);
            $result = $stocks[$k]->save();
            $encontrado = TRUE;
         }
      }
      if( !$encontrado )
      {
         $stock->referencia = $this->referencia;
         $stock->codalmacen = $almacen;
         $stock->set_cantidad($cantidad);
         $result = $stock->save();
      }
      if($result)
      {
         $nuevo_stock = $stock->total_from_articulo($this->referencia);
         if($this->stockfis != $nuevo_stock)
         {
            $this->stockfis =  $nuevo_stock;
            if( !$this->save() )
               $this->new_error_msg("Error al actualizar el stock del artículo");
         }
      }
      else
         $this->new_error_msg("Error al guardar el stock");
      return $result;
   }
   
   public function sum_stock($almacen, $cantidad=1)
   {
      $result = FALSE;
      $stock = new stock();
      $encontrado = FALSE;
      $stocks = $stock->all_from_articulo($this->referencia);
      foreach($stocks as $k => $value)
      {
         if($value->codalmacen == $almacen)
         {
            $stocks[$k]->sum_cantidad($cantidad);
            $result = $stocks[$k]->save();
            $encontrado = TRUE;
            break;
         }
      }
      if( !$encontrado )
      {
         $stock->referencia = $this->referencia;
         $stock->codalmacen = $almacen;
         $stock->set_cantidad($cantidad);
         $result = $stock->save();
      }
      if($result)
      {
         $nuevo_stock = $stock->total_from_articulo($this->referencia);
         if($this->stockfis != $nuevo_stock)
         {
            $this->stockfis =  $nuevo_stock;
            if( !$this->save() )
               $this->new_error_msg("¡Error al actualizar el stock del artículo!");
         }
      }
      else
         $this->new_error_msg("¡Error al guardar el stock!");
      return $result;
   }
   
   public function exists()
   {
      if( is_null($this->referencia) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($this->referencia).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      /// cargamos la imágen si todavía no lo habíamos hecho
      if( $this->has_imagen )
      {
         if( is_null($this->imagen) )
         {
            $imagen = $this->db->select("SELECT imagen FROM ".$this->table_name."
               WHERE referencia = ".$this->var2str($this->referencia).";");
            if($imagen)
               $this->imagen = $this->str2bin($imagen[0]['imagen']);
            else
            {
               $this->imagen = NULL;
               $this->has_imagen = FALSE;
            }
         }
      }
      /// eliminamos la imágen del directorio para actualizarla
      if( file_exists('tmp/articulos/'.$this->referencia.'.png') )
         unlink('tmp/articulos/'.$this->referencia.'.png');
      
      $this->referencia = str_replace(' ', '_', trim($this->referencia));
      $this->descripcion = $this->no_html($this->descripcion);
      $this->equivalencia = str_replace(' ', '_', trim($this->equivalencia));
      $this->codbarras = $this->no_html($this->codbarras);
      $this->observaciones = $this->no_html($this->observaciones);
      
      if($this->equivalencia == '')
      {
         $this->equivalencia = NULL;
         $this->destacado = FALSE;
      }
      
      if( !preg_match("/^[A-Z0-9_\+\.\*\/\-]{1,18}$/i", $this->referencia) )
      {
         $this->new_error_msg("¡Referencia de artículo no válida! Debe tener entre 1 y 18 caracteres.
            Se admiten letras (excepto Ñ), números, '_', '.', '*', '/' ó '-'.");
      }
      else if( strlen($this->descripcion) > 100 )
         $this->descripcion = substr($this->descripcion, 0, 99);
      else if( !is_null($this->equivalencia) AND !preg_match("/^[A-Z0-9_\+\.\*\/\-]{1,18}$/i", $this->equivalencia) )
      {
         $this->new_error_msg("¡Código de equivalencia del artículos no válido! Debe tener entre 1 y 18 caracteres.
            Se admiten letras (excepto Ñ), números, '_', '.', '*', '/' ó '-'.");
      }
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
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
               imagen = ".$this->bin2str($this->imagen)." WHERE referencia = ".$this->var2str($this->referencia).";";
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
      else
         return FALSE;
   }
   
   public function delete()
   {
      if( file_exists('tmp/articulos/'.$this->referencia.'.png') )
         unlink('tmp/articulos/'.$this->referencia.'.png');
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE referencia = ".$this->var2str($this->referencia).";");
   }
   
   public function search($query, $offset=0, $codfamilia='', $con_stock=FALSE)
   {
      $artilist = array();
      $query = $this->no_html( strtolower($query) );
      
      if($codfamilia == '')
         $sql = "SELECT * FROM ".$this->table_name." WHERE ";
      else
         $sql = "SELECT * FROM ".$this->table_name." WHERE codfamilia = ".$this->var2str($codfamilia)." AND ";
      
      if($con_stock)
         $sql .= "stockfis > 0 AND ";
      
      if( is_numeric($query) )
      {
         $sql .= "(referencia ~~ '%".$query."%' OR equivalencia ~~ '%".$query."%' OR descripcion ~~ '%".$query."%'
            OR codbarras = '".$query."')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $sql .= "(lower(referencia) ~~ '%".$buscar."%' OR lower(equivalencia) ~~ '%".$buscar."%'
            OR lower(descripcion) ~~ '%".$buscar."%')";
      }
      
      $sql .= " ORDER BY referencia ASC";
      
      $articulos = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
            $artilist[] = new articulo($a);
      }
      return $artilist;
   }
   
   public function multiplicar_precios($codfam, $m=1)
   {
      if(isset($codfam) AND $m != 1)
         return $this->db->exec("UPDATE ".$this->table_name." SET pvp = (pvp*".floatval($m).")
            WHERE codfamilia = ".$this->var2str($codfam).";");
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
      $articulos = $this->db->select_limit("SELECT * FROM ".$this->table_name."
         WHERE codfamilia = ".$this->var2str($codfamilia)." ORDER BY referencia ASC", $limit, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
            $artilist[] = new articulo($a);
      }
      return $artilist;
   }
   
   public function count($codfamilia=FALSE)
   {
      $num = 0;
      if( $codfamilia )
         $articulos = $this->db->select("SELECT COUNT(*) as total FROM ".$this->table_name."
            WHERE codfamilia = ".$this->var2str($codfamilia).";");
      else
         $articulos = $this->db->select("SELECT COUNT(*) as total FROM ".$this->table_name.";");
      if($articulos)
         $num = intval($articulos[0]['total']);
      return $num;
   }
   
   public function move_codimpuesto($cod0, $cod1, $mantener=FALSE)
   {
      if($mantener)
      {
         $impuesto = new impuesto();
         $impuesto0 = $impuesto->get($cod0);
         $impuesto1 = $impuesto->get($cod1);
         $multiplo = (100 + $impuesto0->iva) / (100 + $impuesto1->iva);
         return $this->db->exec("UPDATE ".$this->table_name." SET codimpuesto = ".$this->var2str($cod1).",
            pvp = (pvp*".$multiplo.") WHERE codimpuesto = ".$this->var2str($cod0).";");
      }
      else
         return $this->db->exec("UPDATE ".$this->table_name." SET codimpuesto = ".$this->var2str($cod1)."
            WHERE codimpuesto = ".$this->var2str($cod0).";");
   }
}

?>
