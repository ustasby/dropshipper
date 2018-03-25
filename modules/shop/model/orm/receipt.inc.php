<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;
use Shop\Model\CashRegisterApi;
use \Shop\Model\Orm\Order;
use \Shop\Model\Orm\Payment;
use \Users\Model\Orm\User;
use \Shop\Model\PaymentType\PersonalAccount;

/**
* Чеки по транзакциям
*/
class Receipt extends \RS\Orm\OrmObject
{
    protected static
        $table = 'receipt';
        
    const STATUS_SUCCESS = 'success';      
    const STATUS_FAIL    = 'fail';  
    const STATUS_WAIT    = 'wait'; 
    
    const TYPE_SELL       = 'sell';      
    const TYPE_REFUND     = 'sell_refund';  
    const TYPE_CORRECTION = 'sell_correction'; 
    
    private $transaction;
    
    protected
        $cache_payment;
        
        
    function _init()
    {
        parent::_init()->append(array(
                'site_id' => new Type\CurrentSite(),
                'sign' => new Type\Varchar(array(
                    'description' => t('Подпись чека'),
                    'index' => true
                )),
                'uniq_id' => new Type\Varchar(array(
                    'description' => t('Идентификатор транзакции от провайдера'),
                    'index' => true
                )),
                'type' => new Type\Enum(
                array(
                    self::TYPE_SELL, 
                    self::TYPE_REFUND,
                    self::TYPE_CORRECTION
                ), 
                array(
                    'allowEmpty'    => false,
                    'description'   => t('Тип чека'),
                    'listFromArray' => array(array(
                        self::TYPE_SELL       => t('Чек продажи'),
                        self::TYPE_REFUND     => t('Чек возврата'),   
                        self::TYPE_CORRECTION => t('Чек корректировки')    
                    )),
                    'visible' => false
                )), 
                'provider' => new Type\Varchar(array(
                    'description' => t('Провайдер'),
                    'maxLength' => 50,
                )),
                'url' => new Type\Varchar(array(
                    'description' => t('Ссылка на чек покупателю'),
                )),
                'dateof' => new Type\Datetime(array(
                    'description' => t('Дата транзакции'),
                    'visible' => false
                )),              
                'transaction_id' => new Type\Integer(array(
                    'maxLength' => '11',
                    'description' => t('ID связанной транзакции'),
                )), 
                'total' => new Type\Decimal(array(
                    'description' => t('Сумма в чеке'),
                    'maxLength' => 20,
                    'decimal' => 2
                )), 
                'status' => new Type\Enum(
                array(
                    self::STATUS_SUCCESS, 
                    self::STATUS_FAIL,
                    self::STATUS_WAIT
                ), 
                array(
                    'allowEmpty'    => false,
                    'description'   => t('Статус чека'),
                    'listFromArray' => array(array(
                        self::STATUS_SUCCESS => t('Успешно'),
                        self::STATUS_FAIL    => t('Ошибка'),   
                        self::STATUS_WAIT    => t('Ожидание ответа провайдера')    
                    )),
                    'visible' => false
                )), 
                'error' => new Type\Text(array(
                    'description' => t('Ошибка'),
                )),
                'extra' =>  new Type\Text(array(
                    'description' => t('Дополнительное поле для данных'),
                    'visible' => false,
                )),
                'extra_arr' => new Type\ArrayList(array(
                    'visible' => false
                ))
        ));
        
        $this->addIndex(array('transaction_id', 'provider'), self::INDEX_KEY);
    }

    /**
     * Вызывается после загрузки объекта
     * @return void
     */
    function afterObjectLoad()
    {
        $this['extra_arr'] = array();
        if (!empty($this['extra'])) {
            $this['extra_arr'] = unserialize($this['extra']);
        }
    }
    
    /**
    * Возвращает информацию из секции extra
    * 
    * @param string $key - ключ для конкретного массива экстраданных
    * @return array
    */
    function getExtaInfo($key = null)
    {
        if (!$key){
            return $this['extra_arr'];       
        }
        return isset($this['extra_arr'][$key]) ? $this['extra_arr'][$key] : array();
    }
    
    
    /**
    * Добавляет extra информацию в секцию extra по ключу
    * 
    * @param string $key - ключ для записи
    * @param mixed $data - данные для записи
    */
    function setExtraInfo($key, $data)
    {
        $extra_arr         = $this['extra_arr'];
        $extra_arr[$key]   = $data;
        $this['extra_arr'] = $extra_arr;
    }
    
    
    /**
    * Возращает объект транзакции 
    * @return \Shop\Model\Orm\Transaction
    */
    function getTransaction()
    {
        if($this->transaction == null){
            $this->transaction = new Transaction($this['transaction_id']);
        }
        return $this->transaction;
    }


    /**
    * Действия перед записью
    * 
    * @param string $save_flag - insert или update
    */
    function beforeWrite($save_flag)
    {
        $this->before_write_receipt = new \Shop\Model\Orm\Receipt($this['id']);
        if ($save_flag == self::INSERT_FLAG){
            $this['dateof'] = date("Y-m-d H:i:s");    
        }
        $this['extra']  = serialize($this['extra_arr']);
    }
    
    /**
    * Действия после записи
    * 
    * @param string $save_flag - insert или update
    */
    function afterWrite($save_flag)
    {
        if (in_array($this['type'], array(self::TYPE_SELL, self::TYPE_REFUND))){
            $transaction = $this->getTransaction();
            $transaction->no_need_check_sign = true;
            switch($this['status']){
                case self::STATUS_SUCCESS:
                    if ($this['type'] == self::TYPE_SELL){
                        $transaction['receipt'] = $transaction::RECEIPT_SUCCESS;    
                    }else if ($this['type'] == self::TYPE_REFUND){
                        $transaction['receipt'] = $transaction::RECEIPT_REFUND_SUCCESS;  
                    }
                    //Отправим чек пользователю
                    $notice = new \Shop\Model\Notice\ReceiptToUser();
                    $notice->init($this);
                    \Alerts\Model\Manager::send($notice);   
                    break;
                case self::STATUS_FAIL:
                    $transaction['receipt'] = $transaction::RECEIPT_FAIL; 
                    break;
            }
            $transaction->update();
            //Если, эта транзакция для заказа, то сменим ему нужный статус
            if ($this['status'] == self::STATUS_SUCCESS && $this['type'] == self::TYPE_SELL && ($transaction->getOrder()->id)){
                $order = $transaction->getOrder();
                $order['status'] = $transaction->getPayment()->success_status;
                $order->update();
            }
        }
        
        //Если в чеке произошла ошибка, то отправим уведомление об этом
        if (empty($this->before_write_receipt['error']) && !empty($this['error'])){
            $notice = new \Shop\Model\Notice\ReceiptErrorToAdmin();
            $notice->init($this);
            \Alerts\Model\Manager::send($notice);   
        }
    }

    /**
     * Возвращает URL для просмотра выписаного чека
     *
     * @return string
     */
    function getReceiptUrl()
    {
        if ($this['status'] == self::STATUS_SUCCESS
            && $this['type'] != self::TYPE_CORRECTION)
        {
            $api = new CashRegisterApi();
            return $api->getReceiptUrl($this);
        } else {
            return '';
        }
    }
}