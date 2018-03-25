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
* Способ оплаты - Лицевой счет
*/
class PersonalAccount extends AbstractType
{
    
    const SHORT_NAME = 'personalaccount';
    
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Лицевой счет');
    }
    
    /**
    * Возвращает описание типа оплаты. Возможен HTML
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Оплата с лицевого счета');
    }
    
    /**
    * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return self::SHORT_NAME;
    }
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
        ) );
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
        $router = \RS\Router\Manager::obj();
        return $router->getUrl('shop-front-mybalance', array('Act' => 'confirmpay', 'transaction_id' => $transaction->id));
    }
    
    /**
    * Возвращает ID заказа исходя из REQUEST-параметров соотвествующего типа оплаты
    * Используется только для Online-платежей
    * 
    * @return mixed
    */
    function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        return $request->request('transaction_id', TYPE_INTEGER, false);
    }

    
    function onResult(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
    }
    
    /**
    * Вызывается при переходе на страницу успеха, после совершения платежа 
    * 
    * @return void 
    */
    function onSuccess(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
    }

    /**
     * Возвращает дополнительный HTML для админ части в заказе
     * @return string
     */
    function getAdminHTML(\Shop\Model\Orm\Order $order, $params = array())
    {
        $view = new \RS\View\Engine();
        $view->assign(array(
            'order' => $order,
            'params' => $params
        ));

        return $view->fetch('%shop%/form/payment/personalaccount_admin.tpl');
    }

    function actionOrderQuery(\Shop\Model\Orm\Order $order)
    {
        $url = \RS\Http\Request::commonInstance();
        $operation = $url->request('operation', TYPE_STRING);
        $cost = $url->request('cost', TYPE_STRING);
        $payment_id = $url->request('payment_id', TYPE_INTEGER);

        if ($operation == 'orderpay') {
            if ($order['user_id'] <= 0) {
                $error = t('Заказ должен быть привязан к пользователю системы');
            }
            elseif ($order['is_payed']) {
                $error = t('Заказ уже был оплачен');
            }
            elseif ($order->getUser()->getBalance() < $cost) {
                $error = t('Недостаточно средств для оплаты заказа на лицевом счете пользователя');
            }
            else {
                $transaction_api = new \Shop\Model\TransactionApi();
                if ($transaction_api->addFunds(
                        $order['user_id'],
                        $cost,
                        true,
                        t('Оплата заказа №%0', array($order['order_num'])),
                        true,
                        null,
                        null,
                        $payment_id
                )) {

                    $order['is_payed'] = 1;
                    $order->update();

                    return array(
                        'success' => true,
                        'messages' => array(array(
                            'text' => t('Успешно списано %summ с лицевого счета пользователя', array(
                                'summ' => $cost
                            )),
                        ))
                    );
                } else {
                    $error = $transaction_api->getErrorsStr();
                }
            }

            return array(
                'success' => false,
                'messages' => array(array(
                    'text' => $error,
                    'options' => array(
                        'theme' => 'error'
                    )
                ))
            );
        }
    }
    
}
?>