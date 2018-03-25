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
* Контейнер, в котором будут находиться секции.
* @ingroup Templates
*/
class SectionContainer extends \rs\orm\OrmObject
{
    protected static
        $table = 'section_containers',
        $cache_sections = array(); //Все секции для каждой page_id
        
    function _init()
    {
        parent::_init();
        $this->getPropertyIterator()->append(array(
            'page_id' => new Type\Integer(array(
                'visible' => false,
                'index' => true,
                'no_export' => true
            )),
            'columns' => new Type\Integer(array(
                'description' => t('Ширина')
            )),
            'css_class' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('CSS класс'),
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Название'),
            )),
            'is_fluid' => new Type\Integer(array(
                'description' => t('Резиновый контейнер(fluid)'),
                'maxLength' => 1,
                'allowEmpty' => false,
                'checkboxView' => array(1,0),
                'visible' => false,
                'bootstrapVisible' => true
            )),            
            'wrap_css_class' => new Type\Varchar(array(
                'description' => t('CSS-класс оборачивающего блока')
            )),
            'wrap_element' => new Type\Varchar(array(
                'description' => t('Внешний элемент'),
                'listFromArray' => array(array(
                    '' => t('не оборачивать'),
                    'div' => 'div',
                    'header' => 'header',
                    'footer' => 'footer',
                    'section' => 'section'
                ))
            )),            
            'outside_template' => new Type\Template(array(
                'description' => t('Внешний шаблон')
            )),
            'inside_template' => new Type\Template(array(
                'description' => t('Внутренний шаблон')
            )),
            'type' => new Type\Integer(array(
                'description' => t('Порядковый номер контейнера на странице'),
                'maxLength' => '5',
                'visible' => false,
            )),
        ));
    }
    
    /**
    * Возвращает название контейнера
    * @return string
    */
    public function getTitle()
    {
        return !empty($this['title']) ? $this['title'] : t('Контейнер %0', array($this['type']));
    }
    
    /**
    * Возвращает иерархию секций, расположенных в данном контейнере
    * @return array
    */
    public function getSections()
    {
        if (!isset(self::$cache_sections[ $this['page_id'] ])) {
        
            self::$cache_sections[ $this['page_id'] ] = \RS\Orm\Request::make()
                ->from(new Section())
                ->where(array('page_id' => $this['page_id']))
                ->orderby('parent_id, sortn')
                ->objects(null, 'parent_id', true);
        }
            
        return $this->makeSectionsTree(self::$cache_sections[ $this['page_id'] ], -$this['type']);
    }
    
    /**
    * Возвращает дерево секций и блоков
    * @return array
    */
    private function makeSectionsTree($sections, $parent_id)
    {
        $result = array();
        if (isset($sections[$parent_id])) {
            $branch = $sections[$parent_id];
            foreach($branch as $section) {
                if (isset($sections[ $section['id'] ])) {
                    $childs = $this->makeSectionsTree($sections, $section['id']);
                } else {
                    $childs = array();
                }
                $result[] = array('section' => $section, 'childs' => $childs);
            }
        }
        return $result;
    }
    
    /**
    * Меняет местами текущий контейнер и контейнер $destination_container_id
    * 
    * @param integer $destination_container_id - ID контейнера для обмена позициями
    * @return bool
    */
    function changePosition($destination_container_id)
    {
        $dst_container = new self($destination_container_id);
        //Не позволяем обмениваться позициями контейнерами с разных страниц
        if ($dst_container['page_id'] != $this['page_id']) return false;
        $dst_type = $dst_container['type'];
        $dst_container['type'] = $this['type'];
        $this['type'] = $dst_type;
        
        if ($dst_container->update() && $this->update()) {
            //Перемещаем секции между контейнерами
            \RS\Orm\Request::make()
                ->update(new Section)
                ->set(array(
                    'parent_id' => null
                ))->where(array(
                    'parent_id' => -$this['type'],
                    'page_id' => $this['page_id']
                ))->exec();
                
            \RS\Orm\Request::make()
                ->update(new Section)
                ->set(array(
                    'parent_id' => -$this['type']
                ))->where(array(
                    'parent_id' => -$dst_container['type'],
                    'page_id' => $this['page_id']
                ))->exec();
                
            \RS\Orm\Request::make()
                ->update(new Section)
                ->set(array(
                    'parent_id' => -$dst_container['type']
                ))
                ->where('parent_id is null')
                ->where(array('page_id' => $this['page_id']))
                ->exec();                    
            return true;
        }
        return false;
    }
    
    function delete()
    {
        //Удаляем все секции, которые находятся внутри данного
        $sub_sections = \RS\Orm\Request::make()
            ->from(new Section())
            ->where(array(
                'parent_id' => -$this['type'], 
                'page_id' => $this['page_id']
            ))
            ->objects();
            
        foreach($sub_sections as $section) {
            $section->delete();
        }
        
        return parent::delete();
    }
    
    /**
    * Устанавливает допустимый список колонок для текущей сеточной системы
    * 
    * @param string $grid_system - тип сеточного фреймворка
    * @return void
    */
    function setColumnList($grid_system)
    {
        switch($grid_system) {
            case SectionContext::GS_BOOTSTRAP: {
                $gs = array(12); break;
            }
            case SectionContext::GS_GS960: {
                $gs = array(12, 16); break;
            }
            default: {
                $gs = array(); break;
            }
        }
        $result = array();
        foreach($gs as $column) {
            $result[$column] = t('%0 колонок', array($column));
        }
        $this['__columns']->setListFromArray($result);
    }
}

