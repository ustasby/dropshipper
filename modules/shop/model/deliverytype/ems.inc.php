<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

class Ems extends AbstractType
{
    const 
        API_URL = "http://emspost.ru/api/rest/",
        REQUEST_TIMEOUT   = 10; // 10 сек. таймаут запроса к api
    
    private 
        $cache_api_requests = array();  // Кэш запросов к серверу рассчета
    
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('EMS Почта России');
    }
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Международный сервис экспресс-доставки почтовой корреспонденции EMS<p><b>Для этого типа важно задать вес у товара в граммах, либо указать параметр в "Веб-сайт" &rarr; "Настройка модулей" &rarr; "Каталог товаров" &rarr; "Вес одного товара по-умолчанию"</b></p>');
    }
    
    /**
    * Возвращает идентификатор данного типа доставки. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return 'ems';
    }
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'city_from' => new Type\Varchar(array(
                'description' => t('Город отправления'),
                'ListFromArray' => array($this->getCities()),
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
        if(!$address) $address = $order->getAddress();      
        if(!$this->getCityId($address->city, $address->region)){
            return t('Доставка невозможна в город %0', array($address->city));
        }

        $data = $this->apiRequest(array(
            'method'    => 'ems.test.echo',
        ));
        
        if(!isset($data->rsp->stat) || $data->rsp->stat != 'ok'){
            return t('Не удалось соединиться с сервером EMS');
        }

        return false;
    }
    
    
    /**
    * Запрос к серверу рассчета стоимости доставки. Ответ сервера кешируется
    * 
    * @param array $params
    */
     function apiRequest($params)
    {
        ksort($params);                                      
        $cache_key = md5(serialize($params));
        if(!isset($this->cache_api_requests[$cache_key])){
            $ctx = stream_context_create(array(
                'http' => array('timeout' => self::REQUEST_TIMEOUT)
            ));
            
            $url = self::API_URL.'?'.http_build_query($params);
            $json = file_get_contents($url, null, $ctx);
            $this->cache_api_requests[$cache_key] = json_decode($json);
        }
        return $this->cache_api_requests[$cache_key];
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
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery = null, $use_currency = true)
    {
        if (!$address) $address = $order->getAddress();
        if (!$delivery) $delivery = $order->getDelivery();
        
        $data = $this->emsCalculate($order, $address);
        
        if(!isset($data->rsp->price)){
            return false;
        }
        $cost = $data->rsp->price;
        $cost = $this->applyExtraChangeDiscount($delivery, $cost); //Добавим наценку или скидку 
        if($use_currency){
            $cost = $order->applyMyCurrency($cost);
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
        if(!$address) $address = $order->getAddress();

        $data = $this->emsCalculate($order, $address);

        if ($data) {
            list($min, $max) = array($data->rsp->term->min, $data->rsp->term->max);
            return new Helper\DeliveryPeriod($min, $max);
        }
        return null;
    }
    
    private function emsCalculate(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        if (!$this->getOption('city_from')) $this->addError(t('Не указан город отправитель.'));
        if (!$address->city) $this->addError(t('Не указан город получатель.'));
        if (!$order->getWeight()) $this->addError(t('Не указан вес.'));
        
        $data = $this->apiRequest(array(
            'method'    => 'ems.get.max.weight',
        ));
        if (!$data) $this->addError(t('Сервис расчётов не доступен.'));
        if ($order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_KG) > (float) $data->rsp->max_weight) {
            $this->addError(t('Превышен максимально допустимый вес.'));
        } else {
            $data = $this->apiRequest(array(
                'method'    => 'ems.calculate',
                'from'      => $this->getOption('city_from'),
                'to'        => $this->getCityId($address->city, $address->region),
                'weight'    => $order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_KG), // Вес заказа в килограммах
            ));
            
            return $data;
        }
        
        return false;
    }
    
    /**
    * Возвращает идентификатор города или региона в данной системе доставки
    * 
    * @param string $city_title
    * @return string 
    */
    private function getCityId($city_title, $region_title = null)
    {
        $cities = $this->getCities();
        $regions = $this->getRegions();
        
        $key = array_search(mb_strtoupper($city_title), $cities);
        if (!$key && $region_title) {
            //Ищем регион
            $key = array_search(mb_strtoupper($region_title), $regions);
        }
        
        return $key;
    }
    
    /**
    * Возвращает список городов для данного типа доставки
    * 
    * @return array
    */
    private function getCities()
    {
        return EmsLocations::$cities;
    }
    
    /**
    * Возвращает список регионов для данного типа доставки
    * 
    * @return array
    */
    private function getRegions()
    {
        return EmsLocations::$regions;
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