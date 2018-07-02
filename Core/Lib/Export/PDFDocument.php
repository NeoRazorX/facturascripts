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
namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model;

/**
 * PDF document data.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class PDFDocument extends PDFCore
{

    /**
     * Adds a new page.
     *
     * @param string $orientation
     */
    protected function newPage($orientation = 'portrait')
    {
        if ($this->pdf === null) {
            $this->pdf = new \Cezpdf('a4', $orientation);
            $this->pdf->addInfo('Creator', 'FacturaScripts');
            $this->pdf->addInfo('Producer', 'FacturaScripts');
            $this->pdf->tempPath = FS_FOLDER . '/MyFiles/Cache';

            $this->tableWidth = $this->pdf->ez['pageWidth'] - self::CONTENT_X * 2;

            $this->pdf->ezStartPageNumbers(self::CONTENT_X, self::FOOTER_Y, self::FONT_SIZE, 'left', '{PAGENUM} / {TOTALPAGENUM}');
        } elseif ($this->pdf->y < 200) {
            $this->pdf->ezNewPage();
            $this->insertedHeader = false;
        } else {
            $this->pdf->ezText("\n");
        }
    }

     /**
     * Insert header details.
     *
     * @param int $idempresa
     */
    protected function insertHeader($idempresa = null)
    {
        if ($this->insertedHeader) {
            return;
        }

        $this->insertedHeader = true;
        $this->insertCompanyLogo();

        $code = $idempresa ?? AppSettings::get('default', 'idempresa', '');
        $company = new Model\Empresa();
        if ($company->loadFromCode($code)) {
            $this->pdf->ezText($company->nombre, self::FONT_SIZE + 9);
            $address = $company->direccion;
            $address .= empty($company->codpostal) ? '' : ' - CP: ' . $company->codpostal . ' - ';
            $address .= empty($company->ciudad) ? '' : $company->ciudad;
            $address .= empty($company->provincia) ? '' : ' (' . $company->provincia . ') ' . $this->getCountryName($company->codpais);
            $contactData = empty($company->telefono1) ? '' : $company->telefono1 . ' ';
            $contactData .= empty($company->telefono2) ? '' : $company->telefono2 . ' ';
            $contactData .= empty($company->email) ? '' : $company->email . ' ';
            $contactData .= empty($company->web) ? '' : $company->web;

            $lineText = $company->cifnif . ' - ' . $address . ' - ' . $contactData;
            $this->pdf->ezText($lineText . "\n", self::FONT_SIZE);
        }
    }

    /**
     * Adds to $longTitles, and replace all long titles from $titles
     *
     * @param array $longTitles
     * @param array $titles
     */
    protected function removeLongTitles(&$longTitles, &$titles)
    {
        $num = 1;
        foreach ($titles as $key => $value) {
            if (mb_strlen($value) > 12) {
                $longTitles[$num] = $value;
                $titles[$key] = '*' . $num;
                ++$num;
            }
        }
    }

    /**
     * Remove the empty columns to save space.
     *
     * @param array $tableData
     * @param array $tableColsTitle
     * @param mixed $customEmptyValue
     */
    protected function removeEmptyCols(&$tableData, &$tableColsTitle, $customEmptyValue = '0')
    {
        foreach (array_keys($tableColsTitle) as $key) {
            $remove = true;
            foreach ($tableData as $row) {
                if (!empty($row[$key]) && $row[$key] != $customEmptyValue) {
                    $remove = false;
                    break;
                }
            }

            if ($remove) {
                unset($tableColsTitle[$key]);
            }
        }
    }

     /**
     * Adds a description of long titles to the PDF.
     *
     * @param array $titles
     */
    protected function newLongTitles(&$titles)
    {
        $txt = '';
        foreach ($titles as $key => $value) {
            if ($txt !== '') {
                $txt .= ', ';
            }

            $txt .= '*' . $key . ' = ' . $value;
        }

        if ($txt !== '') {
            $this->pdf->ezText($txt);
        }
    }

    /**
     * Insert footer details.
     */
    protected function insertFooter()
    {
        $now = $this->i18n->trans('generated-at', ['%when%' => date('d-m-Y H:i')]);
        $this->pdf->addText($this->tableWidth + self::CONTENT_X, self::FOOTER_Y, self::FONT_SIZE, $now, 0, 'right');
    }

    /**
     * Set the table content.
     *
     * @param array $columns
     * @param array $tableCols
     * @param array $tableColsTitle
     * @param array $tableOptions
     */
    protected function setTableColumns(&$columns, &$tableCols, &$tableColsTitle, &$tableOptions)
    {
        foreach ($columns as $col) {
            if (is_string($col)) {
                $tableCols[$col] = $col;
                $tableColsTitle[$col] = $col;
                continue;
            }

            if (isset($col->columns)) {
                $this->setTableColumns($col->columns, $tableCols, $tableColsTitle, $tableOptions);
                continue;
            }

            if (isset($col->display) && $col->display !== 'none' && isset($col->widget->fieldName)) {
                $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
                $tableColsTitle[$col->widget->fieldName] = $this->i18n->trans($col->title);
                $tableOptions['cols'][$col->widget->fieldName] = [
                    'justification' => $col->display,
                    'col-type' => $col->widget->type,
                ];
            }
        }
    }

    /**
     * Adds a new line to the PDF.
     */
    protected function newLine()
    {
        $posY = $this->pdf->y + 5;
        $this->pdf->line(self::CONTENT_X, $posY, $this->tableWidth + self::CONTENT_X, $posY);
    }

    /**
     * Generate a table with two key => value per row.
     *
     * @param        $tableData
     * @param string $title
     * @param array  $options
     */
    protected function insertParalellTable($tableData, $title = '', $options = [])
    {
        $headers = ['data1' => 'data1', 'data2' => 'data2'];
        $rows = $this->paralellTableData($tableData);
        $this->pdf->ezTable($rows, $headers, $title, $options);
    }

    /**
     * Gets the name of an specify divisa
     *
     * @param string $code
     *
     * @return string
     */
    protected function getDivisaName($code): string
    {
        if (empty($code)) {
            return '';
        }

        $divisa = new Model\Divisa();
        return $divisa->loadFromCode($code) ? $divisa->descripcion : '';
    }
}
