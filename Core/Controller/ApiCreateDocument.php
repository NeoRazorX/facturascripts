<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Proveedor;

class ApiCreateDocument extends ApiController
{
    /** @var string */
    protected $purchases_model;

    /** @var string */
    protected $sales_model;

    protected function runResource(): void
    {
        if (!in_array($this->request->method(), ['POST', 'PUT'])) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'method-not-allowed',
                ]);
            return;
        }

        $this->loadModel();

        if (!empty($this->purchases_model)) {
            $this->createPurchase();
        } elseif (!empty($this->sales_model)) {
            $this->createSale();
        } else {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => 'invalid-model',
                ]);
        }
    }

    protected function createPurchase(): void
    {
        // cargamos el proveedor
        $codproveedor = $this->request->input('codproveedor');
        if (empty($codproveedor)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'codproveedor field is required',
                ]);
            return;
        }
        $proveedor = new Proveedor();
        if (!$proveedor->load($codproveedor)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'supplier-not-found',
                ]);
            return;
        }

        // creamos el documento
        $class = '\\FacturaScripts\\Dinamic\\Model\\' . $this->purchases_model;
        $doc = new $class();

        // asignamos el sujeto
        if (false === $doc->setSubject($proveedor)) {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json([
                    'status' => 'error',
                    'message' => 'error-assigning-subject',
                ]);
            return;
        }

        // asignamos el almacén
        $codalmacen = $this->request->input('codalmacen');
        if ($codalmacen && false === $doc->setWarehouse($codalmacen)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'warehouse-not-found',
                ]);
            return;
        }

        // asignamos la fecha
        $fecha = $this->request->input('fecha');
        $hora = $this->request->input('hora', $doc->hora);
        if ($fecha && false === $doc->setDate($fecha, $hora)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('invalid-date'),
                ]);
            return;
        }

        // asignamos la divisa
        $coddivisa = $this->request->input('coddivisa');
        if ($coddivisa) {
            $doc->setCurrency($coddivisa);
        }

        // asignamos el resto de campos del modelo
        foreach ($doc->getModelFields() as $key => $field) {
            if ($this->request->request->has($key)) {
                $doc->{$key} = $this->request->input($key);
            }
        }

        $this->db()->beginTransaction();

        // guardamos el documento
        if (false === $doc->save()) {
            $this->db()->rollBack();

            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('record-save-error'),
                ]);
            return;
        }

        // guardamos las líneas
        if (false === $this->saveLines($doc)) {
            $this->db()->rollBack();
            return;
        }

        // procesamos factura pagada si aplica
        $this->processInvoicePaid($doc);

        // confirmamos la transacción
        $this->db()->commit();

        // devolvemos la respuesta
        $this->response
            ->json([
                'doc' => $doc->toArray(),
                'lines' => $doc->getLines(),
            ]);
    }

    protected function createSale(): void
    {
        // cargamos el cliente
        $codcliente = $this->request->input('codcliente');
        if (empty($codcliente)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'codcliente field is required',
                ]);
            return;
        }
        $cliente = new Cliente();
        if (!$cliente->load($codcliente)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'customer-not-found',
                ]);
            return;
        }

        // creamos el documento
        $class = '\\FacturaScripts\\Dinamic\\Model\\' . $this->sales_model;
        $doc = new $class();

        // asignamos el sujeto
        if (false === $doc->setSubject($cliente)) {
            $this->response
                ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->json([
                    'status' => 'error',
                    'message' => 'error-assigning-subject',
                ]);
            return;
        }

        // asignamos el almacén
        $codalmacen = $this->request->input('codalmacen');
        if ($codalmacen && false === $doc->setWarehouse($codalmacen)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'warehouse-not-found',
                ]);
            return;
        }

        // asignamos la fecha
        $fecha = $this->request->input('fecha');
        $hora = $this->request->input('hora', $doc->hora);
        if ($fecha && false === $doc->setDate($fecha, $hora)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('invalid-date'),
                ]);
            return;
        }

        // asignamos la divisa
        $coddivisa = $this->request->input('coddivisa');
        if ($coddivisa) {
            $doc->setCurrency($coddivisa);
        }

        // asignamos el resto de campos del modelo
        foreach ($doc->getModelFields() as $key => $field) {
            if ($this->request->request->has($key)) {
                $doc->{$key} = $this->request->input($key);
            }
        }

        $this->db()->beginTransaction();

        // guardamos el documento
        if (false === $doc->save()) {
            $this->db()->rollBack();

            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('record-save-error'),
                ]);
            return;
        }

        // guardamos las líneas
        if (false === $this->saveLines($doc)) {
            $this->db()->rollBack();
            return;
        }

        // procesamos factura pagada si aplica
        $this->processInvoicePaid($doc);

        // confirmamos la transacción
        $this->db()->commit();

        // devolvemos la respuesta
        $this->response
            ->json([
                'doc' => $doc->toArray(),
                'lines' => $doc->getLines(),
            ]);
    }

    protected function loadModel(): void
    {
        switch ($this->getUriParam(2)) {
            case 'crearAlbaranCliente':
                $this->sales_model = 'AlbaranCliente';
                break;

            case 'crearAlbaranProveedor':
                $this->purchases_model = 'AlbaranProveedor';
                break;

            case 'crearFacturaCliente':
                $this->sales_model = 'FacturaCliente';
                break;

            case 'crearFacturaProveedor':
                $this->purchases_model = 'FacturaProveedor';
                break;

            case 'crearPedidoCliente':
                $this->sales_model = 'PedidoCliente';
                break;

            case 'crearPedidoProveedor':
                $this->purchases_model = 'PedidoProveedor';
                break;

            case 'crearPresupuestoCliente':
                $this->sales_model = 'PresupuestoCliente';
                break;

            case 'crearPresupuestoProveedor':
                $this->purchases_model = 'PresupuestoProveedor';
                break;
        }
    }

    protected function processInvoicePaid(BusinessDocument &$doc): void
    {
        if ($doc->hasColumn('idfactura') &&
            $doc->hasColumn('pagada') &&
            $this->request->request->getBool('pagada', false)) {
            foreach ($doc->getReceipts() as $receipt) {
                $receipt->pagado = true;
                $receipt->save();
            }

            // recargamos la factura
            $doc->reload();
        }
    }

    protected function saveLines(BusinessDocument &$documento): bool
    {
        if (!$this->request->request->has('lineas')) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'lineas field is required',
                ]);
            return false;
        }

        $lineData = $this->request->input('lineas');
        $lineas = json_decode($lineData, true);
        if (!is_array($lineas)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'Invalid lines',
                ]);
            return false;
        }

        $newLines = [];
        foreach ($lineas as $line) {
            $newLine = empty($line['referencia'] ?? '') ?
                $documento->getNewLine() :
                $documento->getNewProductLine($line['referencia']);

            $newLine->cantidad = (float)($line['cantidad'] ?? 1);
            $newLine->descripcion = $line['descripcion'] ?? $newLine->descripcion ?? '?';
            $newLine->pvpunitario = (float)($line['pvpunitario'] ?? $newLine->pvpunitario);
            $newLine->dtopor = (float)($line['dtopor'] ?? $newLine->dtopor);
            $newLine->dtopor2 = (float)($line['dtopor2'] ?? $newLine->dtopor2);

            if (isset($line['excepcioniva'])) {
                $newLine->excepcioniva = $line['excepcioniva'] === 'null' ? null : $line['excepcioniva'];
            }

            if (isset($line['codimpuesto'])) {
                $newCodimpuesto = $line['codimpuesto'] === 'null' ? null : $line['codimpuesto'];
                if ($newCodimpuesto !== $newLine->codimpuesto) {
                    $newLine->setTax($newCodimpuesto);
                }
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
        if (false === Calculator::calculate($documento, $newLines, true)) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('error-calculating-totals'),
                ]);
            return false;
        }

        return true;
    }
}
