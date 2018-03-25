<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;
use \RS\Orm\Type;

/**
* Предварительный заказ товара
*/
class Reservation extends \RS\Orm\OrmObject
{
    const
        STATUS_OPEN = 'open',
        STATUS_CLOSE = 'close';
    
    protected static
        $table = 'product_reservation';
        
    protected
        $product;
        
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'product_id' => new Type\Integer(array(
                'description' => t('ID товара'),
                'checker' => array('chkEmpty', t('Не задан товар')),
                'meVisible' => false
            )),
            'product_barcode' => new Type\Integer(array(
                'description' => t('Артикул товара'),
                'meVisible' => false
            )),
            'product_title' => new Type\Varchar(array(
                'description' => t('Название товара'),
                'meVisible' => false
            )),
            'offer' => new Type\Varchar(array(
                'description' => t('Название комплектации товара'), 
                'visible' => false
            )),
            'offer_id' => new Type\Integer(array(
                'description' => t('Комплектация товара'), 
                'Template' => 'form/reservationfield/offer_id.tpl',
                'meVisible' => false
            )),
            'currency' => new Type\Varchar(array(
                'description' => t('Валюта на момент оформления заявки')
            )),
            'multioffer' => new Type\Varchar(array(
                'description' => t('Многомерная комплектация товара'),
                'Template' => 'form/reservationfield/multioffer.tpl',
                'meVisible' => false
            )),
            'multioffers' => new Type\ArrayList(array(
                'descirption' => t('Многомерная комплектация товара - массив'),
                'visible' => false
            )),
            'amount' => new Type\Decimal(array(
                'description' => t('Количество'),
                'maxLength' => 11,
                'decimal' => 3,
                'meVisible' => false
            )),
            'phone' => new Type\Varchar(array(
                'maxLength' => 50,
                'description' => t('Телефон пользователя')
            )),
            'email' => new Type\Varchar(array(
                'description' => t('E-mail пользователя'),
                'checker' => array(array(__CLASS__, 'checkContacts'), t('Укажите телефон или E-mail'))
            )),
            'is_notify' => new Type\Enum(array('1', '0'), array(
                'allowempty' => false,
                'default' => '0',
                'description' => t('Уведомлять о поступлении на склад'),
                'listFromArray' => array(array(
                    '1' => t('Уведомлять'),
                    '0' => t('Не уведомлять')
                ))
            )),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата заказа')
            )),
            'user_id' => new Type\User(array(
                'description' => t('ID пользователя'),
                'meVisible' => false
            )),
            'kaptcha' => new Type\Captcha(array(
                'description' => t('Защитный код'),
                'enable' => false,
                'context' => '',
            )),
            'status' => new Type\Enum(array(self::STATUS_OPEN, self::STATUS_CLOSE), array(
                'allowempty' => false,
                'description' => t('Статус'),
                'listFromArray' => array(array(
                    self::STATUS_OPEN => t('открыт'),
                    self::STATUS_CLOSE => t('закрыт')
                ))
            )),
            'comment' => new Type\Text(array(
                'description' => t('Комментарий администратора')
            ))
        ));
    }    
    
    public static function checkContacts($_this, $value, $error_text)
    {
        if ($_this['phone'] || $_this['email']) return true;
        return $error_text;
    }

    /**
     * Действия перед записью в БД
     *
     * @param string $flag - insert или update
     * @return void
     */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $this['dateof'] = date('c');
            $this['user_id'] = \RS\Application\Auth::getCurrentUser()->id;
        }
        
        $this['multioffer'] = serialize($this['multioffers']);
        $this['product_title'] = $this->getProduct()->title;
    }

    /**
     * Действия после записи в БД
     *
     * @param string $flag - insert или update
     * @return void
     */
    function afterWrite($flag)
    {
        if ($flag == self::INSERT_FLAG) {
            $notice = new \Shop\Model\Notice\Reservation;
            $notice->init($this);
            \Alerts\Model\Manager::send($notice); 
        }        
    }
    
    function afterObjectLoad()
    {
        $this['multioffers'] = @unserialize($this['multioffer']);
        
        // Приведение типов
        $this['amount'] = (float)$this['amount'];
    }
    
    function getProduct()
    {
        if (empty($this->product)){
          $this->product =  new \Catalog\Model\Orm\Product($this['product_id']);
        }   
        return $this->product;
    }
    
    /**
    * Возращает массив мульти комплектаций
    * 
    */
    function getArrayMultiOffer()
    {
       $arr = array();
       if (!empty($this['multioffers'])){
          foreach ($this['multioffers'] as $offer){
              $parse = explode(":",$offer);
              $arr[trim($parse[0])] = trim($parse[1]);
          } 
       }
       
       return $arr;
    }

    /**
     * Возвращает комплектацию товара
     *
     * @return \Catalog\Model\Orm\Offer
     */
    function getOffer()
    {
        $offer = new \Catalog\Model\Orm\Offer($this['offer_id']);
        return $offer;
    }
}
