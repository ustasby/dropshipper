<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;
use RS\Application\Auth;
use \RS\Orm\Type,
    \RS\Helper\CustomView;

/**
* Корзина пользователя. 
* Хранится в базе данных, привязана к пользователю по идентификатору сессии.
* В сессии кэшируются общие данные о корзине. Данный класс также используется для работы с корзиной оформленного заказа.
*/
class Cart
{
    const
        CART_SESSION_VAR = 'cart',
        
        /**
        * Режим обычной корзины. Корзина привязана к сессии
        */
        MODE_SESSION = 'session',
        /**
        * Режим оформления заказа.
        */
        MODE_PREORDER = 'preorder',
        /**
        * Режим привязки к заказу
        */
        MODE_ORDER = 'order',
        /**
        * Режим пустой корзины
        */
        MODE_EMPTY = 'order',
        
        TYPE_PRODUCT = 'product',
        TYPE_COUPON  = 'coupon',
        TYPE_COMMISSION  = 'commission',
        TYPE_TAX = 'tax',
        TYPE_ORDER_DISCOUNT = 'order_discount',
        TYPE_SUBTOTAL = 'subtotal',
        TYPE_DELIVERY = 'delivery';
    
    protected static
        $instance;
    
    protected
        $mode,
        $order,
        $cartitem,
        $user_errors = array(),
        $select_expression = array(),
        $order_expression,
        $session_id,
        $cache_coupons,
        $cache_commission,
        $cache_products,
        /**
        * Элементы корзины
        */
        $items = array(),
        /**
        * Элементы оформленного заказа
        */
        $order_items = array();

    /**
    * Получать экземпляр класса можно только через ::currentBasket()
    */
    protected function __construct($mode = self::MODE_SESSION, Orm\Order $order = null)
    {
        $this->session_id = Auth::getGuestId();
        $this->mode = $mode;
        $this->order = $order;
        switch($this->mode) {
            case self::MODE_ORDER: {
                $this->cartitem = new Orm\OrderItem();
                $this->select_expression = array(
                    'order_id' => $this->order['id']
                );
                $this->order_expression = 'sortn';
                break;
            }
            case self::MODE_EMPTY: {
                $this->cartitem = new Orm\CartItem();
            }
            default: {
                $this->cartitem = new Orm\CartItem();
                $this->select_expression = array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'session_id' => $this->session_id
                );                
                $this->order_expression = 'dateof';
            }
        }
        
        $this->loadCart();
    }
            
    /**
    * Возвращает объект корзины текущего пользователя.
    * 
    * @return Cart
    */
    public static function currentCart()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    /**
    * Возвращает корзину пользователя во время оформления заказа.
    * т.е. элементы корзины еще привязаны к сессии, но к ним уже добавляются сведения из заказа (налоги, доставка)
    * 
    * @param Orm\Order $order
    * @return cart
    */
    public static function preOrderCart(Orm\Order $order = null)
    {
        $cart = new self(self::MODE_PREORDER, $order);
        //Вызовем событие
        \RS\Event\Manager::fire('cart.preorder', array(
            'cart' => $cart,
        ));
        return $cart;
    }

    /**
    * Возвращает пустую корзину
    * 
    * @param Orm\Order $order
    * @return cart
    */
    public static function emptyCart(Orm\Order $order)
    {
        return new self(self::MODE_EMPTY, $order);
    }

    /**
    * Возвращает корзину оформленного заказа.
    * 
    * @param Orm\Order $order
    * @return cart
    */
    public static function orderCart(Orm\Order $order)
    {
        return new self(self::MODE_ORDER, $order);  
    }
    
    /**
    * Уничтожает загруженный экземпляр корзины. Означает, что при следующем вызове ::currentCart()
    * будет произведена загрузка из базы заново
    * 
    * @return void
    */
    public static function destroy()
    {
        self::$instance = null;
    }
    
    /**
    * Возвращает все элементы корзины
    * 
    * @return array
    */
    public function getItems()
    {
        return $this->items;
    }
    
    
    /**
    * Очистка всех истекших по времени корзин
    * 
    */
    public static function deleteExpiredCartItems()
    {
        // Получаем список истекших сессий
        $sessions = \RS\Orm\Request::make()
            ->select("distinct session_id")
            ->from(new Orm\CartItem)
            ->where('dateof < DATE_SUB(NOW(), interval 60 day)')
            ->exec()->fetchSelected(null, 'session_id');
        
        // Удаляем все элементы корзин, относящиеся к этим истекшим сессиям
        if($sessions){
            \RS\Orm\Request::make()
                ->from(new Orm\CartItem)
                ->whereIn('session_id', $sessions)
                ->delete()->exec();
        }
    }
    
    /**
    * Устанавливает объект заказа в режиме PREORDER
    * 
    * @param Orm\Order $order
    */
    function setOrder(Orm\Order $order = null)
    {
        $this->order = $order;
        return $this;
    }
    
    /**
    * Загружает корзину из базы данных
    * 
    * @return void
    */
    function loadCart()
    {
        $q = \RS\Orm\Request::make()
            ->from($this->cartitem)
            ->where($this->select_expression)
            ->orderby($this->order_expression);

        $this->items = $q->objects(null, 'uniq');

        if ($this->mode == self::MODE_ORDER) {
            $this->order_items = $this->items;
        }
    }
    
    /**
    * Возвращает информацию по количеству товаров и стоимости товаров в корзине
    * 
    * @param mixed $format - форматировать общую сумму заказа
    * @param mixed $in_base_currency - возвращать в базовой валюте
    * @return array
    */
    public static function getCurrentInfo($format = true, $use_currency = true)
    {
        $options = (int)$format.(int)$use_currency.\Catalog\Model\CurrencyApi::getCurrentCurrency()->id;
        
        if (!isset($_SESSION[self::CART_SESSION_VAR][$options])) {
            self::currentCart()->getCartData($format, $use_currency);
        }
        
        return $_SESSION[self::CART_SESSION_VAR][$options];
    }
    
    /**
    * Обновляет сведения об элементах заказа.
    * 
    * @param mixed $items
    * @return void
    */
    function updateOrderItems(array $items)
    {
        foreach($this->items as $key => $item) {
            
            if (isset($items[$key])) {
                //Если используются многомерные комплектации 
                if (isset($items[$key]['multioffers']) && !empty($items[$key]['multioffers'])){
                   //Подгрузим сведения
                   $multioffers_values = $items[$key]['multioffers'];
                   $multioffer_api     = new \Catalog\Model\MultiOfferLevelApi();
                   $levels             = $multioffer_api->getLevelsInfoByProductId($items[$key]['entity_id']);
                   
                   $mo_arr = array();
                   foreach($multioffers_values as $prop_id=>$value){
                      $info = $levels[$prop_id]; 
                      $mo_arr[$prop_id] = array(
                         'title' => $info['title'] ? $info['title'] : $info['prop_title'],
                         'value' => $value,
                      );
                   }
                   $items[$key]['offer'] = \Catalog\Model\Api::getOfferByMultiofferValues($item['entity_id'], $mo_arr);                                    
                   $items[$key]['multioffers'] = serialize($mo_arr);
                }
                
                $this->items[$key]->getFromArray((array)$items[$key]);
            } else {
                unset($this->items[$key]); //Удаляем исключенные элементы
            }
        }

        
        foreach($items as $key => $item) {
            // Если такого товара еще нет
            if (!isset($this->items[$key])) {
                // Если это купон, то мы должны узнать его идентификатор по коду
                if(isset($item['type']) && $item['type'] == self::TYPE_COUPON){
                    if(empty($item['entity_id']) && isset($item['code'])){
                        $coupon =\Shop\Model\Orm\Discount::loadByWhere(array('code' => $item['code']));
                        if(!$coupon->id) continue;
                        $item['entity_id'] = $coupon->id;
                    }
                    
                    $this->items[$key] = new Orm\OrderItem();
                    $this->items[$key]->getFromArray((array)$item);
                }
                elseif(isset($item['type']) && $item['type'] == self::TYPE_ORDER_DISCOUNT){
                    if(empty($item['entity_id']) && isset($item['order_discount'])){
                        $item['entity_id'] = 0;
                    }
                    
                    $this->items[$key] = new Orm\OrderItem();
                    $this->items[$key]->getFromArray((array)$item);
                }
                else{

                    // Если передан идентификатор типа цены (в случае если это добавление товаров администратором)
                    $product  = new \Catalog\Model\Orm\Product($item['entity_id']);
                    if(isset($item['cost_id'])){
                        $item['single_cost'] = $product->getCost($item['cost_id'], null, false, false);
                    }
                    
                    if (!isset($item['title'])) {
                        $item['title'] = $product['title'];
                    }
                    
                    //Если используются многомерные комплектации 
                    if (isset($item['multioffers']) && !empty($item['multioffers'])){  
                       //Подгрузим сведения
                       $multioffers_values = $item['multioffers'];
                       $multioffer_api     = new \Catalog\Model\MultiOfferLevelApi();
                       $levels             = $multioffer_api->getLevelsInfoByProductId($item['entity_id']);
                       
                       $mo_arr = array();
                       foreach($multioffers_values as $prop_id=>$value){
                          $info = $levels[$prop_id]; 
                          $mo_arr[$prop_id] = array(
                             'title' => $info['title'] ? $info['title'] : $info['prop_title'],
                             'value' => $value,
                          );
                       }
                       $item['offer'] = \Catalog\Model\Api::getOfferByMultiofferValues($item['entity_id'], $mo_arr);
                       $item['multioffers'] = serialize($mo_arr);
                    }
                    

                    $this->items[$key] = new Orm\OrderItem();
                    
                    $this->items[$key]->getFromArray((array)$item);
                }
            }
        }
        
        \RS\Event\Manager::fire('cart.updateorderitems.after', array('cart' => $this));
        
        $this->cleanCache();
        $this->makeOrderCart();
    }
    
    /**
    * Очищает кэш товаров корзины
    * 
    */
    function cleanCache()
    {
        $this->cache_coupons = null;
        $this->cache_products = null;
    }
    
    /**
    * Очищает кэшированные сведения о сумме и количестве товаров в корзине
    * 
    * @return void
    */
    function cleanInfoCache()
    {
        if (isset($_SESSION)) {
            unset($_SESSION[self::CART_SESSION_VAR]);
        }
    }

    
    /**
    * Возвращает элементы корзины по типу
    * 
    * @param string|null $type Если Type - null, то возвращаются элементы всех типов
    * @return array
    */
    function getCartItemsByType($type = null)
    {
        if ($type === null) {
            return $this->items;
        }
        
        $result = array();
        foreach($this->items as $uniq => $cartitem) {
            if ($cartitem['type'] == $type) {
                $result[$uniq] = $cartitem;
            }
        }
        return $result;
    }
    
    /**
    * Возвращает список товаров в корзине
    * 
    * @param boolean $cache - использовать кэш?
    * @return \Shop\Model\Orm\CartItem[]
    */
    function getProductItems($cache = true)
    {
        if (!$cache || $this->cache_products === null) {
            $this->cache_products = array();
            $ids = array();
            $cartitem_product = $this->getCartItemsByType(self::TYPE_PRODUCT);
            $shop_config = \RS\Config\Loader::byModule($this);
            
            foreach ($cartitem_product as $cartitem) {
                $ids[] = $cartitem['entity_id'];
            }
            if (!empty($ids)) {
                $api = new \Catalog\Model\Api();
                $api->setFilter('id', $ids, 'in');
                $products = $api->getAssocList('id');
                $products = $api->addProductsCost($products);
                $products = $api->addProductsOffers($products);
                $products = $api->addProductsMultiOffers($products);
                $products = $api->addProductsDirs($products);

                foreach($cartitem_product as $key => $cartitem) {
                    
                    if ($this->mode != self::MODE_SESSION && !isset($products[ $cartitem['entity_id'] ])) {
                        //В режиме корзины для заказа, Создаем пустой объект товара, если таковой уже был удален
                        $product = new \Catalog\Model\Orm\Product();
                        $products[ $cartitem['entity_id'] ] = $product;
                    }
                    
                    if (isset($products[ $cartitem['entity_id'] ])) {
                        if ( $this->mode == self::MODE_SESSION
                              && $shop_config['remove_nopublic_from_cart']
                              && !$products[ $cartitem['entity_id'] ]['public'] ) {

                            $this->removeItem($key); //Если товар был скрыт, то удаляем его из корзины
                        } else {
                            $this->cache_products[$key] = array(
                                'product' => clone $products[$cartitem['entity_id']],
                                'cartitem' => $cartitem
                            );
                        }
                    } else {
                        $this->removeItem($key); //Если товар не был найден, то удаляем его из корзины
                    }
                    
                    //Заменяем стандартные свойства товаров, значениями, которые были во время оформления заказа
                    if ($this->mode == self::MODE_ORDER) {
                        $tax_ids_arr = !empty($cartitem['data']['tax_ids']) ? array('tax_ids' => implode(',', (array)$cartitem['data']['tax_ids'])) : array();
                        $this->cache_products[$key]['product']->getFromArray(array(
                            'weight' => $cartitem['single_weight'],
                        ) + $tax_ids_arr);
                        
                        if (empty($this->cache_products[$key]['product']['title'])) {
                            $this->cache_products[$key]['product']['title'] = $cartitem['title'];
                        }
                        
                        $this->cache_products[$key]['product']->setUserCost($cartitem['single_cost']);
                        $this->cache_products[$key]['product']['id'] = $cartitem['entity_id'];
                        //Если были переданы параметры высоты, ширины и глубины из отдельного модуля товару в корзину, то запишем и товару их
                        if (isset($cartitem['_delivery_width'])){
                            $this->cache_products[$key]['product']['_delivery_width'] = $cartitem['_delivery_width'];    
                        }
                        if (isset($cartitem['_delivery_height'])){
                            $this->cache_products[$key]['product']['_delivery_height'] = $cartitem['_delivery_height'];    
                        }
                        if (isset($cartitem['_delivery_depth'])){
                            $this->cache_products[$key]['product']['_delivery_depth'] = $cartitem['_delivery_depth'];    
                        }
                    }
                }
            }
        }
        return $this->cache_products;
    }
    
    /**
    * Добавляет товар в корзину
    * 
    * @param integer $product_id - id товара
    * @param integer $amount - количество
    * @param integer $offer - комплектация
    * @param array $multioffers - многомерная комплектация
    * @param array $concomitants - сопутствующие товары
    * @param array $concomitants_amount - количество сопутствующих товаров
    * @param string $additional_uniq - дополнительный строковый идентификатор товара, исключающий группировку товаров
    *
    * @throws \RS\Exception
    * 
    * @return string|false - возвращает уникальный идентификтор элемента корзины
    */
    function addProduct($product_id, $amount = 0, $offer = 0, $multioffers = array(), $concomitants = array(), $concomitants_amount = array(), $additional_uniq = null)
    {
        $api = new \Catalog\Model\Api();
        /**
         * @var \Catalog\Model\Orm\Product
         */
        $product = $api->getOneItem($product_id);
        if (!$product) {
            throw new \RS\Exception(t('Невозможно добавить товар в корзину. Товар с ID %0 не найден', array($product_id)));
        }

        //Приготовим мультикомплектации
        $multioffers = $this->prepareMultiOffersInfo($product_id, $multioffers);
        
        $eresult = \RS\Event\Manager::fire('cart.addproduct.before', array(
            'product_id'   => $product_id, 
            'amount'       => $amount, 
            'offer'        => $offer,
            'multioffers'  => $multioffers,
            'concomitants' => $concomitants,
            'cart'         => $this,
        ));
        if($eresult->getEvent()->isStopped()){
            return false;
        }
        
        list($product_id, $amount, $offer, $multioffers, $concomitants) = $eresult->extract();
        
        // Производим очистку устаревших корзин
        self::deleteExpiredCartItems();

        $amount = (float)$amount;
        $offer  = (int)$offer;
        if ($amount < $product->getAmountStep()) {
            $amount = $product->getAmountStep(); 
        }
        $product_uniq = $this->exists($product_id, $offer, serialize($multioffers), serialize($concomitants), self::TYPE_PRODUCT, $additional_uniq);

        if (!$product_uniq) {
            $product->fillCost();        //Загружаем цены
            $product->fillOffers();      //Загружаем комплектации    
            $product->fillMultiOffers(); //Загружаем многомерные комлпектации            
            $product->fillCategories();  //Загружаем сведения о категориях


            if ($product) {
                $item                  = clone $this->cartitem;
                $item['session_id']    = $this->session_id;
                $item['uniq']          = $this->generateId();
                $item['dateof']        = date('Y-m-d H:i:s');
                $item['user_id']       = \RS\Application\Auth::getCurrentUser()->id;
                $item['type']          = 'product';
                $item['entity_id']     = $product_id;
                $item['offer']         = $offer;
                $item['multioffers']   = serialize($multioffers);
                $item['amount']        = $amount;
                $item['title']         = $product['title'];
                $item['single_weight'] = $product->getWeight();
                
                $extra = array();
                if (!empty($concomitants)){
                    $extra['sub_products'] = $concomitants;
                    
                    //Проводим валидацию количества сопутствующих товаров
                    $shop_config = \RS\Config\Loader::byModule($this);
                    if ($shop_config['allow_concomitant_count_edit'] && $concomitants_amount) {
                        foreach($concomitants_amount as $sub_product_id => $amount) {
                            $extra['sub_products_amount'][$sub_product_id] = $amount ? abs((int)$amount) : 1;
                        }
                    }
                }
                if (!empty($additional_uniq)) {
                    $extra['additional_uniq'] = $additional_uniq;
                }
                $item['extra'] = serialize($extra);
                
                if($this->mode != self::MODE_PREORDER){
                    $item->insert();
                }
                
                $this->items[$item['uniq']] = $item;
                
                if ($this->cache_products !== null) {
                    $this->cache_products[$item['uniq']] = array(
                        'product' => $product,
                        'cartitem' => $item
                    );
                }
                $product_uniq = $item['uniq'];
            }
        } else {
            $this->items[$product_uniq]['amount'] += $amount;

            if($this->mode != self::MODE_PREORDER){
                $this->items[$product_uniq]->replace();
            }

        }
        $this->cleanInfoCache();
        
        $eresult = \RS\Event\Manager::fire('cart.addproduct.after', array(
            'product_id' => $product_id, 
            'amount' => $amount, 
            'offer' => $offer,
            'multioffers' => $multioffers,
            'concomitants' => $concomitants,
            'cart' => $this,
        ));
        
        return $product_uniq;
    }
    
    /**
    * Подготавливает дополнительную информацию уточняя детали многомерных комплектаций
    * Возвращет подготовленный двумерный массив с ключами id,title для каждой комплектации
    * 
    * @param integer $product_id - id товара
    * @param array $multioffers  - массив с мультикомплектациями полученными из запроса, ключи в нём id характеристики
    * @return array
    */
    function prepareMultiOffersInfo($product_id, $multioffers){
       //Подгрузим полную расширенную информацию о многомерных компл.
       if (!empty($multioffers)){
           $mo_arr      = $multioffers;
           $multioffers = array();
           
           //Получим уровни многомерной комлектации для обработки
           $levelsApi = new \Catalog\Model\MultiOfferLevelApi();
           $levels    = $levelsApi->getLevelsInfoByProductId($product_id); 
           
           if (!empty($levels)){
              foreach ($levels as $k=>$level){
                   if (isset($mo_arr[$level['prop_id']])){
                       $multioffers[$level['prop_id']]['id']    = $level['prop_id'];
                       $multioffers[$level['prop_id']]['value'] = $mo_arr[$level['prop_id']];
                       $multioffers[$level['prop_id']]['title'] = !empty($level['title']) ? $level['title'] : $level['prop_title'];
                   }
              } 
           }
           
       }
        
       return $multioffers;
    }
    
    /**
    * Проверяет существование элемента в корзине.
    * 
    * @param mixed $id          
    * @param integer $offer      - комплектация
    * @param array $multioffers  - многомерная комплектация
    * @param array $concomitants - сопутствующие товары
    * @param mixed $type         - тип позиции в корзине
    * @return Возвращает уникальный идентификатор позиции в корзине или false
    */
    function exists($id, $offer = null, $multioffers = null, $concomitants = null, $type = self::TYPE_PRODUCT, $additional_uniq = null)
    {
        foreach($this->items as $uniq => $cartitem) {
            
            $sub_products = array();
            $extra = unserialize($cartitem['extra']);
            if ( isset($extra['sub_products']) ) {
                $sub_products = $extra['sub_products'];
            }
            $item_additional_uniq = null;
            if ( isset($extra['additional_uniq']) ) {
                $item_additional_uniq = $extra['additional_uniq'];
            }    
            if ($cartitem['type'] == $type 
                && $cartitem['entity_id'] == $id 
                && ($offer === null || $cartitem['offer'] == $offer)
                && ($multioffers === null || $cartitem['multioffers'] == $multioffers)
                && ($concomitants === null || (serialize($sub_products) == $concomitants))
                && ($additional_uniq === null || $item_additional_uniq == $additional_uniq)) {
                return $uniq;
            }
        }
        return false;
    }

    /**
    * Генерирует уникальный в рамках пользователя id позиции в корзине
    * 
    * @return string
    */
    protected function generateId()
    {
        $symb = array_merge(range('a', 'z'), range('0', '9'));
        return \RS\Helper\Tools::generatePassword(10, $symb);
    }
    
    /**
    * Обновляет информацию в корзине
    * 
    * @param array $products - новые сведения по товарам
    * @param string $coupon - код купона на скидку
    * @param bool $safe - если true, то цена, скидка и наименование товара в $products будет игнорироваться
    * @return bool | false
    */
    function update($products = array(), $coupon = null, $safe = true)
    {
        $shop_config = \RS\Config\Loader::byModule('shop');
        $eresult = \RS\Event\Manager::fire('cart.update.before', array(
            'products' => $products, 
            'coupon' => $coupon,
            'cart' => $this
        ));
        
        if ($eresult->getEvent()->isStopped()) return false;
        list($products, $coupon) = $eresult->extract();
        $product_items = $this->getProductItems();
        $result = true;
        foreach($products as $uniq => $product) {
            //Обработка товара
            if (isset($this->items[$uniq]) && $this->items[$uniq]['type'] == self::TYPE_PRODUCT) {
                $extra = @unserialize($this->items[$uniq]['extra']) ?: array();
                
                //Количество
                if (isset($product['amount'])) {
                    $amount = abs((float)$product['amount']);
                    $step = $product_items[$uniq]['product']->getAmountStep();
                    $this->items[$uniq]['amount'] = ceil($amount / $step) * $step;
                }
                
                //Комплектация
                if (isset($product['offer'])) {
                    $this->items[$uniq]['offer'] = (int)$product['offer'];
                }
                
                //Многомерные комплектации
                if (!empty($product['multioffers'])){
                    //Записываем сведения о выбранных параметрах многомерной комплектации
                    $multioffers = $this->prepareMultiOffersInfo($this->items[$uniq]['entity_id'], $product['multioffers']);
                    $this->items[$uniq]['multioffers'] = serialize($multioffers);
                }
                
                if (!$safe) { //Установка приватных параметров товара
                
                    //Индивидуальная скидка для товара
                    if (isset($product['discount'])) {
                        if ($product['discount']) {
                            $extra = array_merge($extra, array('personal_discount' => $product['discount']));
                        } else {
                            unset($extra['personal_discount']);
                        }
                    }
                    
                    //Принудительно назначенная цена
                    if (isset($product['price'])) {
                        if ($product['price'] !== false) {
                            $extra = array_merge($extra, array('price' => $product['price']));
                        } else {
                            unset($extra['price']);
                        }
                    }

                    //Произвольные данные, которые записываются к товару
                    if (isset($product['custom_extra'])) {
                        if ($product['custom_extra'] !== false) {
                            $extra = array_merge($extra, array('custom_extra' => $product['custom_extra']));
                        } else {
                            unset($extra['custom_extra']);
                        }
                    }
                    
                    //Название товара
                    if (isset($product['title'])) {
                        $this->items[$uniq]['title'] = $product['title'];
                    }
                }
                
                $sub_products_amount = array();
                //Проводим валидацию количества сопутствующих товаров
                if ($shop_config['allow_concomitant_count_edit'] && !empty($product['concomitant_amount'])) {
                    $prdouct_concomitants = $product_items[$uniq]['product']->getConcomitant();
                    foreach($product['concomitant_amount'] as $sub_product_id => $amount) {
                        $sub_products_amount['sub_products_amount'][$sub_product_id] = $amount ? abs((float)$amount) : $prdouct_concomitants[$sub_product_id]->getAmountStep();
                    }
                }
                
                $sub_products = isset($product['concomitant']) ? $product['concomitant'] : array();
                $extra_sub_products = array('sub_products' => $sub_products) + $sub_products_amount;
                if (!$safe) {
                    $sub_products_discount = isset($product['concomitant_discount']) ? $product['concomitant_discount'] : array();
                    $sub_products_price = isset($product['concomitant_price']) ? $product['concomitant_price'] : array();
                    $extra_sub_products += array('sub_products_discount' => $sub_products_discount) + array('sub_products_price' => $sub_products_price);
                }
                $extra = array_merge($extra, $extra_sub_products);
                $this->items[$uniq]['extra'] = serialize($extra);
                
                if (isset($this->cache_products)) {
                    $this->cache_products[$uniq]['cartitem'] = $this->items[$uniq];
                }
                
                if ($this->mode == self::MODE_SESSION) {
                    \RS\Orm\Request::make()
                        ->update($this->cartitem)
                        ->set(array(
                            'title' => $this->items[$uniq]['title'],
                            'offer' => $this->items[$uniq]['offer'],
                            'multioffers' => $this->items[$uniq]['multioffers'],
                            'amount' => $this->items[$uniq]['amount'],
                            'extra' => $this->items[$uniq]['extra'],
                        ))
                        ->where(array(
                            'uniq' => $uniq
                        ))->exec();
                }
            }
        }
        if (!empty($coupon)) {
            $result = $this->addCoupon($coupon);
        }
        $this->cleanInfoCache();
        
        $eresult = \RS\Event\Manager::fire('cart.update.after', array(
            'products' => $products, 
            'coupon' => $coupon,
            'cart' => $this            
        ));        
        
        return $result;
    }
    
    /**
    * Удаляет позицию из корзины.
    * 
    * @param string $uniq - Уникальный идентификатор элемента корзины
    * @return bool
    */
    function removeItem($uniq)
    {
        $eresult = \RS\Event\Manager::fire('cart.removeitem.before', array(
            'cart' => $this,
            'uniq' => $uniq
        ));    
        if ($eresult->getEvent()->isStopped()) return false;
                
        if (isset($this->items[$uniq])) {
            if ($this->mode == self::MODE_SESSION) {
                \RS\Orm\Request::make()
                    ->delete()
                    ->from($this->cartitem)
                    ->where($this->select_expression)
                    ->where(array(
                        'uniq' => $uniq
                    ))->exec();
            }
            
            $deleted_item = $this->items[$uniq];
            unset($this->items[$uniq]);
            unset($this->order_items[$uniq]);
            unset($this->cache_products[$uniq]);
            unset($this->cache_coupons[$uniq]);

            \RS\Event\Manager::fire('cart.removeitem.after', array(
                'cart' => $this,
                'uniq' => $uniq,
                'item' => $deleted_item
            ));            
        }
        return true;
    }
    
    /**
    * Очищает корзину
    */
    function clean()
    {
        $this->items = array();
        $this->order_items = array();
        $this->cache_coupons = null;
        $this->cache_products = null;
        
        $result = \RS\Orm\Request::make()
            ->delete()
            ->from($this->cartitem)
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'session_id' => $this->session_id
            ))->exec();
        
        $this->cleanInfoCache();
        return true;
    }
    
    /**
    * Объединяет одинаковые товары с одинаковой комплектацией, увеличивая количество.
    * 
    * @return void
    */
    function mergeEqual()
    {
        $result = array();
        $items = $this->getCartItemsByType(self::TYPE_PRODUCT);
        foreach($items as $uniq => $cartitem) {
            $extra = unserialize($cartitem['extra']);
            $additional_uniq = (isset($extra['additional_uniq'])) ? $extra['additional_uniq'] : '' ;
            $offerkey = $cartitem['entity_id'].'-'.((int)$cartitem['offer']).$cartitem['multioffers'].$cartitem['single_cost'].$additional_uniq;
            if (isset($result[$offerkey])) {
                //Обновляем количество другого товара, удаляем текущий товар
                $this->update(array($result[$offerkey] => array(
                    'amount' => $this->items[$result[$offerkey]]['amount'] + $cartitem['amount']
                )));
                $this->removeItem($uniq);
            } else {
                $result[$offerkey] = $uniq;
            }
        }
    } 
    
    /**
    * Возвращает данные по товарам, налогам и скидкам в корзине
    * 
    * @param bool $format - форматировать цены
    * @param bool $use_currency - Если true, то использовать текущую валюту, иначе используется базовая валюта
    * @return array
    */
    function getPriceItemsData($use_currency = true){
        $result = array(
            'checkcount' => count($this->items),
            'currency' => $use_currency ? \Catalog\Model\CurrencyApi::getCurrecyLiter() : \Catalog\Model\CurrencyApi::getBaseCurrency()->stitle,
            'errors' => array(),
            'has_error' => false
        );  
        
        $result = $this->addProductData($result, $use_currency);
        $result = $this->addDiscountData($result, $use_currency);
        $result = $this->addOrderDiscountData($result, $use_currency);
        $result = $this->addTaxData($result);

        return $result;
    }
    /**
    * Возвращает стоимость товаров в заказе
    * 
    * @param bool $use_currency - Если true, то использовать текущую валюту, иначе используется базовая валюта
    * @return float
    */
    function getTotalWithoutDelivery($use_currency = true)
    {
        $result = $this->getPriceItemsData($use_currency);
        return $result['total'];
    }
    
    /**
    * Возвращает калькулируемые данные, необходимые для отображения корзины пользователя.
    * Результат не содержит объекты товаров. Объекты товаров можно получить вызвав метод getProductItems
    * 
    * @param bool $format - форматировать цены
    * @param bool $use_currency - Если true, то использовать текущую валюту, иначе используется базовая валюта
    * @return array
    */
    function getCartData($format = true, $use_currency = true)
    {
        $result   = $this->getPriceItemsData($use_currency);
        $currency = $result['currency'];
        
        //Сумма без доставки
        $result['total_without_delivery'] = CustomView::cost($result['total'], $currency);
        $result['total_without_delivery_unformatted'] = $result['total'];
        
        //Добавим данные по доставке
        $result = $this->addDeliveryData($result, $use_currency);
        
        //Сумма без комисии способа оплаты
        $result['total_without_payment_commission'] = CustomView::cost($result['total'], $currency);
        $result['total_without_payment_commission_unformatted'] = $result['total'];
        
        //Добавим данные по комиссии за оплату
        $result = $this->addPaymentCommissionData($result);
        
        
        $min_limit = \RS\Config\Loader::byModule($this)->basketminlimit;
        if ($use_currency) $min_limit = \Catalog\Model\CurrencyApi::applyCurrency( $min_limit );
        
        if ($result['total'] < $min_limit) {
            $result['errors'][] = t('Минимальная стоимость заказа должна составлять %0', array(CustomView::cost($min_limit, $result['currency'])));
            $result['has_error'] = true;
        }
        
        $result = $this->appendUserErrors($result);
        $result['total_unformatted'] = $result['total'];
        
        if ($format) {

            foreach($result['items'] as &$product) {
                $product['single_cost'] = CustomView::cost($product['single_cost'], $currency);
                $product['cost']        = CustomView::cost($product['cost'], $currency);
                $product['base_cost']   = CustomView::cost($product['base_cost'], $currency);
                $product['discount']    = CustomView::cost($product['discount'], $currency);


                if (isset($product['sub_products'])){
                    foreach($product['sub_products'] as &$sub_product) {
                        $sub_product['cost']        = CustomView::cost($sub_product['cost'], $currency);
                        $sub_product['single_cost'] = CustomView::cost($sub_product['single_cost'], $currency);
                        if (isset($sub_product['discount'])){
                            $sub_product['discount']    = CustomView::cost($sub_product['discount'], $currency);    
                        }
                    }
                }

            }
            
            //Если если налоги и их надо преобразовать в форматированный вид
            if (isset($result['taxes'])) {
                foreach($result['taxes'] as &$tax) {
                    $tax['cost'] = CustomView::cost($tax['cost'], $currency);
                }
            }
            
            //Если если доставка и её надо преобразовать в форматированный вид
            if (isset($result['delivery'])) {
                $result['delivery']['cost'] = CustomView::cost($result['delivery']['cost'], $currency);
                /**
                 * change
                 */
                $result['delivery']['cost_unformatted'] = $result['delivery']['cost'];
            }
            
            //Если если комиссия и её надо преобразовать в форматированный вид
            if (isset($result['payment_commission'])) {
                $result['payment_commission']['cost'] = CustomView::cost($result['payment_commission']['cost'], $currency);
            }
            
            //Форматируем итоговые суммы
            $result['total']          = CustomView::cost($result['total'], $currency);
            $result['total_base']     = CustomView::cost($result['total_base'], $currency);
            $result['total_discount'] = CustomView::cost($result['total_discount'], $currency);
        }
        
        $options = (int)$format.(int)$use_currency.\Catalog\Model\CurrencyApi::getCurrentCurrency()->id;
        
        
        
        $eresult = \RS\Event\Manager::fire('cart.getcartdata', array(
            'cart' => $this,
            'cart_result' => $result,
            'format' => $format,
            'use_currency' => $use_currency
        ));
        
        $new_result = $eresult->getResult();
        $result = $new_result['cart_result'];
        
        //Формируем кэш данные в сессии
        $products_id = array();
        foreach($this->getCartItemsByType(self::TYPE_PRODUCT) as $cartdata) {
            $id = $cartdata['cartitem']['entity_id'];
            $products_id[$id] = $id;
        }
        if ($this->mode == self::MODE_SESSION) {
            $_SESSION[self::CART_SESSION_VAR][$options] = array(
                'total_unformatted' => $result['total_unformatted'],
                'currency' => $currency,
                'total' => $result['total'],
                'items_count' => $result['items_count'],
                'products_id' => $products_id,
                'has_error' => $result['has_error']
            );
        }
        
        return $result;
    }    
    
    /**
    * Добавляет сведения о налогах к корзине
    * 
    * @param mixed $result
    * @param bool $use_currency - Если true, то использовать текущую валюту, иначе используется базовая валюта
    */
    protected function addTaxData($result)
    {
        $result['taxes'] = array();
        
        if ($this->mode != self::MODE_SESSION && $this->order ) {
            $subtotal = 0;
            //Расчитываем налоги для каждого товара
            foreach($this->getProductItems() as $key => $product) {
                $product_subtotal = $result['items'][$key]['cost']; //стоимость товара без налогов
                $address = $this->order->getAddress();
                 /**
                 * @var \Shop\Model\Orm\Delivery $delivery
                 */
                $delivery = $this->order->getDelivery();

                if (!isset($address->city)){               //если доставка самовывоз, и свой адрес пользователь не вводил
                    if ($delivery['class'] == 'myself') {  // для расчета ставки налога использовать регион(Город) из доставки
                        $city_id = $delivery->getTypeObject()->getOption('myself_addr');
                        if ($delivery_address = \Shop\Model\AddressApi::getAddressByCityid($city_id)) {
                            $address = $delivery_address;
                        }
                    }
                }
                
                $taxes = TaxApi::getProductTaxes($product['product'], $this->order->getUser(), $address);
                /**
                 * @var \Shop\Model\Orm\Tax $tax
                 */
                foreach($taxes as $tax) {
                    $tax_rate = $tax->getRate($address);
                    $tax_part = ($tax['included']) ? ($tax_rate / (100 + $tax_rate)) : ($tax_rate / 100) ;
                    $tax_value = round($result['items'][$key]['cost'] * $tax_part, 2);
                    if (!isset($result['taxes'][$tax['id']])) {
                        $result['taxes'][$tax['id']] = array(
                            'tax' => $tax,
                            'title' => $tax['title'],
                            'cost' => 0
                        );
                    }
                    if ($tax['included']) {
                        $product_subtotal -= $tax_value;
                    }
                    $result['taxes'][$tax['id']]['cost'] += $tax_value;
                    
                    if (!$tax['included']) {
                        $result['total'] += $tax_value;
                    }
                }
                $subtotal += $product_subtotal;
            }

            if (!empty($result['taxes'])) {
                $result['subtotal'] = $subtotal;
            }
        }
        return $result;
    }
    
    /**
    * Возвращает наценку на цену $price
    *
    * @param float $price - сумма
    * @param integer $commission_percent - процент комиссии
    */
    protected function getPaymentCommissionValue($price, $commission_percent)
    {
        return round($price * ($commission_percent/100), 2);
    }
    
    
    /**
    * Добавляет сведения о комиссии в корзине 
    * 
    * @param mixed $result
    * @param bool $use_currency - Если true, то использовать текущую валюту, иначе используется базовая валюта
    */
    protected function addPaymentCommissionData($result, $use_currency = true)
    {
        if ($this->mode != self::MODE_SESSION && $this->order['payment']>0 ) {
            $payment = $this->order->getPayment();
            if ($payment['commission']) { //Если комиссия за оплату назначена
                if ($payment['commission_as_product_discount']) {
                    foreach($result['items'] as $key=>&$item) {
                        $payment_commisson = $item['cost'] * $payment['commission'] / 100;
                        $item['cost']     += $payment_commisson;
                        $item['discount'] -= $payment_commisson;
                        $result['total']  += $payment_commisson;
                    }
                } else {
                    $commission_source = $result['total_base'];
                    // Добавим к сумме на которрую накладывается комиссия стоимость доставки, если включена опция
                    if ($payment['commission_include_delivery'] && isset($result['delivery']['cost'])) {
                        $commission_source += $result['delivery']['cost'];
                    }
                    $result['payment_commission']['object'] = $payment;
                    $result['payment_commission']['cost']   = $this->getPaymentCommissionValue($commission_source, $payment['commission']);
                    $result['total']                        += $result['payment_commission']['cost'];
                }
            }
        }
        
        return $result;
    }
    
    /**
    * Добавляет сведения о доставке в корзину
    * 
    */
    function addDeliveryData($result, $use_currency)
    {
        if ($this->mode != self::MODE_SESSION && $this->order['delivery']>0) {
            
            $delivery = $this->order->getDelivery();

            if ($this->order['user_delivery_cost'] !== null) {
                $cost = $this->order['user_delivery_cost'];
            } else {
                $cost = $delivery->getDeliveryCost($this->order, $this->order->getAddress(), $use_currency);
            }
        
            $result['delivery']['object'] = $delivery;
            $result['delivery']['cost'] = $cost;
            $result['total'] += $cost;
        }
        
        return $result;
    }
    
    /**
    * Переносит элементы из корзины в таблицу элементов заказа
    * Вызывается при подтверждении заказа
    * 
    * @return bool
    */
    function makeOrderCart()
    {
        $catalog_config = \RS\Config\Loader::byModule('catalog');
        $session_items  = $this->getCartItemsByType();
        $products       = $this->getProductItems();        
        $cartdata       = $this->getCartData(false, false);
        
        $this->order_items = array();

        $i = 0;
        foreach($session_items as $uniq => $item) {
            $new_item = new Orm\OrderItem();
            
            $new_item->getFromArray($item->getValues());
            $new_item['order_id'] = $this->order['id'];
            
            if ($item['type'] == self::TYPE_PRODUCT) {
                //Определимся с единицами измерения
                $unit = $products[$uniq]['product']->getUnit()->stitle;
                if ($catalog_config['use_offer_unit']){
                    $offer = @$products[$uniq]['product']['offers']['items'][(int)$item['offer']];
                    if ($offer) $unit = $offer->getUnit()->stitle;
                }

                // fix array('custom_extra', 'personal_discount', 'additional_uniq')
                if (isset($item['extra'])) {
                    $extra = @(array)unserialize($item['extra']);
                    $source_extra = array_intersect_key($extra, array_flip(array('custom_extra', 'personal_discount', 'additional_uniq')));
                } else {
                    $source_extra = array();
                }
                //var_dump($source_extra);exit;
                $new_item['title']         = $products[$uniq]['cartitem']['title'];                                          
                $new_item['model']         = $products[$uniq]['product']->isOffersUse() ? $products[$uniq]['product']->getOfferTitle($item['offer']) : '';
                $new_item['barcode']       = $products[$uniq]['product']->getBarCode($item['offer']);
                $new_item['single_weight'] = $cartdata['items'][$uniq]['single_weight'];
                $new_item['single_cost']   = $cartdata['items'][$uniq]['single_cost'];
                $new_item['price']         = $cartdata['items'][$uniq]['base_cost'];
                $new_item['discount']      = $cartdata['items'][$uniq]['discount'];
                $new_item['profit']        = $new_item->getProfit();
                $new_item['extra']         = serialize(array_merge(array(
                    'tax_ids' => TaxApi::getProductTaxIds($products[$uniq]['product']),
                    'unit' => $unit
                ), $source_extra ));
            }
            $new_item['sortn'] = $i++;
            $this->order_items[$uniq] = $new_item;
        }
            
        if (isset($cartdata['subtotal'])) {
            $new_item             = new Orm\OrderItem();
            $new_item['order_id'] = $this->order['id'];
            $new_item['uniq']     = $this->generateId();
            $new_item['type']     = self::TYPE_SUBTOTAL;
            $new_item['title']    = t('Товаров на сумму');
            $new_item['price']    = $cartdata['subtotal'];
            $new_item['discount'] = $cartdata['subtotal'];
            $new_item['sortn']    = $i++;
            
            $this->order_items[$new_item['uniq']] = $new_item;
        }                
        
        foreach($cartdata['taxes'] as $taxdata) {
            $new_item              = new Orm\OrderItem();
            $new_item['order_id']  = $this->order['id'];
            $new_item['uniq']      = $this->generateId();
            $new_item['type']      = self::TYPE_TAX;
            $new_item['title']     = $taxdata['tax']->getTitle();
            $new_item['entity_id'] = $taxdata['tax']['id'];
            $new_item['price']     = $taxdata['cost'];
            
            if ($taxdata['tax']['included']) {
                $new_item['discount'] = $taxdata['cost'];
            }
            $new_item['sortn'] = $i++;
            $this->order_items[$new_item['uniq']] = $new_item;
        }
        if (isset($cartdata['delivery'])) {
            if ($cartdata['delivery']['cost']===false){
                return t('Не удалось получить цену доставки. Попробуйте оформить заказ позже.');
            }
            $new_item              = new Orm\OrderItem();
            $new_item['order_id']  = $this->order['id'];
            $new_item['uniq']      = $this->generateId();
            $new_item['type']      = self::TYPE_DELIVERY;
            $new_item['title']     = t('Доставка: %0', array($cartdata['delivery']['object']['title']));
            $new_item['entity_id'] = $cartdata['delivery']['object']['id'];
            
            $new_item['price']     = $cartdata['delivery']['cost'];
            $new_item['sortn']     = $i++;
            
            $this->order_items[$new_item['uniq']] = $new_item;
        }
        //Коммиссия на оплату
        if (isset($cartdata['payment_commission'])) {
            $payment               = $cartdata['payment_commission']['object'];
            $new_item              = new Orm\OrderItem();
            $new_item['order_id']  = $this->order['id'];
            $new_item['uniq']      = $this->generateId();
            $new_item['type']      = self::TYPE_COMMISSION;
            $new_item['title']     = t('Комиссия при оплате через %0 %1%', array(
                $payment['title'],
                $payment['commission']
            ));
            $new_item['entity_id'] = $cartdata['payment_commission']['object']['id'];
            
            $new_item['price']     = $cartdata['payment_commission']['cost'];
            $new_item['sortn']     = $i++;
            
            $this->order_items[$new_item['uniq']] = $new_item;
        }
        
        //Скидка на заказ
        if (isset($cartdata['order_discount'])) {
            $new_item              = new Orm\OrderItem();
            $new_item['order_id']  = $this->order['id'];
            $new_item['uniq']      = $this->generateId();
            $new_item['type']      = self::TYPE_ORDER_DISCOUNT;
            $new_item['title']     = t('Скидка на заказ %0', array(
                isset($cartdata['order_discount_extra']) ? $cartdata['order_discount_extra'] : ""
            ));
            $new_item['entity_id'] = 0;
            
            $new_item['price']     = $cartdata['order_discount_unformatted'];
            $new_item['discount']  = $cartdata['order_discount_unformatted'];
            $new_item['sortn']     = $i++;
            
            $this->order_items[$new_item['uniq']] = $new_item;
        }
        
        $this->order['totalcost']          = $cartdata['total'];
        
        $this->order['user_delivery_cost'] = isset($cartdata['delivery']) ? $cartdata['delivery']['cost'] : 0;
        
        return true;
    }
    
    /**
    * Сохраняет сформированный заказ в базу данных
    * 
    * @return bool
    */
    function saveOrderData()
    {
        \RS\Orm\Request::make()->delete()
            ->from(new Orm\OrderItem())
            ->where(array('order_id' => $this->order['id']))
            ->exec();
        
        foreach($this->order_items as $uniq => $order_item) {
            $order_item['order_id'] = $this->order['id'];
            $order_item->insert();
        }
        
        return true;
    }
    
    /**
    * Возвращает доходность товаров в заказе
    * 
    * @return double
    */
    function getOrderProfit()
    {
        $profit = 0;
        foreach($this->order_items as $uniq => $order_item) {
            if ($order_item['type'] == Orm\OrderItem::TYPE_PRODUCT) {
                $profit += $order_item['profit'];
            }
        }
        return $profit;
    }
    
    
    /**
    * Возвращает сведения об элементах заказа. 
    * Сведения не зависят от существования в магазине реальных элементов заказа.
    * 
    * @param bool $format - если true, то форматировать вывод
    * @param bool $use_currency - если true, то конвертировать в валюту заказа
    * @param bool $write_currency_liter - если true, то отображать символ валюты после суммы
    * @return array
    */
    function getOrderData($format = true, $use_currency = true, $write_currency_liter = true)
    {
        $result = array(
            'items' => array(),
            'other' => array(),
            'total_cost' => 0, //Общая стоимость заказа все равно пересчитывается, чтобы избежать копеечных отклонений при конвертировании валют.
            'total_weight' => 0 //Общий вес
        );
        
        if ($use_currency) {
            $result['currency'] = $this->order['currency_stitle'];
        } else {
            $result['currency'] = \Catalog\Model\CurrencyApi::getBaseCurrency()->stitle;
        }
        if (!$write_currency_liter) {
            $result['currency'] = null;
        }
        
        foreach($this->order_items as $key => $cartitem) {
            $cost = $cartitem['price'];
            $discount = $cartitem['discount'];
            $single_cost = $cartitem['single_cost'];
            
            if ($use_currency) {
                $cost = $this->order->applyMyCurrency($cost);
                $discount = $this->order->applyMyCurrency($discount);
                $single_cost = $this->order->applyMyCurrency($single_cost);
            }
            
            $result['total_cost'] += ($cost - $discount);            
            $result['total_weight'] += $cartitem['single_weight'] * $cartitem['amount'];
            $category = ($cartitem['type'] == self::TYPE_PRODUCT) ? 'items' : 'other';
            
            $single_discount = $discount/($cartitem['amount']!=0 ? $cartitem['amount'] : 1);
            $result[$category][$key] = array(
                'cost' => $cost,
                'single_cost' => $single_cost,
                'single_cost_with_discount' => round($single_cost - $single_discount, 2),
                'single_cost_noformat' => $single_cost,
                'discount' => $discount,
                'cartitem' => $cartitem
            );
            
            if ($cost == 0 && $discount == 0) {
                $item_total = '';
            } else {
                $item_total = ($cost == $discount) ? $cost : abs($cost-$discount);
            }
            
            $result[$category][$key]['total'] = $item_total;
            
            if ($format) {
                $result[$category][$key]['total'] = CustomView::cost($result[$category][$key]['total'], $result['currency']);
                $result[$category][$key]['discount'] = CustomView::cost($result[$category][$key]['discount']);
                $result[$category][$key]['single_cost'] = CustomView::cost($result[$category][$key]['single_cost'], $result['currency']);
                $result[$category][$key]['single_cost_with_discount'] = CustomView::cost($result[$category][$key]['single_cost_with_discount'], $result['currency']);
            }
        }
        $result['total_cost_noformat'] = $result['total_cost'];
        if ($format) {
            $result['total_cost'] = CustomView::cost($result['total_cost'], $result['currency']);
        }        
        
        $eresult = \RS\Event\Manager::fire('cart.getorderdata', array(
            'cart' => $this,
            'cart_result' => $result,
            'format' => $format,
            'use_currency' => $use_currency,
            'write_currency_liter' => $write_currency_liter
        ));
        $new_result = $eresult->getResult();
        $result = $new_result['cart_result'];
        
        return $result;
    }    
    
    /**
    * Возвращает true, если уже оформленный заказ можно редактировать и все связи с налогами и доставками в порядке
    * 
    * @return bool
    */
    function canEdit()
    {
        
    }
    
    /**
    * Возвращает общий вес элементов корзины
    * 
    * @return integer
    */
    function getTotalWeight()
    {
        $in_basket = $this->getProductItems();
        $total_weight = 0;
        foreach($in_basket as $n => $item) {
            $product = $item['product'];
            $amount = $item['cartitem']['amount'];
            $total_weight += $product->getWeight() * ($amount>0 ? $amount : 1); //Рассчитываем общий вес
        }
        return $total_weight;
    }
    
    /**
    * Возвращает стоимость заказа
    * 
    * @param array $excludeTypesOfItem - массив элементов из констант self::TYPE_...., 
    * с помощью которого можно исключить из расчета различные компоненты заказа
    * @return float
    */
    function getCustomOrderPrice(array $excludeTypesOfItem = array(), $use_currency = true)
    {
        //Если заказ еще не оформлен
        $result = array(
            'checkcount' => count($this->items),
            'currency' => $use_currency ? \Catalog\Model\CurrencyApi::getCurrecyLiter() : \Catalog\Model\CurrencyApi::getBaseCurrency()->stitle,
            'errors' => array(),
            'has_error' => false,
            'total' => 0
        );        
        if ($this->mode == self::MODE_ORDER) {
            //Если заказ уже оформлен
            foreach($this->order_items as $key => $cartitem) {
                if (!in_array($cartitem['type'], $excludeTypesOfItem)) {
                    $cost = $cartitem['price'];
                    $discount = $cartitem['discount'];
                    if ($use_currency) {
                        $cost = $this->order->applyMyCurrency($cost);
                        $discount = $this->order->applyMyCurrency($discount);
                    }
                    $result['total'] += ($cost - $discount);                    
                }
            }
        } else {
            if (!in_array(self::TYPE_PRODUCT, $excludeTypesOfItem)) {
                $result = $this->addProductData($result, $use_currency);
            }
            $result = $this->addDiscountData($result, $use_currency);
            
            if (!in_array(self::TYPE_TAX, $excludeTypesOfItem)) {
                $result = $this->addTaxData($result);
            }
            if (!in_array(self::TYPE_DELIVERY, $excludeTypesOfItem)) {
                $result = $this->addDeliveryData($result, $use_currency);            
            }
        }
        return $result['total'];        
    }
    
    /**
    * Разделение товаров
    * 
    */
    function splitSubProducts()
    {
        
        $in_basket = $this->getProductItems();
        
        foreach($in_basket as $n => $item) {
            $product = $item['product'];
            $amount  = $item['cartitem']['amount'];
            $extra   = @unserialize($item['cartitem']['extra']) ?: array();
            
            $checked_concomitants         = (array)@$extra['sub_products'];
            $checked_concomitant_amount   = (array)@$extra['sub_products_amount'];
            $checked_concomitant_discount = (array)@$extra['sub_products_discount'];
            $checked_concomitant_price    = (array)@$extra['sub_products_price'];
            
            unset($extra['sub_products']);
            unset($extra['sub_products_amount']);
            unset($extra['sub_products_discount']);
            unset($extra['sub_products_price']);
            $item['cartitem']['extra'] = serialize($extra);
            //var_dump($item['cartitem']['extra']);exit;
            //Переберём сопутствующие
            foreach($product->getConcomitant() as $sub_product) {
                $sub_id             = $sub_product['id'];
                $only_one           = (bool) @$product['concomitant_arr']['onlyone'][$sub_id];
                $concomitant_amount = $amount;                
                $sub_product_amount = $only_one ? 1 : $concomitant_amount;
                
                if (!empty($checked_concomitant_amount)){ //Если пользователь указал количество в корзине
                   $sub_product_amount = isset($checked_concomitant_amount[$sub_id]) ? $checked_concomitant_amount[$sub_id] : $amount; 
                   $only_one = false;
                }                
                
                $checked            = array_search($sub_id, $checked_concomitants) !== false;
                $sub_product_cost   = $only_one ? $sub_product->getCost(null, null, false) : $sub_product_amount * $sub_product->getCost(null, null, false);
                
                if($checked){
                    // Добавляем сопутствующий товар в корзину
                    $uniq = $this->addProduct($sub_product['id'], $sub_product_amount);
                    // если у сопутствующего товара была скидка - добавляем её
                    if (!empty($checked_concomitant_discount[$sub_product['id']])) {
                        $extra = array('personal_discount' => $checked_concomitant_discount[$sub_product['id']]);
                        $this->update(array($uniq => $extra), null, false);
                    }
                    // если у сопутствующего товара была установлена цена - добавляем её
                    if (!empty($checked_concomitant_price[$sub_product['id']])) {
                        $extra = array('price' => $checked_concomitant_price[$sub_product['id']]);
                        $this->update(array($uniq => $extra), null, false);
                    }
                }
            }
        }
    }
    
    /**
    * Получаем массив из количества сопуствующего товара ключ - id товара, значение - количество доступное на складе
    * 
    * @param array $concomitants        - массив сопутствующие товары      
    * @param array $concomitant_amounts - массив уже подготовленных остатков     
    * @return array
    */
    private function getConcomitantsAmounts($concomitants, $concomitant_amounts = array())
    {
        foreach ($concomitants as $sub_product){
           if (!isset($concomitant_amounts[$sub_product['id']])){
               $concomitant_amounts[$sub_product['id']] = $sub_product->getNum(0);
           }
        }
        return $concomitant_amounts;
    }
    
    
    /**
    * Пересчитывает стоимости товаров
    * 
    * @param array $result - массив со сведениями о корзине
    * @param bool $use_currency - использовать ли текущую валюту?
    * @return array
    */
    protected function addProductData($result, $use_currency)
    {
        $result = array(
            'total' => 0,
            'total_base' => 0,
            'total_discount' => 0,
            'items' => array(),
            'items_count' => 0,
            'total_weight' => 0,


        ) + $result;
        
        $module_config = \RS\Config\Loader::byModule($this);

        $cost_of_concomitants = 0;
        $concomitant_amounts  = array();
        $in_basket = $this->getProductItems();
        foreach($in_basket as $n => $item) {
            $extra = @unserialize($item['cartitem']['extra']) ?: array();
            /**
            * @var \Catalog\Model\Orm\Product
            */
            $product = $item['product'];
            $amount  = $item['cartitem']['amount'];  
            
            //Если принудительно задана стоимость, то устанавливаем её
            if (isset($extra['price'])) $product->setUserCost($extra['price']);
            
            $cost = $product->getCost(null, $item['cartitem']['offer'], false, true);
            if ($use_currency) $cost = \Catalog\Model\CurrencyApi::applyCurrency($cost);
            $complete_cost = ($cost * ($amount>0 ? $amount : 1)); // Цена товара с учетом количества и комплектации
            
            $result['total'] +=  $complete_cost; //Общая сумма товаров в корзине
            $result['items_count'] += $amount;
            $result['items'][$n] = array(
                'id' => $n,
                'cost' => $complete_cost, //Конечная цена для отображения, данная цена будет изменяться скидками далее
                'base_cost' => $complete_cost, //Данная цена будет использоваться в качестве исходной для скидки. изменяться не будет
                'amount' => $amount, // Количестово товара
                'single_cost' => $cost, //Цена за единицу товара
                'single_weight' => $product->getWeight(), //Вес единицы товара
                'discount' => 0,
                'sub_products' => array(),
                /**
                 * change
                 */
                'ds_single_cost' => $item['cartitem']['ds_single_cost'],
                'ds_price' => $item['cartitem']['ds_price'],
            );
            
            //Если задано, что заказать можно только определённое количество товара и количество не соотвествует
            if ($product['min_order'] > 0 && $amount < (int)$product['min_order']){
               $result['items'][$n]['amount_error'] = t('Минимальное количество для заказа товара %0', array($product['min_order'].' '.$product->getUnit()->stitle));
               $result['has_error'] = true;
            }
            //Если задано, что заказать можно только определённое количество товара и количество не соотвествует
            if ($product['max_order'] > 0 && $amount > (int)$product['max_order']){ 
               $result['items'][$n]['amount_error'] = t('Максимальное количество для заказа товара %0', array($product['max_order'].' '.$product->getUnit()->stitle));
               $result['has_error'] = true;
            }

            //Расчет "сопутствующих товаров"
            $checked_concomitants       = (array)@$extra['sub_products'];
            $checked_concomitant_amount = (array)@$extra['sub_products_amount'];
            $checked_concomitant_price  = (array)@$extra['sub_products_price'];
            
            $concomitants = $product->getConcomitant();
            
            //Подгрузим общее количество для каждого сопутствующего товара, если отмечен флаг
            if ($module_config['check_quantity']) {
               $concomitant_amounts = $this->getConcomitantsAmounts($concomitants,$concomitant_amounts);
            }
            
            foreach($concomitants as $sub_product) {
                $sub_id   = $sub_product['id'];
                //Флаг 'Всегда в количестве одна штука' работает только при отключенной опции 'редактирование количества сопутствующих товаров в корзине'
                $only_one = (bool)@$product['concomitant_arr']['onlyone'][$sub_id];
                $checked  = array_search($sub_id, $checked_concomitants) !== false;
                $concomitant_amount = $only_one ? 1 : $amount;
                
                if (!empty($checked_concomitant_amount)){ //Если пользователь указал количество в корзине
                   $concomitant_amount = isset($checked_concomitant_amount[$sub_id]) ? $checked_concomitant_amount[$sub_id] : $amount; 
                   $only_one = false;
                }
                
                $sub_product_single_cost = (isset($checked_concomitant_price[$sub_id])) ? $checked_concomitant_price[$sub_id] : $sub_product->getCost(null, null, false);
                $sub_product_cost = $only_one ? $sub_product_single_cost : $concomitant_amount * $sub_product_single_cost;
                
                $result['items'][$n]['sub_products'][$sub_id] = array
                (
                    'amount' => $concomitant_amount,
                    'cost' => $sub_product_cost,
                    'single_cost' => $sub_product_single_cost,
                    'checked' => $checked
                );
                
                //Проверяем количество сопутствующего у отмеченного товара, если стоит флаг проверки количества
                if ($module_config['check_quantity'] && $checked) {
                    $check_amount = $result['items'][$n]['sub_products'][$sub_id]['amount'];
                    $concomitant_amounts[$sub_id] -= $check_amount; //Убавим количество 

                    if ($concomitant_amounts[$sub_id]<0){
                       $result['items'][$n]['sub_products'][$sub_id]['amount_error'] = t('На складе нет требуемого количества товара. В наличии: %0', array((float)$sub_product->getNum(0).' '.$sub_product->getUnit()->stitle));
                       $result['has_error'] = true;
                    }   
                }
                
                $result['total'] +=  $checked ? $sub_product_cost : 0;
            }
            if ($amount < $product->getAmountStep()) {
                $result['items'][$n]['amount_error'] = t('Количество должно быть не менее %0', array($product->getAmountStep()));
                $result['has_error'] = true;
            } 
            elseif ($module_config['check_quantity'] && $amount > $product->getNum($item['cartitem']['offer'])) {
                $result['items'][$n]['amount_error'] = t('На складе нет требуемого количества товара. В наличии: %0', array((float)$product->getNum($item['cartitem']['offer']).' '.$product->getUnit()->stitle));
                $result['has_error'] = true;
            }
        }
        
        $result['total_weight'] = $this->getTotalWeight(); 
        $result['total_base'] = $result['total'];
        return $result;
    }   
    
    
    
    /**
    * Добавляет к результату информацию о скидках
    * 
    * @param array $result - массив со сведениями о корзине
    * @param bool $use_currency - использовать ли текущую валюту?
    * @return array
    */
    protected function addDiscountData($result, $use_currency)
    {
        $shop_config = \RS\Config\Loader::byModule($this);
        $coupon_items = $this->getCouponItems();       
        $coupon_usage = $this->getCouponsUsage($coupon_items);
        $in_basket = $this->getProductItems();
        $type_order_coupons = array();
        
        foreach($coupon_items as $key => $item) {
            /**
            * @var \Shop\Model\Orm\Discount
            */
            $coupon = $item['coupon'];   
            
            //Проверим купон на минимальную сумму заказа
            $min_order_price = $coupon->getMinOrderPrice();
            if ($use_currency){
                $min_order_price = \Catalog\Model\CurrencyApi::applyCurrency($min_order_price);    
            } 
            if ($min_order_price > $result['total']) {
                $result['errors'][] = t('Минимальная сумма заказа для применения купона %0: %1', array(
                    $coupon['code'], CustomView::cost($min_order_price, $result['currency'])));
            } else {
                if (empty($coupon_usage[$key]['products']) && !$item['cartitem']->getExtraParam('ignore_usage')) {
                    $this->removeItem($key); //Удаляем купон, если нет соответствующих товаров
                } else {
                    //Применяем скидку к товарам
                    foreach($result['items'] as $uniq => &$data) {
                        $product = $in_basket[$uniq]['product'];
                        $offer = $in_basket[$uniq]['cartitem']['offer'];
                        $amount = $in_basket[$uniq]['cartitem']['amount'];

                        if (isset($coupon_usage[$key]['products'][$uniq])) {

                            $cost = $data['cost']; //По умолчанию считаем скидку от цены товара в корзине (цены пользователя)
                            if ($shop_config['discount_base_cost_type']) { //Если в настройках модуля задан тип цен, от которого считать скидку
                                //Получаем в $cost цену товара, от которой нужно считать скидку
                                $cost = $this->getProductPrice($product, $offer, $amount, $shop_config['discount_base_cost_type'], $use_currency);
                            }

                            $discount = $coupon->getDiscountValue($cost, $use_currency);

                            //Проверяем, если скидочный купон дает большую скидку,
                            // чем была у товара до этого, то применяем купон, иначе - нет.
                            if (($cost - $discount) < $data['cost']) {
                                $discount = $data['cost'] - ($cost - $discount);

                                $data['cost'] = round($data['cost'] - $discount, 2);
                                $data['discount'] = $discount;
                                $result['total'] -= $discount;
                                $result['total_base'] -= $discount;
                            }
                        }    
                        
                        //Добавим к сопутствующим товарам сведения о скидке, если сопутствующие присутствуют
                        if (isset($data['sub_products'])){
                            foreach ($data['sub_products'] as $k=>&$sub_product){
                                
                                if (isset($coupon_usage[$key]['products'][$k])) {

                                    $cost = $sub_product['cost']; //По умолчанию считаем скидку от цены товара в корзине (цены пользователя)
                                    if ($shop_config['discount_base_cost_type']) { //Если в настройках модуля задан тип цен, от которого считать скидку
                                        $concomitants = $product->getConcomitant();
                                        $cost = $this->getProductPrice($concomitants[$k], 0, $sub_product['amount'], $shop_config['discount_base_cost_type'], $use_currency);
                                    }

                                    $discount = $coupon->getDiscountValue($cost, $use_currency);

                                    //Проверяем, если скидочный купон дает большую скидку,
                                    //чем была у товара до этого, то применяем купон, иначе - нет.
                                    if (($cost - $discount) < $sub_product['cost']) {
                                        $discount = $sub_product['cost'] - ($cost - $discount);

                                        $sub_product['cost'] = round($sub_product['cost'] - $discount, 2);
                                        $sub_product['discount'] = $discount;
                                        $sub_product['single_cost'] = round($sub_product['single_cost'] - $coupon->getDiscountValue($sub_product['single_cost'], $use_currency), 2);

                                        //Если сопутствующий товар отмечен для посчёта
                                        if ($sub_product['checked']) {
                                            $result['total'] -= $discount;
                                            $result['total_base'] -= $discount;
                                        }
                                    }
                                }   
                            }
                        }
                    }
                }
            }
        }
        
        // Если купонов в корзине нет, то сбрасываем скидки у товаров на ноль
        if(empty($coupon_items)){
            foreach($result['items'] as $uniq => &$data) {
                $data['discount'] = 0;
                if (isset($data['sub_products'])){
                    foreach ($data['sub_products'] as $k=>&$sub_product){
                        $sub_product['discount'] = 0;     
                    }
                }
            }
        }
        
        $result = $this->addInternalProductDiscount($result, $use_currency);
        
        return $result;
    }


    /**
    * Возвращает заданный тип цен у товара с учетом его количества
    *
    * @param \Catalog\Model\Orm\Product $product объект товара
    * @param integer $offer номер комплектации
    * @param float $amount количество
    * @param integer $cost_id ID цены
    * @param bool $use_currency использовать ли текущую валюту?
    * @return float
    */
    private function getProductPrice($product, $offer, $amount, $cost_id, $use_currency)
    {
        $cost = $product->getCost($cost_id, $offer, false, true);
        if ($use_currency) $cost = \Catalog\Model\CurrencyApi::applyCurrency($cost);

        // Цена товара с учетом количества и комплектации
        $cost = ($cost * ($amount>0 ? $amount : 1));

        return $cost;
    }
    
    /**
    * Добавляет сведения по скидкам
    * 
    * @param float $order_discount - скидка общая в единицах
    * @param array $result - массив со сведениями о корзине
    * @param bool $use_currency - использовать ли текущую валюту?
    * @return array
    */
    private function addDiscountToProductsFromOrderDiscount($order_discount, $result)
    {
        if (!empty($result['items'])){
            //Получим долю скидки
            $percent = $order_discount / $result['total_without_order_discount'];
            $count_discount = 0; //Общая сумма скидки                                
            foreach($result['items'] as $uniq => &$data) {
                if ($data['cost'] > 0) { 
                    $one_item_discount = ceil($data['cost'] * $percent); //Скидка одного элемента
                    
                    if ($order_discount < $count_discount + $one_item_discount){ //Если последняя скидка больше по цене, то приведём её к нормальному виду
                        $one_item_discount = $order_discount - $count_discount;
                    }
                    $count_discount += $one_item_discount; //Общий плюсованный размер скидки
                    
                    // Удаляем скидки товаров из суммы заказа и подменяем скидки новыми
                    $data['cost']     = $data['cost'] - $one_item_discount;
                    $data['discount'] = ($one_item_discount + $data['discount']);
                    
                    //Пройдёмся по сопутствующим товарам
                    if (isset($data['sub_products']) && !empty($data['sub_products'])){
                        foreach ($data['sub_products'] as $sub_uniq=>&$subdata){
                            if ($subdata['checked']){ //Если есть выбранные
                                $one_item_discount = ceil($subdata['cost'] * $percent); //Скидка одного элемента
                                
                                if ($order_discount < $count_discount + $one_item_discount){ //Если последняя скидка больше по цене, то приведём её к нормальному виду
                                    $one_item_discount = $order_discount - $count_discount;
                                }
                                $count_discount += $one_item_discount; //Общий плюсованный размер скидки
                                
                                // Удаляем скидки товаров из суммы заказа и подменяем скидки новыми
                                $subdata['cost']     = $subdata['cost'] - $one_item_discount;
                                $subdata['discount'] = ($one_item_discount + $subdata['discount']);
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
    
    /**
    * Добавляет к результату информацию о скидках на заказ
    * 
    * @param array $result - массив со сведениями о корзине
    * @param bool $use_currency - использовать ли текущую валюту?
    * @return array
    */
    protected function addOrderDiscountData($result, $use_currency)
    {
        //Вызовем событие для модулей
        $eresult = \RS\Event\Manager::fire('cart.before.addorderdata', array(
            'cart_result' => $result,
            'cart' => $this,
            'use_currency' => $use_currency
        ));
        if($eresult->getEvent()->isStopped()){
            return;
        }
        list($result) = $eresult->extract();
        
        if (isset($result['order_discount']) || ($order_discounts = $this->getCartItemsByType(self::TYPE_ORDER_DISCOUNT))){
            if (!($coupons = $this->getCouponItems()) || (isset($result['order_discount_can_use_coupon']) && $result['order_discount_can_use_coupon'])){ //Если небыло применено ранее купонов, то можем общую скидку добавить
                //Если элемент с общей скидкой уже записан, то возьмём скидку оттуда
                if (!empty($order_discounts)){
                    $order_discount_item = current($order_discounts);
                    reset($order_discounts);
                    $order_discount = $order_discount_item['price']; 
                }else{
                    $order_discount = $result['order_discount'];
                }
                
                //Добавим сведения размере скидки
                $result['total_discount'] = $order_discount;
                $result['total_discount_unformatted'] = $order_discount;
                
                //Добавим записи до применения скидки на заказ
                $result['total_without_order_discount'] = $result['total'];
                
                //Добавим сведения по скидкам в каждый товар    
                $result = $this->addDiscountToProductsFromOrderDiscount($order_discount, $result);
                
                if ($use_currency){
                    $result['total_without_order_discount'] = CustomView::cost($result['total'], $result['currency']);
                }
                $result['total_without_order_discount_unformatted'] = $result['total'];
                //Отнимем от общей суммы            
                $result['total']      -= $order_discount;
                $result['total_base'] -= $order_discount;
            }
        }
        
        return $result;
    }
    
    /**
    * Добавляет сведения по скидкам к товарам, без купонов
    * 
    * @param array $result - массив со сведениями о корзине
    * @param bool $use_currency - использовать ли текущую валюту?
    */
    protected function addInternalProductDiscount($result, $use_currency)
    {
        $in_basket = $this->getProductItems();
        foreach($in_basket as $n => $item) {
            $extra = @unserialize($item['cartitem']['extra']) ?: array();
            if (isset($extra['personal_discount'])) {
                $personal_discount = $extra['personal_discount'];
                //Скидка может быть в процентах и в фиксированных величинах
                if (strpos($personal_discount, '%') !== false) {
                    $discount = $result['items'][$n]['cost'] * (((float)$personal_discount) / 100);
                } else {
                    $discount = (float)$personal_discount;
                    if ($use_currency){
                        $discount = \Catalog\Model\CurrencyApi::applyCurrency($discount);
                    } 
                }
                $discount = \Catalog\Model\CostApi::roundCost($discount, 'round');
                if ($discount > $result['items'][$n]['discount']) {
                    $delta = $discount - $result['items'][$n]['discount'];
                    $result['items'][$n]['cost']     = $result['items'][$n]['base_cost'] - $discount;
                    $result['items'][$n]['discount'] = $discount;
                    $result['total']      -= $delta;
                    $result['total_base'] -= $delta;
                }
            }
            if (isset($extra['sub_products_discount'])) {
                foreach ($extra['sub_products_discount'] as $subid=>$sub_product_discount) {
                    if (strpos($sub_product_discount, '%') !== false) {
                        $discount = $result['items'][$n]['sub_products'][$subid]['cost'] * (((float)$sub_product_discount) / 100);
                    } else {
                        $discount = (float)$sub_product_discount;
                        if ($use_currency){
                            $discount = \Catalog\Model\CurrencyApi::applyCurrency($discount);
                        }
                    }
                    $discount = \Catalog\Model\CostApi::roundCost($discount, 'round');
                    if ($discount > $result['items'][$n]['sub_products'][$subid]['discount']) {
                        $delta = $discount - $result['items'][$n]['sub_products'][$subid]['discount'];
                        $result['items'][$n]['sub_products'][$subid]['cost']     = $result['items'][$n]['sub_products'][$subid]['base_cost'] - $discount;
                        $result['items'][$n]['sub_products'][$subid]['discount'] = $discount;
                        $result['total']      -= $delta;
                        $result['total_base'] -= $delta;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
    * Возвращает товары, на которые данный купон предоставляет скидку
    * 
    * @param array $coupons_items - результат выполнения getCouponItems()
    * @return array
    */
    protected function getCouponsUsage($coupons_items)
    {
        $result = array();
        $products = $this->getProductItems();
        $dirapi = new \Catalog\Model\Dirapi();
        
        foreach($coupons_items as $key => $coupon_item) {
            $coupon = $coupon_item['coupon'];
            // Получение всех категорий, на которые действунт купон
            $coupon_products = $coupon['products'];
            if (!empty($coupon_products['group'])) {
                $coupon_products['group'] = $dirapi->FindSubFolder($coupon_products['group']);
            }
            $coupon['products'] = $coupon_products;
          
            $result[$key] = array(
                'products' => array()
            );
            foreach($products as $uniq => $product_item) {
                $product = $product_item['product'];
                //Сопутствующие
                if (isset($product['concomitant_arr']['product'])){
                    $concomitants = $product['concomitant_arr']['product'];   
                }
                
                if ($coupon->isForAll()) {
                   $result[$key]['products'][$uniq] = $product['id'];
                   
                   //Если есть сопутствующие, то и их добавим
                   if (!empty($concomitants)){
                       $result[$key]['products'] = $result[$key]['products'] + array_combine((array)$concomitants, (array)$concomitants);
                   }
                   
                } else { //Если у купона скидка на определенные товары и группы
                    $in_group = false;
                    $groups = array_merge($product['xdir'], $product['xspec']);
                    foreach($groups as $dir) {
                        if (@in_array($dir, (array)$coupon['products']['group'])) {
                            $in_group = true; 
                            break;
                        }
                    }
                    //Посмотрим по отдельным товарам
                    if (@in_array($product['id'], (array)$coupon['products']['product']) || $in_group) {
                        $result[$key]['products'][$uniq] = $product['id'];
                    }
                    //Если есть сопутствующие, то и их добавим
                    if (!empty($concomitants)){
                        foreach ((array)$concomitants as $concomitant){
                            
                            $in_group = false;
                            $product  = new \Catalog\Model\Orm\Product($concomitant);
                            $product->fillCategories();
                            $groups = array_merge($product['xdir'], $product['xspec']);
                            foreach($groups as $dir) {
                                if (@in_array($dir, (array)$coupon['products']['group'])) {
                                    $in_group = true;
                                    break;
                                }
                            }
                            if (@in_array($concomitant, (array)$coupon['products']['product']) || $in_group) {
                                $result[$key]['products'][$concomitant] = $concomitant;
                            }    
                        }
                    }
                }
            }                   
        }
        return $result;
    }
            
    
    /**
    * Возвращает объекты купонов на скидку
    * 
    * @return array
    */
    function getCouponItems()
    {
        if ($this->cache_coupons === null) {
            $this->cache_coupons = array();
            $ids = array();
            $cartitem_coupon = $this->getCartItemsByType(self::TYPE_COUPON);
            foreach ($cartitem_coupon as $cartitem) {
                $ids[] = $cartitem['entity_id'];
            }
            if (!empty($ids)) {
                $api = new \Shop\Model\DiscountApi();
                $api->setFilter('id', $ids, 'in');
                $coupons = $api->getAssocList('id');
                
                foreach($cartitem_coupon as $key => $cartitem) {
                    
                    if (!isset($coupons[ $cartitem['entity_id'] ]) 
                        || ($coupons[ $cartitem['entity_id'] ]->isActive() !== true && $this->mode != self::MODE_ORDER))
                    {
                        $this->removeItem($key); //Если купон стал неактивным, то исключаем его
                    } else {
                        $this->cache_coupons[$key] = array(
                            'coupon' => $coupons[ $cartitem['entity_id'] ],
                            'cartitem' => $cartitem
                        );
                    }
                }
            }
        }
        return $this->cache_coupons;
    }

    /**
    * Получает сколько раз был использован купон, текущим авторизованным пользователем
    * 
    * @param \Shop\Model\Orm\Discount $coupon - объект купона
    */
    function getCouponUsedTimesByCurrentUser(\Shop\Model\Orm\Discount $coupon){
       $user = \RS\Application\Auth::getCurrentUser(); 
        
       $cnt = \RS\DB\Adapter::sqlExec('SELECT COUNT(*)as cnt FROM('.\RS\Orm\Request::make()
            ->select('I.*,O.user_id')
            ->from(new \Shop\Model\Orm\OrderItem(),'I')
            ->join(new \Shop\Model\Orm\Order(),'O.id=I.order_id','O')
            ->where(array(
                'I.entity_id' => $coupon['id'],
                'I.type' => 'coupon',
            ))
            ->where(array(
                'O.user_id' => $user['id'],
            ))
            ->toSql().')as AA')
            ->getOneField('cnt',0);
       
       return $cnt; 
    }
    
    /**
    * Добавляет купон на скидку
    * 
    * @param string $code - код купона
    * @param array $extra - массив дополнительных данных
    * @return bool | string возвращает true или текст ошибки
    */
    function addCoupon($code, array $extra = null)
    {
        $discount_api = new \Shop\Model\Discountapi();
        $coupon = $discount_api->setFilter('code', $code)->getFirst();
        
        
        if (!$coupon) return t('Купон с таким кодом не найден');            
        if (count($this->getCartItemsByType(self::TYPE_COUPON)) >0) 
            return t('Нельзя использовать больше одного скидочного купона');
    
        
        //Если действует лимит на использование одним пользователем
        if ($coupon['oneuserlimit']>0){
            //Проверим авторизован ли пользователь
            if (!\RS\Application\Auth::isAuthorize()){
                return t('Для активации купона необходимо авторизоватся');
            }elseif($this->getCouponUsedTimesByCurrentUser($coupon)>=$coupon['oneuserlimit']){
                return t('Превышено число использования купона');
            }
        }    
        
    
        if (($error = $coupon->isActive()) !== true) return $error;
        $coupon_usage = $this->getCouponsUsage(array(
            'new' => array(
                'coupon' => $coupon
        )));
        
        
        if (empty($coupon_usage['new']['products']) && empty($extra['ignore_usage'])) {
            return t('Нет товаров, к которым можно применить скидочный купон %0', array($coupon['code']));
        }   
       
        //Добавляем событие перед  добавлением купона
        $eresult = \RS\Event\Manager::fire('cart.addcoupon.before', array(
            'coupon' => $coupon
        ));
        if($eresult->getEvent()->isStopped()){
            return implode(',', $eresult->getEvent()->getErrors());
        }  
        
        //Добавляем купон в базу
        $item = $this->cartitem;
        $item['session_id'] = $this->session_id;
        $item['uniq'] = $this->generateId();
        $item['dateof'] = date('Y-m-d H:i:s');
        $item['user_id'] = \RS\Application\Auth::getCurrentUser()->id;
        $item['type'] = self::TYPE_COUPON;
        $item['entity_id'] = $coupon['id'];
        $item['title'] = t('Купон на скидку %0', array($coupon['code']));
        $item['extra'] = serialize($extra);
        $item->insert();   
        
        $this->items[$item['uniq']] = $item;
        
        if (is_array($this->cache_coupons)) {        
            $this->cache_coupons[$item['uniq']] = array(
                'coupon' => $coupon,
                'cartitem' => $item
            );
        }           
        $this->cleanInfoCache();
        
        //Добавляем событие перед  добавление купона
        $eresult = \RS\Event\Manager::fire('cart.addcoupon.after', array(
            'coupon' => $coupon
        ));
        
        return true;
    }
    
    /**
    * Добавляет ошибки к корзине
    * 
    * @param string $message
    * @param bool $can_checkout
    * @param mixed $key
    * @return Cart
    */
    function addUserError($message, $can_checkout = true, $key = null)
    {
        $error = array(
            'message' => $message,
            'can_checkout' => $can_checkout,
        );
        
        if ($key === null) {
            $this->user_errors[] = $error; 
        } else {
            $this->user_errors[$key] = $error;
        }
        
        return $this;
    }
    
    /**
    * Возвращает массив с ошибкой по ключу
    * 
    * @param string $key - ключ в массиве ошибок
    * @return boolean
    */
    function getUserError($key)
    {
        return isset($this->user_errors[$key]) ? $this->user_errors[$key] : false;
    }
    
    /**
    * Удаляет одну по ключу или все пользовательские ошибки
    * 
    * @param mixed $key
    * @return Cart
    */
    function cleanUserError($key = null)
    {
        if ($key === null) {
            $this->user_errors = array();
        } else {
            unset($this->user_errors[$key]);
        }
        return $this;
    }
    
    /**
    * Добавляет пользовательские ошибки к результату $result
    * 
    * @param array $result - массив со сведениями
    * @return array
    */
    protected function appendUserErrors($result)
    {
        foreach($this->user_errors as $error) {
            $result['errors'][] = $error['message'];
            $result['has_error'] = $result['has_error'] || (!$error['can_checkout']);
        }
        
        return $result;
    }
    
    /**
    * Возвращает объект заказа, которому принадлежит корзина
    * 
    * @return \Shop\Model\Orm\Order
    */
    function getOrder()
    {
        return $this->order;
    }

    /**
     * Повторяет корзину из определённого заказа, добавляя товары к текущей корзине пользователя
     *
     * @param string $order_num - номер заказа
     *
     * @return boolean
     */
    function repeatCartFromOrder($order_num)
    {
        //Подгрузим заказ
        $order_api = new \Shop\Model\OrderApi();
        /**
         * @var \Shop\Model\Orm\Order $order
         */
        $order = $order_api->getById($order_num, 'order_num');

        if ($order){ //Если заказ нашли, то получим его корзину и попрбуем добавить теже товары в текущую корзину

            //Проверим принадлежность именно к данному пользователю
            if (\RS\Application\Auth::isAuthorize() && (\RS\Application\Auth::getCurrentUser()->id != $order['user_id'])){
                $this->addUserError(t('Не указан пользователь для заказа №%0', array($order_num)));
                return false;
            }

            $order_cart = $order->getCart()->getOrderData();

            foreach ($order_cart['items'] as $uniq_id=>$item){
                $cartitem = $item['cartitem'];


                /**
                 * @var \Shop\Model\Orm\OrderItem $item
                 * @var \Catalog\Model\Orm\Product $product
                 */
                //Получим есть ли такой товар, то добавим его
                $product = \RS\Orm\Request::make()
                                ->from(new \Catalog\Model\Orm\Product())
                                ->where(array(
                                    'id' => $cartitem['entity_id']
                                ))->object();
                if ($product){ //Добавим товар в корзину
                    $mo_array = array();
                    $multioffers = (array)@unserialize($cartitem['multioffers']);
                    if (!empty($multioffers)){

                        foreach($multioffers as $prop_id=>$multioffer){
                            $mo_array[$prop_id] = $multioffer['value'];
                        }
                    }
                    $this->addProduct($product['id'], $cartitem['amount'], $cartitem['offer'], $mo_array);
                }
            }
            return true;
        }

        return false;
    }
}