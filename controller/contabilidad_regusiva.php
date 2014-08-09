<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('asiento.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('partida.php');
require_model('regularizacion_iva.php');
require_model('subcuenta.php');

class contabilidad_regusiva extends fs_controller
{
   public $fecha_desde;
   public $fecha_hasta;
   public $aux_regiva;
   public $periodo;
   public $regiva;
   public $factura_cli;
   public $factura_pro;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Regularizaciones de IVA', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->regiva = new regularizacion_iva();
      $this->factura_cli = new factura_cliente();
      $this->factura_pro = new factura_proveedor();
      
      switch( Date('n') )
      {
         case '1':
            $this->fecha_desde = Date('01-10-Y', strtotime( Date('Y').' -1 year') );
            $this->fecha_hasta = Date('31-12-Y', strtotime( Date('Y').' -1 year') );
            $this->periodo = 'T4';
            break;
         
         case '2':
         case '3':
         case '4':
            $this->fecha_desde = Date('01-01-Y');
            $this->fecha_hasta = Date('31-03-Y');
            $this->periodo = 'T1';
            break;
         
         case '5':
         case '6':
         case '7':
            $this->fecha_desde = Date('01-04-Y');
            $this->fecha_hasta = Date('30-06-Y');
            $this->periodo = 'T2';
            break;
         
         case '8':
         case '9':
         case '10':
            $this->fecha_desde = Date('01-07-Y');
            $this->fecha_hasta = Date('30-09-Y');
            $this->periodo = 'T3';
            break;
         
         case '11':
         case '12':
            $this->fecha_desde = Date('01-10-Y');
            $this->fecha_hasta = Date('31-12-Y');
            $this->periodo = 'T4';
            break;
      }
      
      if( isset($_POST['idregiva']) )
      {
         $this->full_regularizacion();
      }
      else if( isset($_POST['proceso']) )
      {
         if( $this->factura_cli->huecos() )
         {
            $this->new_error_msg('Tienes huecos en la facturación y por tanto no puedes regularizar el iva.');
         }
         else if($_POST['proceso'] == 'guardar')
         {
            $this->guardar_regiva();
         }
         else
            $this->completar_regiva();
      }
      else
      {
         if( isset($_GET['delete']) )
         {
            $regiva0 = $this->regiva->get($_GET['delete']);
            if($regiva0)
            {
               if( $regiva0->delete() )
               {
                  $this->new_message('Regularización eliminada correctamente.');
               }
               else
                  $this->new_error_msg('Imposible eliminar la regularización.');
            }
            else
               $this->new_error_msg('Regularización no encontrada.');
         }
         
         $this->buttons[] = new fs_button_img('b_nueva_regiva', 'Nueva');
      }
   }
   
   private function full_regularizacion()
   {
      $this->template = 'ajax/contabilidad_regusiva_extra';
      $this->regiva = $this->regiva->get($_POST['idregiva']);
   }
   
   private function completar_regiva()
   {
      $this->template = 'ajax/contabilidad_regusiva';
      
      $this->aux_regiva = array();
      
      $ejercicio = new ejercicio();
      $partida = new partida();
      $subcuenta = new subcuenta();
      
      $eje0 = $ejercicio->get_by_fecha($_POST['desde'], TRUE);
      if($eje0)
      {
         $continuar = TRUE;
         $saldo = 0;
         
         /// obtenemos el IVA soportado
         $scta_ivasop = $subcuenta->get_cuentaesp('IVASOP', $eje0->codejercicio);
         if($scta_ivasop)
         {
            $tot_sop = $partida->totales_from_subcuenta_fechas($scta_ivasop->idsubcuenta, $_POST['desde'], $_POST['hasta']);
            
            /// invertimos el debe y el haber
            $this->aux_regiva[] = array(
                'subcuenta' => $scta_ivasop->codsubcuenta,
                'debe' => $tot_sop['haber'],
                'haber' => $tot_sop['debe']
            );
            $saldo += $tot_sop['haber'] - $tot_sop['debe'];
         }
         else
         {
            $this->new_error_msg('Subcuenta de IVA soportado no encontrada.');
            $continuar = FALSE;
         }
         
         /// obtenemos el IVA repercutido
         $scta_ivarep = $subcuenta->get_cuentaesp('IVAREP', $eje0->codejercicio);
         if($scta_ivarep)
         {
            $tot_rep = $partida->totales_from_subcuenta_fechas($scta_ivarep->idsubcuenta, $_POST['desde'], $_POST['hasta']);
            
            /// invertimos el debe y el haber
            $this->aux_regiva[] = array(
                'subcuenta' => $scta_ivarep->codsubcuenta,
                'debe' => $tot_rep['haber'],
                'haber' => $tot_rep['debe']
            );
            $saldo += $tot_rep['haber'] - $tot_rep['debe'];
         }
         else
         {
            $this->new_error_msg('Subcuenta de IVA repercutido no encontrada.');
            $continuar = FALSE;
         }
         
         if($continuar)
         {
            if($saldo > 0)
            {
               $scta_ivaacr = $subcuenta->get_cuentaesp('IVAACR', $eje0->codejercicio);
               if($scta_ivaacr)
               {
                  $this->aux_regiva[] = array(
                      'subcuenta' => $scta_ivaacr->codsubcuenta,
                      'debe' => 0,
                      'haber' => $saldo
                  );
               }
               else
                  $this->new_error_msg('No se encuentra la subcuenta acreedora por IVA.');
            }
            else if($saldo < 0)
            {
               $scta_ivadeu = $subcuenta->get_cuentaesp('IVADEU', $eje0->codejercicio);
               if($scta_ivadeu)
               {
                  $this->aux_regiva[] = array(
                      'subcuenta' => $scta_ivadeu->codsubcuenta,
                      'debe' => abs($saldo),
                      'haber' => 0
                  );
               }
               else
                  $this->new_error_msg('No se encuentra la subcuenta deudora por IVA.');
            }
         }
         else
            $this->new_error_msg('Error al leer las subcuentas.');
      }
      else
         $this->new_error_msg('El ejercicio está cerrado.');
   }
   
   private function guardar_regiva()
   {
      $asiento = new asiento();
      $ejercicio = new ejercicio();
      $subcuenta = new subcuenta();
      
      $eje0 = $ejercicio->get_by_fecha($_POST['desde'], TRUE);
      if($eje0)
      {
         $continuar = TRUE;
         $saldo = 0;
         
         /// guardamos el asiento
         $asiento->codejercicio = $eje0->codejercicio;
         $asiento->concepto = 'REGULARIZACIÓN IVA '.$_POST['periodo'];
         $asiento->fecha = $_POST['hasta'];
         $asiento->editable = FALSE;
         if( !$asiento->save() )
         {
            $this->new_error_msg('Imposible guardar el asiento.');
            $continuar = FALSE;
         }
         
         /// obtenemos el IVA soportado
         $scta_ivasop = $subcuenta->get_cuentaesp('IVASOP', $eje0->codejercicio);
         if($scta_ivasop)
         {
            $par0 = new partida();
            $par0->idasiento = $asiento->idasiento;
            $par0->concepto = $asiento->concepto;
            $par0->coddivisa = $scta_ivasop->coddivisa;
            $par0->tasaconv = $scta_ivasop->tasaconv();
            $par0->codsubcuenta = $scta_ivasop->codsubcuenta;
            $par0->idsubcuenta = $scta_ivasop->idsubcuenta;
            
            $tot_sop = $par0->totales_from_subcuenta_fechas($scta_ivasop->idsubcuenta, $_POST['desde'], $_POST['hasta']);
            
            /// invertimos el debe y el haber
            $par0->debe = $tot_sop['haber'];
            $par0->haber = $tot_sop['debe'];
            $saldo += $tot_sop['haber'] - $tot_sop['debe'];
            
            if( !$par0->save() )
            {
               $this->new_error_msg('Error al guardar la partida de la subcuenta de IVA soportado.');
               $continuar = FALSE;
            }
         }
         else
         {
            $this->new_error_msg('Subcuenta de IVA soportado no encontrada.');
            $continuar = FALSE;
         }
         
         /// obtenemos el IVA repercutido
         $scta_ivarep = $subcuenta->get_cuentaesp('IVAREP', $eje0->codejercicio);
         if($scta_ivarep)
         {
            $par1 = new partida();
            $par1->idasiento = $asiento->idasiento;
            $par1->concepto = $asiento->concepto;
            $par1->coddivisa = $scta_ivarep->coddivisa;
            $par1->tasaconv = $scta_ivarep->tasaconv();
            $par1->codsubcuenta = $scta_ivarep->codsubcuenta;
            $par1->idsubcuenta = $scta_ivarep->idsubcuenta;
            
            $tot_rep = $par1->totales_from_subcuenta_fechas($scta_ivarep->idsubcuenta, $_POST['desde'], $_POST['hasta']);
            
            /// invertimos el debe y el haber
            $par1->debe = $tot_rep['haber'];
            $par1->haber = $tot_rep['debe'];
            $saldo += $tot_rep['haber'] - $tot_rep['debe'];
            
            if( !$par1->save() )
            {
               $this->new_error_msg('Error al guardar la partida de la subcuenta de IVA repercutido.');
               $continuar = FALSE;
            }
         }
         else
         {
            $this->new_error_msg('Subcuenta de IVA repercutido no encontrada.');
            $continuar = FALSE;
         }
         
         if($continuar)
         {
            if($saldo > 0)
            {
               $scta_ivaacr = $subcuenta->get_cuentaesp('IVAACR', $eje0->codejercicio);
               if($scta_ivaacr)
               {
                  $par2 = new partida();
                  $par2->idasiento = $asiento->idasiento;
                  $par2->concepto = $asiento->concepto;
                  $par2->coddivisa = $scta_ivaacr->coddivisa;
                  $par2->tasaconv = $scta_ivaacr->tasaconv();
                  $par2->codsubcuenta = $scta_ivaacr->codsubcuenta;
                  $par2->idsubcuenta = $scta_ivaacr->idsubcuenta;
                  $par2->debe = 0;
                  $par2->haber = $saldo;
                  if( !$par2->save() )
                  {
                     $this->new_error_msg('Error al guardar la partida de la subcuenta de acreedor por IVA.');
                     $continuar = FALSE;
                  }
               }
               else
                  $this->new_error_msg('No se encuentra la subcuenta acreedora por IVA.');
            }
            else if($saldo < 0)
            {
               $scta_ivadeu = $subcuenta->get_cuentaesp('IVADEU', $eje0->codejercicio);
               if($scta_ivadeu)
               {
                  $par2 = new partida();
                  $par2->idasiento = $asiento->idasiento;
                  $par2->concepto = $asiento->concepto;
                  $par2->coddivisa = $scta_ivadeu->coddivisa;
                  $par2->tasaconv = $scta_ivadeu->tasaconv();
                  $par2->codsubcuenta = $scta_ivadeu->codsubcuenta;
                  $par2->idsubcuenta = $scta_ivadeu->idsubcuenta;
                  $par2->debe = abs($saldo);
                  $par2->haber = 0;
                  if( !$par2->save() )
                  {
                     $this->new_error_msg('Error al guardar la partida de la subcuenta deudora por IVA.');
                     $continuar = FALSE;
                  }
               }
               else
                  $this->new_error_msg('No se encuentra la subcuenta deudora por IVA.');
            }
         }
         else
            $this->new_error_msg('Error al leer las subcuentas.');
         
         if($continuar)
         {
            $this->regiva = new regularizacion_iva();
            $this->regiva->codejercicio = $eje0->codejercicio;
            $this->regiva->fechaasiento = $asiento->fecha;
            $this->regiva->fechafin = $_POST['hasta'];
            $this->regiva->fechainicio = $_POST['desde'];
            $this->regiva->idasiento = $asiento->idasiento;
            $this->regiva->periodo = $_POST['periodo'];
            
            if( $this->regiva->save() )
            {
               $this->new_message('<a href="#" onclick="full_regiva(\''.$this->regiva->idregiva.'\')">Regularización</a>
                  guardada correctamente.');
            }
            else if( $asiento->delete() )
            {
               $this->new_error_msg('Error al guardar la regularización. Se ha eliminado el asiento.');
            }
            else
               $this->new_error_msg('Error al guardar la regularización. No se ha podido eliminar el asiento.');
         }
      }
      else
         $this->new_error_msg('El ejercicio está cerrado.');
   }
}
