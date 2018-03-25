Укажите настройки в личном кабинете:<br>

<br><br>

<b>Способ подключения: </b> API<br><br>

<b>Тип нотификации: </b> HTTP+электронная почта<br><br>

<b>URL для нотификаций: </b><br>
{$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()], true)}

<br><br>

<b>URL страницы успешного платежа: </b><br>
{$router->getUrl('shop-front-onlinepay', [Act=>success, PaymentType=>$payment_type->getShortName()], true)}

<br><br>

<b>URL страницы неуспешного платежа: </b><br>
{$router->getUrl('shop-front-onlinepay', [Act=>fail, PaymentType=>$payment_type->getShortName()], true)}

<br><br>
Проведите тестирование:<br>
<a href="https://oplata.tinkoff.ru/documentation/?section=testing" target="_blank">https://oplata.tinkoff.ru/documentation/?section=testing</a>




