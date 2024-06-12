<?php

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\AssetManager;

class WidgetSelectmulti extends WidgetSelect
{
    /**
     *
     * @var string
     */
    protected $modelDest;

    /**
     *
     * @var string
     */
    protected $modelID1;

    /**
     *
     * @var string
     */
    protected $modelID2;

    /**
     *
     * @var mixed
     */
    protected $modelValue;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->modelDest = $data['modeldest'];
        $this->modelID1 = $data['modelid1'] ?? '';
        $this->modelID2 = $data['modelid2'] ?? '';
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/node_modules/select2/dist/css/select2.min.css');
        AssetManager::add('js', FS_ROUTE . '/node_modules/select2/dist/js/select2.min.js', 2);
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetSelectmulti.js', 1);
    }

    /**
     *
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $class = $this->combineClasses($this->css('form-control select2'), $this->class, $extraClass);
        $html = '<select class="' . $class . '"' . $this->inputHtmlExtraParams() . '>';
        foreach ($this->values as $option) {
            $title = empty($option['title']) ? $option['value'] : $option['title'];
            $found = false;
            foreach ($this->value as $v) {
                /// don't use strict comparation (===)
                if ($option['value'] == $v->{$this->modelID2}) {
                    $found = true;
                    $html .= '<option value="' . $option['value'] . '" selected="">' . $title . '</option>';
                    break;
                }
            }

            if ($found === false) {
                $html .= '<option value="' . $option['value'] . '">' . $title . '</option>';
            }
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $params = parent::inputHtmlExtraParams();
        $params .= ' multiple';
        $params .= ' style="width: 100%;"';

        if ($this->readonly()) {
            $params .= ' disabled';
        } else {
            $params .= 'name="' . $this->fieldname . '[]"';
        }

        return $params;
    }

    /**
     * Set datasource data and Load data from Model into values array.
     *
     * @param array $child
     * @param bool $loadData
     */
    protected function setSourceData(array $child, bool $loadData = true)
    {
        $this->source = $child['source'];
        $this->fieldcode = $child['fieldcode'] ?? 'id';
        $this->fieldtitle = $child['fieldtitle'] ?? $this->fieldcode;
        if ($loadData) {
            $values = static::$codeModel->all($this->source, $this->fieldcode, $this->fieldtitle, false);
            $this->setValuesFromCodeModel($values, $this->translate);
        }
    }

    /**
     * @param object $model
     */
    protected function setValue($model)
    {
        $this->value = [];
        $modelClassDest = '\\FacturaScripts\\Dinamic\\Model\\' . $this->modelDest;
        if (class_exists($modelClassDest) === false) {
            return;
        }

        if (empty($this->modelID1)) {
            $this->modelID1 = $model->primaryColumn();
        }

        if (empty($this->modelID2)) {
            $modelClass2 = '\\FacturaScripts\\Dinamic\\Model\\' . $this->source;
            if (class_exists($modelClass2) === false) {
                return;
            }

            $model2 = new $modelClass2();
            $this->modelID2 = $model2->primaryColumn();
        }

        $this->modelValue = @$model->{$this->modelID1};

        $modelDest = new $modelClassDest();
        $where = [new DataBaseWhere($this->modelID1, $this->modelValue)];
        $array = $modelDest->all($where, [], 0, 0);
        $this->value = empty($array) ? [] : $array;
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        if (empty($this->value)) {
            return '-';
        }

        $array = [];
        foreach ($this->value as $option) {
            $array[] = $option->{$this->fieldtitle} ?? $option->primaryColumnValue();
        }

        $txt = implode(', ', $array);
        if (strlen($txt) < 20) {
            return $txt;
        }

        $html = substr($txt, 0, 20);
        $tooltip = implode('<br>', $array);
        return '<span data-toggle="tooltip" data-html="true" title="' . $tooltip . '">' . $html . '...</span>';
    }
}