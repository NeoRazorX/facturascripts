<?php

require_model('tecnico.php');

class tecnicos extends fs_controller
{
   public $tecnico;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Técnicos de Campo', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->tecnico = new tecnico();
      
      if( isset($_POST['nombre']) )
      {
         /// si tenemos el id, buscamos el tecnico y así lo modificamos
         if( isset($_POST['idtecnico']) )
         {
            $tecn0 = $this->tecnico->get($_POST['idtecnico']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $tecn0 = new tecnico();
            $tecn0->idtecnico = $this->tecnico->nuevo_numero();
         }
         
         $tecn0->nombre = $_POST['nombre'];
         $tecn0->telefono = intval($_POST['telefono']);
         
         if( $tecn0->save() )
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
         $tecn0 = $this->tecnico->get($_GET['delete']);
         if($tecn0)
         {
            if( $tecn0->delete() )
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
   
   public function listar_tecnicos()
   {
      if($this->query != '')
      {
         return $this->tecnico->buscar($_POST['query']);
      }
      else
      {
         return $this->tecnico->listar();
      }
   }
}