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

use Symfony\Component\HttpFoundation\Response;

/**
 * Export interface.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ExportBase
{

    /**
     *
     * @var string
     */
    private $fileName;

    /**
     * Adds a new page with the document data.
     */
    abstract public function addBusinessDocPage($model): bool;

    /**
     * Adds a new page with a table listing the models data.
     */
    abstract public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool;

    /**
     * Adds a new page with the model data.
     */
    abstract public function addModelPage($model, $columns, $title = ''): bool;

    /**
     * Adds a new page with the table.
     */
    abstract public function addTablePage($headers, $rows): bool;

    /**
     * Return the full document.
     */
    abstract public function getDoc();

    /**
     * Blank document.
     */
    abstract public function newDoc(string $title);

    /**
     * Sets default orientation.
     */
    abstract public function setOrientation(string $orientation);

    /**
     * Set headers and output document content to response.
     */
    abstract public function show(Response &$response);

    /**
     * 
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * 
     * @param string $name
     * @param bool   $force
     */
    public function setFileName(string $name, bool $force = false)
    {
        if (empty($this->fileName) || $force) {
            $this->fileName = str_replace([' ', '"', "'"], ['_', '_', '_'], $name);
        }
    }
}
