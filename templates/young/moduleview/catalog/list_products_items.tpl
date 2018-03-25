{if $view_as == 'blocks'}
    <ul class="products">
        {foreach $list as $product}
            {include file="%catalog%/one_product.tpl" shop_config=$shop_config product=$product}
        {/foreach}
    </ul>
    {else}
    <table class="productTable">
        {foreach $list as $product}
        <tr {$product->getDebugAttributes()} data-id="{$product.id}">
            {$main_image=$product->getMainImage()}
            <td class="image"><a href="{$product->getUrl()}"><img src="{$main_image->getUrl(100,100)}"  alt="{$main_image.title|default:"{$product.title}"}"/></a></td>
            <td class="info">
                {hook name="catalog-list_products:tableview-title" product=$product title="{t}Просмотр категории продукции:название товара, табличный вид{/t}"}
                    <a href="{$product->getUrl()}" class="title">{$product.title}</a>
                {/hook}
                <p class="descr">{$product.short_description}</p>
            </td>
            <td class="price">{$product->getCost()} {$product->getCurrency()}</td>
            <td class="actions">
                {hook name="catalog-list_products:tableview-buttons" product=$product title="{t}Просмотр категории продукции:кнопки, табличный вид{/t}"}
                    {if $shop_config}
                        {if $product->shouldReserve()}
                            {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                <a data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="redButton inDialog reservation">{t}Заказать{/t}</a>
                            {else}
                                <a data-href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="redButton inDialog reservation">{t}Заказать{/t}</a>
                            {/if}
                        {else}
                            {if $check_quantity && $product.num<1}
                                <span class="noProduct visible">{t}Нет в наличии{/t}</span>
                            {else}
                                {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                    <span data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="showMultiOffers inDialog noShowCart">{t}В корзину{/t}</span>
                                {else}
                                    <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="addToCart noShowCart" data-add-text="Добавлено">{t}В корзину{/t}</a>
                                {/if}
                            {/if}
                        {/if}
                    {/if}
                    {if $THEME_SETTINGS.enable_compare}
                        <br><a class="compare inline{if $product->inCompareList()} inCompare{/if}"><span>{t}Сравнить{/t}</span><span class="already">{t}В сравнении{/t}</span></a>
                    {/if}
                    
                    {if $THEME_SETTINGS.enable_favorite}
                        <br><a class="favorite inline{if $product->inFavorite()} inFavorite{/if}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
                            <span>{t}В избранное{/t}</span>
                            <span class="already">{t}В избранном{/t}</span>
                        </a>
                    {/if}
                {/hook}
            </td>
        </tr>
        {/foreach}
    </table>
    {/if}
    
<div class="clear"></div>
{include file="%THEME%/paginator.tpl"}