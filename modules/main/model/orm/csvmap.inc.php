<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Main\Model\Orm;
use \RS\Orm\Type;

/**
* Предустановка для экспорта CSV
*/
class CsvMap extends \RS\Orm\OrmObject
{
    const
        TYPE_EXPORT = 'export',
        TYPE_IMPORT = 'import';
    
    protected static
        $table = 'csv_map';
    
    function _init()
    {
        parent::_init()->append(array(
            'schema' => new Type\Varchar(array(
                'description' => t('Схема импорта-экспорта')
            )),
            'type' => new Type\Enum(array('export', 'import'), array(
                'description' => t('Тип операции')
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Название предустановки')
            )),
            'columns' => new Type\ArrayList(),
            '_columns' => new Type\Varchar(array(
                'maxLength' => '5000',
                'description' => t('Информация о колонках')
            ))
        
        ));
    }
    
    function beforeWrite($save_flag)
    {
        $this['_columns'] = serialize($this['columns']);
    }
    
    function afterObjectLoad()
    {
        $this['columns'] = @unserialize($this['_columns']);
    }
    
    /**
    * Возвращает список имеющихся предустанвок для заданной $schema и $type
    * 
    * @param string $schema имя схемы
    * @param string $type импорт или экспорт
    * @return array
    */
    static function loadList($schema, $type)
    {
        return \RS\Orm\Request::make()
            ->from(new self())
            ->where(array(
                'schema' => $schema,
                'type' => $type
            ))->objects();
    }
    
    /**
    * Возвращает JSON для применения предустановки в JavaScript
    * @return string
    */
    function getJson()
    {
        return json_encode($this['columns']);
        
    }
}
?>
