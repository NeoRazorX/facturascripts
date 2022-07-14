<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022  Carlos Garcia Gomez     <carlos@facturascripts.com>
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

/**
 * A province.
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class Provincia extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Code id
     *
     * @var string
     */
    public $codeid;

    /**
     * 'Normalized' code in Spain to identify the provinces.
     *
     * @url: https://es.wikipedia.org/wiki/Provincia_de_España#Denominaci.C3.B3n_y_lista_de_las_provincias
     *
     * @var string
     */
    public $codisoprov;

    /**
     * Country code associated with the province.
     *
     * @var string
     */
    public $codpais;

    /**
     * Identify the registry.
     *
     * @var string
     */
    public $idprovincia;

    /**
     * Name of the province.
     *
     * @var string
     */
    public $provincia;

    public function clear()
    {
        parent::clear();
        $this->codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
    }

    public function install(): string
    {
        // needed dependencies
        new Pais();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idprovincia';
    }

    public static function tableName(): string
    {
        return 'provincias';
    }

    public function test(): bool
    {
        $this->provincia = $this->toolBox()->utils()->noHtml($this->provincia);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->idprovincia)) {
            // asignamos el nuevo ID así para evitar problemas con postgresql por haber importado el listado con ids incluidos
            $this->idprovincia = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
