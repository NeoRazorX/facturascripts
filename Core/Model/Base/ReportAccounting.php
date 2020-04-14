<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

/**
 * Model Base for accounting reports
 *
 * @author Jose Antonio Cuello <jcuello@artextrading.com>
 */
abstract class ReportAccounting extends ModelClass
{

    use ModelTrait;

    /**
     *
     * @var int
     */
    public $channel;

    /**
     *
     * @var string
     */
    public $enddate;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Link to company model
     *
     * @var int
     */
    public $idcompany;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $startdate;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->startdate = \date('01-01-Y');
        $this->enddate = \date('31-12-Y');
        $this->idcompany = $this->toolBox()->appSettings()->get('default', 'idempresa');
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
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        $this->name = $this->toolBox()->utils()->noHtml($this->name);

        if (empty($this->idcompany)) {
            $this->toolBox()->i18nLog()->warning(
                'field-can-not-be-null',
                ['%fieldName%' => 'idempresa', '%tableName%' => static::tableName()]
            );
            return false;
        }

        if (strtotime($this->startdate) > \strtotime($this->enddate)) {
            $params = ['%endDate%' => $this->startdate, '%startDate%' => $this->enddate];
            $this->toolBox()->i18nLog()->warning('start-date-later-end-date', $params);
            return false;
        }

        if (strtotime($this->startdate) < 1) {
            $this->toolBox()->i18nLog()->warning('date-invalid');
            return false;
        }

        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return parent::url($type, 'ListReportAccounting?activetab=' . $list);
    }
}
