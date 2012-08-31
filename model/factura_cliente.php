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
require_once 'model/agente.php';
require_once 'model/albaran_cliente.php';
require_once 'model/articulo.php';
require_once 'model/asiento.php';
require_once 'model/cliente.php';
require_once 'model/secuencia.php';

class linea_factura_cliente extends fs_model
{
   public $pvptotal;
   public $dtopor;
   public $recargo;
   public $irpf;
   public $pvpsindto;
   public $cantidad;
   public $codimpuesto;
   public $pvpunitario;
   public $idlinea;
   public $idfactura;
   public $idalbaran;
   public $descripcion;
   public $dtolineal;
   public $referencia;
   public $iva;

   public function __construct($l=FALSE)
   {
      parent::__construct('lineasfacturascli');
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idfactura = $this->intval($l['idfactura']);
         $this->idalbaran = $this->intval($l['idalbaran']);
         $this->referencia = $l['referencia'];
         $this->descripcion = $l['descripcion'];
         $this->cantidad = floatval($l['cantidad']);
         $this->pvpunitario = floatval($l['pvpunitario']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->dtopor = floatval($l['dtopor']);
         $this->dtolineal = floatval($l['dtolineal']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->recargo = floatval($l['recargo']);
         $this->irpf = floatval($l['irpf']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idfactura = NULL;
         $this->idalbaran = NULL;
         $this->referencia = '';
         $this->descripcion = '';
         $this->cantidad = 0;
         $this->pvpunitario = 0;
         $this->pvpsindto = 0;
         $this->dtopor = 0;
         $this->dtolineal = 0;
         $this->pvptotal = 0;
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->recargo = 0;
         $this->irpf = 0;
      }
   }
   
   public function show_pvp()
   {
      return number_format($this->pvpunitario, 2, '.', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->pvptotal, 2, '.', ' ');
   }
   
   public function show_total_iva()
   {
      return number_format($this->pvptotal*(100+$this->iva)/100, 2, '.', ' ');
   }
   
   public function url()
   {
      $fac = new factura_cliente();
      $fac = $fac->get($this->idfactura);
      return $fac->url();
   }
   
   public function albaran_url()
   {
      $alb = new albaran_cliente();
      $alb = $alb->get($this->idalbaran);
      return $alb->url();
   }
   
   public function articulo_url()
   {
      $art = new articulo();
      $art = $art->get($this->referencia);
      return $art->url();
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idlinea) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function new_idlinea()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idlinea_seq');");
      if($newid)
         $this->idlinea = intval($newid[0]['nextval']);
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
            idalbaran = ".$this->var2str($this->idalbaran).", referencia = ".$this->var2str($this->referencia).",
            descripcion = ".$this->var2str($this->descripcion).", cantidad = ".$this->var2str($this->cantidad).",
            pvpunitario = ".$this->var2str($this->pvpunitario).", pvpsindto = ".$this->var2str($this->pvpsindto).",
            dtopor = ".$this->var2str($this->dtopor).", dtolineal = ".$this->var2str($this->dtolineal).",
            pvptotal = ".$this->var2str($this->pvptotal).", codimpuesto = ".$this->var2str($this->codimpuesto).",
            iva = ".$this->var2str($this->iva).", recargo = ".$this->var2str($this->recargo).",
            irpf = ".$this->var2str($this->irpf)." WHERE idlinea = ".$this->var2str($this->idlinea).";";
      }
      else
      {
         $this->new_idlinea();
         $sql = "INSERT INTO ".$this->table_name." (idlinea,idfactura,idalbaran,referencia,descripcion,cantidad,
            pvpunitario,pvpsindto,dtopor,dtolineal,pvptotal,codimpuesto,iva,recargo,irpf) VALUES
            (".$this->var2str($this->idlinea).",".$this->var2str($this->idfactura).",".$this->var2str($this->idalbaran).",
            ".$this->var2str($this->referencia).",".$this->var2str($this->descripcion).",".$this->var2str($this->cantidad).",
            ".$this->var2str($this->pvpunitario).",".$this->var2str($this->pvpsindto).",".$this->var2str($this->dtopor).",
            ".$this->var2str($this->dtolineal).",".$this->var2str($this->pvptotal).",".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->iva).",".$this->var2str($this->recargo).",".$this->var2str($this->irpf).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function all_from_factura($id)
   {
      $linlist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$id."';");
      if($lineas)
      {
         foreach($lineas as $l)
            $linlist[] = new linea_factura_cliente($l);
      }
      return $linlist;
   }
   
   public function all_from_articulo($ref, $offset=0)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE referencia = '".$ref."' ORDER BY idalbaran DESC",
              FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_factura_cliente($l);
      }
      return $linealist;
   }
}


class linea_iva_factura_cliente extends fs_model
{
   public $totallinea;
   public $totalrecargo;
   public $recargo;
   public $totaliva;
   public $iva;
   public $codimpuesto;
   public $neto;
   public $idfactura;
   public $idlinea;
   
   public function __construct($l=FALSE)
   {
      parent::__construct('lineasivafactcli');
      if($l)
      {
         $this->idlinea = $this->intval($l['idlinea']);
         $this->idfactura = $this->intval($l['idfactura']);
         $this->neto = floatval($l['neto']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->totaliva = floatval($l['totaliva']);
         $this->recargo = floatval($l['recargo']);
         $this->totalrecargo = floatval($l['totalrecargo']);
         $this->totallinea = floatval($l['totallinea']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idfactura = NULL;
         $this->neto = 0;
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->totaliva = 0;
         $this->recargo = 0;
         $this->totalrecargo = 0;
         $this->totallinea = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function show_neto()
   {
      return number_format($this->neto, 2, '.', ' ');
   }
   
   public function show_iva()
   {
      return number_format($this->iva, 2, '.', ' ');
   }
   
   public function show_totaliva()
   {
      return number_format($this->totaliva, 2, '.', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->totallinea, 2, '.', ' ');
   }
   
   public function exists()
   {
      if( isset($this->idlinea) )
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
      else
         return FALSE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
            neto = ".$this->var2str($this->neto).", codimpuesto = ".$this->var2str($this->codimpuesto).",
            iva = ".$this->var2str($this->iva).", totaliva = ".$this->var2str($this->totaliva).",
            recargo = ".$this->var2str($this->recargo).", totalrecargo = ".$this->var2str($this->totalrecargo).",
            totallinea = ".$this->var2str($this->totallinea)." WHERE idlinea = '".$this->idlinea."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idfactura,neto,codimpuesto,iva,totaliva,recargo,totalrecargo,totallinea)
            VALUES (".$this->var2str($this->idfactura).",".$this->var2str($this->neto).",".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->iva).",".$this->var2str($this->totaliva).",".$this->var2str($this->recargo).",
            ".$this->var2str($this->totalrecargo).",".$this->var2str($this->totallinea).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");;
   }
   
   public function all_from_factura($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$id."';");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_iva_factura_cliente($l);
      }
      return $linealist;
   }
}


class factura_cliente extends fs_model
{
   public $idfactura;
   public $idasiento;
   public $idpagodevol;
   public $idfacturarect;
   public $codigo;
   public $numero;
   public $codigorect;
   public $codejercicio;
   public $codserie;
   public $codalmacen;
   public $codpago;
   public $coddivisa;
   public $fecha;
   public $codcliente;
   public $nombrecliente;
   public $cifnif;
   public $direccion;
   public $ciudad;
   public $provincia;
   public $apartado;
   public $coddir;
   public $codpostal;
   public $codpais;
   public $codagente;
   public $neto;
   public $totaliva;
   public $total;
   public $totaleuros;
   public $irpf;
   public $totalirpf;
   public $porcomision;
   public $tasaconv;
   public $recfinanciero;
   public $totalrecargo;
   public $observaciones;
   public $deabono;
   public $automatica;
   public $editable;
   public $nogenerarasiento;

   public function __construct($f=FALSE)
   {
      parent::__construct('facturascli');
      if($f)
      {
         $this->idfactura = $this->intval($f['idfactura']);
         $this->idasiento = $this->intval($f['idasiento']);
         $this->idpagodevol = $this->intval($f['idpagodevol']);
         $this->idfacturarect = $this->intval($f['idfacturarect']);
         $this->codigo = $f['codigo'];
         $this->numero = $f['numero'];
         $this->codigorect = $f['codigorect'];
         $this->codejercicio = $f['codejercicio'];
         $this->codserie = $f['codserie'];
         $this->codalmacen = $f['codalmacen'];
         $this->codpago = $f['codpago'];
         $this->coddivisa = $f['coddivisa'];
         $this->fecha = Date('d-m-Y', strtotime($f['fecha']));
         $this->codcliente = $f['codcliente'];
         $this->nombrecliente = $f['nombrecliente'];
         $this->cifnif = $f['cifnif'];
         $this->direccion = $f['direccion'];
         $this->ciudad = $f['ciudad'];
         $this->provincia = $f['provincia'];
         $this->apartado = $f['apartado'];
         $this->coddir = $f['coddir'];
         $this->codpostal = $f['codpostal'];
         $this->codpais = $f['codpais'];
         $this->codagente = $f['codagente'];
         $this->neto = floatval($f['neto']);
         $this->totaliva = floatval($f['totaliva']);
         $this->total = floatval($f['total']);
         $this->totaleuros = floatval($f['totaleuros']);
         $this->irpf = floatval($f['irpf']);
         $this->totalirpf = floatval($f['totalirpf']);
         $this->porcomision = floatval($f['porcomision']);
         $this->tasaconv = floatval($f['tasaconv']);
         $this->recfinanciero = floatval($f['recfinanciero']);
         $this->totalrecargo = floatval($f['totalrecargo']);
         $this->observaciones = $f['observaciones'];
         $this->deabono = ($f['deabono'] == 't');
         $this->automatica = ($f['automatica'] == 't');
         $this->editable = ($f['editable'] == 't');
         $this->nogenerarasiento = ($f['nogenerarasiento'] == 't');
      }
      else
      {
         $this->idfactura = NULL;
         $this->idasiento = NULL;
         $this->idpagodevol = NULL;
         $this->idfacturarect = NULL;
         $this->codigo = NULL;
         $this->numero = NULL;
         $this->codigorect = NULL;
         $this->codejercicio = NULL;
         $this->codserie = NULL;
         $this->codalmacen = NULL;
         $this->codpago = NULL;
         $this->coddivisa = NULL;
         $this->fecha = Date('d-m-Y');
         $this->codcliente = NULL;
         $this->nombrecliente = NULL;
         $this->cifnif = NULL;
         $this->direccion = NULL;
         $this->provincia = NULL;
         $this->ciudad = NULL;
         $this->apartado = NULL;
         $this->coddir = NULL;
         $this->codpostal = NULL;
         $this->codpais = NULL;
         $this->codagente = NULL;
         $this->neto = 0;
         $this->totaliva = 0;
         $this->total = 0;
         $this->totaleuros = 0;
         $this->irpf = 0;
         $this->totalirpf = 0;
         $this->porcomision = 0;
         $this->tasaconv = 1;
         $this->recfinanciero = 0;
         $this->totalrecargo = 0;
         $this->observaciones = NULL;
         $this->deabono = FALSE;
         $this->automatica = FALSE;
         $this->editable = TRUE;
         $this->nogenerarasiento = FALSE;
      }
   }
   
   public function show_neto()
   {
      return number_format($this->neto, 2, '.', ' ');
   }
   
   public function show_iva()
   {
      return number_format($this->totaliva, 2, '.', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->totaleuros, 2, '.', ' ');
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
      if( is_null($this->idfactura) )
         return 'index.php?page=contabilidad_facturas_cli';
      else
         return 'index.php?page=contabilidad_factura_cli&id='.$this->idfactura;
   }
   
   public function asiento_url()
   {
      $asiento = new asiento();
      $asiento = $asiento->get($this->idasiento);
      if($asiento)
         return $asiento->url();
      else
         return '#';
   }
   
   public function agente_url()
   {
      $agente = new agente();
      $agente = $agente->get($this->codagente);
      if($agente)
         return $agente->url();
      else
         return '#';
   }
   
   public function cliente_url()
   {
      $cliente = new cliente();
      $cliente = $cliente->get($this->codcliente);
      if($cliente)
         return $cliente->url();
      else
         return '#';
   }
   
   public function get($id)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$id."';");
      if($fact)
         return new factura_cliente($fact[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = '".$cod."';");
      if($fact)
         return new factura_cliente($fact[0]);
      else
         return FALSE;
   }
   
   public function get_lineas()
   {
      $linea = new linea_factura_cliente();
      return $linea->all_from_factura($this->idfactura);
   }
   
   public function get_lineas_iva()
   {
      $linea_iva = new linea_iva_factura_cliente();
      $lineasi = $linea_iva->all_from_factura($this->idfactura);
      /// si no hay lineas de IVA las generamos
      if( !$lineasi )
      {
         $lineas = $this->get_lineas();
         if($lineas)
         {
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               foreach($lineasi as &$li)
               {
                  if($l->codimpuesto == $li->codimpuesto)
                  {
                     $encontrada = TRUE;
                     $li->neto += $l->pvptotal;
                     $li->totaliva += ($l->pvptotal*$l->iva)/100;
                     $li->totallinea = $li->neto + $li->totaliva;
                     break;
                  }
               }
               if( !$encontrada )
               {
                  $lineai = new linea_iva_factura_cliente();
                  $lineai->idfactura = $this->idfactura;
                  $lineai->codimpuesto = $l->codimpuesto;
                  $lineai->iva = $l->iva;
                  $lineai->neto = $l->pvptotal;
                  $lineai->totaliva = ($l->pvptotal*$l->iva)/100;
                  $lineai->totallinea = $lineai->neto + $lineai->totaliva;
                  $lineasi[] = $lineai;
               }
            }
            /// guardamos
            foreach($lineasi as $li)
               $li->save();
         }
      }
      return $lineasi;
   }
   
   public function get_agente()
   {
      $agente = new agente();
      return $agente->get($this->codagente);
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idfactura) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = '".$this->idfactura."';");
   }
   
   public function new_idfactura()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idfactura_seq');");
      if($newid)
         $this->idfactura = intval($newid[0]['nextval']);
   }
   
   public function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'nfacturacli');
      if($sec)
      {
         $this->numero = $sec->valorout;
         $sec->valorout++;
         $sec->save();
      }
      
      if(!$sec OR $this->numero <= 1)
      {
         $numero = $this->db->select("SELECT MAX(numero::integer) as num FROM ".$this->table_name."
            WHERE codejercicio = ".$this->var2str($this->codejercicio)." AND codserie = ".$this->var2str($this->codserie).";");
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
      
      $this->codigo = $this->codejercicio . sprintf('%02s', $this->codserie) . sprintf('%06s', $this->numero);
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idasiento = ".$this->var2str($this->idasiento).",
            idpagodevol = ".$this->var2str($this->idpagodevol).", idfacturarect = ".$this->var2str($this->idfacturarect).",
            codigo = ".$this->var2str($this->codigo).", numero = ".$this->var2str($this->numero).",
            codigorect = ".$this->var2str($this->codigorect).", codejercicio = ".$this->var2str($this->codejercicio).",
            codserie = ".$this->var2str($this->codserie).", codalmacen = ".$this->var2str($this->codalmacen).",
            codpago = ".$this->var2str($this->codpago).", coddivisa = ".$this->var2str($this->coddivisa).",
            fecha = ".$this->var2str($this->fecha).", codcliente = ".$this->var2str($this->codcliente).",
            nombrecliente = ".$this->var2str($this->nombrecliente).", cifnif = ".$this->var2str($this->cifnif).",
            direccion = ".$this->var2str($this->direccion).", ciudad = ".$this->var2str($this->ciudad).",
            provincia = ".$this->var2str($this->provincia).",
            apartado = ".$this->var2str($this->apartado).", coddir = ".$this->var2str($this->coddir).",
            codpostal = ".$this->var2str($this->codpostal).", codpais = ".$this->var2str($this->codpais).",
            codagente = ".$this->var2str($this->codagente).", neto = ".$this->var2str($this->neto).",
            totaliva = ".$this->var2str($this->totaliva).", total = ".$this->var2str($this->total).",
            totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
            totalirpf = ".$this->var2str($this->totalirpf).", porcomision = ".$this->var2str($this->porcomision).",
            tasaconv = ".$this->var2str($this->tasaconv).", recfinanciero = ".$this->var2str($this->recfinanciero).",
            totalrecargo = ".$this->var2str($this->totalrecargo).", observaciones = ".$this->var2str($this->observaciones).",
            deabono = ".$this->var2str($this->deabono).", automatica = ".$this->var2str($this->automatica).",
            editable = ".$this->var2str($this->editable).", nogenerarasiento = ".$this->var2str($this->nogenerarasiento)."
            WHERE idfactura = ".$this->var2str($this->idfactura).";";
      }
      else
      {
         $this->new_idfactura();
         $this->new_codigo();
         $sql = "INSERT INTO ".$this->table_name." (idfactura,idasiento,idpagodevol,idfacturarect,codigo,numero,
            codigorect,codejercicio,codserie,codalmacen,codpago,coddivisa,fecha,codcliente,nombrecliente,
            cifnif,direccion,ciudad,provincia,apartado,coddir,codpostal,codpais,codagente,neto,totaliva,total,totaleuros,
            irpf,totalirpf,porcomision,tasaconv,recfinanciero,totalrecargo,observaciones,deabono,automatica,editable,
            nogenerarasiento) VALUES (".$this->var2str($this->idfactura).",".$this->var2str($this->idasiento).",
            ".$this->var2str($this->idpagodevol).",".$this->var2str($this->idfacturarect).",".$this->var2str($this->codigo).",
            ".$this->var2str($this->numero).",".$this->var2str($this->codigorect).",".$this->var2str($this->codejercicio).",
            ".$this->var2str($this->codserie).",".$this->var2str($this->codalmacen).",".$this->var2str($this->codpago).",
            ".$this->var2str($this->coddivisa).",".$this->var2str($this->fecha).",".$this->var2str($this->codcliente).",
            ".$this->var2str($this->nombrecliente).",".$this->var2str($this->cifnif).",".$this->var2str($this->direccion).",
            ".$this->var2str($this->ciudad).",".$this->var2str($this->provincia).",".$this->var2str($this->apartado).",
            ".$this->var2str($this->coddir).",
            ".$this->var2str($this->codpostal).",".$this->var2str($this->codpais).",
            ".$this->var2str($this->codagente).",".$this->var2str($this->neto).",".$this->var2str($this->totaliva).",
            ".$this->var2str($this->total).",".$this->var2str($this->totaleuros).",".$this->var2str($this->irpf).",
            ".$this->var2str($this->totalirpf).",".$this->var2str($this->porcomision).",".$this->var2str($this->tasaconv).",
            ".$this->var2str($this->recfinanciero).",".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",
            ".$this->var2str($this->deabono).",".$this->var2str($this->automatica).",".$this->var2str($this->editable).",
            ".$this->var2str($this->nogenerarasiento).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      if( $this->idasiento )
      {
         $asiento = new asiento();
         $asiento = $asiento->get($this->idasiento);
         if($asiento)
            $asiento->delete();
      }
      /// desvinculamos el/los albaranes asociados
      $this->db->exec("UPDATE albaranescli SET idfactura = NULL, ptefactura = TRUE WHERE idfactura = '".$this->idfactura."';");
      /// eliminamos
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idfactura = '".$this->idfactura."';");
   }
   
   public function all($offset=0)
   {
      $faclist = array();
      $facturas = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC",
                                          FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_cliente($f);
      }
      return $faclist;
   }
   
   public function search($query, $offset=0)
   {
      $faclist = array();
      $query = strtolower( trim($query) );
      
      $consulta = "SELECT * FROM ".$this->table_name." WHERE ";
      if( is_numeric($query) )
         $consulta .= "codigo ~~ '%".$query."%' OR observaciones ~~ '%".$query."%'
            OR total BETWEEN ".($query-.01)." AND ".($query+.01);
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
         $consulta .= "fecha = '".$query."' OR observaciones ~~ '%".$query."%'";
      else
         $consulta .= "lower(codigo) ~~ '%".$query."%' OR lower(observaciones) ~~ '%".str_replace(' ', '%', $query)."%'";
      $consulta .= " ORDER BY fecha DESC";
      
      $facturas = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($facturas)
      {
         foreach($facturas as $f)
            $faclist[] = new factura_cliente($f);
      }
      return $faclist;
   }
}

?>
