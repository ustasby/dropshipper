<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\PaymentType;

class ResultException extends \Exception
{
    private 
        $update_transaction = true,
        $response;
    
    /**
    * Устанавливает содержимое ответа, которое будет отправлено в output
    * 
    * @param string $text
    * @return void
    */
    public function setResponse($text)
    {
        $this->response = $text;
    }

    /**
    * Возвращает ответ, который должен быть отправлен в output
    * @return string
    */
    public function getResponse()
    {
        return $this->response;
    }
    
    /**
    * Устанавливает, нужно ли обновлять транзацию, устанавливая её статус - fail
    * 
    * @param bool $bool
    * @return void
    */
    public function setUpdateTransaction($bool)
    {
        $this->update_transaction = $bool;
    }
    
    /**
    * Возвращает true в случае, если нужно обновлять транзакцию. (устанавливать ей статус - fail)
    * 
    * @return boolean
    */
    public function canUpdateTransaction()
    {
       return $this->update_transaction;
    }
}
?>
