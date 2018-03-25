<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\DeliveryType;
use \RS\Orm\Type;

class Universal extends AbstractType implements 
                                            \Shop\Model\DeliveryType\InterfaceIonicMobile
{
    const RULE_TYPE_WEIGHT  = 'weight';
    const RULE_TYPE_SUM     = 'sum';
    
    const ACTION_TYPE_FIXED             = 'fixed';
    const ACTION_TYPE_ORDER_PERCENT     = 'order_percent';
    const ACTION_TYPE_DELIVERY_PERCENT  = 'delivery_percent';
        
    /**
    * Возвращает название расчетного модуля (типа доставки)
    * 
    * @return string
    */
    function getTitle()
    {
        return t('Универсальная');
    }
    
    /**
    * Возвращает описание типа доставки
    * 
    * @return string
    */
    function getDescription()
    {
        return t('Универсальная доставка');
    }
    
    /**
    * Возвращает идентификатор данного типа доставки. (только англ. буквы)
    * 
    * @return string
    */
    function getShortName()
    {
        return 'universal';
    }
    
    /**
    * Возвращает ORM объект для генерации формы или null
    * 
    * @return \RS\Orm\FormObject | null
    */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            'max_weight' => new Type\Varchar(array(
                'description' => t('Максимальный вес, грамм'),
            )),
        ) );
        
        return new \RS\Orm\FormObject($properties);
    } 
    
    /**
    * Возвращает HTML форму данного типа доставки
    * 
    * @return string
    */
    function getFormHtml()
    {
        $view = new \RS\View\Engine();
        $view->assign(\RS\Module\Item::getResourceFolders('shop'));
        $view->assign('zones', array('all' => t(' - все - ')) + \Shop\Model\ZoneApi::staticSelectList());
        $view->assign('ruletypes', array(
            self::RULE_TYPE_WEIGHT    => t('Вес, грамм'),
            self::RULE_TYPE_SUM       => t('Сумма заказа'),
        ));
        $view->assign('actiontypes', array(
            self::ACTION_TYPE_FIXED             => t('Фиксированная'),
            self::ACTION_TYPE_ORDER_PERCENT     => t('Процент от стоимости заказа'),
            self::ACTION_TYPE_DELIVERY_PERCENT  => t('Процент от стоимости доставки'),
        ));
        $view->assign('data', $this->opt);
        return parent::getFormHtml().$view->fetch('%shop%/form/delivery/delivery_rules_tr.tpl');
    } 
    
    /**
    * Производит валидацию текущих данных в свойствах
    */
    function validate(\Shop\Model\Orm\Delivery $delivery)
    {
        $rules = $this->getOption('rules');
        $rules = (array)$rules;
        // Проверка корректности формул
        foreach($rules as $one){
            try{
                $formula_vars = array(
                    'S' => 1000,
                    'W' => 1000,
                );
                $this->formulaEval($one['value'], $formula_vars);
            }
            catch(\Exception $e){
                $delivery->addError($e->getMessage());
            }
        }
    }
    
    /**
    * Получение значения формулы
    * 
    * @param mixed $formula
    * @param mixed $formula_vars
    */
    private function formulaEval($formula, array $formula_vars = array())
    {
        extract($formula_vars);
        $current_reporting_level = error_reporting();
        error_reporting(E_ALL);
        ob_start();
        $value = eval('return '.$formula.';');
        $output = ob_get_clean();
        error_reporting($current_reporting_level);
        if($value === false || $output){
            throw new \Exception(t('Ошибка в формуле <big><b>%0</b></big>', array($formula)));
        }
        return $value;
    }
    
    
    /**
    * Возвращает текст, в случае если доставка невозможна. false - в случае если доставка возможна
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @return mixed
    */
    function somethingWrong(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null)
    {
        // Проверка веса посылки
        if($this->getOption('max_weight') && $this->getOption('max_weight') < $order->getWeight()) {
            return t('Превышен максимально допустимый вес отправления');
        }
    }
    
    /**
    * Применение одного правила. Возвращает сумму
    * 
    * @param \Shop\Model\Orm\Order $order
    * @param \Shop\Model\Orm\Address $address
    * @param mixed $rule
    * @param mixed $last_delivery_cost
    * @param bool $is_matched
    * @return double
    */
    private function applyRule(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address, $rule, $last_delivery_cost, &$is_matched)
    {
        $is_matched = false;

        // Если это правило для какой-то конкретной зоны
        if (isset($rule['zone'])){
            if($rule['zone'] != 'all') {
                // Если у адреса не указан регион
                if(!$address->region_id){
                    return 0;
                }
                $order_zones = \Shop\Model\ZoneApi::getZonesByRegionId($address->region_id, $address->country_id, $address->city_id);

                // Если данное правило не совпадает с зоной доставки
                if($rule['zone'] != 'all' && !in_array($rule['zone'], $order_zones)){
                    return 0;
                }
            }
        }else{
            return 0;
        }
        
        $order_price_without_delivery = $order->getCart()->getTotalWithoutDelivery();
        $examining_value = 0;
        
        // Определяем, какое из значений заказа будет использовано в качестве условия применения данного правила
        switch($rule['ruletype']) {
            case self::RULE_TYPE_SUM: 
                $examining_value = $order_price_without_delivery;          // Сумма заказа 
                break;
                
            case self::RULE_TYPE_WEIGHT: 
                $examining_value = $order->getWeight();                    // Вес заказа
                break;
            
            default:
                throw new \Exception(t('Неизвестный тип правила %ruletype', $rule));
        }
        
        $formula_vars = array(
            'S' => $order_price_without_delivery,
            'W' => $order->getWeight(),
        );
        $value = $this->formulaEval($rule['value'], $formula_vars);
        
        // Если экзаменуемое значение находится в пределах, заданных данным правилом
        if($examining_value >= $rule['from'] && $examining_value <= $rule['to']) {
            $is_matched = true;

            switch($rule['actiontype']) {
                case self::ACTION_TYPE_FIXED:
                    return $value;
                case self::ACTION_TYPE_ORDER_PERCENT:
                    return ($order_price_without_delivery * $value)/100;
                case self::ACTION_TYPE_DELIVERY_PERCENT:
                    return ($last_delivery_cost * $value)/100;
                default:
                    throw new \Exception(t('Неизвестный тип надбавки %actiontype', $rule));
            }
        }
    }  

    /**
    * Возвращает стоимость доставки для заданного заказа. Только число.
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    * @param boolean $use_currency - Привязывать валюту?
    * @return double
    */
    function getDeliveryCost(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery, $use_currency = true)
    {
        if(!$address) { 
            $address = $order->getAddress();
        }
        $rules = $this->getOption('rules');
        $last_delivery_cost = 0;
        if (!empty($rules)) {
           foreach($rules as $one_rule) {
               $is_matched = false;
               $last_delivery_cost += $this->applyRule($order, $address, $one_rule, $last_delivery_cost, $is_matched);

               //Прерываем перебор остальных правил, если установлен соответствующий флаг у правила и правило сработало
               if ($is_matched && !empty($one_rule['interrupt'])) break;
           } 
        }
        $last_delivery_cost = $this->applyExtraChangeDiscount($delivery, $last_delivery_cost); //Добавим наценку или скидку 
        if ($use_currency){
            $last_delivery_cost = $order->applyMyCurrency($last_delivery_cost);
        }
        return $last_delivery_cost;
    }
    
    /**
    * Возвращает дополнительную информацию о доставке (например сроки достави)
    * 
    * @param \Shop\Model\Orm\Order $order - объект текущего заказа
    * @param \Shop\Model\Orm\Address $address - Адрес доставки
    * @param \Shop\Model\Orm\Delivery $delivery - Объект способа доставки
    * @return string
    */
    function getDeliveryExtraText(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Address $address = null, \Shop\Model\Orm\Delivery $delivery = null)
    {
        return $this->getOption('data');
    }
    
    /**
    * Возвращает HTML для приложения на Ionic
    * 
    * @param \Shop\Model\Orm\Order $order - объект заказа
    * @param \Shop\Model\Orm\Delivery $delivery - объект доставки
    */
    function getIonicMobileAdditionalHTML(\Shop\Model\Orm\Order $order, \Shop\Model\Orm\Delivery $delivery)
    {
        return "";    
    }
}