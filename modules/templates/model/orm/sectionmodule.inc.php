<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Templates\Model\Orm;
use \RS\Orm\Type;

/**
* Один блок в секции
* @ingroup Templates
*/
class SectionModule extends \RS\Orm\OrmObject
{
    protected static
        $table = 'section_modules',
        $cache_blocks = array();
    
    function _init()
    {
        parent::_init();
        $properties = $this->getPropertyIterator()->append(array(
            'page_id' => new Type\Integer(array(
                'no_export' => true,
                'index' => true,
                'description' => t('Страница'),
            )),
            'section_id' => new Type\Integer(array(
                'no_export' => true,
                'description' => t('ID секции'),
            )),
            'module_controller' => new Type\Varchar(array(
                'maxLength' => '150',
                'description' => t('Модуль'),
            )),
            'public' => new Type\Integer(array(
                'description' => t('Публичный'),
                'listenPost' => false,
                'maxLength' => 1,
                'default' => 1,
                'checkboxView' => array(1,0)
            )),
            'sortn' => new Type\Integer(),
            'params' => new Type\Text(array(
                'no_export' => true,
                'description' => t('Параметры'),
            )),
            '_params' => new Type\Mixed(array(
                'no_export' => true
            )),
        ));
    }
    
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            if ($this['sortn'] === null) {
                $this['sortn'] = \RS\Orm\Request::make()
                    ->select('MAX(sortn)+1 as max')
                    ->from($this)
                    ->where(array('section_id' => $this['section_id']))
                    ->exec()->getOneField('max', 0);
            }
        }
        if ($this['page_id'] === null) {
            $section = new Section($this['section_id']);
            $this['page_id'] = $section['page_id'];
        }
    }
    
    function getParams()
    {
        $params = unserialize($this['params']);
        $default = array(\RS\Controller\Block::BLOCK_ID_PARAM => $this['id'], 'generate_by_grid' => true);
        return is_array($params) ? $default + $params : $default;
    }
    
    function setParams($params)
    {
        $this['params'] = serialize($params);
    }
    
    
    public static function getPageBlocks($page_id, $only_public = false)
    {
        if (!isset(self::$cache_blocks[ $page_id ])) {
            self::$cache_blocks[$page_id] = \RS\Orm\Request::make()
                ->from(new self())
                ->where(array('page_id' => $page_id) + ($only_public ? array('public' => 1) : array())) 
                ->orderby('section_id, sortn')
                ->objects(null, 'section_id', true);
        }
        return self::$cache_blocks[ $page_id ];
    }    
    
    /**
    * Перемещает элемент на новую позицию. 0 - первый элемент
    * 
    * @param mixed $new_position
    */
    public function moveToPosition($new_position, $new_section_id = null)
    {
        if ($this->noWriteRights()) return false;

        if ($new_section_id) {
            $this->changeParent($new_section_id);
        }
        
        $q = \RS\Orm\Request::make()
            ->update($this)
            ->where(array(
                'section_id' => $this['section_id']
            ));
        
        //Определяем направлене перемещения 
        if ($this['sortn'] < $new_position) {
            //Вниз
            $q->set('sortn = sortn - 1')
            ->where("sortn > '#cur_pos' AND sortn <= '#new_pos'", array('cur_pos' => $this['sortn'], 'new_pos' => $new_position));
        } else { 
            //Вверх
            $q->set('sortn = sortn + 1')
                ->where("sortn >= '#new_pos' AND sortn < '#cur_pos'", array('cur_pos' => $this['sortn'], 'new_pos' => $new_position));
        }
        $q->exec();
        
        \RS\Orm\Request::make()
            ->update($this)
            ->set(array('sortn' => $new_position))
            ->where(array(
                'id' => $this['id']
            ))
            ->exec();
        
        //Сбросим кэш при перемещении блоков
        \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
            
        return true;
    }


    /**
     * Перемещяет элемент в последнюю позицию нового родителя.
     * Обновляет сортировочные индексы у предыдущего родителя
     *
     * @param integer $new_section_id_id
     */
    function changeParent($new_section_id)
    {
        if ($this['section_id'] == $new_section_id) {
            return false;
        }

        //Изменяем сортировочные индексы в старом контейнере
        \RS\Orm\Request::make()
            ->update($this)
            ->set('sortn = sortn - 1')
            ->where(array(
                'section_id' => $this['section_id']
            ))
            ->where("sortn > '#sortn'", array('sortn' => $this['sortn']))
            ->exec();


        //Получаем новый
        $max_new_sortn = \RS\Orm\Request::make()
            ->select('MAX(sortn)+1 as maxsortn')
            ->from($this)
            ->where(array(
                'section_id' => $new_section_id
            ))
            ->exec()->getOneField('maxsortn', 0);

        //Изменяем родителя секции
        \RS\Orm\Request::make()
            ->update($this)
            ->set(array(
                'sortn' => $max_new_sortn,
                'section_id' => $new_section_id
            ))
            ->where(array(
                'id' => $this['id'],
            ))
            ->exec();
        
        $this['section_id'] = $new_section_id;
        $this['sortn'] = $max_new_sortn;
        
        return true;
    }
    
    /**
    * Возвращает информацию о модуле
    */
    function getBlockInfo($key = null)
    {
        if (class_exists($this['module_controller'])) {
            $result = call_user_func(array($this['module_controller'], 'getInfo'));
        } else {
            $result = array(
                'title' => t('Контроллер не найден'),
                'description' => ''
            );
        }
        return $key !== null ? $result[$key] : $result;
    }
    
    /**
    * Возвращает объект блочного контроллера
    * @return \RS\Controller\Block
    */
    function getControllerInstance()
    {
        if (class_exists($this['module_controller'])) {
            return new $this['module_controller']();
        }
        return new \Templates\Model\ModuleBlockStub();
    }
    
}
