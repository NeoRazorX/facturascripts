<?php

require_model('concepto.php');

class conceptos extends fs_controller
{
   public $concepto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Conceptos Extras', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->concepto = new concepto();
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el concepto y así lo modificamos
         if( isset($_POST['idconcepto']) )
         {
            $coms0 = $this->concepto->get($_POST['idconcepto']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $coms0 = new concepto();
            $coms0->idconcepto = $this->concepto->nuevo_numero();
         }
         
         $coms0->descripcion = $_POST['descripcion'];
         $coms0->precio = intval($_POST['precio']);
         
         if( $coms0->save() )
         {
            $this->new_message('Datos guardados correctamente.');
         }
         else
         {
            $this->new_error_msg('Imposible guardar los datos.');
         }
      }
      else if( isset($_GET['delete']) )
      {
         $coms0 = $this->concepto->get($_GET['delete']);
         if($coms0)
         {
            if( $coms0->delete() )
            {
               $this->new_message('Identificador '. $_GET['delete'] .' eliminado correctamente.');
            }
            else
            {
               $this->new_error_msg('Imposible eliminar los datos.');
            }
         }
      }
   }
   
   public function listar_conceptos()
   {
      if($this->query != '')
      {
         return $this->concepto->buscar($_POST['query']);
      }
      else
      {
         return $this->concepto->listar();
      }
   }
}