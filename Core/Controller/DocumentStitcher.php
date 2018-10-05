<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DocumentStitcher
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class DocumentStitcher extends Base\Controller
{

    /**
     * Model name source.
     *
     * @var string
     */
    public $modelName;

    /**
     * Array of document primary keys.
     *
     * @var array
     */
    public $codes;

    /**
     * Array of documents.
     *
     * @var array
     */
    public $docs;

    /**
     * Array of lines of documents.
     *
     * @var array
     */
    public $linesDocs;

    /**
     * List of document status where can be approved.
     *
     * @var Model\EstadoDocumento[]
     */
    public $approveTo;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'sales';
        $pageData['title'] = 'group-or-split';
        $pageData['icon'] = 'fas fa-thumb-tack';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param Model\User                 $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Read model received
        $this->modelName = $this->request->get('model', '');

        // Operations with data, before execute action
        if (!$this->execPrevious($this->modelName)) {
            return;
        }

        // Store action to execute
        $action = $this->request->get('action', '');
        // Operations with data, after execute action
        $this->execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $modelName
     *
     * @return bool
     */
    protected function execPrevious($modelName)
    {
        $this->approveTo = $this->getDestinyDoc($modelName);

        switch ($modelName) {
            case 'AlbaranCliente':
                /// no break
            case 'AlbaranProveedor':
                /// no break
            case 'PedidoCliente':
                /// no break
            case 'PedidoProveedor':
                /// no break
            case 'PresupuestoCliente':
                /// no break
            case 'PresupuestoProveedor':
                $this->codes = \explode(',', trim($this->request->get('codes', '')));
                if (empty($this->codes)) {
                    $this->miniLog->alert('no-codes-received');
                    return false;
                }

                foreach ($this->codes as $code) {
                    $modelClass = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;
                    $doc = new $modelClass();
                    if ($doc->loadFromCode($code)) {
                        $this->docs[$code] = $doc;
                        $this->linesDocs[$code] = $doc->getLines();
                    }
                }
                break;

            default:
                $this->miniLog->alert('no-modelname-data-received-or-not-supported');
                break;
        }

        return true;
    }

    /**
     * Runs the controller actions after data read.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'FacturaCliente':
                /// no break
            case 'FacturaProveedor':
                /// no break
            case 'AlbaranCliente':
                /// no break
            case 'AlbaranProveedor':
                /// no break
            case 'PedidoCliente':
                /// no break
            case 'PedidoProveedor':
                /// no break
            case 'PresupuestoCliente':
                /// no break
            case 'PresupuestoProveedor':
                $this->approveDocument($action);
                break;
        }
        return true;
    }

    /**
     * Returns a list of destiny docs for this modelName.
     *
     * @param string $modelName
     *
     * @return Model\EstadoDocumento[]
     */
    private function getDestinyDoc($modelName): array
    {
        $docStatus = new Model\EstadoDocumento();
        $where = [new Base\DataBase\DataBaseWhere('nombre', 'Aprobado')];
        switch (true) {
            case strpos($modelName, 'Proveedor') !== false:
                /// no break
            case strpos($modelName, 'Cliente') !== false:
                $where[] = new Base\DataBase\DataBaseWhere('tipodoc', $modelName);
                break;

            default:
                $this->miniLog->alert('no-modelname-data-received-or-not-supported');
                break;
        }

        $approveTo = [];
        if (!empty($where)) {
            $approveTo = $docStatus->all($where);
        }
        return $approveTo;
    }

    /**
     * Generate next document.
     *
     * @param string $generateDoc
     *
     * @return bool
     */
    private function approveDocument($generateDoc): bool
    {
        $data = $this->request->request->all();
        $modelName = $data['model'];
        $lines = $data['line'];
        $prevLines = $data['prevline'];
        $docs = $data['doc'];
        $modelClass = 'FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $docSource = new $modelClass();

        if (empty($docs) || !isset($docs[0])) {
            $this->miniLog->error($this->i18n->trans('document-not-available'));
            return false;
        }

        $docSource->loadFromCode($docs[0]);
        unset($docs[0]);

        // Look for EstadoDocumento as user was selected 'Aprobado' TODO: must be a translatable string
        $docStatus = new Model\EstadoDocumento();
        $where = [
            new Base\DataBase\DataBaseWhere('tipodoc', $modelName),
            new Base\DataBase\DataBaseWhere('generadoc', $generateDoc)
        ];
        if (!$docStatus->loadFromCode('', $where)) {
            $this->miniLog->error($this->i18n->trans('document-status-not-available'));
            return false;
        }

        // Update quantity based on form values
        $docRevisedLines = [];
        foreach ($lines as $line => $quantity) {
            if ($lines[$line] !== $prevLines[$line]) {
                $docRevisedLines[$line] = $quantity;
            }
        }

        $docGenerator = new BusinessDocumentGenerator();
        if (!$docGenerator->generate($docSource, $docStatus->generadoc, $docRevisedLines)) {
            $this->miniLog->error($this->i18n->trans('document-not-generated'));
            return false;
        }

        foreach ($docs as $doc) {
            if ($docSource->loadFromCode($doc)) {
                $docGenerator->addLinesFrom($docSource, $docRevisedLines);
                continue;
            }
        }

        $docDestiny = $docGenerator->getNewDoc();
        $businessDocTools = new BusinessDocumentTools();
        $businessDocTools->recalculate($docDestiny);
        if ($docDestiny->save()) {
            $this->miniLog->notice(
                $this->i18n->trans(
                    'document-generated-successfully',
                    [
                        '%code%' => $docDestiny->codigo,
                        '%url%' => $docDestiny->url()
                    ]
                )
            );
            return true;
        }

        $this->miniLog->error($this->i18n->trans('document-not-generated'));
        return false;
    }
}
