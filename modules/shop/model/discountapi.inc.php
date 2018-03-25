<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;

class DiscountApi extends \RS\Module\AbstractModel\EntityList
{
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Discount,
        array(
            'multisite' => true
        ));
    }
    
    /**
    * Приводит код купона к виду, в которм он хранится в базе
    * 
    * @param string $code
    * @return string
    */
    public static function normalizeCode($code)
    {
        return str_replace('-', '', strtolower($code));
    }
    
    /**
    * Сохраняет купон в базе данных
    * 
    * @param integer|null $id - id купона. если задан будет update иначе insert
    * @param array $user_post - добавить к посту
    * @return bool
    */
    function save($id = null, $user_post = array())
    {
        $result = parent::save($id, $user_post);
        
        if ($id === null && $result && $this->obj_instance['makecount']>1)
        { //Если нужно создать несколько купонов
            $yet = $this->obj_instance['makecount']-1;
            for($i=0; $i<$yet; $i++) {
                $this->obj_instance['id'] = null;
                $this->obj_instance['code'] = $this->obj_instance->generateCode();
                $result = $result && $this->obj_instance->insert();
            }
        }
        return $result;
    }    
    
    /**
    * Сохраняет купон при мультиредактировании
    * 
    * @param array $data - ассоциативный массив с изменяемыми сведеними
    * @param array $ids
    */
    function multiUpdate(array $data, $ids = array())
    {
        if (isset($data['products'])) {
            $data['sproducts'] = serialize($data['products']);
            unset($data['products']);
        }
        return parent::multiUpdate($data, $ids);
    }
    
}
?>
