{if $url->request('Act', $smarty.const.TYPE_STRING) != 'finish'}
<div class="yourcart">
    <p class="icon">{t}ВАШ ЗАКАЗ{/t}</p>
    <a class="cartInfo" href="{$router->getUrl('shop-front-cartpage')}">{$cart_info.items_count} {t count=$cart_info.items_count}[plural:%count:товар|товара|товаров] на сумму{/t} {$cart_info.total}</a>
</div>
{/if}