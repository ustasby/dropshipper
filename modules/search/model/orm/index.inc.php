<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Search\Model\Orm;
use \RS\Orm\Type;

/**
* ORM объект - универсальный поисковый индекс.
*/
class Index extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'search_index';
    
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'result_class' => new Type\Varchar(array(
                'maxLength' => '100',
                'description' => t('Класс результата'),
                'allowEmpty' => false
            )),
            'entity_id' => new Type\Integer(array(
                'description' => t('id сущности'),
                'allowEmpty' => false
            )),
            'title' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Заголовок результата'),
            )),
            'indextext' => new Type\Text(array(
                'description' => t('Описание сущности (индексируемый)'),
            )),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата добавления в индекс'),
            )),
        ));
        
        $this
            ->addIndex(array('result_class', 'entity_id'), self::INDEX_PRIMARY, 'result_class-entity_id')
            ->addIndex(array('title', 'indextext'), self::INDEX_FULLTEXT);
    }
    
    /**
    * Возвращает имя свойства, которое помечено как первичный ключ.
    * 
    * @return string
    */
    public function getPrimaryKeyProperty()
    {
        return array('result_class', 'entity_id');
    }    
}

