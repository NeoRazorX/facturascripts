<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_once 'model/ejercicio.php';
require_once 'model/factura_cliente.php';
require_once 'model/factura_proveedor.php';
require_once 'model/partida.php';
require_once 'model/secuencia.php';

class asiento extends fs_model
{
   public $idasiento;
   public $numero;
   public $idconcepto;
   public $concepto;
   public $fecha;
   public $codejercicio;
   public $codplanasiento;
   public $editable;
   public $documento;
   public $tipodocumento;
   public $importe;
   
   public function __construct($a = FALSE)
   {
      parent::__construct('co_asientos');
      if($a)
      {
         $this->idasiento = $this->intval($a['idasiento']);
         $this->numero = $this->intval($a['numero']);
         $this->idconcepto = $a['idconcepto'];
         $this->concepto = $a['concepto'];
         $this->fecha = Date('d-m-Y', strtotime($a['fecha']));
         $this->codejercicio = $a['codejercicio'];
         $this->codplanasiento = $a['codplanasiento'];
         $this->editable = ($a['editable'] == 't');
         $this->documento = $a['documento'];
         $this->tipodocumento = $a['tipodocumento'];
         $this->importe = floatval($a['importe']);
      }
      else
      {
         $this->idasiento = NULL;
         $this->numero = NULL;
         $this->idconcepto = NULL;
         $this->concepto = NULL;
         $this->fecha = Date('d-m-Y');
         $this->codejercicio = NULL;
         $this->codplanasiento = NULL;
         $this->editable = TRUE;
         $this->documento = NULL;
         $this->tipodocumento = NULL;
         $this->importe = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function show_importe()
   {
      return number_format($this->importe, 2, '.', ' ');
   }
   
   public function url()
   {
      if( is_null($this->idasiento) )
         return 'index.php?page=contabilidad_asientos';
      else
         return 'index.php?page=contabilidad_asiento&id='.$this->idasiento;
   }
   
   public function get_factura()
   {
      if($this->tipodocumento == 'Factura de cliente')
      {
         $fac = new factura_cliente();
         return $fac->get_by_codigo($this->documento);
      }
      else if($this->tipodocumento == 'Factura de proveedor')
      {
         $fac = new factura_proveedor();
         return $fac->get_by_codigo($this->documento);
      }
      else
         return FALSE;
   }
   
   public function factura_url()
   {
      $fac = $this->get_factura();
      if($fac)
         return $fac->url();
      else
         return '#';
   }
   
   public function ejercicio_url()
   {
      $ejercicio = new ejercicio();
      $eje0 = $ejercicio->get($this->codejercicio);
      if($eje0)
         return $eje0->url();
      else
         return '#';
   }
   
   public function get($id)
   {
      if( isset($id) )
      {
         $asiento = $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE idasiento = ".$this->var2str($id).";");
         if($asiento)
            return new asiento($asiento[0]);
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function get_partidas()
   {
      $partida = new partida();
      return $partida->all_from_asiento($this->idasiento);
   }
   
   public function exists()
   {
      if( is_null($this->idasiento) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE idasiento = ".$this->var2str($this->idasiento).";");
   }
   
   public function new_idasiento()
   {
      $newid = $this->db->nextval($this->table_name.'_idasiento_seq');
      if($newid)
         $this->idasiento = intval($newid);
   }
   
   public function new_numero()
   {
      $secc = new secuencia_contabilidad();
      $secc0 = $secc->get_by_params2($this->codejercicio, 'nasiento');
      if($secc0)
      {
         $this->numero = $secc0->valorout;
         $secc0->valorout++;
         $secc0->save();
      }
      
      if( !$secc0 OR $this->numero <= 1 )
      {
         $num = $this->db->select("SELECT MAX(numero::integer) as num FROM ".$this->table_name.
                 " WHERE codejercicio = ".$this->var2str($this->codejercicio).";");
         if($num)
            $this->numero = 1 + intval($num[0]['num']);
         else
            $this->numero = 1;
         
         if($secc0)
         {
            $secc0->valorout = 1 + $this->numero;
            $secc0->save();
         }
      }
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->concepto = $this->no_html($this->concepto);
      $this->documento = $this->no_html($this->documento);
      
      if( strlen($this->concepto) > 255 )
         $this->new_error_msg("Concepto del asiento demasiado largo.");
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function full_test()
   {
      $status = TRUE;
      
      $debe = 0;
      $haber = 0;
      $partidas = $this->get_partidas();
      if($partidas)
      {
         foreach($partidas as $p)
         {
            $debe += $p->debe;
            $haber += $p->haber;
         }
      }
      else
      {
         $this->new_error_msg('Asiento vacío.');
         $status = FALSE;
      }
      
      if( !$this->floatcmp($debe, $haber) )
      {
         $total = $debe - $haber;
         $this->new_error_msg("Asiento descuadrado. Descuadre: ".$total);
         $status = FALSE;
      }
      
      /// comprobamos la factura asociada
      $fac = $this->get_factura();
      if($fac)
      {
         if($fac->idasiento != $this->idasiento)
         {
            $this->new_error_msg("Este asiento apunta a una <a href='".$fac->url()."'>factura incorrecta</a>.");
            $status = FALSE;
         }
      }
      
      return $status;
   }
   
   public function fix()
   {
      $debe = 0;
      $haber = 0;
      foreach($this->get_partidas() as $p)
      {
         $debe += $p->debe;
         $haber += $p->haber;
      }
      $total = $debe - $haber;
      
      /// corregimos descuadres de menos de 0.01
      if( !$this->floatcmp($debe, $haber) AND abs($total) < .01 )
      {
         $debe = 0;
         $haber = 0;
         $partidas = $this->get_partidas();
         foreach($partidas as $p)
         {
            $p->debe = round($p->debe, 2);
            $debe += $p->debe;
            $p->haber = round($p->haber, 2);
            $haber += $p->haber;
         }
         /// si con el redondeo se soluciona el problema, pues genial!
         if( $this->floatcmp($debe, $haber) )
         {
            $this->importe = max( array($debe, $haber) );
            foreach($partidas as $p)
               $p->save();
         }
         else
         {
            /// si no ha funcionado, intentamos arreglarlo
            $total = 0;
            $partidas = $this->get_partidas();
            foreach($partidas as $p)
               $total += ($p->debe - $p->haber);
            
            if($partidas[0]->debe != 0)
               $partidas[0]->debe = ($partidas[0]->debe - $total);
            else if($partidas[0]->haber != 0)
               $partidas[0]->haber += $total;
            
            $debe = 0;
            $haber = 0;
            foreach($partidas as $p)
            {
               $debe += $p->debe;
               $haber += $p->haber;
            }
            
            /// si hemos resuelto el problema grabamos
            if( $this->floatcmp($debe, $haber) )
            {
               $this->importe = max( array($debe, $haber) );
               foreach($partidas as $p)
                  $p->save();
            }
         }
      }
      
      $status = TRUE;
      
      /// comprobamos la factura asociada
      $fac = $this->get_factura();
      if($fac)
      {
         if( is_null($fac->idasiento) )
         {
            $fac->idasiento = $this->idasiento;
            $status = $fac->save();
         }
      }
      
      if($status)
         return $this->full_test();
      else
         return FALSE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET numero = ".$this->var2str($this->numero).",
               idconcepto = ".$this->var2str($this->idconcepto).",
               concepto = ".$this->var2str($this->concepto).", fecha = ".$this->var2str($this->fecha).",
               codejercicio = ".$this->var2str($this->codejercicio).",
               codplanasiento = ".$this->var2str($this->codplanasiento).",
               editable = ".$this->var2str($this->editable).",
               documento = ".$this->var2str($this->documento).",
               tipodocumento = ".$this->var2str($this->tipodocumento).",
               importe = ".$this->var2str($this->importe)."
               WHERE idasiento = ".$this->var2str($this->idasiento).";";
         }
         else
         {
            $this->new_idasiento();
            $this->new_numero();
            $sql = "INSERT INTO ".$this->table_name." (idasiento,numero,idconcepto,concepto,
               fecha,codejercicio,codplanasiento,editable,documento,tipodocumento,importe)
               VALUES (".$this->var2str($this->idasiento).",".$this->var2str($this->numero).",
               ".$this->var2str($this->idconcepto).",".$this->var2str($this->concepto).",
               ".$this->var2str($this->fecha).",".$this->var2str($this->codejercicio).",
               ".$this->var2str($this->codplanasiento).",".$this->var2str($this->editable).",
               ".$this->var2str($this->documento).",".$this->var2str($this->tipodocumento).",
               ".$this->var2str($this->importe).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      /// desvinculamos la factura
      $fac = $this->get_factura();
      if($fac)
      {
         if($fac->idasiento == $this->idasiento)
         {
            $fac->idasiento = NULL;
            $fac->save();
         }
      }
      /// eliminamos las partidas una a una para forzar la actualización de las subcuentas asociadas
      foreach($this->get_partidas() as $p)
         $p->delete();
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE idasiento = ".$this->var2str($this->idasiento).";");
   }
   
   public function search($query, $offset=0)
   {
      $alist = array();
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
         $consulta .= "numero::TEXT ~~ '%".$query."%' OR concepto ~~ '%".$query."%'
            OR importe BETWEEN ".($query-.01)." AND ".($query+.01);
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
         $consulta .= "fecha = '".$query."' OR concepto ~~ '%".$query."%'";
      else
         $consulta .= "lower(concepto) ~~ '%".$buscar = str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC";
      
      $asientos = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($asientos)
      {
         foreach($asientos as $a)
            $alist[] = new asiento($a);
      }
      return $alist;
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $alist = array();
      $asientos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " ORDER BY fecha DESC", $limit, $offset);
      if($asientos)
      {
         foreach($asientos as $a)
            $alist[] = new asiento($a);
      }
      return $alist;
   }
   
   public function descuadrados()
   {
      /// iniciamos partidas para asegurarnos que existe la tabla
      new partida();
      
      $alist = array();
      $asientos = $this->db->select("SELECT p.idasiento,SUM(p.debe) as sdebe,SUM(p.haber) as shaber
         FROM co_partidas p, co_asientos a
          WHERE p.idasiento = a.idasiento
           GROUP BY p.idasiento
            HAVING ABS(SUM(p.haber) - SUM(p.debe)) > 0.01
             ORDER BY p.idasiento DESC;");
      if($asientos)
      {
         foreach($asientos as $a)
            $alist[] = $this->get($a['idasiento']);
      }
      return $alist;
   }
   
   /// renumera todos los asientos. Devuelve FALSE en caso de error
   public function renumerar()
   {
      $ejercicio = new ejercicio();
      foreach($ejercicio->all_abiertos() as $eje)
      {
         $posicion = 0;
         $numero = 1;
         $sql = '';
         $continuar = TRUE;
         $consulta = "SELECT idasiento,numero,fecha FROM co_asientos
            WHERE codejercicio = '".$eje->codejercicio."'
            ORDER BY codejercicio ASC, fecha ASC, idasiento ASC";
         
         $asientos = $this->db->select_limit($consulta, 1000, $posicion);
         while($asientos AND $continuar)
         {
            foreach($asientos as $col)
            {
               if($col['numero'] != $numero)
               {
                  $sql .= "UPDATE co_asientos SET numero = '".$numero.
                       "' WHERE idasiento = '".$col['idasiento']."'; ";
               }
               
               $numero++;
            }
            $posicion += 1000;
            
            if($sql != '')
            {
               if( !$this->db->exec($sql) )
               {
                  $this->new_error_msg("Se ha producido un error mientras se
                     renumeraban los asientos del ejercicio ".$eje->codejercicio);
                  $continuar = FALSE;
               }
               $sql = '';
            }
            
            $asientos = $this->db->select_limit($consulta, 1000, $posicion);
         }
      }
      
      return $continuar;
   }
   
   public function cron_job()
   {
      $this->renumerar();
   }
}

?>