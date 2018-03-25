<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model\Orm;
use \RS\Orm\Type;

/**
* Категория товаров
* @ingroup Catalog
*/
class Dir extends \RS\Orm\OrmObject
{
    protected static
        $table = 'product_dir',
        $deleted_folder_id, //id папки, в которую скидываются все товары из удаленных папок
        $product_xdir;
    
    function _init()
    {                
        parent::_init()->append(array(
            t('Основные'),
                    'site_id' => new Type\CurrentSite(),
                    'xml_id' =>  new Type\Varchar(array(
                        'maxLength' => '255',
                        'description' => t('Идентификатор в системе 1C'),
                        'visible' => false,
                    )),
                    'name' => new Type\Varchar(array(
                        'maxLength' => '255',
                        'description' => t('Название категории'),
                        'Checker' => array('chkEmpty', t('Название категории не может быть пустым')),
                        'attr' => array(array(
                            'data-autotranslit' => 'alias'
                        )),
                        'rootVisible' => false
                    )),
                    'alias' => new Type\Varchar(array(
                        'maxLength' => '150',
                        'Checker' => array('chkalias', null),
                        'description' => t('Псевдоним'),
                        'rootVisible' => false,
                        'meVisible' => false
                    )),
                    'parent' => new Type\Integer(array(
                        'maxLength' => '11',
                        'description' => t('Родитель'),
                        'List' => array(array('\Catalog\Model\DirApi', 'selectList')),
                        'specVisible' => false,
                        'rootVisible' => false
                    )),
                    'public' => new Type\Integer(array(
                        'maxLength' => '1',
                        'description' => t('Публичный'),
                        'CheckboxView' => array(1,0),
                        'rootVisible' => false
                    )),
                    'sortn' => new Type\Integer(array(
                        'maxLength' => '11',
                        'description' => t('Порядк. N'),
                        'visible' => false,
                        'rootVisible' => false
                    )),
                    'is_spec_dir' => new Type\Varchar(array(
                        'maxLength' => '1',
                        'description' => t('Это спец. список?'),
                        'visible' => false,
                        'rootVisible' => false,
                        'hint' => t('Например: новинки, популярные товары, горячие и т.д.'),
                    )),
                    'is_label' => new Type\Integer(array(
                        'description' => t('Показывать как ярлык у товаров'),
                        'hint' => t('Опция актуальна только для тех тем оформления, где поддерживается эта опция'),
                        'maxLength' => 1,
                        'default' => 0,
                        'allowEmpty' => false,
                        'visible' => false,
                        'specVisible' => true,
                        'checkboxView' => array(1, 0)
                    )),
                    'itemcount' => new Type\Integer(array(
                        'description' => t('Количество элементов'),
                        'visible' => false,
                        'rootVisible' => false
                    )),
                    'level' => new Type\Integer(array(
                        'description' => t('Уровень вложенности'),
                        'index' => true,
                        'visible' => false,
                        'rootVisible' => false
                    )),
                    'image' => new Type\Image(array(
                        'max_file_size' => 10000000,
                        'allow_file_types' => array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'),
                        'description' => t('Изображение'),
                        'specVisible' => true,
                        'rootVisible' => false
                    )),
                    'weight' => new Type\Integer(array(
                        'description' => t('Вес товара по умолчанию, грамм'),
                        'rootVisible' => false
                    )),
                    '_alias' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'runtime' => true,
                        'visible' => false,
                        'rootVisible' => false
                    )),
                    '_class' => new Type\Varchar(array(
                        'maxLength' => '50',
                        'runtime' => true,
                        'visible' => false,
                        'rootVisible' => false
                    )),            
                    'closed' => new Type\Integer(array(
                        'maxLength' => '1',
                        'runtime' => true,
                        'visible' => false,
                        'rootVisible' => false,
                    )),
                    'prop' => new Type\Mixed(array(
                        'visible' => false,
                        'rootVisible' => false
                    )),
                    'properties' => new Type\Mixed(array(
                        'visible' => false,
                    )),
                    'processed' => new Type\Integer(array(
                        'maxLength' => '2',
                        'visible' => false,
                    )),

            t('Характеристики'),
                    '__property__' => new Type\UserTemplate('%catalog%/form/dir/property.tpl'),
                    
            t('Описание'),
                    'description' => new Type\Richtext(array(
                        'description' => t('Описание категории'),
                        'rootVisible' => false
                    )),
            t('Мета-тэги'),
                    'meta_title' => new Type\Varchar(array(
                        'maxLength' => '1000',
                        'description' => t('Заголовок'),
                        'rootVisible' => false
                    )),            
                    'meta_keywords' => new Type\Varchar(array(
                        'maxLength' => '1000',
                        'description' => t('Ключевые слова'),
                        'rootVisible' => false
                    )),
                    'meta_description' => new Type\Varchar(array(
                        'maxLength' => '1000',
                        'viewAsTextarea' => true,
                        'description' => t('Описание'),
                        'rootVisible' => false
                    )),
            t('Параметры товаров'),
                    'product_meta_title' => new Type\Varchar(array(
                        'maxLength' => '1000',
                        'description' => t('Заголовок товаров'),
                        'rootVisible' => false
                    )),            
                    'product_meta_keywords' => new Type\Varchar(array(
                        'maxLength' => '1000',
                        'description' => t('Ключевые слова товаров'),
                        'rootVisible' => false
                    )),
                    'product_meta_description' => new Type\Varchar(array(
                        'maxLength' => '1000',
                        'viewAsTextarea' => true,
                        'description' => t('Описание товаров'),
                        'rootVisible' => false
                    )),
                    'in_list_properties_arr' => new Type\ArrayList(array(
                        'description' => t('Характеристики списка (удерживая CTRL можно выбрать несколько)'),
                        'hint' => t('Данные характеристики могут отображаться в списке товаров, если это предусматривает тема оформления'),
                        'list' => array(array('\Catalog\Model\PropertyApi', 'staticSelectList')),
                        'attr' => array(array(
                            'multiple' => true,
                            'size' => 10
                        )),
                        'rootVisible' => false
                    )),
                    'in_list_properties' => new Type\Text(array(
                        'description' => t('Характеристики списка'),
                        'visible' => false
                    )),

            t('Подбор товаров'),
                    '__virtual__' => new Type\UserTemplate('%catalog%/form/dir/virtual.tpl', null, array(
                        'rootVisible' => false,
                        'specVisible' => false,
                        'getBrands' => function() {
                            return \Catalog\Model\BrandApi::staticSelectList();
                        },
                        'getProperties' => function() {
                            return \Catalog\Model\PropertyApi::staticSelectList();
                        }                        
                    )),
                    'is_virtual' => new Type\Integer(array(
                        'description' => t('Включить подбор товаров'),
                        'checkboxView' => array(1,0),
                        'visible' => false,
                    )),
                    'virtual_data_arr' => new Type\ArrayList(array(
                        'description' => t('Параметры выборки товаров'),
                        'visible' => false,
                    )),
                    'virtual_data' => new Type\Text(array(
                        'description' => t('Параметры выборки товаров'),
                        'visible' => false,
                    )),
            t('Экспорт даных'),
                'export_name' => new Type\Varchar(array(
                    'description' => t('Название категории при экспорте'),
                    'hint' => t('Если не указано - используется "название категории". Используется для экспорта данных во внешние системы, например в Яндекс.Маркет'),
                )),
            t('Рекомендуемые товары'),
                'recommended' => new Type\Varchar(array(
                    'maxLength' => 4000,
                    'description' => t('Рекомендуемые товары'),
                    'visible' => false,
                )),
                'recommended_arr' => new Type\ArrayList(array(
                    'visible' => false
                )),
                '_recomended_' => new Type\UserTemplate(
                    '%catalog%/form/product/recomended.tpl',
                    '%catalog%/form/product/merecomended.tpl',array(
                    'meVisible' => true  //Видимость при мультиредактировании
            )),
            t('Сопутствующие товары'),
                'concomitant' => new Type\Varchar(array(
                    'maxLength' => 4000,
                    'description' => t('Сопутствующие товары'),
                    'visible' => false,
                )),
                'concomitant_arr' => new Type\ArrayList(array(
                    'visible' => false
                )),
                '_concomitant_' => new Type\UserTemplate(
                    '%catalog%/form/product/concomitant.tpl',
                    '%catalog%/form/product/meconcomitant.tpl',array(
                    'meVisible' => true  //Видимость при мультиредактировании
            )),
        ));
        
        $this->addIndex(array('site_id', 'parent'));
        $this->addIndex(array('site_id', 'name', 'parent'));
        $this->addIndex(array('site_id', 'xml_id'), self::INDEX_UNIQUE);
        $this->addIndex(array('site_id', 'alias'), self::INDEX_UNIQUE);
    }
    
    /**
    * Возвращает отладочные действия, которые можно произвести с объектом
    * 
    * @return \RS\Debug\Action\AbstractAction[]
    */
    function getDebugActions()
    {
        return array(
            new \RS\Debug\Action\Edit(\RS\Router\Manager::obj()->getAdminPattern('edit_dir', array(':id' => '{id}'), 'catalog-ctrl')),
            new \RS\Debug\Action\Delete(\RS\Router\Manager::obj()->getAdminPattern('del_dir', array(':chk[]' => '{id}'), 'catalog-ctrl'))        
        );
    }

    /**
     * Инициализация свойств по умолчанию
     */
    function _initDefaults()
    {
        $this['is_spec_dir'] = 'N'; //Если свойство не задано, то его значение будет N = Нет
    }
    
    /**
    * При создании записи sortn - ставим максимальный, т.е. добавляем фото в конец.
    */
    function beforeWrite($save_flag)
    {
        if ($save_flag == self::INSERT_FLAG)
        {
            if (!$this->isModified('sortn')) {
                $this['sortn'] = \RS\Orm\Request::make()
                    ->select('MAX(sortn) maxid')
                    ->from($this)
                    ->exec()->getOneField('maxid', 0) + 1;
            }
        }
        
        if (empty($this['itemcount'])) $this['itemcount'] = 0;
        
        $api = new \Catalog\Model\Dirapi();
        $parents_arrs = $api-> getPathToFirst($this['parent']);
        if($this['id'] && isset($parents_arrs[$this['id']])){
            return $this->addError(t('Неверно указан родительский элемент'), 'parent');
        }

        //$this['level'] = ($this['parent']>0) ? count($api->queryParents($this['parent'])) : 0;

        if ($this->isModified('recommended_arr')){ //Если изменялись рекомендуемые
            $this['recommended'] = serialize($this['recommended_arr']);
        }
        if ($this->isModified('concomitant_arr')){ //Если изменялись сопутствующие
            $this['concomitant'] = serialize($this['concomitant_arr']);
        }
        
        if ($this['xml_id'] === '') {
            unset($this['xml_id']);
        }
        
        if (empty($this['alias'])) {
            $this['alias'] = null;
        }
        
        if ($this['is_virtual']) {
            $this['virtual_data'] = serialize($this['virtual_data_arr']);
            \RS\Cache\Manager::obj()->invalidateByTags(\Catalog\Model\VirtualDir::INVALIDATE_TAG);
        }

        if ($this->isModified('in_list_properties_arr')) {
            $this['in_list_properties'] = serialize($this['in_list_properties_arr']);
        }

        return true;
    }    
    
    /**
    * Функция срабатывает после сохранения.
    * 
    * @param string $flag - update или insert
    */
    function afterWrite($flag)
    {
        if ($this->isModified('parent') && empty($this['no_update_levels'])) {
            \Catalog\Model\Dirapi::updateLevels();
        }
        if ($flag == self::UPDATE_FLAG) {
           \RS\Cache\Manager::obj()->invalidateByTags(CACHE_TAG_UPDATE_CATEGORY); 
        }
    }
    
    /**
    * Возвращает клонированный объект доставки
    * @return \Catalog\Model\Orm\Dir
    */
    function cloneSelf()
    {
        $this->fillProperty();
        /**
        * @var \Catalog\Model\Orm\Dir $clone
        */
        $clone = parent::cloneSelf();
        //Клонируем фото, если нужно
        if ($clone['image']){
           /**
           * @var \RS\Orm\Type\Image
           */
           $clone['image'] = $clone->__image->addFromUrl($clone->__image->getFullPath());
        }
        unset($clone['alias']);
        unset($clone['xml_id']);
        unset($clone['sortn']);
        $clone['itemcount'] = 0;
        return $clone;
    }
    
    /**
    * Действия после загрузки самого объекта
    * @return void
    */
    function afterObjectLoad()
    {
        if (!empty($this['recommended'])) {
            $this['recommended_arr'] = @unserialize($this['recommended']);
        }
        if (!empty($this['concomitant'])) {
            $this['concomitant_arr'] = @unserialize($this['concomitant']);
        }
        $this['_alias'] = empty($this['alias']) ? (string)$this['id'] : $this['alias'];
        $this['_class'] = $this['is_spec_dir'] == 'Y' ? 'specdir' : '';
        $this['_class'] .= $this['is_virtual'] ? ' virtual' : '';
        
        if ($this['is_virtual']) {
            $this['virtual_data_arr'] = @unserialize($this['virtual_data']) ?: array();
        }

        $this['in_list_properties_arr'] = @unserialize($this['in_list_properties']);
    }
    
    /**
    * Возвращает список характеристик из поля prop в виде списка объектов
    */
    function getPropObjects()
    {
        return $this['properties'];
    }

    /**
     * Возвращает HTML код для блока "рекомендуемые товары"
     * @return \Catalog\Model\ProductDialog
     */
    function getProductsDialog()
    {
        return new \Catalog\Model\ProductDialog('recommended_arr', true, @(array) $this['recommended_arr']);
    }

    /**
     * Возвращает HTML код для блока "сопутствующие товары"
     * @return \Catalog\Model\ProductDialog
     */
    function getProductsDialogConcomitant()
    {
        $product_dialog = new \Catalog\Model\ProductDialog('concomitant_arr', true, @(array) $this['concomitant_arr']);
        $product_dialog->setTemplate('%catalog%/dialog/view_selected_concomitant.tpl');
        return $product_dialog;
    }

    /**
     * Возвращает товары, рекомендуемые вместе с текущим
     *
     * @param bool $return_hidden - Если true, то метод вернет даже не публичные товары. Если false, то только публичные
     * @return Product[]
     */
    function getRecommended($return_hidden = false)
    {
        $list = array();
        if (isset($this['recommended_arr']['product'])) {
            foreach ($this['recommended_arr']['product'] as $id) {
                $product = new Product($id);
                if ($product['id'] && ($return_hidden || $product['public'])) {
                    $list[$id] = $product;
                }
            }
        }
        return $list;
    }

    /**
     * Возвращает товары, сопутствующие для текущего
     *
     * @return Product[]
     */
    function getConcomitant()
    {
        $list = array();
        if (isset($this['concomitant_arr']['product'])) {
            foreach ($this['concomitant_arr']['product'] as $id) {
                $only_one = @$this['concomitant_arr']['onlyone'][$id];
                $list[$id] = new Product($id);
                $list[$id]->onlyone = $only_one;
            }
        }
        return $list;
    }

    /**
     * Заполняет характеристики категории
     *
     * @return array
     */
    function fillProperty()
    {
        if ($this['properties'] === null) {
            $property_api = new \Catalog\Model\PropertyApi();
            $this['properties'] = $property_api->getGroupProperty($this['id']);
        }
        return $this['properties'];
    }

    /**
     * Возвращает псевдоним переведнный в кодировку для адров
     *
     * @return string
     */
    function alias()
    {
        return urlencode($this['_alias']);
    }
    
    /**
    * Удаление категории товара.
    */
    function delete()
    {        
        $ids = \RS\Orm\Request::make()
            ->select('product_id')
            ->from(new Xdir())
            ->where(array('dir_id' => $this['id']))
            ->exec()
            ->fetchSelected(null, 'product_id');

        if (!empty($ids)) {
            $api = new \Catalog\Model\Api();
            $api->multiDelete($ids, $this['id']); //Удаляем товары
        }        
		
    	return parent::delete(); //Удаляем текущий объект из базы.
	}
    
    
    /**
    * Перемещает товары из удаляемой папки в папку для удаленных товаров.
    */
    protected function _moveProducts()
    {
        if (!isset(self::$deleted_folder_id)) {
            $api = new \Catalog\Model\Dirapi();
            $dir = $api->getByAlias('deleted');
            if ($dir) {
                self::$deleted_folder_id = $dir['id'];
            } else {//Создаем эту папку при необходимости
                $newdir = $api->getElement();
                $newdir['id'] = '1';
                $newdir['name'] = t('Товары из удаленных папок');
                $newdir['parent'] = 0;
                $newdir['public'] = 0;
                $newdir['alias'] = 'deleted';
                $newdir->insert();
                self::$deleted_folder_id = $newdir['id'];
            }
        }
        
        //Переносим товары в спец. папку. Помним, что товар уже может присутствовать там
        \RS\Orm\Request::make()
            ->update(new Xdir(), true)
            ->set(array('dir_id' => self::$deleted_folder_id))
            ->where(array('dir_id' => $this['id']))
            ->exec();

        //Если в предыдущем запросе возникла ошибка Duplicate, то чистим связи с этой директорией.            
        \RS\Orm\Request::make()
            ->delete()
            ->from(new Xdir())
            ->where(array('dir_id' => $this['id']))
            ->exec();
    }
    
    /**
    * Возвращает объект фото-заглушку
    */
    function getImageStub()
    {
        return new \Photo\Model\Stub();
    }
    
    /**
    * Возвращает главную фотографию
    * @return \Photo\Model\Orm\Image
    */
    function getMainImage($width = null, $height = null, $type = 'xy')
    {
        $img = $this['image'] ? $this->__image : $this->getImageStub();
        
        return ($width === null) ? $img : $img->getUrl($width, $height, $type);
    }
    
    /**
    * Возвращает путь к странице со списком товаров
    * @return string
    */
    function getUrl($absolute = false)
    {
        return \RS\Router\Manager::obj()->getUrl('catalog-front-listproducts', array('category' => $this['_alias']), $absolute);
    }
    
    /**
    * Возвращает родительскую категорию
    * 
    * @return self
    */
    function getParentDir()
    {
        return new self($this['parent']);
    }
    
    /**
    * Возвращает объект, который работает с виртуальными категориями 
    * 
    * @return \Catalog\Model\VirtualDir
    */
    function getVirtualDir()
    {
        return new \Catalog\Model\VirtualDir($this);
    }
    
}