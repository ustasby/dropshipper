<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

class Spsr extends AbstractType implements 
                                    \Shop\Model\DeliveryType\InterfaceIonicMobile
{
    const 
        API_XML_URL = "http://api.spsr.ru/waExec/WAExec",
        API_URL = 'http://www.cpcr.ru/cgi-bin/postxml.pl?TARIFFCOMPUTE_2',
        DEFAULT_COUNTRY   = '209|0', // Россия
        DEFAULT_CITY_FROM = '992|0', // Москва
        DEFAULT_CITY_TO   = '893|0', // Санкт-Петербург (нужен для получения всех тарифов SPSR)
        REQUEST_TIMEOUT   = 10; // 10 сек. таймаут запроса к api
    
    
        
    function __construct()
    {
        $this->setOption(array(
            'city_from' => self::DEFAULT_CITY_FROM
        ));
    }
        
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('SPSR Express');
    }
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    function getDescription()
    {
        return t('SPSR Экспресс-доставка');
    }
    
    /**
    * Возвращает идентификатор данного типа доставки. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return t('spsr');
    }
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'login' => new Type\Varchar(array(
                'description' => t('Логин'),
            )),
            'password' => new Type\Varchar(array(
                'description' => t('Пароль'),
            )),
            'icn' => new Type\Varchar(array(
                'description' => t('ИКН'),
            )),
            'city_from' => new Type\Varchar(array(
                'description' => t('Город отправления'),
                'ListFromArray' => array($this->getCities()),
            )),
            'tariff' => new Type\Varchar(array(
                'description' => t('Тариф'),
                'ListFromArray' => array($this->getAllTariffs()),
            )),
        ) );
        
        return new \RS\Orm\FormObject($properties);
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
        $sxml = $this->requestDeliveryInfo($order, $address);
        
        if(!isset($sxml->Tariff)){
            return t('Не удалось соединиться с сервером SPSR');
        }
        $avalible_tariffs = array();
        foreach($sxml->Tariff as $one){
            $avalible_tariffs[] = trim($one->TariffType, '"');
        }
        if(array_search($this->getOption('tariff'), $avalible_tariffs) === false){
            return t("Доставка невозможна в Ваш регион");
        }
        return false;
    }
    
    /**
    * Запрос к серверу рассчета стоимости доставки. Ответ сервера кешируется
    * 
    * @param array $params
    */
    private function apiRequest($params)
    {
        ksort($params);
        $cache_key = md5(serialize($params));
        
        $ctx = stream_context_create(array(
            'http' => array('timeout' => self::REQUEST_TIMEOUT)
        ));
        
        $url      = self::API_URL.'&'.http_build_query($params);
        $response = file_get_contents($url, null, $ctx);
        
        return $response;
    }
    
    /**
    * Запрос к серверу SPSR
    * 
    * @param \SimpleXMLElement $sxml
    */
    private function apiXmlRequest($sxml)
    {
        $context = array(
            'http' => array(
                'header' => 'Content-Type: application/xml'."\r\n",
                'content' => $sxml->asXML(),
            )
        );
        $result = file_get_contents(self::API_XML_URL, null, stream_context_create($context));
        
        return new \SimpleXMLElement($result);
    }
    
    /**
    * Авторизация в SPSR
    * Возвращает идентификатор сессии
    * 
    * @return string
    */
    private function spsrLogin()
    {
        $login = $this->getOption('login');
        $pass = $this->getOption('password');
        if (!empty($login) and !empty($pass)) {
            $sxml = new \SimpleXMLElement('<root/>');
            $sxml['xmlns'] = 'http://spsr.ru/webapi/usermanagment/login/1.0';
            $sxml->addChild('p:Params', null, 'http://spsr.ru/webapi/WA/1.0');
            $sxml->Params['Name'] = 'WALogin';
            $sxml->Params['Ver'] = '1.0';
            $sxml->Login['Login'] = $login;
            $sxml->Login['Pass'] = $pass;
            $sxml->Login['UserAgent'] = $login;
            
            $sxml = $this->apiXmlRequest($sxml);
            
            return (string) $sxml->Login['SID'];
        }
        return false;
    }
   
    /**
    * Обобщающий метод запроса информации к серверу
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - объект адреса
    * @return \SimpleXMLElement
    */
    private function requestDeliveryInfo(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if(!$address) $address = $order->getAddress();
        $request = array(
            'FromCountry'   => self::DEFAULT_COUNTRY,
            'FromCity'      => $this->getOption('city_from', self::DEFAULT_CITY_FROM),
            'Country'       => self::DEFAULT_COUNTRY,
            'ToRegion'      => '',
            'to_Cities_name'=> '',
            'ToCity'        => $this->getCityId($address->city),
            'Weight'        => $order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_KG),
        );
        if ($sid = $this->spsrLogin()) {
            $user_data = array(
                'SID' => $sid,
                'ICN' => $this->getOption('icn'),
            );
            $request = array_merge($request, $user_data);
        }
        
        $sid = $this->spsrLogin();
        
        $data = $this->apiRequest($request);
        
        return new \SimpleXMLElement($data);
    }
    
    /**
    * Возвращает список всех доступных тарифов SPSR
    * 
    * @return array
    */
    public function getAllTariffs()
    {
        $request = array(
            'FromCountry'   => self::DEFAULT_COUNTRY,
            'FromCity'      => self::DEFAULT_CITY_FROM,
            'Country'       => self::DEFAULT_COUNTRY,
            'ToRegion'      => '',
            'to_Cities_name'=> '',
            'ToCity'        => self::DEFAULT_CITY_TO,
            'Weight'        => 1,
        );
        if ($sid = $this->spsrLogin()) {
            $user_data = array(
                'SID' => $sid,
                'ICN' => $this->getOption('icn'),
            );
            $request = array_merge($request, $user_data);
        }
        
        $data = $this->apiRequest($request);
        $sxml = new \SimpleXMLElement($data);
        $tariffs = array();
        foreach($sxml->Tariff as $one){
            $key = str_replace('"', "'", $one->TariffType);
            $tariffs[$key] = $key;
        }
        return $tariffs;
    }

    /**
    * Возвращает стоимость доставки для заданного заказа. Только число.
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @return double
    */
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery, $use_currency = true)
    {
        $sxml = $this->requestDeliveryInfo($order, $address);
        $desiredTariff = $sxml->Tariff[0];
        foreach($sxml->Tariff as $tariff){
            $tariff_name = trim($tariff->TariffType, '"');
            if($tariff_name == $this->getOption('tariff')){
                $desiredTariff = $tariff;
            }
        }
        $cost = $desiredTariff->Total_Dost;
        $cost = $this->applyExtraChangeDiscount($delivery, $cost); //Добавим наценку или скидку 
        
        if($use_currency){
            $cost = $order->applyMyCurrency($cost);
            $currency  = $order->getMyCurrency();
        }
        return $cost;
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
        try {
            $sxml = $this->requestDeliveryInfo($order, $address);
            $desiredTariff = $sxml->Tariff[0];
            foreach ($sxml->Tariff as $tariff) {
                $tariff_name = trim($tariff->TariffType, '"');
                if ($tariff_name == $this->getOption('tariff')) {
                    $desiredTariff = $tariff;
                }
            }

            list($min, $max) = explode('-', $desiredTariff->DP);
            return new Helper\DeliveryPeriod($min, $max);

        } catch(\Exception $e) {}
    }
    
    
    /**
    * Возвращает идентификатор города в данной системе доставки
    * 
    * @param string $city_title
    */
    private function getCityId($city_title)
    {
        $cities = $this->getCities();
        $cities = array_map('mb_strtolower', $cities);
        $key = array_search(mb_strtolower($city_title), $cities);
        return $key;
    }
    
    /**
    * Возвращает список городов для данного типа доставки
    * 
    * @return array
    */
    private function getCities()
    {
        $cities = SPSR\SpsrLocations::$cities;
        asort($cities);
        return $cities;
    }
    
    /**
    * Возвращает HTML для приложения на Ionic
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    */
    function getIonicMobileAdditionalHTML(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Delivery $delivery)
    {
        return "";    
    }
}