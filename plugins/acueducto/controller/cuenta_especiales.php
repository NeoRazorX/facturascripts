<?php

require_model('cuenta_especial.php');

class cuenta_especiales extends fs_controller
{
   public $cuenta_especial;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Cuentas Especiales', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->cuenta_especial = new cuenta_especial();
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el cuenta_especial y así lo modificamos
         if( isset($_POST['idcuentaesp']) )
         {
            $cesp0 = $this->cuenta_especial->get($_POST['idcuentaesp']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $cesp0 = new cuenta_especial();
            $cesp0->idcuentaesp = $_POST['idcuentaesp'];
         }
         
         $cesp0->descripcion = $_POST['descripcion'];
         $cesp0->codcuenta = $_POST['codcuenta'];
         $cesp0->codsubcuenta = $_POST['codsubcuenta'];
         
         
         if( $cesp0->save() )
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
         $cesp0 = $this->cuenta_especial->get($_GET['delete']);
         if($cesp0)
         {
            if( $cesp0->delete() )
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
   
   public function listar_cuenta_especiales()
   {
      if($this->query != '')
      {
         return $this->cuenta_especial->buscar($_POST['query']);
      }
      else
      {
         return $this->cuenta_especial->listar();
      }
   }
}