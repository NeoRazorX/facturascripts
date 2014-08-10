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
require_model('albaran_cliente.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('linea_pedido_cliente.php');
require_model('secuencia.php');

/**
 * Pedido de cliente
 */
class pedido_cliente extends fs_model
{
   public $idpedido;
   public $idalbaran;
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
   public $servido;
   public $fechasalida;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('pedidoscli', 'plugins/presupuestos_y_pedidos/');
      if($p)
      {
         $this->idpedido = $this->intval($p['idpedido']);
         $this->idalbaran = $this->intval($p['idalbaran']);
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

         $this->hora =  Date('H:i:s', strtotime($p['fecha']));
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
         $this->servido = $this->str2bool($p['servido']);
         
         $this->fechasalida = NULL;
         if( isset($p['fechasalida']) )
            $this->fechasalida = Date('d-m-Y', strtotime($p['fechasalida']));
      }
      else
      {
         $this->idpedido = NULL;
         $this->idalbaran = NULL;
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
         $this->servido = FALSE;
         $this->fechasalida = NULL;
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
      if( is_null($this->idpedido) )
         return 'index.php?page=ventas_pedidos';
      else
         return 'index.php?page=ventas_pedido&id='.$this->idpedido;
   }
   
   public function albaran_url()
   {
      if( is_null($this->idalbaran) )
      {
         return '#';
      }
      else
      {
         return 'index.php?page=ventas_albaran&id='.$this->idalbaran;
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
      $linea = new linea_pedido_cliente();
      return $linea->all_from_pedido($this->idpedido);
   }
   
   public function get_agente()
   {
      $agente = new agente();
      return $agente->get($this->codagente);
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
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($this->idpedido).";");
   }
   
   public function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'npedidocli');
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
               editable = ".$this->var2str($this->editable).", fecha = ".$this->var2str($this->fecha).", hora = ".$this->var2str($this->hora).",
               fechasalida = ".$this->var2str($this->fechasalida).", idalbaran = ".$this->var2str($this->idalbaran).",
               irpf = ".$this->var2str($this->irpf).", neto = ".$this->var2str($this->neto).",
               nombrecliente = ".$this->var2str($this->nombrecliente).", numero = ".$this->var2str($this->numero).",
               observaciones = ".$this->var2str($this->observaciones).", porcomision = ".$this->var2str($this->porcomision).",
               provincia = ".$this->var2str($this->provincia).", recfinanciero = ".$this->var2str($this->recfinanciero).",
               servido = ".$this->var2str($this->servido).", tasaconv = ".$this->var2str($this->tasaconv).",
               total = ".$this->var2str($this->total).", totaleuros = ".$this->var2str($this->totaleuros).",
               totalirpf = ".$this->var2str($this->totalirpf).", totaliva = ".$this->var2str($this->totaliva).",
               totalrecargo = ".$this->var2str($this->totalrecargo)." WHERE idpedido = ".$this->var2str($this->idpedido).";";
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codalmacen,
               codcliente,coddir,coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,
               direccion,editable,fecha,hora,fechasalida,idalbaran,irpf,neto,nombrecliente,
               numero,observaciones,porcomision,provincia,recfinanciero,servido,tasaconv,total,totaleuros,
               totalirpf,totaliva,totalrecargo) VALUES (".$this->var2str($this->apartado).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->codcliente).",".$this->var2str($this->coddir).",".$this->var2str($this->coddivisa).",
               ".$this->var2str($this->codejercicio).",".$this->var2str($this->codigo).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->codpais).",".$this->var2str($this->codpostal).",".$this->var2str($this->codserie).",
               ".$this->var2str($this->direccion).",".$this->var2str($this->editable).",".$this->var2str($this->fecha).",
               ".$this->var2str($this->hora).",".$this->var2str($this->fechasalida).",".$this->var2str($this->idalbaran).",
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
      if( $this->idalbaran )
      {
         $albaran = new albaran_cliente();
         $albaran = $albaran->get($this->idalbaran);
         if($albaran)
            $albaran->delete();
      }
      
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($this->idpedido).";");
   }
   
   public function all($offset=0)
   {
      $pedilist = array();
      
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedilist[] = new pedido_cliente($p);
      }
      return $pedilist;
   }
   
   public function all_ptealbaran($offset=0, $order='DESC')
   {
      $pedilist = array();
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE idalbaran IS NULL ORDER BY fecha ".$order.", codigo ".$order, FS_ITEM_LIMIT, $offset);
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
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codcliente = ".$this->var2str($codcliente).
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
      $pedidos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codagente = ".$this->var2str($codagente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedilist[] = new pedido_cliente($p);
      }
      return $pedilist;
   }
   
   public function all_desde($desde, $hasta)
   {
      $pedlist = array();
      $pedidos = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE fecha >= ".$this->var2str($desde)." AND fecha <= ".$this->var2str($hasta).
         " ORDER BY codigo ASC;");
      if($pedidos)
      {
         foreach($pedidos as $p)
            $pedlist[] = new pedido_cliente($p);
      }
      return $pedlist;
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
   
   public function stats_last_days($numdays = 25)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('d-m-Y').'-'.$numdays.' day'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 day', 'd') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('day' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMDD')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%d')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as dia, sum(total) as total
         FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)."
         AND fecha <= ".$this->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY dia ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['dia']);
            $stats[$i] = array(
                'day' => $i,
                'total' => floatval($d['total'])
            );
         }
      }
      return $stats;
   }
   
   public function stats_last_months($num = 11)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('01-m-Y').'-'.$num.' month'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 month', 'm') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('month' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMMM')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%m')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(total) as total
         FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)."
         AND fecha <= ".$this->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY mes ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['mes']);
            $stats[$i] = array(
                'month' => $i,
                'total' => floatval($d['total'])
            );
         }
      }
      return $stats;
   }
   
   public function stats_last_years($num = 4)
   {
      $stats = array();
      $desde = Date('d-m-Y', strtotime( Date('d-m-Y').'-'.$num.' year'));
      
      foreach($this->date_range($desde, Date('d-m-Y'), '+1 year', 'Y') as $date)
      {
         $i = intval($date);
         $stats[$i] = array('year' => $i, 'total' => 0);
      }
      
      if( strtolower(FS_DB_TYPE) == 'postgresql')
         $sql_aux = "to_char(fecha,'FMYYYY')";
      else
         $sql_aux = "DATE_FORMAT(fecha, '%Y')";
      
      $data = $this->db->select("SELECT ".$sql_aux." as ano, sum(total) as total
         FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)."
         AND fecha <= ".$this->var2str(Date('d-m-Y'))."
         GROUP BY ".$sql_aux." ORDER BY ano ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $i = intval($d['ano']);
            $stats[$i] = array(
                'year' => $i,
                'total' => floatval($d['total'])
            );
         }
      }
      return $stats;
   }
   
   /*
    * Devuelve un array con los datos estadísticos de las compras del cliente
    * en los cinco últimos años.
    */
   public function stats_from_cli($codcliente)
   {
      $stats = array();
      $years = array();
      for($i=4; $i>=0; $i--)
         $years[] = intval(Date('Y')) - $i;
      
      $meses = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
      
      foreach($years as $year)
      {
         for($i = 1; $i <= 12; $i++)
         {
            $stats[$year.'-'.$i]['mes'] = $meses[$i-1].' '.$year;
            $stats[$year.'-'.$i]['compras'] = 0;
         }
         
         if( strtolower(FS_DB_TYPE) == 'postgresql')
            $sql_aux = "to_char(fecha,'FMMM')";
         else
            $sql_aux = "DATE_FORMAT(fecha, '%m')";
         
         $data = $this->db->select("SELECT ".$sql_aux." as mes, sum(total) as total
            FROM ".$this->table_name." WHERE fecha >= ".$this->var2str(Date('1-1-'.$year))."
            AND fecha <= ".$this->var2str(Date('31-12-'.$year))." AND codcliente = ".$this->var2str($codcliente)."
            GROUP BY ".$sql_aux." ORDER BY mes ASC;");
         if($data)
         {
            foreach($data as $d)
               $stats[$year.'-'.intval($d['mes'])]['compras'] = number_format($d['total'], FS_NF0, '.', '');
         }
      }
      
      return $stats;
   }
}
