<?php

require_model('cargo.php');
require_model('cliente.php');
require_model('concepto.php');

class cargos extends fs_controller
{
   public $cargo;
   public $cliente;
   public $concepto;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Cargos a Facturas', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->cargo = new cargo();
      $this->cliente = new cliente();
      $this->concepto = new concepto();
      
      
      if( isset($_POST['codcliente']) )
      {
         /// si tenemos el id, buscamos el cargo y así lo modificamos
         if( isset($_POST['idcargo']) )
         {
            $carg0 = $this->cargo->get($_POST['idcargo']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $carg0 = new cargo();
            $carg0->idcargo = $this->cargo->nuevo_numero();
         }
         
         $carg0->codcliente = $_POST['codcliente'];
         $carg0->idconcepto = $_POST['idconcepto'];
         $carg0->precio = intval($_POST['precio']);
         $carg0->cantidad = intval($_POST['cantidad']);
         $carg0->total = intval($_POST['total']);
         $carg0->fecha = $_POST['fecha'];
         $carg0->facturado = isset($_POST['facturado']);
         $carg0->numero = intval($_POST['numero']);
         $carg0->imputacion = $_POST['imputacion'];
         $carg0->usuario = $_POST['usuario'];
         
         if( $carg0->save() )
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
         $carg0 = $this->cargo->get($_GET['delete']);
         if($carg0)
         {
            if( $carg0->delete() )
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
   
   public function listar_cargos()
   {
      if($this->query != '')
      {
         return $this->cargo->buscar($_POST['query']);
      }
      else
      {
         return $this->cargo->listar();
      }
   }
}