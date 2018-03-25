<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace SiteUpdate\Controller\Admin;
use \RS\HashStore\Api as HashStoreApi;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar;
use SiteUpdate\Model\Api;


class Wizard extends \RS\Controller\Admin\Front
{
    protected $helper;

    /**
     * @var \SiteUpdate\Model\Api
     */
    protected $api;
    
    function init()
    {
        $this->api = new \SiteUpdate\Model\Api();

        $this->helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $this->helper
            ->viewAsAny()
            ->setTopTitle(t('Центр обновления'));
            
        $this->view->assign(array(
            'elements' => $this->helper,        
        ));
    }
    
    function actionIndex()
    {
        $can_update = $this->api->canCheckUpdate();
        //Проверяем права у модуля
        if ($error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            $can_update = false;
            $this->api->addError($error);
        }
        
        if ($this->url->isPost()) {
            $count = null;
            if ($this->api->prepareProductsForUpdate($count)) {
                if ($count>1) {
                    $this->result->setSuccess(true)->setAjaxWindowRedirect( $this->router->getAdminUrl('selectProduct') );
                } else {
                    $this->result->setSuccess(true)->setAjaxWindowRedirect( $this->router->getAdminUrl('update') );
                }
            } else {
                $this->result->setSuccess(false)->setErrors($this->api->getDisplayErrors());
            }
            return $this->result;
        }
        
        $this->view->assign(array(
            'isUpdateExpire' => $this->api->isUpdateExpire(),
            'expireSale' => $this->api->getSaleUpdateExpire(),
            'expireSaleDays' => $this->api->getSaleUpdateExpireDays(),
            'expireSaleBuyUrl' => $this->api->getSaleUpdateUrl(),
            
            'canUpdate' => $can_update,
            'errors' => $this->api->getDisplayErrors(),
            'currentStep' => '1'
        ));
        
        $this->helper['form'] = $this->view->fetch('checkupdate.tpl');
        return $this->result->setTemplate( $this->helper['template'] );                
    }
    
    function actionSelectProduct()
    {
        if ($this->url->isPost()) {
            $product = $this->url->post('update_product', TYPE_STRING);
            
            if ($this->api->prepareUpdateInfo( $product )) {
                $this->result->setSuccess(true)->setAjaxWindowRedirect( $this->router->getAdminUrl('update') );
            } else {
                $this->result->setSuccess(false)->setErrors($this->api->getDisplayErrors());
            }
            return $this->result;
        }
        
        $this->view->assign(array(
            'currentStep' => '2',
            'data' => $this->api->getPrepearedData()
        ));       
                
        $this->helper['form'] = $this->view->fetch('selectproduct.tpl');
        return $this->result->setTemplate( $this->helper['template'] ); 
    }
    
    
    function actionUpdate()
    {
        if ($this->url->isPost()) {
            $modules = $this->url->post('chk', TYPE_ARRAY);
            $is_start = $this->url->post('start', TYPE_INTEGER);
            
            if ($is_start) {
                HashStoreApi::get(Api::UPDATE_IN_PROGRESS_STORE_KEY, true);
                $data = $this->api->prepareInstallUpdate($modules);
            } else {
                $data = $this->api->doUpdate();
                if (!empty($data['complete'])) {
                    $_SESSION['SUCCESS_INSTALL_TEXT'] = t('Обновления успешно установлены');                    
                }
            }
            
            if (isset($data['errors']) && $this->api->canRestore()) {
                $this->api->restoreSystem();
            }

            if(isset($data['errors']) || !empty($data['complete'])){
                // Снятие флага "обновление выполняется"
                HashStoreApi::get(Api::UPDATE_IN_PROGRESS_STORE_KEY, false);
            }
            
            return json_encode($data);
        }
        
        //Для совместимости со старыми версиями
        $text_column = class_exists('\RS\Html\Table\Type\Text') ? '\RS\Html\Table\Type\Text' : '\RS\Html\Table\Type\String';
        
        $table = new \RS\Html\Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('module', array(
                    'cellAttrParam' => 'checkbox_attr'
                )),
                new TableType\Usertpl('title', t('Название модуля'), '%siteupdate%/module_col.tpl'),
                new $text_column('my_version', t('Текущая версия')),
                new $text_column('new_version', t('Доступная версия')),
                new TableType\Usertpl('module', '', '%siteupdate%/changelog_col.tpl')
            )
        ));
        
        $this->api->compareVersions();
        $data = $this->api->getPrepearedData();
        $table->setData( $data['updateData'] );

        $button = !count($data['updateData']) ? 
                    new ToolbarButton\Cancel($this->router->getAdminUrl(false) , t('назад')) 
                    : new ToolbarButton\SaveForm($this->router->getAdminUrl('update'), t('установить выбранные обновления'), array('attr' => array('class' => 'btn-success saveform'), 'noajax' => true));
        
        
        $this->helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array($button)
        )));        
        
        $this->view->assign(array(
            'success_text' => isset($_SESSION['SUCCESS_INSTALL_TEXT']) ? $_SESSION['SUCCESS_INSTALL_TEXT'] : false,
            'table' => $table,
            'data' => $data,
            'currentStep' => '3'
        ));
        
        unset($_SESSION['SUCCESS_INSTALL_TEXT']);
        $this->helper['form'] = $this->view->fetch('update.tpl');
        return $this->result->setTemplate( $this->helper['template'] );
    }
    
    function actionViewChangelog()
    {
        $module = $this->url->request('module', TYPE_STRING);
        $data = $this->api->getPrepearedData();
        $module_name = $data['updateData'][$module]['title'];
        
        $this->helper
            ->viewAsForm()
            ->setTopTitle(t('Изменения в модуле {name}'), array('name' => $module_name) )
            ->setBottomToolbar(new Toolbar\Element( array(
                'Items' => array(
                    new ToolbarButton\Cancel($this->router->getAdminUrl('update'))
                )
            )));
        
        $this->view->assign('changelog', $this->api->getChangelog($module));
        $this->helper['form'] = $this->view->fetch('view_changelog.tpl');
        
        return $this->result->setTemplate( $this->helper['template'] );
    }
    
    function actionDiscountUpdate()
    {
        if (property_exists('Setup', 'RS_SERVER_DOMAIN')) {
            $rs_domain = \Setup::$RS_SERVER_PROTOCOL.'://'.\Setup::$RS_SERVER_DOMAIN;
        } else {
            //Для совместимости с предыдущими версиями            
            $rs_domain = 'http://readyscript.ru';
        }
        
        $main_license = null;
        __GET_LICENSE_LIST($main_license);
        
        $this->view->assign(array(
            'url'         => $rs_domain.'/update-discount/',
            'license'     => $main_license['license_key'],
            'domain_hash' => md5($main_license['domain'])
        ));
        
        $this->wrapOutput(false);
        return $this->result->setTemplate('post_redirector.tpl');
    }
}
?>
