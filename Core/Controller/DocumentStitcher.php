<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DocumentStitcher
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class DocumentStitcher extends Controller
{

    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * Array of document primary keys.
     *
     * @var array
     */
    public $codes = [];

    /**
     *
     * @var TransformerDocument[]
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
     * @var TransformerDocument[]
     */
    public $moreDocuments = [];

    /**
     * Returns available status to group this model.
     * 
     * @return array
     */
    public function getAvaliableStatus()
    {
        $status = [];
        $documentState = new EstadoDocumento();
        $where = [new DataBaseWhere('tipodoc', $this->modelName)];
        foreach ($documentState->all($where) as $docState) {
            if ($docState->generadoc) {
                $status[] = $docState;
            }
        }

        return $status;
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['showonmenu'] = false;
        $data['title'] = 'group-or-split';
        $data['icon'] = 'fas fa-magic';
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->codes = $this->getCodes();
        $this->modelName = $this->getModelName();
        $this->loadDocuments();
        $this->loadMoreDocuments();

        // duplicated request?
        $token = $this->request->request->get('multireqtoken', '');
        if ($token && $this->multiRequestProtection->tokenExist($token)) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return;
        }

        $status = (int) $this->request->request->get('status', '');
        if ($status) {
            $this->generateNewDocument($status);
        }
    }

    /**
     * 
     * @param array               $newLines
     * @param TransformerDocument $doc
     */
    protected function addBlankLine(array &$newLines, $doc)
    {
        $blankLine = $doc->getNewLine();
        $blankLine->cantidad = 0;
        $blankLine->iva = 0.0;
        $blankLine->irpf = 0.0;
        $blankLine->recargo = 0.0;
        $newLines[] = $blankLine;
    }

    /**
     * 
     * @param TransformerDocument $newDoc
     *
     * @return bool
     */
    protected function addDocument($newDoc)
    {
        foreach ($this->documents as $doc) {
            if ($doc->coddivisa != $newDoc->coddivisa || $doc->subjectColumnValue() != $newDoc->subjectColumnValue()) {
                $this->toolBox()->i18nLog()->warning('incompatible-document', ['%code%' => $newDoc->codigo]);
                return false;
            }
        }

        $this->documents[] = $newDoc;
        return true;
    }

    /**
     * 
     * @param array               $newLines
     * @param TransformerDocument $doc
     */
    protected function addInfoLine(array &$newLines, $doc)
    {
        $infoLine = $doc->getNewLine();
        $infoLine->cantidad = 0;
        $infoLine->descripcion = $this->toolBox()->i18n()->trans($doc->modelClassName() . '-min')
            . ' ' . $doc->codigo . "\n--------------------";
        $infoLine->iva = 0.0;
        $infoLine->irpf = 0.0;
        $infoLine->recargo = 0.0;
        $newLines[] = $infoLine;
    }

    /**
     * 
     * @param TransformerDocument $doc
     * @param array               $newLines
     * @param array               $quantities
     * @param int                 $idestado
     */
    protected function breakDownLines(&$doc, &$newLines, &$quantities, $idestado)
    {
        $full = true;
        foreach ($doc->getLines() as $line) {
            $quantity = (float) $this->request->request->get('approve_quant_' . $line->primaryColumnValue(), '0');
            $quantities[$line->primaryColumnValue()] = $quantity;

            if (empty($quantity) && $line->cantidad) {
                $full = $full && $line->servido >= $line->cantidad;
                continue;
            } elseif (($quantity + $line->servido) < $line->cantidad) {
                $full = false;
            }

            $newLines[] = $line;
        }

        if ($full) {
            $doc->setDocumentGeneration(false);
            $doc->idestado = $idestado;
            if (false === $doc->save()) {
                $this->dataBase->rollback();
                $this->toolBox()->i18nLog()->error('record-save-error');
                return;
            }
        }

        /// we get the lines again in case they have been updated
        foreach ($doc->getLines() as $line) {
            $line->servido += $quantities[$line->primaryColumnValue()];
            if (false === $line->save()) {
                $this->dataBase->rollback();
                $this->toolBox()->i18nLog()->error('record-save-error');
                return;
            }
        }
    }

    /**
     * Generates a new document with this data.
     * 
     * @param int $idestado
     */
    protected function generateNewDocument($idestado)
    {
        $this->dataBase->beginTransaction();

        /// group needed data
        $newLines = [];
        $properties = ['fecha' => $this->request->request->get('fecha', '')];
        $prototype = null;
        $quantities = [];
        foreach ($this->documents as $doc) {
            if (null === $prototype) {
                $prototype = clone $doc;
            } elseif ('true' === $this->request->request->get('extralines', '')) {
                $this->addBlankLine($newLines, $doc);
            }

            if (\count($this->documents) > 1 && 'true' === $this->request->request->get('extralines', '')) {
                $this->addInfoLine($newLines, $doc);
            }

            /// we break down quantities and lines
            $this->breakDownLines($doc, $newLines, $quantities, $idestado);
        }

        if (null === $prototype || empty($newLines)) {
            $this->dataBase->rollback();
            return;
        }

        /// allow plugins to do stuff on the prototype before save
        if (false === $this->pipe('checkPrototype', $prototype, $newLines)) {
            $this->dataBase->rollback();
            return;
        }

        /// generate new document
        $generator = new BusinessDocumentGenerator();
        $newClass = $this->getGenerateClass($idestado);
        if (false === $generator->generate($prototype, $newClass, $newLines, $quantities, $properties)) {
            $this->dataBase->rollback();
            $this->toolBox()->i18nLog()->error('record-save-error');
            return;
        }

        $this->dataBase->commit();

        /// redir to new document
        foreach ($generator->getLastDocs() as $doc) {
            $this->redirect($doc->url());
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            break;
        }
    }

    /**
     * Returns documents keys.
     * 
     * @return array
     */
    protected function getCodes()
    {
        $code = $this->request->request->get('code', []);
        if ($code) {
            return $code;
        }

        $codes = \explode(',', $this->request->get('codes', ''));
        $newcodes = $this->request->get('newcodes', []);
        return empty($newcodes) ? $codes : \array_merge($codes, $newcodes);
    }

    /**
     * Returns the name of the new class to generate from this status.
     * 
     * @param int $idestado
     *
     * @return string
     */
    protected function getGenerateClass($idestado)
    {
        $estado = new EstadoDocumento();
        $estado->loadFromCode($idestado);
        return $estado->generadoc;
    }

    /**
     * Returns model name.
     * 
     * @return string
     */
    protected function getModelName()
    {
        $model = $this->request->get('model', '');
        return $this->request->request->get('model', $model);
    }

    /**
     * Loads selected documents.
     */
    protected function loadDocuments()
    {
        if (empty($this->codes) || empty($this->modelName)) {
            return;
        }

        $modelClass = self::MODEL_NAMESPACE . $this->modelName;
        foreach ($this->codes as $code) {
            $doc = new $modelClass();
            if ($doc->loadFromCode($code)) {
                $this->addDocument($doc);
            }
        }

        /// sort by date
        \uasort($this->documents, function ($doc1, $doc2) {
            if (\strtotime($doc1->fecha . ' ' . $doc1->hora) > \strtotime($doc2->fecha . ' ' . $doc2->hora)) {
                return 1;
            } elseif (\strtotime($doc1->fecha . ' ' . $doc1->hora) < \strtotime($doc2->fecha . ' ' . $doc2->hora)) {
                return -1;
            }

            return 0;
        });
    }

    protected function loadMoreDocuments()
    {
        if (empty($this->documents) || empty($this->modelName)) {
            return;
        }

        $modelClass = self::MODEL_NAMESPACE . $this->modelName;
        $model = new $modelClass();
        $where = [
            new DataBaseWhere('editable', true),
            new DataBaseWhere('coddivisa', $this->documents[0]->coddivisa),
            new DataBaseWhere($model->subjectColumn(), $this->documents[0]->subjectColumnValue())
        ];
        $order = ['fecha' => 'ASC', 'hora' => 'ASC'];
        foreach ($model->all($where, $order) as $doc) {
            if (false === \in_array($doc->primaryColumnValue(), $this->getCodes())) {
                $this->moreDocuments[] = $doc;
            }
        }
    }
}
