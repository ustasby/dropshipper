<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Menu\Model\Orm;
use \RS\Orm\Type;

class Menu extends \RS\Orm\OrmObject
{
    const
        TYPELINK_SEPARATOR = 'separator'; //Разделитель
        
    protected static
        $table = "menu";
        
    protected
        $cache_type;
    
    protected static $act_path; //Путь до корневого элемента активного пункта меню

    protected function _init()
    {
        parent :: _init();
        
        $this->getPropertyIterator()->append(array(
            t('Основные'),
                    'site_id' => new Type\CurrentSite(),
                    'menutype' => new Type\Varchar(array(
                        'maxLength' => '70',
                        'description' => t('Тип меню'),
                        'visible' => false,
                    )),
                    'title' => new Type\Varchar(array(
                        'maxLength' => '150',
                        'description' => t('Название'),
                        'Checker' => array( array(__CLASS__, 'checkEmptyDependTypelink'), t('Необходимо заполнить поле название')),
                        'specVisible' => false,
                        'attr' => array(array(
                            'data-autotranslit' => 'alias'
                        ))
                    )),
                    'hide_from_url' => new Type\Integer(array(
                        'maxLength' => 1,
                        'description' => t('Не использовать для построения URL'),
                        'hint' => t('При активации данной опции у дочерних элементов не будет текущей секции'),
                        'checkboxView' => array(1,0),
                        'specVisible' => false
                    )),                    
                    'alias' => new Type\Varchar(array(
                        'maxLength' => '150',
                        'description' => t('Симв. идентификатор'),
                        'specVisible' => false,
                        'meVisible' => false,
                        'Checker' => array( array(__CLASS__, 'checkEmptyDependTypelink'), t('Необходимо заполнить поле Символьный идентификатор')),
                        'hint' => t('Символьный идентификатор используется для формирования адреса страницы для пункта меню')
                    )),
                    'parent' => new Type\Integer(array(
                        'maxLength' => '11',
                        'description' => t('Родитель'),
                        'List' => array(array('\Menu\Model\Api', 'selectList')),
                    )),
                    'public' => new Type\Integer(array(
                        'maxLength' => '1',
                        'default' => 1,
                        'description' => t('Публичный'),
                        'CheckboxView' => array(1,0),
                        'specVisible' => false,
                    )),                    
                    'typelink' => new Type\Varchar(array(
                        'maxLength' => '20',
                        'description' => t('Тип элемента'),
                        'list' => array(array('\Menu\Model\Api', 'getMenuTypesNames')),
                        'Attr' => array(array('size' => 0)),
                        'hint' => \Menu\Model\Api::getMenuTypeDescriptions(),
                        'default' => 'article',
                        'meVisible' => false,
                        'specVisible' => false,
                        'template' => '%menu%/form/menu/other.tpl',
                    )),                    
                    'sortn' => new Type\Integer(array(
                        'maxLength' => '11',
                        'description' => t('Порядк. №'),
                        'visible' => false,
                    )),
                    'closed' => new Type\Integer(array(
                        'maxLength' => '1',
                        'runtime' => true,
                        'visible' => false,
                    ))
        ));
        
        //Добавляем свойства из типов пункта меню
        $types = \Menu\Model\Api::getMenuTypes(false);
        foreach($types as $type) {
            if ($form = $type->getFormObject()) {
                $iterator = $form->getPropertyIterator()->setPropertyOptions(array('visible' => false));
                $this->getPropertyIterator()->appendPropertyIterator($iterator);
            }
        }
        
        $this
            ->addIndex(array('site_id', 'alias', 'parent'), self::INDEX_UNIQUE)
            ->addIndex(array('parent', 'sortn'))
            ->addIndex('site_id');
    }
    
    /**
    * Возвращает отладочные действия, которые можно произвести с объектом
    * 
    * @return \RS\Debug\Action[]
    */
    public function getDebugActions()
    {
        return array(
            new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'menu-ctrl')),
            new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'menu-ctrl')),
            new \RS\Debug\Action\Create(\RS\Router\Manager::obj()->getAdminPattern('add', array(':pid' => '{id}'), 'menu-ctrl'), t('создать подменю'))
        );
    }    
    
    public static function checkEmptyDependTypelink($coreobj, $value, $real_errtext)
    {
        if ($coreobj['typelink'] == self::TYPELINK_SEPARATOR) return true;
        return !empty($value) ? true : $real_errtext;
    }
    
    function beforeWrite($save_flag)
    {
        if ($save_flag == self::INSERT_FLAG && !isset($this['sortn']))
        {
            $q = \RS\Orm\Request::make()
                ->select('MAX(sortn) max_sort')
                ->from($this)
                ->where(array(
                    'site_id' => $this->__site_id->get(),
                    'parent' => $this['parent'],
                    'menutype' => $this['menutype']
                ));
                
                $this['sortn'] = $q->exec()
                ->getOneField('max_sort', -1) + 1;
        }
        $api = new \Menu\Model\Api();
        $parents_arrs = $api-> getPathToFirst($this['parent']);
        if($this['id'] && isset($parents_arrs[$this['id']])){
            return $this->addError(t('Неверно указан родительский элемент'), 'parent');
        }
        if ($this['id'] && $this['parent'] == $this['id']) {
            return $this->addError(t('Неверно указан родительский элемент'), 'parent');
        }
    }
    
    /**
    * Возвращает объект типа
    * 
    * @return \Menu\Model\MenuType\AbstractType
    */
    function getTypeObject($cache = true)
    {
        if (!$cache || $this->cache_type === null) {
            $list = \Menu\Model\Api::getMenuTypes();
            if (isset($list[$this['typelink']])) {
                $this->cache_type = clone $list[$this['typelink']];
            } else {
                //Тип пункта меню по умолчанию
                $this->cache_type = new \Menu\Model\MenuType\Article();
            }
            $this->cache_type->init($this);
        }
            
        return $this->cache_type;
    }
    
    /**
    * Проверяет, есть ли права для работы с данным пунктом меню у пользователя
    */
    function checkUserRights(\Users\Model\Orm\User $user = null)
    {
        if ($user === null) $user = \RS\Application\Auth::getCurrentUser();
        $access_menu = $user->getMenuAccess();
        
        if ($this['menutype'] == 'user' && in_array(FULL_USER_ACCESS, $access_menu)) return true;
        if (in_array($this['id'], $access_menu)) return true;
        return false;
    }

    
    /**
    * Возвращает URL в зависимости от типа пункта меню
    */
    function getHref()
    {
        return $this->getTypeObject()->getHref();
    }
    
    function isAct()
    {
        return $this->getTypeObject()->isActive();
    }    
    
    
    /**
    * Возвращает клонированный объект меню
    * @return Menu
    */
    function cloneSelf()
    {
        /**
        * @var \Menu\Model\Orm\Menu
        */
        $clone = parent::cloneSelf();
        unset($clone['alias']);
        return $clone;
    }
}