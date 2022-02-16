<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of CopyModel
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CopyModel extends Controller
{

    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * @var CodeModel
     */
    public $codeModel;

    /**
     * @var object
     */
    public $model;

    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var string
     */
    public $modelCode;

    /**
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'copy';
        $data['icon'] = 'fas fa-cut';
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
        } elseif (false === $this->loadModel()) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return;
        }

        $this->title .= ' ' . $this->model->primaryDescription();
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
            }
        }
    }

    protected function autocompleteAction()
    {
        $this->setTemplate(false);
        $results = [];
        $utils = $this->toolBox()->utils();
        $data = $this->request->request->all();
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term']) as $value) {
            $results[] = ['key' => $utils->fixHtml($value->code), 'value' => $utils->fixHtml($value->description)];
        }

        $this->response->setContent(json_encode($results));
    }

    /**
     * @return bool
     */
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

    /**
     * @param BusinessDocument $newDoc
     */
    protected function saveDocumentEnd($newDoc)
    {
        $tool = new BusinessDocumentTools();
        $tool->recalculate($newDoc);
        if (false === $newDoc->save()) {
            $this->toolBox()->i18nLog()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        $this->dataBase->commit();
        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->redirect($newDoc->url() . '&action=save-ok');
    }

    protected function savePurchaseDocument()
    {
        $subject = new Proveedor();
        if (false === $subject->loadFromCode($this->request->request->get('codproveedor'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return;
        }

        $this->dataBase->beginTransaction();

        $className = self::MODEL_NAMESPACE . $this->modelClass;
        $newDoc = new $className();
        $newDoc->setAuthor($this->user);
        $newDoc->setSubject($subject);
        $newDoc->codalmacen = $this->request->request->get('codalmacen');
        $newDoc->setCurrency($this->model->coddivisa);
        $newDoc->codpago = $this->request->request->get('codpago');
        $newDoc->codserie = $this->request->request->get('codserie');
        $newDoc->setDate($this->request->request->get('fecha'), $this->request->request->get('hora'));
        $newDoc->numproveedor = $this->request->request->get('numproveedor');
        $newDoc->observaciones = $this->request->request->get('observaciones');
        if (false === $newDoc->save()) {
            $this->toolBox()->i18nLog()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        foreach ($this->model->getLines() as $line) {
            $newLine = $newDoc->getNewLine($line->toArray());
            if (false === $newLine->save()) {
                $this->toolBox()->i18nLog()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->saveDocumentEnd($newDoc);
    }

    protected function saveSalesDocument()
    {
        $subject = new Cliente();
        if (false === $subject->loadFromCode($this->request->request->get('codcliente'))) {
            $this->toolBox()->i18nLog()->warning('record-not-found');
            return;
        }

        $this->dataBase->beginTransaction();

        $className = self::MODEL_NAMESPACE . $this->modelClass;
        $newDoc = new $className();
        $newDoc->setAuthor($this->user);
        $newDoc->setSubject($subject);
        $newDoc->codalmacen = $this->request->request->get('codalmacen');
        $newDoc->setCurrency($this->model->coddivisa);
        $newDoc->codpago = $this->request->request->get('codpago');
        $newDoc->codserie = $this->request->request->get('codserie');
        $newDoc->setDate($this->request->request->get('fecha'), $this->request->request->get('hora'));
        $newDoc->numero2 = $this->request->request->get('numero2');
        $newDoc->observaciones = $this->request->request->get('observaciones');
        if (false === $newDoc->save()) {
            $this->toolBox()->i18nLog()->warning('record-save-error');
            $this->dataBase->rollback();
            return;
        }

        foreach ($this->model->getLines() as $line) {
            $newLine = $newDoc->getNewLine($line->toArray());
            if (false === $newLine->save()) {
                $this->toolBox()->i18nLog()->warning('record-save-error');
                $this->dataBase->rollback();
                return;
            }
        }

        $this->saveDocumentEnd($newDoc);
    }
}
