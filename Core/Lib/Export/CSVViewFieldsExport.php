<?php declare(strict_types=1);

namespace FacturaScripts\Core\Lib\Export;

class CSVViewFieldsExport extends CSVExport
{
    /** Adds a new page with a table listing the model's data. */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        $this->setFileName($title);

        $fields = array_values($this->getColumnTitles($columns));

        $cursor = $model::all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->writeData([], $fields);
        }

        while (!empty($cursor)) {
            $data = $this->getCursorData($cursor, $columns);
            $this->writeData($data, $fields);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model::all($where, $order, $offset, self::LIST_LIMIT);
        }

        /// do not continue with export
        return false;
    }
}
