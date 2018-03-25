<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;
use \Shop\Model\Orm\Order;
use \Shop\Model\Orm\Payment;
use Shop\Model\UserStatusApi;
use \Users\Model\Orm\User;
use \Shop\Model\PaymentType\PersonalAccount;

class Transaction extends \RS\Orm\OrmObject
{
    public
        $no_need_check_sign = false; //Эта транзакция до записи новых значений
        
    protected static
        $table = 'transaction';  
        
    //Статусы транзакции
    const STATUS_NEW                 = 'new';    
    const STATUS_SUCCESS             = 'success';    
    const STATUS_FAIL                = 'fail';  
      
    
    //Статусы чека
    const NO_RECEIPT                  = 'no_receipt'; //Чека пока не создано
    const RECEIPT_IN_PROGRESS         = 'receipt_in_progress'; //Чека пока не создано
    const RECEIPT_SUCCESS             = 'receipt_success'; //Чек успешно выбит      
    const RECEIPT_REFUND_SUCCESS      = 'refund_success';   
    const RECEIPT_FAIL                = 'fail'; //Если была ошибка в чеке 
    
    private $order;
    private $user;
    private $receipt;      
    
    protected
        $cache_payment;
        
    function _init()
    {
        parent::_init()->append(array(
                'site_id' => new Type\CurrentSite(),
                'dateof' => new Type\Datetime(array(
                    'description' => t('Дата транзакции'),
                    'visible' => false
                )),
                'user_id' => new Type\User(array(
                    'maxLength' => '11',
                    'description' => t('Пользователь'),
                    'template' => '%shop%/form/transaction/user.tpl'
                )),                
                'order_id' => new Type\Integer(array(
                    'maxLength' => '11',
                    'description' => t('ID заказа'),
                    'visible' => false
                )),  
                'personal_account' => new Type\Integer(array(
                    'maxLength' => '1',
                    'description' => t('Транзакция изменяющая баланс лицевого счета'),
                    'visible' => false
                )),  
                'cost' => new Type\Decimal(array(
                    'maxLength' => '15',
                    'decimal' => 2,
                    'description' => t('Сумма'),
                )),
                'comission' => new Type\Decimal(array(
                    'maxLength' => 15,
                    'decimal' => 2,
                    'description' => t('Сумма комиссии платежной системы')
                )),
                'payment' => new Type\Integer(array(
                    'description' => t('Тип оплаты'),
                    'visible' => false
                )),
                'reason' => new Type\Text(array(
                    'description' => t('Назначение платежа'),
                )),
                'error' => new Type\Varchar(array(
                    'description' => t('Ошибка'),
                    'visible' => false
                )),
                'status' => new Type\Enum(
                    array(
                        self::STATUS_NEW, 
                        self::STATUS_SUCCESS, 
                        self::STATUS_FAIL,
                    ), 
                    array(
                    'allowEmpty'    => false,
                    'description'   => t('Статус транзакции'),
                    'listFromArray' => array(array(
                        self::STATUS_NEW                 => t('Платеж инициирован'),
                        self::STATUS_SUCCESS             => t('Платеж успешно завершен'),
                        self::STATUS_FAIL                => t('Платеж завершен с ошибкой')        
                    )),
                    'visible' => false
                )),   
                'receipt' => new Type\Enum(
                    array(
                        self::NO_RECEIPT,
                        self::RECEIPT_IN_PROGRESS, 
                        self::RECEIPT_SUCCESS, 
                        self::RECEIPT_REFUND_SUCCESS,
                        self::RECEIPT_FAIL
                    ), 
                    array(
                    'allowEmpty'    => false,
                    'description'   => t('Последний статус получения чека'),
                    'listFromArray' => array(array(
                        self::NO_RECEIPT => t('Чек пока не получен'),
                        self::RECEIPT_IN_PROGRESS => t('Чек в очереди на получение'), 
                        self::RECEIPT_SUCCESS => t('Чек получен'), 
                        self::RECEIPT_REFUND_SUCCESS => t('Чек возвратата получен'),   
                        self::RECEIPT_FAIL => t('Ошибка в последнем чеке')   
                    )),
                    'default' => self::NO_RECEIPT,
                    'visible' => false
                )),  
                'refunded' =>  new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Дополнительное поле для данных'),
                    'checkboxview' => array(1, 0),
                    'default' => 0,
                    'visible' => false,
                )),                             
                'sign' => new Type\Varchar(array(
                    'description' => t('Подпись транзакции'),
                    'visible' => false
                )),
                'entity' => new Type\Varchar(array(
                    'description' => t('Сущность к которой привязана транзакция'),
                    'maxLength' => 50,
                    'visible' => false
                )),
                'entity_id' => new Type\Bigint(array(
                    'description' => t('ID сущности, к которой привязана транзакция'),
                    'visible' => false
                )),
                'extra' =>  new Type\Varchar(array(
                    'maxLength' => '4096',
                    'description' => t('Дополнительное поле для данных'),
                    'visible' => false,
                )),
                'extra_arr' => new Type\ArrayList(array(
                    'visible' => false
                )),
                'cashregister_last_operation_uuid' => new Type\Varchar(array(
                    'description' => t('Последний уникальный идентификатор полученный в ответ от кассы'),
                    'visible' => false
                )),
        ));
        
        $this->addIndex(array('entity', 'entity_id'), self::INDEX_KEY);
    }

    /**
     * Вызывается после загрузки объекта
     * @return void
     */
    function afterObjectLoad()
    {
        if (!empty($this['extra'])) {
            $this['extra_arr'] = unserialize($this['extra']);
        }
    }


    public function beforeWrite($save_flag)
    {
        $this['extra'] = serialize($this['extra_arr']);

        if($save_flag == self::INSERT_FLAG || $this->no_need_check_sign){
            return;
        }
        if(!$this->checkSign()){
            throw new \Exception(t('Неверная подпись транзакции %0. Изменение транзакции невозможно', array($this->id)));
        }
    }
    
    public function afterWrite($save_flag)
    {
        if ($this->no_need_check_sign){
            return;
        }
        $user = $this->getUser();
        
        if ($user->id){
            $transApi = new \Shop\Model\TransactionApi();
            $real_balance = $transApi->getBalance($user->id);
                                 
            // Перезагрузка объекта пользователя
            $user = $user->loadSingle($user->id);
            
            // Проверяем подпись баланса пользователя
            if(!$user->checkBalanceSign()){
                throw new \Exception(t('Неверная подпись баланса у пользователя id: %0', array($user->id)));
            }
            
            // Если баланс по сумме транзакций отличается от баланса, сохраненного в поле balance у пользователя
            if($user->getBalance() != $real_balance){
                // Проверяем верен ли старый баланс
                $old_balance = $transApi->getBalance($user->id, array($this->id));                  // Получаем сумму на счету без учета этой транзакции
                $sign        = \Shop\Model\TransactionApi::getBalanceSign($old_balance, $this->user_id);   // Формируем подпись к старом балансу
                if($user->balance_sign == $sign){                                                   // Проверяем верна ли подпись
                    // Устанавливаем новый баланс пользователю
                    $user->balance      = $real_balance;
                    $user->balance_sign = \Shop\Model\TransactionApi::getBalanceSign($real_balance, $this->user_id);
                    $user->update();
                }
                else{
                    throw new \Exception(t('Нарушение целостности истории транзакций'));
                }
            } 
        }
        
        //Отправляем уведомление о пополнении лицевого счёта
        if ($save_flag == self::INSERT_FLAG 
            && (!$this['status'] || $this['status'] == self::STATUS_NEW) 
            && $this['cost']>0) { //если новая и баланс на пополнение
            $notice = new \Shop\Model\Notice\NewTransaction();
            $notice->init($this, $user);  
            \Alerts\Model\Manager::send($notice); 
        }   
    }
    
    /**
    * Проверка подписи транзакции
    * 
    * @return bool
    */
    public function checkSign()
    {
        if(!$this->id) throw new \Exception(t('Невозможно подписать транзакцию с нулевым идентификатором'));
        $ok = $this->sign == \Shop\Model\TransactionApi::getTransactionSign($this);
        return $ok;
    }
    
    /**
    * Возращает объект заказа 
    * @return \Shop\Model\Orm\Order
    */
    public function getOrder()
    {
        if($this->order == null){
            $this->order = new Order($this->order_id);
        }
        return $this->order;
    }
    
    /**
    * Возвращает объект пользователя
    * @return \Users\Model\Orm\User
    */
    public function getUser()
    {
        if($this->user == null){
            if ($this->order_id>0){
                $this->user = $this->getOrder()->getUser();
            }else{
                $this->user = new User($this->user_id);    
            }
        }
        return $this->user;
    }
    
    /**
    * Возвращает объект способа оплаты
    * 
    * @return Payment
    */
    function getPayment()
    {
        if ($this->cache_payment === null) {
            $this->cache_payment = new Payment($this['payment'], true, $this->getOrder()->id ? $this->getOrder() : null, $this);
        }
        
        return $this->cache_payment;
    }
    
    /**
    * Возвращает привязанные к транзакции чеки
    * 
    */
    function getReceipts()
    {
        return \RS\Orm\Request::make()
                        ->from(new \Shop\Model\Orm\Receipt())
                        ->where(array(
                            'transaction_id' => $this->id
                        ))->objects();
    }
    
    /**
    * Возвращает URL для перехода на сайт сервиса оплаты для совершения платежа
    * 
    * @return string
    */
    function getPayUrl()
    {
        return $this->getPayment()->getTypeObject()->getPayUrl($this);
    }
    
    /**
    * Возвращает стоимость транзакции с учетом текущих параметров
    * 
    * @param bool $use_currency - если true, то значение будет возвращено в текущей валюте, иначе в базовой
    * @param bool $format - если true, то форматировать возвращаемое значение, приписывать символ валюты
    * @return string
    */
    function getCost($use_currency = false, $format = false)
    {
        $cost = ($use_currency) ? \Catalog\Model\CurrencyApi::applyCurrency($this['cost']) : $this['cost'];
        return $format ? \RS\Helper\CustomView::cost($cost, \Catalog\Model\CurrencyApi::getCurrecyLiter()) : $cost;
    }
    
    /**
    * Вызывается при оплате.
    * Возвращает строку ответ серверу оплаты
    * 
    * @param \RS\Http\Request $request
    * @return string
    */
    function onResult(\RS\Http\Request $request)
    {
        if($this->status != self::STATUS_NEW){
            throw new \Exception(t('Попытка повторной оплаты транзакции'));
        }     
        
        $response = "";
        try{
            // Вызываем способ оплаты
            $response = $this->getPayment()->getTypeObject()->onResult($this, $request);
        }
        catch(\Exception $e){
            
            // Устанавливаем статус транзакции - платеж завершен с ошибкой
            $is_result_exception = $e instanceof \Shop\Model\PaymentType\ResultException;
            if (!$is_result_exception || $e->canUpdateTransaction()) {
                $this['status']   = self::STATUS_FAIL;      
                $this['error']    = $e->getMessage();       // Записываем текст ошибки
                $this->update();
            }
            
            // Если исключение содержит отдельным свойстовм текст ответа серверу
            if( $is_result_exception ){
                return $e->getResponse();               // Возращаем сохраненный в исключении ответ серверу
            }
            else{
                return $e->getMessage();                // Возвращаем текст ошибки как ответ серверу оплаты
            }
        }
        
        // Устанавливаем статус транзакции - платеж успешно завершен
        $this['status']   = self::STATUS_SUCCESS; 
        $this->update();        

        // Если это транзакция оплаты заказа  
        if($this->order_id){                        
            $order = $this->getOrder();

            //Проверим нужно ли исползовать статус запроса чека, если оплата прошла.
            if ($this->getPayment()->isReceiptEnabled()){
                $order->status = UserStatusApi::getStatusIdByType(UserStatus::STATUS_NEEDRECEIPT);
            } else {
                if ($this->getPayment()->success_status) {
                    // Выставляем статус который указан в настройках типа оплаты
                    $order->status = $this->getPayment()->success_status;
                }
            }

            $order->is_payed = 1;                                 // Ставим пометку "Оплачен"  
            $order->is_exported = 0;                              // Помечаем на выгрузку
            $order->update();
            
            $notice = new \Shop\Model\Notice\OrderPayed;
            $notice->init($order);  
            \Alerts\Model\Manager::send($notice); 
        }else{    
            $notice = new \Shop\Model\Notice\AddBalance();
            $notice->init($this, $this->getUser());  
            \Alerts\Model\Manager::send($notice);             
        }

        //Инициируем пробитие чека через online кассу
        if ($this->getPayment()->isReceiptEnabled()) {
            $transaction_api = new \Shop\Model\TransactionApi();
            $transaction_api->createReceipt($this);
        }

        return $response;  // Возращаем текст ответа серверу оплаты
    }
}