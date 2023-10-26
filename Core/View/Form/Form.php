<?php


namespace FacturaScripts\Core\View\Form;


use FacturaScripts\Core\Lib\Widget\WidgetText;

class Form
{
    protected $model;
    protected $fieldname;
    protected $type;
    protected $children;

    public function __construct($model, $fieldname, $type, $children)
    {
        $this->model = $model;
        $this->fieldname = $fieldname;
        $this->type = $type;
        $this->children = $children;
    }

    public function render()
    {
        $data = [
            'fieldname' => $this->fieldname,
            'type' => $this->type,
            'children' => $this->children,
        ];
        $widgetText = new WidgetText($data);

        return $widgetText->edit($this->model);
    }

}
