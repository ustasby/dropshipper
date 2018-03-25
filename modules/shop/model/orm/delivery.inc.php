<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;

/**
* Способ доставки текущего сайта, присутствующий в списке выбора при оформлении заказа. 
* Содержит связь с модулем расчета.
*/
class Delivery extends \RS\Orm\OrmObject
{
    protected static
        $table = 'order_delivery';
    
    protected
        $cache_delivery;
        
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Название'),
                )),
                'admin_suffix' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Пояснение'),
                    'hint' => t('Отображается только в диалогах административной части<br>
                                используйте если у вас есть доставки с одинаковым названем')
                )), 
                'description' => new Type\Text(array(
                    'description' => t('Описание'),
                )),
                'picture' => new Type\Image(array(
                    'max_file_size' => 10000000,
                    'allow_file_types' => array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'),
                    'description' => t('Логотип'),
                )),
                'parent_id' => new Type\Integer(array(
                    'description' => t('Категория'),
                    'default' => 0,
                    'allowEmpty' => false,
                    'list' => array(array('\Shop\Model\DeliveryDirApi','selectList'))
                )),
                'xzone' => new Type\ArrayList(array(
                    'description' => t('Зоны'),
                    'list' => array(array('\Shop\Model\DeliveryApi', 'getZonesList')),
                    'attr' => array(array(
                        'size' => 10,
                        'multiple' => true,
                    )),
                )),
                'min_price' => new Type\Integer(array(
                    'description' => t('Минимальная сумма заказа'),
                    'default' => 0,
                    'allowEmpty' => false,
                    'hint' => t('Условие при котором, будет показываться доставка.<br/>0 - условие не действует.'),
                )),
                'max_price' => new Type\Integer(array(
                    'description' => t('Максимальная сумма заказа'),
                    'default' => 0,
                    'allowEmpty' => false,
                    'hint' => t('Условие при котором, будет показываться доставка.<br/>0 - условие не действует.'),
                )),
                'min_weight' => new Type\Integer(array(
                    'description' => t('Минимальный вес заказа гр.'),
                    'default' => 0,
                    'allowEmpty' => false,
                    'hint' => t('Условие при котором, будет показываться доставка.<br/>0 - условие не действует.'),
                )),
                'max_weight' => new Type\Integer(array(
                    'description' => t('Максимальный вес заказа гр.'),
                    'default' => 0,
                    'allowEmpty' => false,
                    'hint' => t('Условие при котором, будет показываться доставка.<br/>0 - условие не действует.'),
                )),
                'min_cnt' => new Type\Integer(array(
                    'description' => t('Минимальное количество товаров в заказе'),
                    'default' => 0,
                    'allowEmpty' => false,
                    'hint' => t('Условие при котором, будет показываться доставка.<br/>0 - условие не действует.'),
                )),
                'first_status' => new Type\Integer(array(
                    'description' => t('Стартовый статус заказа'),
                    'list' => array(array(__CLASS__, 'getFirstStatusList'))
                )),                
                'user_type' => new Type\Enum(array('all', 'user', 'company'), array(
                    'allowEmpty' => false,
                    'description' => t('Категория пользователей для данного способа доставки'),
                    'listFromArray' => array(array(
                        'all' => t('Все'),
                        'user' => t('Физические лица'),
                        'company' => t('Юридические лица')
                    ))
                )),
                'extrachange_discount' => new Type\Real(array(
                    'description' => t('Наценка/скидка на доставку'),
                    'hint' => t('Положительное число - наценка, число с минусом, это скидка. <br/> Например: -100'),
                    'template' => '%shop%/form/delivery/extrachangediscount.tpl',
                    'maxLength' => 11,
                    'decimal' => 4,
                    'default' => 0,
                )),
                'extrachange_discount_type' => new Type\Integer(array(
                    'description' => t('Тип скидки или наценки'),
                    'maxLength' => 1,
                    'listFromArray' => array(array(
                        0 => t('ед.'),
                        1 => '%',
                    )),
                    'default' => 0,
                    'visible' => false
                )),
                'public' => new Type\Integer(array(
                    'description' => t('Публичный'),
                    'maxLength' => 1,
                    'default' => 1,
                    'checkboxView' => array(1,0)
                )),
                'default' => new Type\Integer(array(
                    'description' => t('По умолчанию'),
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxView' => array(1,0),
                    'hint' => t('Включение данной опции у доставки, требующей указания дополнительных параметров,
                                совместно с настройкой "Не показывать шаг оформления заказа - доставка?"<br>
                                может привести к ошибкам.')
                )),
                'class' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Расчетный класс (тип доставки)'),
                    'meVisible' => false,
                    'template' => '%shop%/form/delivery/other.tpl',
                    'list' => array(array('\Shop\Model\DeliveryApi', 'getTypesAssoc'))
                )),
                '_serialized' => new Type\Text(array(
                    'description' => t('Параметры расчетного класса'),
                    'visible' => false,
                )),
                'data' => new Type\ArrayList(array(
                    'visible' => false
                )),
                'sortn' => new Type\Integer(array(
                    'maxLength' => '11',
                    'allowEmpty' => true,
                    'description' => t('Сорт. индекс'),
                    'visible' => false,
                )),
            t('Срок доставки'),
                'delivery_periods' => new Type\ArrayList(array(
                    'description' => t('Сроки доставки в регионы'),
                    'list' => array(array('\Shop\Model\DeliveryApi', 'getZonesList')),
                    'meVisible' => false,
                    'template' => '%shop%/form/delivery/delivery_periods.tpl'
                )),
                '_delivery_periods' => new Type\Text(array(
                    'description' => t('Сроки доставки в регионы (Сохранение данных)'),
                    'visible' => false
                )),
            t('Налоги'),
                '_tax_ids' => new Type\Varchar(array(
                    'description' => t('Налоги (сериализованные)'),
                    'visible' => false,
                )),
                'tax_ids' => new Type\ArrayList(array(
                    'description' => t('Налоги'),
                    'list' => array(array('\Shop\Model\TaxApi', 'staticSelectList')),
                    'attr' => array(array(
                        'size' => 5,
                        'multiple' => true,
                    )),
                )),
        ));
    }
    
    /**
    * Действия перед записью объекта
    * 
    * @param string $flag - insert или update
    * @return void
    */
    function beforeWrite($flag)
    {
        
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as max')
                ->from($this)
                ->where(array(
                    'site_id' => $this->__site_id->get()
                ))
                ->exec()->getOneField('max', 0) + 1;  
        }
        if (empty($this['xzone'])) {
            $this['xzone'] = array(0);
        }
        $this['_serialized'] = serialize($this['data']);

        // Сохранить заданные строки доставки

        if ($this->isModified('delivery_periods')){
            $this['_delivery_periods'] = serialize($this['delivery_periods']);
        }
        if ($this->isModified('tax_ids')){
            $this['_tax_ids'] = serialize($this['tax_ids']);
        }
    }
    
    /**
    * Действия после записи объекта
    * 
    * @param string $flag - insert или update
    * @return void
    */
    function afterWrite($flag)
    {
        // Удаляем старые связи с зонами
        $this->deleteZones();
            
        // Записываем новые зоны
        if(is_array($this->xzone))
        {   
            if(array_search(0, $this->xzone) !== false){
                $this->xzone = array(0);
            }
            foreach($this->xzone as $zone_id){
                $link = new DeliveryXZone();
                $link->delivery_id   = $this->id;
                $link->zone_id = $zone_id;
                $link->insert();
            }
        }
    }

    /**
    * Удалить все связи этой доставки с зонами
    * 
    */
    function deleteZones()
    {
        \RS\Orm\Request::make()->delete()
            ->from(new DeliveryXZone())
            ->where(array('delivery_id' => $this->id))
            ->exec();
    }
    
    /**
    * Заполнить поле xzone массивом идентификаторов зон
    * 
    */
    function fillZones()
    {
        $zones = \RS\Orm\Request::make()->select('zone_id')
            ->from(new DeliveryXZone())
            ->where(array('delivery_id' => $this->id))
            ->exec()->fetchSelected(null, 'zone_id');
        $this->xzone = $zones;
        if(empty($zones)){
            $this->xzone = array(0);
        }
    }
    
    /**
    * Возвращает клонированный объект доставки
    * @return Delivery
    */
    function cloneSelf()
    {
        /**
        * @var \Shop\Model\Orm\Delivery
        */
        $clone = parent::cloneSelf();

        //Клонируем фото, если нужно
        if ($clone['picture']){
           /**
           * @var \RS\Orm\Type\Image
           */
           $clone['picture'] = $clone->__picture->addFromUrl($clone->__picture->getFullPath());
        }
        return $clone;
    }

    /**
     * Действия полсле загрузки объекта
     */
    function afterObjectLoad()
    {
        $this['data'] = @unserialize($this['_serialized']);
        $this['delivery_periods'] = @unserialize($this['_delivery_periods']);
        $this['tax_ids'] = @unserialize($this['_tax_ids']);
    }
    
    /**
    * Производит валидацию текущих данных в свойствах
    * 
    * @return bool Возвращает true, если нет ошибок, иначе - false
    */
    function validate()
    {
        $this->getTypeObject()->validate($this);
        return parent::validate();
    }
    
    
    /**
    * Возвращает объект расчетного класса (типа доставки)
    * 
    * @return \Shop\Model\DeliveryType\AbstractType | false
    */
    function getTypeObject()
    {
        if ($this->cache_delivery === null) {
            $this->cache_delivery = clone \Shop\Model\DeliveryApi::getTypeByShortName($this['class']);
            $this->cache_delivery->loadOptions((array)$this['data']);
        }
        
        return $this->cache_delivery;
    }

    /**
     * Возвращает дополнительный HTML для публичной части,
     * если например нужен виджет с выбором для забора посылки
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     *
     * @return string
     */
    function getAddittionalHtml(\Shop\Model\Orm\Order $order = null){
       /**
       * @var \Shop\Model\DeliveryType\AbstractType $delivery_type
       */ 
       $delivery_type = $this->getTypeObject(); 
       return $delivery_type->getAddittionalHtml($this, $order);
    }
    
    /**
    * Возвращает список статусов заказа, для способа доставки
    * 
    * @return array
    */
    public static function getFirstStatusList()
    {
        $userstatus_api = new \Shop\Model\UserStatusApi();
        $list = $userstatus_api->getSelectList();
        return array(0 => t('По умолчанию (как у способа оплаты)')) + $list;
    }
    
    /**
    * Возвращает стоимость доставки 
    * 
    * @param Order $order текущий заказ пользователя
    * @param Address $address объект адреса доставки
    * @param bool $use_currency применять валюту заказа
    * @return string
    */
    function getDeliveryCost(Order $order, Address $address = null, $use_currency = true)
    {        
        $type_obj = $this->getTypeObject();
        $cost     = $type_obj->getDeliveryCost( $order, $address, $this, $use_currency );
        
        return $cost;
    }

    /**
    * Возвращает стоимость доставки в текстовом виде всегда в валюте заказа
    * 
    * @param Order $order текущий заказ пользователя
    * @param Address $address объект адреса доставки
    * @return string
    */
    function getDeliveryCostText(Order $order, Address $address = null)
    {        
        $type_obj = $this->getTypeObject();
        $cost     = $type_obj->getDeliveryCostText($order, $address, $this);
        return $cost;
    }

    /**
     * Возвращает дополнительный произвольный текст для данной доставки (обычно срок доставки)
     *
     * @param \Shop\Model\Orm\Order $order
     * @param \Shop\Model\Orm\Address|null $address
     * @return string
     */
    function getDeliveryExtraText(Order $order, Address $address = null)
    {
        $type_obj = $this->getTypeObject();
        $text     = $type_obj->getDeliveryExtraText($order, $address, $this);

        //Если доставка не вернула из её типа дополнительной информации, то посмотрим на указанную конкретно у самой доставки
        //Если такая имеется
        if (empty($text) && !empty($this['delivery_periods'])){
            //Получим все зоны
            $zone_api = new \Shop\Model\ZoneApi();
            $zones    = $zone_api->getZonesByRegionId($address['region_id'], $address['country_id'], $address['city_id']);
            foreach ($this['delivery_periods'] as $delivery_period){
                if (empty($zones) && ($delivery_period['zone'] == 0)){ //Если зона все
                    $text = $delivery_period['text'].' '.$delivery_period['days_min'].' '.$delivery_period['days_max'];
                }elseif (!empty($zones) && ($delivery_period['zone'] == 0)){
                    $text = $delivery_period['text'].' '.$delivery_period['days_min'].' '.$delivery_period['days_max'];
                }else{
                    if (in_array($delivery_period['zone'], $zones)){
                        $text = $delivery_period['text'].' '.$delivery_period['days_min'].' '.$delivery_period['days_max'];
                    }
                }
                if (!empty($text)){ //Если срок найден.
                    break;
                }
            }
        }

        return $text;
    }
}
