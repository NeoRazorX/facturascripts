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
namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Lib\PDF\PDFDocument;
use FacturaScripts\Dinamic\Model\FormatoDocumento;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF export data.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Carlos Jiménez Gómez         <carlos@evolunext.es>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class PDFExport extends PDFDocument
{

    const LIST_LIMIT = 500;

    /**
     * Adds a new page with the document data.
     *
     * @param BusinessDocument $model
     *
     * @return bool
     */
    public function addBusinessDocPage($model): bool
    {
        if (null === $this->format) {
            $this->format = $this->getDocumentFormat($model);
        }

        $this->newPage();
        $this->insertHeader($model->idempresa);
        $this->insertBusinessDocHeader($model);
        $this->insertBusinessDocBody($model);
        $this->insertBusinessDocFooter($model);

        /// do not continue with export
        return false;
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param ModelClass      $model
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     *
     * @return bool
     */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        $this->setFileName($title);

        $orientation = 'portrait';
        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = ['cols' => [], 'shadeCol' => [0.95, 0.95, 0.95], 'shadeHeadingCol' => [0.95, 0.95, 0.95]];
        $tableData = [];
        $longTitles = [];

        /// turns widget columns into needed arrays
        $this->setTableColumns($columns, $tableCols, $tableColsTitle, $tableOptions);
        if (count($tableCols) > 5) {
            $orientation = 'landscape';
            $this->removeLongTitles($longTitles, $tableColsTitle);
        }

        $this->newPage($orientation);
        $tableOptions['width'] = $this->tableWidth;
        $this->insertHeader();

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->pdf->ezTable($tableData, $tableColsTitle, '', $tableOptions);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols, $tableOptions);
            $this->removeEmptyCols($tableData, $tableColsTitle, $this->numberTools->format(0));
            $this->pdf->ezTable($tableData, $tableColsTitle, $title, $tableOptions);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        $this->newLongTitles($longTitles, $tableColsTitle);
        $this->insertFooter();
        return true;
    }

    /**
     * Adds a new page with the model data.
     *
     * @param ModelClass $model
     * @param array      $columns
     * @param string     $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->newPage();
        $idempresa = isset($model->idempresa) ? $model->idempresa : null;
        $this->insertHeader($idempresa);

        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = [
            'width' => $this->tableWidth,
            'showHeadings' => 0,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => []
        ];

        /// Get the columns
        $this->setTableColumns($columns, $tableCols, $tableColsTitle, $tableOptions);

        $tableDataAux = [];
        foreach ($tableColsTitle as $key => $colTitle) {
            $value = $tableOptions['cols'][$key]['widget']->plainText($model);
            $tableDataAux[] = ['key' => $colTitle, 'value' => $this->fixValue($value)];
        }

        $title .= ': ' . $model->primaryDescription();
        $this->pdf->ezText("\n" . $this->fixValue($title) . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $this->insertParalellTable($tableDataAux, '', $tableOptions);
        $this->insertFooter();
        return true;
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     *
     * @return bool
     */
    public function addTablePage($headers, $rows): bool
    {
        $orientation = count($headers) > 5 ? 'landscape' : 'portrait';
        $this->newPage($orientation);

        $tableOptions = [
            'width' => $this->tableWidth,
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'cols' => []
        ];
        foreach (\array_keys($headers) as $key) {
            if (\in_array($key, ['debe', 'haber', 'saldo', 'saldoprev'])) {
                $tableOptions['cols'][$key]['justification'] = 'right';
            }
        }

        $this->insertHeader();
        $this->pdf->ezTable($rows, $headers, '', $tableOptions);
        $this->insertFooter();
        return true;
    }

    /**
     * Return the full document.
     *
     * @return mixed
     */
    public function getDoc()
    {
        if ($this->pdf === null) {
            $this->newPage();
            $this->pdf->ezText('');
        }

        return $this->pdf->ezOutput();
    }

    /**
     * Blank document.
     * 
     * @param string $title
     * @param int    $idformat
     * @param string $langcode
     */
    public function newDoc(string $title, int $idformat, string $langcode)
    {
        $this->setFileName($title);

        if (!empty($idformat)) {
            $this->format = new FormatoDocumento();
            $this->format->loadFromCode($idformat);
        }

        if (!empty($langcode)) {
            $this->i18n->setLang($langcode);
        }
    }

    /**
     * Set headers and output document content to response.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline;filename=' . $this->getFileName() . '.pdf');
        $response->setContent($this->getDoc());
    }
}
