<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of Report
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Report extends Base\ModelClass
{

    use Base\ModelTrait;

    const DEFAULT_TYPE = 'area';

    /**
     *
     * @var int
     */
    public $compared;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $table;

    /**
     *
     * @var string
     */
    public $type;

    /**
     *
     * @var string
     */
    public $xcolumn;

    /**
     *
     * @var string
     */
    public $xoperation;

    /**
     *
     * @var string
     */
    public $ycolumn;

    public function clear()
    {
        parent::clear();
        $this->type = self::DEFAULT_TYPE;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn(): string
    {
        return 'name';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'reports';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->name = $utils->noHtml($this->name);
        $this->table = $utils->noHtml($this->table);
        $this->type = $utils->noHtml($this->type);
        $this->xcolumn = $utils->noHtml($this->xcolumn);
        $this->xoperation = $utils->noHtml($this->xoperation);
        $this->ycolumn = $utils->noHtml($this->ycolumn);
        return parent::test();
    }
}
