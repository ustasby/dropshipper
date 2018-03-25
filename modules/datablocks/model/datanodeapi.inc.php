<?php
namespace DataBlocks\Model;
use RS\View\Engine;

/**
* Класс для организации выборок ORM объекта.
*/
class DataNodeApi extends \RS\Module\AbstractModel\TreeList
{
    function __construct()
    {
        parent::__construct(new Orm\DataNode(), array(
            'multisite' => true,
            'nameField' => 'title',
            'aliasField' => 'alias',
            'parentField' => 'parent_id',
            'defaultOrder' => 'parent_id, sortn'
        ));
    }

    /**
     * Возвращает массив со списком узлов для отображения в элементе select
     *
     * @param array $root Корневой элемент массива [ID => Наименование]
     * @return array
     */
    public static function staticSelectList($root = array(), $parent_id = 0)
    {
        $_this = new static();
        return $root + $_this->getSelectList($parent_id);
    }

}