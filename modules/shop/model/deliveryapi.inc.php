<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

/**
* API функции для работы со способами доставки для текущего сайта
*/
class DeliveryApi extends \RS\Module\AbstractModel\EntityList
{
    protected static
        $types;

    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Delivery,
        array(
            'nameField' => 'title',
            'multisite' => true,
            'defaultOrder' => 'sortn',
            'sortField' => 'sortn'
        ));
    }
    
    /**
    * Возвращает Имеющиеся в системе обработчики типов доставок.
    * 
    * @return array
    */
    function getTypes()
    {
        if (self::$types === null) {
            $event_result = \RS\Event\Manager::fire('delivery.gettypes', array());
            $list = $event_result->getResult();
            self::$types = array();
            foreach($list as $delivery_type_object) {
                if (!($delivery_type_object instanceof DeliveryType\AbstractType)) {
                    throw new \rs\Exception(t('Тип доставки должен быть наследником \Shop\Model\DeliveryType\AbstractType'));
                }
                self::$types[$delivery_type_object->getShortName()] = $delivery_type_object;
            }
        }
        
        return self::$types;
    }
    
    /**
    * Возвращает массив ключ => название типа доставки
    * 
    * @return array
    */
    public static function getTypesAssoc()
    {
        $_this = new self();
        $result = array();
        foreach($_this->getTypes() as $key => $object) {
            $result[$key] = $object->getTitle();
        }
        return $result;
    }

    /**
     * Получение списка доставок для типа оплаты
     *
     * @return array
     * @throws \RS\Orm\Exception
     */
    public static function getListForOrder()
    {
        $groups = array(0 => array('title' => t('Без группы'))) +
            \RS\Orm\Request::make()
                ->from(new \Shop\Model\Orm\DeliveryDir())
                ->orderby('sortn')
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId()
                ))
                ->objects(null,'id');

        $_this = new self();
        $deliveries = $_this->clearFilter()
                            ->setOrder('sortn')
                            ->getList();

        $list = array();
        //Составим двухуровневый список
        if (!empty($deliveries)){
            foreach ($deliveries as $delivery){
                $title = $groups[$delivery['parent_id']]['title'] ?: t('Без группы');
                $list[$title][$delivery['id']] = $delivery;
            }
        }
        return $list;
    }

    /**
     * Получение списка доставок для типа оплаты
     *
     */
    public static function getListForPayment()
    {
        $list = parent::staticSelectList();
        $list = array('0' => t('-Все-')) + $list;
        return $list;
    }
    
    /**
    * Возвращает объект типа доставки по идентификатору
    * 
    * @param string $name
    */
    public static function getTypeByShortName($name)
    {
        $_this = new self();
        $list = $_this->getTypes();
        return isset($list[$name]) ? $list[$name] : new DeliveryType\Stub($name);
    }
    
    public static function getZonesList()
    {
        return array(0 => t(' - все - ')) + \Shop\Model\ZoneApi::staticSelectList();
    }
    
    /**
    * Устанавливает фильтр по магистральным поясам
    * 
    * @param array $zone - массив с магистральными поясами
    */
    public function setZoneFilter($zone)
    {
        $zone = array_merge(array(0), (array)$zone);
        $q = $this->queryObj()
            ->join(new Orm\DeliveryXZone(), 'DZ.delivery_id = A.id AND DZ.zone_id IN ('.implode(',', $zone).')', 'DZ');
    }
    
    /**
    * Возвращает массив классов отвечающих за самовывоз в системе
    * 
    * @return array
    */
    public static function getPickupPointClasses()
    {
        return array('myself');
    }
    
    /**
    * Возвращает список доступных доставок, указанного класса
    * 
    * @param array $classes - список классов
    * @param bool $inverse - если true - будут выбраны доставки не входящие в указанный список классов
    * @param \Shop\Model\Orm\Order $order - если указан объект заказа - вернутся доставки, подходящие под данный заказ
    */
    static function getDeliveriesByClasses($classes, $inverse = false, $order = null)
    {
        $_this = new self();
        $type = $inverse ? 'notin' : 'in';
        
        //Посмотрим есть ли доставки с типом самовывоз
        $_this->setFilter('public', 1)
              ->setFilter('class', $classes, $type);
        
        if ($order && $order->getCart()){ //Если есть объект заказа, то применим к нему дополниельные фильтры
            $cartdata = $order->getCart()->getCartData(false);
            //Проверим условие минимальной цены
            $_this->setFilter(array(
                array(
                    'min_price' => 0,
                    '|min_price:<=' => $cartdata['total_without_delivery_unformatted'],
                )
            ));
            //Проверим условие максимальной цены
            $_this->setFilter(array(
                array(
                    'max_price' => 0,
                    '|max_price:>' => $cartdata['total_without_delivery_unformatted'],
                )
            ));
            //Проверим условие минимального количества товаров
            $_this->setFilter(array(
                array(
                    'min_cnt' => 0,
                    '|min_cnt:<=' => $cartdata['items_count'],
                )
            )); 
        }
        
        return $_this->getList();
    }
     
    /**
    * Возвращает список пунктов самовывоза в системе.
    * Если передать объект заказа, то вернутся пункты подходящие под данный заказ
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */
    public static function getPickUpPoints($order = null)
    {
        return self::getDeliveriesByClasses(self::getPickupPointClasses(), false, $order);
    }
    
    /**
    * Проверяет есть пункты самовывоза в системе. Проверяет наличие таких доставок, а затем пунктов самовывоза, если на находит доставок этого типа
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * 
    * @return boolean
    */
    public static function isHavePickUpPoints($order = null)
    {
        $delivery_points = self::getPickUpPoints($order);
        return (!empty($delivery_points)) ? true : false;
    }
    
    /**
    * Проверяет наличие доступных в системе доставок по адресу
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * 
    * @return boolean
    */
    public static function isHaveToAddressDelivery($order = null)
    {
        $delivery_points = self::getDeliveriesByClasses(self::getPickupPointClasses(), true, $order);;
        return (!empty($delivery_points)) ? true : false;
    }
    
    /**
    * Возвращает список пользователей-курьеров
    * 
    * @return array
    */
    public static function getCourierList()
    {
        $courier_group = \RS\Config\Loader::byModule(__CLASS__)->courier_user_group;
        
        if ($courier_group) {
            return \RS\Orm\Request::make()
                ->select('U.*')
                ->from(new \Users\Model\Orm\User(), 'U')
                ->join(new \Users\Model\Orm\UserInGroup(), 'G.user = U.id', 'G')
                ->where(array(
                    'G.group' => $courier_group
                ))->objects(null, 'id');
        }
        return array();
    }
    
    /**
    * Возвращает ассоциативный массив для отображения списка курьеров
    * 
    * @param array $root - корневой элемент
    * @return array
    */
    public static function getCourierSelectList($root = array('- Не выбрано -'))
    {
        $couriers = self::getCourierList();
    
        $result = array();
        foreach($couriers as $courier) {
            $result[$courier['id']] = $courier->getFio();
        }
        return $root + $result;
    }

    /**
     * Возвращает доставки, которые необходимо отобразить на
     * этапе оформления заказа
     *
     * @param \Users\Model\Orm\User $user - объект пользователя
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param bool $check_public - возвращать только публичные доставки
     * @param bool $strict_toaddress_filter - не включать самовывоз к доставкам до адреса
     * @return \Shop\Model\Orm\Delivery[]
     */
    function getCheckoutDeliveryList(\Users\Model\Orm\User $user, \Shop\Model\Orm\Order $order, $check_public = true, $strict_toaddress_filter = false)
    {
        $my_type = $user['is_company'] ? 'company' : 'user';

        if ($check_public) {
            $this->setFilter('public', 1);
        }
        $this->setFilter('user_type', array('all', $my_type), 'in');

        if (!$order['only_pickup_points']){ //Если не только самовывозом
            //Получим все зоны
            $zone_api = new \Shop\Model\ZoneApi();
            $address  = $order->getAddress();
            $zones    = $zone_api->getZonesByRegionId($address['region_id'], $address['country_id'], $address['city_id']);

            $this->setZoneFilter($zones);
            
            if ($strict_toaddress_filter) {
                $this->setFilter('class', $this::getPickupPointClasses(), 'notin');
            }
        }else{ //Если только пункты самовывоза
            $this->setFilter('class', $this::getPickupPointClasses(), 'in');
        }

        $cartdata = $order->getCart()->getCartData(false);
        //Проверим условие минимальной цены
        $this->setFilter(array(
            array(
                'min_price' => 0,
                '|min_price:<=' => $cartdata['total_without_delivery_unformatted'],
            )
        ));
        //Проверим условие максимальной цены
        $this->setFilter(array(
            array(
                'max_price' => 0,
                '|max_price:>' => $cartdata['total_without_delivery_unformatted'],
            )
        ));
        //Проверим условие минимального веса
        $this->setFilter(array(
            array(
                'min_weight' => 0,
                '|min_weight:<=' => $cartdata['total_weight'],
            )
        ));
        //Проверим условие максимального веса
        $this->setFilter(array(
            array(
                'max_weight' => 0,
                '|max_weight:>' => $cartdata['total_weight'],
            )
        ));
        //Проверим условие минимального количества товаров
        $this->setFilter(array(
            array(
                'min_cnt' => 0,
                '|min_cnt:<=' => $cartdata['items_count'],
            )
        ));
        $this->queryObj()->groupby('id');
        $delivery_list = $this->getAssocList('id');

        // Событие для модификации списка доставок
        $result = \RS\Event\Manager::fire('checkout.delivery.list', array(
            'list' => $delivery_list,
            'order' => $order,
            'user' => $user
        ));

        list($delivery_list) = $result->extract();

        return $delivery_list;
    }
        
}
