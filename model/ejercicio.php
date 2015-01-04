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
require_model('asiento.php');
require_model('subcuenta.php');

/**
 * Ejercicio contable.
 */
class ejercicio extends fs_model
{
   public $idasientocierre;
   public $idasientopyg;
   public $idasientoapertura;
   public $plancontable;
   public $longsubcuenta;
   public $estado;
   public $fechafin;
   public $fechainicio;
   public $nombre;
   public $codejercicio;

   public function __construct($e = FALSE)
   {
      parent::__construct('ejercicios');
      if($e)
      {
         $this->idasientocierre = $this->intval($e['idasientocierre']);
         $this->idasientopyg = $this->intval($e['idasientopyg']);
         $this->idasientoapertura = $this->intval($e['idasientoapertura']);
         $this->plancontable = $e['plancontable'];
         $this->longsubcuenta = $this->intval($e['longsubcuenta']);
         $this->estado = $e['estado'];
         $this->fechafin = Date('d-m-Y', strtotime($e['fechafin']));
         $this->fechainicio = Date('d-m-Y', strtotime($e['fechainicio']));
         $this->nombre = $e['nombre'];
         $this->codejercicio = $e['codejercicio'];
      }
      else
      {
         $this->idasientocierre = NULL;
         $this->idasientopyg = NULL;
         $this->idasientoapertura = NULL;
         $this->plancontable = '08';
         $this->longsubcuenta = 10;
         $this->estado = 'ABIERTO';
         $this->fechafin = Date('31-12-Y');
         $this->fechainicio = Date('01-01-Y');
         $this->nombre = '';
         $this->codejercicio = NULL;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codejercicio,nombre,fechainicio,fechafin,
         estado,longsubcuenta,plancontable,idasientoapertura,idasientopyg,idasientocierre)
         VALUES ('0001','".Date('Y')."',".$this->var2str(Date('01-01-Y')).",
         ".$this->var2str(Date('31-12-Y')).",'ABIERTO',10,'08',NULL,NULL,NULL);";
   }
   
   public function abierto()
   {
      return ($this->estado == 'ABIERTO');
   }
   
   public function year()
   {
      return Date('Y', strtotime($this->fechainicio));
   }
   
   public function get_new_codigo()
   {
      $cod = $this->db->select("SELECT MAX(".$this->db->sql_to_int('codejercicio').") as cod FROM ".$this->table_name.";");
      if($cod)
         return sprintf('%04s', (1 + intval($cod[0]['cod'])));
      else
         return '0001';
   }
   
   public function url()
   {
      if( is_null($this->codejercicio) )
         return 'index.php?page=contabilidad_ejercicios';
      else
         return 'index.php?page=contabilidad_ejercicio&cod='.$this->codejercicio;
   }
   
   public function is_default()
   {
      return ( $this->codejercicio == $this->default_items->codejercicio() );
   }
   
   /**
    * Devuelve la fecha más próxima a $fecha que esté dentro del intervalo de este ejercicio
    */
   public function get_best_fecha($fecha, $show_error=FALSE)
   {
      $fecha2 = strtotime( $fecha );
      
      if( $fecha2 >= strtotime( $this->fechainicio ) AND $fecha2 <= strtotime( $this->fechafin ) )
         return $fecha;
      else if( $fecha2 > strtotime( $this->fechainicio ) )
      {
         if($show_error)
            $this->new_error_msg('La fecha seleccionada está fuera del rango del ejercicio.
               Se ha seleccionado una mejor.');
         
         return $this->fechafin;
      }
      else
      {
         if($show_error)
            $this->new_error_msg('La fecha seleccionada está fuera del rango del ejercicio.
               Se ha seleccionado una mejor.');
         
         return $this->fechainicio;
      }
   }
   
   public function get($cod)
   {
      $ejercicio = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codejercicio = ".$this->var2str($cod).";");
      if($ejercicio)
         return new ejercicio($ejercicio[0]);
      else
         return FALSE;
   }
   
   /**
    * Devuelve el ejercicio para la fecha indicada.
    * Si no existe, lo crea.
    */
   public function get_by_fecha($fecha, $solo_abierto=TRUE, $crear=TRUE)
   {
      $ejercicio = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE fechainicio <= ".$this->var2str($fecha)." AND fechafin >= ".$this->var2str($fecha).";");
      if($ejercicio)
      {
         $eje = new ejercicio($ejercicio[0]);
         if( $eje->abierto() OR !$solo_abierto )
            return $eje;
         else
            return FALSE;
      }
      else if($crear)
      {
         $eje = new ejercicio();
         $eje->codejercicio = $this->get_new_codigo();
         $eje->nombre = Date('Y', strtotime($fecha));
         $eje->fechainicio = Date('1-1-Y', strtotime($fecha));
         $eje->fechafin = Date('31-12-Y', strtotime($fecha));
         if( $eje->save() )
            return $eje;
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codejercicio) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE codejercicio = ".$this->var2str($this->codejercicio).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codejercicio = trim($this->codejercicio);
      $this->nombre = $this->no_html($this->nombre);
      
      if( !preg_match("/^[A-Z0-9_]{1,4}$/i", $this->codejercicio) )
      {
         $this->new_error_msg("Código de ejercicio no válido.");
      }
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
      {
         $this->new_error_msg("Nombre de cliente no válido.");
      }
      else if( strtotime($this->fechainicio) > strtotime($this->fechafin) )
      {
         $this->new_error_msg("La fecha de inicio (".$this->fechainicio.") es posterior a la fecha fin (".$this->fechafin.").");
      }
      else
         $status = TRUE;
      
      $asiento = new asiento();
      if( !$asiento->get( $this->idasientoapertura ) )
      {
         $this->idasientoapertura = NULL;
      }
      
      if( !$asiento->get( $this->idasientocierre ) )
      {
         $this->idasientocierre = NULL;
      }
      
      if( !$asiento->get( $this->idasientopyg ) )
      {
         $this->idasientopyg = NULL;
      }
      
      return $status;
   }
   
   public function full_test()
   {
      $status = TRUE;
      
      /// comprobamos el balance de todas las subcuentas
      $debe = 0;
      $haber = 0;
      $subcuenta = new subcuenta();
      foreach($subcuenta->all_from_ejercicio($this->codejercicio) as $sc)
      {
         $debe += $sc->debe;
         $haber += $sc->haber;
         
         if( !$this->abierto() AND !$this->floatcmp($sc->debe, $sc->haber, FS_NF0) )
         {
            $this->new_error_msg('El ejercicio está cerrado pero la subcuenta <a href="'.
                    $sc->url().'">'.$sc->codsubcuenta.'</a> aún tiene saldo ('.$sc->saldo.').');
            $status = FALSE;
         }
      }
      
      if( !$this->floatcmp($debe, $haber, FS_NF0) )
      {
         $this->new_error_msg('El ejercicio está descuadrado. Debe: '.$debe.' | Haber: '.$haber);
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
               fechainicio = ".$this->var2str($this->fechainicio).", fechafin = ".$this->var2str($this->fechafin).",
               estado = ".$this->var2str($this->estado).", longsubcuenta = ".$this->var2str($this->longsubcuenta).",
               plancontable = ".$this->var2str($this->plancontable).", idasientoapertura = ".$this->var2str($this->idasientoapertura).",
               idasientopyg = ".$this->var2str($this->idasientopyg).", idasientocierre = ".$this->var2str($this->idasientocierre)."
               WHERE codejercicio = ".$this->var2str($this->codejercicio).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codejercicio,nombre,fechainicio,fechafin,estado,longsubcuenta,plancontable,
               idasientoapertura,idasientopyg,idasientocierre) VALUES (".$this->var2str($this->codejercicio).",".$this->var2str($this->nombre).",
               ".$this->var2str($this->fechainicio).",".$this->var2str($this->fechafin).",".$this->var2str($this->estado).",
               ".$this->var2str($this->longsubcuenta).",".$this->var2str($this->plancontable).",
               ".$this->var2str($this->idasientoapertura).",".$this->var2str($this->idasientopyg).",
               ".$this->var2str($this->idasientocierre).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codejercicio = ".$this->var2str($this->codejercicio).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_ejercicio_all');
      $this->cache->delete('m_ejercicio_all_abiertos');
   }
   
   public function all()
   {
      $listae = $this->cache->get_array('m_ejercicio_all');
      if( !$listae )
      {
         $ejercicios = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY fechainicio DESC;");
         if($ejercicios)
         {
            foreach($ejercicios as $e)
               $listae[] = new ejercicio($e);
         }
         $this->cache->set('m_ejercicio_all', $listae);
      }
      return $listae;
   }
   
   public function all_abiertos()
   {
      $listae = $this->cache->get_array('m_ejercicio_all_abiertos');
      if( !$listae )
      {
         $ejercicios = $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE estado = 'ABIERTO' ORDER BY codejercicio DESC;");
         if($ejercicios)
         {
            foreach($ejercicios as $e)
               $listae[] = new ejercicio($e);
         }
         $this->cache->set('m_ejercicio_all_abiertos', $listae);
      }
      return $listae;
   }
}
