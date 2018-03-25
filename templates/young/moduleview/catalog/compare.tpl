{addjs file="{$mod_js}jquery.favorite.js" basepath="root"}
{$app->setBodyClass('comparePageBody')}
{addjs file="{$mod_js}jquery.compareshow.js" basepath="root"}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config->check_quantity}
{$app->setBodyClass('compareBody')}
{if count($comp_data.items)}
<div class="comparePage" id="compareShow" data-compare-url='{ "remove":"{$router->getUrl('catalog-front-compare', ["Act" => "remove"])}" }' data-favorite-url="{$router->getUrl('catalog-front-favorite')}" >
    <div class="compareHeadWrap">
        <table class="compareHead">
            <tr>
                <td class="compareKey">
                {if $CONFIG.logo}
                    <div class="logo">
                        <img src="{$CONFIG.__logo->getUrl(238, 238)}" alt=""/>
                        <br><span class="slogan">{$CONFIG.slogan}</span>
                    </div>
                {/if}
                    <a class="blueButton print">{t}Распечатать{/t}</a>
                </td>
                {foreach $comp_data.items as $product}
                {$imglist=$product->getImages(false)}
                <td class="compareItem{if !$product->isAvailable()} notAvaliable{/if}" data-id="{$product.id}">
                    <span class="removeWrap"><a title="{t}Исключить из сравнения{/t}" class="remove"></a></span>
                    <div class="item">
                        <div class="image{if count($imglist)>1} activelist{/if}">
                            {foreach $imglist as $image}
                                <img src="{$image->getUrl(220,120)}" {if !$image@first}class="hidden"{/if} alt="{$image.title}"/>
                            {/foreach}
                            {if !count($imglist)}
                                <img src="{$product->getImageStub()->getUrl(220,120)}" alt=""/>
                            {/if}
                        </div>
                        {if $THEME_SETTINGS.enable_favorite}
                        <a class="favorite listStyle{if $product->inFavorite()} inFavorite{/if}" data-title="{t}В избранное{/t}" data-already-title="{t}В избранном{/t}"></a>
                        {/if}
                        <div class="actions">
                            <span class="title">{$product.title}</span>
                            {if $product.multioffers.use}
                                {* Многомерные комплектации *}
                                <div class="multiOffers">
                                    {foreach $product.multioffers.levels as $level}
                                        {if !empty($level.values)}
                                            <div class="multiofferTitle">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</div>
                                            <select name="multioffers[{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}">
                                                {foreach $level.values as $value}
                                                    <option value="{$value.val_str}">{$value.val_str}</option>   
                                                {/foreach}
                                            </select>
                                        {/if}
                                    {/foreach}
                                </div>
                                {if $product->isOffersUse()}
                                    {foreach from=$product.offers.items key=key item=offer name=offers}
                                        <input value="{$key}" type="hidden" name="hidden_offers" class="hidden_offers" {if $smarty.foreach.offers.first}checked{/if} id="offer_{$key}" data-info='{$offer->getPropertiesJson()}' {if $check_quantity}data-num="{$offer.num}"{/if} data-change-cost='{ ".offerBarcode": "{$offer.barcode|default:$product.barcode}", ".myCost": "{$product->getCost(null, $key)}", ".lastPrice": "{$product->getOldCost($key)}"}'/>
                                    {/foreach}                        
                                    <input type="hidden" name="offer" value="0"/>                                            
                                {/if}                    
                            {elseif $product->isOffersUse()}
                                {* Простые комплектации *}
                                <div class="offers">
                                    <select name="offer">
                                        {foreach from=$product.offers.items key=key item=offer name=offers}
                                        <option value="{$key}" {if $check_quantity}data-num="{$offer.num}"{/if} data-change-cost='{ ".myCost": "{$product->getCost(null, $key)}", ".lastPrice": "{$product->getOldCost($key)}"}'>{$offer.title}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            {/if}  
                            <div class="price"><span class="myCost">{$product->getCost()}</span> {$product->getCurrency()}</div>
                            <div class="noProduct">{t}Нет в наличии{/t}</div>
                            {if $shop_config}
                            <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" data-add-text="{t}Добавлено{/t}" class="addToCart noShowCart">{t}В корзину{/t}</a>
                            {/if}
                        </div>
                    </div>
                </td>
                {/foreach}
            </tr>
        </table>
    </div>
    <div class="compareLines">
        {foreach $comp_data.values as $group_id=>$values}
        <p class="group">{$comp_data.groups[$group_id].title|default:t('Общие')}</p>
        <table class="lines">
            {foreach $values as $prop_id=>$product_values}    
                    {if !$comp_data.props[$prop_id].hidden}
                    <tr>
                        <td class="compareKey"><span>{$comp_data.props[$prop_id].title}{if $comp_data.props[$prop_id].unit}, {$comp_data.props[$prop_id].unit}{/if}</span></td>
                        {foreach $product_values as $product_id=>$prop}
                        <td class="compareItem" data-id="{$product_id}">
                            <span class="product">{$comp_data.items[$product_id].title}</span>
                            <span class="value">{if $prop}{$prop->textView()}{else}-{/if}</span>
                        </td>
                        {/foreach}
                    </tr>
                    {/if}
            {/foreach}            
        </table>
        {/foreach}
    </div>
</div>
{else}
<div class="noEntity">
    {t}Добавьте товары для сравнения{/t}
</div>
{/if}