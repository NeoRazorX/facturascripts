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
     * Array of document primary keys.
     *
     * @var array
     */
    public $codes = [];

    /**
     *
     * @var array
     */
    public $documents = [];

    /**
     * Model name source.
     *
     * @var string
     */
    public $modelName;

    /**
     * 
     * @param string $description
     *
     * @return string
     */
    public function fixDescription($description)
    {
        return nl2br(Base\Utils::fixHtml($description));
    }

    /**
     * 
     * @return array
     */
    public function getDestinyDocs()
    {
        $types = [];

        $documentState = new Model\EstadoDocumento();
        $where = [new Base\DataBase\DataBaseWhere('tipodoc', $this->modelName)];
        foreach ($documentState->all($where) as $docState) {
            if (!empty($docState->generadoc)) {
                $types[$docState->generadoc] = $docState->generadoc;
            }
        }

        return $types;
    }

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
        $pageData['icon'] = 'fas fa-thumbtack';
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
        $this->codes = $this->getCodes();
        $this->modelName = $this->getModelName();
        $this->setDocuments();

        $destiny = $this->request->request->get('destiny', '');
        if (!empty($destiny)) {
            $this->generateNewDocument($destiny);
        }
    }

    /**
     * 
     * @param string $destiny
     */
    protected function generateNewDocument($destiny)
    {
        $newLines = [];
        $prototype = null;
        $quantities = [];
        foreach ($this->documents as $doc) {
            foreach ($doc->getLines() as $line) {
                $quantity = (float) $this->request->request->get('approve_quant_' . $line->primaryColumnValue(), '0');
                if (empty($quantity)) {
                    continue;
                }

                if (null === $prototype) {
                    $prototype = $doc;
                }

                $quantities[$line->primaryColumnValue()] = $quantity;
                $newLines[] = $line;
            }
        }

        if (null === $prototype) {
            return;
        }

        $generator = new BusinessDocumentGenerator();
        if ($generator->generate($prototype, $destiny, $newLines, $quantities)) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));

            /// redir to new document
            foreach ($generator->getLastDocs() as $doc) {
                $this->response->headers->set('Refresh', '0; ' . $doc->url());
                break;
            }
            return;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));
    }

    /**
     * 
     * @return array
     */
    protected function getCodes()
    {
        $code = $this->request->request->get('code', []);
        if (!empty($code)) {
            return $code;
        }

        $codes = $this->request->get('codes', '');
        return explode(',', $codes);
    }

    /**
     * 
     * @return string
     */
    protected function getModelName()
    {
        $model = $this->request->get('model', '');
        return $this->request->request->get('model', $model);
    }

    protected function setDocuments()
    {
        foreach ($this->codes as $code) {
            $modelClass = 'FacturaScripts\\Dinamic\\Model\\' . $this->modelName;
            $doc = new $modelClass();
            if ($doc->loadFromCode($code)) {
                $this->documents[] = $doc;
            }
        }
    }
}
