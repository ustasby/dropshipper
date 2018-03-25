<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Csv\Preset;

/**
* Абстрактный класс набора колонок(preset'а). Во время экспорта данных пресеты получают управление 
* по очереди, описываемой в схеме, слева-направо. Каждый пресет формирует массив с колонками для одной строки данных.
* Во время импорта данных пресеты получают управление в обратном порядке - справа-налево, 
* выполняя определенное действие по импорту или подготовке для импорта сведений.
* У каждого пресета всегад присутствует ссылка на общую CSV схему (AbstractSchema $schema), благодаря чему пресеты могут обращаться к другим пресетам.
*/
abstract class AbstractPreset
{
    protected
        $id,
        /**
        * @var \RS\Csv\AbstractSchema
        */
        $schema,
        $fields_map,
        $before_import_callback,
        $after_import_callback,
        $before_row_export_callback,    
        $option_prefixes = array('set', 'add');
    
    function __construct($options)
    {
        foreach($options as $option => $value)
            foreach($this->option_prefixes as $prefix) {
                $method_name = $prefix.$option;
                if (method_exists($this, $method_name)) {
                    $this->$method_name($value);
                } else {
                    $this->$method_name = $value;
                }
            }                
    }    
        
    /**
    * Возвращает ассоциативный массив с одной строкой данных, где ключ - это id колонки, а значение - это содержимое ячейки
    * 
    * @param integer $n - индекс в наборе строк $this->rows
    * @return array
    */
    abstract function getColumnsData($n);
    
    /**
    * Импортирует одну строку данных
    * 
    * @return void
    */
    abstract function importColumnsData();
    
    /**
    * Возвращает колонки, которые добавляются текущим набором 
    * 
    * @return array
    */
    abstract function getColumns();
    
    /**
    * Устанавливает схему, для которй работает данный пресет
    * 
    * @param \RS\Csv\AbstractSchema $schema
    * @return AbstractPreset
    */
    function setSchema(\RS\Csv\AbstractSchema $schema)
    {
        $this->schema = $schema;
        return $this;
    }    
    
    /**
    * Возвращает текущую схему
    * 
    * @return \RS\Csv\AbstractSchema
    */
    function getSchema(){
        return $this->schema;
    }
    
    /**
    * Данный метод вызывается перед тем, как строка будет импортирована
    */
    function beforeRowImport()
    {
        if ($this->before_import_callback) {
            return call_user_func($this->before_import_callback, $this);
        }
    }

    function beforeRowExport($row_index)
    {
        if ($this->before_row_export_callback) {
            call_user_func($this->before_row_export_callback, $this, $row_index);
        }
    }

    /**
    * Данный метод вызывается после того, как строка импортирована
    */
    function afterRowImport()
    {
        if ($this->after_import_callback) {
            call_user_func($this->after_import_callback, $this);
        }
    }
        
    
    /**
    * Устанавливает callback, который вызовется перед импортом строки
    * 
    * @param callback $callback
    * @return void
    */
    function setBeforeRowImport($callback)
    {
        $this->before_import_callback = $callback;
        return $this;
    }    
    
    /**
    * Устанавливает callback, который вызовется после импорта строки
    * 
    * @param callback $callback
    * @return void
    */
    function setAfterRowImport($callback)
    {
        $this->after_import_callback = $callback;
        return $this;
    }        
    
    /**
    * Устанавливает callback, который будет вызван перед экспортом строки
    * 
    * @return void
    */
    function setBeforeRowExportCallback($callback)
    {
        $this->before_row_export_callback = $callback;
        return $this;
    }    

    /**
    * Устанавливает внутренний ID для пресета
    * 
    * @return AbstractPreset
    */
    function setId($id)
    {
        $this->id = $id;
        return $this;
    }        
    
    /**
    * Устанавливает переназначение свойств для импорта данных
    * 
    * @param array $map массив, где ключ - это имя поля из выборки, а значение - это реальное имя колонки у текущего объекта пресета
    * @return 
    */
    function setFieldsMap(array $map)
    {
        $this->fields_map = $map;
        return $this;
    }
    
    /**
    * Возвращает true, если существуют правила для переназначения свойств для поля $field
    * 
    * @param mixed $field
    * @return bool
    */
    function hasMap($field)
    {
        return isset($this->fields_map[$field]);
    }
    
    /**
    * Возвращает имя поля с учетом его переназначения
    * 
    * @param string $field
    * @return string
    */
    function getMappedField($field)
    {
        return isset($this->fields_map[$field]) ? $this->fields_map[$field] : $field;
    }    

    /**
    * Загружает данные перед экспортом
    * 
    * @return void
    */
    function loadData()
    {
    }    
}