<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\PaymentType;

/**
* Квитанция по форме ПД-4
*/
class FormPd4 extends AbstractType
{
 /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Квитанция ПД-4');
    }
    
    /**
    * Возвращает описание типа оплаты. Возможен HTML
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Формирует квитанцию на оплату через банк');
    }
    
    /**
    * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return 'formpd4';
    }
    
    /**
    * Возвращает список названий документов и ссылки, по которым можно открыть данные документы, 
    * генерируемых данным типом оплаты
    * 
    * @return array
    */
    function getDocsName()
    {
        return array(
            'pd4' => array(
                'title' => t('Квитанция')
            )
        );
    }
    
    /**
    * Возвращает true, если данный тип поддерживает проведение платежа через интернет
    * 
    * @return bool
    */
    function canOnlinePay()
    {
        return false;
    }
    
    /**
    * Возвращает html документа для печати пользователем
    * 
    * @param mixed $dockey
    */    
    function getDocHtml($dockey = null)
    {        
        $view = new \RS\View\Engine();
        
        // Квитанция для оплаты заказа
        if($this->order){
            $view->assign('order', $this->order);
            return $view->fetch('%shop%/payment/pd4.tpl');
        }
        // Квитанция для оплаты транзации пополнения счета
        if($this->transaction){
            $view->assign('transaction', $this->transaction);
            return $view->fetch('%shop%/payment/pd4_transaction.tpl');
        }
        
        throw new \Exception(t('Невозможно сформировать документ. Не передан ни объект заказа, ни объект транзакции'));
    }    
    
    /**
    * Возвращает URL для перехода на сайт сервиса оплаты для совершения платежа
    * 
    * @param Transaction $transaction
    * @return string
    */
    function getPayUrl(\Shop\Model\Orm\Transaction $transaction)
    {
        return $this->getDocUrl('order');
    }    
    
    
}
?>
