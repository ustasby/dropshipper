<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\PaymentType;
use \Shop\Model\Orm\Transaction;

/**
* Абстрактный класс типа оплаты.
*/
abstract class AbstractType
{
    private 
        $opt = array();
            
    protected
        $post_params = array(), //Параметры для POST запроса
        $order,
        $transaction;
    
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    abstract function getTitle();
    
    /**
    * Возвращает описание типа оплаты. Возможен HTML
    * 
    * @return string
    */
    abstract function getDescription();
    
    /**
    * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
    * 
    * @return string
    */
    abstract function getShortName();
    
    /**
    * Возвращает true, если данный тип поддерживает проведение платежа через интернет
    * 
    * @return bool
    */
    abstract function canOnlinePay();        
    
    /**
    * Возвращает true, если можно обращаться к ResultUrl для данного метода оплаты.
    * Обычно необходимо для способов оплаты, которые применяются только на мобильных приложениях.
    * По умолчанию возвращает то же, что и canOnlinePay.
    * 
    * @return bool
    */
    function isAllowResultUrl()
    {
        return $this->canOnlinePay();
    }
    
    /**
    * Устанавливает настройки, которые были заданы в способе оплаты.
    * В случае, если расчетный класс вызывается у готового заказа, 
    * то дополнительно устанавливаются order и transaction
    * 
    * @param mixed $opt Настройки расчетного класса
    * @param \Shop\Model\Orm\Order $order Заказ
    * @param \Shop\Model\Orm\Transaction $transaction Транзакция
    */
    function loadOptions(array $opt = null, \Shop\Model\Orm\Order $order = null, \Shop\Model\Orm\Transaction $transaction = null)
    {
        $this->opt          = $opt;
        $this->order        = $order;
        $this->transaction  = $transaction;
    }
    
    /**
    * Получает значение опции способа оплаты
    * 
    * @param string $key - ключ опции
    * @param mixed $default - значение по умолчанию
    */
    function getOption($key = null, $default = null)
    {
        if ($key == null) return $this->opt;
        return isset($this->opt[$key]) ? $this->opt[$key] : $default;
    }
    
    /**
    * Возвращает true, если необходимо использовать 
    * POST запрос для открытия страницы платежного сервиса
    * 
    * @return bool
    */ 
    function isPostQuery()
    {
        return false;
    }
    
    
    /**
    * Добавляет один параметр поста в определённый ключ
    * 
    * @param string $key - ключ 
    * @param string|array $value - значение
    */
    function addPostParam($key, $value){
       $this->post_params[$key] = $value; 
    }
    
    /**
    * Добавляет параметры для Пост запроса
    * 
    * @param array $post_params - массив параметров
    */
    function addPostParams(array $post_params){
       $this->post_params +=  $post_params;
    }
    
    
    /**
    * Возвращает параметры - ключ значение для выполнения поста
    * @return array
    */
    function getPostParams()
    {
         return $this->post_params;
    }

    function setOption($key_or_array = null, $value = null)
    {
        if (is_array($key_or_array)) {
            $this->opt = $key_or_array + $this->opt;
        } else {
            $this->opt[$key_or_array] = $value;
        }
    }
    
     /**
    * Функция срабатывает после создания заказа
    * 
    * @param \Shop\Model\Orm\Order $order     - объект заказа
    * @param \Shop\Model\Orm\Address $address - Объект адреса
    * @return mixed
    */
    function onOrderCreate(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {}
    
    
    /**
    * Возвращает ORM объект для генерации формы в административной панели или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {}
    
    /**
    * Возвращает дополнительный HTML для админ части в заказе
    * @return string
    */
    function getAdminHTML(\Shop\Model\Orm\Order $order)
    {
        return "";
    }
    
    
    /**
    * Действие с запросами к заказу для получения дополнительной информации от доставки
    * 
    */
    function actionOrderQuery(\Shop\Model\Orm\Order $order)
    {}
    
    /**
    * Возвращает HTML форму данного типа оплаты, для ввода дополнительных параметров
    * 
    * @return string
    */
    function getFormHtml()
    {
        if ($params = $this->getFormObject()) {
            $params->getPropertyIterator()->arrayWrap('data');
            $params->getFromArray((array)$this->opt);
            $params->setFormTemplate(strtolower(str_replace('\\', '_', get_class($this))));
            $module = \RS\Module\Item::nameByObject($this);
            $tpl_folder = \Setup::$PATH.\Setup::$MODULE_FOLDER.'/'.$module.\Setup::$MODULE_TPL_FOLDER;
            
            return $params->getForm(array('payment_type' => $this), null, false, null, '%system%/coreobject/tr_form.tpl', $tpl_folder);
        }
    }
    
    /**
    * Возвращает список названий документов и ссылки, по которым можно открыть данные документы, 
    * генерируемых данным типом оплаты
    * 
    * @return array
    */
    function getDocsName()
    {
        
    }
    
    /**
    * Возвращает URL к печтной форме документа
    * 
    * @param string $doc_key - ключ документа
    * @param bool $absolute - если true, то вернуть абсолютный URL
    */
    function getDocUrl($doc_key = null, $absolute = false)
    {
        // Если это транзакия для оплаты заказа
        if($this->order){
            return \RS\Router\Manager::obj()->getUrl('shop-front-documents', array('doc_key' => $doc_key, 'order' => $this->order['hash']), $absolute);
        }
        
        // Если это транзакция для пополнения лицевого счета
        if($this->transaction){
            return \RS\Router\Manager::obj()->getUrl('shop-front-documents', array('doc_key' => $doc_key, 'transaction' => $this->transaction['sign']), $absolute);
        }
        
        throw new \Exception(t('Невозможно сформировать URL. Не передан ни объект заказа, ни объект транзакции'));
    }
    
    /**
    * Возвращает html документа для печати пользователем
    * 
    * @param mixed $dockey
    */
    function getDocHtml($dockey = null)
    {}
    
    /**
    * Вызывается единоразово при оформлении заказа
    * 
    * @return void
    */
    function onCreateOrder()
    {}
    
    /**
    * Возвращает объект компании, которая предоставляет услуги
    * 
    * @return \Shop\Model\Orm\Company
    */
    function getCompany()
    {
        return false;
    }
    
    /**
    * Возвращает URL для перехода на сайт сервиса оплаты для совершения платежа
    * Используется только для Online-платежей
    * 
    * @param Transaction $transaction
    * @return string
    */
    function getPayUrl(Transaction $transaction)
    {}
    
    /**
    * Возвращает ID заказа исходя из REQUEST-параметров соотвествующего типа оплаты
    * Используется только для Online-платежей
    * 
    * @param \RS\Http\Request $request
    * @return mixed
    */
    function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        return false;
    }
    
    /**
    * Вызывается при оплате сервером платежной системы. 
    * Возвращает строку - ответ серверу платежной системы.
    * В случае неверной подписи бросает исключение
    * Используется только для Online-платежей
    * 
    * @param Transaction $transaction
    * @param \RS\Http\Request $request
    * @return string 
    */
    function onResult(Transaction $transaction, \RS\Http\Request $request)
    {}

    /**
    * Вызывается при открытии страницы успеха, после совершения платежа 
    * В случае неверной подписи бросает исключение
    * Используется только для Online-платежей
    * 
    * @param Transaction $transaction
    * @param \RS\Http\Request $request
    * @return void 
    */
    function onSuccess(Transaction $transaction, \RS\Http\Request $request)
    {}

    /**
    * Вызывается при открытии страницы неуспешного проведения платежа 
    * Используется только для Online-платежей
    * 
    * @param Transaction $transaction
    * @param \RS\Http\Request $request
    * @return void 
    */
    function onFail(Transaction $transaction, \RS\Http\Request $request)
    {}
}