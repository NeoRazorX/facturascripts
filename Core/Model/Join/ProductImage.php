<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Dinamic\Model\Base\JoinModel;
use FacturaScripts\Dinamic\Model\ProductImage as DinProductImage;

use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;

/**
 * Model ProductImage with attached file data
 *
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 */

/**
 * Model Product Image with file attach data
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ProductImage extends JoinModel
{

    /**
     * Constructor and class initializer.
     * Set master model for controller actions.
     *
     * @param array $data
     */
    public function __construct($data = array())
    {
        parent::__construct($data);
        $this->setMasterModel(new DinProductImage());
    }

    /**
     * Returns the file extension in lowercase.
     *
     * @return string
     */
    public function getExtension()
    {
        $parts = \explode('.', \strtolower($this->filename));
        return \count($parts) > 1 ? \strtolower(\end($parts)) : '';
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return int
     */
    public function getMaxFileUpload()
    {
        $docFile = new AttachedFileRelation();
        return $docFile->getMaxFileUpload();
    }

    /**
     * Get an authorized url to download the file.
     *
     * @return string
     */
    public function getUrlDownload()
    {
        return $this->path . '?myft=' . MyFilesToken::get($this->path, false);
    }

    /**
     *
     * @param int $size
     * @return string
     */
    public function getSize(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if (empty($this->idproducto)) {
            return '';
        }
        return 'EditProducto?code=' . $this->idproducto . '&active=ProductImage';
    }

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'idimage' => 'img.idimage',
            'idproducto' => 'img.idproducto',
            'referencia' => 'img.referencia',
            'idfile' => 'img.idfile',
            'referencia' => 'variantes.referencia',
            'creationdate' => 'rel.creationdate',
            'idattached' => 'rel.id',
            'idfile' => 'rel.idfile',
            'nick' => 'rel.nick',
            'date' => 'att.date',
            'hour' => 'att.hour',
            'filename' => 'att.filename',
            'path' => 'att.path',
            'mimetype' => 'att.mimetype',
            'size' => 'att.size',
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'productosimagenes img'
            . ' LEFT JOIN variantes ON variantes.referencia = img.referencia'
            . ' INNER JOIN attached_files_rel rel ON rel.model = \'ProductImage\' AND rel.modelid = img.idimage'
            . ' INNER JOIN attached_files att ON att.idfile = rel.idfile';
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'productosimagenes',
            'variantes',
            'attached_files_rel',
            'attached_files',
        ];
    }
}
