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
require_model('cuenta.php');
require_model('direccion_cliente.php');
require_model('factura_cliente.php');
require_model('subcuenta.php');
require_model('subcuenta_cliente.php');

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
   public $codagente;
   public $codgrupo;
   public $debaja;
   public $fechabaja;
   public $observaciones;
   public $tipoidfiscal;
   public $regimeniva;
   public $recargo;
   public $dtopor;
   public $zona;
   public $ruta;
   public $tipo_portes;
   public $agencia_transporte;
   public $dias_pago;
   public $i343;

   public function __construct($c=FALSE)
   {
      parent::__construct('clientes', 'plugins/cliente_plus/');
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
         $this->codagente = $c['codagente'];
         $this->codgrupo = $c['codgrupo'];
         $this->debaja = $this->str2bool($c['debaja']);
         $this->fechabaja = $c['fechabaja'];
         $this->observaciones = $this->no_html($c['observaciones']);
         $this->tipoidfiscal = $c['tipoidfiscal'];
         $this->regimeniva = $c['regimeniva'];
         $this->recargo = $this->str2bool($c['recargo']);
         $this->dtopor = $c['dtopor'];
         $this->zona = $c['zona'];
         $this->ruta = $c['ruta'];
         $this->tipo_portes = $c['tipo_portes'];
         $this->agencia_transporte = $c['agenciat'];
         $this->dias_pago = $c['dias_pago'];
         
         $this->i343 = TRUE;
         if( !is_null($c['i343']) AND $c['i343'] != '' )
            $this->i343 = $this->str2bool($c['i343']);
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
         $this->codserie = $this->default_items->codserie();
         $this->coddivisa = $this->default_items->coddivisa();
         $this->codpago = $this->default_items->codpago();
         $this->codagente = NULL;
         $this->codgrupo = NULL;
         $this->debaja = FALSE;
         $this->fechabaja = NULL;
         $this->observaciones = NULL;
         $this->tipoidfiscal = 'NIF';
         $this->regimeniva = 'General';
         $this->dtopor = 0;
         $this->zona = NULL;
         $this->ruta = NULL;
         $this->tipo_portes = NULL;
         $this->agencia_transporte = NULL;
         $this->dias_pago = NULL;
         $this->i343 = TRUE;
         $this->recargo = FALSE;
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
      return ( $this->codcliente == $this->default_items->codcliente() );
   }
   
   public function regimenes_iva()
   {
      return array('General', 'Exportaciones', 'U.E.', 'Exento');
   }
   
   public function zonas()
   {
      $zlist = array();
      
      $zonas = $this->db->select("SELECT DISTINCT(zona) as zona FROM ".$this->table_name.";");
      if($zonas)
      {
         foreach($zonas as $z)
            if($z['zona'] != '')
               $zlist[] = $z['zona'];
      }
      
      return $zlist;
   }
   
   public function rutas()
   {
      $rlist = array();
      
      $rutas = $this->db->select("SELECT DISTINCT(ruta) as ruta FROM ".$this->table_name.";");
      if($rutas)
      {
         foreach($rutas as $z)
            if($z['ruta'] != '')
               $rlist[] = $z['ruta'];
      }
      
      return $rlist;
   }
   
   public function tipos_portes()
   {
      $plist = array();
      
      $tportes = $this->db->select("SELECT DISTINCT(tipo_portes) as tipo_portes FROM ".$this->table_name.";");
      if($tportes)
      {
         foreach($tportes as $z)
            if($z['tipo_portes'] != '')
               $plist[] = $z['tipo_portes'];
      }
      
      return $plist;
   }
   
   public function agencias_transportes()
   {
      $alist = array();
      
      $agencias = $this->db->select("SELECT DISTINCT(agenciat) as agenciat FROM ".$this->table_name.";");
      if($agencias)
      {
         foreach($agencias as $z)
            if($z['agenciat'] != '')
               $alist[] = $z['agenciat'];
      }
      
      return $alist;
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
   
   public function get_facturas($offset=0)
   {
      $fac = new factura_cliente();
      return $fac->all_from_cliente($this->codcliente, $offset);
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
      $subcuenta = FALSE;
      
      foreach($this->get_subcuentas() as $s)
      {
         if($s->codejercicio == $ejercicio)
         {
            $subcuenta = $s;
            break;
         }
      }
      if( !$subcuenta )
      {
         /// intentamos crear la subcuenta y asociarla
         $continuar = TRUE;
         
         $cuenta = new cuenta();
         $ccli = $cuenta->get_by_codigo('430', $ejercicio);
         if( $ccli )
         {
            $codsubcuenta = 4300000000 + $this->codcliente;
            $subcuenta = new subcuenta();
            $subc0 = $subcuenta->get_by_codigo($codsubcuenta, $ejercicio);
            if( !$subc0 )
            {
               $subc0 = new subcuenta();
               $subc0->codcuenta = $ccli->codcuenta;
               $subc0->idcuenta = $ccli->idcuenta;
               $subc0->codejercicio = $ejercicio;
               $subc0->codsubcuenta = $codsubcuenta;
               $subc0->descripcion = $this->nombre;
               if( !$subc0->save() )
               {
                  $this->new_error_msg('Imposible crear la subcuenta para el cliente '.$this->codcliente);
                  $continuar = FALSE;
               }
            }
            
            if( $continuar )
            {
               $sccli = new subcuenta_cliente();
               $sccli->codcliente = $this->codcliente;
               $sccli->codejercicio = $ejercicio;
               $sccli->codsubcuenta = $subc0->codsubcuenta;
               $sccli->idsubcuenta = $subc0->idsubcuenta;
               if( $sccli->save() )
                  $subcuenta = $subc0;
               else
                  $this->new_error_msg('Imposible asociar la subcuenta para el cliente '.$this->codcliente);
            }
         }
      }
      
      return $subcuenta;
   }
   
   public function exists()
   {
      if( is_null($this->codcliente) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE codcliente = ".$this->var2str($this->codcliente).";");
   }
   
   public function get_new_codigo()
   {
      $cod = $this->db->select("SELECT MAX(".$this->db->sql_to_int('codcliente').") as cod FROM ".$this->table_name.";");
      if($cod)
         return sprintf('%06s', (1 + intval($cod[0]['cod'])));
      else
         return '000001';
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codcliente = trim($this->codcliente);
      $this->nombre = $this->no_html($this->nombre);
      $this->nombrecomercial = $this->no_html($this->nombrecomercial);
      $this->cifnif = $this->no_html($this->cifnif);
      $this->observaciones = $this->no_html($this->observaciones);
      
      if($this->zona == '')
         $this->zona = NULL;
      
      if($this->ruta == '')
         $this->ruta = NULL;
      
      if($this->tipo_portes == '')
         $this->tipo_portes = NULL;
      
      if($this->agencia_transporte == '')
         $this->agencia_transporte = NULL;
      
      if($this->dias_pago == '')
         $this->dias_pago = NULL;
      
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
               codagente = ".$this->var2str($this->codagente).", codgrupo = ".$this->var2str($this->codgrupo).",
               debaja = ".$this->var2str($this->debaja).", fechabaja = ".$this->var2str($this->fechabaja).",
               observaciones = ".$this->var2str($this->observaciones).",
               tipoidfiscal = ".$this->var2str($this->tipoidfiscal).", regimeniva = ".$this->var2str($this->regimeniva).",
               zona = ".$this->var2str($this->zona).", ruta = ".$this->var2str($this->ruta).",
               tipo_portes = ".$this->var2str($this->tipo_portes).", agenciat = ".$this->var2str($this->agencia_transporte).",
               dtopor = ".$this->var2str($this->dtopor).", dias_pago = ".$this->var2str($this->dias_pago).",
               i343 = ".$this->var2str($this->i343).", recargo = ".$this->var2str($this->recargo)."
               WHERE codcliente = ".$this->var2str($this->codcliente).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codcliente,nombre,nombrecomercial,cifnif,telefono1,
               telefono2,fax,email,web,codserie,coddivisa,codpago,codagente,codgrupo,debaja,fechabaja,
               observaciones,tipoidfiscal,regimeniva,zona,ruta,tipo_portes,agenciat,dtopor,dias_pago,i343,recargo)
               VALUES (".$this->var2str($this->codcliente).",".$this->var2str($this->nombre).",
               ".$this->var2str($this->nombrecomercial).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->telefono1).",".$this->var2str($this->telefono2).",
               ".$this->var2str($this->fax).",".$this->var2str($this->email).",
               ".$this->var2str($this->web).",".$this->var2str($this->codserie).",
               ".$this->var2str($this->coddivisa).",".$this->var2str($this->codpago).",".$this->var2str($this->codagente).",
               ".$this->var2str($this->codgrupo).",".$this->var2str($this->debaja).",".$this->var2str($this->fechabaja).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->tipoidfiscal).","
               .$this->var2str($this->regimeniva).",".$this->var2str($this->zona).",".$this->var2str($this->ruta).","
               .$this->var2str($this->tipo_portes).",".$this->var2str($this->agencia_transporte).","
               .$this->var2str($this->dtopor).",".$this->var2str($this->dias_pago).","
               .$this->var2str($this->i343).",".$this->var2str($this->recargo).");";
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
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
         $consulta .= "codcliente LIKE '%".$query."%' OR cifnif LIKE '%".$query."%' OR observaciones LIKE '%".$query."%'";
      else
      {
         $buscar = str_replace(' ', '%', $query);
         $consulta .= "lower(nombre) LIKE '%".$buscar."%' OR lower(cifnif) LIKE '%".$buscar."%'
            OR lower(observaciones) LIKE '%".$buscar."%'";
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
   
   public function search_by_dni($dni, $offset=0)
   {
      $clilist = array();
      $query = strtolower( $this->no_html($dni) );
      $consulta = "SELECT * FROM ".$this->table_name." WHERE lower(cifnif) LIKE '".$query."%' ORDER BY nombre ASC";
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