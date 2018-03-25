<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\CashRegisterType;
use RS\Config\Loader;
use RS\Helper\Log;

/**
* Класс абстрактного типа онлайн касс
*/
abstract class AbstractType
{
    
    const
        OPERATION_SELL            = "sell", //Приход
        OPERATION_SELL_REFUND     = "sell_refund", //Возврат прихода
        OPERATION_SELL_CORRECTION = "sell_correction", //Коррекция прихода

        //Лог файл с запросами и пр. информацией
        LOG_FILE = '/logs/cash_register.log'; //storage/log/....


    
    protected $timeout = 30; //Таймаут на запрос
    protected $errors = array();
    
    protected $log;
    protected $log_file;
    protected $config;
        
    /**
    * Конструктор класса
    * 
    */
    function __construct()
    {
        $this->config = \RS\Config\Loader::byModule("shop");
        if ($this->config['cashregister_enable_log']) { //Включим лог
            $this->log = Log::file(self::getLogFilename());
            $this->log->enableDate(true);
        }
    }


    /**
     * Возвращает поддерживаемый список налогов
     *
     * @return array
     */
    public static function getTaxesList()
    {
        return array();
    }
    
    /**
    * Возвращает название расчетного модуля (онлайн кассы)
    * 
    * @return string
    */
    abstract function getTitle();
    
    /**
    * Возвращает идентификатор данного типа онлайн кассы. (только англ. буквы)
    * 
    * @return string
    */
    abstract function getShortName();
    
    /**
    * Отправляет запрос на создание чека по транзакции
    * 
    * @param \Shop\Model\Orm\Transaction $transaction - объект транзакции
    * @param string $operation_type - тип чека, приход или возврат
    */
    abstract function createReceipt(\Shop\Model\Orm\Transaction $transaction, $operation_type = 'sell');

    /**
    * Отправляет запрос на создание чека корректировки
    * 
    * @param $transaction_id - id транзакции
    * @param $form_object - объект с заполненными данными формы, возвращенной методом getCorrectionReceiptFormObject
    */
    abstract function createCorrectionReceipt($transaction_id, $form_object);
    
    /**
    * Делает запрос на запрос статуса чека и возвращаетданные записывая их в чек, если произошли изменения
    * 
    * @param \Shop\Model\Orm\Receipt $receipt - объект чека
    */
    abstract function getReceiptStatus(\Shop\Model\Orm\Receipt $receipt);
    
    /**
    * Функция обработки запроса продажи от провайдера чека продажи
    * 
    * @param \RS\Http\Request $request - объект запроса
    */
    abstract function onResultSell(\RS\Http\Request $request);
    
    /**
    * Функция обработки запроса продажи от провайдера чека возврата
    * 
    * @param \RS\Http\Request $request - объект запроса
    */
    abstract function onResultRefund(\RS\Http\Request $request);
    
    /**
    * Функция обработки запроса продажи от провайдера чека коррекции
    * 
    * @param \RS\Http\Request $request - объект запроса
    */
    abstract function onResultCorrection(\RS\Http\Request $request);


    /**
     * Возвращает объект формы чека коррекции
     *
     * @return \RS\Orm\FormObject | false Если false, то это означает, что кассовый модуль не поддерживает чеки коррекции
     */
    function getCorrectionReceiptFormObject()
    {
        return false;
    }
    
    /**
    * Устанавливает таймаут на запрос
    * 
    * @param integer $seconds - количество секунд для таймаутов
    */
    function setTimeout($seconds)
    {
        $this->timeout = $seconds;
    }
    
    /**
    * Возвращает таймаут для запроса
    * 
    */
    function getTimeout()
    {
        return $this->timeout;
    }
        
    /**
    * Получает значение опции онлайн кассы из модуля конфига модуля
    * 
    * @param string $key - ключ опции
    * @param mixed $default - значение по умолчанию
    * @return mixed
    */
    function getOption($key = null, $default = null)
    {
        $config = \RS\Config\Loader::byModule($this);
        if ($key == null) return $config;
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    
    /**
    * Отправляет запрос к АПИ провайдера обмена данными и возвращает результат в нужном типе. 
    * В ответ получает ответ либо false, если не удалось сделать запрос, либо результат в том 
    * типе, который указан в параметре
    * 
    * @param string $url - адрес на который отправить запрос
    * @param mixed $params - дополнительные параметры запроса
    * @param array $headers - массив дополнительных заголовков для запроса
    * @param boolean $ssl - Запрос по SSL защищённому соединению
    * @param string $method - метод отправки GET|POST
    * @param string $post_type - тип отправляемого ответа json|text|xml через POST
    * @param string $answer_type - тип принимаемого ответа json|text|xml
    * 
    * @return mixed|false
    */
    function createRequest($url, $params = array(), $headers = array(), $ssl = true, $method = 'GET', $post_type = 'json', $answer_type = 'json')
    {
        //Создадим запрос
        $opts['http']['method']  = $method;
        $opts['http']['timeout'] = $this->getTimeout(); //Таймаут 30 секунд
        $opts['http']['ignore_errors'] = true;
        
        $append_headers = array();
        //Заполним параметры
        if (!empty($params)){
            switch (mb_strtoupper($method)){
                case "POST": // POST запрос
                    $content = http_build_query($params);
                    $content_type = 'Content-Type: application/x-www-form-urlencoded';
                    switch(mb_strtolower($post_type)){
                        case "json":
                            $content = json_encode($params);
                            $append_headers[] = "Content-Length: ".mb_strlen($content);
                            $content_type = 'Content-type: application/json; charset=utf-8';
                            break;
                        case "xml":
                            $content = $params;
                            $content_type = 'Content-Type: application/xml';
                            break;
                    }
                    
                    $append_headers[] = $content_type; //Заголовки
                    $opts['http']['content'] = $content;
                    break;
                case "GET": // GET запрос
                default:
                    $params = http_build_query($params);
                    $url    = (mb_stripos($url, "?") !== false) ? $url .= "&".$params : $url .= "?".$params;
                    break;
            }    
        }
        
        $headers += $append_headers; 
        
        //Заполним заголовки
        if (!empty($headers)){
          $opts['http']['header'] = implode("\r\n", $headers);  
        }
        
        if ($ssl){ //Если запрос по SSL
            $opts['ssl']['verify_peer'] = false;
            $opts['ssl']['verify_peer_name'] = false;
        }
        
        if ($this->log){ //Если нужен лог
            $this->log->append('--------------');
            $this->log->append('[out] url: '.$url);
            $this->log->append('[out] params: '.var_export($params, true));    
            $this->log->append('[out] context options '.var_export($opts, true));
        }

        $context = stream_context_create($opts);

        //Отправляем запрос
        $response = @file_get_contents($url, false, $context);
        if ($this->log){ //Если нужен лог
            $this->log->append('[out] response: '.var_export($response, true));
        }


        if ($response !== false){
            switch(mb_strtolower($answer_type)){
                case "text":
                    return $response;
                    break;  
                case "xml":
                    return @simplexml_load_string($response);
                    break;  
                case "json":
                default:
                    return @json_decode($response, true);
                    break;     
            }    
        } else {
            $err = error_get_last();
            //$this->addError($err['message']);
        }
        return false;
    }
    
    /**
    * Возвращает url текушего домена
    * 
    * @return string
    */
    function getCurrentDomainUrl()
    {
        $current_site = \RS\Site\Manager::getSite();
        if (\RS\Module\Manager::staticModuleExists('partnership') && \RS\Module\Manager::staticModuleEnabled('partnership') && ($partner = \Partnership\Model\Api::getCurrentPartner())){
            $current_site = $partner;
        }
        $domain = $current_site->getMainDomain();
        return $domain;
    }
    
    /**
    * Функция возвращает по ключу значение заголовка параметра чека. Необходимо перегружать для каждого отдельного типа.
    * Т.к. там будут разные значения для заголовков. Используется в купе с extra_arr в чеке
    * 
    * @param string $key - ключ массив соответствия
    * @return string
    */
    function getReceiptInfoStringByKey($key){
        return $key;
    }
    
    
    /**
    * Добавляет ошибку в список
    * 
    * @param string $message - сообщение об ошибке
    * @param string $fieldname - название поля
    * @param string $form - техническое имя поля (например, атрибут name у input)
    * 
    */
    function addError($message, $fieldname = null, $form = null)
    {
        $this->errors[] = $message;
    }
    
    /**
    * Возвращает true, если имеются ошибки
    * 
    * @return bool
    */
    function hasError()
    {
        return !empty($this->errors);
    }

    /**
    * Возвращает полный список ошибок
    * @return array
    */
    function getErrors()
    {
        return $this->errors;
    }    
    
    /**
    * Возвращает строку с ошибками
    * @return string
    */
    function getErrorsStr()
    {
        return implode(", ", $this->errors);
    } 
    
    /**
    * Очищает ошибки
    * @return void
    */
    function cleanErrors()
    {
        $this->errors = array();
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
     * Возвращает настройки модуля Касс
     *
     * @return object
     */
    public function getCashRegisterTypeConfig()
    {
        return Loader::byModule($this);
    }
}
