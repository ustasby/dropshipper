<?php
namespace DataBlocks\Model\Orm;
use DataBlocks\Model\Exception;
use RS\Helper\Tools;
use RS\Orm\AbstractObject;
use RS\Orm\Type;
use RS\View\Engine;

/**
 * Объект описывает один кастомный параметр одного узла
 */
class DataNodeField extends AbstractObject
{
    const
        FIELD_TYPE_CHECKBOX = 'checkbox',
        FIELD_TYPE_STRING = 'string',
        FIELD_TYPE_NUMERIC = 'numeric',
        FIELD_TYPE_FILE = 'file',
        FIELD_TYPE_PHOTOGALLERY = 'photos',
        FIELD_TYPE_IMAGE = 'image',
        FIELD_TYPE_RICHTEXT = 'richtext',
        FIELD_TYPE_TEXTAREA = 'textarea',
        FIELD_TYPE_SELECT = 'select';

    protected static
        $table = 'datablocks_node_fields';

    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'node_id' => new Type\Integer(array(
                'description' => t('ID узла')
            )),
            'type' => new Type\Enum(array_keys(self::getTypesTitle()), array(
                'description' => t('Тип'),
                'list' => array(array('\DataBlocks\Model\Orm\DataNodeField', 'getTypesTitle'))
            )),
            'name' => new Type\Varchar(array(
                'description' => t('Идентификатор поля'),
                'checker' => array('ChkEmpty', t('Укажите Идентификатор поля')),
                ' checker' => array('ChkPattern', t('Идентификатор может состоять только из английских букв и знака _(подчеркивание)'), '/^[a-zA-Z_\-]+$/'),
                '  checker' => array(function($_this, $value, $err) {
                    if ($_this['type'] == self::FIELD_TYPE_PHOTOGALLERY && strlen($value) > 17) {
                        return $err;
                    }
                    return true;
                }, t('Длина идентификатора не может превышать 17 знаков'))
            )),
            'title' => new Type\Varchar(array(
                'description' => t('Наименование поля'),
                'checker' => array(function($_this, $value, $err) {
                    //Для фотогалереи название задавать не обязательно
                    if ($_this['type'] != self::FIELD_TYPE_PHOTOGALLERY && $value == '') {
                        return $err;
                    }
                    return true;
                }, t('Не указано наименование'))
            )),
            'tab_title' => new Type\Varchar(array(
                'description' => t('Имя вкладки'),
            )),
            'attributes' => new Type\ArrayList(array(
                'description' => t('Атрибуты')
            )),
            '_attributes' => new Type\Text(array(
                'description' => t('Атрибуты'),
                'visible' => false
            )),
            'list_values' => new Type\ArrayList(array(
                'description' => t('Значения списка')
            )),
            '_list_values' => new Type\Text(array(
                'description' => t('Значения списка')
            )),
            'custom' => new Type\ArrayList(array(
                'description' => t('Произвольные данные')
            )),
            '_custom' => new Type\Text(array(
                'description' => t('Произвольные данные'),
                'visible' => false
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Порядок')
            ))
        ));

        $this->addIndex(array('node_id', 'name'), self::INDEX_PRIMARY);
    }

    /**
     * Выполняется перед сохранением объекта
     *
     * @param string $flag
     * @return void
     */
    function beforeWrite($flag)
    {
        $this['_attributes'] = serialize($this['attributes']);
        $this['_custom'] = serialize($this['custom']);
        $this['_list_values'] = serialize($this['list_values']);
    }

    /**
     * Выполняется после сохранения объекта
     */
    function afterObjectLoad()
    {
        $this['attributes'] = @unserialize($this['_attributes']) ?: array();
        $this['custom'] = @unserialize($this['_custom']) ?: array();
        $this['list_values'] = @unserialize($this['_list_values']) ?: array();
    }

    /**
     * Возвращает названия возможных типов параметров
     *
     * @return array
     */
    public static function getTypesTitle()
    {
        return array(
            self::FIELD_TYPE_CHECKBOX => t('да/нет'),
            self::FIELD_TYPE_STRING => t('строка'),
            self::FIELD_TYPE_NUMERIC => t('число'),
            self::FIELD_TYPE_FILE => t('файл'),
            self::FIELD_TYPE_PHOTOGALLERY => t('фотогалерея'),
            self::FIELD_TYPE_IMAGE => t('картинка'),
            self::FIELD_TYPE_RICHTEXT => t('текстовый редактор'),
            self::FIELD_TYPE_TEXTAREA => t('текст'),
            self::FIELD_TYPE_SELECT => t('Список')
        );
    }

    /**
     * Возвращает шаблон текущего параметра для отображения в карточке элемента (узла)
     *
     * @return string
     */
    public function getFieldTemplate()
    {
        switch($this['type']) {
            case self::FIELD_TYPE_PHOTOGALLERY:
                return '%datablocks%/form/datanode/types/photos.tpl';
            case self::FIELD_TYPE_SELECT:
                return '%datablocks%/form/datanode/types/list.tpl';
        }
        return '%datablocks%/form/datanode/types/default.tpl';
    }

    /**
     * Возвращает true, если по данному полю возможен поиск
     *
     * @return bool
     */
    public function canSearch()
    {
        switch($this['type']) {
            case self::FIELD_TYPE_SELECT:
            case self::FIELD_TYPE_STRING:
            case self::FIELD_TYPE_NUMERIC:
                $result = true;
                break;
            default:
                $result = false;
        }

        return $result;
    }

    /**
     * Возвращает готовый HTML одной строки данных
     *
     * @param bool $block_name
     * @return string
     */
    public function getFieldView($block_name = false)
    {
        $view = new Engine();
        $view->assign(array(
            'field' => $this,
            'block_name' => $block_name,
            'uniq_key' => Tools::generatePassword(10)
        ));
        return $view->fetch($this->getFieldTemplate());
    }

    /**
     * Возвращает название текущего типа параметра
     *
     * @return string
     */
    public function getTypeTitle()
    {
        $reference = self::getTypesTitle();
        return $reference[$this['type']];
    }

    /**
     * Возвращает объект свойства (Orm\Type\...) для вставки в ORM объект
     *
     * @return Type\File|Type\Image|Type\Integer|Type\Richtext|Type\Text|Type\Varchar
     * @throws Exception
     */
    public function getOrmTypeObject()
    {
        switch($this['type']) {
            case self::FIELD_TYPE_STRING:
            case self::FIELD_TYPE_NUMERIC:
                $orm_type = new Type\Varchar(array());
                break;
            case self::FIELD_TYPE_SELECT:
                $orm_type = new Type\Varchar(array(
                    'listFromArray' => array($this['list_values'])
                ));
                break;
            case self::FIELD_TYPE_IMAGE:
                $orm_type = new Type\Image();
                break;
            case self::FIELD_TYPE_FILE:
                $orm_type = new Type\File();
                break;
            case self::FIELD_TYPE_CHECKBOX:
                $orm_type = new Type\Integer(array(
                    'checkboxView' => array(1,0)
                ));
                break;
            case self::FIELD_TYPE_RICHTEXT:
                $orm_type = new Type\Richtext();
                break;
            case self::FIELD_TYPE_TEXTAREA:
                $orm_type = new Type\Text();
                break;
            case self::FIELD_TYPE_PHOTOGALLERY:
                $orm_type = new Type\UserTemplate('%datablocks%/form/datanode/photos.tpl');
                break;
            default:
                throw new Exception(t('Неподдерживаемый тип параметра `%0`', array($this['type'])), Exception::BAD_FIELD_TYPE);
        }

        $orm_type->setDescription($this['title']);
        $orm_type->setRuntime(true);
        $orm_type->setName($this['name']);
        $orm_type->setArrayWrap('node_values');
        $orm_type->is_dynamic_field = true;
        $orm_type->dynamic_field_type = $this['type'];
        return $orm_type;
    }

    /**
     * Конвертирует формат сведений о характеристиках комплектации
     *
     * @param array $_propsdata ['key' => [ключ1, ключ2,...],  'value' => [значение1, значение2, ...]]
     * @return array ['ключ1' => 'значение1', 'ключ2' => 'значение2',...]
     */
    function convertArrayToAssoc($data)
    {
        $assoc_array = array();
        if (!empty($data)) {
            foreach($data['key'] as $n => $val) {
                if ($val !== '') {
                    $assoc_array[$val] = $data['val'][$n];
                }
            }
        }
        return $assoc_array;
    }
}