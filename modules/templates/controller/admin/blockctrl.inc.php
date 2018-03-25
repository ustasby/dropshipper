<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Templates\Controller\Admin;
use \RS\Module\AbstractModel,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar;

/**
* Настройка блоков в административной панели
* @ingroup Templates
*/
class BlockCtrl extends \RS\Controller\Admin\Crud
{
    protected
        $pageApi,
        $containerApi,
        $sectionApi,
        $sectionModuleApi;
    
    function __construct()
    {
        $this->pageApi = new \Templates\Model\PageApi();
        parent::__construct($this->pageApi);
        
        //Создаем типовые модели
        $this->containerApi = new \Templates\Model\ContainerApi();
        
        $this->sectionApi = new AbstractModel\EntityList(new \Templates\Model\Orm\Section, 
        array(
            'loadOnDelete' => true
        ));

        $this->sectionModuleApi = new AbstractModel\EntityList(new \Templates\Model\Orm\SectionModule,
        array(
            'loadOnDelete' => true
        ));
    }
    
    function actionIndex()
    {
        $page_id = $this->url->get('page_id', TYPE_INTEGER);
        $context = $this->url->get('context', TYPE_STRING, 'theme');
        
        $page = new \Templates\Model\Orm\SectionPage($page_id);
        $default_page = \Templates\Model\Orm\SectionPage::loadByRoute('default', $context);
        if (!$page['id']) {
            $page = $default_page;
        }
        $site_config = \RS\Config\Loader::getSiteConfig();
        $this->pageApi->setFilter('context', $context);
        $theme = \RS\Theme\Item::makeByContext($context);

        $pages = $this->pageApi->getList();
        //Сортируем страницы по алфавиту
        usort($pages, function($a, $b) {
            return strcmp($a->getRoute()->getDescription(), $b->getRoute()->getDescription());
        });

        $this->view->assign(array(
            'defaultPage' => $default_page,
            'currentPage' => $page,
            'currentTheme' => $site_config['theme'],
            'pages' => $pages,
            'context_list' => \RS\Theme\Manager::getContextList(),
            'context' => $context,
            'grid_system' => $theme->getGridSystem()
        ));
        return parent::actionIndex();
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Конструктор сайта'));
        $helper->setTopHelp($this->view->fetch('help/blockctrl_index.tpl'));
        $helper->setTopToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Add($this->url->replaceKey(array($this->action_var => 'add')), t('добавить страницу')),
                new ToolbarButton\Space(),
                new ToolbarButton\Button(null, t('сохранить эталон темы'), array(
                    'attr' => array(
                        'title' => t('Сохраняет block.xml в папке с темой'),
                        'class' => 'crud-get',
                        'data-confirm-text' => t('Вы действительно хотите перезаписать структуру блоков blocks.xml в каталоге темы ?'),
                        'data-url' => $this->router->getAdminUrl('saveTheme')
                    )
                )),
                new ToolbarButton\Button($this->url->replaceKey(array($this->action_var => 'export')), t('экспорт'), array(
                    'attr' => array(
                        'title' => t('Экспорт в XML файл')
                    )
                )),
                new ToolbarButton\Button($this->url->replaceKey(array($this->action_var => 'import')), t('импорт'), array(
                    'attr' => array(
                        'class' => 'crud-add',
                        'title' => t('Импорт из XML файла')
                    )
                )),
        ))));
        $helper['template'] = 'block_manager.tpl';
        return $helper;
    }
    
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $this->api = $this->pageApi;
        $helper = $primaryKeyValue ? $this->helperEdit() : $this->helperAdd();
        if ($primaryKeyValue) {
            $helper->setTopTitle(t('Редактировать страницу'));
        } else {
            $helper->setTopTitle(t('Добавить страницу'));
        }
        
        $this->setHelper($helper);
        $this->api->getElement()->tpl_module_folders = \RS\Module\Item::getResourceFolders('templates');
        
        if ($primaryKeyValue == null) {
            $elem = $this->api->getElement();
            $elem['inherit'] = 1;
            $elem['context'] = $this->url->request('context', TYPE_STRING, 'theme');
        }

        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }
    
    function actionEditPage()
    {
        $this->view->assign('dialogTitle', t('Редактирование страницы'));
        $id = $this->url->get('id', TYPE_STRING, 0);
        $context = $this->url->get('context', TYPE_STRING, 'theme');
        if ($id) {
            $this->pageApi->getElement()->load($id);
        } else {
            $page = \Templates\Model\Orm\SectionPage::loadByRoute('default', $context);
            $this->pageApi->getElement()->getFromArray($page->getValues());
            $id = $page['id'];
        }                     
        return $this->actionAdd($id);
    }
    
    function actionDelPage()
    {
        $this->api = $this->pageApi;
        return parent::actionDel();
    }
    
    function actionCopyContainer()
    {
        $context = $this->url->request('context', TYPE_STRING, 'theme');        
        if ($this->url->isPost()) {
            $to_page           = $this->url->request('page_id', TYPE_INTEGER);
            $to_container_type = $this->url->request('type', TYPE_INTEGER);
            $from_container    = $this->url->request('from_container', TYPE_INTEGER);
            
            $this->result->setSuccess( $this->containerApi->copyContainer($from_container, $to_page, $to_container_type) );
            
            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if (!$this->result->isSuccess()) {
                    $this->result->addEMessage($this->containerApi->getErrorsStr());
                } else {
                    $this->result->setSuccessText(t('Изменения успешно сохранены'));
                    if (!$this->url->request('dialogMode', TYPE_INTEGER)) {
                        $this->result->setAjaxWindowRedirect( $this->url->getSavedUrl($this->controller_name.'index') );
                    }
                }
                return $this->result->getOutput();
            }
            
            if ($this->result->isSuccess()) {
                if ($returnOnSuccess) return true; 
                    else $this->successSave();
            } else {
                $helper['formErrors'] = $orm_object->getDisplayErrors();
            }
        }
        
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper->viewAsForm()->setTopTitle(t('Копировать контейнер'));
        $helper->setBottomToolbar($this->buttons(array('save', 'cancel')));
        
        $pages = $this->api->setFilter('context', $context)->getAssocList('id');
        $containers = !$pages ? array() : $this->containerApi
                                               ->setFilter('page_id', array_keys($pages), 'in')
                                               ->queryObj()
                                               ->objects(null, 'page_id', true);
        
        $this->view->assign(array(
            'pages' => $pages,
            'containers' => $containers,
            'elements' => $helper
        ));
        $helper['form'] = $this->view->fetch( 'copy_container.tpl' );
        return $this->result->setTemplate( $helper['template'] );
    }
    
    function actionAddContainer($primaryKeyValue = null)
    {    
        $this->api = $this->containerApi;
        $helper = $this->helperAdd();
        $elem = $this->api->getElement();
        
        if (!$primaryKeyValue) {
            $page_id = $this->url->get('page_id', TYPE_INTEGER);
            $type = $this->url->get('type', TYPE_INTEGER);
            $elem->page_id = $page_id;
            $elem->type = $type;            
            $helper->setTopTitle(t('Добавить контейнер'));
        } else {
            $helper->setTopTitle(t('Редактировать контейнер'));
        }
                
        $grid_system = $this->pageApi->getPageGridSystem($elem->page_id);
        $helper->setFormSwitch($grid_system);        
        $elem->setColumnList($grid_system);

        $this->setHelper($helper);        
        return parent::actionAdd($primaryKeyValue);
    }
    
    function actionEditContainer()
    {
        $id = $this->url->get('id', TYPE_STRING, 0);
        if ($id) $this->containerApi->getElement()->load($id);
        return $this->actionAddContainer($id);
    }
    
    function actionRemoveContainer()
    {
        $this->api = $this->containerApi;
        return parent::actionDel();
    }
    
    function actionRemoveLastContainer()
    {
        $page_id = $this->url->get('page_id', TYPE_INTEGER, 0);
        $container = $this->containerApi->setFilter('page_id', $page_id)
                           ->setOrder('type DESC')
                           ->getFirst();
        
        if ($container['id']) {
            if ($container->delete()) {
                $this->result->setSuccess(true);
            } else {
                foreach($container->getErrors() as $error) {
                    $this->result->addEMessage($error);
                }
            }
        }
        return $this->result;        
    }    
    
    function actionAddSection($primaryKeyValue = null)
    {
        $this->api = $this->sectionApi;
        $elem = $this->api->getElement();
        $helper = $this->helperAdd();
        $element_type = $this->url->get('element_type', TYPE_STRING, 'col');
        
        if (!$primaryKeyValue) {
            $parent_id = $this->url->get('parent_id', TYPE_INTEGER);
            $page_id = $this->url->get('page_id', TYPE_INTEGER);

            $elem->page_id = $page_id;
            $elem->parent_id = $parent_id;
            $elem->element_type = $element_type;
            if ($element_type == 'row') {
                $helper->setTopTitle(t('Добавить строку'));
            } else {
                $helper->setTopTitle(t('Добавить секцию'));
            }
        } else {
            $helper->setTopTitle(t('Редактировать секцию'));
        }

        
        if ($elem->element_type == 'row') {
            $switch = 'row';
        } else {
            //Определяем тип сеточного фреймворка, чтобы выставить параметры формы
            $switch = $this->pageApi->getPageGridSystem($elem->page_id);
        }
        $helper->setFormSwitch($switch);
        
        //Устанавливаем максимальную ширину секции
        if ($elem['parent_id']<0) {
            $this->containerApi->setFilter('type', abs($elem['parent_id']));
            $this->containerApi->setFilter('page_id', $elem['page_id']);
            $container = $this->containerApi->getFirst();
        } else {
            $parent_section = $this->sectionApi->getOneItem(abs($elem['parent_id']));
            $container = $parent_section->getContainer();
            if (!$container['id']) $container = false;
        }
        
        if ($container) {
            $pwidth = $this->api->getElement()->getProp('width');
            $pwidth->setListFromArray(array_combine(range(1, $container['columns']), range(1, $container['columns'])));
        }
                
        $this->setHelper($helper);
        
        return parent::actionAdd($primaryKeyValue);
    }
    
    function actionEditSection()
    {
        $id = $this->url->get('id', TYPE_INTEGER);
        if ($id) $this->sectionApi->getElement()->load($id);
        return $this->actionAddSection($id);
    }
    
    function actionDelSection()
    {
        $this->api = $this->sectionApi;
        return parent::actionDel();
    }
    
    function actionAjaxMoveSection()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->addEMessage($access_error);
        }        
        
        $id = $this->url->get('section_id', TYPE_INTEGER);
        $position = $this->url->get('position', TYPE_INTEGER);
        $new_parent_id = $this->url->get('parent_id', TYPE_INTEGER);
        
        $this->sectionApi->getElement()->load($id);
        $result = array(
            'success' => $this->sectionApi->getElement()->moveToPosition($position, $new_parent_id)
        );
        
        if (!$result['success']) {
            $result['error'] = implode(',', $this->sectionApi->getElement()->getErrors());
        }
        return json_encode($result);
    }
    
    function actionAjaxMoveContainer()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->addEMessage($access_error);
        }        
        
        $source_id = $this->url->get('source_id', TYPE_INTEGER);
        $destination_id = $this->url->get('destination_id', TYPE_INTEGER);
        
        $container = $this->containerApi->getElement();
        return $this->result
            ->setSuccess($container->load($source_id) && $container->changePosition($destination_id));
    }    
    
    function actionAjaxMoveBlock()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->addEMessage($access_error);
        }
        
        $id = $this->url->get('block_id', TYPE_INTEGER);
        $position = $this->url->get('position', TYPE_INTEGER);
        $new_parent_id = $this->url->get('parent_id', TYPE_INTEGER);
        
        $this->sectionModuleApi->getElement()->load($id);
        $result = array(
            'success' => $this->sectionModuleApi->getElement()->moveToPosition($position, $new_parent_id)
        );
        
        if (!$result['success']) {
            $result['error'] = implode(',', $this->sectionModuleApi->getElement()->getErrors());
        }
        return json_encode($result);
    }    
    
    
    function actionAddModule()
    {
        $module_manager = new \RS\Module\Manager();
        $controllers_tree = $module_manager->getBlockControllers();
        $section_id = $this->url->get('section_id', TYPE_INTEGER);
        
        $this->view->assign(array(
            'controllers_tree' => $controllers_tree,
            'section_id' => $section_id
        ));
        $this->result->setHtml($this->view->fetch('block_manager_add_module_form.tpl'));
        return $this->result->getOutput();
    }
    
    function actionAddModuleStep2($primaryKeyValue = null)
    {
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, $this->sectionApi, $this->url);
        $helper->setBottomToolbar($this->buttons(array('save', 'cancel')));
        $helper->setTemplate('%templates%/crud-block-form.tpl');
                
        /**
        * @var \Templates\Model\Orm\SectionModule
        */
        $sectionModule = $this->sectionModuleApi->getElement();
        
        if (!$primaryKeyValue) {
            //Если добавляем новый блок
            $block = $this->url->get('block', TYPE_STRING);
            $section_id = $this->url->get('section_id', TYPE_INTEGER);
            
            $block_controller = \RS\Module\Item::getBlockControllerInstance($block);            
            if (!$block_controller) {
                throw new \RS\Controller\ParameterException('block_controller, section');
            }
            $sectionModule['section_id'] = $section_id;
            $sectionModule['module_controller'] = $block;
            $sectionModule['public'] = 1;
        }        
        
        $controller = $sectionModule->getControllerInstance();

        $block_info = $controller->getInfo();
        $helper->setTopTitle(t('Настройки блока {title}'), array('title' => $block_info['title']));
        $helper['block_controller'] = $sectionModule['module_controller'];
        
        $object = $controller->getParamObject();
        
        if ($object) {
            //Отображаем настройки модуля, если таковые имеются
            $object->getFromArray( $sectionModule->getParams() + $controller->getParam());
            $object->setLocalParameter('form_template', 'moduleblock_'.str_replace('\\', '_', $sectionModule['module_controller']));
            $helper['form'] = $object->getForm(null, null, false, null, null, $this->mod_tpl);
        } else {
            $helper['form'] = '';
            //Если у контроллера нет параметров, то сразу отдаем JSON
            if (!$primaryKeyValue) {
                if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
                    return $this->result
                            ->addSection('close_dialog', true)
                            ->addEMessage($access_error);
                }

                $sectionModule->insert();
                //Сбрасываем кэш
                \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
                return $this->result
                    ->addSection('close_dialog', true)
                    ->setNoAjaxRedirect($this->url->getSavedUrl($this->controller_name.'index'))
                    ->getOutput();
            }
        }
        
        $errors = array();
        if ($this->url->isPost()) {
            if ($object->checkData()) {
                $sectionModule->setParams($object->getValues());
                //Параметры контроллера заданы корректно.
                if ($sectionModule->save($primaryKeyValue)) {
                    $this->result->setSuccess(true);
                } else {
                    $errors = $sectionModule->getErrors();
                    $this->result->setSuccess(false)->setErrors($sectionModule->getErrors(), $sectionModule->getErrorsByForm());
                }
            } else {
                $errors = $object->getErrors();
                $this->result->setSuccess(false)->setErrors($object->getErrors(), $object->getErrorsByForm());                
            }
            //Сбрасываем кэш
            \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if ($this->result->isSuccess()) {
                    $this->result->setSuccessText(t('Изменения успешно сохранены'));
                }
                return $this->result->getOutput();
            }
            
            if ($this->result->isSuccess()) {
                $this->successSave();
            }
        }

        $this->view->assign(array(
            'elements' => $helper,
            'errors' => $errors
        ));
        return $this->result->setTemplate( $helper['template'] );
    }    
    
    /**
    * Возвращает окно редактирования блочного контроллера
    * 
    */
    function actionEditModule()
    {
        $id = $this->url->get('id', TYPE_INTEGER); //id блока
        $this->sectionModuleApi->getElement()->load($id);
        return $this->actionAddModuleStep2($id);
    }
    
    /**
    * Возращает окно для редактирования блочного контроллера, который был вставлен через moduleinsert в шаблон
    * Сохраняет настройки блока
    * сбрасывает кэш блока
    * 
    */
    function actionEditTemplateModule()
    {
       $block_id  = $this->request('_block_id',TYPE_INTEGER);    //id блока в кэше
       $block_url = $this->request('block',TYPE_STRING); //короткое имя контроллера блока 
       
       
       $api    = new \Templates\Model\TemplateModuleApi();
       /**
       * @var \RS\Controller\StandartBlock
       */
       if ($block = $api->getBlockFromCache($block_id,$block_url)){ //Подгрузим блок
           
           $block_info = $block->getInfo();
           $helper     = new \RS\Controller\Admin\Helper\CrudCollection($block);
           $helper->setBottomToolbar($this->buttons(array('save', 'cancel')));
           
           $helper->setTemplate($this->mod_tpl.'crud-block-form.tpl'); 
           $helper->setTopTitle(t('Настройки блока {title}'), array('title' => $block_info['title']));
           
           $block_info['block_class']  = mb_strtolower(get_class($block));
           $helper['block_controller'] = $block_info['block_class'];
           
           /**
           * @var \RS\Orm\ControllerParamObject
           */
           $object = $block->getParamObject(); //Получим объект с параметрами
           
           //Пытаемся получить окно для редактирования блока
           if ($object) { //Если получить объекть удалось
                //Отображаем настройки модуля, если таковые имеются
                $object->getFromArray($block->getParam());
                $object->setLocalParameter('form_template', 'moduleblock_'.str_replace('\\', '_', $block_info['block_class'])); // Установил параметр для генерирования шаблона
                $helper['form'] = $object->getForm(null, null, false, null, null, $this->mod_tpl);
           } else { //Если не извлеч параметры
                $helper['form'] = '';
                //Если не удалось получить объект, то сбросим кэш, т.к. он возможно устарел
                \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
                return $this->result->setSuccess(true)->setSuccessText(t('Попытка получить блок не удалась.<br/> Нужна перезагрука страницы'));
           }
           
           $errors = array();
           //Обработаем пост если он к нам пришёл
           if ($this->url->isPost()){
              if ($object->checkData()){ //Заполняем объект с проверкой
                 
                 //Сохранение
                 $api->saveBlockValues($block,$object); 
                 
                 //Сбросим кэш
                 \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM); 
                 $cleaner = new \RS\Cache\Cleaner();
                 $cleaner->clean($cleaner::CACHE_TYPE_COMMON);
                 $cleaner->clean($cleaner::CACHE_TYPE_TPLCOMPILE);
                 return $this->result->setSuccess(true)->setSuccessText('Успешно сохранено');
              } 
              //Или возвратим ошибку
              $errors = $object->getErrors();  
           }
           
           
           $this->view->assign(array(
                'elements' => $helper,
                'errors' => $errors
            ));
           
           return $this->result->setTemplate($helper['template']);
       }
       
       //Если не удалось получить объект, то сбросим кэш, т.к. он возможно устарел
       \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM); 
       
       //Перезагрузим страницу
       return $this->result->setSuccess(true)->setSuccessText(t('Попытка получить блок не удалась.<br/> Нужна перезагрука страницы'));
    }
    
    function actionDelModule()
    {
        $id = $this->url->get('id', TYPE_INTEGER);
        $this->api = $this->sectionModuleApi;
        return parent::actionDel();
    }
    
    function actionImport()
    {
        $context = $this->url->request('context', TYPE_STRING, 'theme');
        $helper = parent::helperAdd();
        $helper['form'] = $this->view->fetch('form/import.tpl');
        $helper->setTopTitle(t('Импорт структуры блоков'));
        
        $helper['bottomToolbar']->addItem(new ToolbarButton\SaveForm(null, t('импортировать')), 'save');
        if ($this->url->isPost()) {
            $this->result->setSuccess( $this->api->importXML($this->url->files('file', TYPE_ARRAY), $context ) );
            $helper['formErrors'] = $this->api->getDisplayErrors();
            
            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if (!$this->result->isSuccess()) {
                    $this->result->setErrors( $this->api->getDisplayErrors() );
                } else {
                    $this->result->setSuccessText(t('Данные успешно импортированы'));
                    $this->result->setAjaxRedirect( $this->router->getAdminUrl(false, array('context' => $context)) );
                    if (!$this->url->request('dialogMode', TYPE_INTEGER)) {
                        $this->result->setAjaxWindowRedirect( $this->url->getSavedUrl($this->controller_name.'index') );
                    }
                }
                return $this->result->getOutput();
            }
        }
        
        $this->view->assign('elements', $helper);
        return $this->result->setTemplate( $helper['template'] );
    }
    
    function actionExport()
    {
        $context = $this->url->request('context', TYPE_STRING);
        $this->wrapOutput(false);
        $filename = 'blocks.xml';
        $this->app->headers->addHeaders(array(
            'Content-Type' => 'text/xml',
            'Content-Transfer-Encoding' => 'binary',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Connection' => 'close'
        ));
        return $this->api->getBlocksXML($context);
    }
    
    function actionSaveTheme()
    {
        $this->result
            ->setSuccess( $this->api->saveThemeBlocks() )
            ->addSection( 'noUpdate', true);
            
        if ($this->result->isSuccess()) {
            $this->result->addMessage(t('Структура блоков успешно сохранена в теме'));
        } else {
            $this->result->addEMessage( $this->api->getErrorsStr() );
        }
        return $this->result;
    }
    
    function helperContextOptions()
    {
        return parent::helperAdd();
    }
    
    function actionContextOptions()
    {
        $context = $this->url->request('context', TYPE_STRING);
        
        $helper = $this->getHelper();
        
        $theme = \RS\Theme\Item::makeByContext($context);
        $theme_info = $theme->getInfo();
        $options = $theme->getContextOptions();

        if ($this->url->isPost()) {            
            $options->replaceOn(true);
            $this->result->setSuccess($options->save());
            if ($this->result->isSuccess()) {
                $this->result->setSuccess(true)
                             ->setSuccessText(t('Изменения успешно сохранены'));
            } else {
                $this->result->setErrors($options->getDisplayErrors());
            }
            
            return $this->result;
        }        
        
        //Получаем динамический объект для генерации формы
        $form_object = $options->getContextFormObject();
        
        $helper['form'] = $form_object->getForm();
        $helper->setTopTitle(t('Настройка темы {title}'), array('title' => $theme_info['name']));
        
        return $this->result->setTemplate( $helper['template'] );
    }
    
    function actionAjaxToggleViewModule()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ACCESS_BIT_WRITE)) {
            return $this->result->addEMessage($access_error);
        }        
                
        $block_id = $this->url->request('id', TYPE_INTEGER);
        $module = new \Templates\Model\Orm\SectionModule($block_id);

        if (!$module['id']) {
            return $this->e404(t('Модуль не найден'));
        }
        $module['public'] = !$module['public'];
        $module->update();
        
        //Очищаем кэш, связанный с блоками
        \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
        
        return $this->result->setSuccess(true);
    }
}
