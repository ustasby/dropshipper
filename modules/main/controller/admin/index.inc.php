<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Main\Controller\Admin;
use \RS\Application\Auth as AppAuth,
    \RS\Cache\Cleaner as CacheCleaner,
    \Main\Model\NoticeSystem\Meter;

/**
* Основной контроллер администраторской панели
* Предает управление фронт-кнтроллерам модулей 
* @ingroup Main
*/
class Index extends \RS\Controller\AbstractController
{
    
    function __construct()
    {
        parent::__construct();
        $this->app->title->addSection(t('Административная панель'));
        $this->app->setJsDefaultFooterPosition(false);
        $this->app->addJsVar(array(
                'authUrl' => $this->router->getAdminUrl(false, array('Act' => 'auth'), false),
        ));
        $this->app->meta->add(array('name' => 'robots', 'content' => 'noindex, nofollow'));
    }
        
    /**
    * Точка входа в администраторскую панель
    */
    function actionIndex()
    {
        if ($auth = $this->needAuthorize(null, true)) {
             return $auth; //Требуется авторизация
        }
        
        if (\RS\Site\Manager::getAdminCurrentSite() === false) {
            return $this->authPage(t('Вы не имеете прав на администрирование ни одного сайта'));
        }
        
        $this->app->setBaseJs(\Setup::$JS_PATH);
        $this->app->setBaseCss(\Setup::$CSS_PATH.'/admin');

        $meter = Meter::getInstance();
        
        if (!$this->url->isAjax()) {
            //$this->app->setAnyHeadData('<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="'.\Setup::$JS_PATH.'/flot/excanvas.js"></script><![endif]-->');
            $this->app->addJsVar(array(
                'adminSection' => '/'.\Setup::$ADMIN_SECTION,
                'scriptType' => \Setup::$SCRIPT_TYPE,
                'resImgUrl' => \Setup::$IMG_PATH,
                'meterNextRecalculation' => $meter->getNextRecalculateInterval(),
                'meterRecalculationUrl' => $meter->getRecalculationUrl()
            ));
            
            $this->app
                ->addJs('jquery.min.js','jquery')
                ->addJs('jquery.ui/jquery-ui.min.js',null, BP_COMMON)
                ->addJs('jquery.ui/jquery.ui.touch-punch.min.js', null, BP_COMMON)
                ->addJs('jquery.datetimeaddon/jquery.datetimeaddon.min.js',null, BP_COMMON)
                ->addJs('lab/lab.min.js', null, BP_COMMON)
                ->addJs('jquery.form/jquery.form.js', null, BP_COMMON)
                ->addJs('jquery.cookie/jquery.cookie.js', null, BP_COMMON)
                ->addJs('jquery.rs.admindebug.js')
                ->addJs('jquery.rs.admin.js')
                ->addJs('jquery.rs.ormobject.js', null, BP_COMMON)
                ->meta
                    ->add(array('http-equiv' => 'X-UA-Compatible', 'content' => 'IE=Edge', 'unshift' => true));
        }
        
        $controller_name = $this->url->request('mod_controller', TYPE_STRING);
        
        if (preg_match('/^(.*?)-(.*)/', $controller_name, $match)) {
            //Строим полное имя фронт контроллера 
            $full_controller_name = '\\'.str_replace('-','\\', $match[1].'-controller-admin-'.$match[2]);

            if (class_exists($full_controller_name) && is_subclass_of($full_controller_name, '\RS\Controller\AbstractModule')) {
                $front_controller = new $full_controller_name();
                return $front_controller->exec();
            }
        }
        
        $this->e404();
    }
    
    /**
    * Авторизация пользователя
    */
    function actionAuth()
    {
        $error = "";
        $referer = \RS\Helper\Tools::cleanOpenRedirect( $this->url->request('referer', TYPE_STRING, $this->router->getUrl('main.admin')) );
        $data = array(
            'login' => $this->url->request('login', TYPE_STRING,''),
            'pass' => $this->url->request('pass', TYPE_STRING,''),
            'remember' => $this->url->request('remember', TYPE_INTEGER),
            'do' => $this->url->request('do', TYPE_STRING)
        );
        
        if ($this->url->isPost()) {
            if ($data['do'] == 'recover') {
                //Восстановление пароля
                $user_api = new \Users\Model\Api();
                $this->result->setSuccess( $user_api->sendRecoverEmail($data['login'], true) );
                if ($this->result->isSuccess()) {
                    $data['successText'] = t('Письмо успешно отправлено. Следуйте инструкциям в письме');
                    $this->result->addSection('successText', $data['successText']);
                } else {
                    $error = $user_api->getErrorsStr();
                    $this->result->addSection('error', $error);
                }
                
            } else {
                //Авторизация
                AppAuth::logout(null);
                
                $auth_result = AppAuth::login($data['login'], $data['pass'], $data['remember']);
                $this->result->setSuccess($auth_result);
                
                if ($auth_result) {
                    return $this->result->setNoAjaxRedirect($referer);
                } else {
                    $error = AppAuth::getError();
                    $this->result->addSection('error', $error);
                }
            }
                
            if ($this->url->isAjax()) {
                return $this->result;
            }            
        }
        
        return $this->authPage($error, $referer, false, $data);
    }
    
    /**
    * Возвращает диалог со сменой пароля пользователя
    */
    function actionChangePassword()
    {
        $hash = $this->url->get('uniq', TYPE_STRING);
        $user_api = new \Users\Model\Api();
        $error = '';
        $user = $user_api->getByHash($hash);
        if (!$user) {
            return $this->e404();
        }
        
        if ($this->url->isPost() && $this->url->checkCsrf('change_password')) {
            $new_pass = $this->url->post('new_pass', TYPE_STRING, '', false);
            $new_pass_confirm = $this->url->post('new_pass_confirm', TYPE_STRING, '', false);
            
            if ($user_api->changeUserPassword($user, $new_pass, $new_pass_confirm)) {
                //Авторизовываем пользователя
                AppAuth::setCurrentUser($user);
                return $this->redirect($this->router->getAdminUrl(false, null, false));
            } else {
                $error = $user_api->getErrorsStr();
            }
        }
        
        $this->view->assign(array(
            'current_lang' => \RS\Language\Core::getCurrentLang(),
            'locale_list' => \RS\Language\Core::getSystemLanguages(),        
            'uniq' => $hash,
            'user' => $user,
            'err' => $error
        ));
        return $this->wrapHtml( $this->view->fetch('%system%/admin/change_pass.tpl') );
    }
    
    
    /**
    * Отображает страницу авторизаии
    */
    function authPage($error = "", $referer = null, $js = true, $data = array())
    {
        $result_helper = new \RS\Controller\Result\Standard($this);
        $result_helper->setNeedAuthorize(true);
        
        if (!$this->url->isAjax()) {
            if ($referer === null) {
                $referer = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            }

            if (file_exists(\Setup::$ROOT.\Setup::$BRAND_SPLASH_IMAGE)) {
                $this->view->assign(array(
                    'alternative_background_url' => \Setup::$BRAND_SPLASH_IMAGE
                ));
            }
            
            $this->view->assign(array(
                'data' => $data,
                'current_lang' => \RS\Language\Core::getCurrentLang(),
                'locale_list' => \RS\Language\Core::getSystemLanguages(),
                'js' => $js,
                'err' => $error,
                'referer' => $referer,
                'auth_url' => $this->router->getAdminUrl(false, array('Act' => 'auth'), false)
            ));
            
            $body = $this->view->fetch('%system%/admin/auth.tpl');
            $result_helper->setHtml($this->wrapHtml($body));
        }
        
        return $result_helper;
    }
    
    /**
    * Изменяет язык администраторчкой панели
    */
    function actionChangeLang()
    {
        $referer = $this->url->request('referer', TYPE_STRING, '/');
        $lang = $this->url->request('lang', TYPE_STRING);
        
        \RS\Language\Core::setSystemLang($lang);
        $this->redirect($referer);
    }
    
    /**
    * Измняет текущий сайт в администраторской панели
    */
    function actionChangeSite()
    {
        $site = $this->url->get('site', TYPE_INTEGER, false);
        $referer = urldecode($this->url->get('referer', TYPE_BOOLEAN));
        
        \RS\Site\Manager::setAdminCurrentSite($site);
        if ($referer) {
            $this->redirect($referer);
        } else {
            $this->redirect($this->router->getAdminUrl(false, null, false));
        }
    }
    
    /**
    * Сбрасывает авторизацию
    */
    function actionLogout()
    {
        \RS\Application\Auth::logout();
        $this->redirect($this->router->getUrl('main.admin'));
    }        
    
    function actionInDebug()
    {
        \RS\Debug\Mode::enable();
        $this->redirect( \RS\Site\Manager::getSite()->getRootUrl(true) );
    }
    
    function actionOutDebug()
    {
        \RS\Debug\Mode::disable();
        $this->redirect( \RS\Site\Manager::getSite()->getRootUrl(true) );
    }
    
    function actionAjaxToggleDebug()
    {
        \RS\Debug\Mode::enable( !\RS\Debug\Mode::isEnabled() );
        return $this->result->setSuccess(true);
    }
    

    /**
    * Отображает страницу авторизации и прерывает выполнение скрипта, если у пользователя не хватает прав
    * 
    * @param mixed $group
    * @param mixed $need_admin
    * @return boolean(false)
    */
    function needAuthorize($need_group = null, $need_admin = false)
    {
        $result = \RS\Application\Auth::checkUserRight($need_group, $need_admin);
        if ($result !== true) {
            return $this->authPage($result);
        }
        return false;
    }    
    
    function actionCleanCache()
    {
        CacheCleaner::obj()->clean();
        return $this->result->setSuccess(true);
    }

    /**
     * Производит пересчет счетчиков.
     * Возвращает новые пересчитанные числа в браузер
     */
    function actionRecalculateMeters()
    {
        $meter = Meter::getInstance();
        $meter->recalculateNumbers();

        return $this->result->setSuccess(true)->addSection(array(
            'numbers' => $meter->getNumbers(),
            'nextRecalculation' => $meter->getNextRecalculateInterval()
        ));
    }

}

