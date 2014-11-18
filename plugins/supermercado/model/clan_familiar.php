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
require_model('albaran_cliente.php');
require_model('cliente.php');

class clan_familiar extends fs_model
{
   public $codclan;
   public $nombre;
   public $limite;
   public $restringido;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('clanes', 'plugins/supermercado/');
      if($c)
      {
         $this->codclan = $c['codclan'];
         $this->nombre = $c['nombre'];
         $this->limite = floatval($c['limite']);
         $this->restringido = ($c['restringido'] == 't');
      }
      else
      {
         $this->codclan = NULL;
         $this->nombre = NULL;
         $this->limite = 0;
         $this->restringido = FALSE;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      return 'index.php?page=ventas_clan&cod='.$this->codclan;
   }
   
   public function get_clientes()
   {
      $cliente2clan = new cliente2clan();
      return $cliente2clan->all4clan($this->codclan);
   }
   
   public function gastado()
   {
      $data = $this->db->select("SELECT SUM(totaleuros) as total FROM albaranescli WHERE codcliente IN
         (SELECT codcliente FROM cliente2clan WHERE codclan = ".$this->var2str($this->codclan).")
            AND fecha >= ".$this->var2str(Date('1-n-Y')).";");
      if($data)
         return floatval($data[0]['total']);
      else
         return 0;
   }
   
   public function pendiente()
   {
      return $this->limite - $this->gastado();
   }
   
   public function get($cod)
   {
      $c = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codclan = ".$this->var2str($cod).";");
      if($c)
         return new clan_familiar($c[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codclan) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE codclan = ".$this->var2str($this->codclan).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
               limite = ".$this->var2str($this->limite).", restringido = ".$this->var2str($this->restringido)."
               WHERE codclan = ".$this->var2str($this->codclan).";";
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (nombre,limite,restringido) VALUES
               (".$this->var2str($this->nombre).",".$this->var2str($this->limite).",".$this->var2str($this->restringido).");";
            if( $this->db->exec($sql) )
            {
               $this->codclan = $this->db->lastval();
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
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codclan = ".$this->var2str($this->codclan).";");
   }
   
   public function all()
   {
      $clanlist = array();
      $clanes = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
      if($clanes)
      {
         foreach($clanes as $c)
            $clanlist[] = new clan_familiar($c);
      }
      return $clanlist;
   }
   
   public function last_albaranes($offset = 0)
   {
      $albalist = array();
      $data = $this->db->select_limit("SELECT * FROM albaranescli WHERE codcliente IN
         (SELECT codcliente FROM cliente2clan WHERE codclan = ".$this->var2str($this->codclan).")
            ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $a)
            $albalist[] = new albaran_cliente($a);
      }
      return $albalist;
   }
}


class cliente2clan extends fs_model
{
   public $codcliente;
   public $codclan;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('cliente2clan', 'plugins/supermercado/');
      
      if($c)
      {
         $this->codcliente = $c['codcliente'];
         $this->codclan = $c['codclan'];
      }
      else
      {
         $this->codcliente = NULL;
         $this->codclan = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get_clan($codcliente)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codcliente = ".$this->var2str($codcliente).";");
      if($data)
      {
         $clan = new clan_familiar();
         return $clan->get($data[0]['codclan']);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codclan) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE codclan = ".$this->var2str($this->codclan).
                 " AND codcliente = ".$this->var2str($this->codcliente).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codclan = ".$this->var2str($this->codclan)."
               WHERE codcliente = ".$this->var2str($this->codcliente).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codclan,codcliente)
               VALUES (".$this->var2str($this->codclan).",".$this->var2str($this->codcliente).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codclan = ".$this->var2str($this->codclan).
              " AND codcliente = ".$this->var2str($this->codcliente).";");
   }
   
   public function all4clan($cod)
   {
      $clilist = array();
      $cliente = new cliente();
      
      $datos = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codclan = ".$this->var2str($cod).";");
      if($datos)
      {
         foreach($datos as $c)
            $clilist[] = $cliente->get($c['codcliente']);
      }
      
      return $clilist;
   }
}
