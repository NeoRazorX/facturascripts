<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\EstadosDocumentos;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\Lib\ExtendedController\OwnerDataTrait;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;

/**
 * Controlador que permite agrupar o partir documentos de venta o compra
 * (presupuestos, pedidos y albaranes) para generar un nuevo documento
 * a partir de las líneas seleccionadas, o cerrar los documentos cambiando
 * su estado. No admite facturas.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class DocumentStitcher extends Controller
{
    use OwnerDataTrait;

    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /** @var array */
    public $codes = [];

    /** @var TransformerDocument[] */
    public $documents = [];

    /** @var array */
    public $filters = ['codpago' => '', 'desde' => '', 'hasta' => ''];

    /** @var string */
    public $modelName;

    /** @var TransformerDocument[] */
    public $moreDocuments = [];

    /** @var array */
    public $payMethods = [];

    /** @var bool */
    public $showFilters = false;

    /** @var array */
    public $where = [];

    public function getAvailableStatus(): array
    {
        $status = [];
        foreach (EstadosDocumentos::byTipoDoc($this->modelName) as $docState) {
            if ($docState->activo && $docState->generadoc) {
                $status[] = $docState;
            }
        }

        return $status;
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'group-or-split';
        $data['icon'] = 'fa-solid fa-wand-magic-sparkles';
        $data['showonmenu'] = false;
        return $data;
    }

    public function getSeries(): array
    {
        return CodeModel::all('series', 'codserie', 'descripcion', false);
    }

    /**
     * Ejecuta la lógica privada del controlador.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $action = $this->request->request->get('action', '');

        $this->codes = $this->getCodes();
        $this->modelName = $this->getModelName();

        // no se pueden agrupar o partir facturas
        if (in_array($this->modelName, ['FacturaCliente', 'FacturaProveedor'])) {
            $this->redirect('List' . $this->modelName);
            return;
        }

        $this->loadDocuments();
        $this->addFilters();
        if ('search' === $action) {
            $this->processFormDataLoad();
        }

        $this->loadMoreDocuments();

        $statusCode = $this->request->input('status', '');
        if ($statusCode) {
            // ¿validar el token del formulario?
            if (false === $this->validateFormToken()) {
                return;
            }

            // Evita aprobar más cantidad de la que realmente queda pendiente en cada línea.
            if (false === $this->validateSelectedQuantities()) {
                return;
            }

            // si el $statusCode empieza por close:, cerramos
            if (0 === strpos($statusCode, 'close:')) {
                $status = substr($statusCode, 6);
                $this->closeDocuments((int)$status);
            } else {
                $this->generateNewDocument((int)$statusCode);
            }
        }
    }

    /**
     * @param array $newLines
     * @param TransformerDocument $doc
     */
    protected function addBlankLine(array &$newLines, $doc): void
    {
        $blankLine = $doc->getNewLine([
            'cantidad' => 0,
            'mostrar_cantidad' => false,
            'mostrar_precio' => false
        ]);

        $this->pipe('addBlankLine', $blankLine);
        $newLines[] = $blankLine;
    }

    /**
     * @param TransformerDocument $newDoc
     *
     * @return bool
     */
    protected function addDocument($newDoc): bool
    {
        foreach ($this->documents as $doc) {
            if (
                $doc->codalmacen != $newDoc->codalmacen ||
                $doc->coddivisa != $newDoc->coddivisa ||
                $doc->idempresa != $newDoc->idempresa ||
                $doc->dtopor1 != $newDoc->dtopor1 ||
                $doc->dtopor2 != $newDoc->dtopor2 ||
                $doc->subjectColumnValue() != $newDoc->subjectColumnValue()
            ) {
                Tools::log()->warning('incompatible-document', ['%code%' => $newDoc->codigo]);
                return false;
            }
        }

        $this->documents[] = $newDoc;
        return true;
    }

    protected function addFilters(): void
    {
        if (empty($this->documents)) {
            return;
        }

        foreach (FormasPago::all() as $payMethod) {
            if ($payMethod->activa && $payMethod->idempresa == $this->documents[0]->idempresa) {
                $this->payMethods[$payMethod->id()] = $payMethod->descripcion;
            }
        }

        asort($this->payMethods);
    }

    /**
     * @param array $newLines
     * @param TransformerDocument $doc
     */
    protected function addInfoLine(array &$newLines, $doc): void
    {
        $infoLine = $doc->getNewLine([
            'cantidad' => 0,
            'descripcion' => $this->getDocInfoLineDescription($doc),
            'mostrar_cantidad' => false,
            'mostrar_precio' => false
        ]);

        $this->pipe('addInfoLine', $infoLine);
        $newLines[] = $infoLine;
    }

    /**
     * @param TransformerDocument $doc
     * @param array $docLines
     * @param array $newLines
     * @param array $quantities
     * @param int $idestado
     *
     * @return bool
     */
    protected function breakDownLines(&$doc, &$docLines, &$newLines, &$quantities, $idestado): bool
    {
        $full = true;
        foreach ($docLines as $line) {
            $quantity = (float)$this->request->input('approve_quant_' . $line->id(), '0');
            $quantities[$line->id()] = $quantity;

            if (empty($quantity) && $line->cantidad) {
                $full = $full && $line->servido >= $line->cantidad;
                continue;
            } elseif (($quantity + $line->servido) < $line->cantidad) {
                $full = false;
            }

            $this->pipe('breakDownLines', $line);
            $newLines[] = $line;
        }

        if ($full) {
            $doc->setDocumentGeneration(false);
            $doc->idestado = $idestado;
            if (false === $doc->save()) {
                Tools::log()->error('record-save-error');
                return false;
            }
        }

        // reponemos las referencias con líneas frescas, porque el cambio de estado
        // puede haberlas actualizado sobre otras instancias
        foreach ($doc->getLines() as $line) {
            foreach ($newLines as $num => $newLine) {
                if ($newLine->id() === $line->id()) {
                    $newLines[$num] = $line;
                    break;
                }
            }
        }

        return true;
    }

    protected function closeDocuments(int $idestado): void
    {
        foreach ($this->documents as $doc) {
            if (false === $doc->editable) {
                Tools::log()->warning('non-editable-document', ['%code%' => $doc->codigo]);
                return;
            }
        }

        $this->db()->beginTransaction();

        foreach ($this->documents as $doc) {
            $doc->setDocumentGeneration(false);
            $doc->idestado = $idestado;
            if (false === $doc->save()) {
                $this->db()->rollback();
                Tools::log()->error('record-save-error');
                return;
            }
        }

        $this->db()->commit();
        Tools::log()->notice('record-updated-correctly');
    }

    /**
     * Genera un nuevo documento con estos datos.
     *
     * @param int $idestado
     */
    protected function generateNewDocument(int $idestado): void
    {
        foreach ($this->documents as $doc) {
            if (false === $doc->editable) {
                Tools::log()->warning('non-editable-document', ['%code%' => $doc->codigo]);
                return;
            }
        }

        $this->db()->beginTransaction();

        // agrupamos los datos necesarios
        $newLines = [];
        $properties = [];
        $prototype = null;
        $quantities = [];

        $newDate = $this->request->input('fecha', '');
        if (!empty($newDate)) {
            $properties['fecha'] = $newDate;
            if (strtotime($newDate) === strtotime(Tools::date())) {
                $properties['hora'] = Tools::hour();
            }
        }

        foreach ($this->documents as $doc) {
            $lines = $doc->getLines();

            if (null === $prototype) {
                $prototype = clone $doc;
                $prototype->codserie = $this->request->input('codserie', $doc->codserie);
            } elseif ('true' === $this->request->input('extralines', '') && !empty($lines)) {
                $this->addBlankLine($newLines, $doc);
            }

            if ('true' === $this->request->input('extralines', '') && !empty($lines)) {
                $this->addInfoLine($newLines, $doc);
            }

            // desglosamos las cantidades y líneas
            if (false === $this->breakDownLines($doc, $lines, $newLines, $quantities, $idestado)) {
                $this->db()->rollback();
                return;
            }
        }

        if (null === $prototype || empty($newLines)) {
            $this->db()->rollback();
            return;
        }

        // permitimos a los plugins actuar sobre el prototipo antes de guardar
        if (false === $this->pipe('checkPrototype', $prototype, $newLines)) {
            $this->db()->rollback();
            return;
        }

        // generamos el nuevo documento
        $generator = new BusinessDocumentGenerator();
        $newClass = $this->getGenerateClass($idestado);
        if (empty($newClass)) {
            $this->db()->rollback();
            return;
        }

        if (false === $generator->generate($prototype, $newClass, $newLines, $quantities, $properties)) {
            $this->db()->rollback();
            Tools::log()->error('record-save-error');
            return;
        }

        $this->db()->commit();

        // redirigimos al nuevo documento
        foreach ($generator->getLastDocs() as $doc) {
            $this->redirect($doc->url());
            Tools::log()->notice('record-updated-correctly');
            break;
        }
    }

    /**
     * Devuelve las claves de los documentos.
     *
     * @return array
     */
    protected function getCodes(): array
    {
        $codes = $this->request->request->getArray('codes');
        if ($codes) {
            return $codes;
        }

        $codes = explode(',', $this->request->queryOrInput('codes', ''));
        $new_codes = $this->request->request->getArray('newcodes');
        return empty($new_codes) ? $codes : array_merge($codes, $new_codes);
    }

    /**
     * @param TransformerDocument $doc
     *
     * @return string
     */
    protected function getDocInfoLineDescription($doc): string
    {
        $description = Tools::trans($doc->modelClassName() . '-min') . ' ' . $doc->codigo;

        if (isset($doc->numero2) && $doc->numero2) {
            $description .= ' (' . $doc->numero2 . ')';
        } elseif (isset($doc->numproveedor) && $doc->numproveedor) {
            $description .= ' (' . $doc->numproveedor . ')';
        }

        $description .= ', ' . $doc->fecha . "\n--------------------";
        return $description;
    }

    /**
     * Devuelve el nombre de la nueva clase a generar a partir de este estado.
     *
     * @param int $idestado
     *
     * @return ?string
     */
    protected function getGenerateClass(int $idestado): ?string
    {
        return EstadosDocumentos::get($idestado)->generadoc;
    }

    /**
     * Devuelve el nombre del modelo.
     *
     * @return string
     */
    protected function getModelName(): string
    {
        return $this->request->inputOrQuery('model', '');
    }

    /**
     * Carga los documentos seleccionados.
     */
    protected function loadDocuments(): void
    {
        if (empty($this->codes) || empty($this->modelName)) {
            return;
        }

        $modelClass = self::MODEL_NAMESPACE . $this->modelName;
        foreach ($this->codes as $code) {
            $doc = new $modelClass();
            if (false === $doc->load($code)) {
                continue;
            }

            // no permitimos agrupar/partir documentos ajenos
            if (false === $this->checkOwnerData($doc)) {
                Tools::log()->warning('not-allowed-modify');
                continue;
            }

            $this->addDocument($doc);
        }

        // ordenamos por fecha
        uasort($this->documents, function ($doc1, $doc2) {
            if (strtotime($doc1->fecha . ' ' . $doc1->hora) > strtotime($doc2->fecha . ' ' . $doc2->hora)) {
                return 1;
            } elseif (strtotime($doc1->fecha . ' ' . $doc1->hora) < strtotime($doc2->fecha . ' ' . $doc2->hora)) {
                return -1;
            }

            return 0;
        });
    }

    protected function loadMoreDocuments(): void
    {
        if (empty($this->documents) || empty($this->modelName)) {
            return;
        }

        $modelClass = self::MODEL_NAMESPACE . $this->modelName;
        $model = new $modelClass();
        $this->where[] = Where::eq('codalmacen', $this->documents[0]->codalmacen);
        $this->where[] = Where::eq('coddivisa', $this->documents[0]->coddivisa);
        $this->where[] = Where::eq('codserie', $this->documents[0]->codserie);
        $this->where[] = Where::eq('dtopor1', $this->documents[0]->dtopor1);
        $this->where[] = Where::eq('dtopor2', $this->documents[0]->dtopor2);
        $this->where[] = Where::eq('editable', true);
        $this->where[] = Where::eq('idempresa', $this->documents[0]->idempresa);
        $this->where[] = Where::eq($model->subjectColumn(), $this->documents[0]->subjectColumnValue());
        $orderBy = ['fecha' => 'ASC', 'hora' => 'ASC'];
        foreach ($model->all($this->where, $orderBy, 0, 0) as $doc) {
            if (in_array($doc->id(), $this->getCodes())) {
                continue;
            }

            // no sugerimos documentos ajenos cuando el usuario solo ve los suyos
            if (false === $this->checkOwnerData($doc)) {
                continue;
            }

            $this->moreDocuments[] = $doc;
        }
    }

    protected function processFormDataLoad(): void
    {
        // filters
        $this->filters['codpago'] = $this->request->request->get('codpago', '');
        if ($this->filters['codpago']) {
            $this->where[] = Where::eq('codpago', $this->filters['codpago']);
            $this->showFilters = true;
        }

        $this->filters['desde'] = $this->request->request->get('desde', '');
        if ($this->filters['desde']) {
            $this->where[] = Where::gte('fecha', $this->filters['desde']);
            $this->showFilters = true;
        }

        $this->filters['hasta'] = $this->request->request->get('hasta', '');
        if ($this->filters['hasta']) {
            $this->where[] = Where::lte('fecha', $this->filters['hasta']);
            $this->showFilters = true;
        }
    }

    protected function validateSelectedQuantities(): bool
    {
        foreach ($this->documents as $document) {
            foreach ($document->getLines() as $line) {
                $quantity = (float)$this->request->input('approve_quant_' . $line->id(), '0');

                $pending = max(0, $line->cantidad - $line->servido);
                if ($quantity <= $pending) {
                    continue;
                }

                Tools::log()->error('error-more-quant-than-pending', [
                    '%description%' => $line->descripcion,
                    '%pending%' => $pending,
                    '%selected_quantity%' => $quantity,
                ]);
                return false;
            }
        }

        return true;
    }
}
