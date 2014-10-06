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
require_model('ejercicio.php');

/**
 * Define que cuentas hay que usar para generar los distintos informes contables.
 */
class balance extends fs_model
{
   public $descripcion4ba;
   public $descripcion4;
   public $nivel4;
   public $descripcion3;
   public $orden3;
   public $nivel3;
   public $descripcion2;
   public $nivel2;
   public $descripcion1;
   public $nivel1;
   public $naturaleza;
   public $codbalance; /// pkey
   
   public function __construct($b=FALSE)
   {
      parent::__construct('co_codbalances08');
      if($b)
      {
         $this->codbalance = $b['codbalance'];
         $this->naturaleza = $b['naturaleza'];
         $this->nivel1 = $b['nivel1'];
         $this->descripcion1 = $b['descripcion1'];
         $this->nivel2 = $this->intval($b['nivel2']);
         $this->descripcion2 = $b['descripcion2'];
         $this->nivel3 = $b['nivel3'];
         $this->descripcion3 = $b['descripcion3'];
         $this->orden3 = $b['orden3'];
         $this->nivel4 = $b['nivel4'];
         $this->descripcion4 = $b['descripcion4'];
         $this->descripcion4ba = $b['descripcion4ba'];
      }
      else
      {
         $this->codbalance = NULL;
         $this->naturaleza = NULL;
         $this->nivel1 = NULL;
         $this->descripcion1 = NULL;
         $this->nivel2 = NULL;
         $this->descripcion2 = NULL;
         $this->nivel3 = NULL;
         $this->descripcion3 = NULL;
         $this->orden3 = NULL;
         $this->nivel4 = NULL;
         $this->descripcion4 = NULL;
         $this->descripcion4ba = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->codbalance) )
         return 'index.php?page=contabilidad_balances';
      else
         return 'index.php?page=contabilidad_balance&cod='.$this->codbalance;
   }
   
   public function get($cod)
   {
      $balance = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codbalance = ".$this->var2str($cod).";");
      if($balance)
         return new balance($balance[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codbalance) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE codbalance = ".$this->var2str($this->codbalance).";");
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
            $sql = "UPDATE ".$this->table_name." SET naturaleza = ".$this->var2str($this->naturaleza).",
               nivel1 = ".$this->var2str($this->nivel1).", descripcion1 = ".$this->var2str($this->descripcion1).",
               nivel2 = ".$this->var2str($this->nivel2).", descripcion2 = ".$this->var2str($this->descripcion2).",
               nivel3 = ".$this->var2str($this->nivel3).", descripcion3 = ".$this->var2str($this->descripcion3).",
               orden3 = ".$this->var2str($this->orden3).", nivel4 = ".$this->var2str($this->nivel4).",
               descripcion4 = ".$this->var2str($this->descripcion4).",
               descripcion4ba = ".$this->var2str($this->descripcion4ba).",
               WHERE codbalance = ".$this->var2str($this->codbalance).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codbalance,naturaleza,nivel1,descripcion1,
               nivel2,descripcion2,nivel3,descripcion3,orden3,nivel4,descripcion4,descripcion4ba)
               VALUES (".$this->var2str($this->codbalance).",".$this->var2str($this->naturaleza).",
               ".$this->var2str($this->nivel1).",".$this->var2str($this->descripcion1).",
               ".$this->var2str($this->nivel2).",".$this->var2str($this->descripcion2).",
               ".$this->var2str($this->nivel3).",".$this->var2str($this->descripcion3).",
               ".$this->var2str($this->orden3).",".$this->var2str($this->nivel4).",
               ".$this->var2str($this->descripcion4).",".$this->var2str($this->descripcion4ba).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->select("DELETE FROM ".$this->table_name." WHERE codbalance = ".$this->var2str($this->codbalance).";");
   }
   
   public function all()
   {
      $balist = array();
      $balances = $this->db->select("SELECT * FROM ".$this->table_name.
         " ORDER BY codbalance ASC;");
      if($balances)
      {
         foreach($balances as $b)
            $balist[] = new balance($b);
      }
      return $balist;
   }
}


/**
 * Detalle de un balance.
 */
class balance_cuenta extends fs_model
{
   public $id; /// pkey
   public $codbalance;
   public $codcuenta;
   public $desccuenta;
   
   public function __construct($b = FALSE)
   {
      parent::__construct('co_cuentascb');
      if($b)
      {
         $this->id = $this->intval($b['id']);
         $this->codbalance = $b['codbalance'];
         $this->codcuenta = $b['codcuenta'];
         $this->desccuenta = $b['desccuenta'];
      }
      else
      {
         $this->id = NULL;
         $this->codbalance = NULL;
         $this->codcuenta = NULL;
         $this->desccuenta = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $bc = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE id = ".$this->var2str($id).";");
      if($bc)
         return new balance_cuenta($bc[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codbalance = ".$this->var2str($this->codbalance).",
            codcuenta = ".$this->var2str($this->codcuenta).",
            desccuenta = ".$this->var2str($this->desccuenta)."
            WHERE id = ".$this->var2str($this->id).";";
         return $this->db->exec($sql);
      }
      else
      {
         $newid = $this->db->nextval($this->table_name.'_id_seq');
         if($newid)
         {
            $this->id = intval($newid);
            $sql = "INSERT INTO ".$this->table_name." (id,codbalance,codcuenta,desccuenta)
               VALUES (".$this->var2str($this->id).",".$this->var2str($this->codbalance).",
               ".$this->var2str($this->codcuenta).",".$this->var2str($this->desccuenta).");";
            return $this->db->exec($sql);
         }
         else
            return FALSE;
      }
      
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all()
   {
      $balist = array();
      $balances = $this->db->select("SELECT * FROM ".$this->table_name.";");
      if($balances)
      {
         foreach($balances as $b)
            $balist[] = new balance_cuenta($b);
      }
      return $balist;
   }
   
   public function all_from_codbalance($cod)
   {
      $balist = array();
      $balances = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codbalance = ".$this->var2str($cod)." ORDER BY codcuenta ASC;");
      if($balances)
      {
         foreach($balances as $b)
            $balist[] = new balance_cuenta($b);
      }
      return $balist;
   }
}


/**
 * Detalle abreviado de un balance.
 */
class balance_cuenta_a extends fs_model
{
   public $id; /// pkey
   public $codbalance;
   public $codcuenta;
   public $desccuenta;
   
   public function __construct($b = FALSE)
   {
      parent::__construct('co_cuentascbba');
      if($b)
      {
         $this->id = $this->intval($b['id']);
         $this->codbalance = $b['codbalance'];
         $this->codcuenta = $b['codcuenta'];
         $this->desccuenta = $b['desccuenta'];
      }
      else
      {
         $this->id = NULL;
         $this->codbalance = NULL;
         $this->codcuenta = NULL;
         $this->desccuenta = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   /*
    * Devuelve el saldo del balance de un ejercicio
    */
   public function saldo(&$ejercicio)
   {
      $extra = '';
      if( isset($ejercicio->idasientopyg) )
      {
         if( isset($ejercicio->idasientocierre) )
            $extra = " AND idasiento NOT IN (".$this->var2str($ejercicio->idasientocierre).",".$this->var2str($ejercicio->idasientopyg).");";
         else
            $extra = " AND idasiento != ".$this->var2str($ejercicio->idasientopyg).";";
      }
      else if( isset($ejercicio->idasientocierre) )
         $extra = " AND idasiento != ".$this->var2str($ejercicio->idasientocierre).";";
      
      if($this->codcuenta == '129')
      {
         $data = $this->db->select("SELECT SUM(debe) as debe, SUM(haber) as haber FROM co_partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
               WHERE (codcuenta LIKE '6%' OR codcuenta LIKE '7%') AND codejercicio = ".$this->var2str($ejercicio->codejercicio).")".$extra.";");
      }
      else
      {
         $data = $this->db->select("SELECT SUM(debe) as debe, SUM(haber) as haber FROM co_partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
               WHERE codcuenta LIKE '".$this->codcuenta."%' AND codejercicio = ".$this->var2str($ejercicio->codejercicio).")".$extra.";");
      }
      
      if($data)
         return floatval($data[0]['haber']) - floatval($data[0]['debe']);
      else
         return 0;
   }
   
   public function get($id)
   {
      $bca = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($bca)
         return new balance_cuenta_a($bca[0]);
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
         $sql = "UPDATE ".$this->table_name." SET codbalance = ".$this->var2str($this->codbalance).",
            codcuenta = ".$this->var2str($this->codcuenta).", desccuenta = ".$this->var2str($this->desccuenta)."
            WHERE id = ".$this->var2str($this->id).";";
         return $this->db->exec($sql);
      }
      else
      {
         $newid = $this->db->nextval($this->table_name.'_id_seq');
         if($newid)
         {
            $this->id = intval($newid);
            $sql = "INSERT INTO ".$this->table_name." (id,codbalance,codcuenta,desccuenta)
               VALUES (".$this->var2str($this->id).",".$this->var2str($this->codbalance).",
               ".$this->var2str($this->codcuenta).",".$this->var2str($this->desccuenta).");";
            return $this->db->exec($sql);
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all()
   {
      $balist = array();
      $balances = $this->db->select("SELECT * FROM ".$this->table_name.";");
      if($balances)
      {
         foreach($balances as $b)
            $balist[] = new balance_cuenta_a($b);
      }
      return $balist;
   }
   
   public function all_from_codbalance($cod)
   {
      $balist = array();
      $balances = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codbalance = ".$this->var2str($cod)." ORDER BY codcuenta ASC;");
      if($balances)
      {
         foreach($balances as $b)
            $balist[] = new balance_cuenta_a($b);
      }
      return $balist;
   }
   
   public function search_by_codbalance($cod)
   {
      $balist = array();
      $balances = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codbalance LIKE '".$cod."%' ORDER BY codcuenta ASC;");
      if($balances)
      {
         foreach($balances as $b)
            $balist[] = new balance_cuenta_a($b);
      }
      return $balist;
   }
}
