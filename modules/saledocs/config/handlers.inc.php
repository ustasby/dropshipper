<?php
namespace SaleDocs\Config;
use \RS\Orm\Type;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('printform.getlist')
            ->bind('orm.init.users-user')
            ->bind('orm.init.site-config');
    }
    
    public static function printformGetList($list)
    {
        $list[] = new \SaleDocs\Model\PrintForm\Bill();
        $list[] = new \SaleDocs\Model\PrintForm\Contract();
        $list[] = new \SaleDocs\Model\PrintForm\Act();        
        return $list;
    } 
    
    /**
    * Расширяем объект Покупатель, добавляя поля, необходимые для заключения договора
    * 
    * @param \Users\Model\Orm\User $user
    */
    public static function ormInitUsersUser($user)
    {
        $user->getPropertyIterator()->append(array(
            t('Основные'),
                'passport' => new Type\Varchar(array(
                    'description' => t('Серия, номер, кем и когда выдан паспорт'),
                    'hint' => t('например: серия 03 06, номер 123456, выдан УВД Западного округа г. Краснодар, 04.03.2006'),
                    'meVisible' => false,
                )),
            t('Организация'),
                'company_kpp' => new Type\Varchar(array(
                    'description' => t('КПП огранизации'),
                    'meVisible' => false,
                )),
                'company_ogrn' => new Type\Varchar(array(
                    'description' => t('ОГРН/ОГРНИП'),
                    'meVisible' => false,
                )),
                'company_v_lice' => new Type\Varchar(array(
                    'description' => t('в лице ....'),
                    'hint' => t('например: директора Иванова Ивана Ивановича. Пустое поле означает - "в собственном лице"'),
                    'meVisible' => false,
                )),
                'company_deistvuet' => new Type\Varchar(array(
                    'description' => t('действует на основании ...'),
                    'hint' => t('например: устава или свидетельства о регистрации физ.лица в качестве ИП, ОГРНИП:0000000000'),
                    'meVisible' => false,
                )),
                'company_bank' => new Type\Varchar(array(
                    'description' => t('Наименование банка'),
                    'meVisible' => false,
                )),
                'company_bank_bik' => new Type\Varchar(array(
                    'description' => t('БИК'),
                    'meVisible' => false,
                )),
                'company_bank_ks' => new Type\Varchar(array(
                    'description' => t('Коррекспондентский счет'),
                    'meVisible' => false,
                )),
                'company_rs' => new Type\Varchar(array(
                    'description' => t('Рассчетный счет'),
                    'meVisible' => false,
                )),
                'company_address' => new Type\Varchar(array(
                    'description' => t('Юридический адрес'),
                    'meVisible' => false,
                )),
                'company_post_address' => new Type\Varchar(array(
                    'description' => t('Фактический адрес'),
                    'meVisible' => false,
                )),
                'company_director_post' => new Type\Varchar(array(
                    'description' => t('Должность руководителя'),
                    'meVisible' => false,
                )),
                'company_director_fio' => new Type\Varchar(array(
                    'description' => t('Фамилия и инициалы руководителя'),
                    'meVisible' => false,
                ))
        ));
    }
    
    /**
    * Расширяем объект Продавца (настройка магазина), добавляя туда поля, необходимые для заключения договора
    * 
    * @param \Site\Model\Orm\Config $site_config
    */
    public static function ormInitSiteConfig($site_config)
    {
        $site_config->getPropertyIterator()->append(array(
            t('Организация'),
            'firm_ogrn' => new Type\Varchar(array(
                'description' => t('ОГРН(ИП)'),
            )),            
            'firm_v_lice' => new Type\Varchar(array(
                'description' => t('Компания представлена в лице ....'),
                'hint' => t('например: директора Иванова Ивана Ивановича. Пустое поле означает - "в собственном лице"')
            )),
            'firm_deistvuet' => new Type\Varchar(array(
                'description' => t('действует на основании ...'),
                'hint' => t('например: устава или свидетельства о регистрации физ.лица в качестве ИП, ОГРНИП:0000000000')
            )),
            'firm_address' => new Type\Varchar(array(
                'description' => t('Юридический адрес'),
            )),
            'firm_post_address' => new Type\Varchar(array(
                'description' => t('Фактический адрес'),
            )),
        ));
    }
    
}


