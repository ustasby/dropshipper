<?php
namespace TinkoffPayment\Model\PaymentType;
use \RS\Orm\Type;
use \Shop\Model\Orm\Transaction;
use \Shop\Model\PaymentType\ResultException;
use \TinkoffPayment\Model\Lib\TinkoffMerchantAPI;

/**
* Способ оплаты - TinkoffPayment
*/
class TinkoffPayment extends \Shop\Model\PaymentType\AbstractType
{

    const APIURL = "https://securepay.tinkoff.ru/rest/";
    /**
    * Возвращает название расчетного модуля (типа доставки)
    *
    * @return string
    */
    function getTitle()
    {
        return t('Оплата по карте (Тинькофф)');
    }

    /**
    * Возвращает описание типа оплаты. Возможен HTML
    *
    * @return string
    */
    function getDescription()
    {
        return t('Оплата по карте через интернет-эквайринг банка Тинькофф');
    }

    /**
    * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
    *
    * @return string
    */
    function getShortName()
    {
        return 'TinkoffPayment';
    }

    /**
    * Отправка данных с помощью POST?
    *
    */
    function isPostQuery()
    {
        return false;
    }

    /**
    * Возвращает ORM объект для генерации формы или null
    *
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'login' => new Type\Varchar(array(
                'maxLength' => 255,
                'description' => t('Идентификатор магазина'),
                'hint' => t('Отображается в Личном Кабинете')

            )),
            'password' => new Type\Varchar(array(
                'description' => t('Секретный ключ/пароль'),
                'hint' => t('Отображается в Личном Кабинете')
            )),
            /*
            'api_url' => new Type\Varchar(array(
                'description' => t('Api Url'),
                'hint' => t('Предоставляется банком')
            )),*/
            /*
            'testmode' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Тестовый режим'),
                'checkboxview' => array(1,0),
            )),*/
            '__help__' => new Type\Mixed(array(
                'description' => t(''),
                'visible' => true,
                'template' => '%tinkoffpayment%/form/payment/tinkoffpayment/help.tpl'
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

    public function getServer() {
        return $this->getOption('testmode')?self::TEST_SERVER:self::PROD_SERVER;
    }

    /**
    * Возвращает URL для перехода на сайт сервиса оплаты
    *
    * @param Transaction $transaction - ORM объект транзакции
    * @return string
    */
    function getPayUrl(\Shop\Model\Orm\Transaction $transaction)
    {

        $order      = $transaction->getOrder(); //Данные о заказе
        /**
        * @var mixed
        */
        $inv_id     = $transaction->id;
        $router     = \RS\Router\Manager::obj();

        $out_summ   = round($transaction->cost, 2);
        $user = $transaction->getUser();

        $data_ar = array(
            'first_name' => $user['name'],
            'last_name'  => $user['surname'],
            'email'      => $user['e_mail'],
            'phone'      => $user['phone'],
        );
        $DATA = '';
        foreach ($data_ar as $key => $data){
            if($data){
                if($DATA === ''){
                    $DATA .= $key . '=' . $data;
                }
                else{
                    $DATA .= '|' . $key . '=' . $data;
                }
            }
        }

        $arrFields = array(
            'OrderId'           => $inv_id,
            'Amount'            => (int) $out_summ * 100,
            'Description'       => 'Оплата заказа №'.$order->order_num,
            'DATA'              => $DATA,
        );

        $Tinkoff = new TinkoffMerchantAPI( $this->getOption('login'), $this->getOption( 'passwords' ), self::APIURL/*($this->getOption( 'api_url' )*/ );
        $request = $Tinkoff->buildQuery('Init', $arrFields);

        //$request = '{"Success":true,"ErrorCode":"0","TerminalKey":"TestB","Status":"NEW","PaymentId":"13660","OrderId":"21050","Amount":100000,"PaymentURL":"https://securepay.tinkoff.ru/rest/Authorize/1B63Y1"}';
        $result = json_decode($request);

        if(!$result || (isset($result->ErrorCode) && $result->ErrorCode!=0)){
            if(!$result) {
                $error_msg = 'Сетевая ошибка';
            } else {
                $error_msg = $result->Message;
            }
            $fail = $router->getUrl('shop-front-onlinepay', array(
               'Act'            => 'fail',
               'OrderId'        => $inv_id,
               'PaymentType'    => $this->getShortName(),
               'Details'        => $error_msg,
            ), true);
            return $fail;
        } else {
            return  $result->PaymentURL;
        }
    }

    /**
    * Возвращает ID заказа исходя из REQUEST-параметров соотвествующего типа оплаты
    * Используется только для Online-платежей
    *
    * @return mixed
    */
    function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        return $request->request('OrderId', TYPE_INTEGER, 0);
    }

    /**
    * Обработка возврата покупателя с формы сбербанка
    *
    * @param \Shop\Model\Orm\Transaction $transaction - объект транзакции
    * @param \RS\Http\Request $request - объект запросов
    * @return string
    */
    function onResult(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        $router     = \RS\Router\Manager::obj();
        $app = \RS\Application\Application::getInstance(); //Получили экземпляр класса страница
        $_POST['Password'] = $this->getOption( 'password' );
        ksort($_POST);
        $sorted = $_POST;
        $original_token = $sorted['Token'];

        unset($sorted['Token']);

        $values = implode('', array_values($sorted));
        $token = hash('sha256', $values);
        $status = $request->post('Status', TYPE_STRING, "");

        if($token == $original_token){
            if($status == 'AUTHORIZED') {
                $exception = new ResultException(t('Запрос авторизации'),1);
                $exception->setResponse('OK');
                $exception->setUpdateTransaction(false);
                throw $exception;
            } elseif( $status == 'CONFIRMED'){
                return 'OK';
            }else {
                $exception = new ResultException(t('Ошибка'),1);
                $exception->setResponse('OK');

                throw $exception;
            }
        } else {
            $exception = new ResultException(t('Ошибка'),1);
            $exception->setResponse('NOTOK');
            $exception->setUpdateTransaction(false);
            throw $exception;
        }

    }

    /**
    * Вызывается при открытии страницы неуспешного проведения платежа
    * Используется только для Online-платежей
    *
    * @param \Shop\Model\Orm\Transaction $transaction
    * @param \RS\Http\Request $request
    * @return void
    */
    function onFail(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {

        $error = $request->get('Details',TYPE_STRING, '');
        if($error) {
            throw new \Exception($error);
        }
    }


}
