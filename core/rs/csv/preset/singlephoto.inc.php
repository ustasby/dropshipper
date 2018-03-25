<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace RS\Csv\Preset;

/**
* Добавляет к экспорту колонку для поля Orm\Type\Image с экспортом фото.
* Обеспечивает корректный импорт
*/
class SinglePhoto extends AbstractPreset
{
    protected
        $link_preset_id,
        $link_foreign_field,
        $title;
        
    function loadData()
    {
        $this->rows = $this->schema->rows;
    }
    
    /**
    * Устанавливает название колонки
    * 
    * @param string $title название колонки в CSV файле
    * @return void
    */
    function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
    * Определяет foreign key другого объекта
    * 
    * @param string $field
    * @return void
    */
    function setLinkForeignField($field)
    {
        $this->link_foreign_field = $field;
    }
    
    /**
    * Устанавливает номер пресета, к которому линкуется текущий пресет
    * 
    * @param integer $n - номер пресета
    * @return void
    */
    function setLinkPresetId($id)
    {
        $this->link_preset_id = $id;
    }
    
    
    /**
    * Возвращает данные для вывода в CSV
    * 
    * @return array
    */
    function getColumns()
    {
        return array(
            $this->id.'-singlephoto' => array(
                'key' => 'singlephoto',
                'title' => $this->title
            )
        );
    }
    
    /**
    * Возвращает данные для вывода в CSV
    * 
    * @return array
    */
    function getColumnsData($n)
    {
        $this->row = array();
        foreach($this->getColumns() as $id => $column) {
            if ($this->rows[$n][$this->link_foreign_field]) {
                $this->row[$id] = $this->rows[$n]['__'.$this->link_foreign_field]->getLink();
            }
        }
        return $this->row;
    }
    
    
    /**
    * Импортирует данные одной строки текущего пресета в базу
    * 
    * @return void
    */
    function importColumnsData()
    {
        if ($this->row['singlephoto']) {
            //Перемещаем файл в хранилище
            $linked_preset = $this->schema->getPreset($this->link_preset_id);
            $import_file = \Setup::$PATH.'/'.ltrim($this->row['singlephoto'], '/');
            if (file_exists($import_file)) {
                $object = $linked_preset->getOrmObject();                
                $field = $object['__'.$this->link_foreign_field];
                $value = $field->generateValue(basename($this->row['singlephoto']));
                \RS\File\Tools::makePath( $field->getStorageFolder() );
                if (@copy($import_file, $field->getStorageFolder().$field->getUniqFilename())) {
                    $linked_preset->row[$this->link_foreign_field] = $value;
                    
                }
            }
        }
    }
}
?>