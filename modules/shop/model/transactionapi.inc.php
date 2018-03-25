<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;
use \Shop\Model\Orm\Transaction,
    \Shop\Model\Orm\Order,
    \Shop\Model\PaymentType\PersonalAccount;

/**
* API функции для работы с танзакциями
*/
class TransactionApi extends \RS\Module\AbstractModel\EntityList
{
    protected static
        $types;

    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Transaction, 
        array(
            'multisite' => true
        ));
    }
    
    /**
    * Создаёт чек и оправляет его на ККТ
    * 
    * @param Orm\Transaction $transaction - объект транзакции
    * @param string $operation_type - тип чека
    */
    function createReceipt(\Shop\Model\Orm\Transaction $transaction, $operation_type = 'sell')
    {
        //Создадим чек
        $cashRegisterApi = new \Shop\Model\CashRegisterApi();
        return $cashRegisterApi->createReceipt($transaction, $operation_type);
    }
    
    /**
    * Создать транзакцию для данного заказа. Создает новую транзакцию со статусом new
    * Используется перед редиректом на внешнюю страницу оплаты
    * 
    * @param int $order_id
    * @return Orm\Transaction
    */
    function createTransactionFromOrder($order_id)
    {
        $config   = \RS\Config\Loader::byModule($this);
        $order    = \Shop\Model\Orm\Order::loadByWhere(array(
          'order_num' => $order_id
        )); 
        
        if($order_id && !$order->id){
            throw new \RS\Exception(t('Не найден заказ %0', array($order_id)));
        }
        
        $transaction = new \Shop\Model\Orm\Transaction();
        $transaction->dateof    = date('Y-m-d H:i:s');
        $transaction->payment   = $order->getPayment()->id;
        // Флаг, означающий влияет ли эта транзакция на баланс лицевого счета
        $transaction->personal_account   = $order->getPayment()->class == PersonalAccount::SHORT_NAME;
        $transaction->order_id  = $order->id;
        $transaction->user_id   = $order->user_id;
        $transaction->cost      = $order->getTotalPrice(false);
        $transaction->reason    = t('Оплата заказа №%0', array($order->order_num));
        $transaction->insert();
        $transaction->sign = self::getTransactionSign($transaction);
        $transaction->update();
        return $transaction;
    }
    
    /**
    * Создать транзацкию для Добавления/Списания средств с лицевого счета
    * 
    * @param int $user_id
    * @param int $payment_id
    * @param string $cost
    * @return Orm\Transaction
    */
    function createTransactionForAddFunds($user_id, $payment_id, $cost)
    {
        $transaction = new \Shop\Model\Orm\Transaction();
        $transaction->dateof    = date('Y-m-d H:i:s');
        $transaction->payment   = $payment_id;
        $transaction->personal_account   = 1;
        $transaction->order_id  = 0; 
        $transaction->user_id   = $user_id;
        $transaction->cost      = $cost;
        $transaction->reason    = t('Пополнение баланса лицевого счета');
        $transaction->insert();
        $transaction->sign = self::getTransactionSign($transaction);
        $transaction->update();
        return $transaction;
    }
    
    /**
    * Создает новую транзакцию на изменение баланса лицевого счета с учетом данных, полученных из $data.
    * В отличие от метода addFunds позволяет сохранять и произвольные поля к транзакции, 
    * добавленные через другие модули к объекту \Shop\Model\Orm\Transaction
    */
    function changeBalance($data)
    {
        $element = $this->getElement();
        
        if (!$data['cost']) {
            $element->addError(t('Не указана сумма'), 'cost');
        }
        
        if (!$data['reason']) {
            $element->addError(t('Не указано назначение платежа'), 'reason');
        }
        
        if ($element->hasError()) return false;
        
        if ($element->save(null, array(), $data)) {
            $element['sign'] = self::getTransactionSign($element);
            $element->update();
            return true;
        }
        return false;
    }
    
    /**
    * Добавить/Списать средства с лицевого счета
    * 
    * @param int $user_id ИД пользователя
    * @param string $amount Сумма пополнения (списания)
    * @param bool $writeoff Флаг списания
    * @param string $reason Причина
    * @param bool $ckeck_rights Проверять права на запись для модуля Магазин
    * @param string $entity Сущность, к которой привязана транзакция (произвольный идентификатор)
    * @param integer $entity_id ID сущности, к которой привязана транзакция
    * @param integer $payment_id ID способа оплаты, по умолчанию 0 - нет привязки к способу оплаты
    * 
    * entity и entity_id может использоваться для выборки транзакций по необходимому критерию, 
    * если такие критерии были записаны при создании транзакций.
    * 
    * @return Transaction|bool(false)
    */
    function addFunds($user_id, $amount, $writeoff, $reason, $ckeck_rights = true, $entity = null, $entity_id = null, $payment_id = 0)
    {
        if($ckeck_rights){
            if ($error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
                $this->addError($error);
            }
        }
        
        if(!$amount){
            $this->addError(t('Не указана сумма'), t('Сумма'), 'amount');
        }
        if($amount < 0){
            $this->addError(t('Сумма не может быть отрицательной'), t('Сумма'), 'amount');
        }
        if($this->hasError()){
            return false;
        }

        try{
            $transaction = $this->createTransactionForAddFunds($user_id, $payment_id, $writeoff ? -$amount : $amount);
            $transaction->reason = $reason;
            $transaction->status = \Shop\Model\Orm\Transaction::STATUS_SUCCESS;
            $transaction->entity = $entity;
            $transaction->entity_id = $entity_id;
            $transaction->update();
            return $transaction;
        }catch(\Exception $e){
            $this->addError($e->getMessage());
            return false;
        }
    }
    
    /**
    * Разпознать в запросе идентификатор транзакции. Каждый конкретный тип оплаты это делает по своему.
    * 
    * @param mixed $payment_type Короткое имя типа оплаты
    * @param \RS\Http\Request $request
    * @return Orm\Transaction
    */
    function recognizeTransactionFromRequest($payment_type, \RS\Http\Request $request)
    {
        $pay_api = new \Shop\Model\PaymentApi();
        // Получаем ассоциативный массив типов оплаты. Ключ - коротное название типа оплаты. Значение - объект типа оплаты.
        $payment_types = $pay_api->getTypes();
        //
        if(!isset($payment_types[$payment_type])){
            throw new \RS\Exception(t('Неверный тип оплаты: %0', array($payment_type)));
        }
        if(!($payment_types[$payment_type] instanceof \Shop\Model\PaymentType\AbstractType)){
            throw new \RS\Exception(t('Тип оплаты должен быть наследником PaymentType\AbstractType: %0', array($payment_type)));
        }
        if(!$payment_types[$payment_type]->isAllowResultUrl()){
            throw new \RS\Exception(t('Тип оплаты не поддерживает online платежи: %0', array($payment_type)));
        }
        
        // Просим объект типа оплаты распознать ID транзакции из REQUEST. 
        $transaction_id = $payment_types[$payment_type]->getTransactionIdFromRequest($request);
        
        if(!$transaction_id){
            throw new \RS\Exception(t("Не передан идентификатор транзакции"));
        }
        $transaction = new \Shop\Model\Orm\Transaction((int)$transaction_id);
        if(!$transaction->id){
            throw new \RS\Exception(t("Транзакция с идентификатором %0 не найдена", array($transaction_id)));
        }
        return $transaction;
    }
    
    /**
    * Возвращает подпись баланса пользователя
    * 
    * @param string $balance
    * @param int $user_id
    * @return string
    */
    static function getBalanceSign($balance, $user_id)
    {
        if($balance == 0) return "";
        $parts = array();
        $parts[] = round($balance, 2);
        $parts[] = (int)$user_id;
        $parts[] = \Setup::$SECRET_KEY;
        return sha1(join('&', $parts));
    }
    
    /**
    * Возвращает подпись транзакции
    * 
    * @param Orm\Transaction $transaction
    * @return string
    */
    static function getTransactionSign(\Shop\Model\Orm\Transaction $transaction)
    {
        if(!$transaction->id) throw new \RS\Exception(t('Невозможно подписать транзакцию с нулевым идентификатором'));
        $parts = array();
        $parts[] = (int)$transaction->id;
        $parts[] = (int)$transaction->personal_account;
        $parts[] = (int)$transaction->user_id;
        $parts[] = round($transaction->cost, 2);
        $parts[] = \Setup::$SECRET_KEY;
        return sha1(join('&', $parts));

    }

    /**
    * Возвращает баланс пользователя исходя из суммы транзакций
    * 
    * @param mixed $user_id
    * @param mixed $except_transaction_ids
    * @return double
    */
    static function getBalance($user_id, array $except_transaction_ids = array())
    {
        // Получаем "Приход"
        $q = \RS\Orm\Request::make();
        $q->select('SUM(`cost`) as income');
        $q->from(new Transaction);
        $q->where(array('user_id' => $user_id));
        $q->where(array('personal_account' => 1));
        $q->where(array('order_id' => 0));
        $q->where(array('status' => 'success'));
        if(!empty($except_transaction_ids)){
            $q->where('`id` NOT IN('.join(',', $except_transaction_ids).')');
        }
        $result = $q->exec()->fetchSelected(null, 'income');
        $income = reset($result);

        // Получаем "Расход"
        $q = \RS\Orm\Request::make();
        $q->select('SUM(`cost`) as debit');
        $q->from(new Transaction);
        $q->where(array('user_id' => $user_id));
        $q->where(array('personal_account' => 1));
        $q->where('order_id != 0');
        $q->where(array('status' => 'success'));
        if(!empty($except_transaction_ids)){
            $q->where('`id` NOT IN('.join(',', $except_transaction_ids).')');
        }
        $result = $q->exec()->fetchSelected(null, 'debit');
        $debit = reset($result);

        return $income - $debit;
    }
    
    /**
    * Установка фильтра для получения только транзацкий поолнения/списания с лицевого счета
    * 
    */
    function setPersonalAccountTransactionsFilter()
    {
        $this->queryObj()->select = 'A.*';
        $this->queryObj()->leftjoin(new \Shop\Model\Orm\Payment, "A.payment=P.id", "P");
        $this->queryObj()->where("(A.order_id = 0 OR P.class='".PersonalAccount::SHORT_NAME."')");
        $this->queryObj()->where(array('user_id' => \RS\Application\Auth::getCurrentUser()->id));
    }
}