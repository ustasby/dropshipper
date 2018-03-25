<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

class Sheepla extends AbstractType
{
    const 
        API_URL             = "http://api.sheepla.com/",                               //URL API Sheepla
        PUBLIC_API_URL_JS   = "http://panel.sheepla.pl/Content/GetWidgetAPIJavaScript",//URL JS виджета
        PUBLIC_API_URL_CSS  = "http://panel.sheepla.pl/Content/GetWidgetAPICss",       //URL CSS виджета
        DEFAULT_LANGUAGE_ID = 1049, // Россия - язык по умолчанию
        REQUEST_TIMEOUT     = 10; // 10 сек. таймаут запроса к api
    
    protected
        $templates        = null, //Шаблоны доставки
        $template_carrier = null, //Агрегатор доставки текущего шаблона
        $carries          = null, //Агрегаторы доставки существующие в sheepla
        $carries_accounts = null; //Агрегаторы доставки привязаные к Вашей sheepla
    
    private
        $cache_api_requests = array(),  // Кэш запросов к серверу рассчета 
        $cache_currencies   = null,  // Валюты
        $delivery_currency  = null;  // Текущая Валюта
        
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Sheepla');
    }
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Sheepla агрегатор доставок<br/><br/>
        <div class="notice-box no-padd">
            <div class="notice-bg">
                Для работы доставки необходимо указывать Вес у товара в граммах.<br/> 
                Укажите Вес по умолчанию в <b>"Веб-сайт" &rarr; "Настройка модулей" &rarr; "Каталог товаров" &rarr; "Вес одного товара по-умолчанию"</b><br/>
                <b>Минимальный вес для расчётов - 100 грамм.</b><br/><br/>
                Для работы Sheepla также необходимо в настройках источника доставок указать опцию &quot;
Использовать правила расчета стоимости доставки&quot; и настроить правила используя автоматический(динамический) подсчёт в настройках аккаунта на Sheepla 
            </div>
        </div>');
    }
    
    /**
    * Возвращает идентификатор данного типа доставки. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return t('sheepla');
    }
    
    /**
    * Функция которая возвращает надо ли, проверять возможность создание заказа на доставку
    *
    */
    function getNeedCheckCreate(){
        return true;
    } 
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'max_weight' => new Type\Varchar(array(
                'description' => t('Максимальный вес, грамм'),
            )),
            'width' => new Type\Integer(array(
                'description' => t('Свойство со значением ширины изделия'),
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),
            'height' => new Type\Integer(array(
                'description' => t('Свойство со значением высоты изделия'),
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),
            'length' => new Type\Integer(array(
                'description' => t('Свойство со значением длинны изделия'),
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),
            'language' => new Type\Integer(array(
                'description' => t('Язык интефейса для виджетов'),
                'ListFromArray' => array($this->getLanguages()),
            )),
            'admin_api' => new Type\Varchar(array(
                'description' => t('Ключ API администратора'),
                'hint' => t('Получается из панели Sheepla'),   
                'maxLength' => 255,
                'template' => '%shop%/form/delivery/sheepla/sheepla_admin_api.tpl',
            )),
            'public_api' => new Type\Varchar(array(
                'description' => t('Ключ API публичный'),
                'hint' => t('Получается из панели Sheepla'),
                'maxLength' => 255
            )),
            'template_id' => new Type\Integer(array(
                'description' => t('Шаблон доставки'),
                'list' => array(array('\Shop\Model\DeliveryType\Sheepla','staticGetTemplates'),$this),
                'template' => '%shop%/form/delivery/sheepla/sheepla_template.tpl',
            )),
            'timeout' => new Type\Integer(array(
                'description' => t('Время ожидания ответа Sheepla, сек'),
                'hint' => t('Иногда запросы к Sheepla идут очень долго,<br/> чтобы не дожидатся ответа используется это значение.<br/>Рекоммендуемое значение 10 сек.'),
                'default' => 10,
            )),  
        ));
        return new \RS\Orm\FormObject($properties);
    } 
    
    /**
    * Получает валюту по имени этой волюты пришедшей из СДЭК
    * 
    * @param string $name - сокращённое название валюты из СДЭК
    */
    private function getCurrencyByName($name)
    {
        if ($this->cache_currencies === null){
           //Подгружим валюты системы
           $this->cache_currencies = \RS\Orm\Request::make()
                    ->from(new \Catalog\Model\Orm\Currency())
                    ->where(array(
                        'public' => 1
                    ))
                    ->orderby('`default` DESC')
                    ->objects(null,'title'); 
        }
        
        if (isset($this->cache_currencies[$name])){
            return $this->cache_currencies[$name];
        }else{
            foreach($this->cache_currencies as $currency){
                if ($currency['default']){
                    return $currency;
                }
            }
        }
    }
    
    
    /**
    * Возвращает стоимость доставки для заданного заказа. Только число.
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @param boolean $use_currency - Привязывать валюту?
    * @return double
    */
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery, $use_currency = true)
    {
        if(!$address) { 
            $address = $order->getAddress();
        }
        $order_delivery  = $order->getDelivery(); 
        
        //Получим данные по стоимостям доставок
        $cache_key = md5($order['order_num'].$order_delivery['id']);
        if (!isset($this->cache_api_requests[$cache_key])){
           $this->cache_api_requests[$cache_key] = $sxml = $this->requestGetDeliveryCost($order, $address);
        }else{
           $sxml = $this->cache_api_requests[$cache_key]; 
        }
        $templateId = $this->getOption('template_id'); //Текущий шаблон
        
                                                                     
        $price = false;
        $default_currency = \Catalog\Model\CurrencyApi::getDefaultCurrency(); //Текущая валюта
        $this->delivery_currency = $default_currency['stitle']; 
        
        //Если есть методы с посчитанными ценами
        if (isset($sxml->deliveryMethods)){
            $found = false; 
            foreach($sxml->deliveryMethods->deliveryMethod as $deliveryMethod){
                if ($templateId==(int)$deliveryMethod->shipmentTemplateId){
                    $price        = (string)$deliveryMethod->price; 
                    $currency_obj = $this->getCurrencyByName((string)$deliveryMethod->currency); 
                    $this->delivery_currency = $currency_obj['stitle'];
                    $found = true;
                }
            }   
            if (!$found){ //Если небыло найдено цены за доставку среди полученных
                $this->addError(t('Не удалось подсчитать стоимость'));
            }  
            $price = $this->applyExtraChangeDiscount($delivery, $price); //Добавим наценку или скидку                                  
        }else{
            $this->addError(t('Не удалось подсчитать стоимость'));
            return false;
        }
        
        return $price;
    }
    
    /**
    * Возвращает текст, в случае если доставка невозможна. false - в случае если доставка возможна
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @return mixed
    */
    function somethingWrong(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if (!$address){
           $address = $order->getAddress(); 
        }
        
        $cost = $this->getDeliveryCost($order, $address, $order->getDelivery());
        
        
        if ($this->hasErrors()){ //Если есть ошибки
            return $this->getErrorsStr();
        }
        
        return false;
    }
    
    /**
    * Применение одного правила. Возвращает сумму
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address
    * @param mixed $rule
    * @param mixed $last_delivery_cost
    * @return double
    */
    private function applyRule(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address, $rule, $last_delivery_cost)
    {
        // Если это правило для какой-то конкретной зоны
        if($rule['zone'] != 'all') {
            // Если у адреса не указан регион
            if(!$address->region_id){
                return 0;
            }
            $order_zones = \Shop\Model\ZoneApi::getZonesByRegionId($address->region_id);

            // Если данное правило не совпадает с зоной доставки
            if($rule['zone'] != 'all' && !in_array($rule['zone'], $order_zones)){
                return 0;
            }
        }
        
        
        $order_price_without_delivery = $order->getCart()->getCustomOrderPrice(array(\Shop\Model\Cart::TYPE_DELIVERY));
        $examining_value = 0;
        
        // Определяем, какое из значений заказа будет использовано в качестве условия применения данного правила
        switch($rule['ruletype']) {
            case self::RULE_TYPE_SUM: 
                $examining_value = $order_price_without_delivery;          // Сумма заказа 
                break;
                
            case self::RULE_TYPE_WEIGHT: 
                $examining_value = $order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_G);                    // Вес заказа
                break;
            
            default:
                throw new \Exception(t('Неизвестный тип правила %ruletype', $rule));
        }
        
        $formula_vars = array(
            'S' => $order_price_without_delivery,
            'W' => $order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_G),
        );
        $value = $this->formulaEval($rule['value'], $formula_vars);
        
        // Если экзаменуемое значение находится в пределах, заданных данным правилом
        if($examining_value >= $rule['from'] && $examining_value <= $rule['to']) {
            switch($rule['actiontype']) {
                case self::ACTION_TYPE_FIXED:
                    return $value;
                case self::ACTION_TYPE_ORDER_PERCENT:
                    return ($order_price_without_delivery * $value)/100;
                case self::ACTION_TYPE_DELIVERY_PERCENT:
                    return ($last_delivery_cost * $value)/100;
                default:
                    throw new \Exception(t('Неизвестный тип надбавки %actiontype', $rule));
            }
        }
    }  
    
    /**
    * Получение значения формулы
    * 
    * @param mixed $formula
    * @param mixed $formula_vars
    */
    private function formulaEval($formula, array $formula_vars = array())
    {
        extract($formula_vars);
        $current_reporting_level = error_reporting();
        error_reporting(E_ALL);
        ob_start();
        $value = eval('return '.$formula.';');
        $output = ob_get_clean();
        error_reporting($current_reporting_level);
        if($value === false || $output){
            throw new \Exception(t('Ошибка в формуле <big><b>%0</b></big>', array($formula)));
        }
        return $value;
    }
    
    
    /**
    * Запрос к серверу рассчета стоимости доставки. Ответ сервера кешируется
    * 
    * @param string $xml_request - xml которая отправляется в виде запроса на сервер
    * @param string $method - метод который будет запрашиватся
    */
    private function apiRequest($xml_request,$method)
    {
        
        try{
            $result = false;
            $context = stream_context_create(array('http' => array(
                'method' => "POST",
                'header' => "Content-Type: text/xml; charset=utf-8",
                'content' => $xml_request,
                'content-length' => mb_strlen($xml_request),
                'timeout' => $this->getOption('timeout', 10) ? $this->getOption('timeout', 10) : 10,
                )));
            if (mb_strlen($xml_request) > 0)
            {
                
                $result = @file_get_contents(self::API_URL.$method, false, $context);
                if (!$result){
                    return false;
                }else{
                    return simplexml_load_string($result);
                }
            }
            else
            {
                $result = @file_get_contents(self::API_URL.$method);
            }

            return $result; 
        }catch(\Exception $ex){ //Если запрос не удался
            throw new \RS\Exception(t('Запрос выполнить не удалось').$ex->getMessage(),$ex->getCode());
        }
    }
    
    
    /**
    * Проверяет наличие ошибок в ответе доставки
    * 
    * @param \SimpleXMLElement $response - xml ответ от сервера
    * @param mixed $method
    */
    private function checkErrorsAnswer($response,$method)
    {  
        if (isset($response->errors)){ //Если секция с ошибками найдена
            foreach($response->errors->error as $error){   
               $this->addError((string)$error); 
            }
            return false;
        }
        return true;
    }
    
    /**
    * Проверяет наличие ошибок в ответе при оформлении заказа
    * 
    * @param \SimpleXMLElement $response - xml ответ от сервера
    * @param mixed $method
    */
    private function checkOrderErrorsAnswer($response,$method)
    {  
        if (isset($response->orders->order->errors->error)){ //Если секция с ошибками при оформлении заказа найдена
            foreach($response->orders->order->errors->error as $error){   
               $this->addError((string)$error); 
            }
            throw new \RS\Exception(t("При отправке заказа с доставкой возникли следующие 
            ошибки: \"%0\" Метод %1", array(implode(",",$this->getErrors()), $method)), 100);
            exit();
        }
        return true;
    }
    
    
    /**
    * Получает текущий набор шаблонов из sheepla в виде массива ключ=>значение
    * 
    * @param Sheepla $sheepla_delivery - объект доставки sheepla
    * @return array()
    */
    static function staticGetTemplates(Sheepla $sheepla_delivery)
    {
       $templates = $sheepla_delivery->requestGetShipmentTemplates();
       
       $arr = array();
       foreach ($templates as $template_id=>$template) {
         $arr[$template_id] = $template['name'];  
       }
       
       return $arr;
    }
    
    /**
    * Получает список шаблонов ключ=>значение при помощи api переданного API администратора
    * 
    * @param array $params - параметры
    */
    static function staticGetTemplatesByApiKey($params)
    {
        
       $api_key = $params['admin_api']; 
       $sheepla_delivery = new self();
       $sheepla_delivery->setOption('admin_api',$api_key);
       
       $list  = self::staticGetTemplates($sheepla_delivery);
       $items = array();
       foreach ($list as $id=>$item){
          $items[] = array(
            'id' => $id,
            'title' => $item,
          ); 
       }
       $arr['list'] = $items;
       
       return $arr;
    }     
    
    /**
    * Запрашивает текущий набор шаблонов из sheepla
    * 
    */
    private function requestGetShipmentTemplates()
    {
        if ($this->templates === null){
            $method = 'getShipmentTemplates';
            
            //Подготовим XML
            $sxml = new \SimpleXMLElement('<getShipmentTemplatesRequest/>');
            $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
            $sxml->authentication->apiKey = $this->getOption('admin_api');
            
            $xml = $sxml->asXML();
            
            $response_xml = $this->apiRequest($xml,$method);
            
            if (!$response_xml){ //Если запрос не удался           
                throw new \RS\Exception(t('Не удалось отправить запрос в Sheepla. Метод ').$method,101);
            }
            
            if (!$this->checkErrorsAnswer($response_xml,$method)){
                return array();
            }
            $templates = array();
            
            //Преобразуем xml в массив
            foreach ($response_xml->templates->template as $template){
               $template_id = (int)$template['id']; 
               
               $templates[$template_id] = array(
                 'id'              => $template_id,
                 'name'            => (string)$template['name'],
                 'carrierName'     => (string)$template['carrierName'],
                 'isCod'           => (boolean)$template['isCod'],
                 'baseServiceCode' => (int)$template['baseServiceCode'],
               );  
            }

            $this->templates = $templates; 
        }                     
        
        return $this->templates;
    }
    
    /**
    * Получает имя текущего агрегатора доставки
    * 
    */
    private function getCarrierTemplateName()
    {
       $templates = $this->requestGetShipmentTemplates();
        
       $carrier_name = false;
       if ( !empty($templates) ) {
            $template_id  = $this->getOption('template_id');
            $carrier_name = $templates[$template_id]['carrierName'];  
       }
       return $carrier_name;
    }
    
    /**
    * Получает текущего агрегатора доставки
    * 
    */
    private function getTemplateCarrier()
    {
       if ($this->template_carrier === null){
           $templates = $this->requestGetShipmentTemplates();
        
           $carrier = false;
           if ( !empty($templates) ) {
                $template_id = $this->getOption('template_id');
                $carrier     = $templates[$template_id];  
           }
           $this->template_carrier = $carrier;  
       } 
       return $this->template_carrier;
    }
    
    /**
    * Получает id агрегатора доставок по имени 
    * 
    * @param string $name - имя доставки
    * @return integer|boolean
    */
    private function getCarrierIdByName($name)
    {
       $carriers = $this->requestGetCarrierAccounts(); 
       return $carriers[$name]['carrierAccountId'];
    }
    
    /**
    * Получает дополнительный узел с пояснением по доставке
    * 
    * @param \SimpleXMLElement $sxml - объект XML
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * 
    */
    private function getAdditionalDeliveryNode(&$sxml,\Shop\Model\Orm\Order $order)
    {
        $extra = $this->getExtraDataArray($order);
        if (empty($extra)) return;
        
        //$sxml->orders->order->deliveryOptions->cod       = $this->getOption('cashornot',0); // Оплата наличными при доставке
        //$sxml->orders->order->deliveryOptions->insurance = $this->getOption('insurance',0); // Страховка посылки
        
        foreach ($extra as $str_method=>$value){
           $method = "up".str_replace('-','',$str_method); 
           if ( method_exists($this, $method) ) {
                $this->$method($sxml,$value);
           }
        } 
        
    }
    
    /**
    * Отправка запроса получения созданных в аккаунте агрегаторов доставок
    * Возвращает false если  есть ошибки, либо номер заказа созданного
    * 
    * @return false|integer
    */
    private function requestGetCarrierAccounts($field_key = 'carrier')
    {
        if ($this->carries_accounts === null){
            $method = 'getCarrierAccounts';
            
            //Подготовим XML
            $sxml = new \SimpleXMLElement("<getCarrierAccountsRequest/>");
            $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
            
            $sxml->authentication->apiKey  = trim($this->getOption('admin_api')); //Ключ Api 
         
            $xml = $sxml->asXML();

            $response_xml = $this->apiRequest($xml,$method);
            if (!$response_xml){ //Если запрос не удался
                $this->addError('Не удалось отправить запрос в Sheepla. Метод '.$method);
            }
            
            
            
            $arr = array();
            if (isset($response_xml->carrierAccounts->carrierAccount)){
               foreach ($response_xml->carrierAccounts->carrierAccount as $carrierAccount){
                   $arr[(string)$carrierAccount->$field_key] = array(
                      'carrier'          => (string)$carrierAccount->carrier,
                      'name'             => (string)$carrierAccount->name,
                      'carrierAccountId' => (string)$carrierAccount->carrierAccountId,
                   );
               } 
            }                                          
            $this->carries_accounts = $arr; 
        }
        return $this->carries_accounts;
    }
    
    
    /**
    * Отправка запроса получения интегрированных в sheepla доставок
    * Возвращает false если  есть ошибки, либо номер заказа созданного
    * 
    * @return false|integer
    */
    private function requestGetIntegratedCarriers()
    {
        if ($this->carries === null){
            $method = 'getIntegratedCarriers';
            
            //Подготовим XML
            $sxml = new \SimpleXMLElement("<getIntegratedCarriersRequest/>");
            $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
            
            $sxml->authentication->apiKey  = trim($this->getOption('admin_api')); //Ключ Api 
         
            $xml = $sxml->asXML();

            $response_xml = $this->apiRequest($xml,$method);
            if (!$response_xml){ //Если запрос не удался
                $this->addError(t('Не удалось отправить запрос в Sheepla. Метод ').$method);
            }
            
            $arr = array();
            if (isset($response_xml->shipments->carrier)){
               foreach ($response_xml->shipments->carrier as $carrier){
                   $arr[(string)$carrier->carrierId] = array(
                      'id'      => (string)$carrier->carrierId,
                      'name'    => (string)$carrier->name,
                      'country' => (string)$carrier->country,
                   );
               } 
            }
            $this->carries = $arr; 
        }
        return $this->carries;
    }
    
    /**
    * Отправляет запрос на получение цены от Sheepla
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - текущий адрес
    */
    private function requestGetDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if(!$address){
            $address = $order->getAddress();
        } 
        
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><checkoutPricingRequest/>");
        $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
        
        $sxml->authentication->apiKey = trim($this->getOption('admin_api')); //Ключ Api 
        
        $sxml->orderDate = date("c");
        $sxml->deliveryAddress->zipCode = $address['zipcode'];
        $sxml->deliveryAddress->city = $address['city'];

        //Товары в заказе
        $cart     = $order->getCart();
        $products = $cart->getProductItems();
        $cartdata = $cart->getPriceItemsData();
        
        $i=0;
        //Добавим товары
        foreach ($products as $n=>$item){
           /**
           * @var \Catalog\Model\Orm\Product
           */
           $product = $item['product'];
           $amount  = $item['cartitem']['amount']; 
           $cost    = $cartdata['items'][$n]['single_cost'] * $amount; 
           
           $sxml->products->product[$i]->attribute[0]['name']  = 'WEIGHT';
           $sxml->products->product[$i]->attribute[0]['value'] = $product->getWeight($item['cartitem']['offer'], \Catalog\Model\Api::WEIGHT_UNIT_G);
           $sxml->products->product[$i]->attribute[1]['name']  = 'PRICE';
           $sxml->products->product[$i]->attribute[1]['value'] = $cost;
           
           $i++;
        }
                                              
        $xml = $sxml->asXML(); //XML заказа
        $xml = $this->toFormatedXML($xml);
        
        $method       = 'checkoutPricing';
        $response_xml = $this->apiRequest($xml,$method);
   
        //Проверка на общие ошибки
        $this->checkErrorsAnswer($response_xml, $method);
        //Проверка на ошибки заказа             
        $this->checkOrderErrorsAnswer($response_xml,$method);
        return $response_xml;
    }
    
   
    /**
    * Отправка запроса на создание заказа доставки
    * Возвращает false если  есть ошибки, либо номер заказа созданного
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address
    * @return false|integer
    */
    private function requestCreateOrder(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if(!$address) $address = $order->getAddress();
        $catalog_config = \RS\Config\Loader::byModule('catalog');
        
        $currency      = \Catalog\Model\CurrencyApi::getBaseCurrency(); //Базовая валюта
        $date          = date('c',strtotime($order['dateof'])); //Дата заказа
        $delivery      = $order->getDelivery(); //объект доставки
        $delivery_cost = $delivery->getDeliveryCost($order);   
        $payment       = $order->getPayment();
        $user          = $order->getUser();
        
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><createOrderRequest/>");
        $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
        
        $sxml->authentication->apiKey = trim($this->getOption('admin_api')); //Ключ Api 
        
        $sxml->orders->order->orderValue               = $order['totalcost']; // Вся цена заказа
        $sxml->orders->order->orderValueCurrency       = $currency['title']; // Валюта
        $sxml->orders->order->externalDeliveryTypeId   = $delivery['id']; // id Доставки
        $sxml->orders->order->externalDeliveryTypeName = $delivery['title']; // Название доставки
        $sxml->orders->order->externalPaymentTypeId    = $payment['id']; // id Оплаты
        $sxml->orders->order->externalPaymentTypeName  = $payment['title']; // Название оплаты
        $sxml->orders->order->externalCarrierName      = $this->getTitle(); // Название типа доставки
        $sxml->orders->order->externalCarrierId        = $this->getShortName(); // id типа доставки
        $sxml->orders->order->externalCountryId        = $address['country']; // Страна
        $sxml->orders->order->externalBuyerId          = $user['id'] ? $user['id'] : $order['id']; // id пользователя
        $sxml->orders->order->externalOrderId          = $order['order_num']; // id заказа
        $sxml->orders->order->shipmentTemplate         = $this->getOption('template_id',0); // Выбранный шаблон доставки
        $sxml->orders->order->comments                 = $order['comments']; // Комментарий к заказу
        $sxml->orders->order->createdOn                = date('c', strtotime($order['dateof'])); // Дата заказа
        $sxml->orders->order->deliveryPrice            = $delivery_cost; // Стоимость доставки
        $sxml->orders->order->deliveryPriceCurrency    = $currency['title']; // Валюта
        $sxml->orders->order->orderWeight              = $order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_G); // Вес
        
        
        
        //Добавляет по надобности дополнительный XML с опциями
        $this->getAdditionalDeliveryNode($sxml, $order);
        
        //Сведения об адресе
        $sxml->orders->order->deliveryAddress->street            = $address['address']; 
        $sxml->orders->order->deliveryAddress->buildingNumber    = "-"; 
        $sxml->orders->order->deliveryAddress->zipCode           = $address['zipcode']; 
        $sxml->orders->order->deliveryAddress->city              = $address['city']; 
        $sxml->orders->order->deliveryAddress->countryAlpha2Code = strtoupper($this->getLanguageCode()); //Код страны двухзначный 
        $sxml->orders->order->deliveryAddress->firstName         = $user['name'];                       
        $sxml->orders->order->deliveryAddress->lastName          = $user['surname']; 
        if ($user['company_name']){ //Если это компания
           $sxml->orders->order->deliveryAddress->companyName    = $user['company_name']; 
        }
        $sxml->orders->order->deliveryAddress->phone             = $user['phone']; 
        $sxml->orders->order->deliveryAddress->email             = $user['e_mail']; 
        
        //Товары в заказе
        $cart     = $order->getCart();
        $products = $cart->getProductItems();
        $cartdata = $cart->getPriceItemsData();
        
        $i = 0;
        foreach ($products as $n=>$item){
           /**
           * @var \Catalog\Model\Orm\Product
           */
           $product           = $item['product'];
           $barcode           = $product->getBarCode($item['cartitem']['offer']);
           $offer_title       = $product->getOfferTitle($item['cartitem']['offer']);
           $multioffer_titles = $item['cartitem']->getMultiOfferTitles();  
           $unit              = $product->getUnit()->stitle; 
           if ($catalog_config['use_offer_unit']){
               $unit = $item['product']['offers']['items'][$item['cartitem']['offer']]->getUnit()->stitle;
           }
                   
           if ($product['title']==$offer_title){ //Если наименования комплектаций совпадает, то покажем только название товара
               $sxml->orders->order->orderItems->orderItem[$i]->name = $product['title'];
           }else{
               $sxml->orders->order->orderItems->orderItem[$i]->name = $offer_title ? $product['title']." [".$offer_title."]" : $product['title'];
           }
           $sxml->orders->order->orderItems->orderItem[$i]->sku        = $barcode;
           $sxml->orders->order->orderItems->orderItem[$i]->qty        = $item['cartitem']['amount'];
           $sxml->orders->order->orderItems->orderItem[$i]->unit       = $unit;
           $sxml->orders->order->orderItems->orderItem[$i]->weight     = $product->getWeight($item['cartitem']['offer'], \Catalog\Model\Api::WEIGHT_UNIT_G);
           
           $property_width = $product->getPropertyValueById($this->getOption('width'));
           $width          = $property_width ? $property_width : $product->getDefaultProductDimensions('width'); 
           if ($width){ //Если ширина есть
              $sxml->orders->order->orderItems->orderItem[$i]->width = $width; 
           }
           
           $property_height = $product->getPropertyValueById($this->getOption('height'));
           $height          = $property_height ? $property_height : $product->getDefaultProductDimensions('depth'); 
           if ($height){ //Если высота есть
              $sxml->orders->order->orderItems->orderItem[$i]->height = $height; 
           }
           
           $property_length = $product->getPropertyValueById($this->getOption('length'));
           $length          = $property_length ? $property_length : $product->getDefaultProductDimensions('height'); 
           if ($length){ //Если длинна есть
              $sxml->orders->order->orderItems->orderItem[$i]->length = $length;
           }
           
           $sxml->orders->order->orderItems->orderItem[$i]->priceGross = $cartdata['items'][$n]['single_cost']; //Цена товара
           
           //Закомментированно за ненадобностью(Таких параметров пока нет)
           //$sxml->orders->order->orderItems->orderItem[$i]->ean13      = "";
           //$sxml->orders->order->orderItems->orderItem[$i]->ean8       = "";
           //$sxml->orders->order->orderItems->orderItem[$i]->issn       = "";
           $i++;
        }
        
        
        $xml = $this->toFormatedXML($sxml->asXML());  //XML заказа
        
        $method       = 'CreateOrder';
        $response_xml = $this->apiRequest($xml, $method);
        
        //Проверка на общие ошибки
        $this->checkErrorsAnswer($response_xml,$method);
        //Проверка на ошибки заказа
        $this->checkOrderErrorsAnswer($response_xml,$method);
        
        return $response_xml;
    }
    
    /**
    * Отправка запроса подтверждение доставки
    * Возвращает false если  есть ошибки, либо номер заказа созданного
    * 
    * @param string $shipmentEDTN  - единый номер доставки
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - объект адреса
    * @return false|integer
    */
    private function requestConfirmShipment($shipmentEDTN,\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if(!$address) $address = $order->getAddress(); 
        $method = 'confirmShipment';
        
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<confirmShipmentRequest/>");
        $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
        
        $sxml->authentication->apiKey  = trim($this->getOption('admin_api')); //Ключ Api 
        $sxml->shipments->shipmentEDTN = $shipmentEDTN;
     
        $xml = $sxml->asXML();

        $response_xml = $this->apiRequest($xml,$method);
        if (!$response_xml){ //Если запрос не удался
            $this->addError(t('Не удалось отправить запрос в Sheepla. Метод ').$method);
        }
        
        if (!$this->checkErrorsAnswer($response_xml,$method)){
            return false;
        }
        return $response_xml;
    }
    
    
    /**
    * Отправка запроса создание доставки
    * Возвращает false если  есть ошибки, либо номер заказа созданного
    * 
    * @param \Shop\Model\Orm\Order $order
    * @return false|integer
    */
    private function requestCreateShipment(\Shop\Model\Orm\Order $order)
    {
        $method = 'addShipmentToOrder';
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<addShipmentToOrderRequest/>");
        $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
        
        $sxml->authentication->apiKey         = trim($this->getOption('admin_api')); //Ключ Api 
        $sxml->orders->order->externalOrderId = $order['id'];
        
        $xml = $sxml->asXML();

        $response_xml = $this->apiRequest($xml,$method);
        if (!$response_xml){ //Если запрос не удался
            $this->addError(t('Не удалось отправить запрос в Sheepla. Метод ').$method);
        }
        
        if (!$this->checkErrorsAnswer($response_xml,$method)){
            return false;
        }
        return $response_xml;
    }
    
   
    
    /**
    * Отправка запроса - запрос создания реестра доставок
    * Возвращает false если  есть ошибки, либо номер заказа созданного
    * 
    * @param string $shipmentEDTN - номер доставки 
    * @param \Shop\Model\Orm\Order $order - объект доставки
    * @param \Shop\Model\Orm\Address $address - объект адреса
    * @return false|integer
    */
    private function requestCreateManifest($shipmentEDTN)
    {
        $method = 'createManifest';
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<createManifestRequest/>");
        $sxml['xmlns'] = "http://www.sheepla.pl/webapi/1_0";
        
        $sxml->authentication->apiKey  = trim($this->getOption('admin_api')); //Ключ Api 
        
        $template_carrier = $this->getTemplateCarrier(); //Получим сведения о текущем агрегаторе для данного шаблона
        $carrier_id       = $this->getCarrierIdByName($template_carrier['carrierName']);
        
        $sxml->carrierAccountId        = $carrier_id; 
        $sxml->shipments->shipmentEDTN = $shipmentEDTN;

        $xml = $sxml->asXML();
        
        $response_xml = $this->apiRequest($xml,$method);
        if (!$response_xml){ //Если запрос не удался
            $this->addError(t('Не удалось отправить запрос в Sheepla. Метод ').$method);
        }
        
        return $response_xml;
    }
    
   
    /**
    * Возвращает список языков для данного типа доставки
    * 
    * @return array
    */
    private function getLanguages()
    {
        $languages = Sheepla\SheeplaLanguage::$languages;
        asort($languages);
        return $languages;
    }
    
    /**
    * Возвращает дополнительный HTML для публичной части
    * 
    * @return string
    */
    function getAddittionalHtml(\Shop\Model\Orm\Delivery $delivery, \Shop\Model\Orm\Order $order = null)
    {
       $view = new \RS\View\Engine();
       
       $view->assign(array(
          'public_api_js_url'  => self::PUBLIC_API_URL_JS,  
          'public_api_css_url' => self::PUBLIC_API_URL_CSS,   
          'public_api_key'     => trim($this->getOption('public_api')), //Публичный API 
          'cultural_id'        => $this->getOption('language'),      //Язык 
          'template_id'        => $this->getOption('template_id'),      //Язык 
          'order'              => \Shop\Model\Orm\Order::currentOrder(),   //Текущий недоофрмленный заказ
          'delivery'           => $delivery,   //Текущий объект доставки
          'user'               => \RS\Application\Auth::getCurrentUser(),   //Текущий объект доставки
       ) + \RS\Module\Item::getResourceFolders($this)); 
       
       return $view->fetch('%shop%/delivery/sheepla/widget.tpl');
    }
    
    
    /**
    * Очищает сессию от остатков выбранных параметров sheepla
    * 
    * @return void
    */ 
    private function clearSheeplaSession()
    {
       //Удалим все с приставкой sheepla
       foreach ($_SESSION as $key=>$val){
          if (stripos($key, 'sheepla')!==false){
             unset($_SESSION[$key]);
          }  
       } 
    }
    
    
    /**
    * Возвращает дополнительный HTML для административной части с выбором опций доставки в заказе
    * 
    * @param \Shop\Model\Orm\Order $order - заказ доставки
    * @return string
    */
    function getAdminAddittionalHtml(\Shop\Model\Orm\Order $order = null)
    {  
        //Получим данные потоварам
        $products = $order->getCart()->getProductItems();
        
        if (empty($products)){
            $this->addError(t('В заказ не добавлено ни одного товара'));
        }
        
        //Если заказ создаётся в админ. панели, уберём ненужные переменные из сессии, которые возможно были установлены через javascript ранее
        if (isset($order['id']) && ($order['id']<0) && !empty($_SESSION)){
            $this->clearSheeplaSession();
        }

        //Получим цену с параметрами по умолчанию
        $cost     = $this->getDeliveryCostText($order);
        $delivery = $order->getDelivery();
        
        $template_id = $this->getOption('template_id');
        
        $view = new \RS\View\Engine();
        $view->assign(array(
            'errors'      => $this->getErrors(),
            'order'       => $order,
            'cost'        => $cost,
            'extra_info'  => $order->getExtraKeyPair(),
            'delivery'    => $delivery,
            'template_id' => $template_id,
            'sheepla'     => $this,
            'public_api_js_url'  => self::PUBLIC_API_URL_JS,
            'public_api_css_url' => self::PUBLIC_API_URL_CSS,
            'public_api_key'     => $this->getOption('public_api'),    
            'culture_id'         => $this->getOption('language'),    
        )+ \RS\Module\Item::getResourceFolders($this));           
        
        
        return $view->fetch("%shop%/form/delivery/sheepla/sheepla_admin.tpl");
    }
    
    
    
    /**
    * Функция срабатывает после создания заказа
    * 
    * @param \Shop\Model\Orm\Order $order     - объект заказа
    * @param \Shop\Model\Orm\Address $address - Объект адреса
    * @return void
    */
    function onOrderCreate(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
       $extra = $order->getExtraInfo();  
       if (!isset($extra['sheepla_order_id'])){ //Если заказ в sheepla ещё не создан
           //Создадим заказ
           $created_order = $this->requestCreateOrder($order,$address); 
           
           //Если ответа дождались, то запишем номер заказа
           if ($created_order){
              //Запишем id заказа, который мы передали в сессию
               $sheepla_order_id = (string)$created_order->orders->order['orderId'];
               $order->addExtraInfoLine(
                    t('id заказа в sheepla'),
                    '<a href="http://panel.sheepla.ru/mOrder/EditOrder/'.$sheepla_order_id.'" target="_blank">'.$sheepla_order_id.'</a>',
                    array(
                        'orderId' => $sheepla_order_id
                    ),
                    'sheepla_order_id'
               );
               
           }else{ //Иначе
               $order->addExtraInfoLine(
                    'Sheepla',
                    '<a href="http://panel.sheepla.ru/mOrder/All" target="_blank">'.t('Ссылка в список заказов в sheepla').'</a>',
                    'sheepla_order_id'
               );
           }
           $extra = $order->getExtraInfo();      
           
       }  
       
       /* 
       
       !!!Закомментированно за ненадобностью(с перспективой на будущее)!!!
       
       if (!isset($extra['sheepla_sheepment_id'])){ //Если доставка ещё не создана
           //Создадим доставку в sheepla
           $created_shipment = $this->requestCreateShipment($order,$address);   
           
           //Запишем номер доставки, который мы передали в сессию
           $sheepla_sheepment_id = (string)$created_shipment->orders->order['shipmentEDTN'];
           $order->addExtraInfoLine(
                t('id доставки в sheepla'),
                '<a href="http://panel.sheepla.ru/EditShipment/'.$sheepla_sheepment_id.'" target="_blank">'.$sheepla_sheepment_id.'</a>',
                array(
                    'shipmentEDTN' => $sheepla_sheepment_id,
                ),
                'sheepla_sheepment_id'
           );
           $extra = $order->getExtraInfo();             
        }
        
        $shipmentEDTN = $extra['sheepla_sheepment_id']['data']['shipmentEDTN'];
        
        
        //Если доставка ещё не подтверждена, а её надо подтвердить
        if (!isset($extra['sheepla_ctn_id']) && $this->getOption('shipment_confirm',0)){  
           //Подтвердим доставку в sheepla
           $confirm_shipment = $this->requestConfirmShipment($shipmentEDTN,$order,$address);    
           //Запишем номер доставки, который мы передали в сессию
           $sheepla_sheepment_id = (string)$confirm_shipment->shipments->shipment['edtn'];
           $sheepla_ctn_id       = (string)$confirm_shipment->shipments->shipment['ctn'];
           
           $order->addExtraInfoLine(
                t('Номер CTN'),
                '<a href="http://panel.sheepla.ru/mShipmentManagement/mDetailsShipment/'.$shipmentEDTN.'" target="_blank">'.$sheepla_ctn_id.'</a>',
                array(
                    'shipmentEDTN' => $sheepla_sheepment_id,
                    'shipmentCTN'  => $sheepla_ctn_id
                ),
                'sheepla_ctn_id'
           );
        }
        
        //Пробуем создать манифест (реестр доставок)
        if (!isset($extra['sheepla_manifest_id']) && $this->getOption('create_manifest',0)){ //Если манифест ещё не создан
           //Отправим запрос на создание манифеста(Реестра доставок)
           $manifest = $this->requestCreateManifest($shipmentEDTN);
           //Запишем номер доставки, который мы передали в сессию
           $manifest_id      = (string)$manifest->manifestInfo->manifestId;
           $manifest_created = (string)$manifest->manifestInfo->manifestCreated;
           

           
           $order->addExtraInfoLine(
                t('Номер CTN'),
                '<a href="http://panel.sheepla.ru/mShipmentManagement/mDetailsShipment/'.$shipmentEDTN.'" target="_blank">'.$sheepla_ctn_id.'</a>',
                array(
                    'manifestId'      => $manifest_id,
                    'manifestCreated' => $manifest_created
                ),
                'sheepla_ctn_id'
           );
           $extra = $order->getExtraInfo();  
        }       */
        
       //Запишем данные в таблицу, чтобы не вызывать повторного сохранения
       \RS\Orm\Request::make()
                ->update()
                ->from(new \Shop\Model\Orm\Order())
                ->set(array(
                    '_serialized' => serialize($order['extra'])
                ))
                ->where(array(
                    'id' => $order['id']
                ))->exec(); 
    }
    
    /**
    * Присваиваем элемент LogiBox нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuLogiBoxPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruLogiBox->popId   = $value;
        $sxml->orders->order->deliveryOptions->ruLogiBox->popName = $value;
    }
    
    /**
    * Присваиваем элемент IMLogistics нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuIMLogisticsPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruIMLogistics->pickupPointCarrierCode = $value;
    }
    
    /**
    * Присваиваем элемент ShopLogistics нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuShopLogisticsPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruShopLogistics->popId = $value;
    }
 
    
    /**
    * Присваиваем элемент InPost нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuShopLogisticsMetroStation(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruShopLogistics->metroStationId = $value;
    }
    
    /**
    * Присваиваем элемент QiwiPost нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuQiwiPostPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruQiwiPost->popName = $value;
    }
    
    /**
    * Присваиваем элемент Cdek нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuCdekPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruCdek->popName = $value;
    }
    
    /**
    * Присваиваем элемент BoxBerry нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuBoxBerryPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruBoxBerry->popId   = $value;
        $sxml->orders->order->deliveryOptions->ruBoxBerry->popName = $value;
    }
    
    /**
    * Присваиваем элемент PickPoint нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuPickPointPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruPickPoint->pickupPointCarrierCode = $value;
    }
    
    /**
    * Присваиваем элемент PolishPost нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetPpplpocztaPolskav2paczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->plPolishPost->pickupPointCarrierCode = $value;
    }
    
    /**
    * Присваиваем элемент Own Carrier нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetPlownCarrierPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->plOwnCarrier->deliveryPointId = $value;
    }
    
    /**
    * Присваиваем элемент Ruch Polska нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetPlRuchPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->plRuch->popName = $value;
    }
    
    /**
    * Присваиваем элемент XPress Polska нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetPlXpressDeliveryFrameTime(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->plXPress->deliveryTimeFrameId = $value;
    }
    
    /**
    * Присваиваем элемент Grastin нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetRuGrastinPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->ruGrastin->popName = $value;
    }
    
    /**
    * Присваиваем элемент PointPack нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetPlPointPackPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->plPointPack->popName = $value;
    }
    
    /**
    * Присваиваем элемент bgEcont нашему дереву XML 
    * 
    * @param \SimpleXMLElement $sxml - XML доставки
    * @param string $value           - Значение для записи
    */
    private function upSheeplaWidgetBgEcontPaczkomat(&$sxml, $value)
    {
        $sxml->orders->order->deliveryOptions->bgEcont->popName = $value;
    }
    
    /**
    * Возвращает двухсимвольный код языка
    * 
    */
    private function getLanguageCode()
    {
       //Получим язык 
       $language = $this->getOption('language'); 
       
       switch ($language) {
           case 1045: //Польский
                return 'pl';
                break;
           case 1033: //Английский
                return 'en';
                break;
           case 1049: //Русский
           default:
                return 'ru';
                break;
       }
    }
    
    /**
    * Возвращает допольнительные данные в виде массива, со сведениями о почтоматах и доставках
    * 
    * @param \Shop\Model\Orm\Order $order
    */
    private function getExtraDataArray( \Shop\Model\Orm\Order $order )
    {
       $extra = $order->getExtraKeyPair('delivery_extra');
       parse_str(htmlspecialchars_decode($extra['value']), $arr);
       return $arr; 
    }
    
    
    /**
    * Возвращает HTML виджет с краткой информацией заказа для админки
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */
    private function getHtmlShortInfo(\Shop\Model\Orm\Order $order)
    {
       $view = new \RS\View\Engine();
       $view->assign(array(
         'public_api_js_url'  => self::PUBLIC_API_URL_JS,  
         'public_api_css_url' => self::PUBLIC_API_URL_CSS, 
         'api_key' => $this->getOption('admin_api',0),    
         'cultureId' => $this->getOption('language', self::DEFAULT_LANGUAGE_ID),   
         'type' => 'short',
         'title' => t('Краткие сведения заказа'),
         'order' => $order,
         'delivery_type' => $this
       ));                       
       return $view->fetch('%shop%/form/delivery/sheepla/sheepla_get_status.tpl'); 
    }
    
    /**
    * Возвращает HTML виджет с информацией заказа для админки
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */
    private function getHtmlInfo(\Shop\Model\Orm\Order $order)
    {
       $view = new \RS\View\Engine();
       $view->assign(array(
         'public_api_js_url'  => self::PUBLIC_API_URL_JS,  
         'public_api_css_url' => self::PUBLIC_API_URL_CSS, 
         'api_key' => $this->getOption('admin_api',0),    
         'cultureId' => $this->getOption('language',self::DEFAULT_LANGUAGE_ID),   
         'type' => 'full',
         'title' => t('Сведения заказа'),
         'order' => $order,
         'delivery_type' => $this
       ));                       
       return $view->fetch('%shop%/form/delivery/sheepla/sheepla_get_status.tpl'); 
    }
    
    
    /**
    * Возвращает HTML виджет с историей заказа для админки
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */
    private function getHtmlHistory(\Shop\Model\Orm\Order $order)
    {
       $view = new \RS\View\Engine();
       $view->assign(array(
         'public_api_js_url'  => self::PUBLIC_API_URL_JS,  
         'public_api_css_url' => self::PUBLIC_API_URL_CSS, 
         'api_key' => $this->getOption('admin_api',0),    
         'cultureId' => $this->getOption('language',self::DEFAULT_LANGUAGE_ID),   
         'type' => 'history',
         'title' => t('История заказа'),
         'order' => $order,
         'delivery_type' => $this
       ));                  
         
       return $view->fetch('%shop%/form/delivery/sheepla/sheepla_get_status.tpl'); 
    }
    
    
    /**
    * Возвращает HTML виджет со статусом заказа для админки
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */
    private function getHtmlStatus(\Shop\Model\Orm\Order $order)
    {
       $view = new \RS\View\Engine();
       $view->assign(array(
         'public_api_js_url'  => self::PUBLIC_API_URL_JS,  
         'public_api_css_url' => self::PUBLIC_API_URL_CSS, 
         'api_key' => $this->getOption('admin_api',0),    
         'cultureId' => $this->getOption('language',self::DEFAULT_LANGUAGE_ID),   
         'type' => 'standard',
         'title' => t('Статус заказа'),
         'order' => $order,
         'delivery_type' => $this
       ));                       
       return $view->fetch('%shop%/form/delivery/sheepla/sheepla_get_status.tpl'); 
    }
    
    
    /**
    * Действие с запросами к заказу для получения дополнительной информации от доставки
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    */
    function actionOrderQuery(\Shop\Model\Orm\Order $order)
    {
        $url = new \RS\Http\Request();
        $method = $url->request('method',TYPE_STRING,false);
        switch ($method){
            case "getInfo": //Получение статуса заказа
                return $this->getHtmlInfo($order); 
                break;
            case "getShortInfo": //Получение статуса заказа
                return $this->getHtmlShortInfo($order); 
                break;
            case "getHistory": //Получение статуса заказа
                return $this->getHtmlHistory($order); 
                break;
            case "getStatus": //Получение статуса заказа
            default:
                return $this->getHtmlStatus($order);    
                break;
        }
    }
    
    
    /**
    * Возвращает дополнительный HTML для админ части в заказе
    * 
    * @param \Shop\Model\Orm\Order $order - заказ доставки
    * @return string
    */
    function getAdminHTML(\Shop\Model\Orm\Order $order)
    {
        $view = new \RS\View\Engine();
        
        $view->assign(array(
            'order' => $order,
        ));
        
        return $view->fetch("%shop%/form/delivery/sheepla/sheepla_additional_html.tpl");
    }
    
}
