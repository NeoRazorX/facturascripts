<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of CopyModel
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CopyModel extends Controller
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';
    const TEMPLATE_ASIENTO = 'CopyAsiento';
    const TEMPLATE_PRODUCTO = 'CopyProducto';

    /** @var CodeModel */
    public $codeModel;

    /** @var object */
    public $model;

    /** @var string */
    public $modelClass;

    /** @var string */
    public $modelCode;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'copy';
        $data['icon'] = 'fa-solid fa-cut';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->codeModel = new CodeModel();

        $action = $this->request->get('action');
        if ($action === 'autocomplete') {
            $this->autocompleteAction();
            return;
        } elseif (false === $this->pipeFalse('execAction', $action, $this->codeModel)) {
            return;
        } elseif (false === $this->loadModel()) {
            Tools::log()->warning('record-not-found');
            return;
        }

        // creamos el título de la página
        $this->title .= ' ' . $this->model->primaryDescription();

        // si no es un documento de compra o venta, cargamos su plantilla
        switch ($this->modelClass) {
            case 'Asiento':
                $this->setTemplate(self::TEMPLATE_ASIENTO);
                break;

            case 'Producto':
                $this->setTemplate(self::TEMPLATE_PRODUCTO);
                break;

            default:
                $this->pipe('before', $this->model);
                break;
        }

        if ($action === 'save') {
            switch ($this->modelClass) {
                case 'AlbaranCliente':
                case 'FacturaCliente':
                case 'PedidoCliente':
                case 'PresupuestoCliente':
                    $this->saveSalesDocument();
                    break;

                case 'AlbaranProveedor':
                case 'FacturaProveedor':
                case 'PedidoProveedor':
                case 'PresupuestoProveedor':
                    $this->savePurchaseDocument();
                    break;

                case 'Asiento':
                    $this->saveAccountingEntry();
                    break;

                case 'Producto':
                    $this->saveProduct();
                    break;

                default:
                    $this->pipe('saveAction', $this->model, $this->codeModel);
                    break;
            }
        }
    }

    protected function autocompleteAction(): void
    {
        $this->setTemplate(false);
        $results = [];
        $data = $this->request->request->all();
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term']) as $value) {
            $results[] = [
                'key' => Tools::fixHtml($value->code),
                'value' => Tools::fixHtml($value->description)
            ];
        }

        $this->response->setContent(json_encode($results));
    }

    protected function loadModel(): bool
    {
        $this->modelClass = $this->request->get('model');
        $this->modelCode = $this->request->get('code');
        if (empty($this->modelClass) || empty($this->modelCode)) {
            return false;
        }

        $className = self::MODEL_NAMESPACE . $this->modelClass;
        $this->model = new $className();
        return $this->model->loadFromCode($this->modelCode);
    }

    protected function saveDocumentEnd(BusinessDocument $newDoc): void
    {
        $lines = $newDoc->getLines();
        if (false === Calculator::calculate($newDoc, $lines, true)) {
            Tools::log()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();
        Tools::log()->notice('record-updated-correctly');
        $this->redirect($newDoc->url() . '&action=save-ok');
    }

    protected function saveAccountingEntry(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $this->dataBase->beginTransaction();

        // creamos el nuevo asiento
        $newEntry = new Asiento();
        $newEntry->canal = $this->request->request->get('canal');
        $newEntry->concepto = $this->request->request->get('concepto');

        $company = $this->request->request->get('idempresa');
        $newEntry->idempresa = empty($company) ? $newEntry->idempresa : $company;

        $diario = $this->request->request->get('iddiario');
        $newEntry->iddiario = empty($diario) ? null : $diario;
        $newEntry->importe = $this->model->importe;

        $fecha = $this->request->request->get('fecha');
        if (false === $newEntry->setDate($fecha)) {
            Tools::log()->warning('error-set-date');
            $this->dataBase->rollback();
            return;
        }

        if (false === $this->pipeFalse('beforeSaveAccounting', $newEntry)) {
            $this->dataBase->rollback();
            return;
        }

        if (false === $newEntry->save()) {
            Tools::log()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        // copiamos las líneas del asiento
        foreach ($this->model->getLines() as $line) {
            $newLine = $newEntry->getNewLine();
            $newLine->loadFromData($line->toArray(), ['idasiento', 'idpartida', 'idsubcuenta']);

            if (false === $this->pipeFalse('beforeSaveAccountingLine', $newLine)) {
                $this->dataBase->rollback();
                return;
            }

            if (false === $newLine->save()) {
                Tools::log()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->dataBase->commit();
        Tools::log()->notice('record-updated-correctly');
        $this->redirect($newEntry->url() . '&action=save-ok');
    }

    protected function savePurchaseDocument(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        // buscamos el proveedor
        $subject = new Proveedor();
        if (false === $subject->loadFromCode($this->request->request->get('codproveedor'))) {
            Tools::log()->warning('record-not-found');
            return;
        }

        $this->dataBase->beginTransaction();

        // creamos el nuevo documento
        $className = self::MODEL_NAMESPACE . $this->modelClass;
        $newDoc = new $className();
        $newDoc->setAuthor($this->user);
        $newDoc->setSubject($subject);
        $newDoc->codalmacen = $this->request->request->get('codalmacen');
        $newDoc->setCurrency($this->model->coddivisa);
        $newDoc->codpago = $this->request->request->get('codpago');
        $newDoc->codserie = $this->request->request->get('codserie');
        $newDoc->dtopor1 = (float)$this->request->request->get('dtopor1', 0);
        $newDoc->dtopor2 = (float)$this->request->request->get('dtopor2', 0);
        $newDoc->setDate($this->request->request->get('fecha'), $this->request->request->get('hora'));
        $newDoc->numproveedor = $this->request->request->get('numproveedor');
        $newDoc->observaciones = $this->request->request->get('observaciones');

        if (false === $this->pipeFalse('beforeSavePurchase', $newDoc)) {
            $this->dataBase->rollback();
            return;
        }

        if (false === $newDoc->save()) {
            Tools::log()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        // copiamos las líneas del documento
        foreach ($this->model->getLines() as $line) {
            $newLine = $newDoc->getNewLine($line->toArray());

            if (false === $this->pipeFalse('beforeSavePurchaseLine', $newLine)) {
                $this->dataBase->rollback();
                return;
            }

            if (false === $newLine->save()) {
                Tools::log()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->saveDocumentEnd($newDoc);
    }

    protected function saveSalesDocument(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        // buscamos el cliente
        $subject = new Cliente();
        if (false === $subject->loadFromCode($this->request->request->get('codcliente'))) {
            Tools::log()->warning('record-not-found');
            return;
        }

        $this->dataBase->beginTransaction();

        // creamos el nuevo documento
        $className = self::MODEL_NAMESPACE . $this->modelClass;
        $newDoc = new $className();
        $newDoc->setAuthor($this->user);
        $newDoc->setSubject($subject);
        $newDoc->codalmacen = $this->request->request->get('codalmacen');
        $newDoc->setCurrency($this->model->coddivisa);
        $newDoc->codpago = $this->request->request->get('codpago');
        $newDoc->codserie = $this->request->request->get('codserie');
        $newDoc->dtopor1 = (float)$this->request->request->get('dtopor1', 0);
        $newDoc->dtopor2 = (float)$this->request->request->get('dtopor2', 0);
        $newDoc->setDate($this->request->request->get('fecha'), $this->request->request->get('hora'));
        $newDoc->numero2 = $this->request->request->get('numero2');
        $newDoc->observaciones = $this->request->request->get('observaciones');

        if (false === $this->pipeFalse('beforeSaveSales', $newDoc)) {
            $this->dataBase->rollback();
            return;
        }

        if (false === $newDoc->save()) {
            Tools::log()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        // copiamos las líneas del documento
        foreach ($this->model->getLines() as $line) {
            $newLine = $newDoc->getNewLine($line->toArray());

            if (false === $this->pipeFalse('beforeSaveSalesLine', $newLine)) {
                $this->dataBase->rollback();
                return;
            }

            if (false === $newLine->save()) {
                Tools::log()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->saveDocumentEnd($newDoc);
    }

    protected function saveProduct(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        }

        $this->dataBase->beginTransaction();

        // obtenemos el producto origen
        /** @var Producto $productoOrigen */
        $productoOrigen = $this->model;

        // obtenemos las variantes del producto origen
        $variantesProductoOrigen = $productoOrigen->getVariants();

        // creamos el nuevo producto y copiamos los campos del producto origen
        $productoDestino = new Producto();

        $camposProducto = array_keys((new Producto())->getModelFields());
        $camposExcluidos = ['actualizado', 'descripcion', 'fechaalta', 'idproducto', 'referencia', 'stockfis'];

        foreach ($camposProducto as $campo) {
            if (false === in_array($campo, $camposExcluidos)) {
                $productoDestino->{$campo} = $productoOrigen->{$campo};
            }
        }

        $productoDestino->descripcion = $this->request->request->get('descripcion');
        $productoDestino->referencia = $this->request->request->get('referencia');

        if (false === $this->pipeFalse('beforeSaveProduct', $productoDestino)) {
            $this->dataBase->rollback();
            return;
        }

        if (false === $productoDestino->save()) {
            Tools::log()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        // creamos las nuevas variantes
        $camposVariante = array_keys((new Variante())->getModelFields());
        $camposExcluidos = ['idvariante', 'idproducto', 'referencia', 'stockfis'];

        foreach ($variantesProductoOrigen as $variante) {
            // Como al crear un producto siempre se crea
            // una variante principal aprovechamos esta
            // y la modificamos para que el producto destino
            // no tenga una variante más que el producto origen
            if ($variante === reset($variantesProductoOrigen)) {
                // si es el primer elemento del array, modificamos la variante existente
                $varianteDestino = $productoDestino->getVariants()[0];
            } else {
                $varianteDestino = new Variante();
            }

            foreach ($camposVariante as $campo) {
                if (false === in_array($campo, $camposExcluidos)) {
                    $varianteDestino->{$campo} = $variante->{$campo};
                }
            }

            // asignamos variantes al producto nuevo
            $varianteDestino->idproducto = $productoDestino->idproducto;

            if (false === $this->pipeFalse('beforeSaveVariant', $varianteDestino)) {
                $this->dataBase->rollback();
                return;
            }

            if (false === $varianteDestino->save()) {
                Tools::log()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->dataBase->commit();
        Tools::log()->notice('record-updated-correctly');
        $this->redirect($productoDestino->url() . '&action=save-ok');
    }
}
