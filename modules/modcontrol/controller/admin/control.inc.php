<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ModControl\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Filter,
    \RS\Html\Toolbar,
    \RS\Html\Table;

class Control extends \RS\Controller\Admin\Front
{   
    protected 
        $action_var = 'do',
        $form_tpl = 'forms/%MODULE%_form.tpl',
        $modules;
        
    function __construct()
    {
        parent::__construct();
        $this->api = new \ModControl\Model\ModuleApi();
        
        $this->app->addCss( $this->mod_css.'mcontrol.css','mcontrol', BP_ROOT);
        $this->app->addJs( $this->mod_js.'mcontrol.js','mcontrol', BP_ROOT);        
    }
    
    /**
    * Отображение списка
    */
    public function actionIndex()
    {
        $helper = $this->helperIndex();

        $event_name = 'controller.exec.'.$this->getUrlName().'.index'; //Формируем имя события
        $helper = \RS\Event\Manager::fire($event_name, $helper)->getResult();

        $helper->setTopTitle(t('Настройка модулей'));
        $this->view->assign('elements', $helper->active());
        $this->url->saveUrl($this->controller_name.'index');
        return $this->result->setHtml($this->view->fetch( $helper['template'] ))->getOutput();
    }    
    
    function helperIndex()
    {
        
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, $this->api, $this->url);
        $helper->viewAsTable();
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('class', array(
                    'cellAttrParam' => 'checkbox_attribute'
                )),
                new TableType\Text('name', t('Название'), array(
                    'href' => $this->router->getAdminPattern('edit', array(':mod' => '@class'))
                )),
                new TableType\Text('description', t('Описание')),
                new TableType\Text('version', t('Версия'), array('TdAttr' => array('class'=> 'cell-small'))),                
                new TableType\Usertpl('enabled', t('Включен'), '%modcontrol%/col_enabled.tpl'),
                new TableType\Text('class', t('Идентификатор'), array('ThAttr' => array('width' => '50'))),
                new TableType\Actions('class', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':mod' => '~field~')), null, array('noajax' => true)),
                    ))
                ),
            'rowAttrParam' => 'row_attributes'
        )));
        
        $helper->setFilter(new Filter\Control( array(
            'container' => new Filter\Container( array( 
                                'lines' =>  array(
                                    new Filter\Line( array('items' => array(
                                                            new Filter\Type\Text('name', t('Название')),
                                                            new Filter\Type\Text('class', t('Идентификатор')))
                                                        )
                                    ))
                                )
                            ))
            ));
        
        $helper->setListFunction('tableData');

        // Если не установлен запрет на установку модулей
        if(!defined('CANT_UPLOAD_MODULE'))
        {
            $helper->setTopToolbar(new Toolbar\Element( array(
                'Items' => array(
                    new ToolbarButton\Add($this->url->replaceKey(array($this->action_var => 'add')), t('добавить модуль'), array('noajax' => false)),
                )
            )));
        }

        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Delete(null, null, array('attr' =>
                    array('data-url' => $this->router->getAdminUrl('del'))
                )),
        ))));

        return $helper;
    }
    
    /**
    * Окно редактирования модуля  
    * Сохраняет настройки модуля
    * 
    */
    function actionEdit()
    {
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper->setTemplate($this->mod_tpl.'crud_module.tpl');
        
        $modname = $this->url->request('mod', TYPE_STRING);
        
        $mod = new \RS\Module\Item($modname);
        $config_obj = $mod->getConfig();
        
        if (!$config_obj) $this->e404(t('Такого модуля не существует'));
        $helper->setTopTitle(t('Настройка модуля').' {name}', $config_obj);
        
        //Если пост идет для текущего модуля
         if ($this->url->isPost()) 
        {            
            $this->result->setSuccess( $config_obj->save(1) );

            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if (!$this->result->isSuccess()) {
                    $this->result->setErrors($config_obj->getDisplayErrors(), $config_obj->getErrorsByForm());
                } else {
                    $this->result->setSuccessText(t('Изменения успешно сохранены'));
                }
                return $this->result->getOutput();
            }
            
            if ($this->result->isSuccess()) {
                $this->successSave();
            } else {
                $error = $config_obj->getLastError();
            }
        } 
        
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\SaveForm(null, null, null, true),
                new ToolbarButton\Cancel($this->url->getSavedUrl($this->controller_name.'index'))
        ))));
        
        $helper['form'] = $config_obj->getForm(null, null, false, null, '%system%/coreobject/config_form.tpl', $this->mod_tpl);
        
        $this->view->assign(array(
            'controller_list' => $mod->getBlockControllers(),
            'module_item' => $mod,
            'elements' => $helper,
            'errors' => isset($error) ? $error : array()
        ));
        
        return $this->result->setTemplate($helper['template']);
    }
    
    
    
    /**
    * Добавляем модуль
    */
    function actionAdd()
    {
        // Если установлен запрет на установку модулей
        if(defined('CANT_UPLOAD_MODULE')) return;

        $mod_install = \RS\Module\Installer::getInstance();
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);        
        
        //Если пост идет для текущего модуля
        if ($this->url->isPost()) {
            $file = $this->url->files('module');
            $this->result->setSuccess( $mod_install->extractFromPost($file) );

            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if (!$this->result->isSuccess()) {
                    $this->result->setErrors($mod_install->getDisplayErrors());
                    
                } else {
                    $this->result->setAjaxWindowRedirect( $this->router->getAdminUrl('addStep2') );
                }
                return $this->result->getOutput();
            }
            
            if ($this->result->isSuccess()) {
                $this->redirect( $this->router->getAdminUrl('addStep2') );
            } else {
                $helper['formErrors'] = $orm_object->getDisplayErrors();
            }
        }
        
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\SaveForm(null, t('Далее')),
                new ToolbarButton\Cancel($this->router->getadminUrl(false))
            )
        )));
        
        $helper->setTopTitle(t('Установка модуля'));
        $helper->viewAsForm();
        
        $this->view->assign(array(
            'is_empty_tmp' => $mod_install->isEmptyTmp(),
            'elements' => $helper
        ));
        
        $helper['form'] = $this->view->fetch('add.tpl');
        return $this->result->setTemplate( $helper['template'] );
    }
    
    /**
    * Информация о распакованом модуле
    */
    function actionAddStep2()
    {        
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);        
        $helper
            ->setTopTitle( t('Параметры установки модуля') )
            ->viewAsForm();
            
        $mod_install = \RS\Module\Installer::getInstance();        
        
        if ($this->url->isPost()) {
            $mod_install->setOption('insertDemoData', $this->url->request('insertDemoData', TYPE_BOOLEAN, false));
            
            if ($mod_install->installFromTmp()) {
                $_SESSION['INSTALLED_MODULE'] = $mod_install->getModName();
                return $this->result->setAjaxWindowRedirect($this->router->getAdminUrl('addSuccess'));
            } else {
                return $this->result->setErrors($mod_install->getDisplayErrors());
            }
        }
        
        $valid = $mod_install->validateTmp();
        $this->view->assign(array(
            'mod_validate' => $valid,
            'mod_errors' => $mod_install->getErrors(),
            'mod_info' => $mod_install->getTmpInfo(),
            'elements' => $helper
        ));        
        
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                'next' => new ToolbarButton\SaveForm($this->router->getAdminUrl('addStep3'), t('установить')),
                'back' => new ToolbarButton\Cancel($this->router->getAdminUrl(false), t('назад')),
                'clean' => new ToolbarButton\Button($this->router->getAdminUrl('cleanTmp'), t('удалить модуль из временной папки'), array(
                    'attr' => array(
                        'class' => 'btn-danger'
                    )
                )),
            )
        )));          
        
        if (!$valid) {
            $helper['bottomToolbar']->removeItem('next');
        }

        $helper['form'] = $this->view->fetch('add_step2.tpl');
        return $this->result->setTemplate( $helper['template'] );
    }
    
    function actionAddSuccess()
    {
        if (!isset($_SESSION['INSTALLED_MODULE'])) {
            $this->redirect( $this->router->getAdminUrl(false) );
        }
        
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper
            ->viewAsForm()
            ->setTopTitle(t('Установка модуля завершена'))
            ->setBottomToolbar(new Toolbar\Element( array(
                'Items' => array(
                    new ToolbarButton\Cancel($this->router->getAdminUrl(false), t('к списку модулей'))
                )
            )));
        
        $this->view->assign(array(
            'elements' => $helper,
            'module_name' => $_SESSION['INSTALLED_MODULE']
        ));
        unset($_SESSION['INSTALLED_MODULE']);
        $helper['form'] = $this->view->fetch('add_ok.tpl');
        
        return $this->result->setTemplate( $helper['template'] );
    }
    
    function actionCleanTmp()
    {
        $mod_install = \RS\Module\Installer::getInstance();
        $mod_install->cleanTmpFolder();
        return $this->result
            ->setSuccess(true)
            ->setRedirect($this->router->getAdminUrl(false));
    }

    /**
    * Удаляет модуль
    */
    function actionDel()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
            
        $chk = $this->url->request('chk', TYPE_ARRAY, array());
        $mod_install = \RS\Module\Installer::getInstance();
        
        if (!$mod_install->uninstallModules($chk)) {
            foreach($mod_install->getErrors() as $error) {
                $this->result->addEMessage($error);
            }
        }
        
        return $this->result->setSuccess( true );
    }
   
    
    function successSave()
    {
        header('location: '.$this->url->replaceKey(array($this->action_var => '')));
        exit; 
    }
    
    /**
    * Устанавливает или переустанавливает модуль
    */
    function actionAjaxReInstall()
    {
        $mod = $this->url->request('module', TYPE_STRING);
        $module = new \RS\Module\Item($mod);
        if ($module->exists()) {
            $this->result->setSuccess( ($install_result = $module->install()) === true );
            
            if ($this->result->isSuccess()) {
                $this->result->addMessage(t('Модуль успешно установлен'));
            } else {
                foreach($install_result as $error) {
                    $this->result->addEMessage($error);
                }
            }
        }
        
        return $this->result;
    }
    
    function actionAjaxInstallDemoData()
    {
        $mod       = $this->url->request('module', TYPE_STRING);
        $params    = $this->url->request('params', TYPE_ARRAY);
        
        $module = new \RS\Module\Item($mod);
        if ($module->exists() && ($install = $module->getInstallInstance())!==false ) {
            $access_error = \RS\AccessControl\Rights::CheckRightError($mod, ACCESS_BIT_WRITE);
            if (!$access_error) {
                $result = $install->insertDemoData($params);
                
                $this->result->setSuccess( $result );
                if ($this->result->isSuccess()) {
                    if ($result===true){
                       $this->result->addMessage(t('Данные успешно добавлены')); 
                    }else{
                       $this->result
                           ->addSection('repeat',true)
                           ->addSection('queryParams',array(
                             'data'=> array(
                                'params' => $result
                             )
                           )); 
                    }
                } else {
                    foreach($install->getErrors() as $error) {
                        $this->result->addEMessage($error);
                    }
                }
            } else {
                $this->result->addEMessage($access_error);
            }
        }
        return $this->result;
    }
    
    function actionAjaxShowChangelog()
    {
        $mod = $this->url->request('module', TYPE_STRING);
        $module = new \RS\Module\Item($mod);
        
        if ($module->exists()) {
            $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
            $helper->setTopTitle(t('История изменений модуля {module_title}'), array('module_title' => $module->getConfig()->name));
            $helper->setBottomToolbar(new Toolbar\Element(array(
                'items' => array(
                    new ToolbarButton\Cancel('')
                )
            )));
            $helper->viewAsForm();        
            
            $this->view->assign(array(
                'module_item' => $module
            ));
            
            $helper['form'] = $this->view->fetch('show_changelog.tpl');
            $this->result->setTemplate( $helper['template'] );
        }
        
        return $this->result;
    }

    function actionAjaxModuleList()
    {
        $this->view->assign(array(
            'modules' => $this->api->tableData()
        ));

        return $this->result
                        ->addSection('title', t('Перейти к настройкам модуля'))
                        ->setTemplate('module_list.tpl');
    }
}
