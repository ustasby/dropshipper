<?php
namespace TinkoffPayment\Config;
use \RS\Orm\Type as OrmType;

/**
* Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('payment.gettypes');
    }


    public static function paymentGetTypes($list)
    {
        $list[] = new \TinkoffPayment\Model\PaymentType\TinkoffPayment();
        return $list;
    }

}