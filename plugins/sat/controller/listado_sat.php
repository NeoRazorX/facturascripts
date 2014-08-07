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

require_model('cliente.php');
require_model('registro_sat.php');

class listado_sat extends fs_controller
{
   public $cliente;
   public $registro_sat;
   public $resultado;
   public $estado;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'SAT', 'ventas', FALSE, TRUE);
      
      /// cualquier cosa que pongas aquí se ejecutará DESPUÉS de process()
   }
   
   /**
    * esta función se ejecuta si el usuario ha hecho login,
    * a efectos prácticos, este es el constructor
    */
   protected function process()
   {
      $this->cliente = new cliente();
      $this->registro_sat = new registro_sat();
      
      if( isset($_GET['id']) )
      {
        if(isset($_GET['opcion']))
        {
        if($_GET['opcion']=="imprimir")
        {
            $this->resultado=$this->registro_sat->get($_GET['id']);
             $this->template = "imprimir";
        }

        }
        else
        {
            $this->template = "edita";
          $this->page->title = "Edita SAT: ".$_GET['id'];
     
         $this->edita_sat();
        }
      }
      else if( isset($_GET['opcion']) )
      {
         
         if($_GET['opcion'] == "nuevosat")
         {
            $this->page->title = "Nuevo SAT";
            
            if( isset($_GET['codcliente']) )
            {
               if( isset($_POST['modelo']) )
               {
                  $this->template = "edita";
                  
                  
                  $nsat = $this->agrega_sat();
                  $this->page->title = "Edita SAT: ".$nsat;
                  $this->resultado = $this->registro_sat->get($nsat);
               }
               else
               {
                  $this->template = "agregasat";
                  $this->resultado = $this->cliente->get($_GET['codcliente']);
               }
            }
            else
            {
               $this->nuevo_cliente();
            }
         }
         

      }
      else
      {
         $this->custom_search = TRUE;
         $this->buttons[] = new fs_button('b_nuevo_sat', 'Nuevo', $this->url().'&opcion=nuevosat');
         $this->template = "sat";
      }
   }
   
   public function nuevo_cliente()
   {
      //----------------------------------------------
      // muestra una vista para seleccion de clientes
      //----------------------------------------------
      $this->template = "satlistaclientes";
      $this->buttons[] = new fs_button('b_nuevo_cliente', 'Nuevo Cliente');
      
      if( isset($_POST['nombre']) )
      {
         $cliente = new cliente();
         $cliente->codcliente = $cliente->get_new_codigo();
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->telefono1 = $_POST['telefono1'];
         $cliente->telefono2 = $_POST['telefono2'];
         
         if( $cliente->save() )
            $this->new_message('Cliente modificado correctamente.');
         else
            $this->new_error_msg('Error al modificar los datos del cliente.');
      }
   }
   
   public function agrega_sat()
   {
      $cliente = $this->cliente->get($_GET['codcliente']);
      if($cliente)
      {
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->telefono1 = $_POST['telefono1'];
         $cliente->telefono2 = $_POST['telefono2'];
         
         if( $cliente->save() )
            $this->new_message('Cliente modificado correctamente.');
         else
            $this->new_error_msg('Error al guardar los datos del cliente.');
         
         $this->registro_sat->codcliente = $_GET['codcliente'];
         $this->registro_sat->modelo = $_POST['modelo'];
         $this->registro_sat->fcomienzo = $_POST['fcomienzo'];
         
         if($_POST['ffin'] != '')
            $this->registro_sat->ffin = $_POST['ffin'];
         
         $this->registro_sat->averia = $_POST['averia'];
         $this->registro_sat->accesorios = $_POST['accesorios'];
         $this->registro_sat->observaciones = $_POST['observaciones'];
         $this->registro_sat->prioridad = $_POST['prioridad'];
         
         if( $this->registro_sat->save() )
         {
            $this->new_message('Datos del SAT guardados correctamente.');
            return $this->registro_sat->nsat;
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos del SAT.');
            return FALSE;
         }
      }
      else
      {
         $this->new_error_msg('CLiente no encontrado.');
         return FALSE;
      }
   }
   
   public function edita_sat()
   {
      $this->resultado = $this->registro_sat->get($_GET['id']);
      if($this->resultado AND isset($_POST['modelo']))
      {
         $cliente = $this->cliente->get($this->resultado->codcliente);
         if($cliente AND isset($_POST['nombre']))
         {
            $cliente->nombre = $_POST['nombre'];
            $cliente->nombrecomercial = $_POST['nombre'];
            $cliente->telefono1 = $_POST['telefono1'];
            $cliente->telefono2 = $_POST['telefono2'];
            
            if( $cliente->save() )
               $this->new_message('Cliente modificado correctamente.');
            else
               $this->new_error_msg('Error al guardar los datos del cliente.');
         }
         
         $this->resultado->modelo = $_POST['modelo'];
         $this->resultado->fcomienzo = $_POST['fcomienzo'];
         
         if($_POST['ffin'] != '')
            $this->resultado->ffin = $_POST['ffin'];
         
         $this->resultado->averia = $_POST['averia'];
         $this->resultado->accesorios = $_POST['accesorios'];
         $this->resultado->observaciones = $_POST['observaciones'];
         $this->resultado->posicion = $_POST['posicion'];
         $this->resultado->estado = $_POST['estado'];
         $this->resultado->prioridad = $_POST['prioridad'];
         if( $this->resultado->save() )
         {
            $this->new_message('Datos del SAT guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos del SAT.');
         }
      }
      else if(!$this->resultado)
      {
         $this->new_error_msg('Datos no encontrados.');
      }
   }
   
   public function listar_sat()
   {
      if( isset($_POST['query']) )
      {
         return $this->registro_sat->search($_POST['query']);
      }
      else if( isset($_POST['desde']) )
      {
         return $this->registro_sat->search('', $_POST['desde'], $_POST['hasta'], $_POST['estado']);
      }
      else
      {
         return $this->registro_sat->all();
      }
   }
   
   public function listar_estados()
   {
      $estados = array();
      
      /**
       * En registro_sat::estados() nos devuelve un array con todos los estados,
       * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
       */
      foreach($this->registro_sat->estados() as $i => $value)
         $estados[] = array('id_estado' => $i, 'nombre_estado' => $value);
      
      return $estados;
   }
   
   public function listar_prioridad()
   {
      $prioridad = array();
      
      /**
       * En registro_sat::prioridad() nos devuelve un array con todos los prioridades,
       * pero como queremos también el id, pues hay que hacer este bucle para sacarlos.
       */
      foreach($this->registro_sat->prioridad() as $i => $value)
         $prioridad[] = array('id_prioridad' => $i, 'nombre_prioridad' => $value);
      
      return $prioridad;
   }
}
