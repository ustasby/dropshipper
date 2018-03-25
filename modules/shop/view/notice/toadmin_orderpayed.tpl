<p>{t}Уважаемый, администратор!{/t} {t url=$url->getDomainStr()}На сайте %url оплачен заказ{/t} №{$data->order.order_num}.
<a href="{$router->getAdminUrl('edit', ["id" => $data->order.id], 'shop-orderctrl', true)}">{t}Перейти к заказу{/t}</a></p>

<p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>