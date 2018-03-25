<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Html\Table\Type;

class Select extends AbstractType
{
    const
        DEFAULT_SELECT_NAME = 'rights[%VALUE%]';
    
    public
        $select_name,
        $index_name;
    
    protected
        $items,    
        $body_template = 'system/admin/html_elements/table/coltype/select.tpl';
        
    function __construct($field, $title, $items, $select_name, $index_name, $property = null)
    {
        parent::__construct($field, $title, $property);
        $this->select_name = $select_name;
        $this->index_name = $index_name;
        $this->setItems($items);
    }
    
    /**
    * Устанавливает элементы для списка
    * 
    * @param array $items
    */
    function setItems(array $items)
    {
        $this->items = $items;
    }
    
    /**
    * Возвращает элементы для списка
    */
    function getItems()
    {
        return $this->items;
    }
    
    function getSelectName()
    {
        return str_replace('%VALUE%', $this->row[$this->index_name], $this->select_name);
    }
    
}
