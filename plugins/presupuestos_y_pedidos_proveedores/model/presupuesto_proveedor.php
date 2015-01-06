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
require_model('pedido_proveedor.php');
require_model('articulo.php');
require_model('proveedor.php');
require_model('ejercicio.php');
require_model('linea_presupuesto_proveedor.php');
require_model('secuencia.php');

/**
 * Presupuesto de proveedor
 */
class presupuesto_proveedor extends fs_model
{
   public $idpresupuesto;
   public $idpedido;
   public $codigo;
   public $codserie;
   public $codejercicio;
   public $codproveedor;
   public $codagente;
   public $codpago;
   public $coddivisa;
   public $codalmacen;
   public $codpais;
   public $coddir;
   public $codpostal;
   public $numero;
   
   /**
    * Número opcional a disposición del usuario.
    * @var type 
    */
   public $numero2;
   public $nombre;
   public $cifnif;
   public $direccion;
   public $ciudad;
   public $provincia;
   public $apartado;
   public $fecha;
   public $finoferta;
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
      parent::__construct('presupuestosprov', 'plugins/presupuestos_y_pedidos_proveedores/');
      if($p)
      {
         $this->idpresupuesto = $this->intval($p['idpresupuesto']);
         $this->idpedido = $this->intval($p['idpedido']);
         $this->codigo = $p['codigo'];
         $this->codagente = $p['codagente'];
         $this->codpago = $p['codpago'];
         $this->codserie = $p['codserie'];
         $this->codejercicio = $p['codejercicio'];
         $this->codproveedor = $p['codproveedor'];
         $this->coddivisa = $p['coddivisa'];
         $this->codalmacen = $p['codalmacen'];
         $this->codpais = $p['codpais'];
         $this->coddir = $p['coddir'];
         $this->codpostal = $p['codpostal'];
         $this->numero = $p['numero'];
         $this->numero2 = $p['numero2'];
         $this->nombre = $p['nombre'];
         $this->cifnif = $p['cifnif'];
         $this->direccion = $p['direccion'];
         $this->ciudad = $p['ciudad'];
         $this->provincia = $p['provincia'];
         $this->apartado = $p['apartado'];
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));
         $this->finoferta = Date('d-m-Y', strtotime($p['finoferta']));

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
         $this->codproveedor = NULL;
         $this->coddivisa = NULL;
         $this->codalmacen = NULL;
         $this->codpais = NULL;
         $this->coddir = NULL;
         $this->codpostal = '';
         $this->numero = NULL;
         $this->numero2 = NULL;
         $this->nombre = NULL;
         $this->cifnif = NULL;
         $this->direccion = NULL;
         $this->ciudad = NULL;
         $this->provincia = NULL;
         $this->apartado = NULL;
         $this->fecha = Date('d-m-Y');
         $this->finoferta = date("d-m-Y", strtotime(Date('d-m-Y')." +30 days"));
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
         return 'index.php?page=compras_presupuestos';
      else
         return 'index.php?page=compras_presupuesto&id='.$this->idpresupuesto;
   }
   
   public function pedido_url()
   {
      if( is_null($this->idpedido) )
         return 'index.php?page=compras_pedido';
      else
         return 'index.php?page=compras_pedido&id='.$this->idpedido;
   }
   
   public function agente_url()
   {
      if( is_null($this->codagente) )
         return "index.php?page=admin_agentes";
      else
         return "index.php?page=admin_agente&cod=".$this->codagente;
   }
   
   public function proveedor_url()
   {
      if( is_null($this->codproveedor) )
         return "index.php?page=compras_proveedores";
      else
         return "index.php?page=compras_proveedor&cod=".$this->codproveedor;
   }
   
   public function get_lineas()
   {
      $linea = new linea_presupuesto_proveedor();
      return $linea->all_from_presupuesto($this->idpresupuesto);
   }
   
   public function get($id)
   {
      $presupuesto = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($id).";");
      if($presupuesto)
         return new presupuesto_proveedor($presupuesto[0]);
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
   
   public function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'npresupuestoprov');
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
      
      if( $this->floatcmp($this->total, $this->neto+$this->totaliva-$this->totalirpf+$this->totalrecargo, FS_NF0, TRUE) )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg("Error grave: El total está mal calculado. ¡Informa del error!");
         return FALSE;
      }
   }
   
   public function full_test($duplicados = TRUE)
   {
      $status = TRUE;
      
      /// comprobamos las líneas
      $neto = 0;
      $iva = 0;
      $irpf = 0;
      $recargo = 0;
      foreach($this->get_lineas() as $l)
      {
         if( !$l->test() )
            $status = FALSE;
         
         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
         $irpf += $l->pvptotal * $l->irpf / 100;
         $recargo += $l->pvptotal * $l->recargo / 100;
      }
      
      $neto = round($neto, FS_NF0);
      $iva = round($iva, FS_NF0);
      $irpf = round($irpf, FS_NF0);
      $recargo = round($recargo, FS_NF0);
      $total = $neto + $iva - $irpf + $recargo;
      
      if( !$this->floatcmp($this->neto, $neto, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor neto de ".FS_PRESUPUESTO." incorrecto. Valor correcto: ".$neto);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totaliva de ".FS_PRESUPUESTO." incorrecto. Valor correcto: ".$iva);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totalirpf de ".FS_PRESUPUESTO." incorrecto. Valor correcto: ".$irpf);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totalrecargo de ".FS_PRESUPUESTO." incorrecto. Valor correcto: ".$recargo);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->total, $total, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor total de ".FS_PRESUPUESTO." incorrecto. Valor correcto: ".$total);
         $status = FALSE;
      }
      else if( !$this->floatcmp($this->totaleuros, $this->total * $this->tasaconv, FS_NF0, TRUE) )
      {
         $this->new_error_msg("Valor totaleuros de ".FS_PRESUPUESTO." incorrecto.
            Valor correcto: ".round($this->total * $this->tasaconv, FS_NF0));
         $status = FALSE;
      }
      
      return $status;
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
               codproveedor = ".$this->var2str($this->codproveedor).", coddir = ".$this->var2str($this->coddir).",
               coddivisa = ".$this->var2str($this->coddivisa).", codejercicio = ".$this->var2str($this->codejercicio).",
               codigo = ".$this->var2str($this->codigo).", codpago = ".$this->var2str($this->codpago).",
               codpais = ".$this->var2str($this->codpais).", codpostal = ".$this->var2str($this->codpostal).",
               codserie = ".$this->var2str($this->codserie).", direccion = ".$this->var2str($this->direccion).",
               editable = ".$this->var2str($this->editable).", fecha = ".$this->var2str($this->fecha).",
               finoferta = ".$this->var2str($this->finoferta).", hora = ".$this->var2str($this->hora).",
               idpedido = ".$this->var2str($this->idpedido).", irpf = ".$this->var2str($this->irpf).",
               neto = ".$this->var2str($this->neto).",nombre = ".$this->var2str($this->nombre).",
               numero = ".$this->var2str($this->numero).", numero2 = ".$this->var2str($this->numero2).",
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
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codalmacen,codproveedor,coddir,
               coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,direccion,editable,fecha,finoferta,hora,idpedido,irpf,neto,
               nombre,numero,observaciones,porcomision,provincia,recfinanciero,tasaconv,total,totaleuros,totalirpf,
               totaliva,totalrecargo,numero2) VALUES (".$this->var2str($this->apartado).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->codproveedor).",
               ".$this->var2str($this->coddir).",".$this->var2str($this->coddivisa).",".$this->var2str($this->codejercicio).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->codpais).",".$this->var2str($this->codpago).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->codserie).",".$this->var2str($this->direccion).",
               ".$this->var2str($this->editable).",".$this->var2str($this->fecha).",".$this->var2str($this->finoferta).",
               ".$this->var2str($this->hora).",".$this->var2str($this->idpedido).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->neto).",".$this->var2str($this->nombre).",".$this->var2str($this->numero).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->porcomision).",".$this->var2str($this->provincia).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->tasaconv).",".$this->var2str($this->total).",
               ".$this->var2str($this->totaleuros).",".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",
               ".$this->var2str($this->totalrecargo).",".$this->var2str($this->numero2).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idpresupuesto = $this->db->lastval();
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
      if( $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";") )
      {
         if($this->idpedido)
         {
            /**
             * Delegamos la eliminación en la clase correspondiente,
             * que tendrá que hacer más cosas.
             */
            $pedido = new pedido_proveedor();
            $ped0 = $pedido->get($this->idpedido);
            if($ped0)
            {
               $ped0->delete();
            }
         }
         
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function all($offset=0)
   {
      $preslist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_proveedor($p);
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
            $preslist[] = new presupuesto_proveedor($p);
      }
      return $preslist;
   }
   
   public function all_from_proveedor($codproveedor, $offset=0)
   {
      $preslist = array();
      $presupuestos = $this->db->select_limit("SELECT * FROM ".$this->table_name.
              " WHERE codproveedor = ".$this->var2str($codproveedor).
              " ORDER BY fecha DESC, codigo DESC", FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_proveedor($p);
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
            $preslist[] = new presupuesto_proveedor($p);
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
            $preslist[] = new presupuesto_proveedor($p);
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
         $consulta .= "codigo LIKE '%".$query."%' OR numero2 LIKE '%".$query."%' OR observaciones LIKE '%".$query."%'
            OR total BETWEEN '".($query-.01)."' AND '".($query+.01)."'";
      }
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) ) /// es una fecha
      {
         $consulta .= "fecha = ".$this->var2str($query)." OR observaciones LIKE '%".$query."%'";
      }
      else
      {
         $consulta .= "lower(codigo) LIKE '%".$query."%' OR lower(numero2) LIKE '%".$query."%' "
                 . "OR lower(observaciones) LIKE '%".str_replace(' ', '%', $query)."%'";
      }
      $consulta .= " ORDER BY fecha DESC, codigo DESC";
      
      $presupuestos = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_proveedor($p);
      }
      return $preslist;
   }
   
   public function search_from_proveedor($codproveedor, $desde, $hasta, $serie, $obs='')
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE codproveedor = ".$this->var2str($codproveedor).
         " AND idpedido AND fecha BETWEEN ".$this->var2str($desde)." AND ".$this->var2str($hasta).
         " AND codserie = ".$this->var2str($serie);
      
      if($obs != '')
         $sql .= " AND lower(observaciones) = ".$this->var2str(strtolower($obs));
      
      $sql .= " ORDER BY fecha DESC, codigo DESC;";
      
      $presupuestos = $this->db->select($sql);
      if($presupuestos)
      {
         foreach($presupuestos as $p)
            $preslist[] = new presupuesto_proveedor($p);
      }
      return $preslist;
   }
}
