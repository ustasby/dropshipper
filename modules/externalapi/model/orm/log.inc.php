<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Model\Orm;
use \RS\Orm\Type;

/**
* Лог запросов к внешнему API
*/
class Log extends \RS\Orm\OrmObject
{
    protected static
        $table = 'external_api_log';
    
    function _init()
    {
        parent::_init()->append(array(
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата совершения запроса'),
                'index' => true
            )),
            'request_uri' => new Type\Text(array(
                'description' => t('URL запроса к API')
            )),
            'request_params' => new Type\Blob(array(
                'description' => t('Параметры запроса'),
                'template' => '%externalapi%/form/log/request_params.tpl'
            )),
            'response' => new Type\Mediumblob(array(
                'description' => t('Ответ на запрос'),
                'template' => '%externalapi%/form/log/response.tpl'
            )),
            'ip' => new Type\Varchar(array(
                'description' => t('IP-адрес')
            )),
            'user_id' => new Type\Integer(array(
                'description' => t('Пользователь')
            )),
            'token' => new Type\Varchar(array(
                'description' => t('Авторизационный токен')
            )),
            'client_id' => new Type\Varchar(array(
                'description' => t('Идентификатор клиента')
            )),
            'method' => new Type\Varchar(array(
                'description' => t('Метод API')
            )),            
            'error_code' => new Type\Varchar(array(
                'description' => t('Код ошибки')
            ))
        ));
    }
    
    /**
    * Возвращает параметры запроса в удобочитаемом виде
    * 
    * @return string
    */
    function getRequestParamsView()
    {
        $request_params = @unserialize($this['request_params']);
        $flags = defined('JSON_PRETTY_PRINT') ?  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : null;
        return json_encode($request_params, $flags);
    }
    
    /**
    * Возвращает ответ сервера в удобочитаемом виде
    * 
    * @return string
    */
    function getResponseView()
    {
        $response = @unserialize($this['response']);
        $flags = defined('JSON_PRETTY_PRINT') ?  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE : null;
        return json_encode($response, $flags);
    }
    
    /**
    * Удаляет старые записи
    */
    static public function removeOldItems()
    {
        $total = \RS\Orm\Request::make()
            ->from(new self)
            ->count();
            
        $limit = 1000;
        if($total <= $limit) return;
            
        \RS\Orm\Request::make()
            ->from(new self)
            ->orderby("id")
            ->limit($total - $limit)
            ->delete()
            ->exec();
    }
}
