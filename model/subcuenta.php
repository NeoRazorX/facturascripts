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
require_model('cuenta.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('partida.php');

/**
 * El cuarto nivel de un plan contable. Está relacionada con una única cuenta.
 */
class subcuenta extends fs_model
{
   public $idsubcuenta;
   public $codsubcuenta;
   public $idcuenta;
   public $codcuenta;
   public $codejercicio;
   public $coddivisa;
   public $codimpuesto;
   public $descripcion;
   public $haber;
   public $debe;
   public $saldo;
   public $recargo;
   public $iva;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('co_subcuentas');
      if($s)
      {
         $this->idsubcuenta = $this->intval($s['idsubcuenta']);
         $this->codsubcuenta = $s['codsubcuenta'];
         $this->idcuenta = $this->intval($s['idcuenta']);
         $this->codcuenta = $s['codcuenta'];
         $this->codejercicio = $s['codejercicio'];
         $this->coddivisa = $s['coddivisa'];
         $this->codimpuesto = $s['codimpuesto'];
         $this->descripcion = $s['descripcion'];
         $this->debe = floatval($s['debe']);
         $this->haber = floatval($s['haber']);
         $this->saldo = floatval($s['saldo']);
         $this->recargo = floatval($s['recargo']);
         $this->iva = floatval($s['iva']);
      }
      else
      {
         $this->idsubcuenta = NULL;
         $this->codsubcuenta = NULL;
         $this->idcuenta = NULL;
         $this->codcuenta = NULL;
         $this->codejercicio = NULL;
         $this->coddivisa = $this->default_items->coddivisa();
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->debe = 0;
         $this->haber = 0;
         $this->saldo = 0;
         $this->recargo = 0;
         $this->iva = 0;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      
      /// eliminamos todos los PDFs relacionados
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_mayor') )
      {
         foreach(glob('tmp/'.FS_TMP_NAME.'libro_mayor/*') as $file)
         {
            if( is_file($file) )
               unlink($file);
         }
      }
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_diario') )
      {
         foreach(glob('tmp/'.FS_TMP_NAME.'libro_diario/*') as $file)
         {
            if( is_file($file) )
               unlink($file);
         }
      }
      if( file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances') )
      {
         foreach(glob('tmp/'.FS_TMP_NAME.'inventarios_balances/*') as $file)
         {
            if( is_file($file) )
               unlink($file);
         }
      }
      
      /// forzamos la creación de la tabla de cuentas
      $cuenta = new cuenta();
      return '';
   }
   
   public function get_descripcion_64()
   {
      return base64_encode($this->descripcion);
   }
   
   public function tasaconv()
   {
      if( isset($this->coddivisa) )
      {
         $divisa = new divisa();
         $div0 = $divisa->get($this->coddivisa);
         if($div0)
            return $div0->tasaconv;
         else
            return 1;
      }
      else
         return 1;
   }
   
   public function url()
   {
      if( is_null($this->idsubcuenta) )
         return 'index.php?page=contabilidad_cuentas';
      else
         return 'index.php?page=contabilidad_subcuenta&id='.$this->idsubcuenta;
   }
   
   public function get_cuenta()
   {
      $cuenta = new cuenta();
      return $cuenta->get($this->idcuenta);
   }
   
   public function get_ejercicio()
   {
      $eje = new ejercicio();
      return $eje->get($this->codejercicio);
   }
   
   public function get_partidas($offset=0)
   {
      $part = new partida();
      return $part->all_from_subcuenta($this->idsubcuenta, $offset);
   }
   
   public function get_partidas_full()
   {
      $part = new partida();
      return $part->full_from_subcuenta($this->idsubcuenta);
   }
   
   public function count_partidas()
   {
      $part = new partida();
      return $part->count_from_subcuenta($this->idsubcuenta);
   }
   
   public function get_totales()
   {
      $part = new partida();
      return $part->totales_from_subcuenta( $this->idsubcuenta );
   }
   
   public function get($id)
   {
      $subc = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsubcuenta = ".$this->var2str($id).";");
      if($subc)
      {
         return new subcuenta($subc[0]);
      }
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod, $ejercicio, $crear=FALSE)
   {
      $subc = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codsubcuenta = ".$this->var2str($cod).
              " AND codejercicio = ".$this->var2str($ejercicio).";");
      if($subc)
      {
         return new subcuenta($subc[0]);
      }
      else if($crear)
      {
         /// buscamos la subcuenta equivalente en otro ejercicio
         $subc = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codsubcuenta = ".$this->var2str($cod).";");
         if($subc)
         {
            $old_sc = new subcuenta($subc[0]);
            
            /// buscamos la cuenta equivalente es ESTE ejercicio
            $cuenta = new cuenta();
            $new_c = $cuenta->get_by_codigo($old_sc->codcuenta, $ejercicio);
            if($new_c)
            {
               $new_sc = new subcuenta();
               $new_sc->codcuenta = $new_c->codcuenta;
               $new_sc->coddivisa = $old_sc->coddivisa;
               $new_sc->codejercicio = $ejercicio;
               $new_sc->codimpuesto = $old_sc->codimpuesto;
               $new_sc->codsubcuenta = $old_sc->codsubcuenta;
               $new_sc->descripcion = $old_sc->descripcion;
               $new_sc->idcuenta = $new_c->idcuenta;
               $new_sc->iva = $old_sc->iva;
               $new_sc->recargo = $old_sc->recargo;
               if( $new_sc->save() )
               {
                  return $new_sc;
               }
               else
                  return FALSE;
            }
            else
            {
               $this->new_error_msg('No se ha encontrado la cuenta equivalente a '.$old_sc->codcuenta.' en el ejercicio '.$ejercicio.'.');
               return FALSE;
            }
         }
         else
         {
            $this->new_error_msg('No se ha encontrado ninguna subcuenta equivalente a '.$cod.' para copiar.');
            return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function get_cuentaesp($id, $eje)
   {
      $data = $this->db->select("SELECT * FROM co_subcuentas WHERE idcuenta IN "
         ."(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ".$this->var2str($id)." AND codejercicio = ".$this->var2str($eje).");");
      if($data)
      {
         return new subcuenta($data[0]);
      }
      else
         return FALSE;
   }
   
   public function is_outdated()
   {
      $sql = "SELECT * FROM ".$this->table_name." WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).
              " AND codejercicio IN (SELECT codejercicio FROM ejercicios WHERE estado = 'ABIERTO')".
              " AND (debe != (SELECT COALESCE(SUM(debe),0) as debe FROM co_partidas
                   WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).")
                 OR haber != (SELECT COALESCE(SUM(haber),0) as haber FROM co_partidas
                   WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).")
                 OR saldo != (SELECT COALESCE(SUM(debe),0)-COALESCE(SUM(haber),0) as saldo
                   FROM co_partidas WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).")
              );";
      
      if( $this->db->select($sql) )
      {
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function tiene_saldo()
   {
      return !$this->floatcmp($this->debe, $this->haber);
   }
   
   public function exists()
   {
      if( is_null($this->idsubcuenta) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      
      $totales = $this->get_totales();
      $this->debe = round($totales['debe'], FS_NF0+1);
      $this->haber = round($totales['haber'], FS_NF0+1);
      $this->saldo = round($totales['saldo'], FS_NF0+1);
      
      if( strlen($this->codsubcuenta)>0 AND strlen($this->descripcion)>0 )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg('Faltan datos en la subcuenta.');
         return FALSE;
      }
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codsubcuenta = ".$this->var2str($this->codsubcuenta).",
               idcuenta = ".$this->var2str($this->idcuenta).", codcuenta = ".$this->var2str($this->codcuenta).",
               codejercicio = ".$this->var2str($this->codejercicio).",
               coddivisa = ".$this->var2str($this->coddivisa).",
               codimpuesto = ".$this->var2str($this->codimpuesto).",
               descripcion = ".$this->var2str($this->descripcion).",
               recargo = ".$this->var2str($this->recargo).", iva = ".$this->var2str($this->iva).",
               debe = ".$this->var2str($this->debe).", haber = ".$this->var2str($this->haber).",
               saldo = ".$this->var2str($this->saldo)." WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).";";
         }
         else
         {
            $newid = $this->db->nextval($this->table_name.'_idsubcuenta_seq');
            if($newid)
            {
               $this->idsubcuenta = intval($newid);
               $sql = "INSERT INTO ".$this->table_name." (idsubcuenta,codsubcuenta,idcuenta,codcuenta,
                  codejercicio,coddivisa,codimpuesto,descripcion,debe,haber,saldo,recargo,iva) VALUES
                  (".$this->var2str($this->idsubcuenta).",".$this->var2str($this->codsubcuenta).",
                  ".$this->var2str($this->idcuenta).",
                  ".$this->var2str($this->codcuenta).",".$this->var2str($this->codejercicio).",
                  ".$this->var2str($this->coddivisa).",".$this->var2str($this->codimpuesto).",
                  ".$this->var2str($this->descripcion).",0,0,0,
                  ".$this->var2str($this->recargo).",".$this->var2str($this->iva).");";
            }
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).";");
   }
   
   public function clean_cache()
   {
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->idsubcuenta.'.pdf') )
         unlink('tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->idsubcuenta.'.pdf');
      
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_diario/'.$this->codejercicio.'.pdf') )
         unlink('tmp/'.FS_TMP_NAME.'libro_diario/'.$this->codejercicio.'.pdf');
      
      if( file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$this->codejercicio.'.pdf') )
         unlink('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$this->codejercicio.'.pdf');
   }
   
   public function all()
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY idsubcuenta DESC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function all_from_cuenta($idcuenta)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idcuenta = ".$this->var2str($idcuenta)." ORDER BY codsubcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function all_from_ejercicio($codejercicio)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codejercicio = ".$this->var2str($codejercicio).
              " ORDER BY codsubcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function search($query)
   {
      $sublist = array();
      $query = strtolower( $this->no_html($query) );
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codsubcuenta LIKE '".$query."%' OR codsubcuenta LIKE '%".$query."'
               OR lower(descripcion) LIKE '%".$query."%'
               ORDER BY codejercicio DESC, codcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function search_by_ejercicio($ejercicio, $query)
   {
      $query = $this->escape_string( strtolower( trim($query) ) );
      
      $sublist = $this->cache->get_array('search_subcuenta_ejercicio_'.$ejercicio.'_'.$query);
      if( count($sublist) < 1 )
      {
         $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codejercicio = ".$this->var2str($ejercicio).
              " AND (codsubcuenta LIKE '".$query."%' OR codsubcuenta LIKE '%".$query."'
               OR lower(descripcion) LIKE '%".$query."%')
               ORDER BY codcuenta ASC;");
         
         if($subcuentas)
         {
            foreach($subcuentas as $s)
               $sublist[] = new subcuenta($s);
         }
         
         $this->cache->set('search_subcuenta_ejercicio_'.$ejercicio.'_'.$query, $sublist, 300);
      }
      
      return $sublist;
   }
}
