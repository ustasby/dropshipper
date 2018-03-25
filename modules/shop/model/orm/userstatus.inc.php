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
* Пльзовательский статус заказа
*/
class UserStatus extends \RS\Orm\OrmObject
{
    const
        STATUS_NEW         = 'new',
        STATUS_WAITFORPAY  = 'waitforpay',
        STATUS_INPROGRESS  = 'inprogress',
        STATUS_NEEDRECEIPT = 'needreceipt', //Особый статус для выбивания чека
        STATUS_SUCCESS     = 'success',
        STATUS_CANCELLED   = 'cancelled',
        
        STATUS_USER = 'other';
    
    protected static
        $table = 'order_userstatus',
        $sort  = array(
            self::STATUS_NEW, 
            self::STATUS_WAITFORPAY, 
            self::STATUS_INPROGRESS, 
            self::STATUS_NEEDRECEIPT, 
            self::STATUS_USER, 
            self::STATUS_SUCCESS, 
            self::STATUS_CANCELLED);
    
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'description' => t('Статус')
            )),
            'parent_id' => new Type\Integer(array(
                'description' => t('Родитель'),
                'list' => array(array('\Shop\Model\UserStatusApi', 'staticRootList')),
                'default' => 0,
                'allowEmpty' => false,
            )),
            'bgcolor' => new Type\Color(array(
                'maxLength' => 7,
                'description' => t('Цвет фона')
            )),
            'type' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Идентификатор(Англ.яз)')
            )),
            'copy_type' => new Type\Varchar(array(
                'maxLength' => 20,
                'description' => t('Дублирует системный статус'),
                'hint' => t('Данный статус будет дублировать поведение выбранного системного статуса'),
                'list' => array(array(__CLASS__, 'getDefaultStatusesTitles'), array('' => t('- Не выбрано -'))),
                'visible' => false,
                'otherVisible' => true
            )),
            'is_system' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Это системный статус. (его нельзя удалять)'),
                'visible' => false,
                'listenPost' => false,
                'default' => 0,
                'allowEmpty' => false
            )),
        ));
        $this->addIndex(array('site_id', 'type'), self::INDEX_UNIQUE);
        
    }
    
    /**
    * Функция срабатывает перед записью объекта
    * 
    * @param string $flag - insert или update флаг текущего действия
    * @return void
    */
    function beforeWrite($flag)
    {
        if ($flag == self::UPDATE_FLAG){ //Если обновление 
            //Проверим на зарезервированные alias статусов. Исключим подмену alias
            $old_status = new \Shop\Model\Orm\UserStatus($this['id']);
            
            if (in_array($old_status['type'],self::$sort)){
                unset($this['type']);
            }
        }
        // Проверка и корректировка parent_id
        if ($this['parent_id'] != 0) {
            $parent = new \Shop\Model\Orm\UserStatus($this['parent_id']);
            if (empty($parent['id']) || $this['id'] == $parent['id']) {
                $this['parent_id'] = 0;
            } else {
                if ($parent['parent_id'] != 0) {
                    $this['parent_id'] = $parent['parent_id'];
                }
                \RS\Orm\Request::make()
                    ->update(new \Shop\Model\Orm\UserStatus)
                    ->set(array('parent_id' => $this['parent_id']))
                    ->where(array('parent_id' => $this['id']))
                    ->exec();
            }
        }
    }
    
    /**
    * Добавляет в базу данных стандартные статусы
    * 
    * @param integer $site_id - ID сайта, на котором небходимо добавить статусы
    * @return void
    */
    public static function insertDefaultStatuses($site_id)
    {
        $default_names = self::getDefaultStatues();
        $assoc = array();
        foreach($default_names as $type => $data) {
            $record = new self();
            $record->getFromArray($data);
            $record['site_id'] = $site_id;
            $record['type'] = $type;
            $record['is_system'] = 1;
            $record->insert();
            $assoc[$type] = $record['id'];
        }
        //Устанавливаем в настройки модуля статус заказа по умолчанию "Ожидает оплаты"
        $config = \RS\Config\Loader::byModule('shop', $site_id);
        $config['first_order_status'] = $assoc[self::STATUS_WAITFORPAY];
        $config->update();
    }
    
    /**
    * Возвращает статусы по умолчанию
    * 
    * @return array
    */
    public static function getDefaultStatues()
    {
        return array(
            self::STATUS_NEW => array(
                'title' => t('Новый'),
                'bgcolor' => '#83b7b3'
            ),
            self::STATUS_WAITFORPAY => array(
                'title' => t('Ожидает оплату'),
                'bgcolor' => '#687482'
            ),
            self::STATUS_INPROGRESS => array(
                'title' => t('В обработке'),
                'bgcolor' => '#f2aa17'
            ),
            self::STATUS_NEEDRECEIPT => array(
                'title' => t('Ожидание чека'),
                'bgcolor' => '#808000'
            ),
            self::STATUS_SUCCESS => array(
                'title' => t('Выполнен и закрыт'),
                'bgcolor' => '#5f8456'
            ),
            self::STATUS_CANCELLED => array(
                'title' => t('Отменен'),
                'bgcolor' => '#ef533a'
            )
        );
    }
    
    /**
    * Возвращает ассоциативный массив со статусами по-умолчанию
    * 
    * @return array
    */
    public static function getDefaultStatusesTitles(array $first_element = null)
    {
        $result = array();
        foreach (self::getDefaultStatues() as $key => $data) {
            $result[$key] = $data['title'];
        }
        return ($first_element ?: array()) + $result;
    }
    
    /**
    * Возвращает массив с порядком статусов
    * 
    * @return array
    */
    public static function getStatusesSort()
    {
        return self::$sort;
    }  
    
    /**
    * Возвращает true, если статус является системным, иначе - false
    * 
    * @return bool
    */
    function isSystem()
    {
        static 
            $defaults;
            
        if ($defaults === null) $defaults = self::getDefaultStatues();
        return isset($defaults[$this['type']]);
    }
    
    /**
    * Возвращаетколичество заказов для текущего статуса
    * 
    * @return integer
    */
    function getOrdersCount($cache = true)
    {
        static $cache_taxonomy;
        if (!$cache || $cache_taxonomy === null) {
            $cache_taxonomy = $this->getTaxonomy();
        }
        
        return isset($cache_taxonomy[$this['id']]) ? $cache_taxonomy[$this['id']] : 0;
    }
    
    /**
    * Возвращает количество заказов для каждого статуса
    * 
    * @return array
    */
    private function getTaxonomy()
    {
        $count_by_status = \RS\Orm\Request::make()
            ->select('status, COUNT(*) as cnt')
            ->from(new \Shop\Model\Orm\Order())
            ->where(array('site_id' => \RS\Site\Manager::getSiteId()))
            ->groupby('status')
            ->exec()->fetchSelected('status', 'cnt');
        
        $count_by_status[0] = 0;
        foreach($count_by_status as $value) {
            $count_by_status[0] += $value;
        }
        return $count_by_status;
    }
}
