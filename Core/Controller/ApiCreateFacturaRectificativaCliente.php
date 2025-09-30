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
use FacturaScripts\Core\Response;
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

        // si no se especifican cantidades de las lineas,
        // incluimos todas las lineas en la factura rectificativa.
        if (empty($lines)) {
            $lines = $invoiceLines;
        }

        $this->db()->beginTransaction();

        if ($invoice->editable) {
            foreach ($invoice->getAvailableStatus() as $status) {
                if ($status->editable || !$status->activo) {
                    continue;
                }

                $invoice->idestado = $status->idestado;
                if (false === $invoice->save()) {
                    $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
                    $this->db()->rollback();
                    return null;
                }
            }
        }

        $newRefund = new FacturaCliente();
        $newRefund->loadFromData($invoice->toArray(), $invoice::dontCopyFields());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $this->request->input('codserie') ?? $invoice->codserie;
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->nick = $this->request->input('nick');
        $newRefund->observaciones = $this->request->input('observaciones');

        $date = $this->request->input('fecha');
        $hour = $this->request->input('hora');
        if (false === $newRefund->setDate($date, $hour)) {
            $this->sendError('error-set-date', Response::HTTP_BAD_REQUEST);
            $this->db()->rollback();
            return null;
        }

        if (false === $newRefund->save()) {
            $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->db()->rollback();
            return null;
        }

        foreach ($lines as $line) {
            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - (float)$this->request->input('refund_' . $line->id(), $line->cantidad);
            $newLine->idlinearect = $line->idlinea;
            if (false === $newLine->save()) {
                $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
                $this->db()->rollback();
                return null;
            }
        }

        $newLines = $newRefund->getLines();
        $newRefund->idestado = $invoice->idestado;
        if (false === Calculator::calculate($newRefund, $newLines, true)) {
            $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->db()->rollback();
            return null;
        }

        // si la factura estaba pagada, marcamos los recibos de la nueva como pagados
        if ($invoice->pagada) {
            foreach ($newRefund->getReceipts() as $receipt) {
                $receipt->pagado = true;
                $receipt->save();
            }
        }

        // asignamos el estado de la factura
        $newRefund->idestado = $this->request->input('idestado');
        if (false === $newRefund->save()) {
            $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->db()->rollback();
            return null;
        }

        $this->db()->commit();
        Tools::log()->notice('record-updated-correctly');

        return $newRefund;
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
