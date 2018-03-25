{$check_quantity=$shop_config.check_quantity}
<li {$product->getDebugAttributes()} data-id="{$product.id}">
    <div class="hoverLayer">
        <div class="underMain{if !$THEME_SETTINGS.enable_compare} noCompare{/if}">
            {hook name="catalog-list_products:blockview-buttons" title="{t}Просмотр категории продукции:кнопки, блочный вид{/t}"}
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
                    <a class="compare {if $product->inCompareList()} inCompare{/if}"><span>{t}Сравнить{/t}</span><span class="already">{t}Добавлено{/t}<br><i class="ext doCompare">{t}Сравнить{/t}</i></span></a>
                {/if}
            {/hook}
        </div>
    </div>
    <div class="mainLayer">
        {if $product->inDir('new')}<i class="new"></i>{/if}
        {$main_image=$product->getMainImage()}
        <a href="{$product->getUrl()}" class="image"><img src="{$main_image->getUrl(220,220)}" alt="{$main_image.title|default:"{$product.title}"}"/></a>
        
        {if $THEME_SETTINGS.enable_favorite}
            <a class="favorite listStyle{if $product->inFavorite()} inFavorite{/if}" data-title="{t}В избранное{/t}" data-already-title="{t}В избранном{/t}"><span></span><span class="already"></span></a>
        {/if}
        
        {hook name="catalog-list_products:blockview-title" title="{t}Просмотр категории продукции:название товара, блочный вид{/t}"}
            <a href="{$product->getUrl()}" class="title">{$product.title}</a>
        {/hook}
        <div class="price">{$product->getCost()} {$product->getCurrency()} 
        {$last_price=$product->getOldCost()}
        {if $last_price>0}<span class="last">{$last_price} {$product->getCurrency()}</span>{/if}</div>
    </div>
</li>