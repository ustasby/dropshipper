{*
    В этом шаблоне доступна переменная $transaction
    Объект заказа можно получить так: $transaction->getOrder()
    $need_check_receipt - Флаг, если нужно проверить статус чека после оплаты 
*}
<div class="paymentResult success profile">
    {if $need_check_receipt}
        <img id="rs-waitReceiptLoading" src="{$THEME_IMG}/loading.gif" alt=""/>
    {/if}
    <h2>Оплата успешно проведена {if $need_check_receipt}<br/><span id="rs-waitReceiptStatus">Ожидается получение чека.</span>{/if}</h2>    
    <p class="descr">
    {if $transaction->getOrder()->id}
        <a href="{$router->getUrl('shop-front-myorderview', ['order_id' => $transaction->getOrder()->order_num])}" class="colorButton">перейти к заказу</a>
    {else}
        <a href="{$router->getUrl('shop-front-mybalance')}" class="colorButton">перейти к лицевому счету</a>
    {/if}
    </p>
</div>

{if $need_check_receipt} {* Если нужно проверить статус чека после оплаты *}
   {addjs file="%shop%/order/success_receipt.js"}
{/if}