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
 * Visual configuration of the FacturaScripts views,
 * each PageOption corresponds to a view or tab.
 *
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class PageOption extends ModelClass
{
    use ModelTrait;

    /** @var array Definición de los grupos y columnas de la vista. */
    public $columns;

    /** @var int Identificador único de la configuración visual. */
    public $id;

    /** @var string Fecha y hora de la última actualización. */
    public $last_update;

    /** @var array Definición de los formularios modales de la vista. */
    public $modals;

    /** @var string Nombre de la página o controlador al que pertenece la configuración. */
    public $name;

    /** @var string Nombre del usuario propietario de la configuración. */
    public $nick;

    /** @var array Definición del tratamiento visual especial de las filas. */
    public $rows;

    public function clear(): void
    {
        parent::clear();
        $this->columns = [];
        $this->last_update = Tools::dateTime();
        $this->modals = [];
        $this->rows = [];
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
        array_push($exclude, 'columns', 'modals', 'filters', 'rows', 'code', 'action');
        parent::loadFromData($data, $exclude, $sync);

        $this->columns = json_decode($data['columns'], true);
        $this->modals = json_decode($data['modals'], true);
        $this->rows = json_decode($data['rows'], true);
    }

    public function save(): bool
    {
        // encode the values of the view configuration fields
        $this->columns = $this->getEncodeValues()['columns'];
        $this->modals = $this->getEncodeValues()['modals'];
        $this->rows = $this->getEncodeValues()['rows'];

        $saved = parent::save();

        // decode the values of the view configuration fields
        $this->columns = json_decode($this->columns, true);
        $this->modals = json_decode($this->modals, true);
        $this->rows = json_decode($this->rows, true);

        return $saved;
    }

    public static function tableName(): string
    {
        return 'pages_options';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $type === 'list' ?
            parent::url($type, $list) :
            'EditPageOption?code=' . $this->name;
    }

    /**
     * Returns the values of the view configuration fields in JSON format
     *
     * @return array
     */
    private function getEncodeValues(): array
    {
        return [
            'columns' => json_encode($this->columns),
            'modals' => json_encode($this->modals),
            'rows' => json_encode($this->rows),
        ];
    }
}
