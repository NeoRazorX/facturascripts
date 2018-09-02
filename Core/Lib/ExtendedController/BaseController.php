<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\CodeModel;

/**
 * Description of BaseController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BaseController extends Base\Controller
{

    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * Indicates the active view.
     *
     * @var string
     */
    public $active;

    /**
     * Model to use with select and autocomplete filters.
     *
     * @var CodeModel
     */
    public $codeModel;

    /**
     * Indicates current view, when drawing.
     *
     * @var string
     */
    private $current;

    /**
     * Object to export data.
     *
     * @var ExportManager
     */
    public $exportManager;

    /**
     * Tools to work with numbers.
     *
     * @var Base\NumberTools
     */
    public $numberTools;

    /**
     * List of views displayed by the controller.
     *
     * @var BaseView[]
     */
    public $views;

    /**
     * Inserts the views to display.
     */
    abstract protected function createViews();

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    abstract protected function loadData($viewName, $view);

    /**
     * Initializes all the objects and properties.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     * @param string          $uri
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        $this->active = $this->request->get('activetab', '');
        $this->codeModel = new CodeModel();
        $this->exportManager = new ExportManager();
        $this->numberTools = new Base\NumberTools();
        $this->views = [];
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    public function addCustomView($viewName, $view)
    {
        $this->views[$viewName] = $view;
        if (empty($this->active)) {
            $this->active = $viewName;
        }
    }

    /**
     * 
     * @return BaseView
     */
    public function getCurrentView()
    {
        return $this->views[$this->current];
    }

    /**
     * 
     * @param string $viewName
     */
    public function setCurrentView($viewName)
    {
        $this->current = $viewName;
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $data = $this->requestGet(['field', 'source', 'fieldcode', 'fieldtitle', 'term', 'formname']);
        if ($data['source'] == '') {
            return $this->getAutocompleteValues($data['formname'], $data['field']);
        }

        $results = [];
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term']) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        return $results;
    }

    /**
     * Return values from Widget Values for autocomplete action
     *
     * @param string $viewName
     * @param string $fieldName
     *
     * @return array
     */
    protected function getAutocompleteValues(string $viewName, string $fieldName): array
    {
        $result = [];
        $column = $this->views[$viewName]->columnForField($fieldName);
        if (!empty($column)) {
            foreach ($column->widget->values as $value) {
                $result[] = ['key' => $this->i18n->trans($value['title']), 'value' => $value['value']];
            }
        }
        return $result;
    }

    /**
     * 
     * @return array
     */
    protected function getFormData(): array
    {
        $data = $this->request->request->all();

        /// get file uploads
        foreach ($this->request->files->all() as $key => $uploadFile) {
            if (is_null($uploadFile)) {
                continue;
            } elseif (!$uploadFile->isValid()) {
                $this->miniLog->error($uploadFile->getErrorMessage());
                continue;
            }

            /// exclude php files
            if (\in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
                $this->miniLog->error($this->i18n->trans('php-files-blocked'));
                continue;
            }

            if ($uploadFile->move(FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName())) {
                $data[$key] = $uploadFile->getClientOriginalName();
            }
        }

        return $data;
    }

    /**
     * Return array with parameters values
     *
     * @param array $keys
     *
     * @return array
     */
    protected function requestGet($keys): array
    {
        $result = [];
        foreach ($keys as $value) {
            $result[$value] = $this->request->get($value);
        }
        return $result;
    }
}
