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
require_model('detalles_sat.php');
require_model('fs_extension.php');

class listado_sat extends fs_controller
{
   public $cliente;
   public $registro_sat;
   public $busqueda = array();
   public $detalles_sat;
   public $resultado;
   public $estado;
   public $num_detalles;
   
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
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      $this->meter_extensiones();
      
      $this->cliente = new cliente();
      $this->registro_sat = new registro_sat();
      $this->detalles_sat = new detalles_sat();
      
      if( isset($_REQUEST['buscar_cliente']) )
      {
         /// desactivamos la plantilla HTML
         $this->template = FALSE;
         
         $json = array();
         foreach($this->cliente->search($_REQUEST['buscar_cliente']) as $cli)
         {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
         }
         
         header('Content-Type: application/json');
         echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
      }
      else if( isset($_GET['id']) )
      {
         if(isset($_GET['opcion']))
         {
            if($_GET['opcion']=="imprimir")
            {
               $this->resultado = $this->registro_sat->get($_GET['id']);
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
               if( isset($_POST['modelo']) ) /// editar
               {
                  //si recibe el modelo se entra en editar
                  $cliente = $this->cliente->get($_GET['codcliente']);
                  $cliente->nombre = $_POST['nombre'];
                  $cliente->nombrecomercial = $_POST['nombre'];
                  $cliente->telefono1 = $_POST['telefono1'];
                  $cliente->telefono2 = $_POST['telefono2'];
                  
                  if( $cliente->save() )
                  {
                     $this->new_message('Cliente modificado correctamente.');
                  }
                  else
                     $this->new_error_msg('Error al guardar los datos del cliente.');
                  
                  $this->cliente = $cliente;
                  $nsat = $this->agrega_sat();
                  $this->page->title = "Edita SAT: ".$nsat;
                  $this->resultado = $this->registro_sat->get($nsat);
                  $this->template = "edita";
               }
               else /// nuevo
               {
                  /// nuevo sat con un cliente existente
                  $this->resultado = $this->cliente->get($_GET['codcliente']);
                  $this->template = "agregasat";
               }
            }
            else
            {
               $cliente_id = $this->nuevo_cliente();
               $cliente = $this->cliente->get($cliente_id);
               $this->cliente = $cliente;
               $this->resultado = $cliente;
               $this->template = "agregasat";
            }
         }
      }
      else
      {
         $this->template = "sat";
      }
   }
   
   public function nuevo_cliente()
   {
      //----------------------------------------------
      // agrega un cliente nuevo y retorna el id
      //----------------------------------------------
      
      if( isset($_POST['nombre']) )
      {
         $cliente = new cliente();
         $cliente->codcliente = $cliente->get_new_codigo();
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->telefono1 = $_POST['telefono1'];
         $cliente->telefono2 = $_POST['telefono2'];
         
         if( $cliente->save() )
            $this->new_message('Cliente agregado correctamente.');
         else
            $this->new_error_msg('Error al agregar los datos del cliente.');
      }
      
      return $cliente->codcliente;
   }
   
   public function agrega_sat()
   {      
      if($this->cliente)
      {

         
         $this->registro_sat->codcliente = $_GET['codcliente'];
         $this->registro_sat->modelo = $_POST['modelo'];
         
         if($_POST['fcomienzo'] == '')
         {
            $this->registro_sat->fcomienzo = NULL;
         }
         else
            $this->registro_sat->fcomienzo = $_POST['fcomienzo'];
         
         if($_POST['ffin'] == '')
         {
            $this->registro_sat->ffin = NULL;
         }
         else
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
      if( isset($_POST['detalle']) )
      {
         $this->agrega_detalle(); 
      }
      else
      {
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
         if($this->resultado->estado != $_POST['estado'])//si tiene el mismo estado no tiene que hacer nada sino tiene que añadir un detalle
         {
            $this->resultado->estado = $_POST['estado'];
            $this->agrega_detalle_estado($_POST['estado']);
         }
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
      
   }
   
   public function listar_sat()
   {
      if(isset($_POST['query']) || isset($_POST['desde']) ||isset($_POST['hasta']) || isset($_POST['desde']))
      {
        if( isset($_POST['buscar']) )
        {
            $this->busqueda['contenido']=$_POST['buscar'];
        }
        else 
        {
            $this->busqueda['contenido']="";
        }
        if( isset($_POST['desde']) )
        {
            $this->busqueda['desde']=$_POST['desde'];
        }
        else 
        {
            $this->busqueda['desde']="";
        }
        if( isset($_POST['hasta']) )
        {
            $this->busqueda['hasta']=$_POST['hasta'];
        }
        else 
        {
            $this->busqueda['hasta']="";
        }
        if( isset($_POST['estado']) )
        {
            $this->busqueda['estado']=$_POST['estado'];
        }
        else 
        {
            $this->busqueda['estado']="";
        }
        
        if( isset($_POST['orden']) )
        {
            $this->busqueda['orden']=$_POST['orden'];
        }
        else 
        {
            $this->busqueda['orden']="nsat";
        }
        
        return $this->registro_sat->search($this->busqueda['contenido'], $this->busqueda['desde'], $this->busqueda['hasta'], $this->busqueda['estado'], $this->busqueda['orden']);
      }
      if( isset($_GET['codcliente']) )
      {
        return $this->registro_sat->all_from_cliente($_GET['codcliente']);
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
   
   /*listar el dealle de sat*/
   public function listar_sat_detalle()
   {
      return $this->detalles_sat->all_from_sat($_GET['id']);
   }

    public function agrega_detalle()
   {


         $detalle= new detalles_sat();
         $detalle->descripcion = $_POST['detalle'];
         $detalle->nsat = $_GET['id'];
         if( $detalle->save() )
         {
            $this->new_message('Detalle guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar el detalle.');
            return FALSE;
         }
 
   }
   
   public function agrega_detalle_estado($estado)
   {


         $detalle= new detalles_sat();
         $detalle->descripcion = "Se a cambiado el estado a : ".$this->registro_sat->nombre_estado_param($estado);
         $detalle->nsat = $_GET['id'];
         if( $detalle->save() )
         {
            $this->new_message('Detalle guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar el detalle.');
            return FALSE;
         }
 
   }
   
   private function meter_extensiones()
   {
      /// añadimos la extensión para clientes
      $extension = array(
          'from' => __CLASS__,
          'to' => 'ventas_cliente',
          'type' => 'button',
          'name' => 'cliente_sat',
          'text' => 'SAT'
      );
      $fsext0 = new fs_extension();
      if( !$fsext0->array_save($extension) )
      {
         $this->new_error_msg('Imposible guardar los datos de la extensión.');
      }
   }
}