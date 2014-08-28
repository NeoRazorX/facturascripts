<?php

require_model('consumo.php');

class consumos extends fs_controller
{
   public $consumo;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Tarifa Consumos', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->consumo = new consumo();
      
      if( isset($_POST['descripcion']) )
      {
         /// si tenemos el id, buscamos el consumo y así lo modificamos
         if( isset($_POST['idconsumo']) )
         {
            $coms0 = $this->consumo->get($_POST['idconsumo']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $coms0 = new consumo();
            $coms0->idconsumo = $this->consumo->nuevo_numero();
         }
         
         $coms0->descripcion = $_POST['descripcion'];
         $coms0->inicial = intval($_POST['inicial']);
         $coms0->final = intval($_POST['final']);
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
         $coms0 = $this->consumo->get($_GET['delete']);
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
   
   public function listar_consumos()
   {
      if($this->query != '')
      {
         return $this->consumo->buscar($_POST['query']);
      }
      else
      {
         return $this->consumo->listar();
      }
   }
}