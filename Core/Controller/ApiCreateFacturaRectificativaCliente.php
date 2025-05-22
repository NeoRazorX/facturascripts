<?php declare(strict_types=1);
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
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\FacturaCliente;

class ApiCreateFacturaRectificativaCliente extends ApiController
{
    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected DataBase $dataBase;

    public function __construct(string $className, string $url = '')
    {
        parent::__construct($className, $url);

        $this->dataBase = new DataBase();
    }

    protected function runResource(): void
    {
        // si el método no es POST o PUT, devolvemos un error
        if (!in_array($this->request->method(), ['POST', 'PUT'])) {
            $this->response->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        // comprobamos que los datos obligatorios nos llegan en la request
        $required_fields = ['idfactura', 'fecha'];
        foreach ($required_fields as $field) {
            $value = $this->request->get($field);
            if (empty($value)) {
                $this->response->setHttpCode(Response::HTTP_BAD_REQUEST);
                $this->response->setContent(json_encode([
                    'status' => 'error',
                    'message' => $field . ' field is required',
                ]));
                return;
            }
        }

        $invoice = $this->newRefundAction();
        if ($invoice) {
            $this->response->setContent(json_encode([
                'doc' => $invoice->toArray(),
                'lines' => $invoice->getLines(),
            ]));
        }
    }

    protected function newRefundAction(): ?FacturaCliente
    {
        $invoice = new FacturaCliente();
        $code = $this->request->request->get('idfactura');
        if (empty($code) || false === $invoice->loadFromCode($code)) {
            $this->sendError('record-not-found', Response::HTTP_NOT_FOUND);
            return null;
        }

        $lines = [];
        $invoiceLines = $invoice->getLines();
        foreach ($invoiceLines as $line) {
            $quantity = (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            if (!empty($quantity)) {
                $lines[] = $line;
            }
        }

        // si no se especifican cantidades de las lineas,
        // incluimos todas las lineas en la factura rectificativa.
        if (empty($lines)) {
            $lines = $invoiceLines;
        }

        $this->dataBase->beginTransaction();

        if ($invoice->editable) {
            foreach ($invoice->getAvailableStatus() as $status) {
                if ($status->editable || !$status->activo) {
                    continue;
                }

                $invoice->idestado = $status->idestado;
                if (false === $invoice->save()) {
                    $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
                    $this->dataBase->rollback();
                    return null;
                }
            }
        }

        $newRefund = new FacturaCliente();
        $newRefund->loadFromData($invoice->toArray(), $invoice::dontCopyFields());
        $newRefund->codigorect = $invoice->codigo;
        $newRefund->codserie = $this->request->request->get('codserie') ?? $invoice->codserie;
        $newRefund->idfacturarect = $invoice->idfactura;
        $newRefund->nick = $this->request->request->get('nick');
        $newRefund->observaciones = $this->request->request->get('observaciones');

        $date = $this->request->request->get('fecha');
        $hour = $this->request->request->get('hora');
        if (false === $newRefund->setDate($date, $hour)) {
            $this->sendError('error-set-date', Response::HTTP_BAD_REQUEST);
            $this->dataBase->rollback();
            return null;
        }

        if (false === $newRefund->save()) {
            $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->dataBase->rollback();
            return null;
        }

        foreach ($lines as $line) {
            $newLine = $newRefund->getNewLine($line->toArray());
            $newLine->cantidad = 0 - (float)$this->request->request->get('refund_' . $line->primaryColumnValue(), $line->cantidad);
            $newLine->idlinearect = $line->idlinea;
            if (false === $newLine->save()) {
                $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
                $this->dataBase->rollback();
                return null;
            }
        }

        $newLines = $newRefund->getLines();
        $newRefund->idestado = $invoice->idestado;
        if (false === Calculator::calculate($newRefund, $newLines, true)) {
            $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->dataBase->rollback();
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
        $newRefund->idestado = $this->request->request->get('idestado');
        if (false === $newRefund->save()) {
            $this->sendError('record-save-error', Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->dataBase->rollback();
            return null;
        }

        $this->dataBase->commit();
        Tools::log()->notice('record-updated-correctly');

        return $newRefund;
    }

    private function sendError(string $message, int $http_code): void
    {
        $this->response->setHttpCode($http_code);
        $this->response->setContent(json_encode([
            'status' => 'error',
            'message' => Tools::lang()->trans($message),
        ]));
    }
}
