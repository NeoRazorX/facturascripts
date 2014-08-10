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
require_model('linea_presupuesto_cliente.php');

class presupuesto_cliente extends fs_model
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
   public $codpais;
   public $codpostal;
   public $codserie;
   public $direccion;
   public $editable;
   public $fecha;
   public $idpresupuesto;
   public $irpf;
   public $neto;
   public $nombrecliente;
   public $numero;
   public $observaciones;
   public $porcomision;
   public $provincia;
   public $recfinanciero;
   public $tasaconv;
   public $total;
   public $totaleuros;
   public $totalirpf;
   public $totaliva;
   public $totalrecargo;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('presupuestoscli', 'plugins/presupuestos_y_pedidos/');
      
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
         $this->codpais = $p['codpais'];
         $this->codpostal = $p['codpostal'];
         $this->codserie = $p['codserie'];
         $this->direccion = $p['direccion'];
         $this->editable = $this->str2bool($p['editable']);
         $this->fecha = Date('d-m-Y', strtotime($p['fecha']));
         $this->idpresupuesto = intval($p['idpresupuesto']);
         $this->irpf = floatval($p['irpf']);
         $this->neto = floatval($p['neto']);
         $this->nombrecliente = $p['nombrecliente'];
         $this->numero = $p['numero'];
         $this->observaciones = $p['observaciones'];
         $this->porcomision = floatval($p['porcomision']);
         $this->provincia = $p['provincia'];
         $this->recfinanciero = floatval($p['recfinanciero']);
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
         $this->codpais = NULL;
         $this->codpostal = NULL;
         $this->codserie = NULL;
         $this->direccion = NULL;
         $this->editable = TRUE;
         $this->fecha = Date('d-m-Y');
         $this->idpresupuesto = NULL;
         $this->irpf = 0;
         $this->neto = 0;
         $this->nombrecliente = NULL;
         $this->numero = NULL;
         $this->observaciones = '';
         $this->porcomision = 0;
         $this->provincia = NULL;
         $this->recfinanciero = 0;
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
      if( is_null($this->idpresupuesto) )
         return 'index.php?page=ventas_presupuestos';
      else
         return 'index.php?page=ventas_presupuesto&id='.$this->idpresupuesto;
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($id).";");
      if($data)
         return new presupuesto_cliente($data[0]);
      else
         return FALSE;
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
   
   public function exists()
   {
      if( is_null($this->idpresupuesto) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";");
   }
   
   public function test()
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
               codigo = ".$this->var2str($this->codigo).",
               codpais = ".$this->var2str($this->codpais).", codpostal = ".$this->var2str($this->codpostal).",
               codserie = ".$this->var2str($this->codserie).", direccion = ".$this->var2str($this->direccion).",
               editable = ".$this->var2str($this->editable).", fecha = ".$this->var2str($this->fecha).",
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
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codalmacen,codcliente,coddir,
               coddivisa,codejercicio,codigo,codpais,codpostal,codserie,direccion,editable,fecha,irpf,neto,
               nombrecliente,numero,observaciones,porcomision,provincia,recfinanciero,tasaconv,total,totaleuros,totalirpf,
               totaliva,totalrecargo) VALUES (".$this->var2str($this->apartado).",".$this->var2str($this->cifnif).",
               ".$this->var2str($this->ciudad).",".$this->var2str($this->codagente).",".$this->var2str($this->codalmacen).",
               ".$this->var2str($this->codcliente).",
               ".$this->var2str($this->coddir).",".$this->var2str($this->coddivisa).",".$this->var2str($this->codejercicio).",
               ".$this->var2str($this->codigo).",".$this->var2str($this->codpais).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->codserie).",".$this->var2str($this->direccion).",
               ".$this->var2str($this->editable).",".$this->var2str($this->fecha).",".$this->var2str($this->irpf).",
               ".$this->var2str($this->neto).",".$this->var2str($this->nombrecliente).",".$this->var2str($this->numero).",
               ".$this->var2str($this->observaciones).",".$this->var2str($this->porcomision).",".$this->var2str($this->provincia).",
               ".$this->var2str($this->recfinanciero).",".$this->var2str($this->tasaconv).",".$this->var2str($this->total).",
               ".$this->var2str($this->totaleuros).",".$this->var2str($this->totalirpf).",".$this->var2str($this->totaliva).",
               ".$this->var2str($this->totalrecargo).");";
            
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
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpresupuesto = ".$this->var2str($this->idpresupuesto).";");
   }
   
   public function all()
   {
      $plist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY fecha DESC, codigo DESC;");
      if($data)
      {
         foreach($data as $d)
            $plist[] = new presupuesto_cliente($d);
      }
      
      return $plist;
   }
}