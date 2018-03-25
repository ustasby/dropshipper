{$check_quantity=$shop_config->check_quantity}
{$imagelist=$product->getImages(false)}
<li {$product->getDebugAttributes()} data-id="{$product.id}" {if count($imagelist)>1}class="photoView"{/if}>
    <div class="hoverLayer">
        <div class="gallery{if count($imagelist)>3} scrollable{/if}">
            <a class="control up"></a>
            <a class="control down"></a>
            <div class="scrollBox">
                <ul class="items">
                    {foreach $imagelist as $n=>$image}
                    <li data-change-preview="{$image->getUrl(226,236)}" {if $image@first}class="act"{/if}><a href="{$product->getUrl()}" class="imgWrap"><img src="{$image->getUrl(56, 56)}" alt="{$image.title}"/></a></li>
                    {/foreach}
                </ul>
            </div>
        </div>
        <div class="underMain">
            {hook name="catalog-list_products:blockview-buttons" title="{t}Просмотр категории продукции:кнопки, блочный вид{/t}"}
                {if $shop_config}
                    {if $product->shouldReserve()}
                        {if $product->isOffersUse() || $product->isMultiOffersUse()}
                            <a data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="inDialog reserve">{t}Заказать{/t}</a>
                        {else}
                            <a data-href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="inDialog reserve">{t}Заказать{/t}</a>
                        {/if}
                    {else}        
                        {if $check_quantity && $product.num<1}
                            <span class="unobtainable">{t}Нет в наличии{/t}</span>
                        {else}
                            {if $product->isOffersUse() || $product->isMultiOffersUse()}
                                <span data-href="{$router->getUrl('shop-front-multioffers', ["product_id" => $product.id])}" class="cartButton showMultiOffers inDialog noShowCart">{t}В корзину{/t}</span>
                            {else}
                                <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="cartButton addToCart noShowCart" data-add-text="Добавлено">{t}В корзину{/t}</a>
                            {/if}
                        {/if}
                        
                    {/if}
                {/if}
                {if $THEME_SETTINGS.enable_compare}
                    <a class="compare{if $product->inCompareList()} inCompare{/if}" data-title="{t}К сравнению{/t}" data-already-title="{t}В сравнении{/t}"><span></span><span class="already"></span></a>
                {/if}
                {if $THEME_SETTINGS.enable_favorite}
                    <a class="favorite inline{if $product->inFavorite()} inFavorite{/if}" data-title="{t}В избранное{/t}" data-already-title="{t}В избранном{/t}"><span></span><span class="already"></span></a>
                {/if}
            {/hook}
        </div>
    </div>
    <div class="mainLayer">
        {$main_image=$product->getMainImage()}
        <a href="{$product->getUrl()}" class="image"><span class="markers">{if $product->inDir('new')}<img src="{$THEME_IMG}/newest.png" alt=""/>{/if}</span>
        <img src="{$main_image->getUrl(226, 236)}" class="middlePreview" alt="{$main_image.title|default:"{$product.title}"}"/></a>
        {hook name="catalog-list_products:blockview-title" title="{t}Просмотр категории продукции:название товара, блочный вид{/t}"}
            <a href="{$product->getUrl()}" class="title">{$product.title}</a>
        {/hook}
        <p class="price">{$product->getCost()} {$product->getCurrency()}</p>
        <div class="starsLine">
            <span class="stars" title="{t}рейтинг{/t}: {$product->getRatingBall()}"><i style="width:{$product->getRatingPercent()}%"></i></span>
            <a href="{$product->getUrl()}#comments" class="comments">{$product->getCommentsNum()}</a>
        </div>
    </div>
</li>