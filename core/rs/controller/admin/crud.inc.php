<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/


namespace RS\Controller\Admin;
use \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Toolbar;

/**
* Стандартный конроллер спискового компонента.
* У которого есть табличная форма, форма создания, форма редактирования, форма мультиредактирования
*/
abstract class Crud extends Front
{        
    protected 
        $allow_crud_actions,
        $sqlMultiUpdate = true, //Если True - то используется метод api->multiUpdate иначе открывается каждый coreobject и персонально обновляется во время группового редактирования.
        $selectAllVar = 'selectAll',
        $edit_url_var = 'edit_url',
        $sess_where = '_list', //имя переменной в сесии с условием последней выборки должно быть следующим ИМЯ_КОНТРОЛЕРА.        
        $multiedit_check_func, //callback, вызываемый для проверки данных при мультиредактировании
        $user_post_data = array(), //Этот массив мержится с массивом POST
        $api; //Наследник \RS\Module\AbstractModel\EntityList
    
    private
        $helper;

    public
        $edit_call_action = 'actionAdd';
    
    function __construct(\RS\Module\AbstractModel\EntityList $api)
    {
        parent::__construct();
        $this->api = $api;
    }
    
    /**
    * Отображение списка
    */
    public function actionIndex()
    {
        $helper = $this->getHelper();
        $this->view->assign('elements', $helper->active());
        $this->url->saveUrl($this->controller_name.'index');
        $this->api->saveRequest($this->controller_name.'_list');
        return $this->result->setHtml($this->view->fetch( $helper['template'] ))->getOutput();
    }
    
    /**
    * Вызывается перед действием Index и возвращает коллекцию элементов, 
    * которые будут находиться на экране.
    */
    protected function helperIndex()
    {
        return new Helper\CrudCollection($this, $this->api, $this->url, array(
            'paginator', 
            'topToolbar' => $this->buttons(array('add')),
            'bottomToolbar' => $this->buttons(array('delete')),
            'viewAs' => 'table'
        ));
    }        

    /**
    * Форма добавления элемента
    * 
    * @param mixed $primaryKeyValue - id редактируемой записи
    * @param boolean $returnOnSuccess - Если true, то будет возвращать === true при успешном сохранении,
    *                                   иначе будет вызов стандартного _successSave метода
    * @param null|Helper\CrudCollection $helper - текуй хелпер
    * @return \RS\Controller\Result\Standard|bool
    */
    public function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {                                      
        if ($primaryKeyValue < 0 || $primaryKeyValue === 0) $primaryKeyValue = null;
        $orm_object = $this->api->getElement();
        
        if ($helper === null) {
            $helper = $this->getHelper();
        }
        
        if ($primaryKeyValue === null) {
            $orm_object->fillDefaults();
        }
        //Если пост идет для текущего модуля
        if ($this->url->isPost()) 
        {            
            $this->result->setSuccess( $this->api->save($primaryKeyValue, $this->user_post_data) );

            if ($this->url->isAjax()) { //Если это ajax запрос, то сообщаем результат в JSON
                if (!$this->result->isSuccess()) {
                    $this->result->setErrors($orm_object->getDisplayErrors());
                } else {
                    $this->result->setSuccessText(t('Изменения успешно сохранены'));
                    if ($primaryKeyValue === null && !$this->url->request('dialogMode', TYPE_INTEGER)) {
                        $this->result->setAjaxWindowRedirect( $this->url->getSavedUrl($this->controller_name.'index') );
                    }
                }
                if ($returnOnSuccess) {
                    return true;
                } else {
                    return $this->result;
                }
            }
            
            if ($this->result->isSuccess()) {
                if ($returnOnSuccess) return true; 
                    else $this->successSave();
            } else {
                $helper['formErrors'] = $orm_object->getDisplayErrors();
            }
        } 
        
        $this->view->assign(array(
            'elements' => $helper->active(),
        ));
        return $this->result->setTemplate( $helper['template'] );
    }

    /**
     * Подготавливает Helper объекта для добавления
     *
     * @return Helper\CrudCollection
     */
    protected function helperAdd()
    {
         return new Helper\CrudCollection($this, $this->api, $this->url, array(
            'bottomToolbar' => $this->buttons(array('save', 'cancel')),
            'viewAs' => 'form',
            'formTitle' => t('Добавить')
         ));
    }

    /**
     * Редактирование элемента
     *
     * @return mixed
     */
    public function actionEdit()
    {
        $id = $this->url->get('id', TYPE_STRING, 0);
        if ($id) $this->api->getElement()->load($id);

        //Отмечаем объект просмотренным
        //Передаем в JS сведения с новыми счетчиками
        if ($this->api instanceof \Main\Model\NoticeSystem\HasMeterInterface) {
            $meter_api = $this->api->getMeterApi();
            $new_counter = $meter_api->markAsViewed($id);
            $this->result->addSection(array(
                'meters' => array(
                    $meter_api->getMeterId() => $new_counter
                ),
                'markViewed' => array(
                    $meter_api->getMeterId() => $id
                )
            ));
        }

        return $this->{$this->edit_call_action}($id);
    }
    
    protected function helperEdit()
    {
         return $this->helperAdd()
                     ->setBottomToolbar($this->buttons(array('saveapply', 'cancel')))
                     ->setTopTitle(t('Редактировать'));
    }

    /**
     * Удаляет записи
     *
     * @return mixed
     */
    public function actionDel()
    {
        $ids = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array(), false) );        
        $id = $this->url->get('id', TYPE_STRING, false);
        
        if (empty($ids) && !empty($id)) {
            $ids = (array)$id;
        }
        $result = $this->api->multiDelete($ids);

        if ($result && $this->api instanceof \Main\Model\NoticeSystem\HasMeterInterface) {

            //Передаем в JS сведения с новыми счетчиками
            $meter_api = $this->api->getMeterApi();
            $new_counter = $meter_api->removeViewedFlag($ids);

            $this->result->addSection(array(
                'meters' => array(
                    $meter_api->getMeterId() => $new_counter
                )
            ));
        }
        
        //Если передан параметр redirect, то перенаправляем пользователя
        if ($this->url->isAjax()) {
            $return = $this->result
                ->setSuccess($result);
            
            if (!$result) {
                foreach($this->api->getElement()->getErrors() as $error) {
                    $return->addEMessage($error);
                }
                foreach($this->api->getErrors() as $error) {
                    $return->addEMessage($error);
                }
            }
            return $return->getOutput();            
        } else {
            $this->redirectToIndex();
        }
    }
    
    function redirectToIndex()
    {
        $redirect_url = urldecode($this->url->request('redirect', TYPE_STRING)) ;
        
        if (!empty($redirect_url)) {
            if (preg_match('/^saved:(.*)$/u', $redirect_url, $match)) {
                $redirect_url = $this->url->getSavedUrl($this->controller_name.$match[1], '?');
            }
        } else {
            $redirect_url = $this->url->getSavedUrl($this->controller_name.'index');
        }
        
        if (!empty($redirect_url)) $this->redirect($redirect_url);
    }

    /**
     * Успешное сохранение объекта и редирект
     */
    function successSave()
    {
        $this->redirect($this->url->getSavedUrl($this->controller_name.'index'));
    }

    /**
     * Возвращает диалог настройки таблицы
     *
     * @return mixed
     * @throws \Exception
     * @throws \SmartyException
     */
    function actionTableOptions()
    {   
        $helper = $this->getHelper();
        $this->view->assign('elements', $helper);
        $helper['form'] = $this->view->fetch('%system%/admin/tableoptions.tpl');
        return $this->result->setHtml( $this->view->fetch($helper['template']) )->getOutput();
    }

    /**
     * Подготавливает Helper для опций таблицы
     *
     * @return Helper\CrudCollection
     * @throws \RS\Event\Exception
     */
    function helperTableOptions()
    {
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, $this->api, $this->url);
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                'save' => new ToolbarButton\Button(null, t('сохранить'), array('attr' => array(
                    'class' => 'btn-success saveToCookie'
                ))),
                'cancel' => $this->buttons('cancel'),
                'reset' => new ToolbarButton\Button(null, t('Сброс'), array('attr' => array(
                    'class' => 'btn-danger reset'
                )))
             )))
        )->setTopTitle(t('Настройка таблицы'));
        
        $index_helper = $this->helperIndex(); //Получаем структуру таблицы из helper'а

        $event_name = 'controller.exec.'.$this->getUrlName().'.index'; //Формируем имя события
        $index_helper = \RS\Event\Manager::fire($event_name, $index_helper)->getResult();

        if (isset($index_helper['table'])) {    
            $index_helper['table']->fill();
            $helper['tableOptionControl'] = $index_helper['table'];
        }        
        
        $helper->viewAsForm();
        return $helper;
    }

    /**
     * Групповое редактирование элементов
     *
     * @return \RS\Controller\Result\Standard
     * @throws \Exception
     * @throws \SmartyException
     */
    function actionMultiedit()
    {
        $ids = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array()) );
        
        if (count($ids) == 1) { //Перекидываем на обычное редактирование, если выбран один элемент
            $edit_url = $this->url->request($this->edit_url_var, TYPE_STRING,  $this->router->getAdminUrl('edit', array('id' => reset($ids))) );
            $this->redirect(str_replace('%ID%', reset($ids), $edit_url));
        }
        
        $doedit = $this->url->request('doedit', TYPE_ARRAY, array());
        $this->param['name'] .= 'multi';
        
        if ($this->url->isPost() && !empty($ids)) {
            
            $obj        = $this->api->getElement();
            $allow_keys = $obj->getProperties()->getMultieditKeys();    
            $post       = array_intersect_key($_POST, $allow_keys);
            
            //Устанавливаем checkBox'ы
            foreach($allow_keys as $key=>$val) {
                if (isset($obj['__'.$key])) {       
                    $property = $obj->getProp($key);
                    
                    if (count($property->getCheckboxParam())) {
                        $post[$key] = isset($post[$key]) ? $property->getCheckboxParam('on') : $property->getCheckboxParam('off');
                    }
                    if ($property instanceof \RS\Orm\Type\ArrayList && !isset($post[$key])) $post[$key] = array();
                }
            }

            $post = array_intersect_key($post, array_flip($doedit));
            $this->result->setSuccess(empty($post));
            
            $element_class = $this->api->getElementClass();                    
            $prototype = new $element_class();
            
            //Экранируем необходимые значения
            foreach($post as $key => $value) {
                if (isset($prototype['__'.$key])) {
                    $post[$key] = $prototype['__'.$key]->escape($value);
                }
            }
                        
            if (!empty($post)) {
                $obj->setCheckFields($doedit);
                if ($obj->checkData($post, array(), array(), $doedit)
                    //Проводим дополнительую проверку, если установлено свойство multiedit_check_func
                    && (!isset($this->multiedit_check_func) || call_user_func($this->multiedit_check_func, $obj, $post, $ids))) 
                {
                    
                    if ($this->sqlMultiUpdate) {
                        $this->api->clearFilter();
                        $this->api->setFilter($this->api->getIdField(), $ids, 'in');
                        $this->api->multiUpdate($post, $ids);
                    } else {
                        foreach($ids as $id) {
                            $prototype->load($id);
                            $prototype->setCheckFields($doedit);
                            $prototype->save($id, $post, array(), array());
                            if ($prototype->hasError()) {
                                $prototype->addError(t("Во время обработки элемента %0 произошла ошибка", array($id)));
                                $error = $prototype->getLastError();
                                break;
                            }
                        }
                    }
                    $this->result->setSuccess(true);
                } else {
                    $error = $obj->getLastError();
                    $this->result->setSuccess(false)->setErrors($obj->getDisplayErrors());
                }
            }
            
            if ($this->url->isAjax()) {
                return $this->result->getOutput();
            } else {
                if ($this->result->isSuccess()) {
                    $this->successSave();
                }
            }
            
        } //POST
        
        $hidden_fields = array();
        if ($this->url->request($this->selectAllVar, TYPE_STRING) == 'on') {
            $hidden_fields[$this->selectAllVar] = 'on';
        } else {
            foreach($ids as $key=>$id)
                $hidden_fields["chk[$key]"] = $id;
        }
        
        $this->app->addJs('jquery.rs.ormobject.js','jquery.rs.ormobject.js');
         

        $helper = $this->getHelper();
        $helper['hiddenFields'] = $hidden_fields;
        
        $this->view->assign(array(
            'elements' => $helper,
            'errors' => isset($error) ? $error  : array(),
            'param' => array(
                'doedit' => $doedit, 
                'ids' => $ids, 
                'sel_count' => count($ids)
            )
        ));
       
        $this->result->setHtml( $this->view->fetch( $helper['template'] ) );
        return $this->result->getOutput();
    }
    
    function helperMultiedit()
    {
        return new Helper\CrudCollection($this, $this->api, $this->url, array(
            'topTitle' => t('Редактировать'),
            'bottomToolbar' => $this->buttons(array('save', 'cancel')),
            'template' => '%system%/admin/crud_form.tpl',
            'multieditMode' => true
        ));
    }
    
    /**
    * Если был выделен checkbox "Выделить все на всех страницах", то добываем все id, которые были на странице, иначе возвращаем, входящий параметр
    */
    function modifySelectAll($ids)
    {
        $request_object = $this->api->getSavedRequest($this->controller_name.'_list');
        if ($this->url->request($this->selectAllVar, TYPE_STRING) == 'on' &&  $request_object !== null) {
            return $this->api->getIdsByRequest($request_object);
        }
        return $ids;
    }
    
    /**
    * Возвращает массив для элемента html/toolbar со стандартными кнопками и установленными для контроллеров crud параметрами
    * 
    * @param array|string $buttons - имя кнопок, которые должны присутствовать: add,delete,multiedit,save,cancel
    * @param array $buttons_text - массив с текстами для кнопок. например: 'add' => 'Добавить .....'
    * @param bool $ajax - Если true, то кнопкам будут спецпараметры для работы в ajax режиме
    * @return Toolbar\Element
    */
    function buttons($buttons, $buttons_text = null, $ajax = true)
    {
        $default_buttons = array(
            'add' => new ToolbarButton\Add($this->url->replaceKey(array($this->action_var => 'add')),null, array('noajax' => !$ajax)),
            'delete' => new ToolbarButton\Delete(null, null, array('attr' => 
                    array('data-url' => $this->router->getAdminUrl('del')),
                    'noajax' => !$ajax
                )),
            'multiedit' => new ToolbarButton\Multiedit($this->router->getAdminUrl('multiedit'), null, array('noajax' => !$ajax)),
            'save' => new ToolbarButton\SaveForm(null, null, array('noajax' => !$ajax)),
            'saveapply' => new ToolbarButton\SaveForm(null, null, array('noajax' => !$ajax), true),

            'apply' => new ToolbarButton\ApplyForm(null, null, array('noajax' => !$ajax)),
            'cancel' => new ToolbarButton\Cancel($this->url->getSavedUrl($this->controller_name.'index'), null, array('noajax' => !$ajax)),
            'moduleconfig' => new ToolbarButton\ModuleConfig($this->router->getAdminUrl('edit', array('mod' => $this->mod_name), 'modcontrol-control'))
        );
        
        if (is_array($buttons_text)) {
            foreach($buttons_text as $key => $title) {
                $default_buttons[$key]->setTitle($title);
            }
        }

        if (is_array($buttons)) {
            $options = array();
            foreach($buttons as $button) {
                if (isset($default_buttons[$button])) {
                    $options['Items'][$button] = $default_buttons[$button];
                }
            }
            return new Toolbar\Element($options);
        } else {
            return $default_buttons[$buttons];
        }
    }

    /**
     * Устанавливает произвольный helper, который потом может использоваться в Action
     * @param Helper\CrudCollection $helper - объект crud coolection
     * @return Helper\CrudCollection
     */
    function setHelper($helper)
    {
        return $this->helper = $helper;
    }
    
    /**
    * Возвращает установленный helper
    * @return Helper\CrudCollection
    */
    function getHelper()
    {
        return $this->helper;
    }    
    
    /**
    * Устанавливает какие действия могут быть запущены именно из данного класса.
    * 
    * @param string|array $actions, $actions, ....
    * @return void
    */
    function setCrudActions($actions = null)
    {
        $this->allow_crud_actions = is_array($actions) ? $actions : func_get_args();
    }

    /**
     * Выполняет action(действие) текущего контроллера, возвращает результат действия
     *
     * @param boolean $returnAsIs - возвращать как есть. Если true, то метод будет возвращать точно то,
     * что вернет действие, иначе результат будет обработан методом processResult
     *
     * @return mixed
     * @throws \RS\Controller\ExceptionPageNotFound
     * @throws \RS\Event\Exception
     */
    function exec($returnAsIs = false)
    {
        $act = $this->getAction();
        
        if (!empty($this->allow_crud_actions)) {
            if (!in_array($act, $this->allow_crud_actions) && is_callable(array(__CLASS__, 'action'.$act))) {
                $this->e404(t('Указанного действия не существует'));
            }
        }

        $helper_method = 'helper'.$act;
        if (is_callable(array($this, $helper_method))) {
            $helper_result = $this->$helper_method(); //Вызываем метод, который должен сформировать helper
            
            if ($helper_result !== null) {
                $event_name = 'controller.exec.'.$this->getUrlName().'.'.$act; //Формируем имя события
                
                /**
                * Event: controller.exec.Короткое имя контроллера.Имя действия
                * Вызывается перед рендерингом страницы. Обработчики данного события могут изменить содержимое helper'а
                * paramtype mixed - helper
                */
                $helper_result = \RS\Event\Manager::fire($event_name, $helper_result)->getResult();
                $this->setHelper($helper_result); //Сохраяем helper
            }
        }
        
        return parent::exec($returnAsIs);
    }

    /**
     * Метод для клонирования
     *
     * @return bool|\RS\Controller\Result\Standart
     * @throws \RS\Controller\ExceptionPageNotFound
     */
    function actionClone()
    {
        $this->setHelper( $this->helperAdd() );
        $id = $this->url->get('id', TYPE_INTEGER);
        
        $elem = $this->api->getElement();
        
        if ($elem->load($id)) {
            $clone = $elem->cloneSelf();
            $this->api->setElement($clone);
            $clone_id = (int)$clone['id']; //ID = 0, а не null

            return $this->actionAdd($clone_id);
        } else {
            $this->e404();
        }
    }

    /**
     * Метод обеспечивает отметку о прочтении одного объекта,
     * если API объекта это поддерживает
     *
     * @return \RS\Controller\Result\Standart
     * @throws \RS\Controller\ExceptionPageNotFound
     */
    function actionMarkOneAsViewed()
    {
        if (!($this->api instanceof \Main\Model\NoticeSystem\HasMeterInterface)) {
            $this->e404();
        }

        $id = $this->url->request('id', TYPE_STRING);
        $meter_api = $this->api->getMeterApi();
        $new_counter = $meter_api->markAsViewed($id);

        return $this->result->setSuccess(true)
            //Сообщим новое значение счетчика в JS
            ->addSection('meters', array(
                $meter_api->getMeterId() => $new_counter
            ))
            ->addSection('markViewed', array(
                $meter_api->getMeterId() => $id
            ));
    }

    /**
     * Метод обеспечивает отметку о прочтении всех объектов,
     * если API объекта это поддерживает
     *
     * @return \RS\Controller\Result\Standart
     * @throws \RS\Controller\ExceptionPageNotFound
     */
    function actionMarkAllAsViewed()
    {
        if (!($this->api instanceof \Main\Model\NoticeSystem\HasMeterInterface)) {
            $this->e404();
        }

        $meter_api = $this->api->getMeterApi();
        $new_counter = $meter_api->markAllAsViewed();

        return $this->result->setSuccess(true)
            //Сообщим новое значение счетчика в JS
            ->addSection('meters', array(
                $meter_api->getMeterId() => $new_counter
            ))
            ->addSection('markViewed', array(
                $meter_api->getMeterId() => 'all'
            ));
    }
}
