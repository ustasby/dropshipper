<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Tags\Model\Orm;
use \RS\Orm\Type;

class Word extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'tags_words';
        
    function _init()
    {
        return $this->getPropertyIterator()->append(array(
            'id' => new Type\Bigint(array(
                'autoincrement' => true,
                'allowEmpty' => false,
                'primaryKey' => true,
                'visible' => false
            )),        
            'stemmed' => new Type\Varchar(array(
                'description' => t('Тег без окончания'),
                'index' => true
            )),
            'word' => new Type\Varchar(array(
                'description' => t('Тег')
            )),
            'alias' => new Type\Varchar(array(
                'description' => t('Английское название тега'),
                'unique' => true
            ))
        ));
    }
    
    /**
    * Функция срабатывает перед запиью в базу тега
    * 
    * @param string $flag - строковое представление текущей операции insert или update
    */
    function beforeWrite($flag)
    {
       if ($flag==self::INSERT_FLAG){ //Если вставка
          $this['alias'] = \RS\Helper\Transliteration::str2url($this['word']);
          //Проверим есть ли такое английское название уже в базе
          $api = new \Tags\Model\Api();
          $this['alias'] = $api->checkAliasByAlias($this['alias']);
       }
       
       if ($flag==self::UPDATE_FLAG && empty($this['alias'])){ //Если обновление
          $this['alias'] = \RS\Helper\Transliteration::str2url($this['word']);
          //Проверим есть ли такое английское название уже в базе
          $api = new \Tags\Model\Api();
          $this['alias'] = $api->checkAliasByAlias($this['alias']);
       }
    }
}

