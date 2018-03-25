{if $url->request('Act', $smarty.const.TYPE_STRING) != 'finish'}
<div class="yourcart">
    <p class="icon">{t}ВАШ ЗАКАЗ{/t}</p>
    <a class="cartInfo" href="{$router->getUrl('shop-front-cartpage')}">{$cart_info.items_count} {t count=$cart_info.items_count total=$cart_info.total}[plural:%count:товар|товара|товаров] на сумму %total{/t}</a>
</div>
{/if}