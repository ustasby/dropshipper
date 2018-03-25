<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm\Property;
use \RS\Orm\Type;

/**
* Значение списковой характеристики
*/
class ItemValue extends \RS\Orm\OrmObject
{
    protected static
        $table = 'product_prop_value';
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'prop_id' => new Type\Integer(array(
                'description' => t('Характеристика'),
                'visible' => false
            )),
            /*
            'prop_type' => new Type\Varchar(array(
                'description' => t('Тип характеристики'),
                'visible' => false,
                'list' => array(array('\Catalog\Model\Orm\Property\Item', 'getAllowTypeValues'))
            )),
            */
            'value' => new Type\Varchar(array(
                'description' => t('Значение характеристики'),
                'attr' => array(array(
                    'data-autotranslit' => 'alias'
                )),
                'checker' => array('chkEmpty', t('Значение не может быть пустым'))
            )),
            'color' => new Type\Color(array(
                'description' => t('Цвет'),
                'colorVisible' => true,
                'visible' => false,
            )),
            'image' => new Type\Image(array(
                'description' => t('Изображение'),
                'colorVisible' => true,
                'imageVisible' => true,
                'visible' => false
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядок'),
                'visible' => false
            )),
            'xml_id' => new Type\Varchar(array(
                'description' => t('Внешний идентификатор'),
                'visible' => false
            ))
        ));
        
        $this->addIndex(array('prop_id', 'value'), self::INDEX_UNIQUE);
        $this->addIndex(array('site_id', 'xml_id'), self::INDEX_UNIQUE);
    }
    
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['sortn'] = \RS\Orm\Request::make()
                ->select('MAX(sortn) as next_sort')
                ->from($this)
                ->where(array(
                    'site_id' => $this->__site_id->get(),
                    'prop_id' => $this['prop_id'],
                ))->exec()->getOneField('next_sort', 0) + 1;
        }
        
        if ($this['xml_id'] === '') {
            unset($this['xml_id']);
        }
    }
    
    function afterWrite($flag)
    {
        if ($flag == self::UPDATE_FLAG) {
            //Обновляем значение в таблице связей
            \RS\Orm\Request::make()
                ->update(new Link())
                ->set(array(
                    'val_str' => $this['value']
                ))
                ->where(array(
                    'val_list_id' => $this['id']
                ))
                ->exec();
        }
    }
    
    /**
    * Возвращает внешний вид одного значения характеристики в админ.панели (HTML).
    * Можно добавлять собственные обработчики типов извне за счет функциональности "behavior"
    * 
    * @return string
    */
    function getAdminItemView($property_type)
    {
        $method = 'adminItemView'.$property_type;
        if (method_exists($this, $method) || $this->behaviorMethodExists($method)) {
            return $this->$method();
        }
        return '';
    }
    
    /**
    * Возвращает внешний вид значения типа Цвет
    * 
    * @return string
    */
    protected function adminItemViewColor()
    {
        $view = new \RS\View\Engine();
        $view->assign('elem', $this);
        return $view->fetch('%catalog%/form/property/valuetype/color.tpl');
    }
    
    /**
    * Возвращает внешний вид значения типа Цвет
    * 
    * @return string
    */
    protected function adminItemViewImage()
    {
        $view = new \RS\View\Engine();
        $view->assign('elem', $this);
        return $view->fetch('%catalog%/form/property/valuetype/image.tpl');
    }    
    
    /**
    * Возвращает ID текущего объекта по значению
    * 
    * @param integer 
    * @param string $value
    */
    public static function getIdByValue($property_id, $value, $create = true)
    {
        static
            $cache = array();
        
        if (!isset($cache[$property_id][$value])) {
            $data = array(
                'prop_id' => $property_id,
                'value' => $value
            );
            
            $id = \RS\Orm\Request::make()
                    ->select('id')
                    ->from(new self())
                    ->where($data)
                    ->exec()
                    ->getOneField('id', null);
            
            if (!$id && $create) {
                $self = new self();
                $self->getFromArray($data);
                $self->insert();
                $id = $self['id'];
            }
            
            $cache[$property_id][$value] = $id;
        }
        return $cache[$property_id][$value];
    }
    
    /**
    * Возвращает значение характеристики по ID
    * 
    * @param integer | array $ids - один ID или список ID
    * @return string
    */
    public static function getValueById($ids)
    {
        static
            $cache = array();
        
        if (!$ids) return '';
        
        $ids_arr = (array)$ids;
        if ($no_loaded_id = array_diff($ids_arr, array_keys($cache))) {
            //Вычисляем ID, которые следует подгрузить
            $cache += \RS\Orm\Request::make()
                        ->select('id, value')
                        ->from(new self())
                        ->whereIn('id', $no_loaded_id)
                        ->exec()->fetchSelected('id', 'value');
        }
        
        $values = array_intersect_key($cache, array_flip($ids_arr));
        return is_array($ids) ? $values : (isset($values[$ids]) ? $values[$ids] : '');
    }
}
