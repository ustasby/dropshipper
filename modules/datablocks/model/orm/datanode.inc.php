<?php
namespace DataBlocks\Model\Orm;
use Photo\Model\Orm\Image;
use Photo\Model\PhotoApi;
use RS\File\Tools as FileTools;
use RS\Orm\OrmObject;
use RS\Orm\Request;
use RS\Router\Manager as RouterManager;
use RS\Orm\Type;

/**
 * ORM объект одного узла дерева произвольных значений
 */
class DataNode extends OrmObject
{
    protected static
        $table = 'datablocks_node';

    protected
        $fields_appended = false,
        $node_values_copy,
        $for_delete_fields = array();
    
    public function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'description' => t('Наименование'),
                    'checker' => array('ChkEmpty', t('Наименование элемента должно быть заполнено')),
                    'attr' => array(array(
                        'data-autotranslit' => 'alias'
                    ))
                )),
                'alias' => new Type\Varchar(array(
                    'description' => t('Англ. идентификатор'),
                    'hint' => t('Будет использоваться для построения URL')
                )),
                'parent_id' => new Type\Integer(array(
                    'description' => t('Родительский элемент'),
                    'readOnly' => true,
                    'allowEmpty' => false,
                    'list' => array(array('DataBlocks\Model\DataNodeApi', 'staticSelectList'), array(0 => t('-- Верхний уровень --')))
                )),
                'public' => new Type\Integer(array(
                    'description' => t('Публичный'),
                    'default' => 1,
                    'checkboxView' => array(1,0)
                )),
                'sortn' => new Type\Integer(array(
                    'description' => t('Сорт.индекс'),
                    'default' => 100
                )),
                '_tmpid' => new Type\Hidden(array(
                    'meVisible' => false
                )),
            t('Параметры'),
                'child_structure' => new Type\ArrayList(array(
                    'description' => t('Параметры дочерних узлов'),
                    'hint' => t('Данные параметры можно будет настраивать у дочерних элементов'),
                    'field_types' => DataNodeField::getTypesTitle(),
                    'template' => '%datablocks%/form/datanode/child_structure.tpl',
                    'checker' => array(function($_this) {
                        $_this->validateChildStructure();
                        return true;
                    })
                )),
                'node_values' => new Type\ArrayList(array(
                    'description' => t('Значения параметров узла'),
                    'visible' => false
                )),
                'child_inherit_structure' => new Type\Integer(array(
                    'description' => t('Установить дочерним элементам параметры'),
                    'hint' => t('При создании дочернего элемента, у него будет сразу устанавливаться структура параметров (для его дочерних элементов) из данного элемента'),
                    'list' => array(array('DataBlocks\Model\DataNodeApi', 'staticSelectList'), array(0 => t('-- Текущего элемента --'), -1 => t('-- Не устанавливать --')))
                )),
                'child_is_leaf' => new Type\Integer(array(
                    'description' => t('Дочерние элементы - это крайние листы дерева'),
                    'hint' => t('Включение данной опции запретит создание дочерних элементов у потомков текущего элемента'),
                    'checkboxView' => array(1,0)
                )),
            t('Мета-теги'),
                'meta_title' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'description' => t('Заголовок'),
                )),
                'meta_keywords' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'description' => t('Ключевые слова(keywords)'),
                )),
                'meta_description' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'viewAsTextarea' => true,
                    'description' => t('Описание(description)'),
                )),
        ));

        //Включаем в форму hidden поле id.
        $this['__id']->setVisible(true);
        $this['__id']->setMeVisible(false);
        $this['__id']->setHidden(true);

        $this->addIndex(array('site_id', 'alias'), self::INDEX_UNIQUE);
    }

    /**
     * Вызывается перед сохранением элемента
     *
     * @param string $flag
     * @return bool|void
     */
    public function beforeWrite($flag)
    {
        if ($this['id'] < 0) {
            $this['_tmpid'] = $this['id'];
            unset($this['id']);
        }

        if ($flag == self::UPDATE_FLAG) {

            $before_field_list = $this->getFieldNameList();
            $new_field_list = array();
            foreach($this['child_structure'] as $item) {
                $new_field_list[] = $item['name'];
            }

            //Найдем поля, которые были удалены
            $this->for_delete_fields = array_diff($before_field_list, $new_field_list);
        }

        if ($this->hasError()) {
            return false;
        }

        $this->saveFields();
    }

    /**
     * Проверяет корректность заполнения параметров для дочерних элементов
     *
     * @return void
     */
    public function validateChildStructure()
    {
        $n = 1;
        $exists_names = array();
        $photogallery_tabs = array();
        foreach($this['child_structure'] as $item) {

            $field = new DataNodeField();
            $field['node_id'] = $this['id'];
            $field->getFromArray($item);

            $list_values = isset($item['list_values']) ? $item['list_values'] : array();
            $field['list_values'] = $field->convertArrayToAssoc($list_values);

            $attributes = isset($item['attributes']) ? $item['attributes'] : array();
            $field['attributes'] = $field->convertArrayToAssoc($attributes);

            if ($item['name']) {
                if (isset($exists_names[$item['name']])) {
                    $field->addError(t('Повторное использование идентификатора %0 в параметре %1', array(
                        $item['name'],
                        $n
                    )));
                }
                $exists_names[$item['name']] = true;
            }

            if ($item['type'] == DataNodeField::FIELD_TYPE_PHOTOGALLERY) {
                if (isset($photogallery_tabs[$item['tab_title']])) {
                    $field->addError(t('Невозможно использовать одно и то же имя вкладки для фотогалереи в параметре %1', array(
                        $item['tab_title'],
                        $n
                    )));
                }
                $photogallery_tabs[$item['tab_title']] = true;
            }

            foreach ($field->getProperties() as $key => $property) {
                $err = $this->validateFieldProperty($field, $property);  //Пропускаем через валидаторы форм
                if ($err !== true) {
                    $field->addError($err, $key);
                }
            }

            if ($field->hasError()) {
                $this->addError(t('Ошибки в параметре %0:%1', array($field['name'] ?: $n, $field->getErrorsStr())), 'child_structure');
            }
            $n++;
        }
    }

    /**
     * Проверяет Параметр дочернего элемента на корректность заполнения
     *
     * @param DataNodeField $field
     * @param Type\AbstractType $property
     * @return bool|string Текст ошибки или true
     */
    public function validateFieldProperty($field, $property)
    {
        foreach ($property->getCheckers() as $checker) {

            $param = array_merge(array($field, $property->get()), $checker['param']);
            if (is_string($checker['callmethod'])) {
                $callback = array('\RS\Orm\Type\Checker', $checker['callmethod']);
            } else {
                $callback = $checker['callmethod'];
            }
            /* Передает checker'у параметры:
              1.$this - текущий объект
              2.$value - значение поля на проверку
              3.$errortext - текст ошибки
              4.... произвольные параметры, переданные в setChecker у свойства
              5....
              ... */
            $result = call_user_func_array($callback, $param);

            if ($result !== true)
                return $result;
        }
        return true;
    }

    /**
     * Вызывается после сохранения элемента
     *
     * @param string $flag
     * @return void
     */
    public function afterWrite($flag)
    {
        //Обновляем/создаем записи
        $sortn = 0;
        foreach($this['child_structure'] as $item) {
            $field = new DataNodeField();
            $field['sortn'] = $sortn;
            $field->getFromArray($item);

            $list_values = isset($item['list_values']) ? $item['list_values'] : array();
            $field['list_values'] = $field->convertArrayToAssoc($list_values);

            $attributes = isset($item['attributes']) ? $item['attributes'] : array();
            $field['attributes'] = $field->convertArrayToAssoc($attributes);

            $field['node_id'] = $this['id'];
            
            $field->replace();
            $sortn++;
        }

        //Удаляем старые записи
        if ($this->for_delete_fields) {
            Request::make()
                ->delete()
                ->from(new DataNodeField())
                ->where(array(
                    'node_id' => $this['id']
                ))
                ->whereIn('name', $this->for_delete_fields)
                ->exec();

            Request::make()
                ->delete()
                ->from(new DataNodeValue())
                ->where(array(
                    'node_id' => $this['id'],
                ))
                ->whereIn('field_key', $this->for_delete_fields)
                ->exec();
        }

        //Сохраняем существующие записи
        if ($this->isModified('node_values')) {
            $this->saveNodeValues();
        }

        if ($this['_tmpid']<0) {
            //Перепривязываем фото к новому объекту
            $this->updateLinkPhotogallery();
        }
    }

    /**
     * Вызывается после загрузки элемента из БД
     *
     * @return void
     */
    public function afterObjectLoad()
    {
        $this->loadNodeValues();
    }

    /**
     * Обновляет связи картинок с объектом, после присвоения ему ID
     *
     * @return void
     */
    private function updateLinkPhotogallery()
    {
        //Получаем список параметров с типом фотогалерея
        foreach($this->getProperties() as $name => $item) {
            if (isset($item->is_dynamic_field)
                && $item->dynamic_field_type == DataNodeField::FIELD_TYPE_PHOTOGALLERY)
            {
                $type = $this->getTypePhotogalleryByName($item->getName());

                Request::make()
                    ->update(new Image())
                    ->set(array(
                        'linkid' => $this['id']
                    ))
                    ->where(array(
                        'linkid' => $this['_tmpid'],
                        'type' => $type
                    ))->exec();
            }
        }
    }

    /**
     * Сохраняет значения кастомных полей для текущего элемента.
     *
     * @return void
     */
    private function saveNodeValues()
    {
        Request::make()
            ->delete()
            ->from(new DataNodeValue())
            ->where(array(
                'node_id' => $this['id']
            ))->exec();

        $node_values = $this['node_values'];

        foreach($node_values as $field_key => &$value) {
            if (isset($this['__fld_'.$field_key])) {
                $type = $this['__fld_'.$field_key]->dynamic_field_type;

                //Восстановим значение для файлов и изображений
                if (($type == DataNodeField::FIELD_TYPE_FILE
                        || $type == DataNodeField::FIELD_TYPE_IMAGE)
                        && !$value
                        && empty($_POST['del_'.$field_key])
                ) {

                    $value = $this->node_values_copy[$field_key]['field_str_value'];
                }

                $node_value = new DataNodeValue();
                $node_value['parent_node_id'] = $this['parent_id'];
                $node_value['field_key'] = $field_key;
                $node_value['field_str_value'] = mb_substr($value, 0, 255);
                if (is_numeric($value)) {
                    $node_value['field_int_value'] = $value;
                }
                $node_value['field_text_value'] = $value;
                $node_value['node_id'] = $this['id'];
                $node_value['node_type'] = $type;
                $node_value->insert();
            }
        }

        $this['node_values'] = $node_values;
    }

    /**
     * Возвращает объект родительского элемента
     *
     * @return DataNode
     */
    public function getParent()
    {
        return new DataNode($this['parent_id']);
    }

    /**
     * Возвращает один уровень дочерних элементов
     *
     * @param int $page номер страницы, начиная с 1. 0 - вывести все элементы
     * @param int $limit количество элементов на странице
     * @param null $order поле для сортировки
     * @return array
     */
    public function getChilds($page = 0, $limit = 20, $order = null)
    {
        $q = Request::make()
            ->from($this)
            ->where(array(
                'parent_id' => $this['id']
            ));

        if ($page > 0) {
            $offset = $page * $limit;
            $q->limit($limit, $offset);
        }

        if ($order) {
            $q->order($order);
        }

        return $q->objects();
    }

    /**
     * Загружает значения кастомных параметров узла в свойство node_values
     *
     * @return void
     */
    public function loadNodeValues()
    {
        $this['node_values'] = Request::make()
            ->from(new DataNodeValue())
            ->where(array(
                'node_id' => $this['id']
            ))
            ->objects(null, 'field_key');

        foreach($this['node_values'] as $key => $value) {
            $this['fld_'.$key] = $value->getFullValue();
        }
        $this->node_values_copy = $this['node_values'];
    }

    /**
     * Загружает структуру кастомных параметров в виде массива в свойство child_structure
     *
     * @return void
     */
    public function loadChildStructure()
    {
        $fields = Request::make()
            ->from(new DataNodeField())
            ->where(array(
                'node_id' => $this['id']
            ))
            ->orderby('sortn')
            ->objects();

        $result = array();
        foreach($fields as $item) {
            $result[] = $item->getValues();
        }

        $this['child_structure'] = $result;
    }

    /**
     * Возвращает структуру параметров для дочерних элементов в виде массива объектов
     *
     * @return DataNodeField[]
     */
    public function getChildStructureObjects()
    {
        $result = array();
        if ($this['child_structure']) {
            foreach ($this['child_structure'] as $item) {
                $field = new DataNodeField();
                $field->getFromArray($item);
                $result[] = $field;
            }
        }

        return $result;
    }

    /**
     * Возвращает список идентификаторов кастомных параметров дочерних узлов
     *
     * @return array
     */
    private function getFieldNameList()
    {
        return Request::make()
            ->select('name')
            ->from(new DataNodeField())
            ->where(array(
                'node_id' => $this['id']
            ))
            ->exec()->fetchSelected(null, 'name');
    }

    /**
     * Дополняет текущий элемент полями, которые заданы у родительского элемента
     *
     * @return void
     */
    public function appendParentFields()
    {
        if ($this['parent_id'] > 0 && !$this->fields_appended) {
            $parent = $this->getParent();
            $parent->loadChildStructure();


            $append_fields = array();
            foreach($parent->getChildStructureObjects() as $item) {
                if ($item['tab_title']) {
                    $append_fields[] = $item['tab_title'];
                } else {
                    $append_fields[] = t('Основные');
                }

                //Сохраняем с префиксом, чтобы не было конфликтов с родными полями DataNode
                $append_fields['fld_'.$item['name']] = $item->getOrmTypeObject();
            }

            $this->getPropertyIterator()->append($append_fields);
            $this->fields_appended = true;
        }
    }

    /**
     * Сохраняет значения кастомных параметров в отдельной таблице
     * @return void
     */
    private function saveFields()
    {
        $post = isset($_POST['node_values']) ? (array)$_POST['node_values'] : array();
        $files = isset($_FILES['node_values']) ? (array)$_FILES['node_values'] : array();
        $files = FileTools::normalizeFilePost($files);

        $source_data = $post + $files;
        $node_values = $this['node_values'];

        foreach($this->getProperties() as $name => $item) {
            if (!empty($item->is_dynamic_field)) {
                $value = $item->getFromRequest($source_data);
                $key = $item->getName();

                if ($value) {
                    $item->selfSave(); //Если это сложный объект, то пусть он сам себя сохраняет(например делает запись в промежуточной таблице)
                    $node_values[$key] = $item->get();
                }
            }
        }

        $this['node_values'] = $node_values;
    }

    public function getTypePhotogalleryByName($name)
    {
        return $type = "dn_".$name;
    }

    /**
     * Рекурсивно удаляем все дочерние и связанные элементы
     *
     * @return bool
     */
    public function delete()
    {
        foreach($this->getChilds() as $item) {
            $item->delete();
        }

        //Удаляем элемент вместе со связанными параметрами и значениями
        if ($result = parent::delete()) {
            //Удаляем привязанные фото
            $type = $this->getTypePhotogalleryByName('');
            $images = Request::make()
                ->from(new Image())
                ->where("type like '$type%'")
                ->where(array(
                    'linkid' => $this['id']
                ))
                ->objects();

            foreach($images as $image) {
                $image->delete();
            }

            //Удаляем параметры
            Request::make()
                ->delete()
                ->from(new DataNodeField())
                ->where(array(
                    'node_id' => $this['id']
                ))
                ->exec();

            //Удаляем значения параметров
            Request::make()
                ->delete()
                ->from(new DataNodeValue())
                ->where(array(
                    'node_id' => $this['id']
                ))->exec();
        }

        return $result;
    }

    /**
     * Возвращает ссылку на теущий элемент
     *
     * @param bool $absolute Если true, то вернет абсолютный URL
     * @return string
     */
    public function getUrl($absolute = false)
    {
        return RouterManager::obj()->getUrl('datablocks-front-nodeview', array(
            'alias' => $this['alias'] ?: $this['id']
        ), $absolute);
    }

    /**
     * Возвращает список фотографий, прикрепленных к данному ключу
     *
     * @param string $field_key Идентификатор параметра с типом фотогалерея
     * @return \Photo\Model\Orm\Image[]
     */
    public function getPhotogalleryImages($field_key)
    {
        $type = $this->getTypePhotogalleryByName($field_key);
        $photos_api = new PhotoApi();
        return $photos_api->getLinkedImages($this['id'], $type);
    }

    /**
     * Возвращает объект свойства, созданный динамически (для параметра).
     * Рекомендуется для использования в шаблонах
     *
     * @param string $field_key
     * @return mixed|Type\AbstractType
     */
    public function getDynamicProperty($field_key)
    {
        $this->appendParentFields();
        return $this['__fld_'.$field_key];
    }

    /**
     * Возвращает массив родительских элементов, включая текущий элемент
     *
     * @return DataNode[]
     */
    public function getPathToFirst()
    {
        $result = array();
        $current_item = $this;
        do {
            $result[] = $current_item;
            $current_item = $current_item->getParent();
        } while($current_item->id > 0);

        return array_reverse($result);
    }
}
