{if $view_as == 'blocks'}
    <ul class="products">
        {foreach $list as $product}
            <li {$product->getDebugAttributes()} data-id="{$product.id}">
                {$main_image=$product->getMainImage()}
                <a href="{$product->getUrl()}" class="image">{if $product->inDir('new')}<i class="new"></i>{/if}<img src="{$main_image->getUrl(188,258)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
                {hook name="catalog-list_products:blockview-title" product=$product title="{t}Просмотр категории продукции:название товара, блочный вид{/t}"}
                    <a href="{$product->getUrl()}" class="title">{$product.title}</a>
                {/hook}
                <p class="price">{$product->getCost()} {$product->getCurrency()} 
                    {$last_price=$product->getOldCost()}
                    {if $last_price>0}<span class="last">{$last_price} {$product->getCurrency()}</span>{/if}</p>
                    
                {if $THEME_SETTINGS.enable_favorite}
                <a class="favorite listStyle{if $product->inFavorite()} inFavorite{/if}" data-title="{t}В избранное{/t}" data-already-title="{t}В избранном{/t}"></a>
                {/if}
                
                <div class="hoverBlock">
                    <div class="back"></div>
                    <div class="main">
                        {hook name="catalog-list_products:blockview-buttons" product=$product title="{t}Просмотр категории продукции:кнопки, блочный вид{/t}"}
                            {if $shop_config}
                                {if $product->shouldReserve()}
                                    {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                        <a data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="button reserve inDialog">{t}Заказать{/t}</a>
                                    {else}
                                        <a data-href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="button reserve inDialog">{t}Заказать{/t}</a>
                                    {/if}
                                {else}
                                    {if $check_quantity && $product.num<1}
                                        <span class="noAvaible">{t}Нет в наличии{/t}</span>
                                    {else}
                                        {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                            <span data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="button showMultiOffers inDialog noShowCart">{t}В корзину{/t}</span>
                                        {else}
                                            <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="button addToCart noShowCart" data-add-text="Добавлено">{t}В корзину{/t}</a>
                                        {/if}
                                    {/if}
                                {/if}
                            {/if}
                        {/hook}
                        {if $THEME_SETTINGS.enable_compare}
                        <a class="compare{if $product->inCompareList()} inCompare{/if}"><span>{t}К сравнению{/t}</span><span class="already">{t}Добавлено{/t}</span></a>
                        {/if}
                    </div>
                </div>
            </li>
        {/foreach}
    </ul>
{else}
    <table class="productTable">
        {foreach $list as $product}
        <tr {$product->getDebugAttributes()} data-id="{$product.id}">
            {$main_image=$product->getMainImage()}
            <td class="image"><a href="{$product->getUrl()}"><img src="{$main_image->getUrl(100,100)}" alt="{$main_image.title|default:"{$product.title}"}"/></a></td>
            <td class="info">
                {hook name="catalog-list_products:tableview-title" product=$product title="{t}Просмотр категории продукции:название товара, табличный вид{/t}"}
                    <a href="{$product->getUrl()}" class="title">{$product.title}</a>
                {/hook}
                {if $product.barcode}<p class="barcode">Артикул: {$product.barcode}</p>{/if}
                <p class="descr">{$product.short_description}</p>
            </td>
            {if $THEME_SETTINGS.enable_favorite}
            <td class="best">
                <a class="favorite listStyle{if $product->inFavorite()} inFavorite{/if}" data-title="{t}В избранное{/t}" data-already-title="{t}В избранном{/t}"></a>
            </td>
            {/if}
            <td class="price">{$product->getCost()} {$product->getCurrency()}</td>
            <td class="actions">
                {hook name="catalog-list_products:tableview-buttons" product=$product title="{t}Просмотр категории продукции:кнопки, табличный вид{/t}"}
                    {if $shop_config}
                        {if $product->shouldReserve()}
                            {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                <a href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="button reserve inDialog">{t}Заказать{/t}</a>
                            {else}    
                                <a href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="button reserve inDialog">{t}Заказать{/t}</a>
                            {/if}
                        {else}
                            {if $check_quantity && $product.num<1}
                                <div class="noAvaible">{t}Нет в наличии{/t}</div>
                            {else}
                                {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                    <span data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="button showMultiOffers inDialog noShowCart">{t}В корзину{/t}</span>
                                {else}
                                    <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="button addToCart noShowCart" data-add-text="Добавлено">{t}В корзину{/t}</a>
                                {/if}
                            {/if}
                        {/if}
                    {/if}
                    <br><a class="compare{if $product->inCompareList()} inCompare{/if}"><span>{t}Сравнить{/t}</span><span class="already">{t}Добавлено{/t}</span></a>
                {/hook}
            </td>
        </tr>
        {/foreach}
    </table>
{/if}
{include file="%THEME%/paginator.tpl"}