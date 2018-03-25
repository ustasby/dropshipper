<?php

/**
* Плагин смарти для вставки контроллера
* 
* @param array $params                      - параметры
* @param Smarty_Internal_Template $template - объект шаблона
* @param string $filepath                   - прямой путь к шаблону
*/
function smarty_function_moduleinsert($params, $template, $filepath = null)
{
    static $block_iterator = array();
    
    if (!isset($params['name'])) {
        trigger_error("moduleinsert: param 'name' not found", E_USER_NOTICE);
        return;
    }    
    
    if (isset($params['_params_array'])) { //Для загрузки параметров из массива
        $params += $params['_params_array'];
        unset($params['_params_array']);
    }
    
    //Формируем _block_id
    if (!isset($params[\RS\Controller\Block::BLOCK_ID_PARAM])) {
        if (!isset($block_iterator[$filepath.$params['name']])) {
            $block_iterator[$filepath.$params['name']] = 1;
        } else {
            $block_iterator[$filepath.$params['name']]++;
        }
        //принимаем за block_id - полный путь к шаблону и порядковый номер блока в шаблоне
        $params[\RS\Controller\Block::BLOCK_ID_PARAM]   = $filepath."_".$params['name']."_".$block_iterator[$filepath.$params['name']];
        $params[\RS\Controller\Block::BLOCK_PATH_PARAM] = $filepath;
        $params[\RS\Controller\Block::BLOCK_NUM_PARAM]  = $block_iterator[$filepath.$params['name']];
    }
    
    
    //Флаг о том, что блок был вставлен в шаблоне вручную, для подгрузки редактирования
    if (empty($params['generate_by_grid'])) {
        $params['generate_by_template'] = 1; 
    }
    
    //Записывам переменные, которые присутствовали в модуле в шаблон
    if (!empty($params['var'])) {
        $need_assign_var = $params['var'];
        unset($params['var']);
    }

    $mod_param = $params;
    unset($mod_param['name']);    
    
    $vars = array();    
    $result = \RS\Application\Block\Template::insert($params['name'], $mod_param, $vars);
    
    if (isset($need_assign_var)) {
        $template->assign($need_assign_var, $vars);
    }
    return $result;
}

