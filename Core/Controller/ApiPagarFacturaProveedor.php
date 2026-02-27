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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaProveedor;

class ApiPagarFacturaProveedor extends ApiController
{
    protected function runResource(): void
    {
        if (!in_array($this->request->method(), ['POST', 'PUT'])) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'Method not allowed',
                ]);
            return;
        }

        // comprobamos los parámetros
        if ($this->getUriParam(3) === null) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'missing-id-parameter',
                ]);
            return;
        } elseif (!$this->request->request->has('pagada')) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'missing-pagada-parameter',
                ]);
            return;
        }

        // cargamos la factura
        $factura = new FacturaProveedor();
        $id = $this->getUriParam(3);
        if (!$factura->load($id)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'invoice-not-found',
                ]);
            return;
        }

        // comprobamos si hay cambios
        $pagada = $this->request->request->getBool('pagada');
        if ($factura->pagada == $pagada) {
            $this->response
                ->setHttpCode(Response::HTTP_OK)
                ->json([
                    'ok' => Tools::trans('no-changes'),
                    'data' => $factura->toArray(),
                ]);
            return;
        } elseif ($pagada) {
            $this->payReceipts($factura);
            return;
        }

        $this->unpayReceipts($factura);
    }

    protected function payReceipts(FacturaProveedor &$factura): void
    {
        // marcamos todos los recibos como pagados
        foreach ($factura->getReceipts() as $receipt) {
            $receipt->pagado = true;
            $receipt->fechapago = $this->request->request->get('fechapago', $receipt->fechapago);
            $receipt->codpago = $this->request->request->get('codpago', $receipt->codpago);
            if (false === $receipt->save()) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json([
                        'status' => 'error',
                        'message' => Tools::trans('record-save-error'),
                    ]);
                return;
            }
        }

        // recargamos la factura
        $factura->reload();

        // devolvemos la factura actualizada
        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json([
                'ok' => Tools::trans('record-updated-correctly'),
                'data' => $factura->toArray(),
            ]);
    }

    protected function unpayReceipts(FacturaProveedor &$factura): void
    {
        // marcamos todos los recibos como no pagados
        foreach ($factura->getReceipts() as $receipt) {
            $receipt->pagado = false;
            $receipt->fechapago = null;
            if (false === $receipt->save()) {
                $this->response
                    ->setHttpCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->json([
                        'status' => 'error',
                        'message' => Tools::trans('record-save-error'),
                    ]);
                return;
            }
        }

        // recargamos la factura
        $factura->reload();

        // devolvemos la factura actualizada
        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json([
                'ok' => Tools::trans('record-updated-correctly'),
                'data' => $factura->toArray(),
            ]);
    }
}
