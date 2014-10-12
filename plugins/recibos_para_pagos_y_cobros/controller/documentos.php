<?php

require_model('documento.php');

class documentos extends fs_controller
{
   public $documento;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Documentos', 'tesoreria', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->documento = new documento();
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el documento y así lo modificamos
         if( isset($_POST['iddocumento']) )
         {
            $docu0 = $this->documento->get($_POST['iddocumento']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $docu0 = new documento();
            $docu0->iddocumento = $this->documento->nuevo_numero();
         }
         
         $docu0->descripcion = $_POST['descripcion'];
         
         if( $docu0->save() )
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
         $docu0 = $this->documento->get($_GET['delete']);
         if($docu0)
         {
            if( $docu0->delete() )
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
   
   public function listar_documentos()
   {
      if( isset($_POST['query']) )
      {
         return $this->documento->buscar($_POST['query']);
      }
      else
      {
         return $this->documento->listar();
      }
   }
}
