<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace EmailSubscribe\Controller\Front;

/**
* Контроллер отвечает за окно подписки
*/
class Window extends \RS\Controller\Front
{            
    
    /**
    * Показывает окно подключения к рассылке
    * 
    */
    function actionIndex()
    {
        $errors = array();
        if ($this->isMyPost()) {
            $email = trim($this->request('email', TYPE_STRING));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //Проверим E-mail
                $errors[] = t("Укажите правильный E-mail");
            }
            if (empty($errors)){ //Если ошибок нет, то отправим E-mail
               $api = new \EmailSubscribe\Model\Api();
               if (!$api->checkEmailPresent($email)) {
                   $api->sendSubscribeToEmail($email); 
                   $this->view->assign(array(
                      'success' => t('На Ваш E-mail отправлено письмо с дальнейшей инструкцией для подтвержения подписки')
                   )); 
               }else{
                   $errors[] = t("Ваш E-mail (%0) уже присутствует в списке подписчиков", array($email));
               }
            }
        }
        //Запишем в куку, то что мы показали окно подписки
        if (!$this->url->cookie('subscribe_is_shown', TYPE_BOOLEAN)) {
           $this->app->headers->addCookie('subscribe_is_shown', 1, time() + 60*60*24*30*60, "/"); 
        }
        
        $this->view->assign(array(
           'errors' => $errors 
        ));
        return $this->result->setTemplate( 'window.tpl' );
    }
    
    
    /**
    * Активирует E-mail для подписки
    * 
    */
    function actionActivateEmail()
    {
       $errors    = array();
       $signature = $this->request('signature',TYPE_STRING);
       $api       = new \EmailSubscribe\Model\Api();
       
       if ($api->activateEmailBySignature($signature)){
          $this->view->assign('success', t('Спасибо! Вы успешно подписаны на рассылку.')); 
       }else{
          $errors[] = t('Вы уже активировали E-mail ранее или данного E-mail не существует'); 
       }
       
       $this->view->assign(array(
           'errors' => $errors 
       ));
       return $this->result->setTemplate( 'activate.tpl' );
    }
    
    /**
    * Деактивирует E-mail для подписки по E-mail
    * 
    */
    function actionDeActivateEmail()
    {
       $errors = array();
       $email  = $this->request('email',TYPE_STRING);
       $api    = new \EmailSubscribe\Model\Api();
       
       if ($api->deactivateEmailByEmail($email)){
          $this->view->assign('success', t('Спасибо! Вы отписаны от рассылки.')); 
       }else{
          $errors[] = t('Активный E-mail в базе не найден'); 
       }
       
       $this->app->headers->removeCookie('subscribe_is_shown');
       
       $this->view->assign(array(
           'errors' => $errors 
       ));
       return $this->result->setTemplate( 'deactivate.tpl' );
    }
}
