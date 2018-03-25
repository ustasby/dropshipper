<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Templates\Model;

/**
* Апи блочного контроллера вставленного через moduleinsert в шаблоне 
* @ingroup Templates
*/
class TemplateModuleApi{
    
    /**
    * Возвращает полное имя класса контроллера по маршруту
    * 
    * @param string $block_url
    * @return string
    */
    function getBlockClassByUrlName($block_url)
    {  
       //Генерируем имя класса контроллера
       if (preg_match('/^([^\-]+?)\-(.*)$/', $block_url, $match)) 
       {
            return str_replace('-','\\', "-{$match[1]}-controller-{$match[2]}");
       }
       return '';
    }
    
    /**
    * Возращает блок с классом по его id в кэше
    * Если блока не находит, возражает false
    * 
    * @param integer $cache_id - id в кэше блоков
    * @param string $url_name  - сокращённое url имя блока
    * 
    * @return object|false
    */
    function getBlockFromCache($cache_id, $url_name)
    {
       $block_class = $this->getBlockClassByUrlName($url_name); //Получим класс
       
       // параметры пришли, то обработаем
       if ($cache_id && !empty($block_class)){
           $block = new $block_class(array(
                '_block_id' => $cache_id,
           ));
           
           return $block;
       }
        
       return false; 
    }
    
    function arrayExport($array)
    {
        $line = '[';
        $i = 0;
        foreach($array as $key => $value) {
            if ($i > 0) $line .= ',';
            if (is_array($value)) {
                $value = $this->arrayExport($value);
            } else {
                $value = var_export($value, true);
            }
            $line .= var_export($key, true).' => '.$value;
            $i++;
        }
        $line .= ']';
        return $line;
    }
    
    /**
    * Склеивает массив вместе с ключами
    * 
    * @param array $params - массив с параметрами и значениями
    * @param string $sign - разделитель между ключем и значением
    * @param string $glue - разделитель между парами ключ=>значение
    * @return string
    */
    function glueParams($params, $sign = "=", $glue = " "){
       $str = array();
       if (!empty($params)){
           foreach ($params as $key=>$value){
               if (is_array($value)) {
                   $value = $this->arrayExport($value);
               } else {
                   $value = var_export($value, true);
               } 
               $str[] = $key.$sign.$value;
           }
           $str = implode($glue,$str);
       }
       return $str; 
    }
    
    /**
    * Заменяет в шаблоне вставку moduleinsert подставляя нужные значения
    * 
    * @param array $search_info - информация для поиска вставки moduleinsert в шаблоне, 
    * ключи 
    * 
    * num  - порядковый номер в шаблоне с 1-цы
    * tpl  - шаблон для замены
    * name - параметр name для шаблона, например Catalog\Controller\Block\Category
    * 
    * @param array $params      - массив значений из которых будет собрана конструкция moduleinsert
    * @return void
    */
    function replaceParamsInTemplateByModule($search_info,$params){ 
        $search_info['params']      = $this->glueParams($params);
        $search_info['encode_name'] = "(\\\\)?".str_replace("\\","\\\\",$search_info['name']); //Обезопасим имя класса
        $content = file_get_contents($search_info['tpl']);
        
        $search_info['cnt']         = 0; //Счётчик количества раз найденных совпадений
        $content = preg_replace_callback('/\{moduleinsert([^}]*?)name=["\'](\s+)?'.$search_info['encode_name'].'(\s+)?["\']([^}]*?)\}/usim',function($mathes) use (&$search_info){
            $str                = $mathes[0];
            $search_info['cnt'] = $search_info['cnt']+1; 
            
            if ($search_info['cnt'] == $search_info['num']){
               $str = '{moduleinsert name="\\'.$search_info['name'].'" '.$search_info['params'].'}'; 
            }
           
           return $str; 
        } ,$content);
        

        file_put_contents($search_info['tpl'],$content);
    }
    
    /**
    * Сохраняет новые значения блока в шаблоне
    * 
    * @param \RS\Controller\StandartBlock $block   - блочный контроллер из кэша 
    * @param \RS\Orm\ControllerParamObject $values - значения параметров 
    */
    function saveBlockValues($block,$values)
    {
       $params = array();
       $store_params = $block->getStoreParams(); //Ключи параметров которые должны быть подставлены
       
       //Получаем параметры со значениями для замены 
       if (!empty($store_params)){  
          $values = $values->getValues(); 
          foreach($store_params as $key){
             $value = $values[$key]; 
             if (($value!==false) && ($value!==null)){
                 $params[$key] = $value; 
             }     
          } 
       }
       
       //Данные для поиска в шаблоне
       $module_info['name'] = get_class($block);                                        //Имя класса, блока
       $module_info['num']  = $block->getParam(\RS\Controller\Block::BLOCK_NUM_PARAM);  //Позиция, начиная с 1-цы
       $module_info['tpl']  = $block->getParam(\RS\Controller\Block::BLOCK_PATH_PARAM); //Сам шаблон
       
       if ($module_info['tpl'] && $module_info['num'] && $module_info['name']){ //Поиск если все параметры есть
           $this->replaceParamsInTemplateByModule($module_info,$params);
       }
    }
}

