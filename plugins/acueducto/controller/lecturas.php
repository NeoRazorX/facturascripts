<?php

require_model('lectura.php');
require_model('cliente.php'); /// hay que cargar los modelos que uses ;-)
require_model('contador.php');
require_model('tecnico.php');

class lecturas extends fs_controller
{
   public $lectura;
   public $cliente;
   public $contador;
   public $tecnico;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Lecturas Contadores', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->lectura = new lectura();
      $this->cliente = new cliente();
      $this->contador = new contador();
      $this->tecnico = new tecnico();
      
      if( isset($_POST['codcliente']) )
      {
         /// si tenemos el id, buscamos el lectura y así lo modificamos
         if( isset($_POST['idlectura']) )
         {
            $lect0 = $this->lectura->get($_POST['idlectura']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {
            $lect0 = new lectura();
            $lect0->idlectura = $this->lectura->nuevo_numero();
         }
         
         $lect0->codcliente = $_POST['codcliente'];
         $lect0->idcontador = $_POST['idcontador'];
         $lect0->fecha = $_POST['fecha'];
         $lect0->lectura = intval($_POST['lectura']);
         $lect0->tecnico = $_POST['tecnico'];
         
         /// ISSET() para los checkbox ;-)
         $lect0->verificada = isset($_POST['verificada']);
         
         $lect0->imputacion = $_POST['imputacion'];
         $lect0->usuario = $_POST['usuario'];
         
         if( $lect0->save() )
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
         $lect0 = $this->lectura->get($_GET['delete']);
         if($lect0)
         {
            if( $lect0->delete() )
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
   
   public function listar_lecturas()
   {
      if($this->query != '')
      {
         return $this->lectura->buscar($_POST['query']);
      }
      else
      {
         return $this->lectura->listar();
      }
   }
}