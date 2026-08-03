<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ApiBusinessDocumentTrait;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;

/**
 * Edita un documento de negocio existente (factura, albarán, pedido o presupuesto)
 * en una sola llamada: modificación tanto el documento como las líneas,
 * recalculando totales y manteniendo el stock consistente.
 */
class ApiEditDocument extends ApiController
{
    use ApiBusinessDocumentTrait;

    /** @var string */
    protected $model;

    /**
     * Comprueba que la empresa (idempresa) no se cambia, porque rompería la
     * numeración, la contabilidad y el stock asociado al documento.
     */
    protected function checkCompany(BusinessDocument &$doc): bool
    {
        if (false === $doc->hasColumn('idempresa') || false === $this->request->request->has('idempresa')) {
            return true;
        }

        if ($this->request->input('idempresa') != $doc->idempresa) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('cannot-change-company'),
                ]);
            return false;
        }

        return true;
    }

    /**
     * Comprueba que el sujeto (codcliente / codproveedor) no se cambia.
     */
    protected function checkSubject(BusinessDocument &$doc): bool
    {
        $column = $doc->subjectColumn();
        if (false === $this->request->request->has($column)) {
            return true;
        }

        if ($this->request->input($column) != $doc->{$column}) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('cannot-change-subject'),
                ]);
            return false;
        }

        return true;
    }

    /**
     * Comprueba que el almacén (codalmacen) no se cambia, porque descuadraría
     * el stock de las líneas existentes.
     */
    protected function checkWarehouse(BusinessDocument &$doc): bool
    {
        if (false === $doc->hasColumn('codalmacen') || false === $this->request->request->has('codalmacen')) {
            return true;
        }

        if ($this->request->input('codalmacen') != $doc->codalmacen) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('cannot-change-warehouse'),
                ]);
            return false;
        }

        return true;
    }

    protected function editDocument(BusinessDocument &$doc): bool
    {
        // si el documento no es editable, solo se admiten los campos desbloqueados
        if (false === $doc->editable) {
            return $this->editLockedDocument($doc);
        }

        // el sujeto no se puede cambiar en edición
        if (false === $this->checkSubject($doc)) {
            return false;
        }

        // la empresa no se puede cambiar en edición
        if (false === $this->checkCompany($doc)) {
            return false;
        }

        // el almacén no se puede cambiar en edición
        if (false === $this->checkWarehouse($doc)) {
            return false;
        }

        // actualizamos la cabecera
        if (false === $this->updateHeader($doc)) {
            return false;
        }

        // sincronizamos las líneas (alta, modificación y baja)
        if (false === $this->syncLines($doc)) {
            return false;
        }

        // procesamos factura pagada si aplica
        $this->processInvoicePaid($doc);

        return true;
    }

    /**
     * Edita un documento no editable: solo se permiten los campos desbloqueados
     * (femail, idestado, idestado_ant, numdocs, pagada). Cualquier intento de
     * modificar la cabecera o las líneas se rechaza con 422.
     */
    protected function editLockedDocument(BusinessDocument &$doc): bool
    {
        $unlocked = TransformerDocument::getUnlockedFields();

        // no se permite modificar líneas en un documento no editable
        if ($this->request->request->has('lineas')) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('non-editable-document'),
                ]);
            return false;
        }

        // comprobamos que no se intenta modificar ningún campo bloqueado
        foreach ($doc->getModelFields() as $key => $field) {
            if ($this->request->request->has($key) && false === in_array($key, $unlocked, true)) {
                $this->response
                    ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->json([
                        'status' => 'error',
                        'message' => Tools::trans('non-editable-document'),
                    ]);
                return false;
            }
        }

        // aplicamos los campos desbloqueados (pagada se gestiona aparte)
        foreach ($unlocked as $key) {
            if ($key === 'pagada') {
                continue;
            }
            if ($doc->hasColumn($key) && $this->request->request->has($key)) {
                $doc->{$key} = $this->request->input($key);
            }
        }

        if (false === $doc->save()) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('record-save-error'),
                ]);
            return false;
        }

        // procesamos factura pagada si aplica
        $this->processInvoicePaid($doc);

        return true;
    }

    protected function loadModel(): bool
    {
        switch ($this->getUriParam(2)) {
            case 'editarAlbaranCliente':
                $this->model = 'AlbaranCliente';
                return true;

            case 'editarAlbaranProveedor':
                $this->model = 'AlbaranProveedor';
                return true;

            case 'editarFacturaCliente':
                $this->model = 'FacturaCliente';
                return true;

            case 'editarFacturaProveedor':
                $this->model = 'FacturaProveedor';
                return true;

            case 'editarPedidoCliente':
                $this->model = 'PedidoCliente';
                return true;

            case 'editarPedidoProveedor':
                $this->model = 'PedidoProveedor';
                return true;

            case 'editarPresupuestoCliente':
                $this->model = 'PresupuestoCliente';
                return true;

            case 'editarPresupuestoProveedor':
                $this->model = 'PresupuestoProveedor';
                return true;
        }

        return false;
    }

    protected function processInvoicePaid(BusinessDocument &$doc): void
    {
        if (
            $doc->hasColumn('idfactura') &&
            $doc->hasColumn('pagada') &&
            $this->request->request->has('pagada') &&
            $this->request->request->getBool('pagada', false)
        ) {
            foreach ($doc->getReceipts() as $receipt) {
                $receipt->pagado = true;
                $receipt->save();
            }

            // recargamos la factura
            $doc->reload();
        }
    }

    protected function runResource(): void
    {
        if (false === in_array($this->request->method(), ['PUT', 'PATCH'], true)) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'method-not-allowed',
                ]);
            return;
        }

        // determinamos el modelo a partir de la URL
        if (false === $this->loadModel()) {
            $this->response
                ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->json([
                    'status' => 'error',
                    'message' => 'invalid-model',
                ]);
            return;
        }

        // tomamos la clave primaria del parámetro de la URL
        $code = $this->getUriParam(3);
        if (empty($code)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'record-not-specified',
                ]);
            return;
        }

        // cargamos el documento
        $class = '\\FacturaScripts\\Dinamic\\Model\\' . $this->model;
        $doc = new $class();
        if (false === $doc->load($code)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'record-not-found',
                ]);
            return;
        }

        $this->db()->beginTransaction();

        if (false === $this->editDocument($doc)) {
            $this->db()->rollBack();
            return;
        }

        // confirmamos la transacción
        $this->db()->commit();

        // recargamos para devolver los totales actualizados
        $doc->reload();

        $this->response
            ->json([
                'doc' => $doc->toArray(),
                'lines' => $doc->getLines(),
            ]);
    }

    /**
     * Sincroniza las líneas del documento (full sync): las líneas con idlinea
     * existente se modifican, las que no traen idlinea se crean y las líneas
     * existentes ausentes del payload se borran (revirtiendo el stock).
     */
    protected function syncLines(BusinessDocument &$doc): bool
    {
        if (false === $this->request->request->has('lineas')) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'lineas field is required',
                ]);
            return false;
        }

        $lineas = json_decode($this->request->input('lineas'), true);
        if (false === is_array($lineas)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'Invalid lines',
                ]);
            return false;
        }

        // indexamos las líneas existentes por su clave primaria (idlinea)
        $existingLines = [];
        foreach ($doc->getLines() as $line) {
            $existingLines[(int)$line->primaryColumnValue()] = $line;
        }

        $newLines = [];
        $keepIds = [];
        foreach ($lineas as $lineData) {
            $idlinea = (int)($lineData['idlinea'] ?? 0);
            if ($idlinea > 0 && isset($existingLines[$idlinea])) {
                // modificamos una línea existente
                $line = $existingLines[$idlinea];
                $keepIds[] = $idlinea;
                $this->applyLineFields($line, $lineData, false);
            } else {
                // creamos una línea nueva
                $line = empty($lineData['referencia'] ?? '') ?
                    $doc->getNewLine() :
                    $doc->getNewProductLine($lineData['referencia']);
                $this->applyLineFields($line, $lineData, true);
            }

            $newLines[] = $line;
        }

        // borramos las líneas ausentes del payload con delete() para revertir el stock.
        // Calculator solo guarda las líneas que recibe; no borra las eliminadas.
        foreach ($existingLines as $id => $line) {
            if (in_array($id, $keepIds, true)) {
                continue;
            }
            if (false === $line->delete()) {
                $this->response
                    ->setHttpCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->json([
                        'status' => 'error',
                        'message' => Tools::trans('record-delete-error'),
                    ]);
                return false;
            }
        }

        // recalculamos los totales y guardamos
        if (false === Calculator::calculate($doc, $newLines, true)) {
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

    /**
     * Actualiza la cabecera del documento con los campos permitidos. No toca el
     * sujeto ni el almacén (ya validados como inmutables), ni la clave primaria,
     * la numeración (numero, codigo, codejercicio) ni el estado (idestado), que
     * deben cambiarse por sus mecanismos propios y no por asignación directa.
     */
    protected function updateHeader(BusinessDocument &$doc): bool
    {
        // asignamos la fecha y la hora
        $fecha = $this->request->input('fecha');
        $hora = $this->request->input('hora', $doc->hora);
        if ($fecha && false === $doc->setDate($fecha, $hora)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => Tools::trans('invalid-date'),
                ]);
            return false;
        }

        // asignamos la divisa
        $coddivisa = $this->request->input('coddivisa');
        if ($coddivisa) {
            $doc->setCurrency($coddivisa);
        }

        // asignamos el resto de campos del modelo, excepto los protegidos: sujeto,
        // empresa, almacén, fecha/hora/divisa (ya gestionados arriba), la clave
        // primaria, la numeración (numero, codigo, codejercicio) y el estado
        // (idestado/idestado_ant), que rompen integridad si se asignan a pelo
        $protected = [
            $doc->primaryColumn(), $doc->subjectColumn(), 'idempresa', 'codalmacen',
            'fecha', 'hora', 'coddivisa', 'numero', 'codigo', 'codejercicio',
            'idestado', 'idestado_ant',
        ];
        foreach ($doc->getModelFields() as $key => $field) {
            if (in_array($key, $protected, true)) {
                continue;
            }
            if ($this->request->request->has($key)) {
                $doc->{$key} = $this->request->input($key);
            }
        }

        return true;
    }
}
