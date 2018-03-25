<?php
namespace SeoControl\Model\SeoReplace;

/**
* Класс сео генератора для правил СЕО-контроля
*/
class SeoRule extends \RS\Helper\AbstractSeoGen
{
    public 
        $hint_fields   = array(    //Массив имён свойств которым будет обновлена подсказка (hint)
            'meta_title',
            'meta_keywords',
            'meta_description',
        );  
    
    /**
    * Конструктор класса
    * 
    * @param array $real_replace - массив автозамены, 
    * в котором ключи которые совпадают с внутренним 
    * массивом struct будут заменены.
    * использоватся будет массив struct описанный внутри 
    * этой функции
    * 
    * @return \Catalog\Model\SeoReplace\Product
    */
    function __construct(array $real_replace = array())
    {
        $this->struct = array();                        //Структурный массив автозамены
        
        $this->include_array  = array(); //Массив с ключами включения, какие элементы в массиве автозамены участвуют
        
        // Если есть партнёрский модуль
        if (\RS\Module\Manager::staticModuleExists('partnership') && \RS\Module\Manager::staticModuleEnabled('partnership')) {
            $this->struct['partner_'] = new \Partnership\Model\Orm\Partner();
            $this->include_array[] = 'partner_title';
            $real_replace['partner_'] = \Partnership\Model\Api::getCurrentPartner();
        }
        
        parent::__construct($real_replace);
    }
}