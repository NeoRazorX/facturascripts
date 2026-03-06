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

use FacturaScripts\Core\Params\RefundInvoiceParams;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Service\InvoiceManager;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaCliente;

class ApiCreateFacturaRectificativaCliente extends ApiController
{
    protected function runResource(): void
    {
        // si el método no es POST o PUT, devolvemos un error
        if (!in_array($this->request->method(), ['POST', 'PUT'])) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'Method not allowed',
                ]);
            return;
        }

        // comprobamos que los datos obligatorios nos llegan en la request
        $required_fields = ['idfactura', 'fecha'];
        foreach ($required_fields as $field) {
            $value = $this->request->input($field);
            if (empty($value)) {
                $this->response
                    ->setHttpCode(Response::HTTP_BAD_REQUEST)
                    ->json([
                        'status' => 'error',
                        'message' => $field . ' field is required',
                    ]);
                return;
            }
        }

        $invoice = $this->newRefundAction();
        if ($invoice) {
            $this->response->json([
                'doc' => $invoice->toArray(),
                'lines' => $invoice->getLines(),
            ]);
        }
    }

    protected function newRefundAction(): ?FacturaCliente
    {
        $invoice = new FacturaCliente();
        $code = $this->request->input('idfactura');
        if (empty($code) || false === $invoice->load($code)) {
            $this->sendError('record-not-found', Response::HTTP_NOT_FOUND);
            return null;
        }

        $lines = [];
        $invoiceLines = $invoice->getLines();
        foreach ($invoiceLines as $line) {
            $quantity = (float)$this->request->input('refund_' . $line->id(), '0');
            if (!empty($quantity)) {
                $lines[] = $line;
            }
        }

        $params = new RefundInvoiceParams(
            lines: $lines,
            codserie: $this->request->input('codserie', ''),
            fecha: $this->request->input('fecha'),
            hora: $this->request->input('hora'),
            observaciones: $this->request->input('observaciones', ''),
            idestado: $this->request->input('idestado', ''),
            nick: $this->request->input('nick', ''),
            includeAllLinesIfEmpty: true
        );

        return InvoiceManager::createRefund($invoice, $params);
    }

    private function sendError(string $message, int $http_code): void
    {
        $this->response
            ->setHttpCode($http_code)
            ->json([
                'status' => 'error',
                'message' => Tools::trans($message),
            ]);
    }
}
