<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model;

/**
 * PDF document data.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class PDFDocument extends PDFCore
{

    /**
     * Calculate logo size and return as array of width and height
     *
     * @param $logo
     *
     * @return array
     */
    protected function calcLogoSize($logo)
    {
        $logoSize = $size = getimagesize($logo);
        if ($size[0] > 200) {
            $logoSize[0] = 200;
            $logoSize[1] = $logoSize[1] * $logoSize[0] / $size[0];
            $size[0] = $logoSize[0];
            $size[1] = $logoSize[1];
        }
        if ($size[1] > 80) {
            $logoSize[1] = 80;
            $logoSize[0] = $logoSize[0] * $logoSize[1] / $size[1];
        }
        return [
            'width' => $logoSize[0],
            'height' => $logoSize[1],
        ];
    }

    /**
     * Gets the name of the country with that code.
     *
     * @param string $code
     *
     * @return string
     */
    protected function getCountryName($code): string
    {
        if (empty($code)) {
            return '';
        }

        $country = new Model\Pais();
        return $country->loadFromCode($code) ? Utils::fixHtml($country->nombre) : '';
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

    /**
     * Insert company logo to PDF document or dies with a message to try to solve the problem.
     */
    protected function insertCompanyLogo()
    {
        if (!\function_exists('imagecreatefromstring')) {
            die('ERROR: function imagecreatefromstring() not founded. '
                . ' Do you have installed php-gd package and enabled support to allow us render images? .'
                . 'Note that the package name can differ between operating system or PHP version.');
        }

        $logoPath = \FS_FOLDER . '/Core/Assets/Images/logo.png';
        $logoSize = $this->calcLogoSize($logoPath);

        $xPos = $this->pdf->ez['pageWidth'] - $logoSize['width'] - $this->pdf->ez['topMargin'];
        $yPos = $this->pdf->ez['pageHeight'] - $logoSize['height'] - $this->pdf->ez['rightMargin'];
        $this->pdf->addPngFromFile($logoPath, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
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
            $address .= empty($company->codpostal) ? ' - ' : ' - CP: ' . $company->codpostal . ' - ';
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
}
