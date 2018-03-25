<?php
namespace StatusOrder\Config;

/**
* Класс отвечает за установку и обновление модуля
*/
class Install extends \RS\Module\AbstractInstall
{

	function install()
    {
        
        $result = parent::install();
        if ($result) {
            $order = new \Shop\Model\Orm\Order();
            Handlers::ormInitShopOrder($order);
            $order->dbUpdate();
        }
        
        return $result;
    }
    
    /**
    * Функция обновления модуля, вызывается только при обновлении
    */
    function update()
    {
        $result = parent::update();
        if ($result) {
            $order = new \Shop\Model\Orm\Order();
            Handlers::ormInitShopOrder($order);
            $order->dbUpdate();

        }
        return $result;
    }     
}