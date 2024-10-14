<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Request;

/**
 * Description of PeriodFilter
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class PeriodFilter extends BaseFilter
{
    const END_DATE_ID = 'end';
    const SELECT_ID = 'period';
    const START_DATE_ID = 'start';

    /** @var DateFilter */
    private $endDate;

    /** @var SelectFilter */
    private $select;

    /** @var DateFilter */
    private $startDate;

    public function __construct(string $key, string $field, string $label, $dateTime = false)
    {
        parent::__construct($key, $field, $label);
        $values = PeriodTools::getFilterOptions(static::$i18n);
        $this->select = new SelectFilter($key, '', $label, $values);
        $this->select->icon = 'fa-solid fa-calendar-alt';
        $this->startDate = new DateFilter(self::START_DATE_ID . $key, $field, 'from-date', '>=', $dateTime);
        $this->endDate = new DateFilter(self::END_DATE_ID . $key, $field, 'until-date', '<=', $dateTime);
    }

    public function getDataBaseWhere(array &$where): bool
    {
        // apply both
        $start = $this->startDate->getDataBaseWhere($where);
        $end = $this->endDate->getDataBaseWhere($where);

        // return true if anyone is true
        return $start || $end;
    }

    /**
     * Get the filter value
     *
     * @param string $option
     *
     * @return mixed
     */
    public function getValue(string $option = self::SELECT_ID)
    {
        switch ($option) {
            case self::START_DATE_ID:
                return $this->startDate->getValue();

            case self::END_DATE_ID:
                return $this->endDate->getValue();
        }

        return $this->select->getValue();
    }

    public function render(): string
    {
        if ($this->readonly) {
            $this->select->readonly = true;
            $this->startDate->readonly = true;
            $this->endDate->readonly = true;
        }

        return $this->select->render()
            . $this->startDate->render()
            . $this->endDate->render();
    }

    /**
     * Set value to filter
     *
     * @param mixed $value
     */
    public function setValue($value, $option = self::SELECT_ID)
    {
        switch ($option) {
            case self::START_DATE_ID:
                $this->startDate->setValue($value);
                break;

            case self::END_DATE_ID:
                $this->endDate->setValue($value);
                break;

            default:
                $this->select->setValue($value);
                $this->setPeriodToDates();
                break;
        }
    }

    /**
     * Set value to filter from form request
     *
     * @param Request $request
     */
    public function setValueFromRequest(Request &$request)
    {
        $selectValue = $request->request->get($this->select->name());
        if (empty($selectValue)) {
            // start
            $startValue = $request->request->get($this->startDate->name());
            $this->setValue($startValue, self::START_DATE_ID);

            // end
            $endValue = $request->request->get($this->endDate->name());
            $this->setValue($endValue, self::END_DATE_ID);
            return;
        }

        $this->setValue($selectValue);
    }

    /**
     * Set date value and disable filter
     *
     * @param string $date
     * @param string $option
     */
    private function setDateAndDisable(string $date, string $option)
    {
        $this->setValue($date, $option);
        $this->startDate->readonly = true;
        $this->endDate->readonly = true;
    }

    /**
     * Calculate dates from period value
     */
    private function setPeriodToDates()
    {
        $startDate = date('d-m-Y');
        $endDate = date('d-m-Y');
        PeriodTools::applyPeriod($this->getValue(), $startDate, $endDate);
        $this->setDateAndDisable($startDate, self::START_DATE_ID);
        $this->setDateAndDisable($endDate, self::END_DATE_ID);
    }
}
