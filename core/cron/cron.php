<?php
/**
* Этот файл должен быть поставлен в крон для запуска ежеминутно
*/
use RS\Cron\Manager as CronManager;

$readyscript_root = realpath(__DIR__.'/../../');

//Подключаем файл с локальными настройками, если таковой существует.
if (file_exists('_local_settings.php')) include_once('_local_settings.php');

require $readyscript_root.'/setup.inc.php';


class CronEventRiser
{
    const LOCK_FILE = '/storage/locks/cron';

    private 
        $current_time,
        $last_execution_timestamp;

    public function __construct()
    {
        $this->current_time = time();
        $this->last_execution_timestamp = \RS\HashStore\Api::get(CronManager::LAST_TIME_KEY, $this->current_time-60);
        
        if ($this->current_time - $this->last_execution_timestamp > 1440 * 60) {
            //Разрыв от предыдущего запуска не может превышать 1 суток
            $this->last_execution_timestamp = $this->current_time - 1440 * 60;
        }
    }

    /**
    * Запускает планировщик
    * 
    * @return void
    */
    public function run()
    {
        if(!\Setup::$CRON_ENABLE)
        {
            echo t('Cron отключен в настройках системы (\Setup::$CRON_ENABLE)');
            return;
        }

        if ($this->isLocked())
        {
            echo 'Cron locked';
            return;
        }

        // Устанавливаем блокировку
        $this->lock();

        // Сохраняем время начала выполнения
        RS\HashStore\Api::set(CronManager::LAST_TIME_KEY, (string) $this->current_time);

        try
        {
            RS\Event\Manager::fire('cron', array(
                'last_time' => $this->last_execution_timestamp,
                'current_time' => $this->current_time,
                'minutes' => $this->getArrayOfMinutesFromPeriod($this->last_execution_timestamp, $this->current_time)
            ));
        }
        catch(\Exception $e)
        {
            // Убираем блокировку
            $this->unlock();

            throw $e;
        }

        // Убираем блокировку
        $this->unlock();

        echo 'complete';
    }

    /**
    * Создает файл блокировки, препятствующий одновременному запуску двух планировщиков
    * 
    * @return void
    */
    private function lock()
    {
        $lock_file = \Setup::$PATH . self::LOCK_FILE;
        \RS\File\Tools::makePath($lock_file, true);
        file_put_contents($lock_file, date('Y-m-d H:i:s'));
    }

    /**
    * Улаляет файл блокировки
    * 
    * @return void
    */
    private function unlock()
    {
        $lock_file = \Setup::$PATH . self::LOCK_FILE;
        unlink($lock_file);
    }

    /**
    * Возвращает true, если планировщик уже работает в данное время
    * 
    * @return bool
    */
    private function isLocked()
    {
        $lock_file = \Setup::$PATH . self::LOCK_FILE;

        if(file_exists($lock_file))
        {
            // Если файл блокировки слишком давно не перезаписовался, то удаляем его
            if(time() > filemtime($lock_file) + CronManager::LOCK_EXPIRE_INTERVAL)
            {
                @unlink($lock_file);
            }
        }

        return file_exists($lock_file);
    }


    /**
    * Возвращает массив с перечислением минут прошедших от предыдущего запуска
    * 
    * @param integer $start_time
    * @param integer $end_time
    * @return array
    */
    private function getArrayOfMinutesFromPeriod($start_time, $end_time)
    {
        $start_time_rounded = strtotime(date('Y-m-d H:i', $start_time));

        $arr = array();

        $time = $start_time_rounded;

        while($time < $end_time)
        {
            $time += 60;

            $arr[] = $this->getNumberOfMinute($time);
        }

        return $arr;
    }

    /**
    * Возвращает номер минуты относительно 0 часов для заданного времени
    * 
    * @param integer $time
    * @return integer
    */
    private function getNumberOfMinute($time)
    {
        return date('H', $time) * 60 + date('i', $time);
    }

}


$cronEventRiser = new CronEventRiser();
$cronEventRiser->run();
