<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez         neorazorx@gmail.com
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

class registro_sat extends fs_model
{
   public $nsat;
   public $prioridad;
   public $fentrada;
   public $fcomienzo;
   public $ffin;
   public $modelo;
   public $codcliente;
   public $estado;
   public $averia;
   public $accesorios;
   public $observaciones;
   public $posicion;
   
   /// Estos datos los usas, pero no los guardas en la base de datos
   public $nombre_cliente;
   public $telefono1_cliente;
   public $telefono2_cliente;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('registros_sat', 'plugins/sat/');
      
      if($s)
      {
         $this->nsat = intval($s['nsat']);
         $this->prioridad = intval($s['prioridad']);
         $this->fentrada = date('d-m-Y', strtotime($s['fentrada']));
         
         $this->fcomienzo = NULL;
         if( isset($s['fcomienzo']) )
            $this->fcomienzo = date('d-m-Y', strtotime($s['fcomienzo']));
         
         $this->ffin = NULL;
         if( isset($s['ffin']) )
            $this->ffin = date('d-m-Y', strtotime($s['ffin']));
         
         $this->modelo = $s['modelo'];
         $this->codcliente = $s['codcliente'];
         $this->estado = intval($s['estado']);
         $this->prioridad = intval($s['prioridad']);
         $this->averia = $s['averia'];
         $this->accesorios = $s['accesorios'];
         $this->observaciones = $s['observaciones'];
         $this->posicion = $s['posicion'];
         $this->nombre_cliente = $s['nombre'];
         $this->telefono1_cliente = $s['telefono1'];
         $this->telefono2_cliente = $s['telefono2'];
      }
      else
      {
         $this->nsat = NULL;
         $this->prioridad = 3;
         $this->fentrada = date('d-m-Y');
         $this->fcomienzo = date('d-m-Y');
         $this->ffin = NULL;
         $this->modelo = '';
         $this->codcliente = NULL;
         $this->estado = 1;
         $this->averia = '';
         $this->accesorios = '';
         $this->observaciones = '';
         $this->posicion = '';
         
         $this->nombre_cliente = '';
         $this->telefono1_cliente = '';
         $this->telefono2_cliente = '';
      }
   }
   
   public function install()
   {
      return '';
   }
   
   public function estados()
   {
      $estados = array(
          1 => 'Trabajo por empezar',
          2 => 'Trabajo empezado',
          3 => 'Aceptado Pendiente de Pieza',
          4 => 'Aceptado pendiente empezar',
          5 => 'Terminado pendiente de recoger',
          6 => 'Terminado y recogido'
      );
      
      return $estados;
   }
   
    public function prioridad()
   {
      $prioridad = array(
          1 => 'Urgente',
          2 => 'Prioridad alta',
          3 => 'Prioridad media',
          4 => 'Prioridad baja',
      );
      
      return $prioridad;
   }
   
   
   public function nombre_estado()
   {
      $estados = $this->estados();
      return $estados[$this->estado];
   }
   
   public function nombre_estado_param($estado)
   {
      $estados = $this->estados();
      return $estados[$estado];
   }
   
   public function nombre_prioridad()
   {
      $prioridades = $this->prioridad();
      return $prioridades[$this->prioridad];
   }
   
   
   public function url()
   {
      if( is_null($this->nsat) )
      {
         return 'index.php?page=listado_sat';
      }
      else
      {
         return 'index.php?page=listado_sat&id='.$this->nsat;
      }
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=ventas_clientes";
      else
         return "index.php?page=ventas_cliente&cod=".$this->codcliente;
   }
   
   public function get($id)
   {
      $sql = "SELECT registros_sat.nsat, registros_sat.prioridad,registros_sat.fentrada, registros_sat.fcomienzo, registros_sat.ffin,
         registros_sat.modelo, registros_sat.codcliente, clientes.nombre, clientes.telefono1, clientes.telefono2,
         registros_sat.estado, registros_sat.averia, registros_sat.accesorios, registros_sat.observaciones, registros_sat.posicion
         FROM registros_sat, clientes
         WHERE registros_sat.codcliente = clientes.codcliente AND nsat = ".$this->var2str($id).";";
      $data = $this->db->select($sql);
      if($data)
         return new registro_sat($data[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->nsat) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM registros_sat WHERE nsat = ".$this->var2str($this->nsat).";");
      }
   }
   
   public function test()
   {
      $this->modelo = $this->no_html($this->modelo);
      $this->averia = $this->no_html($this->averia);
      $this->accesorios = $this->no_html($this->accesorios);
      $this->observaciones = $this->no_html($this->observaciones);
      $this->posicion = $this->no_html($this->posicion);
      
      /// realmente no quería comprobar nada, simplemente eliminar el html de las variables
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE registros_sat SET prioridad = ".$this->var2str($this->prioridad).",
               fcomienzo = ".$this->var2str($this->fcomienzo).", ffin = ".$this->var2str($this->ffin).",
               modelo = ".$this->var2str($this->modelo).", codcliente = ".$this->var2str($this->codcliente).",
               estado = ".$this->var2str($this->estado).", averia = ".$this->var2str($this->averia).",
               accesorios = ".$this->var2str($this->accesorios).", observaciones = ".$this->var2str($this->observaciones).",
               posicion = ".$this->var2str($this->posicion)." WHERE nsat = ".$this->var2str($this->nsat).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO registros_sat (prioridad,fentrada,fcomienzo,ffin,modelo,codcliente,estado,
               averia,accesorios,observaciones) VALUES (".$this->var2str($this->prioridad).",
               ".$this->var2str($this->fentrada).",".$this->var2str($this->fcomienzo).",".$this->var2str($this->ffin).",
               ".$this->var2str($this->modelo).",".$this->var2str($this->codcliente).",
               ".$this->var2str($this->estado).",".$this->var2str($this->averia).",
               ".$this->var2str($this->accesorios).",".$this->var2str($this->observaciones).");";
            
            if( $this->db->exec($sql) )
            {
               $this->nsat = $this->db->lastval();
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
      
   }
   
   public function all()
   {
      $satlist = array();
      
      $sql = "SELECT registros_sat.nsat, registros_sat.prioridad,registros_sat.fentrada, registros_sat.fcomienzo, registros_sat.ffin,
         registros_sat.modelo, registros_sat.codcliente, clientes.nombre, clientes.telefono1, clientes.telefono2,
         registros_sat.estado, registros_sat.averia, registros_sat.accesorios, registros_sat.observaciones, registros_sat.posicion
         FROM registros_sat, clientes
         WHERE registros_sat.codcliente = clientes.codcliente AND registros_sat.estado != 6 ORDER BY fcomienzo ASC, prioridad ASC,ffin ASC, fentrada ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $satlist[] = new registro_sat($d);
      }
      
      return $satlist;
   }
   
   
   public function all_from_cliente($cod)
   {
      $satlist = array();
      
      $sql = "SELECT registros_sat.nsat, registros_sat.prioridad,registros_sat.fentrada, registros_sat.fcomienzo, registros_sat.ffin,
         registros_sat.modelo, registros_sat.codcliente, clientes.nombre, clientes.telefono1, clientes.telefono2,
         registros_sat.estado, registros_sat.averia, registros_sat.accesorios, registros_sat.observaciones, registros_sat.posicion
         FROM registros_sat, clientes
         WHERE registros_sat.codcliente = clientes.codcliente AND registros_sat.estado != 6 AND registros_sat.codcliente = ".$this->var2str($cod)."
         ORDER BY fcomienzo ASC, prioridad ASC,ffin ASC, fentrada ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $satlist[] = new registro_sat($d);
      }
      
      return $satlist;
   }
   
   public function search($buscar='', $desde='', $hasta='', $estado='activos',$orden="nsat")
   {
      $satlist = array();
      
      $sql = "SELECT registros_sat.nsat, registros_sat.prioridad,registros_sat.fentrada, registros_sat.fcomienzo, registros_sat.ffin,
         registros_sat.modelo, registros_sat.codcliente, clientes.nombre, clientes.telefono1, clientes.telefono2, registros_sat.estado,
         registros_sat.averia, registros_sat.accesorios, registros_sat.observaciones, registros_sat.posicion
         FROM registros_sat, clientes
         WHERE registros_sat.codcliente = clientes.codcliente";
      
      if($buscar != '')
      {
         $sql .= " AND ((lower(modelo) LIKE lower('%".$buscar."%')) OR (registros_sat.observaciones LIKE '%".$buscar."%')
            OR (lower(nombre) LIKE lower('%".$buscar."%')))";
      }
      
      if($desde != '')
      {
         $sql .= " AND fcomienzo >= ".$this->var2str($desde);
      }
      
      if($hasta != '')
      {
         $sql .= " AND fcomienzo <= ".$this->var2str($hasta);
      }
      
      if($estado != "todos" AND $estado != "activos")
      {
         $sql .= " AND registros_sat.estado = ".$estado;
      }
      else 
      {
          if($estado == "activos")
          {
              $sql .= " AND registros_sat.estado != 6";
          }
          //si no entra en ninguno de los 2 if anteriores muestra todos los estados.
      }
      $sql.= " ORDER BY ".$orden." ASC ";
      
      $data = $this->db->select($sql.";");
      if($data)
      {
         foreach($data as $d)
            $satlist[] = new registro_sat($d);
      }
      
      return $satlist;
   }
   
   public function num_detalles()
   {
      $sql = "SELECT count(*) as num FROM detalles_sat WHERE nsat = ".$this->var2str($this->nsat).";";
      $result = $this->db->select($sql);
      return $this->num_detalles = intval($result[0]['num']);
   }
}
