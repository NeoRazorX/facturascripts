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
require_once 'model/subcuenta.php';

class subcuenta_cliente extends fs_model
{
   public $codcliente;
   public $codsubcuenta;
   public $codejercicio;
   public $idsubcuenta;
   public $id;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('co_subcuentascli');
      if($s)
      {
         $this->codcliente = $s['codcliente'];
         $this->codsubcuenta = $s['codsubcuenta'];
         $this->codejercicio = $s['codejercicio'];
         $this->idsubcuenta = $this->intval($s['idsubcuenta']);
         $this->id = $this->intval($s['id']);
      }
      else
      {
         $this->codcliente = NULL;
         $this->codsubcuenta = NULL;
         $this->codejercicio = NULL;
         $this->idsubcuenta = NULL;
         $this->id = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get_subcuenta()
   {
      $subc = new subcuenta();
      return $subc->get($this->idsubcuenta);
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
         $sql = "";
      }
      else
      {
         $sql = "";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_cliente($cod)
   {
      $sublist = array();
      $subcs = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($cod)."
         ORDER BY codejercicio DESC;");
      if($subcs)
      {
         foreach($subcs as $s)
            $sublist[] = new subcuenta_cliente($s);
      }
      return $sublist;
   }
}

class direccion_cliente extends fs_model
{
   public $id;
   public $codcliente;
   public $codpais;
   public $apartado;
   public $provincia;
   public $ciudad;
   public $codpostal;
   public $direccion;
   public $domenvio;
   public $domfacturacion;
   public $descripcion;
   
   public function __construct($d=FALSE)
   {
      parent::__construct('dirclientes');
      if($d)
      {
         $this->id = $this->intval($d['id']);
         $this->codcliente = $d['codcliente'];
         $this->codpais = $d['codpais'];
         $this->apartado = $d['apartado'];
         $this->provincia = $d['provincia'];
         $this->ciudad = $d['ciudad'];
         $this->codpostal = $d['codpostal'];
         $this->direccion = $d['direccion'];
         $this->domenvio = ($d['domenvio'] == 't');
         $this->domfacturacion = ($d['domfacturacion'] == 't');
         $this->descripcion = $d['descripcion'];
      }
      else
      {
         $this->id = NULL;
         $this->codcliente = NULL;
         $this->codpais = NULL;
         $this->apartado = NULL;
         $this->provincia = NULL;
         $this->ciudad = NULL;
         $this->codpostal = NULL;
         $this->direccion = NULL;
         $this->domenvio = TRUE;
         $this->domfacturacion = TRUE;
         $this->descripcion = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $dir = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($dir)
         return new direccion_cliente($dir[0]);
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
      $this->apartado = $this->no_html( trim($this->apartado) );
      $this->ciudad = $this->no_html( trim($this->ciudad) );
      $this->codpostal = $this->no_html( trim($this->codpostal) );
      $this->descripcion = $this->no_html( trim($this->descripcion) );
      $this->direccion = $this->no_html( trim($this->direccion) );
      $this->provincia = $this->no_html( trim($this->provincia) );
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codcliente = ".$this->var2str($this->codcliente).",
               codpais = ".$this->var2str($this->codpais).", apartado = ".$this->var2str($this->apartado).",
               provincia = ".$this->var2str($this->provincia).", ciudad = ".$this->var2str($this->ciudad).",
               codpostal = ".$this->var2str($this->codpostal).", direccion = ".$this->var2str($this->direccion).",
               domenvio = ".$this->var2str($this->domenvio).", domfacturacion = ".$this->var2str($this->domfacturacion).",
               descripcion = ".$this->var2str($this->descripcion)." WHERE id = ".$this->var2str($this->id).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codcliente,codpais,apartado,provincia,ciudad,codpostal,direccion,
               domenvio,domfacturacion,descripcion) VALUES (".$this->var2str($this->codcliente).",".$this->var2str($this->codpais).",
               ".$this->var2str($this->apartado).",".$this->var2str($this->provincia).",".$this->var2str($this->ciudad).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->direccion).",".$this->var2str($this->domenvio).",
               ".$this->var2str($this->domfacturacion).",".$this->var2str($this->descripcion).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_cliente($cod)
   {
      $dirlist = array();
      $dirs = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($cod).";");
      if($dirs)
      {
         foreach($dirs as $d)
            $dirlist[] = new direccion_cliente($d);
      }
      return $dirlist;
   }
}

class cliente extends fs_model
{
   public $codcliente;
   public $nombre;
   public $nombrecomercial;
   public $cifnif;
   public $telefono1;
   public $telefono2;
   public $fax;
   public $email;
   public $web;
   public $codserie;
   public $coddivisa;
   public $codpago;
   public $debaja;
   public $fechabaja;
   public $observaciones;
   
   private static $default_cliente;

   public function __construct($c=FALSE)
   {
      parent::__construct('clientes');
      if($c)
      {
         $this->codcliente = $c['codcliente'];
         $this->nombre = $c['nombre'];
         $this->nombrecomercial = $c['nombrecomercial'];
         $this->cifnif = $c['cifnif'];
         $this->telefono1 = $c['telefono1'];
         $this->telefono2 = $c['telefono2'];
         $this->fax = $c['fax'];
         $this->email = $c['email'];
         $this->web = $c['web'];
         $this->codserie = $c['codserie'];
         $this->coddivisa = $c['coddivisa'];
         $this->codpago = $c['codpago'];
         $this->debaja = ($c['debaja'] == 't');
         $this->fechabaja = $c['fechabaja'];
         $this->observaciones = $c['observaciones'];
      }
      else
      {
         $this->codcliente = NULL;
         $this->nombre = '';
         $this->nombrecomercial = '';
         $this->cifnif = '';
         $this->telefono1 = '';
         $this->telefono2 = '';
         $this->fax = '';
         $this->email = '';
         $this->web = '';
         $this->codserie = NULL;
         $this->coddivisa = NULL;
         $this->codpago = NULL;
         $this->debaja = FALSE;
         $this->fechabaja = NULL;
         $this->observaciones = NULL;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return '';
   }
   
   public function observaciones_resume()
   {
      if($this->observaciones == '')
         return '-';
      else if( strlen($this->observaciones) < 60 )
         return $this->observaciones;
      else
         return substr($this->observaciones, 0, 50).'...';
   }
   
   public function url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=general_clientes";
      else
         return "index.php?page=general_cliente&cod=".$this->codcliente;
   }

   public function is_default()
   {
      if( isset(self::$default_cliente) )
         return (self::$default_cliente == $this->codcliente);
      else if( !isset($_COOKIE['default_cliente']) )
         return FALSE;
      else
         return ($_COOKIE['default_cliente'] == $this->codcliente);
   }
   
   public function set_default()
   {
      setcookie('default_cliente', $this->codcliente, time()+FS_COOKIES_EXPIRE);
      self::$default_cliente = $this->codcliente;
   }
   
   public function get($cod)
   {
      $cli = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($cod).";");
      if($cli)
         return new cliente($cli[0]);
      else
         return FALSE;
   }
   
   public function get_albaranes($offset=0)
   {
      $alb = new albaran_cliente();
      return $alb->all_from_cliente($this->codcliente, $offset);
   }
   
   public function get_direcciones()
   {
      $dir = new direccion_cliente();
      return $dir->all_from_cliente($this->codcliente);
   }
   
   public function get_subcuentas()
   {
      $subclist = array();
      $subc = new subcuenta_cliente();
      foreach($subc->all_from_cliente($this->codcliente) as $s)
         $subclist[] = $s->get_subcuenta();
      return $subclist;
   }
   
   public function get_subcuenta($ejercicio)
   {
      $retorno = FALSE;
      $subcs = $this->get_subcuentas();
      foreach($subcs as $s)
      {
         if($s->codejercicio == $ejercicio)
            $retorno = $s;
      }
      return $retorno;
   }

   public function exists()
   {
      if( is_null($this->codcliente) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($this->codcliente).";");
   }
   
   public function get_new_codigo()
   {
      $cod = $this->db->select("SELECT MAX(codcliente::integer) as cod FROM ".$this->table_name.";");
      if($cod)
         return sprintf('%06s', (1 + intval($cod[0]['cod'])));
      else
         return '000001';
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codcliente = trim($this->codcliente);
      $this->nombre = $this->no_html( trim($this->nombre) );
      $this->nombrecomercial = $this->no_html( trim($this->nombrecomercial) );
      $this->cifnif = $this->no_html( trim($this->cifnif) );
      $this->observaciones = $this->no_html( trim($this->observaciones) );
      
      if( !preg_match("/^[A-Z0-9]{1,6}$/i", $this->codcliente) )
         $this->new_error_msg("Código de cliente no válido.");
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
         $this->new_error_msg("Nombre de cliente no válido.");
      else if( strlen($this->nombrecomercial) < 1 OR strlen($this->nombrecomercial) > 100 )
         $this->new_error_msg("Nombre comercial de cliente no válido.");
      else
         $status = TRUE;
      
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
               nombrecomercial = ".$this->var2str($this->nombrecomercial).", cifnif = ".$this->var2str($this->cifnif).",
               telefono1 = ".$this->var2str($this->telefono1).", telefono2 = ".$this->var2str($this->telefono2).",
               fax = ".$this->var2str($this->fax).", email = ".$this->var2str($this->email).",
               web = ".$this->var2str($this->web).", codserie = ".$this->var2str($this->codserie).",
               coddivisa = ".$this->var2str($this->coddivisa).", codpago = ".$this->var2str($this->codpago).",
               debaja = ".$this->var2str($this->debaja).", fechabaja = ".$this->var2str($this->fechabaja).",
               observaciones = ".$this->var2str($this->observaciones)."
               WHERE codcliente = ".$this->var2str($this->codcliente).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codcliente,nombre,nombrecomercial,cifnif,telefono1,
               telefono2,fax,email,web,codserie,coddivisa,codpago,debaja,fechabaja,observaciones) VALUES
               (".$this->var2str($this->codcliente).",".$this->var2str($this->nombre).",
               ".$this->var2str($this->nombrecomercial).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->telefono1).",".$this->var2str($this->telefono2).",
               ".$this->var2str($this->fax).",".$this->var2str($this->email).",
               ".$this->var2str($this->web).",".$this->var2str($this->codserie).",
               ".$this->var2str($this->coddivisa).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->debaja).",".$this->var2str($this->fechabaja).",
               ".$this->var2str($this->observaciones).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($this->codcliente).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_cliente_all');
   }
   
   public function all($offset=0)
   {
      $clientlist = array();
      $clientes = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC", FS_ITEM_LIMIT, $offset);
      if($clientes)
      {
         foreach($clientes as $c)
            $clientlist[] = new cliente($c);
      }
      return $clientlist;
   }
   
   public function all_full()
   {
      $clientlist = $this->cache->get_array('m_cliente_all');
      if( !$clientlist )
      {
         $clientes = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
         if($clientes)
         {
            foreach($clientes as $c)
               $clientlist[] = new cliente($c);
         }
         $this->cache->set('m_cliente_all', $clientlist);
      }
      return $clientlist;
   }
   
   public function search($query, $offset=0)
   {
      $clilist = array();
      $query = $this->escape_string( strtolower( trim($query) ) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
         $consulta .= "codcliente ~~ '%".$query."%' OR cifnif ~~ '%".$query."%' OR observaciones ~~ '%".$query."%'";
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $consulta .= "lower(nombre) ~~ '%".$buscar."%' OR lower(cifnif) ~~ '%".$buscar."%'
            OR lower(observaciones) ~~ '%".$buscar."%'";
      }
      $consulta .= " ORDER BY nombre ASC";
      
      $clientes = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($clientes)
      {
         foreach($clientes as $c)
            $clilist[] = new cliente($c);
      }
      return $clilist;
   }
}

?>
