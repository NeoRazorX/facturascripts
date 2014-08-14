<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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
require_model('pedido_cliente.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('linea_presupuesto_cliente.php');
require_model('secuencia.php');

/**
 * Presupuesto de cliente
 */
class presupuesto_cliente extends fs_model
{
   public $idpresupuesto;
   public $idpedido;
   public $codigo;
   public $codserie;
   public $codejercicio;
   public $codcliente;
   public $codagente;
   public $codpago;
   public $coddivisa;
   public $codalmacen;
   public $codpais;
   public $coddir;
   public $codpostal;
   public $numero;
   public $nombrecliente;
   public $cifnif;
   public $direccion;
   public $ciudad;
   public $provincia;
   public $apartado;
   public $fecha;
   public $hora;
   public $neto;
   public $total;
   public $totaliva;
   public $totaleuros;
   public $irpf;
   public $totalirpf;
   public $porcomision;
   public $tasaconv;
   public $recfinanciero;
   public $totalrecargo;
   public $observaciones;

   public $editable;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('presupuestoscli', 'plugins/presupuestos_y_pedidos/');
      if($p)
      {
         $this->idpresupuesto = $this->intval($p['idpresupuesto']);
         $this->idpedido = $this->intval($p['idpedido']);
         $this->codigo = $p['codigo'];
         $this->codagente = $p['codagente'];
         $this->codpago = $p['codpago'];
         $this->codserie = $p['codserie'];
         $this->codejercicio = $p['codejercicio'];
         $this->codcliente = $p['codcliente'];
         $this->coddivisa = $p['coddivisa'];
         $this->codalmacen = $p['codalmacen'];
         $this->codpais = $p['codpais'];
         $this->coddir = $p['coddir'];
         $this->codpostal = $p['codpostal'];
         $this->numero = $p['numero'];
         $this->nombrecliente = $p['nombrecliente'];
         $this->cifnif = $p['cifnif'];
         $this->direccion = $p['direccion'];
         $this->ciudad = $p['ciudad'];
         $this->provincia = $p['provincia'];
         $this->apartado = $p['apartado'];
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));

         $this->hora = '00:00:00';
         if( !is_null($p['hora']) )
            $this->hora = $p['hora'];

         $this->neto = floatval($p['neto']);
         $this->total = floatval($p['total']);
         $this->totaliva = floatval($p['totaliva']);
         $this->totaleuros = floatval($p['totaleuros']);
         $this->irpf = floatval($p['irpf']);
         $this->totalirpf = floatval($p['totalirpf']);
         $this->porcomision = floatval($p['porcomision']);
         $this->tasaconv = floatval($p['tasaconv']);
         $this->recfinanciero = floatval($p['recfinanciero']);
         $this->totalrecargo = floatval($p['totalrecargo']);
         $this->observaciones = $p['observaciones'];

         $this->editable = $this->str2bool($p['editable']);
      }
      else
      {
         $this->idpresupuesto = NULL;
         $this->idpedido = NULL;
         $this->codigo = NULL;
         $this->codagente = NULL;
         $this->codpago = NULL;
         $this->codserie = NULL;
         $this->codejercicio = NULL;
         $this->codcliente = NULL;
         $this->coddivisa = NULL;
         $this->codalmacen = NULL;
         $this->codpais = NULL;
         $this->coddir = NULL;
         $this->codpostal = '';
         $this->numero = NULL;
         $this->nombrecliente = NULL;
         $this->cifnif = NULL;
         $this->direccion = NULL;
         $this->ciudad = NULL;
         $this->provincia = NULL;
         $this->apartado = NULL;
         $this->fecha = Date('d-m-Y');
         $this->hora = Date('H:i:s');
         $this->neto = 0;
         $this->total = 0;
         $this->totaliva = 0;
         $this->totaleuros = 0;
         $this->irpf = 0;
         $this->totalirpf = 0;
         $this->porcomision = NULL;
         $this->tasaconv = 1;
         $this->recfinanciero = 0;
         $this->totalrecargo = 0;
         $this->observaciones = NULL;
         
         $this->editable = TRUE;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function show_hora($s=TRUE)
   {
      if($s)
         return Date('H:i:s', strtotime($this->hora));
      else
         return Date('H:i', strtotime($this->hora));
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
      if( is_null($this->idpresupuesto) )
         return 'index.php?page=ventas_presupuestos';
      else
         return 'index.php?page=ventas_presupuesto&id='.$this->idpresupuesto;
   }
   
   public function pedido_url()
   {
      if( is_null($this->idpedido) )
      {
         return '#';
      }
      else
      {
         if( is_null($this->idpedido) )
            return 'index.php?page=ventas_pedido';
         else
            return 'index.php?page=ventas_pedido&id='.$this->idpedido;
      }
   }
   
   public function agente_url()
   {
      if( is_null($this->codagente) )
         return "index.php?page=admin_agentes";
      else
         return "index.php?page=admin_agente&cod=".$this->codagente;
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=ventas_clientes";
      else
         return "index.php?page=ventas_cliente&cod=".$this->codcliente;
   }
   
   public function get_lineas()
   {
      $linea = new linea_presupuesto_cliente();
      return $linea->all_from_presupuesto($this->idpresupuesto);
   }
   
   public function get($id)
   {
      $presupuesto = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($id).";");
      if($presupuesto)
         return new presupuesto_cliente($presupuesto[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idpresupuesto) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";");
   }
   
   public function new_idpresupuesto()
   {
      $newid = $this->db->nextval($this->table_name.'_idpresupuesto_seq');
      if($newid)
         $this->idpresupuesto = intval($newid);
   }
   
   private function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'npresupuestocli');
      if($sec)
      {
         $this->numero = $sec->valorout;
         $sec->valorout++;
         $sec->save();
      }
      
      if(!$sec OR $this->numero <= 1)
      {
         $numero = $this->db->select("SELECT MAX(".$this->db->sql_to_int('numero').") as num
            FROM ".$this->table_name." WHERE codejercicio = ".$this->var2str($this->codejercicio).
            " AND codserie = ".$this->var2str($this->codserie).";");
         if($numero)
            $this->numero = 1 + intval($numero[0]['num']);
         else
            $this->numero = 1;
         
         if($sec)
         {
            $sec->valorout = 1 + $this->numero;
            $sec->save();
         }
      }
      
      $this->codigo = $this->codejercicio.sprintf('%02s', $this->codserie).sprintf('%06s', $this->numero);
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function full_test($duplicados = TRUE)
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET apartado = ".$this->var2str($this->apartado).",
               cifnif = ".$this->var2str($this->cifnif).", ciudad = ".$this->var2str($this->ciudad).",
               codagente = ".$this->var2str($this->codagente).", codalmacen = ".$this->var2str($this->codalmacen).",
               codcliente = ".$this->var2str($this->codcliente).", coddir = ".$this->var2str($this->coddir).",
               coddivisa = ".$this->var2str($this->coddivisa).", codejercicio = ".$this->var2str($this->codejercicio).",
               codigo = ".$this->var2str($this->codigo).", codpago = ".$this->var2str($this->codpago).",
               codpais = ".$this->var2str($this->codpais).", codpostal = ".$this->var2str($this->codpostal).",
               codserie = ".$this->var2str($this->codserie).", direccion = ".$this->var2str($this->direccion).",
               editable = ".$this->var2str($this->editable).", fecha = ".$this->var2str($this->fecha).",
               hora = ".$this->var2str($this->hora).", idpedido = ".$this->var2str($this->idpedido).",
               irpf = ".$this->var2str($this->irpf).", neto = ".$this->var2str($this->neto).",
               nombrecliente = ".$this->var2str($this->nombrecliente).", numero = ".$this->var2str($this->numero).",
               observaciones = ".$this->var2str($this->observaciones).", porcomision = ".$this->var2str($this->porcomision).",
               provincia = ".$this->var2str($this->provincia).", recfinanciero = ".$this->var2str($this->recfinanciero).",
               tasaconv = ".$this->var2str($this->tasaconv).", total = ".$this->var2str($this->total).",
               totaleuros = ".$this->var2str($this->totaleuros).", totalirpf = ".$this->var2str($this->totalirpf).",
               totaliva = ".$this->var2str($this->totaliva).", totalrecargo = ".$this->var2str($this->totalrecargo)."
               WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";";
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_idpresupuesto();
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codalmacen,codcliente,coddir,
               coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,direccion,editable,fecha,hora,idpedido,irpf,neto,
               nombrecliente,numero,observaciones,porcomision,provincia,recfinanciero,tasaconv,total,totaleuros,totalirpf,
               totaliva,totalrecargo) VALUES (".$this->var2str($this->apartado).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->codcliente).",
               ".$this->var2str($this->coddir).",".$this->var2str($this->coddivisa).",".$this->var2str($this->codejercicio).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->codpais).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->codserie).",".$this->var2str($this->direccion).",
               ".$this->var2str($this->editable).",".$this->var2str($this->fecha).",".$this->var2str($this->hora).",
               ".$this->var2str($this->idpedido).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->neto).",".$this->var2str($this->nombrecliente).",".$this->var2str($this->numero).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->porcomision).",".$this->var2str($this->provincia).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->tasaconv).",".$this->var2str($this->total).",
               ".$this->var2str($this->totaleuros).",".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",
               ".$this->var2str($this->totalrecargo).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      if( $this->idpedido )
      {
         $pedido = new pedido_cliente();
         $pedido = $pedido->get($this->idpedido);
         if($pedido)
            $pedido->delete();
      }
      
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";");
   }
   
   public function all($offset=0)
   {
      $preslist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }
   
   public function all_ptepedir($offset=0, $order='DESC')
   {
      $preslist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE idpedido IS NULL ORDER BY fecha ".$order.", codigo ".$order, FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      $preslist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codcliente = ".$this->var2str($codcliente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }
   
   public function all_from_agente($codagente, $offset=0)
   {
      $preslist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codagente = ".$this->var2str($codagente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }
   
   public function all_desde($desde, $hasta)
   {
      $preslist = array();
      $presupuestos = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE fecha >= ".$this->var2str($desde)." AND fecha <= ".$this->var2str($hasta).
         " ORDER BY codigo ASC;");
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }

   public function search($query, $offset=0)
   {
      $preslist = array();
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
      
      $presupuestos = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }
   
   public function search_from_cliente($codcliente, $desde, $hasta, $serie, $obs='')
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente).
         " AND idpedido AND fecha BETWEEN ".$this->var2str($desde)." AND ".$this->var2str($hasta).
         " AND codserie = ".$this->var2str($serie);
      
      if($obs != '')
         $sql .= " AND lower(observaciones) = ".$this->var2str(strtolower($obs));
      
      $sql .= " ORDER BY fecha DESC, codigo DESC;";
      
      $presupuestos = $this->db->select($sql);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_cliente($p);
      }
      return $preslist;
   }
}
