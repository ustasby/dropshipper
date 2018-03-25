<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Config;

use \RS\Orm\Type;

/**
* @defgroup Catalog Catalog(Каталог товаров)
* Модуль предоставляет возможность управляь списками товаров, валют, характеристик, единиц измерения, типов цен.
*/

/**
* Конфигурационный файл модуля Каталог товаров
* @ingroup Catalog
*/
class File extends \RS\Orm\ConfigObject
{
    const

        ACTION_NOTHING      = "nothing",
        ACTION_CLEAR_STOCKS = "clear_stocks",
        ACTION_DEACTIVATE   = "deactivate",
        ACTION_REMOVE       = "remove";



    public
        $access_bit = array(
            'Чтение', //1-й бит - будет означать Разрешение на Чтение
            'Запись', //2-й бит будет означать разрешение на Запись
            'Оформление заказа' //3-й бит нужен для demo режима. в реальном режиме флаг должен быть включен вместе с флагом "запись"
        );
    
    public function _init()
    {
        parent::_init()->append(array(
            'default_cost' => new Type\Integer(array(
                'description' => t('Цена по умолчанию'),
                'list' => array(array('\Catalog\Model\Costapi', 'staticSelectList'))
            )),
            'old_cost' => new Type\Integer(array(
                'description' => t('Старая(зачеркнутая) цена'),
                'list' => array(array('\Catalog\Model\Costapi', 'staticSelectList'), true)
            )),
            'hide_unobtainable_goods' => new Type\Varchar(array(
                'description' => t('Скрывать товары с нулевым остатком'),
                'listfromarray' => array(array(
                    'Y' => t('Да'),
                    'N' => t('Нет'))
                ),
                'attr' => array(array('size' => 1))
            )),
            'list_page_size' => new Type\Integer(array(
                'description' => t('Количество товаров на одной странице списка')
            )),
            'items_on_page' => new Type\Varchar(array(
                'description' => t('Количество товаров на странице категории. Укажите через запятую, если нужно предоставить выбор'),
                'hint' => t('Например: 12,48,96')
            )),
            'list_default_order' => new Type\Varchar(array(
                'description' => t('Сортировка по умолчанию на странице со списком товаров'),
                'hint' => t('Удалите куки сайта, если Вы поменяли данный параметр, чтобы увидеть результат'),
                'listFromArray' => array(array(
                    'sortn' => t('По весу'),
                    'dateof' => t('По дате'),
                    'rating' => t('По рейтингу'),
                    'cost' => t('По цене'),
                    'title' => t('По наименованию товара'),
                    'num' => t('По наличию'),
                    'barcode' => t('По артикулу')
                ))
            )),
            'list_default_order_direction' => new Type\Varchar(array(
                'description' => t('Направление сортировки по умолчанию на странице со списком товаров'),
                'hint' => t('Удалите куки сайта, если Вы поменяли данный параметр, чтобы увидеть результат'),
                'listFromArray' => array(array(
                    'desc' => t('По убыванию'),
                    'asc' => t('По возрастанию')
                ))
            )),
            'list_order_instok_first' => new Type\Integer(array(
                'description' => t('Отображать в начале товары в наличии'),
                'checkboxView' => array(1,0),
                'default' => 0
            )),
            'list_default_view_as' => new Type\Varchar(array(
                'description' => t('Отображать по умолчанию товары в каталоге'),
                'listFromArray' => array(array(
                    'blocks' => t('В виде блоков'),
                    'table' => t('В виде таблицы')
                ))
            )),
            'default_weight' => new Type\Real(array(
                'description' => t('Вес одного товара по-умолчанию'),
                'hint' => t('Данное значение можно переустановить в настройках категории или у самого товара')
            )),
            'weight_unit' => new Type\Varchar(array(
                'description' => t('Единица измерения веса товаров'),
                'list' => array(array('\Catalog\Model\Api', 'getWeightUnitsTitles'))
            )),
            'default_unit' => new Type\Integer(array(
                'description' => t('Единица измерения по-умолчанию'),
                'default' => t('грамм'),
                'List' => array(array('\Catalog\Model\UnitApi', 'selectList'))
            )),
            'concat_dir_meta' => new Type\Integer(array(
                'description' => t('Дописывать мета теги категорий к товару'),
                'hint' => t('Данная опция имеет значение, когда мета данные не заданы у товара.'),
                'checkboxView' => array(1,0)
            )),
            'auto_barcode' => new Type\Varchar(array(
                'description' => t('Синтаксис для автогенерации артикула в новом товаре'),
                'maxLength'   => 60,
                'hint' => t('n - след. номер товара<br/>цифра - количество цифр')
            )),
            'disable_search_index' => new Type\Varchar(array(
                'description' => t('Отключить добавление товаров ко внутреннему поисковому индексу'),
                'checkboxview' => array(1,0),
                'hint' => t('Данный флаг следует устанавливать только при использовании сторонних поисковых сервисов на сайте')
            )),
            'price_round' => new Type\Decimal(array(
                'description' => t('Округлять цены при внутренних пересчётах до'),
                'hint' => t('Дробная часть указывается через точку<br/>
                            Округление происходит <b>в большую сторону</b>,<br/>
                            результат округления кратен значению:<br/>
                            <b>1</b> - округлять до целых (13,5678 = 14)<br/>
                            <b>0.1</b> - до десятых (13,5678 = 13,6)<br/>
                            <b>10</b> - до десятков (13,5678 = 20)<br/>
                            <b>5</b> - до кратного пяти (13,5678 = 15).<br/><br/>
                            После изменения данной настройки небходимо "пересчитать цены" (пересохранить любую валюту)
                ', array(), 'Описание поля `Округлять цены при внутренних пересчётах до`'),
                'allowEmpty' => false,
                'default' => '0.01'
            )),
            'cbr_link' => new Type\Varchar(array(
                'description' => t('Альтернативный адрес XML API ЦБ РФ'),
                'maxLength'   => 255,
                'hint' => t('Это url с которого будет получена информация для получения курсов валют.<br/> 
                По умолчанию, если поле пустое - используется внутренняя константа с адресом.<br/>
                http://www.cbr.ru/scripts/XML_daily.asp')
            )),
            'cbr_auto_update_interval' => new Type\Integer(array(
                'description' => t('Как часто обновлять курсы валют'),
                'listFromArray' => array(array(
                    '0' => t('Никогда'),
                    '1440' => t('Раз в сутки'),
                    '720' => t('Каждые 12 часов'),
                    '360' => t('Каждые 6 часов'),
                    '180' => t('Каждые 3 часа')
                )),
                'default' => 1440
            )),
            'cbr_percent_update' => new Type\Integer(array(
                'description' => t('Количество процентов, на которое должен отличатся прошлый курс валюты для обновления'),
                'maxLength'   => 11,
                'default' => 0,
                'hint' => t('Если 0, то процент не учитывается')                                                           
            )),
            'use_offer_unit' => new Type\Integer(array(
                'description' => t('Использовать единицы измерения у комлектаций'),
                'maxLength'   => 1,
                'checkboxview' => array(1,0)                                                   
            )),
            'import_photos_timeout' => new Type\Integer(array(
                'description' => t('Время выполнения одного шага импорта фотографий, сек.')
            )),
            'show_all_products' => new Type\Integer(array(
                'description' => t('Показывать все товары по маршруту /catalog/?'),
                'checkboxview' => array(1,0),
            )),
            'price_like_slider' => new Type\Integer(array(
                'description' => t('Показывать фильтр по цене в виде слайдера?'),
                'checkboxview' => array(1,0),
            )),
            'search_fields' => new Type\ArrayList(array(
                'description' => t('Поля, которые должны войти в поисковый индекс товара (помимо названия).'),
                'hint' => t('После изменения, переиндексируйте товары (ссылка справа)'),
                'Attr' => array(array('size' => 5, 'multiple' => 'multiple', 'class' => 'multiselect')),
                'ListFromArray' => array(array(
                    'properties' => t('Характеристики'),
                    'barcode' => t('Артикул'),
                    'brand' => t('Бренд'),
                    'short_description' => t('Короткое описание'),
                    'meta_keywords' => t('Мета ключевые слова')
                )),     
                'CheckboxListView' => true,
                'runtime' => false,
            )),
            'not_public_product_404' => new Type\Integer(array(
                'description' => t('Отдавать 404 ответ сервера у скрытых товаров?'),
                'checkboxview' => array(1,0),
            )),
            'link_property_to_offer_amount' => new Type\Integer(array(
                'description' => t('Учитывать остатки комплектаций товаров в фильтрах при использовании многомерных комплектаций'),
                'hint' => t('Значения характеристик товара будут отображаться в фильтре в зависимости от наличия комплектации с идентичной характеристикой.'),
                'checkboxView' => array(1,0)
            )),
            t('Купить в один клик'),
                '__clickfields__' => new Type\UserTemplate('%catalog%/form/config/userfield.tpl'),
                'clickfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные поля'),
                    'runtime' => false,
                    'visible' => false
                )),
                'buyinoneclick' => new Type\Integer(array(
                    'description' => t('Включить отображение?'),
                    'checkboxview' => array(1,0),
                )),
                'dont_buy_when_null' => new Type\Integer(array(
                    'description' => t('Не разрешать покупки в 1 клик, если товаров недостаточно на складе'),
                    'checkboxview' => array(1,0),
                )),
                'oneclick_name_required' => new Type\Integer(array(
                    'description' => t('Поле "Ваше имя" является обязательным?'),
                    'checkboxview' => array(1,0),
                )),
            t('Обмен данными в CSV'),
                'csv_id_fields' => new Type\ArrayList(array(
                    'runtime' => false,
                    'description' => t('Поля для идентификации товара при импорте (удерживая CTRL можно выбрать несколько полей)'),
                    'hint' => t('Во время импорта данных из CSV файла, система сперва будет обновлять товары, у которых будет совпадение значений по указанным здесь колонкам. В противном случае будет создаваться новый товар'),
                    'list' => array(array('\Catalog\Model\CsvSchema\Product','getPossibleIdFields')),
                    'size' => 7,
                    'attr' => array(array('multiple' => true))
                )),
                'csv_offer_product_search_field' => new Type\Varchar(array(
                    'runtime' => false,
                    'description' => t('Поле идентификации товара во время импорта CSV комплектаций'),
                    'hint' => t('Данные в колонке Товар у CSV файла комплектаций будет сравниваться с указанным здесь полем товара для связи'),
                    'list' => array(array('\Catalog\Model\CsvSchema\Offer','getPossibleProductFields')),
                )),
                'csv_offer_search_field' => new Type\Varchar(array(
                    'description' => t('Поле идентификации комплектации'),
                    'list' => array(array('\Catalog\Model\CsvSchema\Offer','getPossibleOffersFields')),
                )),
            t('Бренды'),
                'brand_products_specdir' => new Type\Integer(array(
                    'description' => t('Спецкатегория, из которой выводить товары на странице бренда'),
                    'list' => array(array('\Catalog\Model\DirApi', 'specSelectList'), true)
                    
                )),
                'brand_products_cnt' => new Type\Integer(array(
                    'description' => t('Кол-во товаров из спец. категории<br/> на странице бренда:'),
                )),
            t('Склады'),
                'warehouse_sticks' => new Type\Varchar(array(
                    'description' => t('Градация наличия товара на складах'),
                    'hint' => t('Перечислите через запятую, количество товара,<br/> 
                    которое будет соответствовать 1, 2, 3-м и т.д. "деленям"<br/> наличия данного товара на складе')
                )),
            t('Настройки импорта YML'),
                'yuml_import_setting' => new Type\Varchar(array(
                    'description' => t('Корневая категория импорта'),
                    'hint' => t('Корневая категория для новых категорий'),
                    'list' => array(array('\Catalog\Model\Dirapi', 'selectList'))
                )),
                'import_yml_timeout' => new Type\Integer(array(
                    'description' => t('Время выполнения импорта продуктов из yml, сек.'),
                    'default' => 26
                )),
                        'import_yml_cost_id' => new Type\Integer(array(
                    'description' => t('Тип цен, в который будет записываться цена товаров во время импорта продуктов из yml'),
                    'list' => array(array('\Catalog\Model\Costapi', 'staticSelectList'), true)
                )),
            'catalog_element_action' => new Type\Varchar(array(
                'maxLength' => '50',
                'description' => t('Что делать с товарами, отсутствующими в файле импорта'),
                'listfromarray' => array(array(
                    self::ACTION_NOTHING      => t('Ничего'),
                    self::ACTION_CLEAR_STOCKS => t('Обнулять остаток'),
                    self::ACTION_DEACTIVATE   => t('Деактивировать'),
                    self::ACTION_REMOVE       => t('Удалить')
                )
                ),
            )),
            'catalog_section_action' => new Type\Varchar(array(
                'maxLength' => '50',
                'description' => t('Что делать с категориями, отсутствующими в файле импорта'),
                'listfromarray' => array(array(
                    self::ACTION_NOTHING    => t('Ничего'),
                    self::ACTION_DEACTIVATE => t('Деактивировать'),
                    self::ACTION_REMOVE     => t('Удалить')
                )
                ),
            )),
            'dont_update_fields' => new Type\ArrayList(array(
                'description' => t('Поля товара, которые не следует обновлять'),
                'Attr' => array(array('size' => 5,'multiple' => 'multiple', 'class' => 'multiselect')),
                'List' => array(array('\Catalog\Model\Importymlapi', 'getUpdatableProductFields')),
                'CheckboxListView' => true,
                'runtime' => false,
            )),
        ));
    }
    
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
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxCleanProperty', array(), 'catalog-tools'),
                    'title' => t('Удалить несвязанные характеристики'),
                    'description' => t('Удаляет характеристики и группы, которые не задействованы в товарах или категориях'),
                    'confirm' => t('Вы действительно хотите удалить несвязанные характеристики?')
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxCleanOffers', array(), 'catalog-tools'),
                    'title' => t('Удалить несвязанные комплектации'),
                    'description' => t('Удалит несвязанные комплектации, которые могли остаться в базе после отмены создания товара'),
                    'confirm' => t('Вы действительно хотите удалить несвязанные комплектации?')
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxCheckAliases', array(), 'catalog-tools'),
                    'title' => t('Добавить ЧПУ имена товарам и категориям'),
                    'description' => t('Добавит символьный идентификатор (методом транслитерации) товарам и категориям, у которых он отсутствует.'),
                    'confirm' => t('Вы действительно хотите добавить ЧПУ имена товарам и категориям?')                    
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxCheckBrandsAliases', array(), 'catalog-tools'),
                    'title' => t('Добавить ЧПУ имена брендам'),
                    'description' => t('Добавит символьный идентификатор (методом транслитерации) брендам, у которых он отсутствует.'),
                    'confirm' => t('Вы действительно хотите добавить ЧПУ имена брендам?')
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxReIndexProducts', array(), 'catalog-tools'),
                    'title' => t('Переиндексировать товары'),
                    'description' => t('Построит заново поисковый индекс по товарам'),
                    'confirm' => t('Вы действительно хотите переиндексировать все товары?')                    
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxCleanImportHash', array(), 'catalog-tools'),
                    'title' => t('Сбросить хеши импрота'),
                    'description' => t('Удаляет данные, использующиеся для ускорения повторного импорта'),
                    'confirm' => t('Вы действительно хотите удалить все хешированные данные импорта?')                    
                ),
            )
        );
    }

    /**
     * Действия перед записью
     *
     * @param string $flag - insert или update
     * @return void
     */
    public function beforeWrite($flag)
    {
        parent::beforeWrite($flag);
        
        $this->before_state = new self();
        $this->before_state->load();
    }

    /**
     * Действия после записи
     *
     * @param string $flag - insert или update
     * @return void
     */
    public function afterWrite($flag)
    {
        parent::afterWrite($flag);
        
        if ($this['hide_unobtainable_goods'] != $this->before_state['hide_unobtainable_goods']) {
            \Catalog\Model\Dirapi::updateCounts(); //Обновляем счетчики у категорий
        }
        
        if ($this['link_property_to_offer_amount'] && !$this->before_state['link_property_to_offer_amount']) {
            $offer_api = new \Catalog\Model\OfferApi();
            $offer_api->updateLinkedPropertiesForAllProducts();
        }
    }
    
    /**
    * Возвращает объект, отвечающий за работу с пользовательскими полями.
    * 
    * @return \RS\Config\UserFieldsManager
    */
    public function getClickFieldsManager()
    {
        return new \RS\Config\UserFieldsManager($this['clickfields'], null, 'clickfields');
    }
    
    /**
    * Возвращает сокращённое обозначение текущей единицы измерения веса
    * 
    * @return string
    */
    public function getShortWeightUnit()
    {
        $units_list = \Catalog\Model\Api::getWeightUnits();
        if (isset($units_list[$this->weight_unit])) {
            return $units_list[$this->weight_unit]['short_title'];
        }
        return $units_list[\Catalog\Model\Api::WEIGHT_UNIT_G]['short_title'];
    }
}
