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
require_model('agente.php');

/**
 * Estructura para almacenar los datos de estado de una caja registradora (TPV).
 */
class caja extends fs_model
{
   public $id;
   public $fs_id;
   public $codagente;
   public $fecha_inicial;
   public $dinero_inicial;
   public $fecha_fin;
   public $dinero_fin;
   public $tickets;
   public $ip;
   
   public $agente;
   
   private static $agentes;
   
   public function __construct($c=FALSE)
   {
      parent::__construct('cajas');
      
      if( !isset(self::$agentes) )
         self::$agentes = array();
      
      if($c)
      {
         $this->id = $this->intval($c['id']);
         $this->fs_id = $this->intval($c['fs_id']);
         $this->fecha_inicial = Date('d-m-Y H:i:s', strtotime($c['f_inicio']));
         $this->dinero_inicial = floatval($c['d_inicio']);
         
         if( is_null($c['f_fin']) )
            $this->fecha_fin = NULL;
         else
            $this->fecha_fin = Date('d-m-Y H:i:s', strtotime($c['f_fin']));
         
         $this->dinero_fin = floatval($c['d_fin']);
         $this->codagente = $c['codagente'];
         $this->tickets = intval($c['tickets']);
         
         $this->ip = NULL;
         if( isset($c['ip']) )
            $this->ip = $c['ip'];
         
         foreach(self::$agentes as $ag)
         {
            if( $ag->codagente == $this->codagente )
            {
               $this->agente = $ag;
               break;
            }
         }
         
         if( !isset($this->agente) )
         {
            $ag = new agente();
            $this->agente = $ag->get($this->codagente);
            self::$agentes[] = $this->agente;
         }
      }
      else
      {
         $this->id = NULL;
         $this->fs_id = FS_ID;
         $this->codagente = NULL;
         $this->fecha_inicial = Date('d-m-Y H:i:s');
         $this->dinero_inicial = 0;
         $this->fecha_fin = NULL;
         $this->dinero_fin = 0;
         $this->tickets = 0;
         
         $this->ip = NULL;
         if( isset($_SERVER['REMOTE_ADDR']) )
            $this->ip = $_SERVER['REMOTE_ADDR'];
         
         $this->agente = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function show_fecha_fin()
   {
      if( is_null($this->fecha_fin) )
      {
         return '-';
      }
      else
         return $this->fecha_fin;
   }
   
   public function diferencia()
   {
      return ($this->dinero_fin - $this->dinero_inicial);
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function get($id)
   {
      if( isset($id) )
      {
         $caja = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
         if($caja)
         {
            return new caja($caja[0]);
         }
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function get_last_from_this_server()
   {
      $caja = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fs_id = ".$this->var2str(FS_ID)." AND f_fin IS NULL;");
      if($caja)
      {
         return new caja($caja[0]);
      }
      else
         return FALSE;
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET fs_id = ".$this->var2str($this->fs_id).",
            codagente = ".$this->var2str($this->codagente).", ip = ".$this->var2str($this->ip).",
            f_inicio = ".$this->var2str($this->fecha_inicial).", d_inicio = ".$this->var2str($this->dinero_inicial).",
            f_fin = ".$this->var2str($this->fecha_fin).", d_fin = ".$this->var2str($this->dinero_fin).",
            tickets = ".$this->var2str($this->tickets)." WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (fs_id,codagente,f_inicio,d_inicio,f_fin,d_fin,tickets,ip) VALUES
            (".$this->var2str($this->fs_id).",".$this->var2str($this->codagente).",".
            $this->var2str($this->fecha_inicial).",".$this->var2str($this->dinero_inicial).",
            ".$this->var2str($this->fecha_fin).",".$this->var2str($this->dinero_fin).",
            ".$this->var2str($this->tickets).",".$this->var2str($this->ip).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $cajalist = array();
      $cajas = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY id DESC", $limit, $offset);
      if($cajas)
      {
         foreach($cajas as $c)
            $cajalist[] = new caja($c);
      }
      return $cajalist;
   }
   
   public function all_by_agente($codagente, $offset=0, $limit=FS_ITEM_LIMIT)
   {
      $cajalist = array();
      $cajas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codagente = ".
              $this->var2str($codagente)." ORDER BY id DESC", $limit, $offset);
      if($cajas)
      {
         foreach($cajas as $c)
            $cajalist[] = new caja($c);
      }
      return $cajalist;
   }
}
