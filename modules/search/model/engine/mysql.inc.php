<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Search\Model\Engine;

/**
* Полнотекстовый поиск средствами Mysql
*/
class Mysql extends AbstractEngine
{          
    /**
    * Возвращает название поискового сервиса
    * 
    * @return string
    */
    public function getTitle()
    {
        return t('MySQL');
    }
    
    /**
    * Возвращает поисковый запрос, подготовленный для использования в выражении like
    * 
    * @return string
    */
    protected function getQueryForLike()
    {
        $stemmer = new \Search\Model\Stem\Ru();
        return '%'.$stemmer->stemWord(str_replace('%', '', $this->query)).'%';
    }    
    
    /**
    * Возвращает поисковый запрос в нужной форме для поиска без учета окончаний
    * 
    * @return string
    */
    protected function getStemmedQuery()
    {
        //Если в поисковой строке найдены кавычки,
        //не применяем эвристических методов улучшения результатов.
        //Считаем, что пользователь опытный, сам составляет запрос.
        if (strpos($this->query, "\"") !== false) return $this->query;
        
        
        $words = preg_split('/[\s,]+/u', $this->query, -1, PREG_SPLIT_NO_EMPTY);
        $stemmer = new \Search\Model\Stem\Ru();
        
        $query = $this->query;
        foreach($words as $word) {
            //Если перед словом не будет задан спец-символ, ставим + (слово обязательно должно присутствовать в результате)
            if (!preg_match('/[+\-"~(<>]/', mb_substr($word,0,1))) {
                $query = str_replace($word, '+'.$word, $query);
            }
            
            $stemmed = $stemmer->stemWord($word);
            if (mb_strlen($stemmed)>3) {//Если после стеминга слово не стало менее 4-х символов, то 
                $query = str_replace($word, $stemmed.'*', $query);
            }
        }

        return $query;
    }
    
    /**
    * Добавляет базовое условие поиска к объекту запроса $q
    * 
    * @param \RS\Orm\Request $q
    * @return \RS\Orm\Request
    */
    protected function getBaseRequest($q = null)
    {
        if ($q == null) $q = new \RS\Orm\Request();
            $q->from(new \Search\Model\Orm\Index())->asAlias('A')
            ->where("MATCH(A.`title`, A.`indextext`) AGAINST('#query' IN BOOLEAN MODE)", array(
                'query' => $this->getStemmedQuery()
            ));
        if (!empty($this->filters)) $q->where($this->filters);        
        return $q;
    }
    
    /**
    * Выполняет поиск по заранее заданным параметрам
    * 
    * @return boolean|\Search\Model\Orm\Index[] - если поиск выполнен, в случае ошибки false
    */
    public function search(\RS\Orm\Request $q = null)
    {
        if (empty($this->query)) {
            $this->addError(t('Введите поисковый запрос'));
            return false;
        }
        
        $q = $this->getBaseRequest($q);
        $this->total = $q->count();
        $results = new \RS\Orm\Request();
        if ($this->total) {
            if ($this->page_size) {
                $offset = (($this->page-1)*$this->page_size);
                $q->limit($offset, $this->page_size);
            }
            
            if ($this->order_type == self::ORDER_RELEVANT) {
                $q->select("*, MATCH(A.`title`, A.`indextext`) AGAINST('".\RS\Db\Adapter::escape($this->query)."' IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION) as rank")
                    ->orderby('rank DESC');
            } else {
                $q->orderby($this->order);
            }
            
            $results = $q->objects();
        }                
        return $results;
    }
    
    /**
    * Модифицирует объект запроса $q, добавляя в него условия для поиска
    * 
    * @param \RS\Orm\Request $q - объект запроса
    * @param mixed $alias_product - псевдоним для таблицы товаров
    * @param mixed $alias - псевдоним для индексной таблицы 
    * @return \RS\Orm\Request 
    */
    public function joinQuery(\RS\Orm\Request $q, $alias_product = 'A', $alias = 'B')
    {
        if ($this->config['search_type'] == 'like') {
            //Like поиск
            $q->join(new \Search\Model\Orm\Index(), "$alias.entity_id = $alias_product.id", $alias)
                ->where("($alias.`title` like '#query' OR $alias.`indextext` like '#query'
                         OR $alias.`title` like '#psquery' OR $alias.`indextext` like '#psquery')",
                        array(
                            'query' => $this->getQueryForLike(),
                            'psquery' => $this->puntoSwitcher($this->getQueryForLike())
                        ));
        } 
        elseif ($this->config['search_type'] == 'likeplus') {
            //Like plus поиск
            $q->join(new \Search\Model\Orm\Index(), "$alias.entity_id = $alias_product.id", $alias);
            $likearr = explode(" ", $this->prepareLikePlusString($this->query));
            $likearr_switch = $this->puntoSwitcher($likearr);
            $stemmer = new \Search\Model\Stem\Ru();
            foreach($likearr as $key=>$like){
                 if ($like != ''){
                    $q->where("($alias.`indextext` like '%#term%'
                                OR $alias.`indextext` like '%#switchterm%')",
                                array(
                                    'term' => $stemmer->stemWord($like),
                                    'switchterm' => $stemmer->stemWord($likearr_switch[$key])
                                ));
                 }
            }
        } else {
            //Полнотекстовый поиск
            $q->join(new \Search\Model\Orm\Index(), "$alias.entity_id = $alias_product.id", $alias)
                ->where("MATCH($alias.`title`, $alias.`indextext`) AGAINST('#query' IN BOOLEAN MODE)", array(
                    'query' => $this->getStemmedQuery()
                ));
        }
        
        if ($this->order_type == self::ORDER_RELEVANT && $this->config['search_type'] == 'fulltext') {
            $q->select("MATCH($alias.`title`, $alias.`indextext`) AGAINST('".\RS\Db\Adapter::escape($this->query)."' IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION) as rank")
                ->orderby('rank DESC');
        }
        
        if (!empty($this->filters)) $q->where($this->filters);
        
        return $q;
    }
    
    /**
    * Преобразует индекс для likeplus поиска
    * 
    * @param mixed $search_item
    */
    function onUpdateSearch($search_item)
    {
        if($this->config['search_type'] == 'likeplus'){
            //Объединяет все слова в одну непрерывную строку
            $search_item['indextext'] = str_replace(' ', '', $this->prepareLikePlusString($search_item['title'] . $search_item['indextext']));
        }
    }
    
    /**
    * Возвращает подготовленную для поиска likePlus строку
    * 
    * @param string $query
    * @return string
    */
    protected function prepareLikePlusString($query)
    {
        $dis = array('`','~','!','@','#','$','%','^','&','*','(',')','-','_','=',
                     '+','\\','|','[',']','{','}',';',':','"','\'',',','.','<',
                     '>','/','?','№');
        
        return str_replace($dis, ' ', mb_strtolower($query));
    }
    
    /**
    * Punto switcher для поиска
    * 
    * @param string|array $query
    * @return string|array
    */
    protected function puntoSwitcher($query)
    {
        if(is_array($query)) {
            foreach($query as $key=>$word) {
                $query[$key] = \RS\Helper\Transliteration::puntoSwitchWord($word);
            }
        } else {
            $query = \RS\Helper\Transliteration::puntoSwitchWord($query);
        }
        return $query;
    }
}