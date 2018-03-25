<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model;
use Main\Model\NoticeSystem\HasMeterInterface;


/**
 * Класс содержит API функции для работы с формой купить в один клик
 * @ingroup Catalog
 */
class OneClickApi extends \RS\Module\AbstractModel\EntityList
{
    private
        $click_info = array(); //Массив полей для отправки и сохранения
    
    function __construct()
    {
        parent::__construct(new \Catalog\Model\Orm\Product());
    }
    
    /**
    * Проверяет поля для отправки и заполняет массив значений перед отправкой
    * 
    * @param \RS\Config\UserFieldsManager $click_fields_manager - менеджер дополнительных полей 
    * @param boolean $use_captcha - Использовать каптчу
    * @return boolean
    */
    function checkFieldsFromPostToSend($click_fields_manager, $use_captcha = true)
    {
        $url = \RS\Http\Request::commonInstance();
        
        //Проверим и получим обязательные переменные из запроса
        $this->click_info['name']  = $url->request('name', TYPE_STRING);       //Имя 
        $this->click_info['phone'] = $url->request('phone', TYPE_STRING);      //Телефон
        $kaptcha_key               = $url->request('kaptcha', TYPE_STRING);    //Каптча
        $click_fields              = $url->request('clickfields', TYPE_ARRAY); //Доп. поля 
        

        $config = \RS\Config\Loader::byModule($this); //Конфиг модуля
        if ($config['oneclick_name_required'] && empty($this->click_info['name'])){  //Если пустые поля
            $this->addError(t("Поле 'Имя' является обязательным.")); 
        }

        if (empty($this->click_info['phone'])){  //Если пустые поля
            $this->addError(t("Поле 'Телефон' является обязательным.")); 
        }

        //Проверим дополнительные поля
        if (!$click_fields_manager->check($click_fields)){
            $this->addError(implode(', ', $click_fields_manager->getErrors()));
        }
        $this->click_info['ext_fields'] = $click_fields_manager->getStructure(); //Получим значения доп. полей

        //Проверим каптчу, если не залогинен
        if (!\RS\Application\Auth::isAuthorize() && $use_captcha){
            $captcha = \RS\Captcha\Manager::currentCaptcha();
            $orm_object = new \Catalog\Model\Orm\OneClickItem();
            $captcha_context = $orm_object->__kaptcha->getReadyContext($orm_object);
            if (!$captcha->check($kaptcha_key, $captcha_context)) {
                $this->addError($captcha->errorText());
            } 
        }
        
        //Если ошибка то выходим
        if ($this->hasError()){
            return false;
        }
        return true;
    }
    
    /**
    * Отсылает форму купить в один клик и предварительно проверяет её. Неоходим вызов функции checkFieldsFromPostToSend
    * 
    * @param array $products  - массив объектов товаров со всеми сведениями. сведения о выбранной комплектации храняться в ключено offer_fields [\Catalog\Model\Orm\Product, ...] 
    * 
    * @return boolean
    */
    function send($products)
    { 
        if (isset($this->click_info['name']) && empty($this->click_info['name'])){
            $this->click_info['name'] = t('Не указано');
        }               
        $this->click_info['products'] = $products;

        $notice = new \Catalog\Model\Notice\OneClickUser();
        $notice->init($this->click_info);
        //Отсылаем sms пользователю
        \Alerts\Model\Manager::send($notice); 
        
        //Добавим в БД
        $this->addOneClickInfo();
    }       
    
    /**
    * Добавляет запись о покупке в один клик в БД
    *
    */
    private function addOneClickInfo()
    {
        //Добавим в БД сведения
        $click_item = new \Catalog\Model\Orm\OneClickItem();
        $click_item['user_fio']    = $this->click_info['name'];
        $click_item['user_phone']  = $this->click_info['phone'];
        $click_item['dateof']      = date("Y.m.d H:i:s");
        $click_item['products']    = $this->click_info['products'];
        $click_item['sext_fields'] = serialize($this->click_info['ext_fields']);
        $click_item['ip']          = $_SERVER['REMOTE_ADDR'] ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR'];

        $click_item->insert();
    }
    
    /**
    * Возвращает подготовленные данные (товары) из корзины пользователя для отправки
    * 
    */
    function getPreparedProductsFromCart()
    {
        $products      = array();
        $cart          = \Shop\Model\Cart::currentCart();
        $product_items = $cart->getProductItems(); //Получим товары из корзины
        foreach ($product_items as $item){
            $product  = $item['product'];  //Товар
            $cartitem = $item['cartitem']; //Объект в корзине
            
            
            //Предварительные данные
            $offer_fields = array(
                'offer_id' => $cartitem['offer'],
                'multioffer' => array(),
                'multioffer_val' => array(),
                'amount' => $cartitem['amount'] //количество
            );
            //Если есть комплектация
            if ($cartitem['offer']!==null){
                $offer = \RS\Orm\Request::make()
                                            ->from(new \Catalog\Model\Orm\Offer())
                                            ->where(array(
                                                'product_id' => $product['id'],
                                                'sortn' => $cartitem['offer'],
                                            ))->object();
                $offer_fields['offer']    = $offer['title'];
                $offer_fields['offer_id'] = $offer['id'];
            }
            
            //Соберём многомерные комплектации, если они есть
            $multioffers = @unserialize($cartitem['multioffers']);
            if (!empty($multioffers)){
                foreach ($multioffers as $prop_id=>$multioffer){
                    $offer_fields['multioffer'][$prop_id]     = $multioffer['value']; //
                    $offer_fields['multioffer_val'][$prop_id] = $multioffer['title'].": ".$multioffer['value']; //Текстовое представление
                }
            }
            
            $product['offer_fields'] = $offer_fields; 
            $products[] = $product;
        }
        return $products;
    }
    
    /**
    * Создаёт заказ из Купить в один клик
    * 
    * @param Orm\OneClickItem $oneclick - объект купить в один клик
    * @return \Shop\Model\Orm\Order
    */
    function createOrderFromOneClick(Orm\OneClickItem $oneclick)
    {
        //Создадим заказ
        $order = new \Shop\Model\Orm\Order();
        //Данные пользователя
        if ($oneclick['user_id'] > 0) {
            $order['user_id'] = $oneclick['user_id'];
        } else {
            $order['user_fio']   = $oneclick['user_fio'];
            $order['user_phone'] = $oneclick['user_phone'];
        }
        
        //Данные валюты
        $order['currency']   = $oneclick['currency'];
         
        $currency_api = new \Catalog\Model\CurrencyApi();
        $currency     = $currency_api->setFilter('title', $order['currency'])->getFirst();
        if ($currency){
            $order['currency_ratio']  = $currency['ratio'];
            $order['currency_stitle'] = $currency['stitle'];
        }
        //Отключение уведомлений
        $order['disable_checkout_notice'] = 1; 

        $order->insert();
        
        //Создаём корзину
        $cart = $order->getCart();
        
        $products_arr = array();
        
        //Получим информацию о товаре, чтобы создать корзину
        $products = $oneclick->tableDataUnserialized();
        
        if (!empty($products)){
            $symb = array_merge(range('a', 'z'), range('0', '9')); //Символя для генерации уникального индекса
            foreach ($products as $product_info){
                //Попробуем загрузить сам товар
                $product     = new \Catalog\Model\Orm\Product($product_info['id']);
                $offer_sortn = 0;
                //Если есть id комплектации, то добавим комплектацию
                if (isset($product_info['offer_fields']['offer_id']) && $product_info['offer_fields']['offer_id']>0){
                    $offer       = new \Catalog\Model\Orm\Offer($product_info['offer_fields']['offer_id']);
                    $offer_sortn = (int)$offer['sortn'];
                }
                
                //Генерируем запист товара
                $uniq = \RS\Helper\Tools::generatePassword(10, $symb); // Уникальный индекс товара
                $cost_id = \Catalog\Model\CostApi::getUserCost($order->getUser()); // Цена пользователя
                $products_arr[$uniq] = array(
                    'uniq'          => $uniq,
                    'type'          => \Shop\Model\Cart::TYPE_PRODUCT,
                    'entity_id'     => $product_info['id'],  
                    'title'         => $product_info['title'],  
                    'barcode'       => $product->getBarCode($offer_sortn),  
                    'single_weight' => $product->getWeight($offer_sortn),
                    'amount'        => $product_info['offer_fields']['amount'],  
                    'offer'         => $offer_sortn,
                    'single_cost'   => $product->getCost($cost_id, $offer_sortn, false),
                );
                
                if (isset($product_info['offer_fields']['multioffer'])){
                    //Разберём многомерные комплектации из текта
                    $products_arr[$uniq]['multioffers'] = $product_info['offer_fields']['multioffer'];
                }
            }
        }

        $cart->updateOrderItems($products_arr); //Обновляем товары в корзине
        $cart->saveOrderData(); //Сохраняем данные товаров в БД
        
        //Отправляем уведомление покупателю
        $notice = new \Shop\Model\Notice\CheckoutUser();
        $notice->init($order);
        \Alerts\Model\Manager::send($notice);  
        
        //Отключим уведомления
        $order['notify_user'] = false;
        $order->update(); //Обновляем заказ
        
        $oneclick['status'] = Orm\OneClickItem::STATUS_VIEWED;
        $oneclick->update();        
        
        return $order;
    }
    
    /**
    * Подготавливает сведения о многомерных комплектациях для отображения
    * 
    * @param array $offer_fields - массив доп. сведения
    * @param Orm\Product $product - объект товар 
    * @param array $multioffers - массив с сведениями для установки
    * @return array
    */
    function preparedMultiOffers($offer_fields, $product, $multioffers)
    {
        //Многомерные комплектации
        if (!empty($multioffers)){
            if ($product->isMultiOffersUse() || $product->isVirtualMultiOffersUse()){
                $product_multioffers = $product->fillMultiOffers();
            }
            

            //Переберём комплектации и запишем значения в виде строки, на случай если удалён товар
            $multioffers_val = array();
            foreach($multioffers as $prop_id=>$value){
                //Проверим, если данные в старом формате, обрежем как надо
                if (mb_stripos($value, ":")!==false){
                    strtok($value, ":");
                    $multioffers[$prop_id] = trim(strtok(":"));
                }
                
                //Запишем значения текстом   
                if (isset($product_multioffers['levels'][$prop_id])){
                    $property_title            = ($product_multioffers['levels'][$prop_id]['title']) ? $product_multioffers['levels'][$prop_id]['title'] : $product_multioffers['levels'][$prop_id]['prop_title']; 
                    $multioffers_val[$prop_id] = $property_title.": ".$multioffers[$prop_id];   
                }   
            }
            $offer_fields['multioffer']     = $multioffers;
            $offer_fields['multioffer_val'] = $multioffers_val;
        }
        return $offer_fields;
    }
    
    
    /**
    * Подготавливает 
    * 
    * @param \Catalog\Model\Orm\Product $product - объект товара
    * @param integer|null $offer_id - id комплектации если есть
    * @param array $multioffers - массив многомерных комплектаций
    * @return array
    */
    function prepareProductOfferFields($product, $offer_id = null, $multioffers = array())
    {
         $offer_fields = array(
            'offer' => null,
            'offer_id' => null,
            'multioffer' => array(),
            'multioffer_val' => array(),
            'amount' => 1 //Количество
       );
       
       //Многомерные комплектации
       $offer_fields = $this->preparedMultiOffers($offer_fields, $product, $multioffers);
       if($offer_id) {
           $offer_fields['offer_id'] = $offer_id;
           $offer_fields['offer'] = \RS\Orm\Request::make()
                                        ->from(new \Catalog\Model\Orm\Offer())
                                        ->where(array(
                                            'product_id' => $product['id'],
                                            'id' => $offer_id,
                                        ))->exec()->getOneField('title', '');
       }
       return $offer_fields;
    }
}