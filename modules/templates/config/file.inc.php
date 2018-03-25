<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Templates\Config;

/**
* Конфигурационный файл модуля
* @ingroup Templates
*/
class File extends \RS\Orm\ConfigObject
{
    /**
    * Возвращает список действий для панели конфига
    * 
    * @return array
    */
    public static function getDefaultValues()
    {
        return parent::getDefaultValues() + array(
            'tools' => array(
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxFixSortn', array(), 'templates-tools'),
                    'title' => t('Исправить сортировку блоков конструктора'),
                    'description' => t('Обновляет сортировочные индексы всех блоков в конструкторе сайта')
                )
            )
        );
    }
}