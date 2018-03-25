{assign var=receipt value=$data->receipt}
{assign var=transaction value=$data->transaction}


{t}Уважаемый, администратор!{/t}
<p>{t url=$url->getDomainStr()}На сайте %url произошла ошибка при выписке чека по транзакции{/t} №{$transaction.id}.</p>
<br/><br/>
<a href="{$router->getAdminUrl(false, ['f' => ['transaction_id' => $transaction['id']]], 'shop-receiptsctrl', true)}">{t}Перейти в админ. панель.{/t}</a>

 