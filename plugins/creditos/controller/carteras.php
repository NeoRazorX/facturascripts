<?php

require_model('cartera.php');

class carteras extends fs_controller
{
   public $cartera;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Carteras', 'creditos', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->cartera = new cartera();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos la cartera y así lo modificamos
         if( isset($_POST['idcartera']) )
         {
            $cart0 = $this->cartera->get($_POST['idcartera']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $cart0 = new cartera();
            $cart0->idcartera = $this->cartera->nuevo_numero();
         }
         
         $cart0->descripcion = $_POST['descripcion'];
         
         if( $cart0->save() )
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
         $cart0 = $this->cartera->get($_GET['delete']);
         if($cart0)
         {
            if( $cart0->delete() )
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
   
   public function listar_carteras()
   {
      if( isset($_POST['query']) )
      {
         return $this->cartera->buscar($_POST['query']);
      }
      else
      {
         return $this->cartera->listar();
      }
   }
}
