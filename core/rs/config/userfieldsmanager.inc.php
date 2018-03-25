<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace RS\Config;

/**
* Класс работает с формами, в которых есть дополнительные(установленные в конфигурации) поля.
*/
class UserFieldsManager
{
    private
        $field,
        $list_delimiter = ',',
        $admin_tpl = '%SYSTEM%/admin/userfields.tpl',
        $tpl = '%SYSTEM%/coreobject/userform.tpl',
        $arr_wrap,
        $structure,
        $error_prefix = '',
        $errors = array(),        
        $values = array();
    
    /**
    * Конструктор менеджера дополнительных полей
    * 
    * @param mixed $structure структура дополнительных полей
    * @param mixed $values значенния дополнительных полей
    * @return UserFieldsManager
    */
    function __construct($structure, $values = null, $field = null)
    {
        $this->structure = (array)$structure;
        $this->setDefaults();
        $this->setField($field);        
        if (!empty($values)) $this->setValues($values);
    }
    
    /**
    * Устанавливает поле в котором у объекта хранятся сведения о доп. полях
    * 
    * @param string $field имя поля
    * @return UserFieldsManager
    */
    function setField($field)
    {
        $this->field = $field;
        return $this;
    }
    
    /**
    * Возвращает поле, в котором у объекта хранятся сведения о доп. полях
    * 
    * @return string
    */
    function getField()
    {
        return $this->field;
    }
    
    /**
    * Возвращает true, если заданы дополнительные поля
    * @return boolean
    */
    function notEmpty()
    {
        return !empty($this->structure);
    }

    /**
     * Возвращает структуру дополнительных полей
     * @return array
     */
    function getStructure()
    {
        return $this->structure;
    }

    
    /**
    * Устанавливает значения по умолчанию, заданные в структуре
    * @return UserFieldsManager
    */
    function setDefaults()
    {
        $values = array();
        foreach ($this->structure as $key => $item) {
            $values[$key] = $item['val'];
        }
        $this->setValues($values);
        return $this;
    }

    /**
    * Устанавливает значения.
    * 
    * @param array $values
    * @return UserFieldsManager
    */
    function setValues($values)
    {
        $this->values = $values;
        foreach($this->structure as $key=>$val) {
            $this->structure[$key]['current_val'] = $this->textView($key);
        }
        return $this;
    }
    
    /**
    * Возвращает установленные значения полей
    * 
    * @return array
    */
    function getValues()
    {
        return $this->values;
    }
    
    /**
    * Возвращает true, если значения не соответствуют требованиям структуры
    * 
    * @param array $data - массив ключ => значение для проверки
    * @return boolean
    */
    function check($data = null)
    {
        if ($data !== null) 
            $this->setValues($data);

        $this->errors = array();
        foreach($this->structure as $key => $item) {
            if ($item['necessary']) {
                if (empty($this->values[$key])) {
                    $this->errors[$key] = t("Поле '%0' является обязательным", array($item['title']));
                }
            }
        }
        return empty($this->errors);
    }
    
    /**
    * Устанавливает имя массива, в котором будут передаваться значения полей
    * 
    * @param string $name
    * @return UserFieldsManager
    */
    function setArrayWrapper($name)
    {
        $this->arr_wrap = $name;
        return $this;
    }
    
    /**
    * Устанавливает префикс перед ключом поля с ошибкой
    * 
    * @param string $prefix
    * @return UserFieldsManager
    */
    function setErrorPrefix($prefix)
    {
        $this->error_prefix = $prefix;
        return $this;
    }
    
    /**
    * Возвращает ошибки, которые произошли при заполнении доолнительных полей
    * @return array
    */
    function getErrors()
    {
        $errors = array();
        foreach($this->errors as $form => $err) {
            $errors[$this->error_prefix.$form] = $err;
        }
        return $errors;
    }
    
    /**
    * Возвращает имя формы для поля с ключем $key
    * 
    * @param $key - идентификатор поля
    * @return string
    */
    function getFieldName($key)
    {
        return $this->arr_wrap.'['.$key.']';
    }
    
    /**
    * Возвращает HTML формы одного дополнительного поля
    * 
    * @param string $key - ключ поля
    * @return string
    */
    function getForm($key, $template = null)
    {   
        if($template == null){
            $template = $this->tpl;
        }     
        $tpl = new \RS\View\Engine();
        if ($this->structure[$key]['type'] == 'list') {
            $tpl->assign('options', $this->parseValueList($this->structure[$key]['values']) );
        }
        
        $fld = $this->structure[$key];
        
        if ($this->arr_wrap !== null) 
            $fld['fieldname'] = $this->arr_wrap.'['.$fld['alias'].']';
            
        $tpl->assign(array(
            'fld' => $fld,
            'values' => $this->values,
            'has_error' => isset($this->errors[$key])
        ));
        return $tpl->fetch($template);
    }
    
    /**
    * Возвращает текстовое отображение дополнительного поля
    * 
    * @param string $key - ключ поля
    * @return string
    */
    function textView($key)
    {
        if ($this->structure[$key]['type'] == 'bool') {
            return empty($this->values[$key]) ? t('Нет') : t('Да');
        } else {
            return isset($this->values[$key]) ? $this->values[$key] : '';
        }
    }
    
    /**
    * Возвращает имя ключа в массиве ошибок для заданного поля
    * 
    * @param string $key - ключ поля
    * @return string
    */
    function getErrorForm($key)
    {
        return $this->error_prefix.$key;
    }
    
    /**
    * Возвращает массив со списком значений для поля с типом: список
    * 
    * @param string $str
    * @return array
    */
    function parseValueList($str)
    {
        $list = explode($this->list_delimiter, $str);
        foreach($list as &$item) {
            $item = trim($item);
        }
        return $list;
    }
    
    /**
    * Возвращает HTML для администрирования дополнительных полей
    * 
    * @return string
    */
    function getAdminForm($before_phrase = '')
    {
        $view = new \RS\View\Engine();
        $view->assign(array(
            'before_phrase' => $before_phrase,
            'manager' => $this
        ));
        return $view->fetch($this->admin_tpl);
    }
    
}
