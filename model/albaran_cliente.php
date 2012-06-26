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
require_once 'model/articulo.php';
require_once 'model/cliente.php';
require_once 'model/factura_cliente.php';
require_once 'model/secuencia.php';

class linea_albaran_cliente extends fs_model
{
   public $idlinea;
   public $idalbaran;
   public $referencia;
   public $descripcion;
   public $cantidad;
   public $dtopor;
   public $dtolineal;
   public $codimpuesto;
   public $iva;
   public $pvptotal;
   public $pvpsindto;
   public $pvpunitario;

   public function __construct($l=FALSE)
   {
      parent::__construct('lineasalbaranescli');
      if($l)
      {
         $this->idlinea = intval($l['idlinea']);
         $this->idalbaran = intval($l['idalbaran']);
         $this->referencia = $l['referencia'];
         $this->descripcion = $l['descripcion'];
         $this->cantidad = floatval($l['cantidad']);
         $this->dtopor = floatval($l['dtopor']);
         $this->dtolineal = floatval($l['dtolineal']);
         $this->codimpuesto = $l['codimpuesto'];
         $this->iva = floatval($l['iva']);
         $this->pvptotal = floatval($l['pvptotal']);
         $this->pvpsindto = floatval($l['pvpsindto']);
         $this->pvpunitario = floatval($l['pvpunitario']);
      }
      else
      {
         $this->idlinea = NULL;
         $this->idalbaran = NULL;
         $this->referencia = '';
         $this->descripcion = '';
         $this->cantidad = 0;
         $this->dtopor = 0;
         $this->dtolineal = 0;
         $this->codimpuesto = NULL;
         $this->iva = 0;
         $this->pvptotal = 0;
         $this->pvpsindto = 0;
         $this->pvpunitario = 0;
      }
   }
   
   public function show_pvp()
   {
      return number_format($this->pvpunitario, 2, ',', '.');
   }
   
   public function show_pvp_iva()
   {
      return number_format($this->pvpunitario + ($this->pvpunitario * $this->iva / 100), 2, ',', '.');
   }
   
   public function show_total()
   {
      return number_format($this->pvptotal, 2, ',', '.');
   }
   
   public function url()
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
         $sql = "UPDATE ".$this->table_name." SET idalbaran = ".$this->var2str($this->idalbaran).",
            referencia = ".$this->var2str($this->referencia).", descripcion = ".$this->var2str($this->descripcion).",
            cantidad = ".$this->var2str($this->cantidad).", dtopor = ".$this->var2str($this->dtopor).",
            dtolineal = ".$this->var2str($this->dtolineal).", codimpuesto = ".$this->var2str($this->codimpuesto).",
            iva = ".$this->var2str($this->iva).", pvptotal = ".$this->var2str($this->pvptotal).",
            pvpsindto = ".$this->var2str($this->pvpsindto).", pvpunitario = ".$this->var2str($this->pvpunitario)."
            WHERE idlinea = '".$this->idlinea."';";
      }
      else
      {
         $this->new_idlinea();
         $sql = "INSERT INTO ".$this->table_name." (idlinea,idalbaran,referencia,descripcion,cantidad,dtopor,dtolineal,
            codimpuesto,iva,pvptotal,pvpsindto,pvpunitario) VALUES (".$this->var2str($this->idlinea).",".$this->var2str($this->idalbaran).",
            ".$this->var2str($this->referencia).",".$this->var2str($this->descripcion).",".$this->var2str($this->cantidad).",
            ".$this->var2str($this->dtopor).",".$this->var2str($this->dtolineal).",".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->iva).",".$this->var2str($this->pvptotal).",".$this->var2str($this->pvpsindto).",
            ".$this->var2str($this->pvpunitario).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
   }
   
   public function all_from_albaran($id)
   {
      $linealist = array();
      $lineas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$id."';");
      if($lineas)
      {
         foreach($lineas as $l)
            $linealist[] = new linea_albaran_cliente($l);
      }
      return $linealist;
   }
   
   public function all_from_articulo($ref, $offset=0)
   {
      $linealist = array();
      $lineas = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE referencia = '".$ref."' ORDER BY idalbaran DESC",
              FS_ITEM_LIMIT, $offset);
      if( $lineas )
      {
         foreach($lineas as $l)
            $linealist[] = new linea_albaran_cliente($l);
      }
      return $linealist;
   }
   
   public function count_by_articulo()
   {
      $num = 0;
      $lineas = $this->db->select("SELECT COUNT(DISTINCT referencia) as total FROM ".$this->table_name.";");
      if($lineas)
         $num = intval($lineas[0]['total']);
      return $num;
   }
   
   public function top_by_articulo()
   {
      $toplist = array();
      $articulo = new articulo();
      $lineas = $this->db->select_limit("SELECT referencia, SUM(cantidad) as ventas FROM ".$this->table_name."
                                         GROUP BY referencia ORDER BY ventas DESC", FS_ITEM_LIMIT, 0);
      if($lineas)
      {
         foreach($lineas as $l)
            $toplist[] = array($articulo->get($l['referencia']), intval($l['ventas']));
      }
      return $toplist;
   }
}


class albaran_cliente extends fs_model
{
   public $idalbaran;
   public $idfactura;
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
   public $ptefactura;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('albaranescli');
      if($a)
      {
         $this->idalbaran = intval($a['idalbaran']);
         $this->idfactura = intval($a['idfactura']);
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
         $this->observaciones = $a['observaciones'];
         $this->ptefactura = ($a['ptefactura'] == 't');
      }
      else
      {
         $this->idalbaran = NULL;
         $this->idfactura = NULL;
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
         $this->ptefactura = TRUE;
      }
   }
   
   public function show_neto()
   {
      return number_format($this->neto, 2, ',', ' ');
   }
   
   public function show_iva()
   {
      return number_format($this->totaliva, 2, ',', ' ');
   }
   
   public function show_total()
   {
      return number_format($this->totaleuros, 2, ',', ' ');
   }
   
   public function show_fecha()
   {
      return $this->fecha;
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
      if( is_null($this->idalbaran) )
         return 'index.php?page=general_albaranes_cli';
      else
         return 'index.php?page=general_albaran_cli&id='.$this->idalbaran;
   }
   
   public function factura_url()
   {
      if( !$this->ptefactura )
      {
         $fac = new factura_cliente();
         $fac = $fac->get($this->idfactura);
         if($fac)
            return $fac->url();
         else
            return $this->url();
      }
      else
         return $this->url();
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
      $albaran = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$id."';");
      if($albaran)
         return new albaran_cliente($albaran[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $albaran = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = '".strtoupper($cod)."';");
      if($albaran)
         return new albaran_cliente($albaran[0]);
      else
         return FALSE;
   }
   
   public function get_lineas()
   {
      $linea = new linea_albaran_cliente();
      return $linea->all_from_albaran($this->idalbaran);
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
      if( is_null($this->idalbaran) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$this->idalbaran."';");
   }
   
   public function new_idalbaran()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idalbaran_seq');");
      if($newid)
         $this->idalbaran = intval($newid[0]['nextval']);
   }
   
   public function new_codigo()
   {
      $sec = new secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'nalbarancli');
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
         $sql = "UPDATE ".$this->table_name." SET idfactura = ".$this->var2str($this->idfactura).",
            codigo = ".$this->var2str($this->codigo).", codagente = ".$this->var2str($this->codagente).",
            codserie = ".$this->var2str($this->codserie).", codejercicio = ".$this->var2str($this->codejercicio).",
            codcliente = ".$this->var2str($this->codcliente).", codpago = ".$this->var2str($this->codpago).",
            coddivisa = ".$this->var2str($this->coddivisa).", codalmacen = ".$this->var2str($this->codalmacen).",
            codpais = ".$this->var2str($this->codpais).", coddir = ".$this->var2str($this->coddir).",
            codpostal = ".$this->var2str($this->codpostal).", numero = ".$this->var2str($this->numero).",
            numero2 = ".$this->var2str($this->numero2).", nombrecliente = ".$this->var2str($this->nombrecliente).",
            cifnif = ".$this->var2str($this->cifnif).", direccion = ".$this->var2str($this->direccion).",
            ciudad = ".$this->var2str($this->ciudad).", provincia = ".$this->var2str($this->provincia).",
            apartado = ".$this->var2str($this->apartado).",
            fecha = ".$this->var2str($this->fecha).", hora = ".$this->var2str($this->hora).", neto = ".$this->var2str($this->neto).",
            total = ".$this->var2str($this->total).", totaliva = ".$this->var2str($this->totaliva).",
            totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
            totalirpf = ".$this->var2str($this->totalirpf).", porcomision = ".$this->var2str($this->porcomision).",
            tasaconv = ".$this->var2str($this->tasaconv).", recfinanciero = ".$this->var2str($this->recfinanciero).",
            totalrecargo = ".$this->var2str($this->totalrecargo).", observaciones = ".$this->var2str($this->observaciones).",
            ptefactura = ".$this->var2str($this->ptefactura)."
            WHERE idalbaran = '".$this->idalbaran."';";
      }
      else
      {
         $this->new_idalbaran();
         $this->new_codigo();
         $sql = "INSERT INTO ".$this->table_name." (idalbaran,idfactura,codigo,codagente,codserie,codejercicio,codcliente,
            codpago,coddivisa,codalmacen,codpais,coddir,codpostal,numero,numero2,nombrecliente,cifnif,direccion,ciudad,provincia,apartado,
            fecha,hora,neto,total,totaliva,totaleuros,irpf,totalirpf,porcomision,tasaconv,recfinanciero,totalrecargo,observaciones,
            ptefactura) VALUES (".$this->idalbaran.",".$this->var2str($this->idfactura).",".$this->var2str($this->codigo).",
            ".$this->var2str($this->codagente).",".$this->var2str($this->codserie).",".$this->var2str($this->codejercicio).",
            ".$this->var2str($this->codcliente).",".$this->var2str($this->codpago).",".$this->var2str($this->coddivisa).",
            ".$this->var2str($this->codalmacen).",".$this->var2str($this->codpais).",".$this->var2str($this->coddir).",
            ".$this->var2str($this->codpostal).",".$this->var2str($this->numero).",".$this->var2str($this->numero2).",
            ".$this->var2str($this->nombrecliente).",".$this->var2str($this->cifnif).",".$this->var2str($this->direccion).",
            ".$this->var2str($this->ciudad).",".$this->var2str($this->provincia).",".$this->var2str($this->apartado).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->hora).",
            ".$this->var2str($this->neto).",".$this->var2str($this->total).",".$this->var2str($this->totaliva).",
            ".$this->var2str($this->totaleuros).",".$this->var2str($this->irpf).",".$this->var2str($this->totalirpf).",
            ".$this->var2str($this->porcomision).",".$this->var2str($this->tasaconv).",".$this->var2str($this->recfinanciero).",
            ".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",".$this->var2str($this->ptefactura).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      if(!$this->ptefactura AND (is_null($this->idfactura) OR $this->idfactura == 0))
         return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idalbaran = '".$this->idalbaran."';");
      else
      {
         $this->new_error_msg("Este albarán está vinculado a una factura.");
         return FALSE;
      }
   }

   public function all($offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY idalbaran DESC",
                                           FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $albalist[] = new albaran_cliente($a);
      }
      return $albalist;
   }
   
   public function all_from_day($offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT fecha,codagente,COUNT(idalbaran) as num,AVG(totaleuros) as media,SUM(totaleuros) as total
         FROM ".$this->table_name." GROUP BY fecha,codagente ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         $agente = new agente();
         foreach($albaranes as $a)
         {
            $albalist[] = array(
                           'fecha' => Date('j-n-Y', strtotime($a['fecha'])),
                           'agente' => $agente->get($a['codagente']),
                           'albaranes' => number_format($a['num'], 0, ',', '.'),
                           'media' => number_format($a['media'], 2, ',', '.'),
                           'total' => number_format($a['total'], 2, ',', '.')
                        );
         }
      }
      return $albalist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codcliente = '".$codcliente."' ORDER BY fecha DESC",
              FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $albalist[] = new albaran_cliente($a);
      }
      return $albalist;
   }
   
   public function search($query, $offset=0)
   {
      $alblist = array();
      $query = strtolower($query);
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codigo ~~ '%".$query."%'
         OR lower(observaciones) ~~ '%".$query."%' ORDER BY fecha DESC", FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
            $alblist[] = new albaran_cliente($a);
      }
      return $alblist;
   }
}

?>
