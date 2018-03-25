<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Photo\Config;
use \RS\Orm\Type;

/**
* Конфигурационный файл модуля
* @ingroup Photo
*/
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            'original_photos_resize' => new Type\Integer(array(
                'description' => t('Изменять размер оригинальной фотографии при загрузке'),
                'checkboxview' => array(1,0),
                'hint' => t('Включение данной опции позволит увеличить безопасность системы, а также увеличить скорость генерации изображений в последующем')
            )),
            'original_photos_width' => new Type\Integer(array(
                'description' => t('Максимальная ширина оригинала фотографии')
            )),
            'original_photos_height' => new Type\Integer(array(
                'description' => t('Максимальная высота оригинала фотографии')
            )),
            'product_sort_photo_desc' => new Type\Integer(array(
                'maxLength' => 1,
                'default' => 0,
                'description' => t('Сортировать добавленные фото в обратном порядке?'),
                'checkboxview' => array(1,0),
            )), 
        ));
    }

    /**
    * Возвращает значения свойств по-умолчанию
    * 
    * @return array
    */
    public static function getDefaultValues()
    {
        return parent::getDefaultValues() + array(
            'tools' => array(
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxDelUnlinkPhotos', array(), 'photo-tools'),
                    'title' => t('Удалить несвязанные фото'),
                    'description' => t('Удаляет оригиналы и миниатюры фотографий, на которые нет ссылок в базе'),
                    'confirm' => t('Вы действительно хотите удалить несвязанные фото?')
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxDelPreviewPhotos', array(), 'photo-tools'),
                    'title' => t('Удалить миниатюры фотографий'),
                    'description' => t('Удаляет автоматически сгенерированные по требованию шаблонов миниатюры фотографий'),
                    'confirm' => t('Вы действительно хотите удалить миниатюры всех фото?')
                )
            )
        );
    }       
}

