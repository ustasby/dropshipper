{assign var=shop_config value=ConfigLoader::byModule('shop')}
{assign var=check_quantity value=$shop_config.check_quantity}

<div class="authorization formStyle multiComplectations">
    <h2 data-dialog-options='{ "width": "765" }'>{t}Выбор комплектации{/t}</h2>
    
    <div class="{if !$product->isAvailable()} notAvaliable{/if}{if $product->canBeReserved()} canBeReserved{/if}{if $product.reservation == 'forced'} forcedReserve{/if}" data-id="{$product.id}">
        <h4 class="fn">{$product.title}</h4>
        <div class="leftColumn">
            <div class="image">
                {$main_image=$product->getMainImage()}
                <img src="{$main_image->getUrl(344, 344)}" class="photo" alt="{$main_image.title|default:"{$product.title}"}"/>
            </div>
            {if $product.barcode}
                <p class="barcode"><span class="cap">{t}Артикул:{/t}</span> <span class="offerBarcode">{$product.barcode}</span></p>
            {/if}
            {if $product.short_description}
                <p class="descr">{$product.short_description|nl2br}</p>
            {/if}
            <div class="fcost">
                {assign var=last_price value=$product->getOldCost()}
                {if $last_price>0}<div class="lastPriceWrap"><span class="lastPrice">{$last_price}</span> {$product->getCurrency()}</div><br>{/if}
                <span class="price"><strong class="myCost">{$product->getCost()}</strong> {$product->getCurrency()}</span>
            </div>
        </div>
        <div class="information">
            {include "%catalog%/product_offers.tpl" preview_width="344" preview_height="344" preview_scale="xy"}
            
            {if $shop_config}
                {* Блок с сопутствующими товарами *}
                {moduleinsert name="\Shop\Controller\Block\Concomitant"}
            {/if}
            
            <div class="buttons">
                <a data-href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="redButton inDialog reservation">{t}Заказать{/t}</a>
                <span class="noProduct">{t}Нет в наличии{/t}</span>
                <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="button addToCart noShowCart">{t}В корзину{/t}</a>
            </div>
        </div>            
    </div>
</div>

{literal}
    <script type="text/javascript">
        $(function() {
            $('[name="offer"]').changeOffer();
        });
        $('.multiComplectations .addToCart').on('click',function(){
            $.colorbox.close();
        });
    </script>
{/literal}