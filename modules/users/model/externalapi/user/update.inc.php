<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\ExternalApi\User;
use \ExternalApi\Model\Exception as ApiException;
  
/**
* Обновляет сведения о пользователе, перезаписывает значения полей
*/
class Update extends \ExternalApi\Model\AbstractMethods\AbstractAuthorizedMethod
{
    const
        RIGHT_LOAD_SELF = 1,
        RIGHT_LOAD = 2;
        
    protected
        /**
        * Поля, которые следует проверять из POST
        */
        $use_post_keys = array('is_company', 'company', 'company_inn', 
                            'name', 'surname', 'midname', 'sex', 'passport', 'phone', 
                            'e_mail', 'openpass', 'captcha', 'data', 'changepass'),
        $user_validator;
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return array(
            self::RIGHT_LOAD_SELF => t('Загрузка авторизованного пользователя')
        );
    }
    
    /**
    * Возвращает список прав, требуемых для запуска метода API
    * По умолчанию для запуска метода нужны все права, что присутствуют в методе
    * 
    * @return [код1, код2, ...]
    */
    public function getRunRights()
    {
        return array(); //Проверка прав будет непосредственно в теле метода
    }    
    
    /**
    * Возвращает ORM объект, который следует загружать
    */
    public function getOrmObject()
    {
        return new \Users\Model\Orm\User();
    }  
    
    /**
    * Форматирует комментарий, полученный из PHPDoc
    * 
    * @param string $text - комментарий
    * @return string
    */
    protected function prepareDocComment($text, $lang)
    {
        $text = parent::prepareDocComment($text, $lang);
        
        //Валидатор для пользователя
        $validator = $this->getUserValidator();
        $text = preg_replace_callback('/\#data-user/', function() use($validator) {
            return $validator->getParamInfoHtml();
        }, $text);
        
        
        return $text;
    }
    
    /**
    * Возвращает валидатор для пользователя который отправляет поля для сохранения
    * 
    */
    private function getUserValidator()
    {
        if ($this->user_validator === null){
            $this->user_validator = new \ExternalApi\Model\Validator\ValidateArray(array(
                'is_company' => array(
                    '@title' => t('Является ли клиент компанией?'), 
                    '@require' => true,
                    '@type' => 'integer'    
                ),
                'company' => array(
                    '@title' => t('Название компании. Только если, стоит ключ is_company.'), 
                    '@type' => 'string', 
                    '@validate_callback' => function($is_company, $full_data) {
                        if (isset($full_data['is_company']) && $full_data['is_company']){
                            return "Название компании обязательное поле.";        
                        } 
                        return true;
                    } 
                ),
                'company_inn' => array(
                    '@title' => t('ИНН компании. Только если, ключ is_company.'), 
                    '@type' => 'string', 
                    '@validate_callback' => function($is_company, $full_data) {
                        if (isset($full_data['is_company']) && $full_data['is_company']){
                            return "ИНН компании обязательное поле.";        
                        } 
                        return true;
                    }      
                ),
                'surname' => array(
                    '@title' => t('Фамилия.'), 
                    '@type' => 'string', 
                    '@require' => true,
                ),
                'name' => array(
                    '@title' => t('Имя.'), 
                    '@type' => 'string', 
                    '@require' => true,
                ),
                'midname' => array(
                    '@title' => t('Отчество.'), 
                    '@type' => 'string', 
                ),
                'phone' => array(
                    '@title' => t('Телефон.'), 
                    '@type' => 'string', 
                    '@require' => true,
                ),
                'e_mail' => array(
                    '@title' => t('E-mail.'), 
                    '@type' => 'string', 
                    '@require' => true,
                ),
                'changepass' => array(
                    '@title' => t('Нужно ли сменить пароль? 0 или 1.'), 
                    '@type' => 'integer' 
                ),
                'pass' => array(
                    '@title' => t('Текущий пароль. Только если, changepass=1'), 
                    '@type' => 'string', 
                    '@validate_callback' => function($is_company, $full_data) {
                        if (isset($full_data['changepass']) && $full_data['changepass']){
                            return "Текущий пароль обязательное поле.";        
                        } 
                        return true;
                    }  
                ),
                'openpass' => array(
                    '@title' => t('Повтор открытого пароля. Только если, changepass=1'), 
                    '@type' => 'string', 
                    '@validate_callback' => function($is_company, $full_data) {
                        if (isset($full_data['changepass']) && $full_data['changepass']){
                            return "Повтор открытого пароля обязательное поле.";        
                        } 
                        return true;
                    } 
                ),
                'openpass_confirm' => array(
                    '@title' => t('Повтор открытого пароля. Только если, changepass=1'), 
                    '@type' => 'string', 
                    '@validate_callback' => function($is_company, $full_data) {
                        if (isset($full_data['changepass']) && $full_data['changepass']){
                            return "Повтор открытого пароля обязательное поле.";        
                        } 
                        return true;
                    } 
                ),
                
                
            ));
        }
        return $this->user_validator;
    }  
    
    /**
    * Обновляет сведения о пользователе, перезаписывает значения полей. 
    * Данные можно обновить только у авторизованного пользователя, который получается из токена.
    * 
    * @param string $token Авторизационный токен
    * @param string $client_id id клиентского приложения
    * @param string $client_secret пароль клиентского приложения
    * @param array $user поля пользователя для сохранения #data-user
    * @param array $regfields_arr поля пользователя из настроек модуля пользователь
    * 
    * @example POST /api/methods/user.update?token=b45d2bc3e7149959f3ed7e94c1bc56a2984e6a86&&user[name]=Супервизор%20тест%20тест&user[surname]=%20Моя%20фамилия&user[e_mail]=admin%40admin.ru&user[phone]=8(000)800-80-30&user[changepass]=0&user[is_company]=0
    * 
    * <pre>
    *  {
    *      "response": {
    *            "success" : false,
    *            "errors" : ['Ошибка'],    
    *            "errors_status" : 2 //Появляется, если присутствует особый статус ошибки (истекла сессия, ошибки в корзине, корзина пуста)
    *      }
    *   }</pre>
    * @throws ApiException
    * @return array Возращает, пустой массив ошибок, если успешно
    */
    protected function process($token, $client_id, $client_secret, $user, $regfields_arr = array())
    {
        //Проверим предварительно приложение
        $app = \RS\RemoteApp\Manager::getAppByType($client_id);
        
        if (!$app || !($app instanceof \ExternalApi\Model\App\InterfaceHasApi)) {
            throw new ApiException(t('Приложения с таким client_id не существует или оно не поддерживает работу с API'), ApiException::ERROR_BAD_CLIENT_SECRET_OR_ID);
        }
        
        //Производим валидацию client_id и client_secret
        if (!$app || !$app->checkSecret($client_secret)) {
            throw new ApiException(t('Приложения с таким client_id не существует или неверный client_secret'), ApiException::ERROR_BAD_CLIENT_SECRET_OR_ID);
        }
        
        //Проверим поля пользователя
        $validator = $this->getUserValidator();
        $validator->validate('user', $user, $this->method_params);
        
        $errors = array();
        $response['response']['success'] = false; 
                                                      
        //Получим пользователя
        /**
         * @var \Users\Model\Orm\User $current_user
         */
        $current_user = $this->token->getUser();
        $current_user->usePostKeys($this->use_post_keys);
        
        $current_user->checkData($_POST['user']);

        //Изменяем пароль
        if ($user['changepass']) {
            $current_pass = $user['pass'];
            $crypt_current_pass = $current_user->cryptPass($current_pass);
            if ($crypt_current_pass === $current_user['pass']) {
                $current_user['pass'] = $crypt_current_pass;
            } else {
                $current_user->addError(t('Неверно указан текущий пароль'), 'pass');
            }
            
            $password = $user['openpass'];
            $password_confirm = $user['openpass_confirm'];
            
            if (strcmp($password, $password_confirm) != 0) {
                $current_user->addError(t('Пароли не совпадают'), 'openpass');
            }                            
        }
        
        if (!$current_user->hasError() && $current_user->save($current_user['id'])) {
            $_SESSION['user_profile_result'] = t('Изменения сохранены');
            $response['response']['success'] = true; 
            
            //Выпишем новый токен под пользователя
            $token = \ExternalApi\Model\TokenApi::createToken($current_user['id'], $client_id);
            
            $auth_user           = \ExternalApi\Model\Utils::extractOrm($current_user);
            $auth_user['fio']    = $current_user->getFio();
            $auth_user['groups'] = $current_user->getUserGroups();
            
            $response['response']['auth']['token']  = $token['token'];
            $response['response']['auth']['expire'] = $token['expire'];
            $response['response']['user']           = $auth_user;
        }else{
            $errors = $current_user->getErrors();
        }
        $response['response']['errors']  = $errors;
        
        return $response;
    }
}