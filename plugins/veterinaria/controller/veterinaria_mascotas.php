<?php

require_model('cliente.php');
require_model('mascota.php');
require_model('raza.php');

class veterinaria_mascotas extends fs_controller
{
   public $cliente;
   public $mascota;
   public $raza;
   
   public function __construct()
   {
      parent::__construct('veterinaria_mascotas', 'Mascotas', 'Veterinaria', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->cliente = new cliente();
      $this->mascota = new mascota();
      $this->raza = new raza();
      $this->custom_search = TRUE;
      
      $this->buttons[] = new fs_button('b_nueva', 'nueva');
      
      if( isset($_POST['nombre']) )
      {
         $cli0 = $this->cliente->get($_POST['cliente']);
         if($cli0)
         {
            $raza0 = $this->raza->get($_POST['raza']);
            if($raza0)
            {
               $this->mascota->cod_cliente = $cli0->codcliente;
               $this->mascota->nombre = $_POST['nombre'];
               $this->mascota->chip = $_POST['chip'];
               $this->mascota->pasaporte = $_POST['pasaporte'];
               $this->mascota->raza = $raza0->nombre;
               $this->mascota->especie = $raza0->especie;
               
               if( $this->mascota->save() )
                  $this->new_message('Datos de mascota guardados correctamente.');
               else
                  $this->new_error_msg('Imposible guardar la mascota.');
            }
            else
               $this->new_error_msg('Raza no encontrada.');
         }
         else
            $this->new_error_msg('Cliente no encontrado.');
      }
   }
}
