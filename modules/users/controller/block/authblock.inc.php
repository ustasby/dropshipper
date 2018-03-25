<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Controller\Block;

/**
* Блок авторизации
*/
class AuthBlock extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Блок авторизации',        //Краткое название контроллера
        $controller_description = 'Отображает ссылки войти/зарегистрироваться или имя текущего пользователя и персональным меню';  //Описание контроллера        
        
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/authblock/authblock.tpl', //Должен быть задан у наследника
        );    
    
    function actionIndex()
    {
        if (\RS\Module\Manager::staticModuleExists('shop')) {
            $shop_config = \RS\Config\Loader::byModule('shop');
            $this->view->assign(array(
                'use_personal_account' => $shop_config->use_personal_account,
                'return_enable' => $shop_config->return_enable
            ));
        }
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}
?>
