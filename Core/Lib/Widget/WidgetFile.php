<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of WidgetFile
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetFile extends BaseWidget
{

    /**
     *
     * @var string
     */
    public $accept;

    /**
     * 
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->accept = $data['accept'] ?? '';
    }

    /**
     * 
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

        $additionalDesc = static::$i18n->trans('help-server-accepts-filesize', ['%size%' => $this->getMaxFileUpload()]);
        $finalDesc = empty($description) ? $additionalDesc : static::$i18n->trans($description) . ' ' . $additionalDesc;

        if ($this->readonly()) {
            $class = $this->combineClasses($this->css('form-control'), $this->class);
            return '<div class="form-group">'
                . '<label>' . $this->onclickHtml(static::$i18n->trans($title), $titleurl) . '</label>'
                . '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
                . '<input type="text" value="' . $this->show() . '" class="' . $class . '" readonly=""/>'
                . '</div>';
        }

        return parent::edit($model, $title, $finalDesc, $titleurl);
    }

    /**
     * 
     * @param object  $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $minilog = new MiniLog();

        // get file uploads
        foreach ($request->files->all() as $key => $uploadFile) {
            if ($key != $this->fieldname || is_null($uploadFile)) {
                continue;
            } elseif (false === $uploadFile->isValid()) {
                $minilog->error($uploadFile->getErrorMessage());
                continue;
            }

            /// exclude php files
            if (\in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
                $minilog->error($this->i18n->trans('php-files-blocked'));
                continue;
            }

            if ($uploadFile->move(\FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName())) {
                $model->{$this->fieldname} = $uploadFile->getClientOriginalName();
                break;
            }

            $minilog->error('file-not-found');
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
     * 
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'file', $extraClass = '')
    {
        $class = empty($extraClass) ? $this->css('form-control-file') : $this->css('form-control-file') . ' ' . $extraClass;
        return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . $this->value
            . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * 
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $html = parent::inputHtmlExtraParams();
        if (!empty($this->accept)) {
            $html .= ' accept="' . $this->accept . '"';
        }

        return $html;
    }
}
