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
interface ExportInterface
{

    /**
     * Adds a new page with the document data.
     */
    public function addBusinessDocPage($model): bool;

    /**
     * Adds a new page with a table listing the models data.
     */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool;

    /**
     * Adds a new page with the model data.
     */
    public function addModelPage($model, $columns, $title = ''): bool;

    /**
     * Adds a new page with the table.
     */
    public function addTablePage($headers, $rows): bool;

    /**
     * Return the full document.
     */
    public function getDoc();

    /**
     * Blank document.
     */
    public function newDoc();

    /**
     * Sets default orientation.
     */
    public function setOrientation(string $orientation);

    /**
     * Set headers and output document content to response.
     */
    public function show(Response &$response);
}
