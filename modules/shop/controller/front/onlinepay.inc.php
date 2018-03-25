<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;
use \RS\Application\Auth as AppAuth;

/**
* Контроллер для обработки Online-платежей
*/
class OnlinePay extends \RS\Controller\Front
{
    /**
    * Шаг 6. Редирект на страницу оплаты (переход к сервису online-платежей)
    * Вызывается только в случае Online типа оплаты.
    * Данный action выполняется при нажатии на кнопку "Перейти к оплате"
    * Перед редиректом создается новая транзакция со статусом 'new'. Её идентификатор будет фигурировать в URL оплаты
    * 
    */
    function actionDoPay()
    {
        $request = $this->url;
        $this->wrapOutput(false);
        $order_id       = $this->url->request('order_id', TYPE_STRING);
        $transactionApi = new \Shop\Model\TransactionApi();
        $transaction    = $transactionApi->createTransactionFromOrder($order_id);
        
        if ($transaction->getPayment()->getTypeObject()->isPostQuery()){ //Если нужен пост запрос
           $url  = $transaction->getPayUrl();
           $this->view->assign(array(
              'url' => $url,
              'transaction' => $transaction
           )); 
           $this->wrapOutput(false);
           return $this->result->setTemplate("%shop%/onlinepay/post.tpl");
        }else{    
           $this->redirect($transaction->getPayUrl()); 
        }
    }
    
    /**
    * Особый action, который вызвается с сервера online платежей
    * В REQUEST['PaymentType'] должен содержаться строковый идентификатор типа оплаты
    * 
    * http://САЙТ.РУ/onlinepay/{PaymentType}/result/
    */
    function actionResult()
    {
        $request = $this->url;
        // Логируем запрос
        $log_file = \RS\Helper\Log::file(\Setup::$PATH.\Setup::$STORAGE_DIR.DS.'logs'.DS.'Onlinepay_Result.log');
        $log_data = 'request - '.$request->getSelfUrl().' - GET: '.serialize($request->getSource(GET)).' - POST: '.serialize($request->getSource(POST));
        $log_file->append($log_data);
        
        $this->wrapOutput(false);
        $payment_type   = $this->url->get('PaymentType', TYPE_STRING);
        $transactionApi = new \Shop\Model\TransactionApi();
        $response       = null;
        try{
            $transaction    = $transactionApi->recognizeTransactionFromRequest($payment_type, $this->url);
            $response       = $transaction->onResult($this->url);
        }
        catch(\Exception $e){
            return $e->getMessage();       // Вывод ошибки
        }
        // Логируем ответ
        $log_file->append('response - '.$response);
        return $response;
    }

    /**
    * Страница извещения об успешном совершении платежа
    * 
    * http://САЙТ.РУ/onlinepay/{PaymentType}/success/
    */
    function actionSuccess()
    {
        $request = $this->url;
        $payment_type = $this->url->get('PaymentType', TYPE_STRING);
        $transactionApi = new \Shop\Model\TransactionApi();
        try{
            $transaction = $transactionApi->recognizeTransactionFromRequest($payment_type, $this->url);
            $transaction->getPayment()->getTypeObject()->onSuccess($transaction, $this->url);
        }
        catch(\Exception $e){
            return $e->getMessage();       // Вывод ошибки
        }
        
        // redirect на партнёрский сайт
        if (\RS\Module\Manager::staticModuleEnabled('partnership')) {
            if (!empty($transaction['partner_id']) && $transaction['partner_id'] != \Partnership\Model\Api::getCurrentPartner()->id) {
                $partner = new \Partnership\Model\Orm\Partner($transaction['partner_id']);
                $this->redirect($this->url->getProtocol().'://'.$partner->getMainDomain().$this->url->selfUri());
            }
        }
        
        $this->view->assign('transaction', $transaction);
        //Проверим, если это заказ и у типа оплаты стоит, флаг выбивания чека
        if ($transaction->getPayment()->create_cash_receipt){
            $this->view->assign(array(
                'need_check_receipt' => true
            ));
            $this->app->addJsVar('receipt_check_url', $this->router->getUrl('shop-front-onlinepay', array(
                'Act' => 'checktransactionreceiptstatus',
                'id' => $transaction['id'],
            )));
        }
        
        $this->app->setBodyClass('payment-ok', true); //Добавим класс для проверки в мобильным приложением
        return $this->result->setTemplate( 'onlinepay/success.tpl' );
    }
    
    /**
    * Страница извещения о неудачи при проведении платежа (например если пользователь отказался от оплаты)
    * 
    * http://САЙТ.РУ/onlinepay/{PaymentType}/fail/
    */
    function actionFail()
    {
        $request = $this->url;
        $payment_type = $this->url->get('PaymentType', TYPE_STRING);
        $transactionApi = new \Shop\Model\TransactionApi();
        try{
            $transaction = $transactionApi->recognizeTransactionFromRequest($payment_type, $this->url);
            $transaction->getPayment()->getTypeObject()->onFail($transaction, $this->url);
        }
        catch(\Exception $e){
            return $e->getMessage();       // Вывод ошибки
        }
        
        // redirect на партнёрский сайт
        if (\RS\Module\Manager::staticModuleEnabled('partnership')) {
            $order = $transaction->getOrder();
            if (!empty($order['partner_id']) && $order['partner_id'] != \Partnership\Model\Api::getCurrentPartner()->id) {
                $partner = new \Partnership\Model\Orm\Partner($order['partner_id']);
                $this->redirect($this->url->getProtocol().'://'.$partner->getMainDomain().$this->url->selfUri());
            }
        }
        
        $this->view->assign('transaction', $transaction);
        $this->app->setBodyClass('payment-fail', true); //Добавим класс для проверки мобильным приложением
        return $this->result->setTemplate( 'onlinepay/fail.tpl' );
    }
    
    /**
    * Проверяет статус выбивания чека для транзакции
    * 
    */
    function actionCheckTransactionReceiptStatus()
    {
        $id = $this->url->get('id', TYPE_INTEGER, 0);  
        $transaction = new \Shop\Model\Orm\Transaction($id);   
        $success = false;
        if ($transaction['id']){
            if ($transaction['receipt'] == \Shop\Model\Orm\Transaction::RECEIPT_SUCCESS || $transaction['receipt'] == \Shop\Model\Orm\Transaction::RECEIPT_REFUND_SUCCESS){ //Если человек получен успешно
                $success = true; 
            }elseif ($transaction['receipt'] == \Shop\Model\Orm\Transaction::RECEIPT_FAIL){
                $success = true; 
                $this->result->addSection('error', t('Ошибка при выписке чека. Пожалуйста обратитесь к менеджеру сайта.'));
            }
        }
        return $this->result->setSuccess($success);
    }

}
