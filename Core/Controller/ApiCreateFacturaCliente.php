<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;

class ApiCreateFacturaCliente extends ApiController
{
    protected function runResource(): void
    {
        // si el método no es POST o PUT, devolvemos un error
        if (!in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        // cargamos el cliente
        $codcliente = $this->request->get('codcliente');
        if (empty($codcliente)) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'codcliente is required',
            ]));
            return;
        }
        $cliente = new Cliente();
        if (!$cliente->loadFromCode($codcliente)) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Customer not found',
            ]));
            return;
        }

        // creamos la factura
        $factura = new FacturaCliente();
        $factura->setSubject($cliente);

        // asignamos el almacén
        $codalmacen = $this->request->get('codalmacen');
        if ($codalmacen && false === $factura->setWarehouse($codalmacen)) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Warehouse not found',
            ]));
            return;
        }

        // asignamos la fecha
        $fecha = $this->request->get('fecha');
        $hora = $this->request->get('hora', $factura->hora);
        if ($fecha && false === $factura->setDate($fecha, $hora)) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Invalid date',
            ]));
            return;
        }

        // asignamos la serie y forma de pago
        $factura->codserie = $this->request->get('codserie', $factura->codserie);
        $factura->codpago = $this->request->get('codpago', $factura->codpago);

        // asignamos la divisa
        $coddivisa = $this->request->get('coddivisa');
        if ($coddivisa) {
            $factura->setCurrency($coddivisa);
        }

        // guardamos la factura
        if (false === $factura->save()) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Error saving the invoice',
            ]));
            return;
        }

        // guardamos las líneas
        $this->saveLines($factura);
    }

    protected function saveLines(FacturaCliente &$factura): void
    {
        if (!$this->request->request->has('lineas')) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Lines are required',
            ]));
            return;
        }

        $lineData = $this->request->request->get('lineas');
        $lineas = json_decode($lineData, true);
        if (!is_array($lineas)) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Invalid lines',
            ]));
            return;
        }

        $newLines = [];
        foreach ($lineas as $line) {
            $newLine = empty($line['referencia']) ?
                $factura->getNewLine() :
                $factura->getNewLine($line['referencia']);

            $newLine->cantidad = (float)($line['cantidad'] ?? 1);
            $newLine->descripcion = $line['descripcion'] ?? $newLine->descripcion ?? '?';
            $newLine->pvpunitario = (float)($line['pvpunitario'] ?? 0);
            $newLine->dtopor = (float)($line['dtopor'] ?? 0);
            $newLine->dtopor2 = (float)($line['dtopor2'] ?? 0);

            if ($line['excepcioniva']) {
                $newLine->excepcioniva = $line['excepcioniva'];
            }

            if ($line['codimpuesto']) {
                $newLine->codimpuesto = $line['codimpuesto'];
            }

            $newLines[] = $newLine;
        }

        // actualizamos los totales y guardamos
        if (false === Calculator::calculate($factura, $newLines, true)) {
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Error calculating the invoice',
            ]));
            return;
        }

        // devolvemos la respuesta
        $this->response->setContent(json_encode([
            'doc' => $factura->toArray(),
            'lines' => $factura->getLines(),
        ]));
    }
}
