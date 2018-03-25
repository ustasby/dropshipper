<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ExternalApi\Model\AbstractMethods;
use \ExternalApi\Model\Exception as ApiException;

/**
* Абстрактный класс для получения отфильтрованных списков
*/
abstract class AbstractFilteredList extends AbstractAuthorizedMethod
{
     const     
        /**
        * Право на загрузку списка объектов
        */
        RIGHT_LOAD = 1,
        
        /**
        * Тип фильтра - полное соответствие
        */
        FILTER_TYPE_EQ    = 'eq',
        FILTER_TYPE_LIKE  = 'like',
        FILTER_TYPE_IN    = 'in';

    /**
     * @var \RS\Module\AbstractModel\EntityList $dao
     */
    protected $dao;
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return array(
            self::RIGHT_LOAD => t('Загрузка списка объектов')
        );
    }   
    
    /**
    * Возвращает возможный ключи для фильтров
    * 
    * @return [
    *   'поле' => [
    *       'title' => 'Описание поля. Если не указано, будет загружено описание из ORM Объекта'
    *       'type' => 'тип значения',
    *       'func' => 'постфикс для функции makeFilter в текущем классе, которая будет готовить фильтр, например eq',
    *       'values' => [возможное значение1, возможное значение2]
    *   ]
    * ]
    */
    public function getAllowableFilterKeys()
    {
        return array();
    }

    /**
     * Возвращает условия для фильтра
     *
     * @param array $filters - весь список фильтров
     * @return array
     * @throws ApiException
     */
    protected function makeFilter($filters)
    {
        $result = array();
        $allowable_keys = $this->getAllowableFilterKeys();
        
        
        foreach($filters as $key => $filter) {
            if (!isset($allowable_keys[$key])) {
                throw new ApiException(t('Неверный параметр фильтра: %0', array($key)), ApiException::ERROR_WRONG_PARAM_VALUE);
            }
            
            if (isset($allowable_keys[$key]['values']) && !in_array($filter, $allowable_keys[$key]['values'])) {
                throw new ApiException(t('Неверное значение параметра фильтра %0. Допускаются только значения: %1', 
                            array($key, implode(',', $allowable_keys[$key]['values']))), ApiException::ERROR_WRONG_PARAM_VALUE);
            }
            
            $func = 'makeFilter'.$allowable_keys[$key]['func'];
            $result = array_merge($result, $this->$func($key, $filter, $filters, $allowable_keys[$key]));
        }
        return $result;
    }

    /**
     * Возвращает готовое условие для установки фильтра. (Тип фильтра - полное соответствие(равно))
     *
     * @param string $key - поле фильтрации
     * @param mixed $value - значение фильтра
     * @param array $filters - весь список фильтров
     * @param array $filter_settings - параметры фильтра
     * @return array
     */
    protected function makeFilterEq($key, $value, $filters, $filter_settings)
    {
        settype($value, $filter_settings['type']);
        return array($key => $value);
    }
    
    /**
    * Возвращает готовое условие для установки фильтра. (Тип фильтра - частичное совпадение %like%)
    * 
    * @param string $key - поле фильтрации
    * @param mixed $value - значение фильтра
    * @param array $filters - весь список фильтров
    * @param array $filter_settings - параметры фильтра,
    * @return array
    */
    protected function makeFilterLike($key, $value, $filters, $filter_settings)
    {
        $like_mask = isset($filter_settings['like_mask']) ? $filter_settings['like_mask'] : '%like%';
        
        return array(
            "$key:$like_mask" => (string)$value
        );
    }
    
    /**
    * Возвращает готовое условие для установки фильтра. (Тип фильтра - поиск через ИЛИ)
    * 
    * @param string $key - поле фильтрации
    * @param mixed $value - значение фильтра
    * @param array $filters - весь список фильтров
    * @param array $filter_settings - параметры фильтра,
    * @return array
    */
    protected function makeFilterIn($key, $value, $filters, $filter_settings)
    {
        $value = array_map(function($val) use ($filter_settings) {
                                settype($val, str_replace('[]', '', $filter_settings['type']));
                                return $val;
                           }, 
                           (array)$value);
        
        if ($value) {            
            return array(
                "$key:in" => implode(',', \RS\Helper\Tools::arrayQuote($value))
            );
        } else {
            return array();
        }
    }
    
    /**
    * Возвращает возможные значения для сортировки
    * 
    * @return array
    */
    public function getAllowableOrderValues()
    {
        return array('id', 'id desc');
    }


    /**
     * Проверяет условие для сортировки
     *
     * @param string $order - направление сортирвки
     *
     * @return string
     * @throws ApiException
     */
    protected function makeOrder($order)
    {
        
        if (in_array(mb_strtolower($order), $this->getAllowableOrderValues())) {
            return $order;
        }
        
        throw new ApiException(t('Неверное значение сортировки: %0', array($order)), ApiException::ERROR_WRONG_PARAM_VALUE);
    }
    
    /**
    * Возвращает объект выборки объектов 
    * 
    * @return \RS\Module\AbstractModel\EntityList
    */
    abstract public function getDaoObject();
    
    /**
    * Возвращает название секции ответа, в которой должен вернуться список объектов
    * 
    * @return string
    */
    public function getObjectSectionName()
    {
        return 'list';
    }


    /**
     * Устанавливает фильтр для выборки
     *
     * @param \RS\Module\AbstractModel\EntityList $dao
     * @param array $filter
     *
     * @throws ApiException
     */
    public function setFilter($dao, $filter)
    {
        $dao->setFilter($this->makeFilter($filter));
    }

    /**
     * Устанавливает сортировку для выборки
     *
     * @param \RS\Module\AbstractModel\EntityList $dao
     * @param string $order - предложенная сортировка
     * @throws ApiException
     */
    public function setOrder($dao, $order)    
    {
        $dao->setOrder($this->makeOrder($order));
    }
    
    /**
    * Возвращает общее число объектов для данной выборки
    * 
    * @param \RS\Module\AbstractModel\EntityList $dao
    * @return integer
    */
    public function getResultCount($dao)
    {
        return $dao->getListCount();
    }
    
    /**
    * Форматирует комментарий, полученный из PHPDoc
    * 
    * @param string $text - комментарий
    * @return string
    */
    protected function prepareDocComment($text, $lang)
    {
        $text = parent::prepareDocComment($text, $lang);
        $text = preg_replace_callback('/\#filters-info/', array($this, 'prepareFilterInfo'), $text);
        $text = preg_replace_callback('/\#sort-info/', array($this, 'prepareSortInfo'), $text);
        
        return $text;
    }

    /**
     * Возвращает информацию по возможным ключам фильтра,
     * основываясь на результате функции $this->getAllowableFilterKeys()
     *
     * @return string готовый HTML код
     * @throws \Exception
     * @throws \SmartyException
     */
    protected function prepareFilterInfo()
    {
        $view = new \RS\View\Engine();
        $view->assign(array(
            'orm_object' => $this->getDaoObject()->getElement(),
            'filters' => $this->getAllowableFilterKeys()
        ));
        
        return $view->fetch('%externalapi%/filter_info.tpl');
    }

    /**
     * Возвращает информацию о возможных способах сортировки,
     * основываясь на результатах функции $this->getAllowableOrderValues()
     *
     * @return string готовый HTML код
     * @throws \Exception
     * @throws \SmartyException
     */
    protected function prepareSortInfo()
    {
        $view = new \RS\View\Engine();
        $view->assign(array(
            'sort_fields' => $this->getAllowableOrderValues()
        ));
        
        return $view->fetch('%externalapi%/sort_info.tpl');
    }
}
