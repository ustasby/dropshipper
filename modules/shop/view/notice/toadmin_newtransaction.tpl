<p>{t}Уважаемый, администратор!{/t} {t url=$url->getDomainStr()}На сайте %url инициирован платеж.{/t}</p>

<p>{t}Сведения о клиенте{/t}:</p>

<table cellpadding="5" border="1" bordercolor="#969696" style="border-collapse:collapse; border:1px solid #969696">
    <tr>
        <td>
           {t}id транзакции{/t}
        </td>
        <td>
           <b>{$data->transaction.id}</b>  
        </td>
    </tr>
    <tr>
        <td>
           {t}Имя{/t} 
        </td>
        <td>
           <b>{$data->user.name}</b>  
        </td>
    </tr>
    <tr>
        <td>
           {t}Фамилия{/t} 
        </td>
        <td>
           <b>{$data->user.surname}</b>  
        </td>
    </tr>
    <tr>
        <td>
           {t}Сумма пополнения баланса{/t} 
        </td>
        <td>
           <b>{$data->transaction.cost}</b> 
        </td>
    </tr>
</table>

<p><a href="{$router->getAdminUrl(null, null,'shop-transactionctrl', true)}">{t}Перейти к просмотру{/t}</a></p>

<p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>