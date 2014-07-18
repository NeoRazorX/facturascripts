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
require_model('articulo.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('pedido_cliente.php');
require_model('linea_presupuesto_cliente.php');
require_model('secuencia.php');

/**
 * Albarán (boceto de pedido o pedido preliminar) de cliente
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
   public $numero2;
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
   public $ptepedido;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('presupuestoscli', 'plugins/presupuestos2/');
      if($a)
      {
         $this->idpresupuesto = $this->intval($a['idpresupuesto']);
         if( $this->str2bool($a['ptepedido']) )
         {
            $this->ptepedido = TRUE;
            $this->idpedido = NULL;
         }
         else
         {
            $this->ptepedido = FALSE;
            $this->idpedido = $this->intval($a['idpedido']);
         }
         $this->codigo = $a['codigo'];
         $this->codagente = $a['codagente'];
         $this->codserie = $a['codserie'];
         $this->codejercicio = $a['codejercicio'];
         $this->codcliente = $a['codcliente'];
         $this->codpago = $a['codpago'];
         $this->coddivisa = $a['coddivisa'];
         $this->codalmacen = $a['codalmacen'];
         $this->codpais = $a['codpais'];
         $this->coddir = $a['coddir'];
         $this->codpostal = $a['codpostal'];
         $this->numero = $a['numero'];
         $this->numero2 = $a['numero2'];
         $this->nombrecliente = $a['nombrecliente'];
         $this->cifnif = $a['cifnif'];
         $this->direccion = $a['direccion'];
         $this->ciudad = $a['ciudad'];
         $this->provincia = $a['provincia'];
         $this->apartado = $a['apartado'];
         $this->fecha = Date('d-m-Y', strtotime($a['fecha']));
         
         $this->hora = '00:00:00';
         if( !is_null($a['hora']) )
            $this->hora = $a['hora'];
         
         $this->neto = floatval($a['neto']);
         $this->total = floatval($a['total']);
         $this->totaliva = floatval($a['totaliva']);
         $this->totaleuros = floatval($a['totaleuros']);
         $this->irpf = floatval($a['irpf']);
         $this->totalirpf = floatval($a['totalirpf']);
         $this->porcomision = floatval($a['porcomision']);
         $this->tasaconv = floatval($a['tasaconv']);
         $this->recfinanciero = floatval($a['recfinanciero']);
         $this->totalrecargo = floatval($a['totalrecargo']);
         $this->observaciones = $this->no_html($a['observaciones']);
      }
      else
      {
         $this->idpresupuesto = NULL;
         $this->idpedido = NULL;
         $this->codigo = NULL;
         $this->codagente = NULL;
         $this->codserie = NULL;
         $this->codejercicio = NULL;
         $this->codcliente = NULL;
         $this->codpago = NULL;
         $this->coddivisa = NULL;
         $this->codalmacen = NULL;
         $this->codpais = NULL;
         $this->coddir = NULL;
         $this->codpostal = '';
         $this->numero = NULL;
         $this->numero2 = NULL;
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
         $this->ptepedido = TRUE;
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
         return 'index.php?page=general_presupuestos_cli';
      else
         return 'index.php?page=general_presupuesto_cli&id='.$this->idpresupuesto;
   }
   
   public function pedido_url()
   {
      if( $this->ptepedido )
      {
         return '#';
      }
      else
      {
         if( is_null($this->idpedido) )
            return 'index.php?page=contabilidad_pedidos_cli';
         else
            return 'index.php?page=contabilidad_pedido_cli&id='.$this->idpedido;
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
         return "index.php?page=general_clientes";
      else
         return "index.php?page=general_cliente&cod=".$this->codcliente;
   }
   
   public function get_lineas()
   {
      $linea = new linea_presupuesto_cliente();
      return $linea->all_from_presupuesto($this->idpresupuesto);
   }
   
   public function get_agente()
   {
      $agente = new agente();
      return $agente->get($this->codagente);
   }
   
   public function get($id)
   {
      $presupuesto = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($id).";");
      if($presupuesto)
         return new presupuesto_cliente($presupuesto[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $presupuesto = $this->db->select("SELECT * FROM ".$this->table_name." WHERE upper(codigo) = ".strtoupper($this->var2str($cod)).";");
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
   
   public function new_codigo()
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
      $this->observaciones = $this->no_html($this->observaciones);
      $this->totaleuros = $this->total * $this->tasaconv;
      
      if( $this->floatcmp($this->total, $this->neto + $this->totaliva, 2, TRUE) )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg("Error grave: El total no es la suma del neto y el iva.
            ¡Avisa al informático!");
         return FALSE;
      }
   }
   
   public function full_test($duplicados = TRUE)
   {
      $status = TRUE;
      
      /// comprobamos las líneas
      $neto = 0;
      $iva = 0;
      foreach($this->get_lineas() as $l)
      {
         if( !$l->test() )
            $status = FALSE;
         
         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
      }
      
      /// comprobamos los totales
      if( !$this->floatcmp($this->neto, $neto, 2, TRUE) )
      {
         $this->new_error_msg("Valor neto de presupuesto incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaliva, $iva, 2, TRUE) )
      {
         $this->new_error_msg("Valor totaliva de presupuesto incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->total, $this->neto + $this->totaliva, 2, TRUE) )
      {
         $this->new_error_msg("Valor total de presupuesto incorrecto. Valor correcto: ".
                 round($this->neto + $this->totaliva, 2));
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaleuros, $this->total * $this->tasaconv, 2, TRUE) )
      {
         $this->new_error_msg("Valor totaleuros de presupuesto incorrecto.
            Valor correcto: ".round($this->total * $this->tasaconv, 2));
         $status = FALSE;
      }
      
      /// comprobamos los pedidos asociadas
      $linea_pedido = new linea_pedido_cliente();
      $pedidos = $linea_pedido->pedidos_from_presupuesto( $this->idpresupuesto );
      if($pedidos)
      {
         if( count($pedidos) > 1 )
         {
            $msg = "Este presupuesto esta asociado a los siguientes pedidos (y no debería):";
            foreach($pedidos as $f)
               $msg .= " <a href='".$f->url()."'>".$f->codigo."</a>";
            $this->new_error_msg($msg);
            $status = FALSE;
         }
         else if($pedidos[0]->idpedido != $this->idpedido)
         {
            $this->new_error_msg("Este presupuesto esta asociado a una <a href='".$this->pedido_url().
                    "'>pedido</a> incorrecta. El correcta es <a href='".$pedidos[0]->url().
                    "'>esta</a>.");
            $status = FALSE;
         }
      }
      else if( isset($this->idpedido) )
      {
         $this->new_error_msg("Este presupuesto esta asociado a una <a href='".$this->pedido_url()."'>pedido</a> incorrecta.");
         $status = FALSE;
      }
      
      if($status AND $duplicados)
      {
         /// comprobamos si es un duplicado
         $presupuestos = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fecha = ".$this->var2str($this->fecha)."
            AND codcliente = ".$this->var2str($this->codcliente)." AND total = ".$this->var2str($this->total)."
            AND codagente = ".$this->var2str($this->codagente)." AND numero2 = ".$this->var2str($this->numero2)."
            AND observaciones = ".$this->var2str($this->observaciones)." AND idpresupuesto != ".$this->var2str($this->idpresupuesto).";");
         if($presupuestos)
         {
            foreach($presupuestos as $presu)
            {
               /// comprobamos las líneas
               $aux = $this->db->select("SELECT referencia FROM lineaspresupuestoscli WHERE
                  idpresupuesto = ".$this->var2str($this->idpresupuesto)."
                  AND referencia NOT IN (SELECT referencia FROM lineaspresupuestoscli
                  WHERE idpresupuesto = ".$this->var2str($presu['idpresupuesto']).");");
               if( !$aux )
               {
                  $this->new_error_msg("Este presupuesto es un posible duplicado de
                     <a href='index.php?page=general_presupuesto_cli&id=".$presu['idpresupuesto']."'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                  $status = FALSE;
               }
            }
         }
      }
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET idpedido = ".$this->var2str($this->idpedido).",
               codigo = ".$this->var2str($this->codigo).",codagente = ".$this->var2str($this->codagente).",
               codserie = ".$this->var2str($this->codserie).",
               codejercicio = ".$this->var2str($this->codejercicio).",
               codcliente = ".$this->var2str($this->codcliente).",
               codpago = ".$this->var2str($this->codpago).",coddivisa = ".$this->var2str($this->coddivisa).",
               codalmacen = ".$this->var2str($this->codalmacen).",
               codpais = ".$this->var2str($this->codpais).", coddir = ".$this->var2str($this->coddir).",
               codpostal = ".$this->var2str($this->codpostal).", numero = ".$this->var2str($this->numero).",
               numero2 = ".$this->var2str($this->numero2).",
               nombrecliente = ".$this->var2str($this->nombrecliente).",
               cifnif = ".$this->var2str($this->cifnif).", direccion = ".$this->var2str($this->direccion).",
               ciudad = ".$this->var2str($this->ciudad).", provincia = ".$this->var2str($this->provincia).",
               apartado = ".$this->var2str($this->apartado).", fecha = ".$this->var2str($this->fecha).",
               hora = ".$this->var2str($this->hora).", neto = ".$this->var2str($this->neto).",
               total = ".$this->var2str($this->total).", totaliva = ".$this->var2str($this->totaliva).",
               totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
               totalirpf = ".$this->var2str($this->totalirpf).",
               porcomision = ".$this->var2str($this->porcomision).",
               tasaconv = ".$this->var2str($this->tasaconv).",
               recfinanciero = ".$this->var2str($this->recfinanciero).",
               totalrecargo = ".$this->var2str($this->totalrecargo).",
               observaciones = ".$this->var2str($this->observaciones).",
               ptepedido = ".$this->var2str($this->ptepedido)."
               WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";";
         }
         else
         {
            $this->new_idpresupuesto();
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (idpresupuesto,idpedido,codigo,codagente,
               codserie,codejercicio,codcliente,codpago,coddivisa,codalmacen,codpais,coddir,
               codpostal,numero,numero2,nombrecliente,cifnif,direccion,ciudad,provincia,apartado,
               fecha,hora,neto,total,totaliva,totaleuros,irpf,totalirpf,porcomision,tasaconv,
               recfinanciero,totalrecargo,observaciones,ptepedido) VALUES
               (".$this->var2str($this->idpresupuesto).",".$this->var2str($this->idpedido).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->codagente).",
               ".$this->var2str($this->codserie).",".$this->var2str($this->codejercicio).",
               ".$this->var2str($this->codcliente).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->coddivisa).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->codpais).",".$this->var2str($this->coddir).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->numero).",
               ".$this->var2str($this->numero2).",".$this->var2str($this->nombrecliente).",
               ".$this->var2str($this->cifnif).",".$this->var2str($this->direccion).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->provincia).",
               ".$this->var2str($this->apartado).",".$this->var2str($this->fecha).",
               ".$this->var2str($this->hora).",".$this->var2str($this->neto).",
               ".$this->var2str($this->total).",".$this->var2str($this->totaliva).",
               ".$this->var2str($this->totaleuros).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->totalirpf).",".$this->var2str($this->porcomision).",
               ".$this->var2str($this->tasaconv).",".$this->var2str($this->recfinanciero).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",
               ".$this->var2str($this->ptepedido).");";
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
      $presualist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $a)
            $presualist[] = new presupuesto_cliente($a);
      }
      return $presualist;
   }
   
   public function all_ptepedido($offset=0, $order='DESC')
   {
      $presualist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE ptepedido = true ORDER BY fecha ".$order.", codigo ".$order, FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $a)
            $presualist[] = new presupuesto_cliente($a);
      }
      return $presualist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      $presualist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codcliente = ".$this->var2str($codcliente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $a)
            $presualist[] = new presupuesto_cliente($a);
      }
      return $presualist;
   }
   
   public function all_from_agente($codagente, $offset=0)
   {
      $presualist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codagente = ".$this->var2str($codagente).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $a)
            $presualist[] = new presupuesto_cliente($a);
      }
      return $presualist;
   }
   
   public function all_desde($desde, $hasta)
   {
      $presulist = array();
      $presupuestos = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE fecha >= ".$this->var2str($desde)." AND fecha <= ".$this->var2str($hasta).
         " ORDER BY codigo ASC;");
      if($presupuestos)
      {
         foreach($presupuestos as $a)
            $presulist[] = new presupuesto_cliente($a);
      }
      return $presulist;
   }
   
   public function search($query, $offset=0)
   {
      $presulist = array();
      $query = strtolower( $this->no_html($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
      {
         $consulta .= "codigo LIKE '%".$query."%' OR numero2 LIKE '%".$query."%' OR observaciones LIKE '%".$query."%'
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
         foreach($presupuestos as $a)
            $presulist[] = new presupuesto_cliente($a);
      }
      return $presulist;
   }
   
   public function search_from_cliente($codcliente, $desde, $hasta, $serie, $obs='')
   {
      $presualist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente).
         " AND ptepedido AND fecha BETWEEN ".$this->var2str($desde)." AND ".$this->var2str($hasta).
         " AND codserie = ".$this->var2str($serie);
      
      if($obs != '')
         $sql .= " AND lower(observaciones) = ".$this->var2str(strtolower($obs));
      
      $sql .= " ORDER BY fecha DESC, codigo DESC;";
      
      $presupuestos = $this->db->select($sql);
      if($presupuestos)
      {
         foreach($presupuestos as $a)
            $presualist[] = new presupuesto_cliente($a);
      }
      return $presualist;
   }
   
   public function cron_job()
   {
      /*
       * Marcamos como ptepedido = TRUE todos los presupuestos de ejercicios
       * ya cerrados. Así no se podrán modificar ni pedidor.
       */
      $ejercicio = new ejercicio();
      foreach($ejercicio->all() as $eje)
      {
         if( !$eje->abierto() )
         {
            $this->db->exec("UPDATE ".$this->table_name." SET ptepedido = FALSE
               WHERE codejercicio = ".$this->var2str($eje->codejercicio).";");
         }
      }
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
