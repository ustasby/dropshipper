<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\PaymentType;
use \RS\Orm\Type;
use \Shop\Model\Orm\Transaction;

/**
* Способ оплаты - Яндекс.Деньги
*/
class YandexMoney extends AbstractType
{
    const
        TAX_NDS_NONE = 1,
        TAX_NDS_0 = 2,
        TAX_NDS_10 = 3,
        TAX_NDS_18 = 4,
        TAX_NDS_110 = 5,
        TAX_NDS_118 = 6;
    
    protected
        $my_sign, //Подпись сформированная мной, на основе прищедших данных
        $sign_md5; //Подпись md5 транзакции.
    
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Яндекс.Касса');
    }
    
    /**
    * Возвращает описание типа оплаты. Возможен HTML
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Оплата через агрегатор платежей "Яндекс.Деньги"');
    }
    
    /**
    * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return 'yandexmoney';
    }

    /**
    * Возвращает true, если необходимо использовать 
    * POST запрос для открытия страницы платежного сервиса
    * 
    * @return bool
    */ 
    function isPostQuery()
    {
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
            'testmode' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Тестовый режим'),
                'checkboxview' => array(1,0),
            )),
            'shop_id' => new Type\Integer(array(
                'description' => t('Идентификатор магазина (shopId)')
            )),
            'only_email' => new Type\Integer(array(
                'description' => t('Отправлять только email пользователя'),
                'checkboxview' => array(1,0),
                'hint' => t('Номер телефона отправлен не будет, даже если он указан'),
            )),
            'shop_article_id' => new Type\Integer(array(
                'description' => t('Идентификатор товара (shopArticleId)'),
                'hint'        => t('shopArticleId необходимо задать тогда, когда используется несколько идентификаторов товара
                <br/>Выдаётся оператором Yandex.
                <br/>По умолчанию - пустое поле')
            )),
            'scid' => new Type\Integer(array(
                'description' => t('Идентификатор витрины магазина (scid)')
            )),
            'password' => new Type\Varchar(array(
                'description' => t('Секретное слово магазина (shopPassword)'),
                'length'      => 500,
            )),
            'payment_type' => new Type\Varchar(array(
                'description' => t('Тип метода оплаты'),
                'length'    => 10,
                'listFromArray' => array(array(
                    ''   => t('Умный платеж'),
                    'PC' => t('Яндекс.Деньги'),
                    'AC' => t('Банковская карта'),
                    'GP' => t('Терминал приёма платежей'),
                    'EP' => t('ЕРИП (Беларусь)'),
                    'MC' => t('Мобильный телефон'),
                    'MP' => t('Мобильный терминал (mPOS)'),
                    'WM' => 'WebMoney',
                    'SB' => t('Оплата через Сбербанк'),
                    'AB' => t('Оплата через Альфа-Клик'),
                    'MA' => t('Оплата через MasterPass'),
                    'PB' => t('Оплата через интернет-банк Промсвязьбанка'),
                    'QW' => t('Оплата через QIWI Wallet'),
                    'KV' => t('Оплата через КупиВкредит (Тинькофф Банк)'),
                    //'QP' => t('Оплата через сервис Доверительный платеж (Куппи.ру)'),
                    'CR' => t('Заплатить по частям')
                )),
                'template' => '%shop%/form/payment/yandexmoney/payment_type.tpl'
            )),
            'category_code' => new Type\Integer(array(
                'description' => t('Характеристика отвечающая за категорию товаров по версии Яндекс для банков<br/>Смотрите <a href="https://money.yandex.ru/i/forms/types_of_products.xls"></a>'),
                'maxLength'    => 11,
                'visible' => false,
                'list' => array(array('\Catalog\Model\PropertyApi','staticSelectList'),true),
            )),
            'cps_provider' => new Type\Varchar(array(
                'description' => t('Провайдер для терминалов'),
                'length'    => 10,
                'listFromArray' => array(array(
                    0       => t('Не выбрано'),
                    'SVZNY' => t('Связной'),
                    'EURST' => t('Евросеть'),
                    'OTHER' => t('Остальные сети'),
                )),
                'template' => '%shop%/form/payment/yandexmoney/cps_provider.tpl'
            )),
            'tax_system' => new Type\Integer(array(
                'description' => t('Система налогообложения магазина'),
                'hint' => t('Используется для передачи данных для чека по 54-ФЗ.<br>
                            Необходим только если у вас несколько систем налогообложения.'),
                'length' => 1,
                'listFromArray' => array(array(
                    '0' => t('- Не указана -'),
                    '1' => t('общая СН'),
                    '2' => t('упрощенная СН (доходы)'),
                    '3' => t('упрощенная СН (доходы минус расходы)'),
                    '4' => t('единый налог на вмененный доход'),
                    '5' => t('единый сельскохозяйственный налог'),
                    '6' => t('патентная СН')
                )),
            )),
            'nds_personal_account' => new Type\Integer(array(
                'description' => t('Налог, при пополнении лицевого счета'),
                'listFromArray' => array(array(
                    self::TAX_NDS_NONE => t('Без НДС'),
                    self::TAX_NDS_0 => t('НДС 0'),
                    self::TAX_NDS_10 => t('НДС 10% (включено в стоимость)'),
                    self::TAX_NDS_18 => t('НДС 18% (включено в стоимость)')
                ))
            )),
            'fix_rus_phone' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Исправлять номер телефона при отправке электронного чека (для Российских номеров)'),
                'hint' => t('- подменяет "8" на "+7" в начале номера<br>- если в номере 9 цифр - дописывает спереди "+7"'),
                'checkboxview' => array(1,0),
            )),
            
            '__help__' => new Type\Mixed(array(
                'description' => t(''),
                'visible' => true,  
                'template' => '%shop%/form/payment/yandexmoney/help.tpl'
            )),
        ));
        
        return new \RS\Orm\FormObject($properties);
    }
    
    
    /**
    * Возвращает true, если данный тип поддерживает проведение платежа через интернет
    * 
    * @return bool
    */
    function canOnlinePay()
    {
        return true;
    }
    
    /**
    * Возвращает URL для перехода на сайт сервиса оплаты
    * 
    * @param Transaction $transaction
    * @return string
    */
    function getPayUrl(Transaction $transaction)
    {
        $shp_item   = 0;
        $inv_id     = $transaction->id;
        $out_summ   = round($transaction->cost, 2);
        $inv_desc   = $transaction->reason;
        
        //Данные магазина
        $mrh_shop_id    = $this->getOption('shop_id');          //Id магазина
        $mrh_shopart_id = $this->getOption('shop_article_id');  //Id товара
        $mrh_scid       = $this->getOption('scid');             //Номер витрины магазина
        $payment_type   = $this->getOption('payment_type');     //Вид оплаты
        $cps_provider   = $this->getOption('cps_provider');     //Провайдер терминалов
        
        //Данные плательщика
        $user_id  = $transaction->user_id;
        $user     = new \Users\Model\Orm\User();
        
        $user->load($user_id);
        $cps_email = $user['e_mail'];
        
        //Сведения о маршрутах приёма информации
        $router = \RS\Router\Manager::obj();
        
        $result = $router->getUrl('shop-front-onlinepay', array(
           'Act'         => 'result',
           'PaymentType' => $this->getShortName(),
        ), true);

        $success = $router->getUrl('shop-front-onlinepay', array(
           'Act'         => 'success',
           'inv_id'      => $inv_id,
           'PaymentType' => $this->getShortName(),
        ), true);
        
        $fail = $router->getUrl('shop-front-onlinepay', array(
           'Act'         => 'fail',
           'inv_id'      => $inv_id,
           'PaymentType' => $this->getShortName(),
        ), true);

        $url = "https://money.yandex.ru/eshop.xml";

        if($this->getOption('testmode')){
            $url = "https://demomoney.yandex.ru/eshop.xml";
        }
        
        $params = array();
        $params['shopId']         = $mrh_shop_id;
        if ($mrh_shopart_id) $params['shopArticleId']  = $mrh_shopart_id; //Id товара
        $params['scid']           = $mrh_scid;
        
        $params['sum']            = $out_summ;
        $params['customerNumber'] = $user_id;
        if ($transaction->order_id) {
            $params['orderNumber']    = $transaction->getOrder()->order_num; //Получаем номер заказа
        }
        
        $params['shopSuccessURL'] = $success;
        $params['shopFailURL']    = $fail;
        $params['paymentType']    = $payment_type;
        if ($cps_provider) {
        $params['cps_provider']   = $cps_provider;
        }
        $params['cps_email']      = $cps_email;
        
        $params['inv_id']         = $inv_id;
        
        //Если это купи в кредит, то надо дописать некоторые дополнительные параметры
        if ($this->getOption('payment_type') == 'KV') {
            //мы платим не как пополнение лицевого счёт
            if ($transaction->order_id){
               $params = $this->addKVAdditionalOptions($transaction, $params); 
            }else{ //Если всё же поставили оплату, то выведем ошибку
               echo t("Вид оплаты КупиВКредит от Yandex невозможно использовать для пополнения счёта");
               exit(); 
            }
        }
        $params_for_check = $this->getParamsForFZ54Check($transaction);
        $params = $params + $params_for_check;
        
        $this->addPostParams($params); //Добавим пост параметры
        
        return $url;
    }
    
    /**
    * Возвращает дополнительные параметры для печати чека по ФЗ-54
    * 
    * @param \Shop\Model\Orm\Transaction $transaction
    * @return array
    */
    protected function getParamsForFZ54Check($transaction)
    {
        $user = $transaction->getUser();
        $base_currency = \Catalog\Model\CurrencyApi::getBaseCurrency();

        if (!empty($user['phone']) && !$this->getOption('only_email')) {
            $customer_contact = preg_replace(array('/[^\+\d]/'), array(''), $user['phone']);
            if ($this->getOption('fix_rus_phone')) { // если нужно, "исправляем" номер телефона
                $customer_contact = preg_replace(array('/(^9.{9}$)/', '/^8/'), array('+7\1', '+7'), $user['phone']);
            }
        } else {
            $customer_contact = $user['e_mail'];
        }
        
        $ym_merchant_receipt = array();
        $ym_merchant_receipt['customerContact'] = $customer_contact;
        if ($this->getOption('tax_system')) {
            $ym_merchant_receipt['taxSystem'] = $this->getOption('tax_system');
        }

        if ($transaction['order_id']) {
            //Оплата заказа
            $order = $transaction->getOrder();
            $cart = $order->getCart();
            if ($cart) {
                $address = $order->getAddress();
                $tax_api = new \Shop\Model\TaxApi();
                $products = $cart->getProductItems();
                foreach ($products as $product) {
                    $taxes = $tax_api->getProductTaxes($product['product'], $this->transaction->getUser(), $address);
                    $item['quantity'] = $product['cartitem']['amount'];
                    $item['price']['amount'] = round($product['cartitem']['single_cost'] - ($product['cartitem']['discount'] / $product['cartitem']['amount']), 2);
                    $item['price']['currency'] = $base_currency['title'];
                    $item['tax'] = $this->getRightTax($taxes, $address);
                    $item['text'] = mb_substr(str_replace(array("\"","'"),'`',\RS\Helper\Tools::unEntityString($product['product']['title'])), 0, 64);

                    $ym_merchant_receipt['items'][] = $item;
                }
                $delivery = $cart->getCartItemsByType(\Shop\Model\Cart::TYPE_DELIVERY);
                foreach ($delivery as $delivery_item) {
                    $taxes = $tax_api->getDeliveryTaxes($order->getDelivery(), $this->transaction->getUser(), $address);
                    $item['quantity'] = 1;
                    $item['price']['amount'] = $delivery_item['price'] - $delivery_item['discount'];
                    $item['price']['currency'] = $base_currency['title'];
                    $item['tax'] = $this->getRightTax($taxes, $address);
                    $item['text'] = mb_substr(str_replace(array("\"","'"),'`',\RS\Helper\Tools::unEntityString($delivery_item['title'])), 0, 64);

                    $ym_merchant_receipt['items'][] = $item;
                }
            }
        } else {
            //Пополнение лицевого счета
            $item['quantity'] = 1;
            $item['price']['amount'] = round($transaction->cost, 2);
            $item['price']['currency'] = $base_currency['title'];
            $item['tax'] = $this->getOption('nds_personal_account');
            $item['text'] = $transaction->reason;
            $ym_merchant_receipt['items'][] = $item;
        }
        // флаг JSON_UNESCAPED_UNICODE появился в PHP 5.4
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $encoded_data = json_encode($ym_merchant_receipt, JSON_UNESCAPED_UNICODE);
        } else { // костыль JSON_UNESCAPED_UNICODE для PHP 5.3
            $encoded_data = preg_replace_callback('/\\\\u(\w{4})/', function ($matches) {
                                return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
                            }, json_encode($ym_merchant_receipt));
        }
        $return = array('ym_merchant_receipt' => $encoded_data);
        
        return $return;
    }
    
    /**
    * Добавление дополнительных сведений для КупиВКредит
    * 
    * @param Transaction $transaction - текущая транзакция
    * @param array $params
    * @return array
    */
    function addKVAdditionalOptions(Transaction $transaction, $params=array())
    {
        $params['seller_id'] = $params['shopId']; //seller_id Равен ShopId
        $order = $transaction->getOrder();
        //А также добавим сведения о товарах 
        $cart = $order->getCart();
        $products = $cart->getProductItems();
        $cartdata = $cart->getPriceItemsData();
        $i=0;
        foreach ($products as $n=>$item){
           /**
           * @var \Catalog\Model\Orm\Product
           */
           $product     = $item['product']; 
           $product->fillProperty(); //Заполним характеристики
           $offer_title = $product->getOfferTitle($item['cartitem']['offer']);
           $title       = $offer_title ? $offer_title : $product['title'];
           if (mb_strlen($title)>256){ //Если превышает лимит, то обрежем
              $title = mb_substr($title, 0, 255); 
           }
           
           $params['category_code_'.$i]        = $product->getPropertyValueById($this->getOption('category_code'), null, false);
           $params['goods_name_'.$i]        = $title;
           $params['goods_description_'.$i] = $title;
           $params['goods_quantity_'.$i]    = $item['cartitem']['amount'];
           $params['goods_cost_'.$i]        = $cartdata['items'][$n]['single_cost'];
           $i++;
        }
        
        //Добавим сумму доставки если есть                        
        $delivery_items = $cart->getCartItemsByType('delivery');
        if (!empty($delivery_items)){
           foreach ($delivery_items as $n=>$item){
              if ($item['price']>0){
                 $params['category_code_'.$i]        = "11111";
                 $params['goods_name_'.$i]        = t('Доставка');
                 $params['goods_description_'.$i] = t('Цена за доставку');
                 $params['goods_quantity_'.$i]    = 1;
                 $params['goods_cost_'.$i]        = $item['price']; 
              }
           } 
        }
        
        
        return $params;
    }
    
    /**
    * Возвращает ID заказа исходя из REQUEST-параметров соотвествующего типа оплаты
    * Используется только для Online-платежей
    * 
    * @return mixed
    */
    function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        return $request->request('inv_id', TYPE_INTEGER, false);
    }
    
    /**
    * Возвращет ответ в XML Версии 1.0
    * 
    * @param string $action - команда от сервера
    * @param array $params  - параметры для XML
    */
    private function sendXMLAnswer($action, $params)
    {
       $dom     = new \DOMDocument('1.0','utf-8');
       $element = $dom->createElement($action."Response");
       
       foreach($params as $key=>$value){
          $element->setAttribute($key,$value);
       }
       
       $dom->appendChild($element);
       $dom->formatOutput = true;  //Для сохранения в XML
       \RS\Application\Application::getInstance()->headers->addHeader('Content-type','application/xml');
       
       return $dom->saveXML();
        
    }

    /**
    * Проверяет подпись запроса
    * 
    * @param string $action                            - команда от сервера
    * @param \Shop\Model\Orm\Transaction $transaction  - объект транзакции
    * @param \RS\Http\Request $request                 - объект запроса с значениями
    */
    private function checkSign($action, \Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        $this->sign_md5 = strtoupper($request->request("md5", TYPE_STRING));                     //md5 сервера
        
        $orderSumAmount = $request->request("orderSumAmount", TYPE_STRING);          //сумма заказа за вычетом комиссии Оператора
        $orderSumCPay   = $request->request("orderSumCurrencyPaycash", TYPE_STRING); //Код валюты для суммы, получаемой Контрагентом на р/с
        $orderSumBPay   = $request->request("orderSumBankPaycash", TYPE_STRING);     //Код процессингового центра Оператора для суммы
        $shopId         = $this->getOption('shop_id');                               //Идентификатор Контрагента(Магазина)
        $invoiceId      = $request->request("invoiceId", TYPE_STRING);               //Уникальный номер транзакции в программно-аппаратном комплексе Оператора
        $customerNumber = $request->request("customerNumber", TYPE_STRING);          //Идентификатор плательщика 
        $shopPassword   = $this->getOption('password');                              //Пароль магазина 
        
        
        
        // Вычисление подписи
        $this->my_sign    = strtoupper(md5("$action;$orderSumAmount;$orderSumCPay;$orderSumBPay;$shopId;$invoiceId;$customerNumber;$shopPassword"));
        
        // Проверка корректности подписи
        return $this->my_sign === $this->sign_md5;
    }
    
    function onResult(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        $action = $request->request('action',TYPE_STRING);
                                                                  
        // Проверка подписи запроса
        if(!$this->checkSign($action, $transaction, $request)){
            $exception = new ResultException(t('Не правильная подпись ответа. Невозможно идентифицировать хэш.'),1);
            $exception->setResponse($this->sendXMLAnswer($action,array(
               'performedDatetime' => date('c'),
               'code'              => 1,
               'invoiceId'         => $request->request('invoceId',TYPE_STRING),
               'shopId'            => $this->getOption('shop_id'),
               'message'           => t('Не правильная подпись ответа.'),
               'techMessage'       => t('Невозможно идентифицировать хэш.'),
            )));
            
            throw $exception;
        }
        
        if ($action == 'cancelOrder') { //Если пришёл запрос на отмену оплаты заказа
            $exception = new ResultException(t('Платёж по транзакции был отменён'),1);
            $exception->setResponse($this->sendXMLAnswer($action, array(
               'performedDatetime' => date('c'),
               'code'              => 0,
               'invoiceId'         => $request->request('invoiceId', TYPE_STRING),
               'shopId'            => $this->getOption('shop_id'),
               'message'           => t('Пришлё запрос на отмену заказа.'),
               'techMessage'       => t('Платёж по транзакции был отменён.'),
            )));
            
            throw $exception;
        } 
        
        
        // Проверка, соответсвует ли сумма платежа сумме, сохраненной в транзакции
        if($request->request('orderSumAmount', TYPE_FLOAT) != floatval($transaction->cost)){
            $exception = new ResultException(t('Не правильная сумма платежа %0. Сумма платежа не совпадает с запрошенной изначально.',array($request->request('orderSumAmount', TYPE_FLOAT))),1);
            $exception->setResponse($this->sendXMLAnswer($action,array(
               'performedDatetime' => date('c'),
               'code'              => 1,
               'invoiceId'         => $request->request('invoiceId', TYPE_STRING),
               'shopId'            => $this->getOption('shop_id'),
               'message'           => t('Не правильная сумма платежа.'),
               'techMessage'       => t('Сумма платежа не совпадает с запрошенной изначально.'),
            )));
            
            throw $exception;
        }

        $result = $this->sendXMLAnswer($action,array(
           'performedDatetime' => date('c'),
           'code'              => 0,
           'invoiceId'         => $request->request('invoiceId', TYPE_STRING),
           'shopId'            => $this->getOption('shop_id'),
        ));
        
        if ($action != 'paymentAviso') {
            $exception = new ResultException(t('Успешный ответ для checkOrder'));
            $exception->setUpdateTransaction(false);
            $exception->setResponse($result);
            throw $exception; //Ответ для CheckOrder (не будет завершать транзакцию)
        } else {
            
            //Сохраняем комиссию 
            $transaction['comission'] = $request->request('orderSumAmount', TYPE_STRING) - $request->request('shopSumAmount', TYPE_STRING);
            $transaction->update();

            return $result; //Успешный ответ для Aviso
        }
    }
    
    /**
    * Возвращает правильный идентификатор налога
    * 
    * @param array(\Shop\Model\Orm\Tax) $taxes - массив налогов
    * @param \Shop\Model\Orm\Address $address - адрес используемый для расчёта
    */
    private function getRightTax(array $taxes, \Shop\Model\Orm\Address $address)
    {
        $tax = new \Shop\Model\Orm\Tax();
        foreach ($taxes as $item){
            if ($item['is_nds']){
                $tax = $item;
                break; 
            } 
        }
        //Получим ставку
        $tax_rate = $tax->getRate($address);
        if (!empty($tax_rate)) {
            switch((int) $tax_rate){
                case 10:
                    $tax_id = ($tax['included']) ? self::TAX_NDS_110 : self::TAX_NDS_10;
                    break;
                case 18:
                    $tax_id = ($tax['included']) ? self::TAX_NDS_118 : self::TAX_NDS_18;
                    break;
                case 0:
                    $tax_id = self::TAX_NDS_0;
                    break;
                default:
                    $tax_id = self::TAX_NDS_NONE;
            }
        } else {
            $tax_id = self::TAX_NDS_NONE;
        }
        return $tax_id;
    }
}

