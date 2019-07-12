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
namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Lib\ListFilter\PeriodTools;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of PeriodFilter
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class PeriodFilter extends BaseFilter
{

    const ENDDATE_ID = 'end';
    const SELECT_ID = 'period';
    const STARTDATE_ID = 'start';

    /**
     *
     * @var DateFilter
     */
    private $endDate;

    /**
     *
     * @var SelectFilter
     */
    private $select;

    /**
     *
     * @var DateFilter
     */
    private $startDate;

    /**
     * Class constructor.
     *
     * @param string $key
     * @param string $field  date field for where filter
     * @param string $label  label to period select
     */
    public function __construct($key, $field, $label)
    {
        parent::__construct($key, $field, $label);
        $values = PeriodTools::getFilterOptions(static::$i18n);
        $this->select = new SelectFilter($key, '', $label, $values);
        $this->select->icon = 'fas fa-calendar-check';
        $this->startDate = new DateFilter(self::STARTDATE_ID . $key, $field, 'from-date', '>=');
        $this->endDate = new DateFilter(self::ENDDATE_ID . $key, $field, 'until-date', '<=');
    }

    /**
     *
     * @param DataBaseWhere[] $where
     *
     * @return bool
     */
    public function getDataBaseWhere(array &$where): bool
    {
        /// apply both
        $start = $this->startDate->getDataBaseWhere($where);
        $end = $this->endDate->getDataBaseWhere($where);

        /// return true if anyone is true
        return $start || $end;
    }

    /**
     * Get the filter value
     *
     * @param string $option
     * @return mixed
     */
    public function getValue($option = self::SELECT_ID)
    {
        switch ($option) {
            case self::STARTDATE_ID:
                return $this->startDate->getValue();

            case self::ENDDATE_ID:
                return $this->endDate->getValue();

            default:
                return $this->select->getValue();
        };
    }

    /**
     *
     * @return string
     */
    public function render()
    {
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
            case self::STARTDATE_ID:
                $this->startDate->setValue($value);
                break;

            case self::ENDDATE_ID:
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
    public function setValueFromRequest(&$request)
    {
        $selectValue = $request->request->get($this->select->name());
        if (empty($selectValue)) {
            /// start
            $startValue = $request->request->get($this->startDate->name());
            $this->setValue($startValue, self::STARTDATE_ID);

            /// end
            $endValue = $request->request->get($this->endDate->name());
            $this->setValue($endValue, self::ENDDATE_ID);
        } else {
            $this->setValue($selectValue, self::SELECT_ID);
        }
    }

    /**
     * Set date value and disable filter
     *
     * @param string $date
     * @param string $option
     */
    private function setDateAndDisable($date, $option)
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
        $startdate = date('d-m-Y');
        $enddate = date('d-m-Y');
        PeriodTools::applyPeriod($this->getValue(self::SELECT_ID), $startdate, $enddate);
        $this->setDateAndDisable($startdate, self::STARTDATE_ID);
        $this->setDateAndDisable($enddate, self::ENDDATE_ID);
    }
}
