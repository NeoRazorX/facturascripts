<?php
/*
   Plugin Fabricantes para FacturaSctipts
   (c) 2014 JHircano@gmail.com
   -----------------------------------------------------------------------------------------

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

require_model('fabricante.php');

class fabricantes extends fs_controller
{
   public $fabricante;
   public $action;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Fabricantes', 'ventas', FALSE, TRUE);
   }
   
   protected function process()
   {
      $cod = ( isset($_GET['codfabricante']) ? intval($_GET['codfabricante']) : 0 );
      $this->fabricante = fabricante::loadObject($cod);
      $this->action = '';

      $this->buttons[] = new fs_button_img('b_nuevo_fabricante', 'Nuevo');
      $this->custom_search = TRUE;

      if( isset($_POST['submit'.ucfirst(__CLASS__)]) )
      {
         $action = ( isset($_POST['codfabricante']) ? 'actualizar' : 'nuevo' );
         
         // codfabricante es el índice, y lo maneja el controlador. El usuario no puede asignarlo / modificarlo.
         $this->fabricante->codfabricante = ( isset($_POST['codfabricante']) ? intval($_POST['codfabricante']) : $this->fabricante->nuevo_numero() );
         $this->fabricante->nombre = $_POST['nombre'];
         $this->fabricante->descripcion = $_POST['descripcion'];
         $this->fabricante->valoracion = $_POST['valoracion'];
         
         $this->fabricante->fecha_alta = NULL;
         if($_POST['fecha_alta'] != '')
            $this->fabricante->fecha_alta = $_POST['fecha_alta'];

         $this->fabricante->activo = ( isset($_POST['activo']) ? 1 : 0 );
         
         if( $this->fabricante->save() )
         {
            $this->new_message('El fabricante <b>'.$this->fabricante->nombre.'</b> se ha guardado con el código: <b>'.$this->fabricante->codfabricante.'</b>.');
            $this->fabricante = fabricante::loadObject( 0 );    // Objeto vacío

            // Header('location: ' . $this->fabricante->url());
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos.');
            // Prevenir modal de actualizar fabricante
            $this->fabricante->codfabricante = NULL;
            // Volver a la ventana modal adecuada:
            $this->action = $action;
         }
      }
      else if( isset($_GET['delete']) )
      {
         $this->fabricante->codfabricante = intval($_GET['delete']);
         
         if( $this->fabricante->delete() )
         {
            $this->new_message('Datos eliminados correctamente.');
            $this->fabricante = fabricante::loadObject( 0 );
         }
         else
         {
            $this->new_error_msg('Imposible eliminar los datos.');
         }
      }
      else if( isset($_GET['cestado']) )
      {
         $this->fabricante = fabricante::loadObject( intval($_GET['cestado']) );
         $this->fabricante->activo = intval(!$this->fabricante->activo);
         
         if( $this->fabricante->save() )
         {
            $this->new_message('Datos actualizados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible actualizar los datos.');
         }
         $this->fabricante = fabricante::loadObject( 0 );
      }
   }
   
   public function listar_fabricantes()
   {
      if( isset($_POST['query']) )
      {
         $listaf = $this->fabricante->buscar($_POST['query']);
         if (count($this->fabricante->get_errors()))
            $this->new_error_msg("Se ha producido un error.");

         return $listaf;
      }
      else
      {
         return $this->fabricante->listar();
      }
   }
}