<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

/**
* Апи для работы с кассами онлайн
*/
class CashRegisterApi
{
    const
        //Список платформ ОФД
        PLATFORM_PLATFORM_OFD = 'platformofd',
        PLATFORM_FIRST_OFD    = '1-ofd',
        PLATFORM_OFD_YA       = 'ofd-ya',
        PLATFORM_SBIS         = 'sbis',
        PLATFORM_OFD_RU       = 'ofd.ru',
        PLATFORM_TAXCOM       = 'taxcom',
        PLATFORM_YANDEX_OFD   = 'yandexofd';
    
    protected
        $shop_config;   
        
    
    public static  
        $types; //Массив типов онлайн касс 

    /**
     * CashRegisterApi constructor.
     * @throws \RS\Exception
     */
    function __construct()
    {
        $this->shop_config = \RS\Config\Loader::byModule($this);
    }

    /**
     * Возвращает список из типов модулей интеграции с кассами онлайн
     *
     * @return array
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     */
    public static function getTypes()
    {
       if (self::$types === null) {
            $event_result = \RS\Event\Manager::fire('cashregister.gettypes', array());
            $list = $event_result->getResult();
            self::$types = array();
            foreach($list as $cashregister_type_object) {
                if (!($cashregister_type_object instanceof \Shop\Model\CashRegisterType\AbstractType)
                    && !($cashregister_type_object instanceof \Shop\Model\CashRegisterType\AbstractProxy)) {
                    throw new \RS\Exception(t('Тип интеграции с ККТ онлайн должен быть наследником \Shop\Model\CashRegisterType\AbstractType или \Shop\Model\CashRegisterType\AbstractProxy'));
                }
                self::$types[$cashregister_type_object->getShortName()] = $cashregister_type_object;
            }
       }
        
       return self::$types;
    }
    
    /**
    * Возвращает список провайдеров касс для выпадающего списка
    * 
    */
    public static function getStaticTypes()
    {
        $arr = array('' => 'Не выбрано');
        $list = self::getTypesAssoc();  
        if (!empty($list)){
            foreach($list as $key=>$item){
                $arr[$key] = $item;
            }
        }
        
        return $arr;
    }

    /**
     * Возвращает массив ключ => название типа доставки
     *
     * @return array
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     */
    public static function getTypesAssoc()
    {
        $_this = new self();
        $result = array('' => 'Не выбрано');
        foreach($_this->getTypes() as $key => $object) {
            $result[$key] = $object->getTitle();
        }
        return $result;
    }

    /**
     * Возвращает объект типа онлайн касс по идентификатору
     *
     * @param string $name - короткий идентификатор класса онлайн касс
     * @return mixed|CashRegisterType\Stub
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     */
    public static function getTypeByShortName($name)
    {
        $_this = new self();
        $list = $_this->getTypes();
        return isset($list[$name]) ? $list[$name] : new CashRegisterType\Stub($name);
    }

    /**
     * Возвращает текущий класс обмена информацией с кассами
     *
     * @return \Shop\Model\CashRegisterType\AbstractType
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     */
    function getCurrentCashRegisterClass()
    {
        $cash_register = $this->getTypeByShortName($this->shop_config['cashregister_class']);
        return $cash_register;
    }

    /**
     * Создаёт чек для ККТ и отправляет его на ККТ
     *
     * @param Orm\Transaction $transaction - объект транзакции
     * @param string $operation_type - тип чека
     * @return bool|string
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     */
    function createReceipt(\Shop\Model\Orm\Transaction $transaction, $operation_type = 'sell')
    {
        $cash_register = $this->getCurrentCashRegisterClass();    
        if ($cash_register instanceof \Shop\Model\CashRegisterType\Stub){
            $cash_register->addError(t('Укажите провайдера ККТ для транзакции'));
        }else{
            $cash_register->createReceipt($transaction, $operation_type);
        }
        
        if ($cash_register->hasError()){
            return $cash_register->getErrorsStr();
        }
        return true;
    }
    
    
    /**
    * Делает возврат средств заказа по онлайн чеку из успешной транзакции  
    * 
    * @param Orm\Order $order - объект заказа
    */
    function makeOrderRefund(\Shop\Model\Orm\Order $order)
    {
        //Получим успешную транзакцию
        $transaction_api = new \Shop\Model\TransactionApi();
        $transaction = $transaction_api->setFilter('order_id', $order['id'])
                                       ->setFilter('status', Orm\Transaction::STATUS_SUCCESS)
                                       ->getFirst();
        if ($transaction['id']){
            $transaction_api->createReceipt($transaction, \Shop\Model\CashRegisterType\AbstractType::OPERATION_SELL_REFUND);
        }
    }
    
    
    /**
    * Производит запрос на получение чека по транзакции принадлежащей переданному заказу
    * 
    * @param Orm\Order $order - объект заказа
    */
    function makeOrderReceipt(\Shop\Model\Orm\Order $order)
    {
        //Получим успешную транзакцию
        $transaction_api = new \Shop\Model\TransactionApi();
        /**
         * @var Orm\Transaction $transaction
         */
        $transaction = $transaction_api->setFilter('order_id', $order['id'])
                                       ->setFilter('status', Orm\Transaction::STATUS_SUCCESS)
                                       ->getFirst();
        if ($transaction['id']){
            $transaction_api->createReceipt($transaction);
        }
    }
    
    /**
    * Возвращает список ОФД для списка выбора
    * @return array
    */
    public static function getStaticOFDList()
    {
        return array(
            self::PLATFORM_PLATFORM_OFD => t('Платформа ОФД'),
            self::PLATFORM_FIRST_OFD => t('Первый ОФД'),
            self::PLATFORM_OFD_YA => t('ОФД-Я'),
            self::PLATFORM_SBIS => t('сбис'),
            self::PLATFORM_OFD_RU => t('OFD.RU'),
            self::PLATFORM_TAXCOM => t('ТАКСКОМ'),
            self::PLATFORM_YANDEX_OFD => t('Яндекс.ОФД')
        );
    }
    
    /**
    * Возвращает ссылку для проверки своего чека
    * 
    * @param string $ofd_type - тип ОФД
    * @return string
    */
    public static function getOFDReceiptUrlMask($ofd_type)
    {
        switch($ofd_type)
        {
            case self::PLATFORM_PLATFORM_OFD: 
                $url = "https://lk.platformaofd.ru/web/noauth/cheque?fn=%fn_number&fp=%fiscal_document_attribute";
                break;
            case self::PLATFORM_FIRST_OFD: 
                $url = "https://consumer.1-ofd.ru/#/landing";
                break;     
            case self::PLATFORM_OFD_YA: 
                $url = "https://ofd-ya.ru/check";
                break;
            case self::PLATFORM_SBIS: 
                $url = "https://ofd.sbis.ru";
                break;
            case self::PLATFORM_OFD_RU: 
                $url = "https://ofd.ru/checkinfo";
                break;
            case self::PLATFORM_TAXCOM: 
                $url = "http://taxcom.ru/ofd/";
                break;
            case self::PLATFORM_YANDEX_OFD: 
                $url = "http://ofd.yandex.ru/";
                break;
            default:
                $url = "";
                break;
        }
        
        return $url;
    }
    
    
    /**
    * Возвращает массив сведений о чеке в виде массива ключ=>значение
    * 
    * @param \Shop\Model\Orm\Receipt $receipt - чек для которого нужно сделать ссылку на провайдера
    * @return array
    */
    private function getReceiptExtraInfoArray(\Shop\Model\Orm\Receipt $receipt)
    {
        $extra_info = array();
        $info = $receipt->getExtaInfo('success_info');
        foreach ($info as $key=>$value)
        {
            $extra_info[$key] = $value;
        }
        
        $values = $receipt->getValues();
        foreach ($values as $key=>$value)
        {
            if (is_string($value) || is_integer($value)){
                $extra_info[$key] = $value; 
            }
        }
        return $extra_info;
    }
    
    
    /**
    * Возвращает URL для просмотра выписаного чека
    * 
    * @param \Shop\Model\Orm\Receipt $receipt - чек для которого нужно сделать ссылку на провайдера
    * @return string
    */
    function getReceiptUrl(\Shop\Model\Orm\Receipt $receipt)
    {
        $url  = self::getOFDReceiptUrlMask($this->shop_config['ofd']);
        $extra_info = $this->getReceiptExtraInfoArray($receipt);
        
        foreach ($extra_info as $key=>$value)
        {
            $url = str_replace("%".$key, $value, $url);
        }
        
        return $url;
    }
}
