<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace AtolOnline\Model\CashRegisterType\Version;
use RS\Orm\PropertyIterator;
use Shop\Model\Orm\Address;
use Shop\Model\Orm\Tax;
use RS\Orm\Type;

/**
* Класс интерграции с АТОЛ Онлайн
*/
class AtolOnlineV3 extends \Shop\Model\CashRegisterType\AbstractType
{

    protected
        $transaction, //Объект транзакции
        $tokenid = "", //Токен полученный при авторизации
        $group   = "", //Группа ККТ
        $inn     = ""; //ИНН
    
    const 
        API_URL = "https://online.atol.ru/possystem/v3/",
        API_RECEIPT_URL = "https://lk.platformaofd.ru/web/noauth/cheque",
        
        MAX_LENGTH = 100, //Максимальное количество в одном чеке
        
        //Операции
        OPERATION_AUTH = "getToken"; //авторизация

    const
        //Налоги
        TAX_NONE   = 'none',
        TAX_VAT0   = 'vat0',
        TAX_VAT10  = 'vat10',
        TAX_VAT18  = 'vat18',
        TAX_VAT110 = 'vat110',
        TAX_VAT118 = 'vat118';

    /**
     * Возвращает URL для запросов
     *
     * @return string
     * @throws \RS\Exception
     */
    function getApiUrl()
    {
        if ($url = \RS\Config\Loader::byModule(__CLASS__)->service_url){

            return $url;
        }
        else {
            return static::API_URL;
        }
    }
    /**
     * Возвращает поддерживаемый список налогов
     *
     * @return array
     */
    public static function getTaxesList()
    {
        return array(
            static::TAX_NONE => t('Без НДС'),
            static::TAX_VAT0 => t('НДС по ставке 0%'),
            static::TAX_VAT10 => t('НДС чека по ставке 10%;'),
            static::TAX_VAT18 => t('НДС чека по ставке 18%'),
            static::TAX_VAT110 => t('НДС чека по расчетной ставке 10/110'),
            static::TAX_VAT118 => t('НДС чека по расчетной ставке 18/118'),
        );
    }

    
    /**
    * Возвращает название расчетного модуля (онлайн кассы)
    * 
    * @return string
    */
    function getTitle() 
    {
        return t('Атол онлайн');
    }                                                      
    
    /**
    * Возвращает идентификатор данного типа онлайн кассы. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return 'atolonline';
    }
    
    
    /**
    * Делает запрос на авторизацию
    * 
    * @return array|false
    */
    private function makeAuthRequest()
    {
        $login = $this->getOption('login', ''); 
        $pass  = $this->getOption('pass', ''); 
        if (empty($login) || empty($pass)){
            $this->addError(t('Логин или пароль не указан'));
        }
        $this->group = $this->getOption('group_code');
        if (empty($this->group)){
           $this->addError(t('Необходимо обязательно указать группу.'));
        }
        $this->inn = $this->getOption('inn');
        if (empty($this->inn)){
           $this->addError(t('Необходимо обязательно указать ИНН организации.'));
        }
        if ($this->hasError()){
           return false; 
        }
        $url = $this->getTokenUrl();
        $params = array(
            'login' => $login,
            'pass' => $pass
        );
        $response = $this->createRequest($url, $params);   
        if ($response && isset($response['token']) && !empty($response['token'])){
            $this->tokenid = $response['token'];
        } elseif (!$this->checkAuthError($response)){
            $this->addError(t('Произошла неизвестная ошибка запроса.'));
        }
        return $response;
    }

    /**
     * Добавляет ошибку по коду. Возвращает false, если ошибки нет.
     * Возвращает true, если ошибка была
     *
     * @param integer $code
     */
    protected function checkAuthError($response)
    {
        if ($response && $response['code']) {
            switch ($response['code']) {
                case 17:
                    $this->addError(t('Неудалось авторизоваться. Некорректная ссылка наавторизацию'));
                    break;
                case 18:
                    $this->addError(t('Неудалось авторизоваться. Необходимо повторить запрос.'));
                    break;
                case 19:
                    $this->addError(t('Неудалось авторизоваться. Необходимо повторить запрос с корректными данными.'));
                    break;
                default:
                    $this->addError(t('Неудалось авторизоваться. Произошла неизвестная ошибка.'));
                    break;
            }
            return true;
        }
        return false;
    }
    
    /**
    * Делает запрос на авторизацию
    * 
    * @return boolean
    */
    function makeAuth()
    {
       $this->makeAuthRequest();
       return (!empty($this->tokenid)); 
    }
    
    /**
    * Возвращает часть url адреса для возврата.
    * 
    * @param string $operation - текущая операция
    *
    * @return string
    */
    private function getCorrectOperationAct($operation)
    {
        switch($operation){
            case "sell_refund":
                return "refund";
                break;
            case "sell_correction":
                return "correction";
                break;
            case "sell":
            default:
                return "sell";
                break; 
        }
    }
    
    /**
    * Возвращает URL для возврата для определённой операции
    * 
    * @param string $operation - нужная операция
    * @param string $sign - уникальная подпись транзакции
    *
    * @return string
    */
    protected function getCallbackUrl($operation, $sign)
    {
        return \RS\Router\Manager::obj()->getUrl('shop-front-cashregister', array(
            'CashRegisterType' => $this->getShortName(),
            'Act' => $this->getCorrectOperationAct($operation),
            'sign' => $sign
        ), true);                                              
    }
    
    /**
    * Возвращает URL для авторизации
    * 
    */
    protected function getTokenUrl()
    {
        return $this->getApiUrl().static::OPERATION_AUTH;
    }

    /**
     * Возвращает url для нужной операции
     *
     * @param string $operation - нужная операция
     *
     * @return string
     * @throws \RS\Exception
     */
    protected function getOperationUrl($operation)
    {
        return $this->getApiUrl().$this->getOption('group_code')."/".$operation."?tokenid=".urlencode($this->tokenid);
    }

    /**
     * Возвращает url для получения информации по чеку
     *
     * @param string $uuid - уникальный идентификатор чека от провайдера
     *
     * @return string
     * @throws \RS\Exception
     */
    protected function getReportUrl($uuid)
    {
        return $this->getApiUrl().$this->getOption('group_code')."/report/".$uuid."?tokenid=".urlencode($this->tokenid);
    }   

    /**
    * Подготавливает телефон для экспорта
    * 
    * @param string $phone - телефон
    *
    * @return string
    */
    protected function preparePhone($phone)
    {
        if ($phone[0] == "8"){//Уберём восьмёрку из номера
            $phone  = ltrim($phone, "8");    
        }
        $search  = array("+7", "(", ")", "-", "_", "*", "[", "]", " ");
        $replace = array("", "", "", "", "", "", "", "", "");
        $phone   = str_replace($search, $replace, $phone);
        
        return $phone;    
    }   
    
    /**
    * Генерирует уникальный идентификатор операции с чеком
    * 
    * @param integer $receipt_number - порядковый номер выбитого чека в рамках сессии
    *
    * @return string
    */
    protected function getReceiptExternalId($receipt_number = 0)
    {
        return sha1(\Setup::$SECRET_SALT.$this->transaction['id'].$receipt_number.time());
    }

    /**
     * Находит среди налогов НДС и возвращает его в виде идентификатора АТОЛ
     *
     * @param Tax[] $taxes
     * @param Address $address
     * @return string
     */
    protected function fetchVatTax($taxes, Address $address)
    {
        $tax     = new Tax();
        foreach ($taxes as $item){
            if ($item['is_nds']){
                $tax = $item;
                break;
            }
        }

        //Получим ставку
        $tax_rate = $tax->getRate($address);
        $tax_id   = static::TAX_NONE;
        switch(floatval($tax_rate)){
            case "10":
                $tax_id = ($tax['included']) ? static::TAX_VAT110 : static::TAX_VAT10;
                break;
            case "18":
                $tax_id = ($tax['included']) ? static::TAX_VAT118 : static::TAX_VAT18;
                break;
            case "0":
                $tax_id = static::TAX_VAT0;
                break;
        }
        return $tax_id;
    }

    /**
    * Возвращает правильный идентификатор налога у товара
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Catalog\Model\Orm\Product $product - объект товара
    *
    * @return string
    */
    protected function getRightTaxForProduct(\Shop\Model\Orm\Order $order, \Catalog\Model\Orm\Product $product)
    {
        $address = $order->getAddress();
        $tax_api = new \Shop\Model\TaxApi();
        $taxes   = $tax_api->getProductTaxes($product, $this->transaction->getUser(), $address);

        return $this->fetchVatTax($taxes, $address);
    }


    /**
     * Возвращает налог, который присутствует у доставки
     *
     * @param \Shop\Model\Orm\Order $order
     * @param \Shop\Model\Orm\Delivery $delivery
     */
    protected function getRightTaxForDelivery(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Delivery $delivery)
    {
        $address = $order->getAddress();

        $tax_api = new \Shop\Model\TaxApi();
        $taxes   = $tax_api->getDeliveryTaxes($delivery, $this->transaction->getUser(), $address);

        return $this->fetchVatTax($taxes, $address);
    }


    /**
     * Возвращает секцию данных о налогах
     *
     * @return array
     */
    protected function getItemTaxData($atol_tax_id)
    {
        return array('tax' => $atol_tax_id);
    }
    
    /**
    * Возвращает двумерный массив из товаров. Ключи это порции до 100 товаров. Значения, это список товаров.
    * 
    * @return array
    */
    protected function getReceiptsFromOrder()
    {
        $order = $this->transaction->getOrder();
        $cart  = $order->getCart();
        
        if (!$cart){  //Если заказ был в это время удалён.
            $this->addError(t('Заказ был удалён'));
            return array();
        }
        
        $list     = $cart->getProductItems();
        $cartdata = $cart->getCartData(false);

        //Определеним сколько порций
        $count = ceil(count($list)/static::MAX_LENGTH);
        
        $receipts = array();
        $total = 0;
        for($i=0; $i<$count; $i++){           
            $items_inside = 0; //Количество товаров внутри чека
            //Получим товары
            $products = array_slice($list, $i*static::MAX_LENGTH, static::MAX_LENGTH);
            
            $total = 0;
            $items = array();
            foreach ($products as $n=>$item){
                /**
                * @var \Catalog\Model\Orm\Product
                */
                $product     = $item['product']; 
                $barcode     = $product->getBarCode($item['cartitem']['offer']);
                $offer_title = $product->getOfferTitle($item['cartitem']['offer']);
                
                if ($product['title'] == $offer_title){ //Если наименования комплектаций совпадает, то покажем только название товара
                    $title = $product['title']; 
                }else{
                    $title = $offer_title ? $product['title']." [".$offer_title."]" : $product['title'];    
                }

                $price = $cartdata['items'][$n]['single_cost'] - round(($cartdata['items'][$n]['discount'] / $item['cartitem']['amount']), 2);
                $sum   = $price * $item['cartitem']['amount'];
                $total += $sum;

                $items[] = array(
                    'price' => (float)$price,
                    'name' => $title,
                    'text' => "",
                    'barcode' => $barcode,
                    'sum' => (float)$sum,
                    'quantity' => (float)$item['cartitem']['amount'],
                ) + $this->getItemTaxData($this->getRightTaxForProduct($order, $product));

                $items_inside++;
            }    
            $receipts[$i]['receipt']['items'] = $items;
            $receipts[$i]['receipt']['total'] = $total;
            
            //Тип оплаты
            $payment_type = array(
                'sum' => $total,
                'type' => 1  //Всегда онлайн оплата
            );      
            $receipts[$i]['receipt']['payments'] = array($payment_type);
        }
        
        //Добавим способы доставки
        $deliveries = $cart->getCartItemsByType(\Shop\Model\Cart::TYPE_DELIVERY);
        if (!empty($deliveries)){
            if (($items_inside < static::MAX_LENGTH)){
                $i -= 1;
            }else{
                $items = array();
                $total = 0;
            }
            
            foreach ($deliveries as $delivery){
                $price = (float)$delivery['price']; 
                $total = (float)($total + $price);
                $items[] = array(
                    'price' => $price,
                    'name' => $delivery['title'],
                    'text' => "",
                    'barcode' => "",
                    'sum' => $price,
                    'quantity' => 1,
                ) + $this->getItemTaxData($this->getRightTaxForDelivery($order, $order->getDelivery()));
            }
            if ($total > 0){ //Только если за доставку платили
                $receipts[$i]['receipt']['items'] = $items;
                $receipts[$i]['receipt']['total'] = $total;  
                //Тип оплаты
                $payment_type = array(
                    'sum' => $total,
                    'type' => 1  //Всегда онлайн оплата
                );      
                $receipts[$i]['receipt']['payments'] = array($payment_type);  
            }
        }
        
        return $receipts;
    } 
    
    
    
    /**
    * Функция возвращает по ключу значение заголовка параметра чека. Необходимо перегружать для каждого отдельного типа.
    * Т.к. там будут разные значения для заголовков. Используется в купе с extra_arr в чеке
    * 
    * @param string $key - ключ массив соответствия
    *
    * @return string
    */
    public function getReceiptInfoStringByKey($key)
    {
        $info_arr = array(
            'fiscal_receipt_number'     => t('Номер чека в смене'),
            'shift_number'              => t('Номер смены'),
            'receipt_datetime'          => t('Дата и время документа из ФН'),
            'total'                     => t('Итоговая сумма документа в рублях'),
            'fn_number'                 => t('Номер ФН'),
            'ecr_registration_number'   => t('Регистрационный номер ККТ'),
            'fiscal_document_number'    => t('Фискальный номер документа'),
            'fiscal_document_attribute' => t('Фискальный признак документа')
        );
        return isset($info_arr[$key]) ? $info_arr[$key] : $key;
    }

    /**
     * Вырезает из массива ключи, которые не являются публичными и не должны отображаться в чеках у пользователей
     */
    public function filterNonPublicInfo($array)
    {
        return array_diff_key($array, array('fiscal_receipt_number' => true, 'fns_site' => true, 'shift_number' => true));
    }
    
    /**
    * Добавляет в чек секцию со служебной информацией
    * 
    * @param array $receipt - чек для отправки
    * @param string $operation_type - тип операции
    * @param string $sign - подпись
    *
    * @return array
    */
    protected function getServicePart($receipt, $operation_type, $sign)
    {
        $receipt['document_type'] = $operation_type;

        $my_config = $this->getCashRegisterTypeConfig();
        $receipt['service']['inn']             = $this->getOption('inn'); //ИНН  
        $receipt['service']['callback_url']    = $this->getCallbackUrl($operation_type, $sign);
        $receipt['service']['payment_address'] = $my_config->domain ? $my_config->domain : $this->getCurrentDomainUrl();

        //Аттрибуты чека
        $sno = $this->getOption('sno', 0);
        if ($sno){
            $receipt['receipt']['attributes']['sno'] = $sno;
        }
        return $receipt;
    }
    
    
    /**
    * Добавляет ошибку при создании чека по её ответному коду
    * 
    * @param string $response - ответ сервера АТОЛ на опрерацию регистрации документа продажи, возврата
    * @return bool
    */
    protected function checkCreateReceiptError($response)
    {
        $error = '';
        if (isset($response['code']) && $response['code']) {
            switch ($response['code']) {
                case 1:
                    $error = t('Ошибка при парсинге JSON. Необходимо повторить запрос с корректными данными.');
                    break;
                case 2:
                    $error = t('Переданы пустые значения <group_code> и/или <operation>. Необходимо повторить запрос с корректными данными.');
                    break;
                case 3:
                    $error = t('Передано некорректное значение <operation>. Необходимо повторить запрос с корректными данными.');
                    break;
                case 4:
                    $error = t('Передан некорректный <tokenid>. Необходимо повторить запрос с корректными данными.');
                    break;
                case 5:
                    $error = t('Переданный <tokenid> не выдавался. Необходимо повторить запрос с корректными данными.');
                    break;
                case 6:
                    $error = t('Срок действия, переданного <tokenid> истёк (срок действия 24 часа). Необходимо запросить новый <tokenid>.');
                    break;
                case 10:
                    $error = t('Документ с переданными значениями <external_id> и <group_code> уже существует в базе. В ответе на ошибку будет передан UUID первого присланного чека с данными параметрами. Можно воспользоваться запросом на получение результат регистрации, указав UUID.');
                    break;
                default:
                    $error = t('Произошла неизвестная ошибка');
                    break;
            }

            $this->addError($error);
        }

        if (!empty($response['error'])){
            $error = $response['error'];
            $this->addError($error);
        }

        return $error != '';
    }


    protected function getClientInfo($receipt, $user)
    {
        $receipt['receipt']['attributes']['email'] = $user['e_mail'];
        $receipt['receipt']['attributes']['phone'] = $this->preparePhone($user['phone']);

        return $receipt;
    }

    /**
     * Выполняет запрос на создание чека продажи или возврата
     *
     * @param array $receipt - объект чека
     * @param string $sign - уникальный идентификатор операции с чеком
     * @param string $operation_type - тип чека
     * @throws \RS\Exception
     */
    protected function createReceiptRequest($receipt, $sign, $operation_type = 'sell')
    {
        $user = $this->transaction->getUser();
        if (empty($user['e_mail']) && empty($user['phone'])){
            $this->addError(t('Не указан E-mail или телефон пользователя'));
        }

        $receipt['timestamp']     = date("d.m.Y H:i:s");
        $receipt['external_id']   = $sign;
        $receipt['group_code']    = $this->group;

        //Служебный раздел
        $receipt = $this->getServicePart($receipt, $operation_type, $sign);
        //Сведения о пользователе
        $receipt = $this->getClientInfo($receipt, $user);

        if (!$this->hasError()){
            //Отправим запрос   
            $response = $this->createRequest($this->getOperationUrl($operation_type), $receipt, array(), true, 'POST');
            
            if (!$response){
                $this->addError(t('Произошла неизвестная ошибка'));
            }

            $this->checkCreateReceiptError($response);
            
            //Запишем сведения о транзакции по порции чека
            $receipt_transaction                   = new \Shop\Model\Orm\Receipt();
            $receipt_transaction['sign']           = $sign;                 
            $receipt_transaction['type']           = $operation_type;                 
            $receipt_transaction['provider']       = $this->getShortName();                 
            $receipt_transaction['transaction_id'] = $this->transaction['id']; 
            $receipt_transaction['total']          = $receipt['receipt']['total']; 
            $receipt_transaction['error']          = (isset($response['error']) && $response['error']) ? $response['error'] : ""; 
            $receipt_transaction['answer']         = serialize($response); 
            
            if (!$this->hasError()){
                $receipt_transaction['uniq_id'] = $response['uuid']; 
                $receipt_transaction['status']  = \Shop\Model\Orm\Receipt::STATUS_WAIT;  
            }else{
                $receipt_transaction['status']  = \Shop\Model\Orm\Receipt::STATUS_FAIL; 
                $receipt_transaction['error']   = $this->getErrorsStr(); 
            }  
            $receipt_transaction->insert();    
        }
    }


    /**
     * Отправляет запрос на создание чека по транзакции (Создние, возврат, корректировка)
     *
     * @param \Shop\Model\Orm\Transaction $transaction - объект транзакции
     * @param string $operation_type - тип чека
     *
     * @return boolean
     * @throws \RS\Exception
     */
    public function createReceipt(\Shop\Model\Orm\Transaction $transaction, $operation_type = 'sell')
    {
        $this->transaction = $transaction;
        $this->makeAuth();
        if (!$this->hasError()){ //Если удалось авторизоваться
            //Подготавливает запрос для чека
            if ($transaction['order_id']){ //Если это оплата заказа
                $receipts = $this->getReceiptsFromOrder();
            }else{ //Если это просто пополнение счёта
                $sum = $transaction['cost'];
                $items[] = array(
                    'price' => (float)$sum,
                    'name' => $transaction['reason'],
                    'barcode' => "",
                    'text' => "",
                    'sum' => (float)$sum,
                    'quantity' => 1,
                ) + $this->getItemTaxData(static::TAX_NONE);

                $receipts[0]['receipt']['items'] = $items;
                $receipts[0]['receipt']['total'] = (float)$sum;     
                //Тип оплаты
                $payment_type = array(
                    'sum' => (float)$sum,
                    'type' => 1  //Всегда онлайн оплата
                );
                $receipts[0]['receipt']['payments']  = array($payment_type);
            }
            
            //Пройдёмся по порциям цека и добавим недостающую информацию
            foreach ($receipts as $k=>$receipt){
                $sign = $this->getReceiptExternalId($k); //Уникальная подпись
                
                $this->createReceiptRequest($receipt, $sign, $operation_type);
            }
            if (!$this->hasError()){
                $transaction->no_need_check_sign = true;;
                $transaction['receipt'] = \Shop\Model\Orm\Transaction::RECEIPT_IN_PROGRESS;
                $transaction->update();
            }
        }
        return (!$this->hasError()) ? true : false;
    }

    /**
     * Обрабатывает дополнительную информацию о налогах и вписывает её в чек коррекции
     *
     * @param array $receipt
     * @param \RS\Orm\FormObject|array $data - объект с данными для чека коррекции
     */
    protected function getCorrectionTax($receipt, $data)
    {
        $receipt['correction']['tax'] = $data['tax'];

        return $receipt;
    }

    /**
     * Создаёт транзакцию на выставление чека коррекции в ОФД
     *
     * @param integer $transaction_id - id транзакции
     * @param \RS\Orm\FormObject|array $data - объект с данными для чека коррекции
     *
     * @return boolean
     * @throws \RS\Exception
     */
    public function createCorrectionReceipt($transaction_id, $data){
        $this->makeAuth();
        if (!$this->hasError()){ //Если удалось авторизоваться
            $sum = $data['sum'];

            $sign = $this->getReceiptExternalId(); //Уникальная подпись
                
            $receipt['timestamp']     = date("d.m.Y H:i:s");
            $receipt['external_id']   = $sign;
            
            //Служебный раздел
            $receipt = $this->getServicePart($receipt, static::OPERATION_SELL_CORRECTION, $sign);
            
            //Коррекция
            $sno = $this->getOption('sno', 0);
            if ($sno){
                $receipt['correction']['attributes']['sno'] = $sno;    
            }

            //Тип оплаты
            $payment_type = array(
                'sum' => (float)$sum,
                'type' => 1  //Всегда онлайн оплата
            );
            $receipt['correction']['payments'] = array($payment_type);

            $receipt = $this->getCorrectionTax($receipt, $data);
            
            //Отправим запрос
            $response = $this->createRequest($this->getOperationUrl(static::OPERATION_SELL_CORRECTION), $receipt, array(), true, 'POST');

            if (!$response){
                $this->addError(t('Произошла неизвестная ошибка'));
            }
            
            if (isset($response['code']) && $response['code']){
                $this->addCreateReceiptErrorByCode($response['code']);
            }

            $response_error = false;
            if (isset($response['error']) && $response['error']){
                $response_error = is_array($response['error']) ? var_export($response['error'], true) : $response['error'];
                $this->addError($response_error);
            }
            
            //Запишем сведения о транзакции по порции чека
            $receipt_transaction                   = new \Shop\Model\Orm\Receipt();
            $receipt_transaction['sign']           = $sign;                 
            $receipt_transaction['type']           = static::OPERATION_SELL_CORRECTION;                 
            $receipt_transaction['provider']       = $this->getShortName();                 
            $receipt_transaction['transaction_id'] = $transaction_id; 
            $receipt_transaction['total']          = (float)$sum; 
            $receipt_transaction['error']          = ($response_error) ? $response_error : "";
            $receipt_transaction['answer']         = serialize($response); 
            
            if (!$this->hasError()){
                $receipt_transaction['uniq_id'] = $response['uuid']; 
                $receipt_transaction['status']  = \Shop\Model\Orm\Receipt::STATUS_WAIT;  
            }else{
                $receipt_transaction['status']  = \Shop\Model\Orm\Receipt::STATUS_FAIL; 
                $receipt_transaction['error']   = $this->getErrorsStr(); 
            }  
            $receipt_transaction->insert();   
        }
        
        return (!$this->hasError()) ? true : false;
    }


    /**
     * Делает запрос на запрос статуса чека и возвращаетданные записывая их в чек, если произошли изменения
     *
     * @param \Shop\Model\Orm\Receipt $receipt - объект чека
     *
     * @return string|false
     * @throws \RS\Exception
     */
    public function getReceiptStatus(\Shop\Model\Orm\Receipt $receipt)
    {
        $this->makeAuth();
        if (!$this->hasError()){ //Если удалось авторизоваться
            $response = $this->createRequest($this->getReportUrl($receipt['uniq_id'])); 
            
            if (!$response){ //Если получить ответ не получилось, то делаем статус, что ещё ожидается ответ
                return 0;
            }  
            
            if (!empty($response['error']) && $response['status'] != "wait"){ //Если ошибок в чеке нет
                $this->addError($response['error']['text']);
                $receipt['status'] = \Shop\Model\Orm\Receipt::STATUS_FAIL;
                $receipt['error']  = $response['error']['text'];
                $receipt->update();
            }else{ //Если есть ошибки в чеке
                switch($response['status']){
                    case "wait": //Если чек ещё в статусе ожидаем
                        return 0;
                        break;
                    case "done": //Чек зарегистрирован
                        $receipt['status'] = \Shop\Model\Orm\Receipt::STATUS_SUCCESS;
                        $receipt->setExtraInfo('success_info', $response['payload']); //Сохраним данные чека
                        $receipt->update();
                        return true;
                        break;
                }
            }
        }
        return ($this->hasError()) ? $this->getErrorsStr() : true;
    }
    
    /**
    * Обрабавтывает результат обработки чека
    * 
    * @param \RS\Http\Request $url - объект текущего пришедшего запроса
    * @throws \RS\Exception
    *
    * @return string
    */
    public function onResult(\RS\Http\Request $url)
    {
        $sign = $url->request('sign', TYPE_STRING, "");
        
        //Поищем наш чек для изменения
        $receipt_api = new \Shop\Model\ReceiptApi();
        $receipt     = $receipt_api->getById($sign, 'sign');  
        
        if (!$receipt['id']){
            throw new \RS\Exception(t('Чек с таким идентификатором не найден.'));
        }
        
        //Проверим статус чека, если он в статусе ожидание, то запросим состояние чека
        $this->getReceiptStatus($receipt);
        return "OK";
    }
    
    
    /**
    * Функция обработки запроса от провайдера чека продажи
    * 
    * @param \RS\Http\Request $request - объект запроса
    *
    * @return string
    */
    public function onResultSell(\RS\Http\Request $url)
    {
        return $this->onResult($url);
    }  
    
    /**
    * Функция обработки запроса от провайдера чека возврата
    * 
    * @param \RS\Http\Request $url - объект запроса
    *
    * @return string
    */
    public function onResultRefund(\RS\Http\Request $url)
    {
        return $this->onResult($url);
    } 
    
    /**
    * Функция обработки запроса от провайдера чека корректировки
    * 
    * @param \RS\Http\Request $url - объект запроса
    *
    * @return string
    */
    public function onResultCorrection(\RS\Http\Request $url)
    {
        return $this->onResult($url);
    } 
    
    
    /**
    * Добавляет сообщение об ошибке
    * 
    * @param string $message - сообщение об ошибке
    * @param string $fieldname - название поля
    * @param string $form - техническое имя поля (например, атрибут name у input)
    */
    public function addError($message, $fieldname = null, $form = null)
    {
        if ($this->log){
            $this->log->append('[Error]: '.$message);   
        }
        parent::addError($message);
    }

    /**
     * Возвращает объект формы чека коррекции
     *
     * @return \RS\Orm\FormObject | false Если false, то это означает, что кассовый модуль не поддерживает чеки коррекции
     */
    public function getCorrectionReceiptFormObject()
    {
        //Получаем объект для отображения формы
        return new \RS\Orm\FormObject(new PropertyIterator(array(
            'transaction_id' => new Type\Integer(array(
                'description' => t('ID транзакции'),
                'hint' => t('Транзакция, для которой делается чек корректировки')
            )),
            'sum' => new Type\Varchar(array(
                'description' => t('Сумма корректировки'),
                'hint' => t('Только положительные числа'),
                'checker' => array(function($orm, $value, $error) {
                    if ($value > 0) {
                        return true;
                    } else {
                        return $error;
                    }
                }, t('Сумма должна быть больше нуля'))
            )),
            'tax' => new Type\Varchar(array(
                'description' => t('Налог'),
                'hint' => t('Указание налога для чека коррекции'),
                'list' => array(array(__CLASS__, 'getTaxesList'))
            )),
        )));
    }
}