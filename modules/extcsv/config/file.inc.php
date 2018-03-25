<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExtCsv\Config;
use \RS\Orm\Type;

/**
* Конфигурационный файл модуля
* @ingroup Custom
*/
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(    
            'csv_id_fields' => new Type\ArrayList(array(
                'runtime' => false,
                'description' => t('Поля для идентификации товара при импорте (удерживая CTRL можно выбрать несколько полей)'),
                'hint' => t('Во время импорта данных из CSV файла, система сперва будет обновлять товары, у которых будет совпадение значений по указанным здесь колонкам. В противном случае будет создаваться новый товар'),
                'list' => array(array('\ExtCsv\Model\CsvSchema\Product','getPossibleIdFields')),
                'size' => 7,
                'attr' => array(array('multiple' => true))
            )),
            'csv_recommended_id_field' => new Type\Varchar(array(
                'description' => t('Поле идентификации рекомендуемых товаров'),
                'list' => array(array('\ExtCsv\Model\CsvSchema\Product','getPossibleIdFields'))
            )),
            'csv_concomitant_id_field' => new Type\Varchar(array(
                'description' => t('Поле идентификации сопутствующих товаров'),
                'list' => array(array('\ExtCsv\Model\CsvSchema\Product','getPossibleIdFields'))
            ))
        ));
    }
}