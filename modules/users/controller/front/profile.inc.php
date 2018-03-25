<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Controller\Front;

use \RS\Orm\Type;

class Profile extends \RS\Controller\AuthorizedFront
{
    protected
        /**
        * Поля, которые следует ожидать из POST
        */
        $use_post_keys = array('is_company', 'company', 'company_inn', 
                            'name', 'surname', 'midname', 'sex', 'passport', 'phone', 
                            'e_mail', 'openpass', 'current_pass', 'openpass_confirm', 'captcha', 'data', 'changepass');
    
    function actionIndex()
    {
        $this->app->title->addSection(t('Профиль'));
        $this->app->breadcrumbs->addBreadCrumb(t('Профиль пользователя'));
        
        $user = clone \RS\Application\Auth::getCurrentUser();

        //Добавим объекту пользователя 2 виртуальных поля
        $user->getPropertyIterator()->append(array(
            'current_pass' => new Type\Varchar(array(
                'name' => 'current_pass',
                'maxLength' => '100',
                'description' => t('Текущий пароль'),
                'runtime' => true,
                'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete'=>'off')),
            )),
            'openpass_confirm' => new Type\Varchar(array(
                'name' => 'openpass_confirm',
                'maxLength' => '100',
                'description' => t('Повтор пароля'),
                'runtime' => true,
                'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete'=>'off')),
            )),
        ));

        $user->usePostKeys($this->use_post_keys);
        
        if ( $this->isMyPost() )
        {
            //В новых версиях шаблона обязательно проверяем на CSRF.
            $theme = new \RS\Theme\Item( \RS\Config\Loader::getSiteConfig()->theme );
            $version = @$theme->getThemeXml()->general->version;
            if (($theme->getName() == 'default' && \RS\Helper\Tools::compareVersion('3.0.0.42', $version)) || 
                ($theme->getName() != 'default' && \RS\Helper\Tools::compareVersion('1.0.0.16', $version))) 
            {
                $this->url->checkCsrf();
            }
            
            $user->checkData();
            
            //Изменяем пароль
            if ($user['changepass']) {
                $crypt_current_pass = $user->cryptPass($user['current_pass']);
                if ($crypt_current_pass === $user['pass']) {
                    $user['pass'] = $crypt_current_pass;
                } else {
                    $user->addError(t('Неверно указан текущий пароль'), 'current_pass');
                }
                
                if (strcmp($user['openpass'], $user['openpass_confirm']) != 0) {
                    $user->addError(t('Пароли не совпадают'), 'openpass');
                }                            
            }

            if (!$user->hasError() && $user->save($user['id'])) {
                $_SESSION['user_profile_result'] = t('Изменения сохранены');
                \RS\Application\Auth::setCurrentUser($user); //Обновляем в пользователя в текущей сессии
                $this->refreshPage();
            }            
        }
        $conf_userfields = $user->getUserFieldsManager();
                              
        $this->view->assign(array(
            'conf_userfields' => $conf_userfields,
            'user' => $user
        ));
        
        if (isset($_SESSION['user_profile_result'])) {
            $this->view->assign('result', $_SESSION['user_profile_result']);
            unset($_SESSION['user_profile_result']);
        }
        
        return $this->result->setTemplate('profile.tpl');
    }
}
?>
