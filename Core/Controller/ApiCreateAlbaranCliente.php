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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\AlbaranCliente;

class ApiCreateAlbaranCliente extends ApiController
{
    protected function runResource(): void
    {
        // si el método no es POST o PUT, devolvemos un error
        if (!in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        // cargamos el cliente
        $codcliente = $this->request->get('codcliente');
        if (empty($codcliente)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'codcliente field is required',
            ]));
            return;
        }
        $cliente = new Cliente();
        if (!$cliente->loadFromCode($codcliente)) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Customer not found',
            ]));
            return;
        }

        // creamos el albaran
        $albaran = new AlbaranCliente();
        $albaran->setSubject($cliente);

        // asignamos el almacén
        $codalmacen = $this->request->get('codalmacen');
        if ($codalmacen && false === $albaran->setWarehouse($codalmacen)) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Warehouse not found',
            ]));
            return;
        }

        // asignamos la fecha
        $fecha = $this->request->get('fecha');
        $hora = $this->request->get('hora', $albaran->hora);
        if ($fecha && false === $albaran->setDate($fecha, $hora)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Invalid date',
            ]));
            return;
        }

        // asignamos la divisa
        $coddivisa = $this->request->get('coddivisa');
        if ($coddivisa) {
            $albaran->setCurrency($coddivisa);
        }

        // asignamos el resto de campos del modelo
        foreach ($albaran->getModelFields() as $key => $field) {
            if ($this->request->request->has($key)) {
                $albaran->{$key} = $this->request->request->get($key);
            }
        }

        $db = new DataBase();
        $db->beginTransaction();

        // guardamos el albaran
        if (false === $albaran->save()) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Error saving the invoice',
            ]));
            $db->rollback();
            return;
        }

        // guardamos las líneas
        if (false === $this->saveLines($albaran)) {
            $db->rollback();
            return;
        }

        $db->commit();

        // devolvemos la respuesta
        $this->response->setContent(json_encode([
            'doc' => $albaran->toArray(),
            'lines' => $albaran->getLines(),
        ]));
    }

    protected function saveLines(AlbaranCliente &$albaran): bool
    {
        if (!$this->request->request->has('lineas')) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'lineas field is required',
            ]));
            return false;
        }

        $lineData = $this->request->request->get('lineas');
        $lineas = json_decode($lineData, true);
        if (!is_array($lineas)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Invalid lines',
            ]));
            return false;
        }

        $newLines = [];
        foreach ($lineas as $line) {
            $newLine = empty($line['referencia'] ?? '') ?
                $albaran->getNewLine() :
                $albaran->getNewProductLine($line['referencia']);

            $newLine->cantidad = (float)($line['cantidad'] ?? 1);
            $newLine->descripcion = $line['descripcion'] ?? $newLine->descripcion ?? '?';
            $newLine->pvpunitario = (float)($line['pvpunitario'] ?? $newLine->pvpunitario);
            $newLine->dtopor = (float)($line['dtopor'] ?? $newLine->dtopor);
            $newLine->dtopor2 = (float)($line['dtopor2'] ?? $newLine->dtopor2);

            if (!empty($line['excepcioniva'] ?? '')) {
                $newLine->excepcioniva = $line['excepcioniva'];
            }

            if (!empty($line['codimpuesto'] ?? '')) {
                $newLine->codimpuesto = $line['codimpuesto'];
            }

            if (!empty($line['suplido'] ?? '')) {
                $newLine->suplido = (bool)$line['suplido'];
            }

            if (!empty($line['mostrar_cantidad'] ?? '')) {
                $newLine->mostrar_cantidad = (bool)$line['mostrar_cantidad'];
            }

            if (!empty($line['mostrar_precio'] ?? '')) {
                $newLine->mostrar_precio = (bool)$line['mostrar_precio'];
            }

            if (!empty($line['salto_pagina'] ?? '')) {
                $newLine->salto_pagina = (bool)$line['salto_pagina'];
            }

            $newLines[] = $newLine;
        }

        // actualizamos los totales y guardamos
        if (false === Calculator::calculate($albaran, $newLines, true)) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Error calculating the invoice',
            ]));
            return false;
        }

        return true;
    }
}
