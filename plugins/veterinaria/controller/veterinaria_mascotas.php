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

require_model('cliente.php');
require_model('fbm_mascota.php');
require_model('fbm_raza.php');
require_model('fs_extension.php');

class veterinaria_mascotas extends fs_controller
{
   public $cliente;
   public $mascota;
   public $raza;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Mascotas', 'Veterinaria', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $this->cliente = new cliente();
      $this->mascota = new fbm_mascota();
      $this->raza = new fbm_raza();
      $this->custom_search = TRUE;
      $this->resultados = array();
      
      if($this->query != '')
      {
         $this->resultados = $this->mascota->search($this->query);
         $this->new_advice('Resultados de la búsqueda <b>'.$this->query.'</b>.');
      }
      else if( isset($_GET['codcliente']) ) /// buscar por cliente
      {
         $cli0 = $this->cliente->get($_GET['codcliente']);
         if($cli0)
         {
            $this->resultados = $this->mascota->all_from_cliente($_GET['codcliente']);
            $this->new_advice('Mascotas de <a href="'.$cli0->url().'">'.$cli0->nombre.'</a>.');
         }
         else
            $this->new_error_msg('Cliente no encontrado.');
      }
      else if( isset($_POST['codcliente']) ) /// nueva mascota
      {
         $cli0 = $this->cliente->get($_POST['codcliente']);
         if($cli0)
         {
            $this->mascota->codcliente = $cli0->codcliente;
            $this->mascota->nombre = $_POST['nombre'];
            $this->mascota->chip = $_POST['chip'];
            $this->mascota->pasaporte = $_POST['pasaporte'];
            $this->mascota->idraza = $_POST['raza'];
            
            if( $this->mascota->save() )
            {
               $this->new_message('Datos de mascota guardados correctamente.');
               header('Location: '.$this->mascota->url());
            }
            else
               $this->new_error_msg('Imposible guardar la mascota.');
         }
         else
            $this->new_error_msg('Cliente no encontrado.');
      }
      else if( isset($_GET['delete']) )
      {
         $mas0 = $this->mascota->get($_GET['delete']);
         if($mas0)
         {
            if( $mas0->delete() )
            {
               $this->new_message('Mascota eliminada correctamente.');
            }
            else
               $this->new_error_msg('Error al eliminar la mascota.');
         }
         else
            $this->new_error_msg('Mascota no encontrada.');
      }
      else
      {
         $this->share_extensions();
         $this->resultados = $this->mascota->all();
      }
   }
   
   private function share_extensions()
   {
      /// añadimos la extensión para clientes
      $extension = array(
          'from' => __CLASS__,
          'to' => 'ventas_cliente',
          'type' => 'button',
          'name' => 'mascotas_cliente',
          'text' => 'Mascotas'
      );
      $fsext0 = new fs_extension();
      if( !$fsext0->array_save($extension) )
      {
         $this->new_error_msg('Imposible guardar los datos de la extensión.');
      }
   }
}
