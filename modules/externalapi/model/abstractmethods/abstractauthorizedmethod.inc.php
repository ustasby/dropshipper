<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Model\AbstractMethods;
use \ExternalApi\Model\Exception as ApiException;

/**
* Метод API, требующий авторизационный токен token с 
* необходимым набором прав для выполнения
*/
abstract class AbstractAuthorizedMethod extends AbstractMethod
{    
    /**
    * Если указать false, то token можно принимать опционально, 
    * чтобы давать в определенных случаях больше прав для вызова данного метода
    */
    protected $token_require = true;
    protected $token_param_name = 'token';
    protected $token;
    
    public $token_is_invalid = false; //Флаг отвечает за показ, того действителен токен или нет
        
    /**
    * Проверяет права на выполнение данного метода
    * 
    * @param array $params - параметры запроса
    * @param string $version - версия приложения
    * @throws ApiException
    * @return void
    */
    public function validateRights($params, $version) 
    {
        parent::validateRights($params, $version);
        
        if ($this->token_require || isset($params[$this->token_param_name])) {
            if (!isset($params[$this->token_param_name])) {
                throw new ApiException(t('Не указан обязательный параметр token'), ApiException::ERROR_WRONG_PARAM_VALUE);
            }
            
            //Загружаем token
            $this->token = new \ExternalApi\Model\Orm\AuthorizationToken();
            
            if (!$this->token->load($params[$this->token_param_name]) || $this->token['expire'] < time()) {
                if ($this->token_require){ //Бросаем только, если токен обязателен
                    throw new ApiException(t('Неверно указан авторизационный токен'), ApiException::ERROR_METHOD_ACCESS_DENIED);    
                }else{
                    $this->token_is_invalid = true;
                }
            }
            
            if (!$this->token_is_invalid){
                //Проверяем права на запуск метода API
                $app_rights = $this->token->getApp()->getAppRights();
                $current_method = $this->getSelfMethodName();
                if (!isset($app_rights[$current_method])
                    || ($app_rights[$current_method] != \ExternalApi\Model\App\AbstractAppType::FULL_RIGHTS 
                        && array_diff($this->getRunRights(), (array)$app_rights[$current_method])))
                {
                    //Формируем права, которые нужны
                    if (isset($app_rights[$current_method])) {
                        $need_rights = implode(',', array_diff_key($this->getRightTitles(), $app_rights[$current_method]));
                    } else {
                        $need_rights = 'доступ к методу '.$current_method;
                    }
                    
                    throw new ApiException(t('Недостаточно прав для запуска метода API. Требуются права на: %0', array($need_rights)), ApiException::ERROR_METHOD_ACCESS_DENIED);
                }
                \RS\Application\Auth::setCurrentUser($this->token->getUser());    
            }
        }
    }
    
    /**
    * Проверяет наличие у token'а отдельных необходимых прав
    * 
    * @param integer | array $rights - Одно или несколько прав. Проверка будет происходить с помощью ИЛИ
    * @return string | false
    */
    public function checkAccessError($rights)
    {
        if (!$this->token['token']) return t('Токен не загружен');
        $rights = (array)$rights;
        
        $app_rights = $this->token->getApp()->getAppRights();

        if (isset($app_rights[$this->getSelfMethodName()]) && $app_rights[$this->getSelfMethodName()] == \ExternalApi\Model\App\AbstractAppType::FULL_RIGHTS) {
            return false;
        }
        
        if (isset($app_rights[$this->getSelfMethodName()]) && array_intersect($rights, $app_rights[$this->getSelfMethodName()])) {
            return false;
        }
        
        $actions = implode(', ', array_intersect_key($this->getRightTitles(), array_flip($rights)));
        return t('Недостаточно прав для выполнения действий: %0', array($actions));
    }
    
    /**
    * Возвращает список прав, требуемых для запуска метода API
    * По умолчанию для запуска метода нужны все права, что присутствуют в методе
    * 
    * @return [код1, код2, ...]
    */
    public function getRunRights()
    {
        return array_keys($this->getRightTitles());
    }
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    abstract public function getRightTitles();
    
    /**
    * Запускает выполнение метода
    * 
    * @param array $params - параметры запроса
    * @param string $version - версия АПИ
    * @param string $lang - язык ответа
    * 
    * @return mixed
    */
    public function run($params, $version = null, $lang = 'ru')
    { 
        $response = parent::run($params, $version, $lang); 
        if ($this->token_is_invalid){ //Если есть и устновлен флаг, что токен не действителен
           $response['response']['token_is_invalid'] = true;  
        }
        return $response;
    }
    
}