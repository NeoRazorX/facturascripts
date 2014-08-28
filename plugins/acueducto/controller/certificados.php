<?php

require_model('certificado.php');

class certificados extends fs_controller
{
   public $certificado;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Certificados DIAN', 'acueducto', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_search = TRUE;
      $this->certificado = new certificado();
      
      if( isset($_POST['numero']) )
      {
         /// si tenemos el id, buscamos el certificado y así lo modificamos
         if( isset($_POST['idcertificado']) )
         {
            $cert0 = $this->certificado->get($_POST['idcertificado']);
         }
         else /// si no está el id, seguimos como si fuese nuevo
         {    
            $cert0 = new certificado();
            $cert0->idcertificado = $this->certificado->nuevo_numero();
         }
         
         $cert0->numero = $_POST['numero'];
         $cert0->fecha_inicio = $_POST['fecha_inicio'];
         if($_POST['fecha_fin'] != '')
         {
            $cert0->fecha_fin = $_POST['fecha_fin'];
         }
         
         $cert0->contador_inicial = intval($_POST['contador_inicial']);
         $cert0->contador_final = intval($_POST['contador_final']);
         
         if( $cert0->save() )
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
         $cert0 = $this->certificado->get($_GET['delete']);
         if($cert0)
         {
            if( $cert0->delete() )
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
   
   public function listar_certificados()
   {
      if($this->query != '')
      {
         return $this->certificado->buscar($this->query);
      }
      else
      {
         return $this->certificado->listar();
      }
   }
}