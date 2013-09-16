<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'base/fs_printer.php';
require_once 'model/agente.php';
require_once 'model/caja.php';
require_once 'plugins/supermercado/model/clan_familiar.php';

class tpv_supermercado extends fs_controller
{
   public $agente;
   public $caja;
   public $clan;
   
   public function __construct()
   {
      parent::__construct('tpv_supermercado', 'Supermercado', 'TPV');
   }
   
   protected function process()
   {
      $this->agente = $this->user->get_agente();
      $this->caja = new caja();
      
      if($this->agente)
      {
         /// obtenemos el bloqueo de caja, sin esto no se puede continuar
         $this->caja = $this->caja->get_last_from_this_server();
         if($this->caja)
         {
            if($this->caja->codagente == $this->user->codagente)
               $this->caja_iniciada();
         }
         else if( isset($_POST['d_inicial']) )
         {
            $this->caja = new caja();
            $this->caja->codagente = $this->user->codagente;
            $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
            $this->caja->dinero_fin = floatval($_POST['d_inicial']);
            if( $this->caja->save() )
            {
               $this->new_message("Caja iniciada con ".$this->caja->show_dinero_inicial()." Euros.");
               $this->caja_iniciada();
               
            }
            else
               $this->new_error_msg("¡Imposible guardar los datos de caja!");
         }
         else
         {
            $fpt = new fs_printer();
            $fpt->abrir_cajon();
         }
      }
      else
      {
         $this->new_error_msg('No tienes un <a href="'.$this->user->url().'">agente asociado</a>
            a tu usuario, y por tanto no puedes hacer tickets.');
      }
   }
   
   public function version()
   {
      return parent::version().'-1';
   }
   
   private function caja_iniciada()
   {
      $this->template = 'tpv_supermercado2';
      
      if( isset($_GET['cerrar_caja']) )
         $this->cerrar_caja();
      
      $this->buttons[] = new fs_button_img('b_borrar_ticket', 'borrar ticket', 'trash.png', '#', TRUE);
      $this->buttons[] = new fs_button_img('b_cerrar_caja', 'cerrar caja', 'remove.png', '#', TRUE);
   }
   
   private function cerrar_caja()
   {
      $this->caja->fecha_fin = Date('d-m-Y H:i:s');
      if( $this->caja->save() )
      {
         $fpt = new fs_printer();
         $fpt->add_big("\nCIERRE DE CAJA:\n");
         $fpt->add("Agente: ".$this->user->codagente." ".$this->agente->get_fullname()."\n");
         $fpt->add("Caja: ".$this->caja->fs_id."\n");
         $fpt->add("Fecha inicial: ".$this->caja->fecha_inicial."\n");
         $fpt->add("Dinero inicial: ".$this->caja->show_dinero_inicial()." Eur.\n");
         $fpt->add("Fecha fin: ".$this->caja->show_fecha_fin()."\n");
         $fpt->add("Dinero fin: ".$this->caja->show_dinero_fin()." Eur.\n");
         $fpt->add("Diferencia: ".$this->caja->show_diferencia()." Eur.\n");
         $fpt->add("Tickets: ".$this->caja->tickets."\n\n");
         $fpt->add("Dinero pesado:\n\n\n");
         $fpt->add("Observaciones:\n\n\n\n");
         $fpt->add("Firma:\n\n\n\n\n\n\n");
         
         /// encabezado común para los tickets
         $fpt->add_big( $fpt->center_text($this->empresa->nombre, 16)."\n");
         $fpt->add( $fpt->center_text($this->empresa->lema) . "\n\n");
         $fpt->add( $fpt->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
         $fpt->add( $fpt->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"); /// corta el papel
         $fpt->add( $fpt->center_text($this->empresa->horario) . "\n");
         
         $fpt->imprimir();
         $fpt->abrir_cajon();
         
         /// recargamos la página
         header('location: '.$this->url());
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }
}

?>