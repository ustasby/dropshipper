<?php
namespace DataBlocks\Model\Orm;

use RS\Orm\AbstractObject;
use RS\Orm\Type;

/**
 * ORM объект значения одного параметра для элемента (узла дерева)
 */
class DataNodeValue extends AbstractObject
{
    protected static
        $table = 'datablocks_node_value';

    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'parent_node_id' => new Type\Integer(array(
                'description' => t('ID родительского узла'),
                'allowEmpty' => false,
            )),
            'field_key' => new Type\Varchar(array(
                'description' => t('Идентификатор поля'),
                'maxLength' => 50,
                'allowEmpty' => false,
            )),
            'field_str_value' => new Type\Varchar(array(
                'description' => t('Строковое значение')
            )),
            'field_int_value' => new Type\Real(array(
                'description' => t('Числовое значение'),
                'decimal' => 6,
                'maxLength' => 20
            )),
            'field_text_value' => new Type\Text(array(
                'description' => t('Текстовое значение')
            )),
            'node_id' => new Type\Integer(array(
                'description' => t('ID узла'),
                'allowEmpty' => false,
                'index' => true
            )),
            'node_type' => new Type\Enum(array_keys(DataNodeField::getTypesTitle()), array(
                'description' => t('Тип'),
                'list' => array(array('\DataBlocks\Model\Orm\DataNodeField', 'getTypesTitle'))
            )),
        ));

        $this->addIndex(array('parent_node_id', 'field_key', 'field_str_value', 'node_id'), self::INDEX_UNIQUE);
        $this->addIndex(array('parent_node_id', 'field_key', 'field_int_value', 'node_id'), self::INDEX_UNIQUE);
    }

    /**
     * Возвращает полный текст значения параметра
     *
     * @return mixed
     */
    function getFullValue()
    {
        switch($this['node_type']) {
            case DataNodeField::FIELD_TYPE_TEXTAREA:
            case DataNodeField::FIELD_TYPE_RICHTEXT:
                return $this['field_text_value'];
            case DataNodeField::FIELD_TYPE_NUMERIC:
                return $this['field_int_value'];
            default:
                return $this['field_str_value'];
        }
    }
}