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

class pedido_cliente extends fs_model
{
   public $apartado;
   public $cifnif;
   public $ciudad;
   public $codagente;
   public $codalmacen;
   public $codcliente;
   public $coddir;
   public $coddivisa;
   public $codejercicio;
   public $codigo;
   public $codpago;
   public $codpais;
   public $codpostal;
   public $codserie;
   public $direccion;
   public $editable;
   public $fecha;
   public $fechasalida;
   public $idpedido;
   public $idpresupuesto;
   public $irpf;
   public $neto;
   public $nombrecliente;
   public $numero;
   public $observaciones;
   public $porcomision;
   public $provincia;
   public $recfinanciero;
   public $servido;
   public $tasaconv;
   public $total;
   public $totaleuros;
   public $totalirpf;
   public $totaliva;
   public $totalrecargo;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('pedidoscli', 'plugins/presupuestos_y_pedidos/');
      
      if($p)
      {
         $this->apartado = $p['apartado'];
         $this->cifnif = $p['cifnif'];
         $this->ciudad = $p['ciudad'];
         $this->codagente = $p['codagente'];
         $this->codalmacen = $p['codalmacen'];
         $this->codcliente = $p['codcliente'];
         $this->coddir = $p['coddir'];
         $this->coddivisa = $p['coddivisa'];
         $this->codejercicio = $p['codejercicio'];
         $this->codigo = $p['codigo'];
         $this->codpago = $p['codpago'];
         $this->codpais = $p['codpais'];
         $this->codpostal = $p['codpostal'];
         $this->codserie = $p['codserie'];
         $this->direccion = $p['direccion'];
         $this->editable = $this->str2bool($p['editable']);
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));
         $this->fechasalida = Date('d-m-Y', strtotime($p['fechasalida']));
         $this->idpedido = intval($p['idpedido']);
         $this->idpresupuesto = $this->intval($p['idpresupuesto']);
         $this->irpf = floatval($p['irpf']);
         $this->neto = floatval($p['neto']);
         $this->nombrecliente = $p['nombrecliente'];
         $this->numero = intval($p['numero']);
         $this->observaciones = $p['observaciones'];
         $this->porcomision = floatval($p['porcomision']);
         $this->provincia = $p['provincia'];
         $this->recfinanciero = floatval($p['recfinanciero']);
         $this->servido = $this->str2bool($p['servido']);
         $this->tasaconv = floatval($p['tasaconv']);
         $this->total = floatval($p['total']);
         $this->totaleuros = floatval($p['totaleuros']);
         $this->totalirpf = floatval($p['totalirpf']);
         $this->totaliva = floatval($p['totaliva']);
         $this->totalrecargo = floatval($p['totalrecargo']);
      }
      else
      {
         $this->apartado = NULL;
         $this->cifnif = NULL;
         $this->ciudad = NULL;
         $this->codagente = NULL;
         $this->codalmacen = NULL;
         $this->codcliente = NULL;
         $this->coddir = NULL;
         $this->coddivisa = NULL;
         $this->codejercicio = NULL;
         $this->codigo = NULL;
         $this->codpago = NULL;
         $this->codpais = NULL;
         $this->codpostal = NULL;
         $this->codserie = NULL;
         $this->direccion = NULL;
         $this->editable = TRUE;
         $this->fecha = Date('d-m-Y');
         $this->fechasalida = NULL;
         $this->idpedido = NULL;
         $this->idpresupuesto = NULL;
         $this->irpf = 0;
         $this->neto = 0;
         $this->nombrecliente = NULL;
         $this->numero = NULL;
         $this->observaciones = '';
         $this->porcomision = NULL;
         $this->provincia = NULL;
         $this->recfinanciero = 0;
         $this->servido = FALSE;
         $this->tasaconv = 1;
         $this->total = 0;
         $this->totaleuros = 0;
         $this->totalirpf = 0;
         $this->totaliva = 0;
         $this->totalrecargo = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idpedido) )
         return 'index.php?page=pedidos_cliente';
      else
         return 'index.php?page=ver_pedido_cli&id='.$this->idpedido;
   }
   
   public function get_lineas()
   {
      $linea = new linea_pedido_cliente();
      return $linea->all_from_pedido($this->idpedido);
   }
   
   public function get($id)
   {
      $pedido = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($id).";");
      if($pedido)
         return new pedido_cliente($pedido[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idpedido) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($this->idalbaran).";");
   }
   
   public function test()
   {
      
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            
         }
         else
         {
            $sql = "INSER INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codalmacen,
               codcliente,coddir,coddivisa,codejercicio,codigo,codpago,codpais,codpostal,codserie,
               direccion,editable,fecha,fechasalida,idpresupuesto,irpf,neto,nombrecliente,
               numero,observaciones,porcomision,provincia,recfinanciero,servido,tasaconv,total,totaleuros,
               totalirpf,totaliva,totalrecargo) VALUES (".$this->var2str($this->apartado).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->codcliente).",".$this->var2str($this->coddir).",".$this->var2str($this->coddivisa).",
               ".$this->var2str($this->codejercicio).",".$this->var2str($this->codigo).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->codpais).",".$this->var2str($this->codpostal).",".$this->var2str($this->codserie).",
               ".$this->var2str($this->direccion).",".$this->var2str($this->editable).",".$this->var2str($this->fecha).",
               ".$this->var2str($this->fechasalida).",".$this->var2str($this->idpresupuesto).",
               ".$this->var2str($this->irpf).",".$this->var2str($this->neto).",".$this->var2str($this->nombrecliente).",
               ".$this->var2str($this->numero).",".$this->var2str($this->observaciones).",".$this->var2str($this->porcomision).",
               ".$this->var2str($this->provincia).",".$this->var2str($this->recfinanciero).",".$this->var2str($this->servido).",
               ".$this->var2str($this->tasaconv).",".$this->var2str($this->total).",".$this->var2str($this->totaleuros).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",".$this->var2str($this->totalrecargo).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idpedido = $this->db->lastval();
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
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($this->idpedido).";");
   }
   
   public function all($offset = 0)
   {
      $pedilist = array();
      
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedilist[] = new pedido_cliente($p);
      }
      
      return $pedilist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      $pedilist = array();
      
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedilist[] = new pedido_cliente($p);
      }
      
      return $pedilist;
   }
   
   public function all_from_agente($codagente, $offset=0)
   {
      $pedilist = array();
      
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codagente = ".$this->var2str($codagente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedilist[] = new pedido_cliente($p);
      }
      
      return $pedilist;
   }
   
   public function search($query, $offset=0)
   {
      $pedilist = array();
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $consulta .= "codigo LIKE '%".$query."%' OR observaciones LIKE '%".$query."%'
            OR total BETWEEN '".($query-.01)."' AND '".($query+.01)."'";
      }
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) ) /// es una fecha
         $consulta .= "fecha = ".$this->var2str($query)." OR observaciones LIKE '%".$query."%'";
      else
         $consulta .= "lower(codigo) LIKE '%".$query."%' OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC, codigo DESC";
      
      $pedidos = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedilist[] = new pedido_cliente($p);
      }
      
      return $pedilist;
   }
   
   public function cron_job()
   {
      
   }
}
