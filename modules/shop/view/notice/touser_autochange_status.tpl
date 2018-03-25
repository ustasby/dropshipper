{$order=$data->order}
<p>{t order_num=$order.order_num date={$order.dateof|date_format:"%d.%m.%Y"} status=$order->getStatus()->title}Заказ N%order_num от %date был переведен в статус "%status".{/t}</p>
<p>{t href=$router->getUrl('shop-front-myorders', [], true)}Все подробности заказа Вы можете посмотреть в <a href="%href">личном кабинете</a>.{/t}</p>
<p>{t}С наилучшими пожеланиями, <br>администрация интернет магазина{/t} {$SITE->getMainDomain()}.</p>