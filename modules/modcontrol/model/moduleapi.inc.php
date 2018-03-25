<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ModControl\Model;

class ModuleApi
{
    protected
        $filter;
        
    function tableData()
    {
        $module_manager = new \RS\Module\Manager();
        $list = $module_manager->getAllConfig();
        
        $table_rows = array();
        $i = 0;
        foreach ($list as $alias => $module)
        {
            $i++;
            
            $disable = $module['is_system'] ? array('disabled' => 'disabled') : null;
            $highlight = (time() - $module['lastupdate']) < 60*60*24 ? array('class' => 'highlight_new') : null;
            $module['class'] = $alias;
            
            if ($this->filter) {
                foreach($this->filter as $key => $val) {
                    if ($val != '' && mb_stripos($module[$key], $val) === false) {
                        continue 2;
                    }
                }
            }
            
            $table_rows[] = array(
                'num' => $i,
                'name' => $module['name'],
                'description' => $module['description'],
                'checkbox_attribute' => $disable,
                'row_attributes' => $highlight
            ) + $module->getValues();
        }
        return $table_rows;
    }
    
    function addTableControl()
    {
    }
    
    function addFilterControl(\RS\Html\Filter\Control $filter_control)
    {
        $key_val = $filter_control->getKeyVal();
        $this->filter = $key_val;
        return $this;
    }
}

