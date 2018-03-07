<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

/**
 * Manage the navigation bar to jump between the data of a model.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Pagination
{
    /**
     * Constants for paging.
     */
    const FS_ITEM_LIMIT = FS_ITEM_LIMIT;
    const FS_PAGE_MARGIN = 5;

    /**
     * URL target
     *
     * @var string
     */
    private $url;

    /**
     * URL ID into base url
     *
     * @var string
     */
    private $urlID;

    /**
     * Join operator to news parameters
     *
     * @var string
     */
    private $join;

    /**
     * Class constructor
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $auxUrl = explode('#', $url);
        $this->url = $auxUrl[0];
        $this->urlID = (count($auxUrl) > 1) ? $auxUrl[1] : '';
        $this->join = (strpos($url, '?') === false) ? '?' : '&';
    }

    /**
     * Returns the offset for the first element of the margin specified for paging.
     *
     * @param int $offset
     *
     * @return int
     */
    private function getRecordMin($offset)
    {
        $result = $offset - (self::FS_ITEM_LIMIT * self::FS_PAGE_MARGIN);
        if ($result < 0) {
            $result = 0;
        }

        return $result;
    }

    /**
     * Returns the offset for the last element of the margin specified for paging.
     *
     * @param int $offset
     * @param int $count
     *
     * @return int
     */
    private function getRecordMax($offset, $count)
    {
        $result = $offset + (self::FS_ITEM_LIMIT * (self::FS_PAGE_MARGIN + 1));
        if ($result > $count) {
            $result = $count;
        }

        return $result;
    }

    /**
     * Returns a paging item.
     *
     * @param int         $page
     * @param int         $offset
     * @param string|bool $icon
     * @param bool        $active
     *
     * @return array
     */
    private function addPaginationItem($page, $offset, $icon = false, $active = false)
    {
        return [
            'url' => $this->url . $this->join . 'offset=' . $offset . $this->urlID,
            'icon' => $icon,
            'page' => $page,
            'active' => $active,
        ];
    }

    /**
     * Calculate the browser between pages.
     * Lets jump to:
     *  - first,
     *  - previous half,
     *  - pageMargin x previous pages
     *  - actual page
     *  - pageMargin x subsequent pages
     *  - back half
     *  - last
     *
     * @param int    $count
     * @param int    $offset
     *
     * @return array
     *               icon   => specific bootstrap icon instead of num. page
     *               page   => page number
     *               active => indicate if it is the active indicator
     */
    public function getPages($count, $offset = 0)
    {
        $result = [];
        $recordMin = $this->getRecordMin($offset);
        $recordMax = $this->getRecordMax($offset, $count);
        $index = 0;

        // We add the first page, if it is not included in the page margin
        if ($offset > (self::FS_ITEM_LIMIT * self::FS_PAGE_MARGIN)) {
            $result[$index] = $this->addPaginationItem(1, 0, 'fa-step-backward');
            ++$index;
        }

        // We add the middle page between the first and the selected page,
        // if the selected page is larger than the page margin
        $recordMiddleLeft = ($recordMin > self::FS_ITEM_LIMIT) ? ($offset / 2) : $recordMin;
        if ($recordMiddleLeft < $recordMin) {
            $page = floor($recordMiddleLeft / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($page + 1, $this->offset($page), 'fa-backward');
            ++$index;
        }

        // We add the selected page and the page margin to its left and right
        for ($record = $recordMin; $record < $recordMax; $record += self::FS_ITEM_LIMIT) {
            if (($record >= $recordMin && $record <= $offset) || ($record <= $recordMax && $record >= $offset)) {
                $page = ($record / self::FS_ITEM_LIMIT) + 1;
                $result[$index] = $this->addPaginationItem($page, $record, false, $record === $offset);
                ++$index;
            }
        }

        // We add the middle page between the selected page and the last one,
        // if the selected page is smaller than the margin between pages
        $recordMiddleRight = $offset + (($count - $offset) / 2);
        if ($recordMiddleRight > $recordMax) {
            $page = floor($recordMiddleRight / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($page + 1, $this->offset($page), 'fa-forward');
            ++$index;
        }

        // We add the last page, if it is not included in the page margin
        if ($recordMax < $count) {
            $pageMax = floor($count / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($pageMax + 1, $this->offset($pageMax), 'fa-step-forward');
        }

        /// if there is only one page, it is not worth showing a single button
        return (count($result) > 1) ? $result : [];
    }

    /**
     * Returns the offset for the page.
     *
     * @param float|int $page
     *
     * @return int
     */
    private function offset($page)
    {
        return $page * self::FS_ITEM_LIMIT;
    }
}
