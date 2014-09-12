<?php

require_model('sector.php');

class sectores extends fs_controller
{
   public $sector;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Sectores Zonas', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->sector = new sector();
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el tecnico y así lo modificamos
         if( isset($_POST['idsector']) )
         {
            $sect0 = $this->sector->get($_POST['idsector']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $sect0 = new sector();
            $sect0->idsector = $this->sector->nuevo_numero();
         }
         
         $sect0->descripcion = $_POST['descripcion'];
         $sect0->observacion = $_POST['observacion'];
         
         if( $sect0->save() )
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
         $sect0 = $this->sector->get($_GET['delete']);
         if($sect0)
         {
            if( $sect0->delete() )
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
   
   public function listar_sectores()
   {
      if($this->query != '')
      {
         return $this->sector->buscar($_POST['query']);
      }
      else
      {
         return $this->sector->listar();
      }
   }
}