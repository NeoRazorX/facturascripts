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

require_model('cuenta.php');
require_model('ejercicio.php');
require_model('epigrafe.php');

class contabilidad_epigrafes extends fs_controller
{
   public $codejercicio;
   public $ejercicio;
   public $epigrafe;
   public $grupo;
   public $resultados;
   public $super_epigrafes;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Grupos y epígrafes', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->codejercicio = $this->empresa->codejercicio;
      $this->ejercicio = new ejercicio();
      $grupo0 = new grupo_epigrafes();
      $epi0 = new epigrafe();
      $this->super_epigrafes = array();;

      if( isset($_POST['ngrupo']) ) /// nuevo grupo
      {
         $this->epigrafe = FALSE;
         $this->grupo = $grupo0->get_by_codigo($_GET['ngrupo'], $_POST['ejercicio']);
         if( !$this->grupo )
         {
            $this->grupo = new grupo_epigrafes();
            $this->grupo->codejercicio = $_POST['ejercicio'];
            $this->grupo->codgrupo = $_POST['ngrupo'];
            $this->grupo->descripcion = $_POST['descripcion'];
            
            if( $this->grupo->save() )
               header( 'Location: '.$this->grupo->url() );
            else
            {
               $this->new_error_msg('Error al guardar el grupo.');
               $this->grupo = FALSE;
            }
         }
      }
      else if( isset($_GET['grupo']) ) /// ver grupo
      {
         $this->epigrafe = FALSE;
         $this->grupo = $grupo0->get($_GET['grupo']);
         if($this->grupo AND isset($_POST['descripcion']) )
         {
            $this->grupo->descripcion = $_POST['descripcion'];
            if( $this->grupo->save() )
               $this->new_message('Grupo modificado correctamente.');
            else
               $this->new_error_msg('Error al modificar el grupo.');
         }
      }
      else if( isset($_GET['deleteg']) ) /// eliminar grupo
      {
         $grupo1 = $grupo0->get($_GET['deleteg']);
         if($grupo1)
         {
            if( $grupo1->delete() )
               $this->new_message('Grupo eliminado correctamente.');
            else
               $this->new_error_msg('Error al eliminar el grupo.');
         }
         else
            $this->new_error_msg('Grupo no encontrado.');
         
         $this->grupo = FALSE;
         $this->epigrafe = FALSE;
      }
      else if( isset($_POST['nepigrafe']) ) /// nuevo epígrafe
      {
         $this->epigrafe = $epi0->get_by_codigo($_POST['nepigrafe'], $_POST['ejercicio']);
         if( !$this->epigrafe )
         {
            $this->epigrafe = new epigrafe();
            $this->epigrafe->codejercicio = $_POST['ejercicio'];
            $this->epigrafe->codepigrafe = $_POST['nepigrafe'];
            
            if( isset($_POST['idpadre']) )
            {
               $this->epigrafe->idpadre = $_POST['idpadre'];
            }
            else
            {
               $this->epigrafe->codgrupo = $_POST['codgrupo'];
               $this->epigrafe->idgrupo = $_POST['idgrupo'];
               
               $this->grupo = $grupo0->get($_POST['idgrupo']);
            }
            
            $this->epigrafe->descripcion = $_POST['descripcion'];
            
            if( $this->epigrafe->save() )
            {
               header( 'Location: '.$this->epigrafe->url() );
            }
            else
               $this->new_error_msg('Error al guardar el epígrafe.');
         }
      }
      else if( isset($_GET['epi']) ) /// ver epígrafe
      {
         $this->grupo = FALSE;
         $this->epigrafe = $epi0->get($_GET['epi']);
         if($this->ejercicio AND isset($_POST['descripcion']) )
         {
            $this->epigrafe->descripcion = $_POST['descripcion'];
            if( $this->epigrafe->save() )
               $this->new_message('Epígrafe modificado correctamente.');
            else
               $this->new_error_msg('Error al modificar el epígrafe.');
         }
      }
      else if( isset($_GET['deletee']) ) /// eliminar epígrafe
      {
         $epi1 = $epi0->get($_GET['deletee']);
         if($epi1)
         {
            $this->grupo = $grupo0->get($epi1->idgrupo);
            
            if( $epi1->delete() )
               $this->new_message('Epígrafe eliminado correctamente.');
            else
               $this->new_error_msg('Error al eliminar el epígrafe.');
         }
         else
         {
            $this->new_error_msg('Epígrafe no encontrado.');
            $this->grupo = FALSE;
         }
      }
      else if( isset($_POST['ncuenta']) ) /// nueva cuenta
      {
         $this->grupo = FALSE;
         $this->epigrafe = FALSE;
         $cuenta0 = new cuenta();
         $cuenta1 = $cuenta0->get_by_codigo($_POST['ncuenta'], $_POST['ejercicio']);
         if($cuenta1)
            header( 'Location: '.$cuenta1->url() );
         else
         {
            $cuenta1 = new cuenta();
            $cuenta1->codcuenta = $_POST['ncuenta'];
            $cuenta1->codejercicio = $_POST['ejercicio'];
            $cuenta1->codepigrafe = $_POST['codepigrafe'];
            $cuenta1->descripcion = $_POST['descripcion'];
            $cuenta1->idepigrafe = $_POST['idepigrafe'];
            
            if( $cuenta1->save() )
               header( 'Location: '.$cuenta1->url() );
            else
               $this->new_error_msg('Error al guardar la cuenta.');
            
            $this->epigrafe = $epi0->get($_POST['idepigrafe']);
         }
      }
      else if( isset($_GET['deletec']) ) /// eliminar una cuenta
      {
         $this->grupo = FALSE;
         $this->epigrafe = FALSE;
         $cuenta0 = new cuenta();
         $cuenta1 = $cuenta0->get($_GET['deletec']);
         if($cuenta1)
         {
            $this->epigrafe = $epi0->get($cuenta1->idepigrafe);
            
            if( $cuenta1->delete() )
               $this->new_message('Cuenta eliminada correctamente.');
            else
               $this->new_error_msg('Error al eliminar la cuenta.');
         }
         else
            $this->new_error_msg('Cuenta no encontrada.');
      }
      
      if($this->grupo)
      {
         $this->ppage = $this->page->get($this->page->name);
         $this->page->title = 'Grupo: '.$this->grupo->codgrupo;
         $this->resultados = $this->grupo->get_epigrafes();
         $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else if($this->epigrafe)
      {
         /// configuramos la página previa
         $this->ppage = $this->page->get($this->page->name);
         
         if( !is_null($this->epigrafe->idgrupo) )
         {
            $this->ppage->title = 'Grupo: '.$this->epigrafe->codgrupo;
            $this->ppage->extra_url = '&grupo='.$this->epigrafe->idgrupo;
         }
         else if( !is_null($this->epigrafe->idpadre) )
         {
            $this->ppage->title = 'Padre';
            $this->ppage->extra_url = '&epi='.$this->epigrafe->idpadre;
         }
         
         $this->page->title = 'Epigrafe: '.$this->epigrafe->codepigrafe;
         $this->resultados = $this->epigrafe->get_cuentas();
         $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
      }
      else if( isset($_POST['ejercicio']) ) /// mostrar grupos de este ejercicio
      {
         $this->codejercicio = $_POST['ejercicio'];
         $this->grupo = FALSE;
         $this->epigrafe = FALSE;
         $this->resultados = $grupo0->all_from_ejercicio($this->codejercicio);
         $this->super_epigrafes = $epi0->super_from_ejercicio($this->codejercicio);
      }
      else
      {
         $this->grupo = FALSE;
         $this->epigrafe = FALSE;
         $this->resultados = $grupo0->all_from_ejercicio($this->empresa->codejercicio);
         $this->super_epigrafes = $epi0->super_from_ejercicio($this->empresa->codejercicio);
      }
   }
}
