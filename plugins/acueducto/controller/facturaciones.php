<?php

require_model('facturacion.php');

class facturaciones extends fs_controller
{
   public $facturacion;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Facturaciones', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->facturacion = new facturacion(); 
      
      if( isset($_POST['fecha']) )
      {
         /// si tenemos el id, buscamos el cargo y así lo modificamos
         if( isset($_POST['idfacturacion']) )
         {
            $fact0 = $this->facturacion->get($_POST['idfacturacion']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $fact0 = new facturacion();
         }
         
         $fact0->fecha = $_POST['fecha'];
         $fact0->imputacion = $_POST['imputacion'];
         $fact0->usuario = $_POST['usuario'];
         
         if( $fact0->save() )
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
         $fact0 = $this->facturacion->get($_GET['delete']);
         if($fact0)
         {
            if( $fact0->delete() )
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
   
   public function listar_facturaciones()
   {
      if($this->query != '')
      {
         return $this->facturacion->buscar($_POST['query']);
      }
      else
      {
         return $this->facturacion->listar();
      }
   }
}