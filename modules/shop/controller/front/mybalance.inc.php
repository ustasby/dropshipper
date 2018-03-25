<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;
use \Shop\Model\PaymentType\PersonalAccount;
/**
* Контроллер лицевого счета
*/
class MyBalance extends \RS\Controller\AuthorizedFront
{   
    function actionIndex()
    {
        $title = t('Лицевой счет');
        $this->app->title->addSection($title);
        $this->app->breadcrumbs->addBreadCrumb($title);        
                
        $page_size = 10;
        $page = $this->url->get('p', TYPE_INTEGER, 1);
        
        $transApi = new \Shop\Model\TransactionApi();
        $transApi->setPersonalAccountTransactionsFilter();
        $transApi->setFilter('status', 'success');
        
        $list = $transApi->getList($page, $page_size, 'id desc');

        $paginator = new \RS\Helper\Paginator($page, $transApi->getListCount(), $page_size);
        $this->view->assign(array(
            'paginator' => $paginator,        
            'list' => $list
        ));        


        return $this->result->setTemplate('mybalance/mybalance.tpl');
    }
    
    /**
    * Пополнение лицевого счета
    * 
    */
    function actionAddFunds()
    {
        $title = t('Пополнение лицевого счета');
        $this->app->title->addSection($title);
        $this->app->breadcrumbs
            ->addBreadCrumb(t('Лицевой счет'), $this->router->getUrl('shop-front-mybalance'))
            ->addBreadCrumb($title);
        
        $transApi = new \Shop\Model\TransactionApi();

        $my_type = $this->user['is_company'] ? 'company' : 'user';
        $pay_api = new \Shop\Model\PaymentApi();
        $pay_api->setFilter('public', 1);
        $pay_api->setFilter('user_type', array('all', $my_type), 'in');     // Разный список оплат, в зависимости от того, компания ли это или обынчй пользователь
        $pay_api->setFilter('class', PersonalAccount::SHORT_NAME, '!=');    // Исключаем из способов пополнения счета оплату с самого себя
        $pay_api->setFilter('target', array('all', 'refill'), 'in');        
        $this->view->assign('pay_list', $pay_api->getList());               // Список типов оплаты
        $this->view->assign('api', $transApi);                 
        
        if ($this->url->isPost()) {
            $payment    = $this->url->post('payment', TYPE_INTEGER);
            $cost       = $this->url->post('cost', TYPE_FLOAT);
            if(!$payment){
                $transApi->addError(t('Укажите способ оплаты'), 'payment');
            }
            if(!$cost){
                $transApi->addError(t('Укажите сумму'), 'cost');
            }
            if(!$transApi->hasError()){
                // Создаем транзакцию для пополнения счета
                $transaction = $transApi->createTransactionForAddFunds(\RS\Application\Auth::getCurrentUser()->id, $payment, $cost);
                
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
        }
        
        return $this->result->setTemplate('mybalance/addfunds.tpl');
    }
    
    /**
    * Страница подтверждения списания средств с лицевого счета
    * Страница показывется при оплате заказа с помощю способа оплаты "Лицевой счет"
    */
    function actionConfirmPay()
    {
        $transaction_id = $this->url->get('transaction_id', TYPE_INTEGER);
        $transaction = new \Shop\Model\Orm\Transaction($transaction_id);
        $transApi = new \Shop\Model\TransactionApi();
        
        // Проверка существования транзации и принедлежности её этому пользователю
        if(!$transaction->id || $transaction->user_id != $this->user->id){
            $this->e404(t('Транзация не найдена'));
        }      
        
        // Проверка наличия средств на лицевом счете
        if( (float) $this->user->getBalance() < (float) $transaction->cost ){
            $transApi->addError(t('На лицевом счете недостаточно средств для оплаты'));
        }
        
        // Нажатие кнопки "Оплатить"
        if($this->url->isPost()) {
            if(!$transApi->hasError()){
                // Все хорошо. Выдаем товар покупателю (помечаем ордер как оплаченный)
                // Для этого вызываем у транзакции метод onResult, по аналогии с тем, как это делается при 
                // оплате через онлайн способы оплаты
                $transaction->onResult($this->url);
                
                // Средства списываются с лицевого счета благодаря тому, что данная транзакция переходит в статус Success
                // Физически списание средств происходит в методе Transaction::afterWrite()
                
                // Переадресуем на страницу уведомления об успешной оплате
                $url = $this->router->getUrl('shop-front-onlinepay', array('Act' => 'success', 'PaymentType' => PersonalAccount::SHORT_NAME, 'transaction_id' => $transaction->id));
                $this->redirect($url);
            }
        }
        
        $this->view->assign('transaction', $transaction);
        $this->view->assign('api', $transApi);
        return $this->result->setTemplate('mybalance/confirmpay.tpl');
    }
}
?>
