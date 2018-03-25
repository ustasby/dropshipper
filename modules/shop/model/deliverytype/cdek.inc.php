<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

class Cdek extends AbstractType implements 
                                    \Shop\Model\DeliveryType\InterfaceIonicMobile
{
    const 
        API_URL               = "https://integration.cdek.ru/", //Основной URL
        API_URL_CALCULATE     = "http://api.cdek.ru/calculator/calculate_price_by_json.php", //URL для калькуляции доставки
        REQUEST_TIMEOUT       = 20, // 10 сек. таймаут запроса к api
        API_CALCULATE_VERSION = "1.0", //Версия API для подсчёта стоимости доставки
        DEVELOPER_KEY ="522d9ea0ad70744c58fd8d9ffae01fc1";// СДЭК попросил добавить дополнительный атрибут к запросу  28.09.2017
     
    private
        $tariffId = array(),  //Идентификатор тарифа по которому будет произведена доставка 
        $delivery_cost_info = array(), //Стоимость доставки по данному расчётному классу
        $cache_api_requests = array(), // Кэш запросов к серверу рассчета
        $cache_pochtomates = array(), // Кэшированный список ПВЗ
        $cache_city_id = array();     // Кэшированные id городов
        
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('СДЭК');
    }
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Доставка СДЭК <br/><br/>
        <div class="notice-box no-padd">
            <div class="notice-bg">
                Для работы доставки необходимо указывать Вес у товара в граммах.<br/> 
                Укажите Вес по умолчанию в <b>"Веб-сайт" &rarr; "Настройка модулей" &rarr; "Каталог товаров" &rarr; "Вес одного товара по-умолчанию"</b><br/>
                <b>Минимальный вес для расчётов - 100 грамм.</b><br/> 
                У товаров должны быть обязательно указаны -  длинна, ширина и высота в характеристиках.</b>
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
        return t('cdek');
    }
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'default_cash_on_delivery' => new Type\Integer(array(
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => array(1,0),
                'description' => t('Наложенный платёж? (значение по умолчанию)'),
                'hint' => t('Платёж при получении товаров')
            )),
            'secret_login' => new Type\Varchar(array(
                'maxLength' => 150,
                'hint' => t('Используется дополнительно для расчёта стоимости<br/>Выдаётся СДЭК'),
                'description' => t('Логин для доступа к серверу расчётов'),
            )),
            'secret_pass' => new Type\Varchar(array(
                'maxLength' => 150,
                'hint' => t('Используется дополнительно для расчёта стоимости<br/>Выдаётся СДЭК'),
                'description' => t('Пароль для доступа к серверу расчётов'),
            )),
            'day_apply_delivery_to_block' => new Type\Integer(array(
                'description' => t('Прибавлять время на подготовку заказа к максимальному времени доставки'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => array(1,0),
                'hint' => t('Будет отображаено в блоке расчета стоимости доставки и на странице оформления заказа'),
            )),
            'day_apply_delivery' => new Type\Integer(array(
                'maxLength' => 11,
                'default' => 1,
                'description' => t('Количество дней, через сколько будет произведена планируемая отправка заказа'),
            )),
            'city_from_name' => new Type\Varchar(array(
                'maxLength' => 150,
                'description' => t('Название города отправления<br/>Например: Краснодар'),
            )),
            'city_from_zipcode' => new Type\Varchar(array(
                'maxLength' => 11,
                'description' => t('Почтовый индекс города отправителя<br/>Например: 350000'),
            )),
            'tariffTypeCode' => new Type\Integer(array(
                'description' => t('Тариф'),
                'maxLength' => 11,
                'visible' => false,
                'List' => array(array('\Shop\Model\DeliveryType\Cdek\CdekInfo','getAllTariffs'), false),
                'ChangeSizeForList' => false,
                'attr' => array(array(
                    'size' => 16
                ))
            )),
            'tariffTypeList' => new Type\ArrayList(array(
                'description' => t('Список тарифов по приоритету'),
                'maxLength' => 1000,
                'runtime' => false,
                'attr' => array(array(
                    'multiple' => true
                )),
                'template' => '%shop%/form/delivery/cdek/tariff_list.tpl',
                'hint' => t('При расчёте стоимости, если указаный тариф будет не доступен для отправления, то расчёт будет вестись по нижеследующему тарифу указанному в списке'),
                'listFromArray' => array(array())
            )),
            'additional_services' => new Type\ArrayList(array(
                'maxLength' => 1000,
                'default' => 0,
                'attr' => array(array(
                    'size' => 7,
                    'multiple' => true
                )),
                'list' => array(array('\Shop\Model\DeliveryType\Cdek','getAdditionalServices')), 
                'description' => t('Добавлять дополнительные услуги к заказу:<br/>(Необязательно)'),
                'hint' => t('Дополнительные услуги зависят от Ваших условий договора. Обратитесь к менеджеру, за дальнейшими разъяснениями по использованию доп. услуг.')
            )),
            'add_barcode_uniq' => new Type\Integer(array(
                'description' => t('Добавлять уникальное окончание к артикулу?'),
                'default' => 0,
                'maxLength' => 1,
                'CheckboxView' => array(1,0),
                'hint' => t('Нужно только если у Вас артикулы совпадают у всех комплектаций товара, т.к. для СДЭКа это кретичный момент.'),
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
            'write_log' => new Type\Integer(array(
                'description' => t('Вести лог запросов?'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => array(1,0),
                'hint' => t("Лог будет сохранён в папке storage/logs на сервере"),
            )),
            'decrease_declared_cost' => new Type\Integer(array(
                'description' => t('Снижать объявленную стоимость товаров до 0.1 копейки'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => array(1,0),
                'hint' => t("Влияет на размер страховки"),
            )),
            'delivery_recipient_vat_rate' => new Type\Varchar(array(
                'description' => t('Ставка НДС за доставку'),
                'default' => '',
                'listFromArray' => array(array(
                    '' => t('- Не указано -'),
                    'VATX' => 'Без НДС',
                    'VAT0' => '0%',
                    'VAT10' => '10%',
                    'VAT18' => '18%'
                )),
            )),
            'min_product_weight' => new Type\Integer(array(
                'description' => t('Минимальный вес одного товара (г)'),
                'hint' => t("Если указан - вес товаров с меньшим весом будет автоматически увеличен"),
                'default' => 0,
            )),
        ) );
        
        return new \RS\Orm\FormObject($properties);
    }


    /**
     * Запрос к серверу рассчета стоимости доставки. Ответ сервера кешируется
     *
     * @param string $script - скрипт
     * @param array $params  - массив параметров
     * @param string $method - POST или GET
     * @return mixed
     */
    private function apiRequest($script, $params = array(), $method="POST")
    {
        if (!empty($params)){ //Если параметры переданы
            ksort($params);
        }
        $cache_key = md5(serialize($params).$method);
        if ($this->getOption('write_log')){
            $this->writeToLog(t('Параметры запроса'), array(
                'script' => $script,
                'params' => $params,
                'method' => $method,
            ));
        }

        if(!isset($this->cache_api_requests[$cache_key])){
            
            $requst_array = array(
                'http' => array(
                    'ignore_errors' => true, //Игнорируем ошибки(статусы ошибок) в заголовках, т.к. может быть 500 ошибка, но контент есть
                    'method'=>$method,
                    'timeout' => self::REQUEST_TIMEOUT
                )
            );
            
            if (stripos($method,'post')!==false){
                $requst_array['http']['content'] = http_build_query($params);
                
                $ctx = stream_context_create($requst_array);
                $url = $this->getApiHost().$script;
            }else{
                $url_params = !empty($params) ? '?'.http_build_query($params) : "";
                $url = $this->getApiHost().$script.$url_params;
                $ctx = stream_context_create($requst_array);
            }
            $response   = @file_get_contents($url, null, $ctx);
            $this->cache_api_requests[$cache_key] = $response;
        }
        if ($this->getOption('write_log')){
            $this->writeToLog(t('Ответ на запрос'), $this->cache_api_requests[$cache_key]);
        }
        return $this->cache_api_requests[$cache_key];
    }
    
    /**
    * Получает хост для api
    */
    private function getApiHost(){
        return self::API_URL;
    }

    /**
     * Получение кода защиты для СДЭК запросов
     *
     * @param string $format - формат даты отправления
     * @return false|string
     */
    private function getDateExecute( $format = "Y-m-d" )
    {
       $time = time() + ((int)$this->getOption("day_apply_delivery", 0) * 60 * 60 * 24); 
       return date($format,$time);
    }

    /**
     * Получает секретный код основанный на MD5 и текущей дате
     *
     * @param string $date_execute - дата для ключа
     * @return string
     */
    private function getSecure($date_execute)
    {
        return md5($date_execute."&".trim($this->getOption('secret_pass', null))); 
    }


    /**
     * Отправляет запрос на получение почтоматов для забора товара пользователем
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Address $address - объект адреса
     * @param array $tariff - массив сведений по тарифу
     * @return array
     */
    private function requestPochtomat(\Shop\Model\Orm\Order $order, $tariff, \Shop\Model\Orm\Address $address = null)
    {
        if (!$address){
            $address = $order->getAddress();
        }
        
        if (empty($this->cache_pochtomates)) {
            // Попробуем найти город в базе СДЭК
            $city_id = $this->getCityIdByName($address);
            $params = ($city_id) ? array('cityid' => $city_id) : array();
            
            $response = $this->apiRequest('pvzlist.php', $params, 'GET');
            
            $pochtomates = array();
            if ($response){
                $xml = @simplexml_load_string($response);
                if (isset($xml->Pvz)){
                    foreach($xml->Pvz as $item){
                        if ($city_id || (!$city_id && strcasecmp((string)$item['ownerCode'], $tariff['ownerCode']) == 0 && in_array($address['city'], explode(', ', (string)$item['City'])) )) {
                            $pochtomates[] = $item;
                        }
                    }
                }
            }
            // удаляем из адреса ПВЗ ковычки чтобы избежать проблем с json
            foreach ($pochtomates as $key=>$item) {
                $pochtomates[$key]['Address'] = str_replace('"', ' ', $item['Address']);
                $pochtomates[$key]['Note'] = str_replace('"', ' ', $item['Note']);
            }
            $this->cache_pochtomates = $pochtomates;
        }
        return $this->cache_pochtomates;
    }


    /**
     * Запрос на информацию по заказу
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return \SimpleXMLElement
     * @throws \RS\Exception
     */
    private function requestGetInfo(\Shop\Model\Orm\Order $order)
    {
       $extra_info = $order->getExtraInfo(); 
       
       if (!isset($extra_info['cdek_order_id']['data']['orderId'])){
            throw new \RS\Exception(t('[Ошибка] Не удалось получить сведения о заказе СДЭК'));
       }
       
       //Подготовим XML
       $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><InfoRequest/>");
       
       $data_create = date('Y-m-d');
       $sxml['Date']       = $data_create;
       $sxml['Account']    = trim($this->getOption('secret_login',null)); //Id аккаунта
       $sxml['Secure']     = $this->getSecure($data_create); //Генерируемый секретный ключ
       
       $sxml->ChangePeriod['DateBeg'] = date('Y-m-d', strtotime($order['dateof'])); //Дата начала запрашиваемого периода
       
       //Номер отправления
       $sxml->Order['DispatchNumber'] = $extra_info['cdek_order_id']['data']['orderId']; 
       
       
       $xml = $sxml->asXML(); //XML заказа
    
       try{
           return @simplexml_load_string($this->apiRequest("info_report.php",array(
               'xml_request' => $xml
           ))); 
       }catch(\Exception $ex){
           throw new \RS\Exception($ex->getMessage().t("<br/> Файл:%0<br/> Строка:%1", array($ex->getFile(), $ex->getLine())),$ex->getCode()); 
       }
    }


    /**
     * Запрос вызова курьера
     *
     * @param array $call - массив со сведениями об отправке
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return \SimpleXMLElement
     * @throws \RS\Exception
     */
    private function requestGetCallCourier($call, \Shop\Model\Orm\Order $order)
    {
        $extra_info = $order->getExtraInfo();
        
        if (!isset($extra_info['cdek_order_id']['data']['orderId'])){
            throw new \RS\Exception(t('[Ошибка] Не удалось получить сведения о заказе СДЭК'));
        }
        
        $adress = $order->getAddress();
        
        //Настройки текущего сайта
        $site_config = \RS\Config\Loader::getSiteConfig();
        
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><CallCourier/>");
        $data_create       = date('Y-m-d');
        $sxml['Date']      = $data_create;
        $sxml['Account']   = trim($this->getOption('secret_login',null)); //Id аккаунта
        $sxml['Secure']    = $this->getSecure($data_create); //Генерируемый секретный ключ
        $sxml['CallCount'] = 1; //Общее количество заявок для вызова курьера в документе
        
        //Сведения об узнаваемом заказе
        $sxml->Call['Date']    = date('Y-m-d',strtotime($call['Date']));
        $sxml->Call['TimeBeg'] = $call['TimeBeg'].":00";
        $sxml->Call['TimeEnd'] = $call['TimeEnd'].":00";
        
        //Если указано время обеда
        if (isset($call['use_lunch']) && $call['use_lunch']){
           $sxml->Call['LunchBeg'] = $call['LunchBeg'].":00";
           $sxml->Call['LunchEnd'] = $call['LunchEnd'].":00"; 
        }
        
        $sxml->Call['SendCityCode'] = $call['SendCityCode'];
        
        //Укажем телефон для вызова
        if (isset($call['use_admin']) && !$call['use_admin']){ //Если свой телефон
            $sxml->Call['SendPhone'] = $call['SendPhone'];
        }else{ //Если телефон администратора
            $sxml->Call['SendPhone'] = $site_config['admin_phone'];
        }
        
        $products = $order->getCart()->getProductItems();
        $order_weight = 0;
        $min_product_weight = $this->getOption('min_product_weight');
        foreach ($products as $n=>$item) {
            $product_weight = $item['product']->getWeight($item['cartitem']['offer'], \Catalog\Model\Api::WEIGHT_UNIT_G);
            $correct_weight = ($product_weight < $min_product_weight) ? $min_product_weight : $product_weight;
            $order_weight += $correct_weight * $item['cartitem']['amount'];
        }
        $sxml->Call['SenderName']   = $call['SenderName'] ? $call['SenderName'] : $site_config['firm_name'];
        $sxml->Call['Weight']       = $order_weight;
        $sxml->Call['Comment']      = $call['Comment'];
        
        //Адрес
        $sxml->Call->Address['Street'] = $adress['address'];
        $sxml->Call->Address['House']  = "-";
        $sxml->Call->Address['Flat']   = "-";
        
        $xml = $sxml->asXML(); //XML заказа

        try{
           return @simplexml_load_string($this->apiRequest("call_courier.php",array(
               'xml_request' => $xml
           ))); 
        }catch(\Exception $ex){
           throw new \RS\Exception($ex->getMessage().t("<br/> Файл:%0<br/> Строка:%1", array($ex->getFile(), $ex->getLine())),$ex->getCode()); 
        }
    }

    /**
     * Запрос статусов заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return \SimpleXMLElement
     * @throws \RS\Exception
     */
    private function requestOrderStatus(\Shop\Model\Orm\Order $order)
    {
        $extra_info = $order->getExtraInfo();

        if (!isset($extra_info['cdek_order_id']['data']['orderId'])){
            throw new \RS\Exception(t('[Ошибка] Не удалось получить сведения о заказе СДЭК'));
        }
        
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><StatusReport/>");
        $data_create = date('Y-m-d');
        $sxml['Date']        = $data_create;
        $sxml['Account']     = trim($this->getOption('secret_login',null)); //Id аккаунта
        $sxml['Secure']      = $this->getSecure($data_create); //Генерируемый секретный ключ
        $sxml['ShowHistory'] = 1; //Показ истории заказа
        
        //Сведения об узнаваемом заказе
        $sxml->Order['DispatchNumber'] = $extra_info['cdek_order_id']['data']['orderId'];
        
        $xml = $sxml->asXML(); //XML заказа
        
        try{
           return @simplexml_load_string($this->apiRequest("status_report_h.php",array(
               'xml_request' => $xml
           ))); 
        }catch(\Exception $ex){
           throw new \RS\Exception($ex->getMessage().t("<br/> Файл:%0<br/> Строка:%1", array($ex->getFile(), $ex->getLine())),$ex->getCode()); 
        }
    }

    /**
     * Запрос на удаление заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return \SimpleXMLElement
     * @throws \RS\Exception
     */
    private function requestDeleteOrder(\Shop\Model\Orm\Order $order)
    {
       $extra_info = $order->getExtraInfo();
       
       //Подготовим XML
       $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><DeleteRequest/>");
       
       $data_create        = date('Y-m-d');
       $sxml['Date']       = $data_create;
       $sxml['Account']    = trim($this->getOption('secret_login',null)); //Id аккаунта
       $sxml['Number']     = (isset($extra_info['cdek_act_number']['data']['actNumber']) && !empty($extra_info['cdek_act_number']['data']['actNumber'])) ? $extra_info['cdek_act_number']['data']['actNumber'] : 1; //Номер Акта
       $sxml['Secure']     = $this->getSecure($data_create); //Генерируемый секретный ключ
       $sxml['OrderCount'] = 1; //Общее количество заказов в xml
       
       
       //Номер отправления
       $sxml->Order['Number'] = $order['order_num']; 
       
       $xml = $sxml->asXML(); //XML заказа
       
       try{
           return @simplexml_load_string($this->apiRequest("delete_orders.php",array(
               'xml_request' => $xml
           ))); 
       }catch(\Exception $ex){
           throw new \RS\Exception($ex->getMessage().t("<br/> Файл:%0<br/> Строка:%1", array($ex->getFile(), $ex->getLine())),$ex->getCode()); 
       }
    }

    /**
     * Отправляет запрос на создание заказа в системе СДЭК
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Address $address - объект адреса
     * @return \SimpleXMLElement
     * @throws \RS\Exception
     */
    private function requestCreateOrder(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if (!$address){
            $address = $order->getAddress();
        }
        
        //Настройки текущего сайта
        $site_config = \RS\Config\Loader::getSiteConfig();
        
        //Доп. данные указанные в доставке
        $extra_info = $order->getExtraKeyPair('delivery_extra');
        $extra_ti = $order->getExtraKeyPair('tariffId');        
        if (isset($extra_info['value'])) {
            $extra_info = json_decode(htmlspecialchars_decode($extra_info['value']), true);
        } else {
            $extra_info = array();
        }
        
        $cash_on_delivery = 0;
        if (!empty($order['payment'])) {
            $payment = $order->getPayment()->getTypeObject();
            if (!$payment->canOnlinePay() && !in_array($payment->getShortName(), array('bill', 'formpd4'))) {
                $cash_on_delivery = 1;
            }
        } else {
            $cash_on_delivery = $this->getOption('default_cash_on_delivery');
        }
        $decrease_declared_cost = $this->getOption('decrease_declared_cost');
        
        //Подготовим XML
        $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><DeliveryRequest/>");
        $sxml['Number'] = $order['order_num']; //Номер заказа

        if ($this->getOption('day_apply_delivery')){
            $data_create        = date('Y-m-d',strtotime("+".$this->getOption('day_apply_delivery')." days"));
        }
        else{
            $data_create        = date('Y-m-d');
        }

        $sxml['Date']       = $data_create; //Дата создания заказа
        $sxml['Account']    = trim($this->getOption('secret_login',null)); //Id аккаунта
        $sxml['Secure']     = $this->getSecure($data_create); //Генерируемый секретный ключ
        $sxml['OrderCount'] = 1; //Сколько заказов будет отправлено
        $sxml['DeveloperKey'] = self::DEVELOPER_KEY;
        

        $delivery = $order->getDelivery(); //Сведения о доставке заказа
        $user     = $order->getUser(); //Получим пользователя 
        
        $sxml->Order['Number'] = $order['order_num'];    //Номер заказа
        if (isset($extra_info['cityCode']) || isset($extra_info['CityCode'])) { //код города получателя если выбран какой-то пункт выдачи или если город найдётся в базе СДЭК
            $sxml->Order['RecCityCode'] = isset($extra_info['cityCode']) ? $extra_info['cityCode'] : $extra_info['CityCode']; //Для совместимости со старой версией проверяем
        } elseif ($recCityCode = $this->getCityIdByName($address)) {
            $sxml->Order['RecCityCode'] = $recCityCode;
        } else {
            $sxml->Order['RecCityPostCode'] = $address['zipcode']; // иначе - почтовый индекс города получателя
        }
        
        $defaultTariff_id = $this->getSelectedFirstTariffId();
        $sxml->Order['SendCityPostCode']      = $this->getOption('city_from_zipcode');     //Индекс города отправителя
        $sxml->Order['RecipientName']         = !empty($address['contact_person']) ? $address['contact_person'] : $user->getFio(); //ФИО получателя
        $sxml->Order['RecipientEmail']        = $user['e_mail']; //E-mail получателя
        $sxml->Order['Phone']                 = $user['phone'];  //Телефон получателя
        $sxml->Order['TariffTypeCode']        = (!empty($extra_info['tariffId']) && in_array($extra_info['tariffId'], $this->getOption('tariffTypeList'))) ? $extra_info['tariffId'] : $extra_ti; //Тип тарифа, по которому будет доставка
        $delivery_recipient_cost = $cash_on_delivery ? $delivery->getDeliveryCost($order, null, false) : 0; //Цена за доставку, если наложенный платёж
        $sxml->Order['DeliveryRecipientCost'] = $delivery_recipient_cost;
        // При наложенном платеже - добавляем сведения о налоге
        if (!empty($delivery_recipient_cost) && $this->getOption('delivery_recipient_vat_rate')) {
            $sxml->Order['DeliveryRecipientVATRate'] = $this->getOption('delivery_recipient_vat_rate');
            $sxml->Order['DeliveryRecipientVATSum']  = round($delivery_recipient_cost * $this->getTaxRateById($this->getOption('delivery_recipient_vat_rate')), 2);
            $sxml->Order['RecipientCurrency']        = $order['currency']; //Валюта доставки
        }
        $sxml->Order['Comment']    = $order['comments']; //Комментарий заказа
        $sxml->Order['SellerName'] = $site_config['firm_name']; //Имя фирмы отправителя
        /**
        * @var \Catalog\Model\Orm\Currency
        */
        $default_currency = \Catalog\Model\CurrencyApi::getBaseCurrency();
        $sxml->Order['ItemsCurrency']    = $default_currency['title']; //Код валюты в которой был составлен заказ
        
        //Адрес куда будет доставлено
        $sxml->Order->Address['Street'] = $address['address'];
        $sxml->Order->Address['House']  = "-";
        $sxml->Order->Address['Flat']   = "-";
        $cdek_info = new \Shop\Model\DeliveryType\Cdek\CdekInfo();
        $tariffs = $cdek_info->getAllTariffsWithInfo();
        $extra_ti = $order->getExtraKeyPair('tariffId');
        if (isset($tariffs[$extra_ti])) {
            $tariff = $tariffs[$extra_ti];
            if ($tariff && in_array($tariff['regim_id'], array(2, 4))) { //Если нужны почтоматы
                if (isset($extra_info['code'])){ //Если есть место забора товара
                    $sxml->Order->Address['PvzCode'] = $extra_info['code'];
                    $order->addExtraInfoLine(t('Выбран пункт забора'), $extra_info['addressInfo']);
                }
            }
        }
        
        //Упаковка с товарами
        $products = $order->getCart()->getProductItems();
        $cartdata = $order->getCart()->getPriceItemsData();
        $sxml->Order->Package['Number']  = $order['order_num'];
        $sxml->Order->Package['BarCode'] = $order['order_num'];
        
        $order_weight = 0;
        $min_product_weight = $this->getOption('min_product_weight'); 
        
        $i=0;
        foreach ($products as $n=>$item) {
            /**
            * @var \Catalog\Model\Orm\Product
            */
            $product     = $item['product']; 
            $barcode     = $product->getBarCode($item['cartitem']['offer']);
            $offer_title = $product->getOfferTitle($item['cartitem']['offer']);

            if ($this->getOption('add_barcode_uniq', 0) && $product->isOffersUse() && $item['cartitem']['offer']){ //Если нужно уникализировать артикул
                $barcode = $barcode."-".$product['offers']['items'][(int) $item['cartitem']['offer']]['id'];
            }

            $sxml->Order->Package->Item[$i]['WareKey'] = $barcode; //Артикул
            $sxml->Order->Package->Item[$i]['Cost']    = $decrease_declared_cost ? '0.001' : $cartdata['items'][$n]['single_cost'] - ($cartdata['items'][$n]['discount'] / $item['cartitem']['amount']); //Цена товара
            $item_payment = $cash_on_delivery ? $cartdata['items'][$n]['single_cost'] - ($cartdata['items'][$n]['discount'] / $item['cartitem']['amount']) : 0; //Оплата при получении, только есть указано в настройках иначе 0
            $sxml->Order->Package->Item[$i]['Payment'] = $item_payment;
            if (!empty($item_payment)) { // При наложенном платеже - добавляем сведения о налоге
                $tax_id = $this->getRightTaxForProduct($order, $product);
                $sxml->Order->Package->Item[$i]['PaymentVATRate'] = $tax_id;
                $sxml->Order->Package->Item[$i]['PaymentVATSum'] = round($item_payment * $this->getTaxRateById($tax_id), 2);
            }
            
            $product_weight = $product->getWeight($item['cartitem']['offer'], \Catalog\Model\Api::WEIGHT_UNIT_G);
            $sxml->Order->Package->Item[$i]['Weight'] = ($product_weight < $min_product_weight) ? $min_product_weight : $product_weight;
            $sxml->Order->Package->Item[$i]['Amount'] = $item['cartitem']['amount'];
            $order_weight += $sxml->Order->Package->Item[$i]['Weight'] * $sxml->Order->Package->Item[$i]['Amount'];
            
            if ($product['title']==$offer_title){ //Если наименования комплектаций совпадает, то покажем только название товара
                $sxml->Order->Package->Item[$i]['Comment'] = $product['title']; 
            }else{
                $sxml->Order->Package->Item[$i]['Comment'] = $offer_title ? $product['title']." [".$offer_title."]" : $product['title'];    
            }
            
            $i++;
        }
        $sxml->Order->Package['Weight']  = $order_weight;
        
        //Добавление дополнительных услуг
        $additional_services = $this->getOption('additional_services',null);
        if (!empty($additional_services)){
           $i=0; 
           foreach($additional_services as $service){
              $sxml->Order->AddService[$i]['ServiceCode'] = $service;
              $i++;
           } 
        }
        
        $xml = $this->toFormatedXML($sxml->asXML()); //XML заказа
        
        try{
           return @simplexml_load_string($this->apiRequest("new_orders.php",array(
               'xml_request' => $xml
           ))); 
        }catch(\Exception $ex){
           throw new \RS\Exception($ex->getMessage().t("<br/> Файл:%0<br/> Строка:%1", array($ex->getFile(), $ex->getLine())),$ex->getCode()); 
        }
        
    }


    /**
     * Запрашивает информацию о доставке
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Address $address - объект адреса доставки
     * @return mixed
     * @throws \RS\Exception
     */
    private function requestDeliveryInfo(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if (!$address){
            $address = $order->getAddress();
        }
        
        //Подготавливаем заголовки
        $request_array = array(
            'http' => array(
                'method' => "POST",
                'header' => "Content-Type: application/json\r\n",
                'timeout' => self::REQUEST_TIMEOUT
            )
        );
       
        //Параметры
        $query_info = array(
            'version'              => self::API_CALCULATE_VERSION,
            'senderCityPostCode'   => $this->getOption('city_from_zipcode'), //Zip код отправителя
        );
        if ($receiverCityId = $this->getCityIdByName($address)) {
            $query_info['receiverCityId'] = $receiverCityId;
        } else {
            $query_info['receiverCityPostCode'] = $address['zipcode'];
        }
        
        //Код тарифа
        $tariffsList = $this->getOption('tariffTypeList');
        $query_info['tariffId']   = null;
        $query_info['tariffList'] = array();
        if (!empty($tariffsList)){
//            $query_info['tariffId'] = $tariffsList[0];  // id тарифа, не нужен, если есть tariffsList
            foreach($tariffsList as $priority=>$tariff){
               $arr[] = array(
                 'priority' => $priority,
                 'id' => $tariff,
               );
            }
            $query_info['tariffList'] = $arr;
        }else{
            $this->addError(t('Не заданы тарифы для доставок.'));
        }
        
        
       
        //Секретный логин и пароль, если они указаны
        $secret_login = trim($this->getOption('secret_login',false));
        $secret_pass  = trim($this->getOption('secret_pass',false));
        if ($secret_login && $secret_pass){
            $query_info['authLogin']   = $secret_login; 
            $date                      = $this->getDateExecute();
            $query_info['secure']      = $this->getSecure($date);
            $query_info['dateExecute'] = $date;
        }
   
        //Переберём товары
        $items = $order->getCart()->getProductItems();
        $product_arr = array();
        $min_product_weight = $this->getOption('min_product_weight');
        foreach ($items as $key=>$item){
            /** 
            * @var \Catalog\Model\Orm\Product
            */
            $product  = $item['product'];
            $cartitem = $item['cartitem'];
            $product->fillProperty();

            //Вес
            $product_weight = $product->getWeight($item['cartitem']['offer'], \Catalog\Model\Api::WEIGHT_UNIT_G);
            $correct_weight = ($product_weight < $min_product_weight) ? $min_product_weight : $product_weight;
            $product_arr['weight'] = $correct_weight / 1000;

            //Длинна
            $property_length = $product->getPropertyValueById($this->getOption('length', 0));
            $length          = $property_length ? $property_length : $product->getDefaultProductDimensions('height');
            if ($length){
              $product_arr['length'] = $length;
            }else{
              $this->addError(t('У товара с артикулом %0 не указана характеристика длинны.', array($product['barcode']))); 
            }

            //Ширина
            $property_width = $product->getPropertyValueById($this->getOption('width', 0));
            $width          = $property_width ? $property_width : $product->getDefaultProductDimensions('width');
            if ($width){
              $product_arr['width'] = $width;
            }else{
              $this->addError(t('У товара с артикулом %0 не указана характеристика ширины.', array($product['barcode']))); 
            }

            //Высота
            $property_height = $product->getPropertyValueById($this->getOption('height', 0));
            $height          = $property_height ? $property_height : $product->getDefaultProductDimensions('depth');
            if ($height){
              $product_arr['height'] = $height;
            }else{
              $this->addError(t('У товара с артикулом %0 не указана характеристика высоты.', array($product['barcode']))); 
            }

            for ($i = 0; $i < $cartitem['amount']; $i++) {
                $query_info['goods'][] = $product_arr;
            }
        }
        
        $request_array['http']['content'] = json_encode($query_info);
        $ctx = stream_context_create($request_array);
        $url = self::API_URL_CALCULATE;
        
        //Кэшируем запрос
        $cache_key = md5($order['order_num'].http_build_query($query_info));
        if(!isset($this->cache_api_requests[$cache_key])){
            $answer = @file_get_contents($url, null, $ctx);
            
            //Логируем
            if (!$order['fake'] && $this->getOption('write_log')) {
                $this->writeToLog(t('Запрос на калькуляцию'), $request_array);
                $this->writeToLog(t('Ответ от калькуляции'), $answer);
            }
            
            if (empty($answer)){
                $this->addError(t('Повторите попытку позже.'));
            }
            
            $response   = json_decode($answer, true);
            if (isset($response['error'])){
               foreach ($response['error'] as $error){
                  $this->addError($error['code']." ".$error['text']); 
               } 
            }else{
               $this->setTariffId($response['result']['tariffId']); 
            }

            $cdek_info = new \Shop\Model\DeliveryType\Cdek\CdekInfo();
            $tariffs = $cdek_info->getAllTariffsWithInfo();
            if (isset($response['result'])){
                $tariff = $tariffs[$response['result']['tariffId']];
                if ($tariff && in_array($tariff['regim_id'], array(2, 4))){ //Если нужны почтоматы
                    $pochtomates = $this->requestPochtomat($order, $tariff);

                    if (empty($pochtomates)) {
                        $this->addError(t('В указанном населённом пункте нет пунктов самовывоза'));
                    }
                }
            }

            
            $this->cache_api_requests[$cache_key] = $response;
        }

        return $this->cache_api_requests[$cache_key];
    }


    /**
     * Получает валюту по имени этой волюты пришедшей из СДЭК
     *
     * @param string $name - сокращённое название валюты из СДЭК
     * @return mixed
     * @throws \RS\Orm\Exception
     */
    private function getCurrencyByName($name)
    {
        //Подгружим валюты системы
        $currencies = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Currency())
                ->where(array(
                    'public' => 1
                ))
                ->orderby('`default` DESC')
                ->objects(null,'title');
        
        if (isset($currencies[$name])){
            return $currencies[$name];
        }else{
            foreach($currencies as $currency){
                if ($currency['default']){
                    return $currency;
                }
            }
        }
    }

    /**
     * Добавление админского комментария в базу к заказу
     *
     * @param integer $order_id - id заказа
     * @param string $text - текст для добавления
     * @throws \RS\Db\Exception
     */
    private function addToOrderAdminComment($order_id, $text)
    {
        \RS\Orm\Request::make()
            ->from(new \Shop\Model\Orm\Order())
            ->set(array(
                'admin_comments' => $text
            ))
            ->where(array(
                'id' => $order_id
            ))
            ->update()
            ->exec(); 
    }

    /**
     * Добавляет ошибки в комментарий админа в заказе через ORM запрос
     *
     * @param string $action - действие русскими словами в родительном падеже
     * @param integer $order_id - id заказа
     * @param array $errors - массив ошибок из ответного XML
     * @throws \RS\Db\Exception
     */
    private function addErrorsToOrderAdminComment($action, $order_id, $errors)
    {
       if($action === NULL){
           $action = t("создание заказа");
       }
       $text = "";
       
       foreach ($errors as $error){
          $str = t("СДЭК ошибка ").$action.": ";
          if (isset($error['ErrorCode'])){
            $str .= "[".$error['ErrorCode']."]";  
          } 
          $text .= $str." ".$error['Msg']."\n"; 
       }
       $this->addToOrderAdminComment($order_id,$text);
    }

    /**
     * Добавляет строки в комментарий админа в заказе через ORM запрос
     *
     * @param integer $order_id - id заказа
     * @param array $strings - массив строк из ответного XML
     * @throws \RS\Db\Exception
     */
    private function addStringsToOrderAdminComment($order_id, $strings)
    {
       $text = ""; 
       
       foreach ($strings as $string){
          $str = t("СДЭК: ");
          if (isset($string['DispatchNumber'])){
            $str .= t("[Код - ").$string['DispatchNumber']."]";  
          } 
          $text .= $str." ".$string['Msg']."\n"; 
       }
       $this->addToOrderAdminComment($order_id,$text);
    }

    /**
     * Возвращает HTML виджет временем прозвона покупателя и отправляет запрос на вызов
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string
     * @throws \Exception
     * @throws \RS\Exception
     * @throws \SmartyException
     */
    private function cdekGetCallCourierHTML(\Shop\Model\Orm\Order $order)
    {  
       $view = new \RS\View\Engine();
       $request = new \RS\Http\Request();
       
       $cdekInfo = new Cdek\CDEKInfo();
       
       if ($request->isPost()){
          $call = $request->request('call',TYPE_ARRAY,false); //Массив со информациями о вызове
          $responce = $this->requestGetCallCourier($call, $order);
          if (isset($responce->CallCourier['ErrorCode'])){
              $this->addError(t('Не удалось отправить запрос на вызов курьера.<br/> ').$responce->CallCourier['Msg']);
          }else{
              $view->assign(array(
                'success' => t('Сделан вызов курьера на %0.<br/> 
                Ожидание курьера с %1:00 по %2:00',
                array($call['Date'], $call['TimeBeg'], $call['TimeEnd']))
              )); 
          }
       }
       
       $view->assign(array(
         'title' => t('Вызов курьера для забора товара СДЭКом'),
         'current_date' => date('Y-m-d',time()+ 24 * 60 * 60),
         'time_range' => range(1,24),
         'time_default_start' => 10,
         'time_default_end' => 13,
         'order' => $order,
         'delivery_type' => $this,
         'current_city' => $this->getOption('city_from_name',''),
         'regions' => $cdekInfo->getAllCities(),
         'errors' => $this->getErrors()
       ));                       
       return $view->fetch('%shop%/form/delivery/cdek/cdek_call_courier.tpl'); 
    }

    /**
     * Пересоздаёт заказ в СДЭК
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string
     * @throws \Exception
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     * @throws \SmartyException
     */
    private function cdekReCreateOrder(\Shop\Model\Orm\Order $order)
    {
        $this->cdekDeleteOrder($order);
        
        if (isset($order['extra']['extrainfo']['cdek_order_id'])){ 
            $order_extra = $order['extra'];
            unset($order_extra['extrainfo']['cdek_order_id']); 
            $order['extra'] = $order_extra;
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
        
        //Пересоздадим заказ 
        $this->onOrderCreate($order, $order->getAddress());
        
        return t("Заказ успешно пересоздан.");
    }

    /**
     * Возвращает HTML виджет с печатной формой
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string
     * @throws \Exception
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     * @throws \SmartyException
     */
    private function cdekDeleteOrder(\Shop\Model\Orm\Order $order)
    {
        $extra_info = $order->getExtraInfo(); 

        if (!isset($extra_info['cdek_order_id']['data']['orderId'])){
            return t('[Ошибка] Не удалось получить сведения о заказе СДЭК');
        }

        //Отправим запрос
        $result = $this->requestDeleteOrder($order); 
        
        //Если старая версия и номер акта небыл указан, добавим его из сообщения 
        if (!isset($extra_info['cdek_act_number']['data']['actNumber']) || (isset($extra_info['cdek_act_number']['data']['actNumber']) && empty($extra_info['cdek_act_number']['data']['actNumber']))){
            $act_number = (string)$result->DeleteRequest[0]['Number'];
            
            $order->addExtraInfoLine(
                t('Номер акта СДЭК'),
                '',
                array(
                    'actNumber' => $act_number
                ),
                'cdek_act_number'
            );
            $extra = $order->getExtraInfo();
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
            
            if (!$this->is_cdekDeleteOrder_action) {
                $this->is_cdekDeleteOrder_action = true;
                return $this->cdekDeleteOrder($order);
            }
        }

        if (isset($result->DeleteRequest)){
            foreach ($result->DeleteRequest as $deleteRequest){
                $status[] = (string)$deleteRequest['Msg']; 
            }    
        }

        $view = new \RS\View\Engine();
        $view->assign(array(
            'status' => $status   
        ));

        return $view->fetch('%shop%/form/delivery/cdek/cdek_delete_order.tpl'); 
    }

    /**
     * Возвращает HTML виджет с печатной формой
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    private function cdekGetPrintDocument(\Shop\Model\Orm\Order $order)
    {
       $extra_info = $order->getExtraInfo(); 
       
       if (!isset($extra_info['cdek_order_id']['data']['orderId'])){
           return t('[Ошибка] Не удалось получить сведения о заказе СДЭК');
       }
       
       
       $view = new \RS\View\Engine();
       
       //Подготовим XML
       $sxml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><OrdersPrint/>");
       
       $data_create        = date('Y-m-d');
       $sxml['Date']       = $data_create;
       $sxml['Account']    = trim($this->getOption('secret_login',null)); //Id аккаунта
       $sxml['Secure']     = $this->getSecure($data_create); //Генерируемый секретный ключ
       $sxml['OrderCount'] = 1; //Общее количество заказов в xml
       $sxml['CopyCount']  = 2; //Количество копий печатной формы в одном документе
       
       //Номер отправления
       $sxml->Order['DispatchNumber'] = $extra_info['cdek_order_id']['data']['orderId']; 
       
       $xml = $sxml->asXML(); //XML заказа
       
       $view->assign(array(
          'xml' => $xml,
          'api_url' => self::API_URL
       ));

       return $view->fetch('%shop%/form/delivery/cdek/cdek_print_form.tpl');
    }

    /**
     * Возвращает HTML виджет с информацией о заказе
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    private function cdekGetInfo(\Shop\Model\Orm\Order $order)
    {
       try{
           $response = $this->requestGetInfo($order);
       } catch (\Exception $ex){
           return $ex->getMessage();
       }
       
       $cdekInfo = new \Shop\Model\DeliveryType\Cdek\CDEKInfo();
       $tariffs = $cdekInfo->getRusTariffs() + $cdekInfo->getInternationTariffs();
       
       $view = new \RS\View\Engine();
       //Если есть доп услуга
       
       if (isset($response->Order->AddedService) && !empty($response->Order->AddedService['ServiceCode'])){
           $addServices  = $cdekInfo->getAllAdditionalServices();
           $service_code = (integer)$response->Order->AddedService['ServiceCode']; 
           if (isset($addServices[$service_code])){
               $view->assign(array(
                  'addTariffCode' => $addServices[$service_code]
               ));
           }
           
       }
       
       
       $view->assign(array(
         'order_info' => $response->Order,
         'title' => t('Информация о заказе СДЭК'),
         'tariffCode' => $tariffs[(integer)$response->Order['TariffTypeCode']],
         'tariffs' => $tariffs,
         'order' => $order,
         'delivery_type' => $this
       ));                       
       return $view->fetch('%shop%/form/delivery/cdek/cdek_order_info.tpl'); 
    }


    /**
     * Возвращает HTML виджет со статусом заказа для админки
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    private function cdekGetHtmlStatus(\Shop\Model\Orm\Order $order)
    {
       try{
           $response = $this->requestOrderStatus($order);
       } catch (\Exception $ex){
           return $ex->getMessage();
       }
       
       
       $view = new \RS\View\Engine();
       $view->assign(array(
         'order_info' => $response->Order,
         'title' => t('Статус заказа'),
         'order' => $order,
         'delivery_type' => $this
       ));                       
       return $view->fetch('%shop%/form/delivery/cdek/cdek_get_status.tpl'); 
    }

    /**
     * Возвращает текст, в случае если доставка невозможна. false - в случае если доставка возможна
     *
     * @param \Shop\Model\Orm\Order $order
     * @param \Shop\Model\Orm\Address $address - Адрес доставки
     * @return bool|mixed|string
     * @throws \RS\Exception
     */
    function somethingWrong(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if (!$address){
           $address = $order->getAddress(); 
        }
        
        $this->requestDeliveryInfo($order, $address);
        
        
        if ($this->hasErrors()){ //Если есть ошибки
            return $this->getErrorsStr();
        }
        
        return false;
    }

    /**
     * Действие с запросами к заказу для получения дополнительной информации от доставки
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return string|void
     * @throws \Exception
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     * @throws \SmartyException
     */
    function actionOrderQuery(\Shop\Model\Orm\Order $order)
    {
        $url = new \RS\Http\Request();
        $method = $url->request('method',TYPE_STRING,false);
        switch ($method){
            case "getCallCourierHTML": //Получение статуса заказа
                return $this->cdekGetCallCourierHTML($order); 
                break;
            case "getInfo": //Получение статуса заказа
                return $this->cdekGetInfo($order); 
                break;
            case "getPrintDocument": //Получение статуса заказа
                return $this->cdekGetPrintDocument($order); 
                break;
            case "deleteOrder": //Получение статуса заказа
                return $this->cdekDeleteOrder($order); 
                break;
            case "recreateOrder": //Получение статуса заказа
                return $this->cdekReCreateOrder($order); 
                break;
            case "getStatus": //Получение статуса заказа
            default:
                return $this->cdekGetHtmlStatus($order);    
                break;
        }
    }

    /**
     * Возвращает дополнительный HTML для админ части в заказе
     *
     * @param \Shop\Model\Orm\Order $order - заказ доставки
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    function getAdminHTML(\Shop\Model\Orm\Order $order)
    {
        $view = new \RS\View\Engine();
        
        $view->assign(array(
            'order' => $order,
        ));
        
        return $view->fetch("%shop%/form/delivery/cdek/cdek_additional_html.tpl");
    }
    
    /**
    * Получает выбранные тарифы для отправки доставки
    * 
    * @return array
    */
    private function getSelectedTariffs()
    {
       return $this->getOption('tariffTypeList');
    }
    
    /**
    * Возвращет первый выбранный пользователем тариф
    * 
    * @return integer
    */
    private function getSelectedFirstTariffId()
    {
       $tariffs    = $this->getSelectedTariffs();
       return !empty($tariffs) ? (int)current($tariffs) : false;  
    }
    
    /**
    * Возвращает информацию по первому выбранному тарифу пользователем
    * 
    * @return false|array
    */
    private function getSelectedFirstTariffInfo()
    {
        $tariff_id = $this->getSelectedFirstTariffId();
        
        if (!$tariff_id){
            return false;
        }                       
        
        $cdek_info = new \Shop\Model\DeliveryType\Cdek\CdekInfo();
        $tariffs = $cdek_info->getAllTariffsWithInfo();
        
        return $tariffs[$tariff_id];
    }


    /**
     * Возвращает дополнительный HTML для публичной части с выбором в заказе
     *
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @param \Shop\Model\Orm\Order $order - заказ доставки
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    function getAddittionalHtml(\Shop\Model\Orm\Delivery $delivery, \Shop\Model\Orm\Order $order = null)
    {  
        $view = new \RS\View\Engine();
        if (!$order){
            $order = \Shop\Model\Orm\Order::currentOrder(); 
        }
        $this->getDeliveryCostText($order, null, $delivery);

        $pochtomates = $this->getPvzList($order);
        if (!empty($pochtomates)){ //Если нужны почтоматы
            return $this->getAdditionalHtmlForPickPoints($delivery, $order, $pochtomates);
        }

        $view->assign(array(
            'errors'      => $this->getErrors(),
            'order'       => $order,
            'extra_info'  => $order->getExtraKeyPair(),
            'delivery'    => $delivery,
            'cdek'        => $this,
        ) + \RS\Module\Item::getResourceFolders($this));
                                  
        return $this->wrapByWidjet($delivery, $order, $view->fetch("%shop%/delivery/cdek/additional_html.tpl"));
    }


    /**
     * Возвращает дополнительный HTML для административной части с выбором опций доставки в заказе
     *
     * @param \Shop\Model\Orm\Order $order - заказ доставки
     * @return string
     * @throws \Exception
     * @throws \SmartyException
     */
    function getAdminAddittionalHtml(\Shop\Model\Orm\Order $order = null)
    {
        $view = new \RS\View\Engine();
        
        //Получим данные потоварам
        $products = $order->getCart()->getProductItems();
        if (empty($products)){
            $this->addError(t('В заказ не добавлено ни одного товара'));
        }

        $pickpoints = $this->getPvzList($order); //Получим почтоматы

        //Получим цену с параметрами по умолчанию
        $cost = $this->getDeliveryCostText($order, null, $order->getDelivery());
        
        $view->assign(array(
            'errors'     => $this->getErrors(),
            'order'      => $order,
            'cost'       => $cost,
            'extra_info' => $order->getExtraKeyPair(),
            'cdek'       => $this,
            'pickpoints' => $pickpoints,
        ) + \RS\Module\Item::getResourceFolders($this));
        
        return $view->fetch("%shop%/form/delivery/cdek/admin_pochtomates.tpl");
    }

    /**
     * Функция срабатывает после создания заказа
     *
     * @param \Shop\Model\Orm\Order $order     - объект заказа
     * @param \Shop\Model\Orm\Address $address - Объект адреса
     * @return void
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    function onOrderCreate(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        $extra = $order->getExtraInfo();
         
        if (!isset($extra['cdek_order_id'])){ //Если заказ в СДЭК ещё не создан
            $created_order = $this->requestCreateOrder($order, $address);
            //Итак смотрим, если в ответе если у первого элемента ответа, есть ErrorCode, 
            //то добавим ошибки в админ поле заказа.
            //Иначе запишем все данные и добавим в коммент сведения об успешном создании заказа
            if ($created_order){//Если дождались ответ от СДЭК
                if (isset($created_order->Order[0]['ErrorCode'])){ //Если есть ошибки, добавим в комметарий
                    $this->addErrorsToOrderAdminComment("создание заказа", $order['id'], $created_order->Order);
                }else{//Если ошибок нет
                    $cdek_order_id   = (string)$created_order->Order[0]['DispatchNumber'];
                    $cdek_act_number = (string)$created_order->Order[0]['Number'];
                    //$this->addStringsToOrderAdminComment($order['id'],$created_order->Order);
                    $order->addExtraInfoLine(
                        t('id заказа СДЭК'),
                        '<a href="http://lk.cdek.ru/" target="_blank">'.t('Перейти к заказу №%0', array($cdek_order_id)).'</a>',
                        array(
                            'orderId' => $cdek_order_id
                        ),
                        'cdek_order_id'
                   );
                   $order->addExtraInfoLine(
                        t('Номер акта СДЭК'),
                        '',
                        array(
                            'actNumber' => $cdek_act_number
                        ),
                        'cdek_act_number'
                   );
                }
            }else{
                $this->addToOrderAdminComment($order['id'], t("Не удалось связаться с сервером с СДЭК при создании заказа."));
            }
        }
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
     * Возвращает стоимость доставки для заданного заказа. Только число.
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Address $address - адрес доставки
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @param boolean $use_currency - использовать валюту?
     * @return double
     * @throws \RS\Event\Exception
     */
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery, $use_currency = true)
    {
        $order_delivery  = $order->getDelivery(); 
        $cache_key = md5($order['order_num'].$order_delivery['id']);
        if (!isset($this->cache_api_requests[$cache_key])){
           $sxml = $this->requestDeliveryInfo($order, $address); 
        }else{
           $sxml = $this->cache_api_requests[$cache_key]; 
        }
        
        $cost = false;
        if (isset($sxml['result'])){
           
            $this->delivery_cost_info = $sxml['result'];
            // Если в ответе есть валюта, используем стоимость в валюте, иначе используем стоимость в рублях 
            $cost = (isset($sxml['result']['currency'])) ? $sxml['result']['priceByCurrency'] : $sxml['result']['price'];
            $cost = $this->applyExtraChangeDiscount($delivery, $cost); //Добавим наценку или скидку

            $currency_name = (isset($sxml['result']['currency'])) ? $sxml['result']['currency'] : 'RUB';
            $currency = \Catalog\Model\Orm\Currency::loadByWhere(array('title' => $currency_name));
            // переводим стоимость в базовую валюту
            if ($currency['id'] && !$currency['is_base']) {
                $cost *= $currency['ratio'];
            }

            if ($use_currency && $cost > 0){
                // Получим текущую валюту
                $currency = \Catalog\Model\CurrencyApi::getCurrentCurrency();
                $cost /= $currency['ratio'];
            }
            //Добавим тариф по которому будет осуществленна доставка
            $order->addExtraKeyPair('tariffId', $sxml['result']['tariffId']);
            $order->addExtraKeyPair('deliveryPeriodMin', $sxml['result']['deliveryPeriodMin']);
            $order->addExtraKeyPair('deliveryPeriodMax', $sxml['result']['deliveryPeriodMax']);
            $order->addExtraKeyPair('deliveryDateMin', $sxml['result']['deliveryDateMin']);
            $order->addExtraKeyPair('deliveryDateMax', $sxml['result']['deliveryDateMax']);
        }
        
        return $cost;
    }


    /**
     * Возвращает цену в текстовом формате, т.е. здесь может быть и цена и надпись, например "Бесплатно"
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Address $address - объект адреса
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @return string
     * @throws \RS\Event\Exception
     */
    function getDeliveryCostText(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery)
    {
        $cost = $this->getDeliveryCost($order, $address, $delivery);
        return ($cost>0) ? \RS\Helper\CustomView::cost($cost).' '.$order->getMyCurrency()->stitle : t('бесплатно');
    }
    
    /**
    * Получает массив доп. услуг
    * 
    * @return array
    */ 
    public static function getAdditionalServices()
    {
        $list = \Shop\Model\DeliveryType\Cdek\CDEKInfo::getAdditionalServices();
        
        $arr = array();
        foreach($list as $k=>$item){
           $arr[$k] = $item['title']; 
        }
        
        return $arr;
    }
    
    
    
    /**
    * Устанавливает тариф по которому будет произведена доставка после подсчёта стоимости 
    * 
    * @param integer $id
    */
    function setTariffId($id)
    {
       $this->tariffId = $id; 
    }
    
    /**
    * Получает id тарифа по которому будет произведена доставка после подсчёта стоимости
    * 
    */
    function getTariffId()
    {
       return $this->tariffId; 
    }

    /**
     * Возвращает трек номер для отслеживания
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return bool
     */
    function getTrackNumber(\Shop\Model\Orm\Order $order)
    {
        $extra = $order->getExtraInfo();
        if (isset($extra['cdek_order_id']['data']['orderId'])){
            return $extra['cdek_order_id']['data']['orderId'];
        }
        return false;
    }
    
    /**
    * Возвращает ссылку на отслеживание заказа
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * 
    * @return string
    */
    function getTrackNumberUrl(\Shop\Model\Orm\Order $order)
    {
        $track_number = $this->getTrackNumber($order);
        if ($track_number){
            return "http://www.edostavka.ru/track.html?order_id=".$track_number;
        }
        return "";
    }

    /**
     * Рассчитывает структурированную информацию по сроку, который требуется для доставки товара по заданному адресу
     *
     * @param \Shop\Model\Orm\Order $order объект заказа
     * @param \Shop\Model\Orm\Address $address объект адреса
     * @param \Shop\Model\Orm\Delivery $delivery объект доставки
     * @return Helper\DeliveryPeriod | null
     */
    protected function calcDeliveryPeriod(\Shop\Model\Orm\Order $order,
                                          \Shop\Model\Orm\Address $address = null,
                                          \Shop\Model\Orm\Delivery $delivery = null)
    {
        if (($info = $this->requestDeliveryInfo($order, $address)) && !$this->hasErrors()) {
            return new Helper\DeliveryPeriod($info['result']['deliveryPeriodMin'], $info['result']['deliveryPeriodMax']);
        }

        return null;
    }

    /**
     * Возвращает HTML для приложения на Ionic
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
     * @return string
     * @throws \Exception
     * @throws \RS\Event\Exception
     * @throws \SmartyException
     */
    function getIonicMobileAdditionalHTML(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Delivery $delivery)
    {
        $view = new \RS\View\Engine();
        if (!$order){
            $order = \Shop\Model\Orm\Order::currentOrder(); 
        }
        
        $tariff = $this->getSelectedFirstTariffInfo();
        
        $pochtomates = array();
        if ($tariff && in_array($tariff['regim_id'], array(2, 4))){ //Если нужны почтоматы
            $pochtomates = $this->requestPochtomat($order, $tariff);
        }
        
        $this->getDeliveryCostText($order, null, $delivery);
        
        $view->assign(array(
            'errors'      => $this->getErrors(),
            'order'       => $order,
            'extra_info'  => $order->getExtraKeyPair(),
            'delivery'    => $delivery,
            'cdek'        => $this,
            'pochtomates' => $pochtomates,
        ) + \RS\Module\Item::getResourceFolders($this));
        
        return $view->fetch("%shop%/delivery/cdek/mobilesiteapp/pochtomates.tpl");
    }
    
    /**
    * Возвращает список доступных ПВЗ для переданного заказа
    * 
    * @param \Shop\Model\Orm\Order $order
    * @return array|boolean
    */
    function getPvzList(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        $this->getDeliveryCost($order, null, $order->getDelivery());
        if (!$address) {
            $address = $order->getAddress();
        }
        $cdek_info = new \Shop\Model\DeliveryType\Cdek\CdekInfo();
        $tariffs = $cdek_info->getAllTariffsWithInfo();
        $extra_ti = $order->getExtraKeyPair('tariffId');
       if (isset($tariffs[$extra_ti])){
           $tariff = $tariffs[$extra_ti];
           if ($tariff && in_array($tariff['regim_id'], array(2, 4))){ //Если нужны почтоматы
               $pochtomates = $this->requestPochtomat($order, $tariff, $address);
               $result = array();

               foreach ($pochtomates as $pochtomat) {
                   $attr = (array) $pochtomat->attributes();
                   $attr = $attr['@attributes'];
                   $pvz = new \Shop\Model\DeliveryType\Cdek\Pvz();
                   // Записываем свойства ПВЗ
                   $pvz->setCode($attr['Code']);
                   $pvz->setTitle($attr['Name']);
                   $pvz->setCountry($attr['CountryName']);
                   $pvz->setRegion($attr['RegionName']);
                   $pvz->setCity($attr['City']);
                   $pvz->setAddress($attr['Address']);
                   $pvz->setWorktime($attr['WorkTime']);
                   $pvz->setCoordX($attr['coordX']);
                   $pvz->setCoordY($attr['coordY']);
                   $pvz->setPhone($attr['Phone']);
                   $pvz->setCashOnDelivery($attr['cashOnDelivery']);
                   $pvz->setNote($attr['Note']);
                   // Удаляем записанные поля из extra
                   $remove_key = array('Code', 'Name', 'CountryName', 'RegionName', 'City', 'coordX', 'coordY', 'Phone', 'cashOnDelivery', 'Note');
                   foreach ($remove_key as $key) {
                       unset($attr[$key]);
                   }
                   // преобразуем массив графика работы
                   $worktime_y = array();
                   foreach ($pochtomat->WorkTimeY as $item) {
                       $worktime_y[(string) $item['day']] = (string) $item['periods'];
                   }
                   $attr['WorkTimeY'] = $worktime_y;
                   $pvz->setExtra($attr);

                   $result[] = $pvz;
               }
               return $result;
           }
       }

        
        return false;
    }

    /**
     * Возвращает правильный идентификатор налога
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Catalog\Model\Orm\Product $product - объект товара
     * @return string
     */
    private function getRightTaxForProduct(\Shop\Model\Orm\Order $order, \Catalog\Model\Orm\Product $product)
    {
        $address = $order->getAddress();
        $tax_api = new \Shop\Model\TaxApi();
        $taxes   = $tax_api->getProductTaxes($product, $order->getUser(), $address);
        $tax     = new \Shop\Model\Orm\Tax();
        foreach ($taxes as $item){
            if ($item['is_nds']){
                $tax = $item;
                break; 
            } 
        }
        
        //Получим ставку
        $tax_rate = (float) $tax->getRate($address);
        switch($tax_rate){
            case "10":
                $tax_id = 'VAT10';
                break;
            case "18":
                $tax_id = 'VAT18';
                break;
            case "0":
                $tax_id = 'VAT0';
                break;
            default:
                $tax_id = 'VATX';
        }
        
        return $tax_id;
    }
    
    /**
    * Возвращает ставку налога по идентификатору
    * 
    * @param string $tax_id
    * @return float
    */
    private function getTaxRateById($tax_id)
    {
        $tax_rate = array(
            'VATX' => 0,
            'VAT0' => 0,
            'VAT10' => 10/110,
            'VAT18' => 18/118,
        );
        
        return $tax_rate[$tax_id];
    }
    
    /**
    * Возвращает id города в базе СДЭК, или false
    * 
    * @param \Shop\Model\Orm\Address $address
    * @return string|false
    */
    private function getCityIdByName(\Shop\Model\Orm\Address $address)
    {
        $city = $address->getCity();
        $region = $address->getRegion();
        $cache_key = $city['title'].'_'.$region['title'].'_'.$region['country'];
        
        if (!isset($this->cache_city_id[$cache_key])) {
            $result = false;
            // По названию страны определяем в каком файле искать город
            $country = $address->getCountry();
            switch ($country['title']) {
                case t('Россия'): $file_name = 'cdek_rus.csv'; break; 
                case t('Казахстан'): $file_name = 'cdek_kaz.csv'; break;
                default: $file_name = ''; 
            }
            
            if (!empty($file_name)) {
                $file_path = \Setup::$PATH . \Setup::$MODULE_FOLDER . '/shop' . \Setup::$CONFIG_FOLDER . '/delivery/cdek/' . $file_name;
                
                $file = fopen($file_path, 'r');
                $f = array_flip(fgetcsv($file, null, ';', '"'));
                
                while($cdek_city = fgetcsv($file, null, ';', '"')) { // Город найден при совпадении названия города и его области
                    if (!empty($cdek_city) && $city['title'] == $cdek_city[$f['city_name']] && stripos($region['title'], $cdek_city[$f['region_name']]) !== false) {
                        $result = $cdek_city[$f['cdek_id']];
                        break;
                    }
                }
            }
            
            $this->cache_city_id[$cache_key] = $result;
        }
        
        return $this->cache_city_id[$cache_key];
    }
}