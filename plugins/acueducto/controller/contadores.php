<?php

require_model('contador.php');

class contadores extends fs_controller
{
   public $contador;
   ///public $cliente;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Contadores', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->contador = new contador();
      ///$this->cliente = new cliente();
      
      if( isset($_POST['codcliente']) )
      {
         /// si tenemos el id, buscamos el lectura y así lo modificamos
         if( isset($_POST['idcontador']) )
         {
            $cont0 = $this->contador->get($_POST['idcontador']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $cont0 = new contador();
            $cont0->idcontador = $this->contador->nuevo_numero();
         }
         
         $cont0->codcliente = $_POST['codcliente'];
         $cont0->numero = $_POST['numero'];
         $cont0->ubicacion = $_POST['ubicacion'];
         $cont0->alta = $_POST['alta'];
         $cont0->lectura = $_POST['lectura'];
         
         if( $cont0->save() )
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
         $cont0 = $this->contador->get($_GET['delete']);
         if($cont0)
         {
            if( $cont0->delete() )
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
   
   public function listar_contadores()
   {
      if($this->query != '')
      {
         return $this->contador->buscar($_POST['query']);
      }
      else
      {
         return $this->contador->listar();
      }
   }
}