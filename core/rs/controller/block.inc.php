<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Controller;

use \RS\Debug;

/**
* Этот класс должен быть родителем клиентского контроллера модуля.
*/
abstract class Block extends AbstractClient
{
    const
        BLOCK_ID_PARAM   = '_block_id', //Параметр в котором передается идентификатор блока
        BLOCK_PATH_PARAM = 'tplpath',   //Реальный путь к шаблону блока
        BLOCK_NUM_PARAM  = 'num';       //Порядковый номер вставленого блока в шаблоне
        
    protected
        /**
        * Каждый блок должен назначить себе уникальную переменую для определения действия, 
        * иначе будет выполняться только actionIndex
        * 
        * @var string
        */
        $action_var = null,
        
        /**
        * Параметры, которые должны быть сохранены в кэше для блоков данного класса.
        * Параметры сохраняются, чтобы при обращении к блок-контроллеру напрямую в переменной $this->param были установленные раннее значения.
        * Для корректной загрузки параметров в дальнейшем у каждого блока на странице появляется _block_id. Его нужно указывать при обращении 
        * напрямую к блок-контроллеру
        * 
        * @var array
        */
        $store_params,
        
        /**
        * Ключ, по которому хранятся значения параметров для блока в кэше
        * 
        * @var string
        */
        $store_key,
        
        /**
        * Группа инструментов, отображаемая в режиме отладки
        * 
        * @var \RS\Debug\Group
        */
        $debug_group;
    
    protected static
        $controller_title = '',        //Краткое название контроллера
        $controller_description = '';  //Описание контроллера
        
    function __construct($param = array())
    {
        
        parent::__construct($param);
        
        if (isset($this->param['_rendering_mode'])) {
            $block_id_hash = $this->getBlockId();            
            //Отключаем возврат данных в json, если блок вставлен в шаблоне
            $this->result->checkAjaxOutput(false); 
        } else {
            $block_id_hash = $this->url->request(self::BLOCK_ID_PARAM, TYPE_STRING);
        }

        $this->store_key = \RS\Cache\Manager::obj()->prepareClass('block_cache_'.get_class($this).$block_id_hash);
        $this->view->assign('_block_id', $block_id_hash);        
        
        if (!isset($this->param['_rendering_mode']) && $block_id_hash) {
            $this->loadStoredParams();
            $this->view->assign('param', $this->getParam());
        }
    }
    
    /**
    * Загружает параметры из кэша
    */
    protected function loadStoredParams()
    {
        if (\RS\Cache\Manager::obj()->exists($this->store_key)) {
            $loaded_params = \RS\Cache\Manager::obj()->read($this->store_key);
            $this->param += $loaded_params;
        } else {
            $this->e404();
        }
    }
    
    /**
    * Возвращает информацию о текущем контроллере.
    * 
    * @param string $key - параметр информации, который нужно вернуть
    * @return array
    */
    public static function getInfo($key = null)
    {
        $info = array(
            'title' => t(static::$controller_title),
            'description' => t(static::$controller_description)
        );
        return $key ? $info[$key] : $info;
    }
    
    /**
    * Возвращает ORM объект, содержащий настриваемые параметры или false в случае, 
    * если контроллер не поддерживает настраиваемые параметры
    * 
    * @return \RS\Orm\ControllerParamObject | false
    */
    public function getParamObject()
    {
        return false;
    }   
    
    /**
    * Выполняет action(действие) текущего контроллера, возвращает результат действия
    * Также помещает в кэш установленые настройки данного блока
    * 
    * @param boolean $returnAsIs - возвращать как есть. Если true, то метод будет возвращать точно то, 
    * что вернет действие, иначе результат будет обработан методом processResult
    * 
    * @return mixed
    */
    public function exec($returnAsIs = false)
    {
        if (!$this->getParam(self::BLOCK_ID_PARAM))  throw new \RS\Controller\Exception(t('Не задан параметр _block_id'));
        // Создаем группу инструментов отладки, если это необходимо
        if (\RS\Debug\Mode::isEnabled() && !$this->router->isAdminZone()) {
            
            if ($this->getParam('generate_by_template')){ // Если блок вставлен в шаблон с помощью moduleinsert
                //Генерируем кнопку
                $this->debug_group->addTool('block_options', new Debug\Tool\BlockOptions($this->getBlockId(),
                    array(
                        'attr'=> array(
                            'href'=> $this->router->getAdminUrl('editTemplateModule', array('_block_id' => $this->getBlockId(), 'block' => $this->getUrlName()), 'templates-blockctrl'),
                        )
                    )
                    
                ));
            }
        }                
        //Если идет режим рендеринга страницы, то сохраняем параметры.
        //Это необходимо, чтобы далее можно было напрямую обращаться к данному контроллеру, оперируя параметрами рендеринга.        
        if ($this->getParam('_rendering_mode') && !\RS\Cache\Manager::obj()->exists($this->store_key)) {
            $for_store = array_intersect_key($this->getParam(), array_flip(array_merge($this->getStoreParams(), array(self::BLOCK_ID_PARAM,self::BLOCK_PATH_PARAM,self::BLOCK_NUM_PARAM))));
            \RS\Cache\Manager::obj()->expire(0)->write($this->store_key, $for_store);
        }
        
        return parent::exec($returnAsIs);
    }
    
    /**
    * Возвращает ключи параметров, которые необходимо сохранять в кэше
    * 
    * @return array
    */
    public function getStoreParams()
    {
        if ($this->store_params === null) {
            $object_params = $this->getParamObject();
            $this->store_params = $object_params ? array_values($object_params->getPropertyIterator()->getKeys()) : array();
        }
        return $this->store_params;
    }
    
    /**
    * Возвращает id блока, который можно использовать в URL для обращения к данному блоку.
    * По данному id будут загружены все параметры($this->param) для блока
    * 
    * @return integer
    */
    public function getBlockId()
    {
        return isset($this->block_id_cache) ? $this->block_id_cache : $this->block_id_cache = crc32($this->getParam(self::BLOCK_ID_PARAM));
    }
    
    /**
    * Возвращает значение параметра из get только если запрос идет конкретно к текущему контроллеру.
    * 
    * @return mixed
    */
    public function myGet($key, $type, $default = null)
    {
        if ($this->url->get(\RS\Router\RouteAbstract::CONTROLLER_PARAM, TYPE_STRING) == $this->getUrlName()) {
            return $this->url->get($key, $type, $default);
        }
        return $default;
    }
    
    /**
    * Возвращает input[type="hidden"] с id блочного контроллера, чтобы отметить, что данный пост идет по его инициативе.
    * 
    * @return string
    */
    public function myBlockIdInput()
    {
        return '<input type="hidden" name="'.self::BLOCK_ID_PARAM.'" value="'.$this->getBlockId().'">';
    }
    
    /**
    * Возвращает true, если инициатором POST запроса выступил данный контроллер
    * 
    * @return bool
    */
    public function isMyPost()
    {
        return $this->url->isPost() && $this->url->post(self::BLOCK_ID_PARAM, TYPE_INTEGER) == $this->getBlockId();
    }
    
    /**
    * Возвращает URL для настройки блока (в случае если используется сборка по сетке)
    * 
    * @return string
    */
    public function getSettingUrl($absolute = false)
    {
        if ($this->getParam('generate_by_template')) {
            $url = $this->router->getAdminUrl('editTemplateModule', array('_block_id' => $this->getBlockId(), 'block' => $this->getUrlName()), 'templates-blockctrl');
        } else {
            $url = $this->router->getAdminUrl('editModule', array('id' => $this->getParam('_block_id')), 'templates-blockctrl', $absolute);
        }
        return $url;
    }
}

