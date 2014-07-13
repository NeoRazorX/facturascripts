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

require_model('paquete.php');

class general_paquete extends fs_controller
{
   public $paquete;
   
   public function __construct()
   {
      parent::__construct('general_paquete', 'Paquete', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_paquetes');
      $this->paquete = new paquete();
      
      if( isset($_GET['delete']) )
      {
         $this->paquete->referencia = $_GET['delete'];
         if( $this->paquete->delete() )
            header("location: ".$this->ppage->url());
         else
            $this->new_error_msg("¡Imposible eliminar el paquete!");
      }
      else if( isset($_POST['referenciapaq']) )
      {
         $this->paquete = $this->paquete->get($_POST['referenciapaq']);
         if($this->paquete)
         {
            $this->paquete->set_grupos($_POST['grupos']);
            /// eliminamos todos los subpquetes
            foreach($this->paquete->subpaquetes as $s)
               $s->delete();
            $this->paquete->subpaquetes = array();
            /// añadimos los artículos marcados
            foreach($this->paquete->get_grupos() as $g)
            {
               if( isset($_POST['grupo_'.$g]) )
               {
                  foreach($_POST['grupo_'.$g] as $ref)
                  {
                     $subp = new subpaquete();
                     $subp->referenciapaq = $this->paquete->referencia;
                     $subp->grupo = $g;
                     $subp->referencia = $ref;
                     if( $subp->save() )
                     {
                        $subp->existe = TRUE;
                        $this->paquete->subpaquetes[] = $subp;
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar el subpaquete del grupo ".$subp->grupo.
                                " con referencia ".$subp->referencia."!");
                  }
               }
            }
         }
         else
         {
            $this->paquete = new paquete();
            $this->paquete->set_articulo($_POST['referenciapaq']);
            $this->paquete->set_grupos($_POST['grupos']);
            if( !$this->paquete->save() )
               $this->new_error_msg("¡Imposible guardar los datos del paquete!");
         }
      }
      else if( isset($_GET['ref']) )
         $this->paquete = $this->paquete->get($_GET['ref']);
      else
         $this->paquete = FALSE;
      
      if($this->paquete)
      {
         $this->buttons[] = new fs_button('b_articulo', 'Ver artículo', $this->paquete->articulo->url());
         $this->buttons[] = new fs_button_img('b_eliminar_paquete', 'Eliminar', 'trash.png',
                 $this->url()."&delete=".$this->paquete->referencia, TRUE);
      }
      else
         $this->new_error_msg("Paquete no encontrado.");
   }
   
   public function url()
   {
      if( !isset($this->paquete) )
         return parent::url();
      else if($this->paquete)
         return $this->paquete->url();
      else
         return $this->ppage->url();
   }
}

?>
