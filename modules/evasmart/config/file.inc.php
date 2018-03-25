<?php
namespace Evasmart\Config;
use Files\Model\FilesType\CatalogProduct;
use RS\Orm\Type;

/**
* Класс конфигурации модуля
*/
class File extends \RS\Orm\ConfigObject
{

    function _init()
    {
        parent::_init()->append(array(
            'bill_min_price' => new Type\Integer(array(
                'description' => t('Минимальная безналичная сумма заказа'),
            )),
            'bill_payment' => new Type\Integer(array(
                'description' => t('Безналичная оплата'),
                'list' => array(array('\Shop\Model\PaymentApi', 'staticSelectList'))
            )),

            'cost' => new Type\Integer(array(
                'description' => t('Цена дропшиперов'),
                'list' => array(array('\Catalog\Model\Costapi', 'staticSelectList'))
            )),
            'category' => new Type\Integer(array(
                'description' => t('Каталог по умолчанию'),
                'list' => array(array('\Catalog\Model\Dirapi', 'staticSelectList'))
            )),

            'number_excluding' => new Type\Text(array(
                'description' => t('Исключенные артикулы'),
            )),
            'unit' => new Type\Integer(array(
                'description' => t('Единица измерения'),
                'List' => array(array('\Catalog\Model\UnitApi', 'selectList')),
            )),
            'mat'=> new Type\Text(array(
                'description' => t('Материал основы'),
            )),
            'color_romb'=> new Type\Text(array(
                'description' => t('Цвет основы, ромб'),
            )),
            'color_sota'=> new Type\Text(array(
                'description' => t('Цвет основы, сота'),
            )),
            'color_kant'=> new Type\Text(array(
                'description' => t('Цвет канта'),
            )),

            'head_scripts' => new Type\Text(array(
                'description' => t('HTML-код для размещения в секции HEAD'),
                'hint' => t('Здесь можно разместить инструкции по подключению сторонних скриптов, например &lt;script type="text/javascript" src="..."&gt;. Если необходимо разместить несколько кодов, то размещайте каждый новый скрипт с новой строки.')
            )),
            'footer_scripts' => new Type\Text(array(
                'description' => t('HTML-код для размещения в конце страницы, перед закрывающим тегом BODY'),
                'hint' => t('Если необходимо разместить несколько кодов, то размещайте каждый код с новой строки')
            )),
            'width' => new Type\Integer(array(
                'description' => t('Свойство со значением ширины товара(см)'),
                'default' => 0,
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),
            'height' => new Type\Integer(array(
                'description' => t('Свойство со значением высоты товара(см)'),
                'default' => 0,
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),
            'length' => new Type\Integer(array(
                'description' => t('Свойство со значением длинны товара(см)'),
                'default' => 0,
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),

        ));

    }






}