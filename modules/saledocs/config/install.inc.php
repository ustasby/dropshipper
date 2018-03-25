<?php
namespace SaleDocs\Config;
use \RS\Cache\Cleaner as CacheCleaner;

class Install extends \RS\Module\AbstractInstall
{
    function update()
    {
        if ($result = parent::update()) {
            //Очищаем загруженные раннее описания полей объектов
            CacheCleaner::obj()->clean( CacheCleaner::CACHE_TYPE_COMMON );
            \RS\Event\Manager::init();
            \Users\Model\Orm\User::destroyClass();
            \Site\Model\Orm\Config::destroyClass();
            
            //Обновляем структуру базы данных
            $user = new \Users\Model\Orm\User();
            $config = new \Site\Model\Orm\Config();
            $user->dbUpdate();            
            $config->dbUpdate();
        }
        return $result;
    }
    
}
?>
