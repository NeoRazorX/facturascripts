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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;

/**
 * Description of WidgetFile
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetFile extends BaseWidget
{
    /** @var string */
    public $accept;

    /** @var bool */
    public $multiple;

    /** @param array $data */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->accept = $data['accept'] ?? '';
        $this->multiple = isset($data['multiple']) && strtolower($data['multiple']) === 'true';
    }

    /**
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);

        $additionalDesc = Tools::lang()->trans('help-server-accepts-filesize', ['%size%' => $this->getMaxFileUpload()]);
        $finalDesc = empty($description) ? $additionalDesc : Tools::lang()->trans($description) . ' ' . $additionalDesc;

        if ($this->readonly()) {
            $class = $this->combineClasses($this->css('form-control'), $this->class);
            return '<div class="mb-3">'
                . '<label class="mb-0">' . $this->onclickHtml(Tools::lang()->trans($title), $titleurl) . '</label>'
                . '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
                . '<input type="text" value="' . $this->show() . '" class="' . $class . '" readonly=""/>'
                . '</div>';
        }

        return parent::edit($model, $title, $finalDesc, $titleurl);
    }

    /**
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $logger = new MiniLog();

        // get file uploads
        foreach ($request->files->all() as $key => $uploadFile) {
            if ($key != $this->fieldname || is_null($uploadFile)) {
                continue;
            } elseif (false === $uploadFile->isValid()) {
                $logger->error($uploadFile->getErrorMessage());
                continue;
            }

            // exclude php files
            if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
                $logger->error(Tools::lang()->trans('php-files-blocked'));
                continue;
            }

            // check if the file already exists
            $destiny = FS_FOLDER . '/MyFiles/';
            $destinyName = $uploadFile->getClientOriginalName();
            if (file_exists($destiny . $destinyName)) {
                $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
            }

            // move the file to the MyFiles folder
            if ($uploadFile->move($destiny, $destinyName)) {
                $model->{$this->fieldname} = $destinyName;
                continue;
            }

            $logger->error('file-not-found');
        }
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return int
     */
    protected function getMaxFileUpload()
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'file', $extraClass = '')
    {
        $class = empty($extraClass) ? $this->css('form-control') : $this->css('form-control') . ' ' . $extraClass;

        if ($this->multiple) {
            return '<input type="' . $type . '" name="' . $this->fieldname . '[]" value="' . $this->value
                . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . ' multiple/>';
        }

        return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . $this->value
            . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    protected function inputHtmlExtraParams(): string
    {
        $html = parent::inputHtmlExtraParams();
        if (!empty($this->accept)) {
            $html .= ' accept="' . $this->accept . '"';
        }

        return $html;
    }
}
