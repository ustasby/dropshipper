<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Controller\Front;
use RS\Helper\Log;

/**
* Контроллер для обработки чеков ККТ от онлайн касс
*/
class CashRegister extends \RS\Controller\Front
{
    const
        LOG_FILE = '/logs/cash_register.log'; //storage/log/....
    
    protected
        /**
        * @var \Shop\Model\CashRegisterType\AbstractType
        */
        $provider; //Провайдер обработки чеков
        
    protected $log;
    protected $log_file;
    
    
    
    /**
    * Инициализация перед обработкой запроса
    * 
    */
    function init()
    {
        if ($this->getModuleConfig()->cashregister_enable_log) { //Включим лог
            $this->log = Log::file(self::getLogFilename());
            $this->log->enableDate(true);
            $this->log->append('---------');
            $this->log->append('[in] Start action '.$this->getAction());
            $this->log->append('[in] Request url: '.$this->url->getSelfUrl());
            $this->log->append('[in] Request data: '.var_export($_REQUEST, true));
        }
        
        $this->wrapOutput(false);         
        $cash_register_type = $this->request('CashRegisterType', TYPE_STRING, null);
        if (!$cash_register_type){ //Если пришёл запрос без указания провайдера
            if ($this->log){
                $this->log->append('-------- ERROR ---------');
                $error = t('Не указан провайдер для онлайн касс');
                $this->log->append('[in] '.$error);    
            }
            throw new \RS\Exception($error);
        }
        
        try{
           $cash_register_api = new \Shop\Model\CashRegisterApi();
           $this->provider = $cash_register_api->getTypeByShortName($cash_register_type);
        }    
        catch(\Exception $e){    
            $this->throwInError($e);
        }
    }
    
    
    /**
    * Особый action, который вызвается с сервера online касс
    * В REQUEST['sign'] должен содержаться строковый идентификатор чека
    * 
    * http://САЙТ.РУ/cashregister/{CashRegisterType}/{Act}/
    */
    function actionSell()
    {
        ob_start(); //Чтобы собрать все notice'ы, если они есть сохраняем буфер
        try{ 
            $response = $this->provider->onResultSell($this->url);
        }
        catch(\Exception $e){
            $this->throwInError($e);
        }
        
        if ($this->log) {
            $for_log_result = ob_get_contents().$response;
            $this->log->append('[in] Your response: '.$for_log_result);
        }
        ob_end_flush();
        return $response;
    }
    
    /**
    * Особый action, который вызвается с сервера online касс
    * В REQUEST['sign'] должен содержаться строковый идентификатор чека
    * 
    * http://САЙТ.РУ/cashregister/{CashRegisterType}/{Act}/
    */
    function actionRefund()
    {
        ob_start(); //Чтобы собрать все notice'ы, если они есть сохраняем буфер
        try{ 
            $response = $this->provider->onResultRefund($this->url);
        }
        catch(\Exception $e){
            $this->throwInError($e);
        }
        
        if ($this->log) {
            $for_log_result = ob_get_contents().$response;
            $this->log->append('[in] Your response: '.$for_log_result);
        }
        ob_end_flush();
        return $response;
    }
    
    /**
    * Особый action, который вызвается с сервера online касс
    * В REQUEST['sign'] должен содержаться строковый идентификатор чека
    * 
    * http://САЙТ.РУ/cashregister/{CashRegisterType}/{Act}/
    */
    function actionCorrection()
    {
        ob_start(); //Чтобы собрать все notice'ы, если они есть сохраняем буфер
        try{ 
            $response = $this->provider->onResultCorrection($this->url);
        }
        catch(\Exception $e){
            $this->throwInError($e);
        }
        
        if ($this->log) {
            $for_log_result = ob_get_contents().$response;
            $this->log->append('[in] Your response: '.$for_log_result);
        }
        ob_end_flush();
        return $response;
    }
    
    /**
    * Возвращает путь к логу для записи сообщений
    * 
    */
    public static function getLogFilename()
    {
        return \Setup::$PATH.\Setup::$STORAGE_DIR.self::LOG_FILE;
    }
    
    /**
    * Функция бросает исключение и если нужно записывает в лог
    * 
    * @param \Exception $e - объект исключения
    */
    private function throwInError(\Exception $e)
    {
        if ($this->log){
            $this->log->append('-------- ERROR --------');
            $this->log->append('[in] Exception message:'.$e->getMessage());
            $this->log->append('[in] Exception code:'.$e->getCode());
            $this->log->append('[in] Exception file:'.$e->getFile());
            $this->log->append('[in] Exception line:'.$e->getLine());
            $this->log->append('[in] Exception StackTrace:'.$e->getTraceAsString());
        }
        throw $e;
    }
}