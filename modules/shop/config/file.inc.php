<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Config;
use RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
    
    public
        //Описывает за что отвечает каждый из 8-ми бит числа, обозначающего уровень доступа
        //Каждый модуль может определить свою таблицу значимости битов
        $access_bit = array(
            'Чтение', //1-й бит - будет означать Разрешение на Чтение
            'Запись', //2-й бит будет означать разрешение на Запись
            'Оформление заказа'
        );
    
    function _init()
    {
        parent::_init()->append(array(
            'basketminlimit' => new Type\Decimal(array(
                'description' => t('Минимальная сумма заказа (в базовой валюте)'),
                'maxLength' => 20,
                'decimal' => 2
            )),
            'basketminweightlimit' => new Type\Decimal(array(
                'description' => t('Минимальный суммарный вес товаров заказа '),
                'maxLength' => 20,
                'decimal' => 2
            )),
            'check_quantity' => new Type\Integer(array(
                'description' => t('Запретить оформление заказа, если товаров недостаточно на складе'),
                'checkboxView' => array(1, 0)
            )),
            'first_order_status' => new Type\Integer(array(
                'description' => t('Стартовый статус заказа (по-умолчанию)'),
                'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList')),
                'hint' => t('Данная настройка перекрывается настройкой способа оплаты, а затем настройкой способа доставки.<br>'.
                            'Важно: система ожидает прием on-line платежей и предоставляет ссылку на оплату только в статусе - Ожидает оплату', null, 'подсказка опции first_order_status')
            )),
            'user_orders_page_size' => new Type\Integer(array(
                'description' => t('Количество заказов в истории на одной странице')
            )),
            'use_personal_account' => new Type\Integer(array(
                'description' => t('Использовать лицевой счет'),
                'checkboxView' => array(1, 0)
            )),
            'reservation' => new Type\Integer(array(
                'description' => t('Разрешить предварительный заказ товаров с нулевым остатком'),
                'hint' => t('Актуально только при включенной опции `Запретить оформление заказа, если товаров недостаточно на складе`'),
                'listFromArray' => array(array(
                    0 => t('Нет'),
                    1 => t('Да')
                ))
            )),
            'allow_concomitant_count_edit' => new Type\Integer(array(
                'description' => t('Разрешить редактирование количества сопутствующих товаров в корзине.'),
                'checkboxView' => array(1, 0)
            )),
            'source_cost' => new Type\Integer(array(
                'description' => t('Закупочная цена товаров'),
                'hint' => t('Цена должна отражать ваши расходы на приобретение товара. Данная цена будет использована для расчета дохода, полученного при продаже товара. Расчет будет по форуме ЦЕНА ПРОДАЖИ - ЗАКУПОЧНАЯ ЦЕНА.'),
                'list' => array(array('\Catalog\Model\Costapi', 'staticSelectList'), true)
            )),
            'auto_change_status' => new Type\Integer(array(
                'maxLength' => 1,
                'checkboxView' => array(1, 0),
                'description' => t('Автоматически изменять статус заказа, который находится в статусе L более N дней'),
                'hint' => t('Опция требует, чтобы в системе был настроен внутренний планировщик. С помощью данной опции удобно автоматически отменять неоплаченные заказы. Проверка статусов заказов происходит один раз в сутки.'),
                'template' => '%shop%/form/config/auto_change_status.tpl'
            )),
            'auto_change_timeout_days' => new Type\Integer(array(
                'description' => t('Кол-во дней(N), после которых нужно автоматически менять статус заказа'),
                'visible' => false
            )),
            'auto_change_from_status' => new Type\ArrayList(array(
                'runtime' => false,
                'description' => t('Список статусов (L), в которых должен находиться заказ для автосмены'),
                'hint' => t('Опция требует, чтобы в системе был настроен внутренний планировщик'),
                'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList')),
                'multiple' => true,
                'checkboxListView' => true,
                'visible' => false
            )),
            'auto_change_to_status' => new Type\Integer(array(
                'description' => t('Статус, на который следует переключать заказ, если он находится в статусе L более N дней'),
                'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => t('- Не выбрано -'))),
                'visible' => false
            )),
            'auto_send_supply_notice' => new Type\Integer(array(
                'description' => t('Автоматически отправлять сообщения о поступлении товара'),
                'hint' => t('Для работы опции требуется настроенный внутренний планировщик'),
                'checkboxView' => array(1,0)
            )),
            'courier_user_group' => new Type\Varchar(array(
                'description' => t('Группа, пользователи которой считаются курьерами'),
                'list' => array(array('\Users\Model\GroupApi','staticSelectList'), array(0 => t('Не выбрано'))),
            )),
            'ban_courier_del' => new Type\Integer(array(
                'description' => t('Запретить курьерам удалять товары из заказа'),
                'default' => 0,
                'checkboxView' => array(1,0)
            )),
            'remove_nopublic_from_cart' => new Type\Integer(array(
                'description' => t('Удалять товары из корзины, которые были скрыты'),
                'checkboxView' => array(1,0)
            )),
            t('Дополнительные поля'),
                '__userfields__' => new Type\UserTemplate('%shop%/form/config/userfield.tpl'),
                'userfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные поля'),
                    'runtime' => false,
                    'visible' => false
                )),
            t('Оформление заказа'),
                'require_address' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Адрес - обязательное поле?'),
                    'checkboxview' => array(1,0)
                )),
                'require_zipcode' => new Type\Integer(array(
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxview' => array(1, 0),
                    'description' => t('Индекс - обязательное?')
                )),
                'use_geolocation_address' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Заполнять город, регион и страну используя геолокацию?'),
                    'checkboxView' => array(1, 0)
                )),
                'require_email_in_noregister' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Поле E-mail является обязательным?<br/>(Этап без регистрации)', null, 'название опции require_email_in_noregister'),
                    'checkboxView' => array(1, 0)
                )),
                'require_phone_in_noregister' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Поле телефон является обязательным?<br/>(Этап без регистрации)', null, 'название опции require_phone_in_noregister'),
                    'checkboxView' => array(1, 0)
                )),
                'myself_delivery_is_default' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Выбирать "самовывоз" по умолчанию'),
                    'checkboxView' => array(1, 0),
                    'default' => 0,
                )),
                'require_license_agree' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Отображать условия продаж?'),
                    'checkboxView' => array(1, 0)
                )),
                'license_agreement' => new Type\Richtext(array(
                    'description' => t('Условия продаж'),
                )),
                'use_generated_order_num' => new Type\Integer(array(
                    'maxLength' => 1,
                    'default' => 0,
                    'description' => t('Использовать генерируемый идентификатор заказа?'),
                    'hint' => t('Этот уникальный номер будет использоваться вместо порядкового номера заказа'),
                    'checkboxView' => array(1, 0)
                )),
                'generated_ordernum_mask' => new Type\Varchar(array(
                    'maxLength' => 20,
                    'description' => t('Маска генерируемого номера'),
                    'hint' => t('Маска по которой формируется, уникальный номер заказа.<br/> {n} - обязательный тег означающий уникальный номер.', null, 'подсказка опции generated_ordernum_mask'),
                    'default' => '{n}'
                )),
                'generated_ordernum_numbers' => new Type\Integer(array(
                    'maxLength' => 11,
                    'default' => 6,
                    'description' => t('Количество символов-цифр генерируемого уникального номера заказа')
                )),
                'hide_delivery' => new Type\Integer(array(
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxview' => array(1, 0),
                    'description' => t('Не показывать шаг оформления заказа - доставка?')
                )),
                'hide_payment' => new Type\Integer(array(
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxview' => array(1, 0),
                    'description' => t('Не показывать шаг оформления заказа - оплата?')
                )),
                'manager_group' => new Type\Varchar(array(
                    'description' => t('Группа, пользователи которой считаются менеджерами заказов'),
                    'hint' => t('Пользователей данной группы можно назначать на ведение заказов'),
                    'default' => 0,
                    'list' => array(array('\Users\Model\GroupApi','staticSelectList'), array(0 => t('Не задано')))
                )),                
                'set_random_manager' => new Type\Integer(array(
                    'description' => t('Устанавливать случайного менеджера при создании заказа'),
                    'hint' => t('Для данной опции должна быть задана группа пользователей-менеджеров.'),
                    'checkboxView' => array(1, 0)
                )),
                'cashregister_class' => new Type\Varchar(array(
                    'description' => t('Класс для обмена информацией с кассами'),
                    'list' => array(array('\Shop\Model\CashRegisterApi', 'getStaticTypes'))
                )), 
                'cashregister_enable_log' => new Type\Integer(array(
                    'description' => t('Включить лог обмена информацией с кассами'),
                    'checkboxView' => array(1, 0)
                )),
                'cashregister_enable_auto_check' => new Type\Integer(array(
                    'description' => t('Включить автоматический запрос на проверку состояния чека?'),
                    'hint' => t('Будет проверяться раз в минуту'),
                    'checkboxView' => array(1, 0)
                )),
                'ofd' => new Type\Varchar(array(
                    'description' => t('Платформа ОФД'),
                    'hint' => t('Отвечает за формирование правильной ссылки на чек.'),
                    'list' => array(array('\Shop\Model\CashRegisterApi', 'getStaticOFDList')),
                )),
            t('Оформление возврата товара'),
                'return_enable' => new Type\Integer(array(
                    'description' => t('Включить функциональность возвратов'),
                    'hint' => t('Влияет на отображение пункта `Мои возвраты` в меню личного кабинета'),
                    'checkboxView' => array(1,0)
                )),
                'return_rules' => new Type\Richtext(array(
                    'description' => t('Правила возврата товаров'),
                )),
                'return_print_form_tpl' => new Type\Template(array(
                    'description' => t('Шаблон заявления на возврат товаров'),
                    'only_themes' => false
                )),
            t('Купоны на скидку'),
                'discount_code_len' => new Type\Integer(array(
                    'description' => t('Длина кода купона на скидку'),
                    'hint' => t('Такая длина будет использована при автоматической генерации номера купона')
                )),
                'discount_base_cost_type' => new Type\Integer(array(
                    'description' => t('Базовый тип цен, от которого рассчитывать скидку по купону'),
                    'hint' => t('Если выбрано `по умолчанию`, то скидка будет рассчитана исходя из текущей цены пользователя. Если выбран базовый тип цен, и купон предоставляет бОльшую цену, чем текущая цена пользователя, то купон не применяется.'),
                    'list' => array(array('\Catalog\Model\Costapi', 'staticSelectList'), true)
                ))
        ));
    }
    
    /**
    * Функция срабатывает перед записью конфига
    * 
    * @param string $flag - insert или update
    * @return void
    */
    function beforeWrite($flag)
    {
        if ($flag == self::UPDATE_FLAG) {
            //Проверим на соотвествие конструкции
            if (empty($this['generated_ordernum_mask'])||(mb_stripos($this['generated_ordernum_mask'],'{n}') === false)){
                $this['generated_ordernum_mask'] = '{n}';
            }
        }
    }
    
    
    /**
    * Возвращает объект, отвечающий за работу с пользовательскими полями.
    * 
    * @return \RS\Config\UserFieldsManager
    */
    function getUserFieldsManager()
    {
        return new \RS\Config\UserFieldsManager($this['userfields'], null, 'userfields');
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
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('ajaxCalcProfit', array(), 'shop-tools'),
                    'title' => t('Пересчитать доходность заказов'),
                    'description' => t('Рассчитывает доходность заказов на основе Закупочной цены товара. Показатель доходности может использоваться другими модулями.'),
                    'confirm' => t('Вы действительно хотите пересчитать доходность заказов?')
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('showCashRegisterLog', array(), 'shop-tools'),
                    'title' => t('Просмотреть лог запросов обмена информацией с кассами'),
                    'description' => t('Открывает в новом окне журнал обмена данными с кассами'),
                    'target' => '_blank',
                    'class' => ' ',
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl('deleteCashRegisterLog', array(), 'shop-tools'),
                    'title' => t('Очистить лог запросов обмена информацией с кассами'),
                    'description' => t('Удаляет лог файл обмена информацией с кассами'),
                ),
                array(
                    'url' => \RS\Router\Manager::obj()->getAdminUrl(false, array(), 'shop-substatusctrl'),
                    'title' => t('Настроить причины отмены заказа'),
                    'description' => t('Здесь вы сможете создать, изменить, удалить причину отмены заказа'),
                    'class' => ' '
                )
            )
        );
    }    
    
}

