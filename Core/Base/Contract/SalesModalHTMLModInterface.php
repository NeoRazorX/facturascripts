<?php declare(strict_types=1);

namespace FacturaScripts\Core\Base\Contract;

use FacturaScripts\Core\Model\Base\SalesDocument;

interface SalesModalHTMLModInterface
{
    public function apply(SalesDocument &$model, array $formData);

    /**
     * Devolver un array con los campos que
     * se quieran agregar a la tabla de productos
     * del modal.
     * Para campos de productos usar "p." ej: p.stockfis
     * Para campos de variantes usar "v." ej: v.margen
     * ejemplo:
     * return [
            [
                'field' => 'p.stockfis',
                'title' => 'physical-stock',
                'isMoney' => false,
            ],
            [
                'field' => 'v.margen',
                'title' => 'margin',
                'isMoney' => true,
            ],
        ];
     *
     * @return array{array{'field': string, 'title': string, 'isMoney': bool}}
     */
    public function addProductColumnsTable();

    /**
     * Retorna el html con los inputs selects de los filtros a agregar
     *
     * @return string
     */
    public function addProductFilters();

    /**
     * Procesar los resultados obtenidos en la busqueda de productos
     *
     * @param array $results
     * @return array
     */
    public function applyResutls(array &$results);
}
