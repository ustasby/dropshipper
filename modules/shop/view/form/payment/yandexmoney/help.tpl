<h3>{t}Настройка аккаунта Яндекс.Касса{/t}</h3>

<p>
    <small>{t}Для работы с Яндекс.Кассой необходимо наличие сертификата (как минимум самоподписного) у сайта для установления SSL соединения.{/t}</small>
</p>

<b>{t}Кодировка{/t}: </b> {t}Только UTF-8{/t}<br>
<b>{t}Тип оплаты{/t}: </b> {t}Фиксированная оплата{/t}

<br><br>

<b>paymentAvisoUrl: </b><br>
https://{$Setup.DOMAIN}{$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()])}

<br><br>

<b>checkUrl: </b><br>
https://{$Setup.DOMAIN}{$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()])}

<br><br>

<b>shopSuccessURL: </b><br>
{$router->getUrl('shop-front-onlinepay', [Act=>success, PaymentType=>$payment_type->getShortName()], true)}

<br><br>

<b>shopFailURL: </b><br>
{$router->getUrl('shop-front-onlinepay', [Act=>fail, PaymentType=>$payment_type->getShortName()], true)}

