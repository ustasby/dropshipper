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
* Объект описывает одну секцию (col-*), строку (row), сброс (clearfix)
* @ingroup Templates
*/
class Section extends \RS\Orm\OrmObject
{        
    const
        ELEMENT_TYPE_COL = 'col',
        ELEMENT_TYPE_ROW = 'row';
        
    protected static
        $table = 'sections';
    
    function _init()
    {
        parent::_init();
        $this->getPropertyIterator()->append(array(
            'page_id' => new Type\Varchar(array(
                'maxLength' => '255',
                'no_export' => true,
                'description' => t('Страница'),
                'visible' => false
            )),
            'parent_id' => new Type\Integer(array(
                'no_export' => true,
                'index' => true,
                'description' => t('Родительская секция'),
                'visible' => false
            )),
            'alias' => new Type\Varchar(array(
                'description' => t('Название секции для автоматической вставки модулей'),
                'Attr' => array(array('size' => 1)),
                'ListFromArray' => array(array(
                    '' => t('Не указано'),
                    'left' => t('Левая колонка'),    
                    'right' => t('Правая колонка'),    
                    'center' => t('Центральная колонка'),
                    '_other' => t('Другое')
                )),
                'template' => '%templates%/form/section/alias.tpl',
                'rowVisible' => false
            )),
            //Ширина секций
            'width_xs' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Ширина (XS)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'width_sm' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Ширина (SM)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),            
            'width' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Ширина'),
                'visible' => true, //Отображается для GS960
                'bootstrapVisible' => false,
                'rowVisible' => false,
                'requestType' => TYPE_STRING
            )),            
            'width_lg' => new Type\Integer(array(
                'maxLength' => '5',
                'description' => t('Ширина'),
                'template' => '%templates%/form/section/width.tpl',
                'visible' => false,
                'bootstrapVisible' => true, //Отображается для Bootstrap                
                'rowVisible' => false,
                'requestType' => TYPE_STRING
            )),            
            
            'inset_align' => new Type\Varchar(array(
                'description' => t('Внутреннее выравнивание'),
                'Attr' => array(array('size' => 1)),
                'ListFromArray' => array(array(    
                    'wide' => t('На всю ширину'),    
                    'left' => t('Слева'),    
                    'right' => t('Справа')
                )),
                'rowVisible' => false
            )),
            //Отступ слева
            'prefix_xs' => new Type\Integer(array(
                'description' => t('Отступ слева (XS)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'prefix_sm' => new Type\Integer(array(
                'description' => t('Отступ слева (SM)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'prefix' => new Type\Integer(array(
                'description' => t('Отступ слева (prefix)'),
                'bootstrapVisible' => false,
                'rowVisible' => false,
                'requestType' => TYPE_STRING
            )),
            'prefix_lg' => new Type\Integer(array(
                'description' => t('Остступ слева (offset)'),
                'visible' => false,
                'bootstrapVisible' => true,
                'template' => '%templates%/form/section/prefix.tpl',
                'requestType' => TYPE_STRING
            )),    
                    
            'suffix' => new Type\Integer(array(
                'description' => t('Отступ справа (suffix)'),
                'bootstrapVisible' => false,
                'rowVisible' => false
            )),
            
            //Сдвиг влево
            'pull_xs' => new Type\Integer(array(
                'description' => t('Сдвиг влево (xs)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'pull_sm' => new Type\Integer(array(
                'description' => t('Сдвиг влево (sm)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'pull' => new Type\Integer(array(
                'description' => t('Сдвиг влево (pull)'),
                'bootstrapVisible' => false,
                'rowVisible' => false,
                'requestType' => TYPE_STRING
            )),
            'pull_lg' => new Type\Integer(array(
                'description' => t('Сдвиг влево (pull)'),
                'visible' => false,
                'bootstrapVisible' => true,
                'template' => '%templates%/form/section/pull.tpl',
                'requestType' => TYPE_STRING
            )),
            
            //Сдвиг вправо
            'push_xs' => new Type\Integer(array(
                'description' => t('Сдвиг вправо (xs)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'push_sm' => new Type\Integer(array(
                'description' => t('Сдвиг вправо (sm)'),
                'visible' => false,
                'requestType' => TYPE_STRING
            )),
            'push' => new Type\Integer(array(
                'description' => t('Сдвиг вправо (push)'),
                'bootstrapVisible' => false,
                'rowVisible' => false,
                'requestType' => TYPE_STRING
            )),
            'push_lg' => new Type\Integer(array(
                'description' => t('Сдвиг вправо (push)'),
                'visible' => false,
                'bootstrapVisible' => true,
                'template' => '%templates%/form/section/push.tpl',
                'requestType' => TYPE_STRING
            )),            
            
            
            'css_class' => new Type\Varchar(array(
                'description' => t('Пользовательский CSS класс'),
            )),
            'is_clearfix_after' => new Type\Integer(array(
                'description' => t('Очистка после элемента(clearfix)'),
                'maxLength' => 1,
                'checkboxView' => array(1,0),
                'template' => '%templates%/form/section/clearfix_after.tpl',
                'rowVisible' => false
            )),
            'clearfix_after_css' => new Type\Varchar(array(
                'description' => t('Пользовательский CSS класс для clearfix'),
                'maxLength' => 150,
                'visible' => false
            )),
            'inset_template' => new Type\Template(array(
                'maxLength' => '255',
                'description' => t('Внутренний шаблон'),
            )),
            'outside_template' => new Type\Template(array(
                'description' => t('Внешний шаблон'),
            )),
            'element_type' => new Type\Enum(array('col', 'row'), array(
                'maxLength' => 1,
                'description' => t('Тип элемента'),
                'allowEmpty' => false,
                'visible' => false
            )),
            'sortn' => new Type\Integer(array(
                'visible' => false
            )),
        ));
    }
    
    function beforeWrite($flag)
    {
        $null_fields = array('width', 'prefix', 'pull', 'push');
        $devices = array('', '_xs', '_sm', '_lg');
        foreach($null_fields as $field) {
            foreach($devices as $device) {
                if ($this[$field.$device] === '') $this[$field.$device] = null;
            }
        }
        
        //Получаем порядковый номер вставляемого блока
        if (!$this->isModified('sortn') && $flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn)+1 as max')
                ->from($this)
                ->where(array(
                    'parent_id' => $this['parent_id'], 
                    'page_id' => $this['page_id']))
                ->exec()->getOneField('max', 0);
        }        
    }
    
    function delete()
    {
        //Удаляем все блоки, которые находятся внутри данного
        $sub_sections = \RS\Orm\Request::make()
            ->from($this)
            ->where(array('parent_id' => $this['id']))
            ->objects();
            
        if (count($sub_sections)) {
            foreach($sub_sections as $section) {
                $section->delete();
            }
        } else {
            $sub_modules = \RS\Orm\Request::make()
                ->from(new SectionModule())
                ->where(array('section_id' => $this['id']))
                ->objects();
            foreach($sub_modules as $module) {
                $module->delete();
            }
        }
        
        return parent::delete();
    }
    
    /**
    * Перемещает элемент на новую позицию. 0 - первый элемент
    * 
    * @param mixed $new_position
    */
    public function moveToPosition($new_position, $new_parent_id = null)
    {
        if ($this->noWriteRights()) return false;

        if ($new_parent_id) {
            $this->changeParent($new_parent_id);
        }
        
        $q = \RS\Orm\Request::make()
            ->update($this)
            ->where(array(
                'page_id' => $this['page_id'],
                'parent_id' => $this['parent_id']
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
            ))->exec();
            
        //Сбросим кэш при перемещении блоков
        \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_BLOCK_PARAM);
        
        return true;
    }

    /**
     * Перемещяет элемент в последнюю позицию нового родителя.
     * Обновляет сортировочные индексы у предыдущего родителя
     *
     * @param integer $new_parent_id
     */
    function changeParent($new_parent_id)
    {
        if ($this['parent_id'] == $new_parent_id) {
            return false;
        }

        //Изменяем сортировочные индексы в старом контейнере
        \RS\Orm\Request::make()
            ->update($this)
            ->set('sortn = sortn - 1')
            ->where(array(
                'page_id' => $this['page_id'],
                'parent_id' => $this['parent_id']
            ))
            ->where("sortn > '#sortn'", array('sortn' => $this['sortn']))
            ->exec();


        //Получаем новый
        $max_new_sortn = \RS\Orm\Request::make()
            ->select('MAX(sortn)+1 as maxsortn')
            ->from($this)
            ->where(array(
                'page_id' => $this['page_id'],
                'parent_id' => $new_parent_id
            ))
            ->exec()->getOneField('maxsortn', 0);

        //Изменяем родителя секции
        \RS\Orm\Request::make()
            ->update($this)
            ->set(array(
                'sortn' => $max_new_sortn,
                'parent_id' => $new_parent_id
            ))
            ->where(array(
                'id' => $this['id'],
            ))
            ->exec();
        
        $this['parent_id'] = $new_parent_id;
        $this['sortn'] = $max_new_sortn;
        
        return true;
    }
    
    /**
    * Возвращает объект контейнера, в котором находится секция
    */
    public function getContainer()
    {
        $id = $this['id'];
        $parent_id = $this['parent_id'];
        while($parent_id>0) {
            $arr = \RS\Orm\Request::make()
                ->select('id, parent_id')
                ->from($this)
                ->where(array('id' => $parent_id))
                ->exec()
                ->fetchRow();
            $parent_id = $arr['parent_id'];
            $id = $arr['id'];
        }
        return SectionContainer::loadByWhere(array('page_id' => $this['page_id'], 'type' => $parent_id));
    }
    
    /**
    * Возвращает блоки, которые находятся в секции
    */
    public function getBlocks()
    {
        $blocks = SectionModule::getPageBlocks($this['page_id']);
        return isset( $blocks[$this['id']] ) ? $blocks[$this['id']] : array();
    }
    
    /**
    * Возвращает true, если в секцию можно добавить еще секцию
    */
    public function canInsertSection()
    {
        $mod_count = \RS\Orm\Request::make()
            ->from(new SectionModule())
            ->where(array('section_id' => $this['id']))
            ->count();
        return $this['element_type'] == self::ELEMENT_TYPE_ROW || !$mod_count;
    }
    
    /**
    * Возвращает true, если в секцию можно добавить модуль
    */
    public function canInsertModule()
    {
        $subsection_count = \RS\Orm\Request::make()
            ->from($this)
            ->where(array('parent_id' => $this['id']))
            ->count();
        return $this['element_type'] == self::ELEMENT_TYPE_COL && !$subsection_count;
    }

    /**
     * Возвращает visible-* классы, которые установлены для секции
     *
     * @return string
     */
    public function getAnyVisibleClass()
    {
        $result = array();
        $classes = explode(' ', $this['css_class']);
        foreach($classes as $class) {
            if (preg_match('/^visible-(xs|sm|md|lg)$/', trim($class), $match)) {
                $result[] = 'bvisible-'.$match[1];
            }
        }

        return implode(' ', $result);
    }
    
}
