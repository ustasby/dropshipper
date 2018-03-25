<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Admin;

class Tools extends \RS\Controller\Admin\Front
{
    function actionAjaxCalcProfit()
    {
        $position = $this->url->request('pos', TYPE_INTEGER);
        
        $order_api = new \Shop\Model\OrderApi();
        $result = $order_api->calculateOrdersProfit($position);
        
        if ($result === true) {
            return $this->result->addMessage(t('Пересчет успешно завершен'));
        } 
        elseif ($result === false) {
            return $this->result->addEMessage($order_api->getErrorsStr());
        }
        else {
            return $this->result
                            ->addSection('repeat', true)
                            ->addSection('queryParams', array(
                                'data' => array(
                                    'pos' => $result
                                ))
                            );
        }
    }
    
    /**
     * Выводит содержимое лог файла обмена с кассами
     * 
     * @return string
     */
    function actionShowCashRegisterLog()
    {
        $this->wrapOutput(false);
        $log_file = \Shop\Model\CashRegisterType\AbstractType::getLogFilename();
        if (file_exists($log_file)) {
            echo '<pre>';
            readfile($log_file);
            echo '</pre>';
        } else {
            return t('Лог файл не найден');
        }
    }

    /**
    * Удаляет лог файл
    * 
    */
    function actionDeleteCashRegisterLog()
    {
        $log_file = \Shop\Model\CashRegisterType\AbstractType::getLogFilename();
        if (file_exists($log_file)) {
            unlink($log_file);
            return $this->result->setSuccess(true)->addMessage(t('Лог-файл успешно удален'));
        } else {
            return $this->result->setSuccess(true)->addEMessage(t('Лог-файл отсутствует'));
        }
    }
}