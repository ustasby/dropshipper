<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

class RussianPostCalc extends \Shop\Model\DeliveryType\AbstractType
{
    const
        SHORT_NAME = 'russianpostcalc',
        CALCULATE_URL =  'http://tariff.russianpost.ru';

    protected
        $config,
        $log_file;

    
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Почта России (api)');
    }
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Почта России (api)');
    }
    
    /**
    * Возвращает идентификатор данного типа доставки. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return self::SHORT_NAME;
    }
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'tariff_code' => new Type\Varchar(array(
                'description' => t('Код тарифа для калькуляции стоимости отправления' ),
                'list' => array(array('\Shop\Model\DeliveryType\RusPost\HandBook', 'valuesTariffCode')),
            )),
            'postoffice_code' => new Type\Integer(array(
                'description' => t('Индекс места приема'),
            )),
            'pack' => new Type\Integer(array(
                'description' => t('Упаковка (Только для тарифа "посылка стандарт")'),
                'listFromArray' => array(array(
                    '10'    => t('Коробка «S»'),
                    '11'    => t('Пакет полиэтиленовый «S»'),
                    '12'    => t('Конверт с воздушно-пузырчатой пленкой «S»'),
                    '20'    => t('Коробка «М»'),
                    '21'    => t('Пакет полиэтиленовый «М»'),
                    '22'    => t('Конверт с воздушно-пузырчатой пленкой «М»'),
                    '30'    => t('Коробка «L»'),
                    '31'    => t('Пакет полиэтиленовый «L»'),
                    '40'    => t('Коробка «ХL»'),
                    '41'    => t('Пакет полиэтиленовый «ХL»'),
                ))
            )),
            'mark_courier' => new Type\Integer(array(
                'description' => t('Отметка «Курьер»'),
                'checkboxView' => array(1,0),
            )),
            'mark_fragile' => new Type\Integer(array(
                'description' => t('Отметка «Осторожно/Хрупкая»"'),
                'checkboxView' => array(1,0),
            )),
            'decrease_declared_cost' => new Type\Integer(array(
                'description' => t('Снижать объявленную стоимость до 1 руб.'),
                'checkboxView' => array(1,0),
                'default' => 0,
            )),
        ));
        
        return new \RS\Orm\FormObject($properties);
    }
    
    /**
    * Возвращает стоимость доставки  -  отсутствует логика по валюте
     * @return mixed
    */
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery, $use_currency = true)
    {
        if ($address === null) {
            $address = $order->getAddress();
        }
        $total_without_delivery_unformatted = $order->getCart()->getTotalWithoutDelivery();
        //$rub_currency = \
        $params = array(
            'tariff_code' => $this->getOption('tariff_code'),
            'indexfrom' => $this->getOption('postoffice_code'),
            'indexto' => $address['zipcode'],
            'mass' => (empty($order['true_weight'])) ? $order->getWeight(\Catalog\Model\Api::WEIGHT_UNIT_G) : $order['true_weight'],
            'declared_value' => $total_without_delivery_unformatted,
            'mark_courier' => $this->getOption('mark_courier'),
            'mark_fragile' => $this->getOption('mark_fragile'),
            'decrease_declared_cost' => $this->getOption('decrease_declared_cost'),
        );
        
        $result = $this->calculateCost($params);

        if (isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->addError($error['msg']);
            }
            return false;
        } elseif (isset($result['paynds'])) {
            $cost = \Catalog\Model\CostApi::roundCost($result['paynds'] / 100);
            $cost = $this->applyExtraChangeDiscount($delivery, $cost); //Добавим наценку или скидку 
            if ($use_currency){
                $cost = $order->applyMyCurrency($cost);
            }
            return $cost;
        } else {
            $this->addError(t('Недоступно при данных условиях'));
            return false;
        }
    }

    /**
     * Рассчитывает стоимость доставки заказа
     *
     * @param array $params - даннные для калькуляции, включает следующие поля:
     *     mail_category - вид РПО
     *     mail_type - тип РПО
     *     indexto - индекс получателя
     *     mass - вес заказа
     *     mark_courier - отметка "курьер"
     *     mark_fragile - отметка "хрупкое"
     *     declared_value - объявленная стоимость
     */
    function calculateCost($params)
    {
        $tariff_list = \Shop\Model\DeliveryType\RusPost\HandBook::valuesTariffCode();
        $tariff_text = $tariff_list[$params['tariff_code']];
        $request_params = array(
            'json' => 1,
            'object' => $params['tariff_code'],
            'from' => $params['indexfrom'],
            'to' => $params['indexto'],
            'weight' => $params['mass'],
            'pack' => $this->getOption('pack'),
        );
        $request_params['service'] = array();
        if ($params['mark_courier']) {
            $request_params['service'][] = 26;
        }
        if ($params['mark_fragile']) {
            $request_params['service'][] = 4;
        }
        if (strpos($tariff_text, 'объявленной ценностью')) {
            if (strpos($tariff_text, 'наложенным платежом')) {
                $request_params['sumoc'] = $params['declared_value'] * 100;
                $request_params['sumnp'] = $params['declared_value'] * 100;
            } else {
                $request_params['sumoc'] = ($params['decrease_declared_cost']) ? 100 : $declared_value * 100;
            }
        }

        $res = $this->apiRequest('get', '/tariff/v1/calculate', $request_params, true, true);
        return $res['response'];
    }
    /**
     * API запрос
     *
     * @param string $method - метод (get|post|put|delete)
     * @param string $request - адрес запроса
     * @param array $params - параметры запроса
     * @param bool $response_to_array - преобразовывать ответ в массив
     * @param bool $calculate_url - отправить запрос на url калькуляции
     * @return array
     */
    protected function apiRequest($method, $request, $params = array(), $response_to_array = true)
    {
        $this->log_file = \RS\Helper\Log::file(\Setup::$PATH.\Setup::$STORAGE_DIR.'/logs/Integration_RusPostCalc.log');
        $url =  self::CALCULATE_URL;
        $url .= $request;
        $requst_array = array(
            'http' => array(
                'header' => 'Authorization: AccessToken ' . $this->config['token'] . "\r\n".
                    'X-User-Authorization: Basic ' . $this->config['auth_key'] . "\r\n".
                    'Content-Type: application/json;charset=UTF-8',
                'method' => strtoupper($method),
            )
        );
        if (stripos($method, 'get') !== false) {
            $url_params = !empty($params) ? '?'.http_build_query($params) : "";
            $url .= $url_params;
        } else {
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $encoded_params = json_encode($params, JSON_UNESCAPED_UNICODE);
            } else { // костыль JSON_UNESCAPED_UNICODE для PHP 5.3
                $encoded_params = preg_replace_callback('/\\\\u(\w{4})/', function ($matches) {
                    return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
                }, json_encode($params));
            }
            $requst_array['http']['content'] = $encoded_params;
        }
        $ctx = stream_context_create($requst_array);

        // Логируем запрос
        $log_str = "request: method = $method, request = $request, params = ".serialize($params);
        $this->log_file->append($log_str);

        $response = @file_get_contents($url, null, $ctx);


        $response_code = $http_response_header[0];

        $result = array();

        if (strpos($response_code, '200') === false) {
            $result['error'] = $response_code;
        }
        $result['response'] = ($response_to_array) ? $this->objToArray((array) json_decode($response)) : $response;

        // Логируем ответ
        if (empty($result['error'])) {
            if (strpos($request, '/1.0/forms/') === false) { // не записывем ответ на запрос zip-архива
                $this->log_file->append("response: ".serialize($result['response']));
            }
        } else {
            $this->log_file->append("error: {$result['error']}");
        }

        return $result;
    }

    /**
     * Рекурсивно превращает объект в массив
     *
     * @param mixed $obj - объект, который нужно преобразовать
     * @return array
     */
    protected function objToArray($obj)
    {
        $result = array();
        foreach ($obj as $key=>$item) {
            if (in_array(gettype($item), array('array', 'object'))) {
                $result[$key] = $this->objToArray((array) $item);
            } else {
                $result[$key] = $item;
            }
        }
        return $result;
    }
    
    
    /**
    * Действие с запросами к заказу для получения дополнительной информации от доставки
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
     *@return string
    */
    function actionOrderQuery(\Shop\Model\Orm\Order $order)
    {
        $url = new \RS\Http\Request();
        $method = $url->request('method', TYPE_STRING, false);
        switch ($method){
            case "createOrder": //Получение статуса заказа
                return $this->actionCreateOrder($order); 
                break;
            case "deleteOrder": //Получение статуса заказа
                return $this->actionDeleteOrder($order); 
                break;
        }
    }

    /**
     * Возвращает текст, в случае если доставка невозможна. false - в случае если доставка возможна
     *
     * @param \Shop\Model\Orm\Order $order
     * @param \Shop\Model\Orm\Address $address - Адрес доставки
     * @return mixed
     */
    public function somethingWrong(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        $cost = $this->getDeliveryCost($order, null, $order->getDelivery());

        if ($this->hasErrors()){ //Если есть ошибки
            return $this->getErrorsStr();
        }
        return false;
    }
    

}