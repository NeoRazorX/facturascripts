<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class RectificativaTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    private static $contado;
    private static $paypal;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();

        self::$contado = self::cargarFormaPago('CONT');
        self::$paypal = self::cargarFormaPago('PAYPAL');
    }

    public function testRectificativaSinMarcarPagadas(): void
    {
        [$original, $rectificativa] = $this->escenario(false, false);

        $this->comprobarFactura($original, false, self::$paypal->codpago, 'original');
        $this->comprobarFactura($rectificativa, false, self::$paypal->codpago, 'rectificativa');
        $this->eliminar($original, $rectificativa);
    }

    public function testRectificativaMarcandoOriginalPagada(): void
    {
        [$original, $rectificativa] = $this->escenario(true, false);

        $this->comprobarFactura($original, true, self::$paypal->codpago, 'original');
        $this->comprobarFactura($rectificativa, false, self::$paypal->codpago, 'rectificativa');
        $this->eliminar($original, $rectificativa);
    }

    public function testRectificativaMarcandoRectificativaPagada(): void
    {
        [$original, $rectificativa] = $this->escenario(false, true);

        $this->comprobarFactura($original, false, self::$paypal->codpago, 'original');
        $this->comprobarFactura($rectificativa, true, self::$paypal->codpago, 'rectificativa');
        $this->eliminar($original, $rectificativa);
    }

    public function testRectificativaMarcandoAmbasPagadas(): void
    {
        [$original, $rectificativa] = $this->escenario(true, true);

        $this->comprobarFactura($original, true, self::$paypal->codpago, 'original');
        $this->comprobarFactura($rectificativa, true, self::$paypal->codpago, 'rectificativa');
        $this->eliminar($original, $rectificativa);
    }

    private static function cargarFormaPago(string $codpago): FormaPago
    {
        $formaPago = new FormaPago();
        self::assertTrue($formaPago->load($codpago), 'forma-pago-no-encontrada-' . $codpago);
        return $formaPago;
    }

    private function cambiarFormaPago(FacturaCliente $factura, string $codpago, bool $pagada): void
    {
        $this->assertTrue($factura->savePaymentMethod($codpago), 'no-se-puede-guardar-forma-pago-' . $factura->codigo);

        foreach ($factura->getReceipts() as $recibo) {
            $recibo->setPaymentMethod($codpago);
            $recibo->pagado = $pagada;
            $this->assertTrue($recibo->save(), 'no-se-puede-guardar-recibo-' . $factura->codigo);
        }

        $factura->reload();
    }

    private function comprobarFactura(FacturaCliente $factura, bool $pagada, string $codpago, string $tipo): void
    {
        $factura->loadFromCode($factura->idfactura);
        $this->assertSame($pagada, $factura->pagada, $tipo . '-pagada-incorrecta');
        $this->assertEquals($codpago, $factura->codpago, $tipo . '-forma-pago-incorrecta');

        foreach ($factura->getReceipts() as $recibo) {
            $this->assertSame($pagada, $recibo->pagado, $tipo . '-recibo-pagado-incorrecto');
            $this->assertEquals($codpago, $recibo->codpago, $tipo . '-recibo-forma-pago-incorrecta');
        }
    }

    private function crearRectificativa(FacturaCliente $original): FacturaCliente
    {
        if ($original->editable) {
            foreach ($original->getAvailableStatus() as $status) {
                if ($status->editable || !$status->activo) {
                    continue;
                }
                $original->idestado = $status->idestado;
                $this->assertTrue($original->save(), 'no-se-puede-bloquear-original');
                break;
            }
        }

        $rectificativa = new FacturaCliente();
        $rectificativa->loadFromData($original->toArray(), $original::dontCopyFields());
        $rectificativa->codigorect = $original->codigo;
        $rectificativa->idfacturarect = $original->idfactura;
        $this->assertTrue($rectificativa->save(), 'no-se-puede-crear-rectificativa');

        foreach ($original->getLines() as $linea) {
            $nuevaLinea = $rectificativa->getNewLine($linea->toArray());
            $nuevaLinea->cantidad = 0 - $linea->cantidad;
            $nuevaLinea->idlinearect = $linea->idlinea;
            $this->assertTrue($nuevaLinea->save(), 'no-se-puede-crear-linea-rectificativa');
        }

        $lineas = $rectificativa->getLines();
        $this->assertTrue(Calculator::calculate($rectificativa, $lineas, true));
        return $rectificativa;
    }

    private function eliminar(FacturaCliente $original, FacturaCliente $rectificativa): void
    {
        $this->assertTrue($rectificativa->delete(), 'no-se-puede-eliminar-rectificativa');
        $original->editable = true;
        $this->assertTrue($original->delete(), 'no-se-puede-eliminar-original');
        $this->assertTrue($original->getSubject()->getDefaultAddress()->delete());
        $this->assertTrue($original->getSubject()->delete());
    }

    private function escenario(bool $originalPagada, bool $rectificativaPagada): array
    {
        $original = $this->getRandomCustomerInvoice();
        $this->assertTrue($original->exists(), 'no-se-puede-crear-original');
        $original->codpago = self::$contado->codpago;
        $this->assertTrue($original->save(), 'no-se-puede-asignar-contado');
        $original->reload();
        $this->assertFalse($original->pagada, 'original-debe-estar-sin-pagar');
        $this->assertEquals(self::$contado->codpago, $original->codpago, 'original-debe-estar-contado');

        $rectificativa = $this->crearRectificativa($original);
        $this->cambiarFormaPago($original, self::$paypal->codpago, $originalPagada);
        $this->cambiarFormaPago($rectificativa, self::$paypal->codpago, $rectificativaPagada);

        return [$original, $rectificativa];
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
