<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Search\Config;
use RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            'search_service' => new Type\Varchar(array(
                'description' => t('Поисковый сервис'),
                'list' => array(array('\Search\Model\SearchApi', 'getEnginesNames'))
            )),
            'search_type' => new Type\Varchar(array(
                'attr' => array(array('size' => 1)),
                'listFromArray' => array(array(
                    'like' => 'like',
                    'likeplus' => 'like +',
                    'fulltext' => t('Полнотекстовый')
                )),
                'description' => t('Тип поиска'),
                'hint' => t('Актуально для поискового сервиса Mysql;<br>После изменения типа поиска требуется переиндексировать товары и другие объекты поиска')
            ))
        ));
        
    }
}

