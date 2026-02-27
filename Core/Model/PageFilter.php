<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Visual filter configuration of the FacturaScripts views,
 * each PageFilter corresponds to a view or tab filter.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class PageFilter extends ModelClass
{
    use ModelTrait;

    /**
     * Human description
     *
     * @var string
     */
    public $description;

    /**
     * Definition of filters values
     *
     * @var array
     */
    public $filters;

    /**
     * Identifier
     *
     * @var int
     */
    public $id;

    /**
     * Name of the page (controller).
     *
     * @var string
     */
    public $name;

    /**
     * User Identifier.
     *
     * @var string
     */
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->filters = [];
    }

    public function install(): string
    {
        new Page();
        new User();

        return parent::install();
    }

    /**
     * Load the data from an array
     *
     * @param array $data
     * @param array $exclude
     * @param bool $sync
     */
    public function loadFromData(array $data = [], array $exclude = [], bool $sync = true): void
    {
        array_push($exclude, 'filters', 'code', 'action');
        parent::loadFromData($data, $exclude, $sync);

        $this->filters = isset($data['filters']) ? json_decode($data['filters'], true) : [];
    }

    public function save(): bool
    {
        // Encode the filters values to JSON format
        $this->filters = $this->getEncodeValues()['filters'];

        $saved = parent::save();

        // decode the filters values back to array format
        $this->filters = json_decode($this->filters, true);

        return $saved;
    }

    public static function tableName(): string
    {
        return 'pages_filters';
    }

    public function test(): bool
    {
        $this->description = Tools::noHtml($this->description);
        if (empty($this->description)) {
            Tools::log()->warning('description-error');
            return false;
        }

        return parent::test();
    }

    /**
     * Returns the values of the view configuration fields in JSON format
     *
     * @return array
     */
    private function getEncodeValues(): array
    {
        return [
            'filters' => json_encode($this->filters)
        ];
    }
}
