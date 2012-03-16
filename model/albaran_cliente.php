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
   
   public function show_total()
   {
      return number_format($this->pvptotal, 2, ',', '.');
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( isset($this->idlinea) )
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idlinea = '".$this->idlinea."';");
      else
         return FALSE;
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
         {
            $linealist[] = new linea_albaran_cliente($l);
         }
      }
      return $linealist;
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
   public $apartado;
   public $fecha;
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
   public $revisado;
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
         $this->apartado = $a['apartado'];
         $this->fecha = $a['fecha'];
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
         $this->revisado = ($a['revisado'] == 't');
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
         $this->numero = '';
         $this->numero2 = '';
         $this->nombrecliente = '';
         $this->cifnif = '';
         $this->direccion = '';
         $this->ciudad = '';
         $this->apartado = '';
         $this->fecha = Date('j-n-Y');
         $this->neto = 0;
         $this->total = 0;
         $this->totaliva = 0;
         $this->totaleuros = 0;
         $this->irpf = 0;
         $this->totalirpf = 0;
         $this->porcomision = 0;
         $this->tasaconv = 0;
         $this->recfinanciero = 0;
         $this->totalrecargo = 0;
         $this->observaciones = '';
         $this->revisado = FALSE;
         $this->ptefactura = TRUE;
      }
   }
   
   public function show_total()
   {
      return number_format($this->totaleuros, 2, ',', '.');
   }
   
   public function show_fecha()
   {
      return Date('j-n-Y', strtotime($this->fecha));
   }
   
   public function url()
   {
      return 'index.php?page=general_albaran_cli&id='.$this->idalbaran;
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( isset($this->idalbaran) )
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$this->idalbaran."';");
      else
         return FALSE;
   }
   
   public function new_idalbaran()
   {
      $newid = $this->db->select("SELECT nextval('".$this->table_name."_idalbaran_seq');");
      if($newid)
         $this->idalbaran = intval($newid[0]['nextval']);
   }
   
   public function new_codigo()
   {
      $numero = $this->db->select("SELECT MAX(numero) as num FROM ".$this->table_name."
         WHERE codejercicio = ".$this->var2str($this->codejercicio)." AND codserie = ".$this->var2str($this->codserie).";");
      if($numero)
      {
         $this->numero = sprintf('%06s', (1 + intval($numero[0]['num'])));
      }
      else
         $this->numero = '000001';
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
            ciudad = ".$this->var2str($this->ciudad).", apartado = ".$this->var2str($this->apartado).",
            fecha = ".$this->var2str($this->fecha).", neto = ".$this->var2str($this->neto).",
            total = ".$this->var2str($this->total).", totaliva = ".$this->var2str($this->totaliva).",
            totaleuros = ".$this->var2str($this->totaleuros).", irpf = ".$this->var2str($this->irpf).",
            totalirpf = ".$this->var2str($this->totalirpf).", porcomision = ".$this->var2str($this->porcomision).",
            tasaconv = ".$this->var2str($this->tasaconv).", recfinanciero = ".$this->var2str($this->recfinanciero).",
            totalrecargo = ".$this->var2str($this->totalrecargo).", observaciones = ".$this->var2str($this->observaciones).",
            revisado = ".$this->var2str($this->revisado).", ptefactura = ".$this->var2str($this->ptefactura)."
            WHERE idalbaran = '".$this->idalbaran."';";
      }
      else
      {
         $this->new_idalbaran();
         $this->new_codigo();
         $sql = "INSERT INTO ".$this->table_name." (idalbaran,idfactura,codigo,codagente,codserie,codejercicio,codcliente,
            codpago,coddivisa,codalmacen,codpais,coddir,codpostal,numero,numero2,nombrecliente,cifnif,direccion,ciudad,apartado,
            fecha,neto,total,totaliva,totaleuros,irpf,totalirpf,porcomision,tasaconv,recfinanciero,totalrecargo,observaciones,
            revisado,ptefactura) VALUES (".$this->idalbaran.",".$this->var2str($this->idfactura).",".$this->var2str($this->codigo).",
            ".$this->var2str($this->codagente).",".$this->var2str($this->codserie).",".$this->var2str($this->codejercicio).",
            ".$this->var2str($this->codcliente).",".$this->var2str($this->codpago).",".$this->var2str($this->coddivisa).",
            ".$this->var2str($this->codalmacen).",".$this->var2str($this->codpais).",".$this->var2str($this->coddir).",
            ".$this->var2str($this->codpostal).",".$this->var2str($this->numero).",".$this->var2str($this->numero2).",
            ".$this->var2str($this->nombrecliente).",".$this->var2str($this->cifnif).",".$this->var2str($this->direccion).",
            ".$this->var2str($this->ciudad).",".$this->var2str($this->apartado).",".$this->var2str($this->fecha).",
            ".$this->var2str($this->neto).",".$this->var2str($this->total).",".$this->var2str($this->totaliva).",
            ".$this->var2str($this->totaleuros).",".$this->var2str($this->irpf).",".$this->var2str($this->totalirpf).",
            ".$this->var2str($this->porcomision).",".$this->var2str($this->tasaconv).",".$this->var2str($this->recfinanciero).",
            ".$this->var2str($this->totalrecargo).",".$this->var2str($this->observaciones).",".$this->var2str($this->revisado).",
            ".$this->var2str($this->ptefactura).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idalbaran = '".$this->idalbaran."';");
   }
   
   public function get($id)
   {
      $albaran = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idalbaran = '".$id."';");
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

   public function all($offset=0)
   {
      $albalist = array();
      $albaranes = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY idalbaran DESC",
                                           FS_ITEM_LIMIT, $offset);
      if($albaranes)
      {
         foreach($albaranes as $a)
         {
            $albalist[] = new albaran_cliente($a);
         }
      }
      return $albalist;
   }
}

?>
