<?php
namespace Evasmart\Model\CsvPreset;

use RS\Csv\Preset\AbstractPreset;

class Product extends AbstractPreset
{

    private $api;

    function loadData()
    {
        $this->rows = $this->schema->rows;
    }

    function getColumnsData($n)
    {
        $this->row = array();
        foreach($this->getColumns() as $id => $column) {
            $value = $this->rows[$n][$column['key']];
            $this->row[$id] = trim($value);
        }
        return $this->row;
    }

    function importColumnsData()
    {
        $this->api->importColumnsData($this->row);

    }


    function getColumns()
    {
        return [
            ['key'   => 'vendor_code', 'title' => 'Артикул'],
            ['key'   => 'brand', 'title' => 'Бренд'],
            ['key'   => 'model', 'title' => 'Марка'],
            ['key'   => 'name', 'title' => 'Наименование'],
            ['key'   => 'wholesale', 'title' => 'Опт'],
            ['key'   => 'retail', 'title' => 'Розница'],
            ['key'   => 'desc', 'title' => 'Описание'],
            ['key'   => 'fasteners', 'title' => 'Крепеж'],
            ['key'   => 'note', 'title' => 'Примечание'],
        ];

    }

    function loadObject()
    {
        return [];
    }

    function setApi($api)
    {
        $this->api = $api;
    }

}