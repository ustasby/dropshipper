<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace MobileSiteApp\Config;
use \RS\Orm\Type;

/**
* Конфигурационный файл модуля Мобильный сайт приложение
*/
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'default_theme' => new Type\Varchar(array(          
                    'runtime' => false, 
                    'description' => t('Шаблон по умолчанию'),
                    'list' => array(array('\MobileSiteApp\Model\TemplateManager','staticTemplatesList')),
                )),
                'allow_user_groups' => new Type\ArrayList(array(
                    'runtime' => false,            
                    'description' => t('Группы пользователей, для которых доступно данное приложение'),
                    'list' => array(array('\Users\Model\GroupApi','staticSelectList')),
                    'size' => 7,
                    'attr' => array(array('multiple' => true))
                )),
                'disable_buy' => new Type\Integer(array(
                    'description' => t('Скрыть корзину в приложении'),
                    'checkboxView' => array(1,0)
                )),
                'push_enable' => new Type\Integer(array(
                    'description' => t('Включить Push уведомления для данного приложения'),
                    'checkboxView' => array(1,0)
                )),
                'banner_zone' => new Type\Integer(array(
                    'description' => t('Баннерная зона'),
                    'list' => array(array('\Banners\Model\ZoneApi','staticAdminSelectList')),
                    'hint' => t('Баннерная зона, из которой будут выводиться баннеры на главной странице мобильного приложения')
                )),
                'mobile_phone' => new Type\Varchar(array(
                    'description' => t('Номер телефона для отображения в приложении'),
                    'hint' => t('Если пусто, то отображаться на будет')
                )),
                'root_dir' => new Type\Integer(array(
                    'description' => t('Корневая директория'),
                    'list' => array(array('\Catalog\Model\DirApi','selectList'), array(true)),
                    'hint' => t('На главной странице приложения будут отображены категории, являющиеся дочерними для указанной в данной опции')
                )),
                'tablet_root_dir_sizes' => new Type\Varchar(array(
                    'description' => t('Размеры отображения категорий для главной на планшете'),
                    'hint' => t('M - middle, s - small. Категории будут отображаться последовательно согласно схеме'),
                )),
                'products_pagesize' => new Type\Integer(array(
                    'description' => t('По сколько товаров показывать в категории'),
                    'hint' => t('Отвечает за количество подгружаемых единоразово товаров, остальные товары будут загружаться при прокрутке до последнего товара в списке')
                )),
                'menu_root_dir' => new Type\Integer(array(
                    'description' => t('Корневой элемент для меню'),
                    'list' => array(array('\Menu\Model\Api','selectList'), array(true)),
                    'hint' => t('В мобильном приложении будут отображены дочерние к выбранному здесь пункту меню')
                )),
                'top_products_dir' => new Type\Integer(array(
                    'description' => t('Категория топ товаров'),
                    'list' => array(array('\Catalog\Model\DirApi','specSelectList'), array(true)),
                    'hint' => t('Указывает категорию, из которой выбирать товары для отображения на главной активности приложения')
                )),
                'top_products_pagesize' => new Type\Integer(array(
                    'description' => t('Сколько товаров показывать в топе'),
                )),
                'top_products_order' => new Type\Varchar(array(
                    'description' => t('Поле сортировки топ товаров'),
                    'listFromArray' => array(array(
                        'id' => 'ID',
                        'title' => t('Название'),
                        
                        'num DESC' => t('По наличию'),
                        'id DESC' => t('ID обратн. порядок'),
                        'dateof DESC' => t('По новизне'),
                        'rating DESC' => t('По рейтингу'),
                    ))
                )),
                'mobile_products_size' => new Type\Integer(array(
                    'description' => t('Сколько товаров показывать на мобильном устройстве'),
                    'listFromArray' => array(array(
                        '12' => '1',
                        '6' => '2',
                        '4' => '3',
                        '3' => '4',
                    ))
                )),
                'tablet_products_size' => new Type\Integer(array(
                    'description' => t('Сколько товаров показывать на планшете'),
                    'listFromArray' => array(array(
                        '12' => '1',
                        '6' => '2',
                        '4' => '3',
                        '3' => '4',
                    ))
                )),
                'article_root_category' => new Type\Varchar(array(
                    'description' => t('Корневой элемент новостей'),
                    'hint' => t('С какой категории выводить'),
                    'listFromArray' => array(\Article\Model\CatApi::selectList(true))
                )),
                'enable_app_sticker' => new Type\Varchar(array(
                    'description' => t('Отображать стикер о том, что есть приложение у сайта'),
                    'checkboxView' => array(1,0)
                ))
        ));  
    }
}