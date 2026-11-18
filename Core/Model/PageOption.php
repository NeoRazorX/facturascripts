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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

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

    /** @var string|null Nombre de la personalización; null en la general y en la "por defecto" del usuario. */
    public $description;

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

    /** @var bool Indica si es la personalización con nombre elegida por el usuario para la vista. */
    public $selected;

    public function clear(): void
    {
        parent::clear();
        $this->columns = [];
        $this->description = null;
        $this->last_update = Tools::dateTime();
        $this->modals = [];
        $this->rows = [];
        $this->selected = false;
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

    /**
     * Guarda la configuración y, si queda marcada como elegida,
     * desmarca las demás personalizaciones del usuario para la misma vista.
     *
     * @return bool
     */
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

        if ($saved && $this->selected) {
            static::unselect($this->name, $this->nick, (int)$this->id);
        }

        return $saved;
    }

    public static function tableName(): string
    {
        return 'pages_options';
    }

    /**
     * Valida la descripción y la selección, y que no exista otra configuración
     * con la misma vista, usuario y descripción.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->description = trim(Tools::noHtml($this->description) ?? '');
        if ($this->description === '') {
            $this->description = null;
        }

        if (empty($this->nick) && null !== $this->description) {
            Tools::log()->warning('description-error');
            return false;
        }

        if (empty($this->nick) || null === $this->description) {
            $this->selected = false;
        }

        // la restricción única no impide duplicados con NULL, por eso se comprueba aquí
        $where = [
            Where::eq('name', $this->name),
            empty($this->nick) ? Where::isNull('nick') : Where::eq('nick', $this->nick),
            null === $this->description ? Where::isNull('description') : Where::eq('description', $this->description),
        ];
        if (null !== $this->id) {
            $where[] = Where::notEq('id', $this->id);
        }
        if (static::count($where) > 0) {
            Tools::log()->warning('duplicate-record');
            return false;
        }

        return parent::test();
    }

    /**
     * Desmarca las personalizaciones elegidas de un usuario para una vista.
     *
     * @param string $name Nombre de la vista XML.
     * @param string $nick Usuario propietario.
     * @param int|null $exceptId Identificador que no se desmarca.
     */
    public static function unselect(string $name, string $nick, ?int $exceptId = null): void
    {
        $where = [
            Where::eq('name', $name),
            Where::eq('nick', $nick),
            Where::eq('selected', true),
        ];
        if (null !== $exceptId) {
            $where[] = Where::notEq('id', $exceptId);
        }

        foreach (static::all($where) as $pageOption) {
            $pageOption->selected = false;
            $pageOption->save();
        }
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
