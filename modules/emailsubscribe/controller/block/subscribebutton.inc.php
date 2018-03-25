<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace EmailSubscribe\Controller\Block;
use \RS\Orm\Type;

/**
* Блок контроллер - Форма подписки на рассылку
*/
class SubscribeButton extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Подписка на рассылку',
        $controller_description = 'Отображает блок подписки на рассылку';
        
    
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/button/button.tpl', //Должен быть задан у наследника
        );
    
    
    function actionIndex()
    {
        $errors = array();
        if ($this->isMyPost()){ //Если E-mail передан
            $email = $this->request('email', TYPE_STRING, false);
            
            if (filter_var($email, FILTER_VALIDATE_EMAIL)){
                $api = new \EmailSubscribe\Model\Api();
                if (!$api->checkEmailPresent($email)) {
                    $api->sendSubscribeToEmail($email);
                    $this->view->assign(array(
                      'success' => t('На Ваш E-mail отправлено письмо с дальнейшей инструкцией для подтверждения подписки')
                    ));
                }
                $errors[] = t("Ваш E-mail (%0) уже присутствует в списке подписчиков", array($email));
            }else{
                $errors[] = t('Укажите правильный E-mail');
            }
        }
        $this->view->assign(array(
           'errors' => $errors 
        ));
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}
