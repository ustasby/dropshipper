<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Users\Controller\Front;
use \RS\Application\Auth as AppAuth;

/**
* Контроллер авторизации клиентской части 
* @ingroup Users
*/
class Auth extends \RS\Controller\Front
{
    function actionIndex()
    {
        $this->app->breadcrumbs->addBreadCrumb(t('Авторизация'));
        $referer = urldecode($this->url->request('referer', TYPE_STRING, \RS\Site\Manager::getSite()->getRootUrl()));
        $referer = \RS\Helper\Tools::cleanOpenRedirect( $referer );
        $error = '';
        
        $data = array(
            'login' => $this->url->request('login', TYPE_STRING),
            'pass' => $this->url->request('pass', TYPE_STRING),
            'referer' => urlencode($referer),
            'remember' => $this->url->request('remember', TYPE_BOOLEAN)
        );        
        
        if ($this->isMyPost()) {
            $this->result->setSuccess( AppAuth::login($data['login'], $data['pass'], $data['remember']) );
            if ( $this->result->isSuccess() ) {
                return $this->result
                        ->setNoAjaxRedirect($referer)
                        ->addSection('reloadPage', true);
            } else {
                $error = AppAuth::getError();
            }
        }
        
        
        $this->view->assign(array(
            'status_message' => isset($_SESSION['auth_access_error']) ? $_SESSION['auth_access_error'] : '',
            'error' => $error,
            'referrer' => $referer,
            'data' => $data
        ));
        
        unset($_SESSION['auth_access_error']);
            
        return $this->result->setTemplate('authorization.tpl');
    }
    
    function actionRecover()
    {
        $this->app->breadcrumbs
            ->addBreadCrumb(t('Авторизация'), $this->router->getUrl('users-front-auth'))
            ->addBreadCrumb(t('Восстановление пароля'));
        
        $data = array(
            'login' => $this->url->request('login', TYPE_STRING)
        );
        $error = false;
        if ($this->isMyPost()) {
            $user_api = new \Users\Model\Api();
            $success = $user_api->sendRecoverEmail($data['login']);
            $this->view->assign('send_success', $success);
            if (!$success) {
                $error = $user_api->getErrorsStr();
            }
        }
        $this->view->assign(array(
            'error' => $error,
            'data' => $data
        ));
        return $this->result->setTemplate( 'recover_pass.tpl' );
    }
    
    function actionChangePassword()
    {
        $this->app->breadcrumbs
            ->addBreadCrumb(t('Авторизация'), $this->router->getUrl('users-front-auth'))
            ->addBreadCrumb(t('Восстановление пароля'));
                    
        $hash = $this->url->get('uniq', TYPE_STRING);
        $user_api = new \Users\Model\Api();
        $user = $user_api->getByHash($hash);
        if (!$user) {
            return $this->e404();
        }

        $error = '';        
        if ($this->url->isPost()) {
            $new_pass = $this->url->post('new_pass', TYPE_STRING);
            $new_pass_confirm = $this->url->post('new_pass_confirm', TYPE_STRING);
            
            if ($user_api->changeUserPassword($user, $new_pass, $new_pass_confirm)) {
                //Авторизовываем пользователя
                AppAuth::setCurrentUser($user);
                return $this->redirect();
            } else {
                $error = $user_api->getErrorsStr();
            }
        }
        
        $this->view->assign(array(
            'uniq' => $hash,
            'user' => $user,
            'error' => $error
        ));
        
        return $this->result->setTemplate( 'change_pass.tpl' );
    }
    
    
    function actionLogout()
    {
        $referer = \RS\Helper\Tools::cleanOpenRedirect( $this->url->request('referer', TYPE_STRING) );
        \RS\Application\Auth::logout();
        $this->redirect($referer);
    }    
}

