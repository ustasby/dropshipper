{* Комплектации товара *}

{addjs file="rs.changeoffer.js"}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
{$catalog_config=ConfigLoader::byModule('catalog')}

{if $product->isMultiOffersUse() || $product->isVirtualMultiOffersUse()}
    {$first_offer_params=$product.offers.items.0.propsdata_arr} {* Первая комплектация для виртуальных многомерок *}
    {* Многомерные комплектации *}
    <div class="multioffers product-offers">
        <span class="product-offers_pname">{$product.offer_caption|default:t('Комплектация')}</span>
        {* Подгрузим у многомерных комплектаций фото к их вариантам *}
        {$product->fillMultiOffersPhotos()}
        {* Переберём доступные многомерные комплектации *}
        
        {foreach $product.multioffers.levels as $property_id => $level}
            {if !empty($level.values)}
                {$property = $level->getPropertyItem()}
                <div class="product-offers_item">
                    <div class="product-offers_title">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</div>
                    {if !$level.is_photo && !isset($level.values_photos)} {* Если отображать не как фото (выпадающим списком)*}

                        {if in_array($property.type, ['radio', 'color', 'image'])}
                            <div class="level-type-{$property.type}">
                            {foreach $level.values as $key => $value}
                                <div class="level-row">
                                    <input id="mo_{$level.prop_id}_{$key}" type="radio" name="multioffers[{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}" data-prop-id="{$property_id}" value="{$value.val_str}">

                                    {if $property.type == 'radio'}
                                        {* Отображаем  радиокнопки *}
                                        <label for="mo_{$level.prop_id}_{$key}">{$value.val_str}</label>

                                    {elseif $property.type == 'color'}
                                        {* Отображаем выбор цвета *}
                                        <label for="mo_{$level.prop_id}_{$key}" title="{$value.val_str}" style="background-color:{$value.color}">
                                            {if $value.image}
                                                <img src="{$value.__image->getUrl(32, 32, 'axy')}">
                                            {/if}
                                        </label>

                                    {elseif $property.type == 'image'}
                                        {* Отображаем картинки *}
                                        <label for="mo_{$level.prop_id}_{$key}" title="{$value.val_str}">
                                            {if $value.image}
                                                <img src="{$value.__image->getUrl(60, 60, 'cxy')}">
                                            {/if}
                                        </label>

                                    {/if}
                                </div>
                            {/foreach}
                            </div>
                        {else}
                            {* Отображаем обычный список *}
                            <select class="select" name="multioffers[{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}" data-prop-id="{$property_id}">
                                {foreach $level.values as $value}
                                    <option value="{$value.val_str}" {if isset($level.is_virtual) && $value.val_str==$first_offer_params[$level.title]}selected{/if}>{$value.val_str}</option>
                                {/foreach}
                            </select>
                        {/if}

                    {else} {* Как фото, привязанные к комплектации *}
                        <div class="multioffers_values">
                            <input type="hidden" name="multioffers[{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}" data-prop-id="{$property_id}"/>
                            {foreach $level.values as $value}
                                {if isset($level.values_photos[$value.val_str])}
                                    <a class="multioffers_value-block {if $value@first}sel{/if}" data-value="{$value.val_str}" data-image="{$level.values_photos[$value.val_str]->getUrl({$preview_width|default:"310"},{$preview_height|default:"310"}, {$preview_scale|default:"axy"})}"  title="{$value.val_str}"><img src="{$level.values_photos[$value.val_str]->getUrl(40,40,'axy')}"/></a>
                                {else}
                                    <a class="multioffers_value-block like-string {if $value@first}sel{/if}" data-value="{$value.val_str}" title="{$value.val_str}">{$value.val_str}</a>
                                {/if}
                            {/foreach}
                        </div>
                    {/if}
                </div>
            {/if}
        {/foreach}
    </div>
    
    {if $product->isVirtualMultiOffersUse()}
        {* Сведения по виртуальным многомерным комплектациям *}
        {foreach $product.virtual_multioffers.items as $product_id => $offer}
            <input value="{$product_id}" type="hidden" name="hidden_multioffers" class="hidden_offers" id="offer_{$product_id}" data-url='{$offer.url}' data-info='{json_encode($offer.values)}'/>
        {/foreach}
        
    {else}
        {* Сведения по простым комплектациям, связанным с многомерными для изменения цены и остатка *}
        {foreach $product.offers.items as $key => $offer}
            {* В data-info подменяем двойную ковычку на специальную конструкцию во избежание проблемы jquery с json *}
            <input value="{$key}" type="hidden" name="hidden_offers" class="hidden_offers" {if $offer@first}checked{/if} id="offer_{$key}" data-info='{str_replace('&quot;', '*`*', {$offer->getPropertiesJson()})}' {if $check_quantity}data-num="{$offer.num}"{/if} {if $catalog_config.use_offer_unit}data-unit="{$offer->getUnit()->stitle}"{/if} data-change-cost='{ ".rs-product-barcode": "{$offer.barcode|default:$product.barcode}", ".rs-price-new": "{$product->getCost(null, $key)}", ".rs-price-old": "{$product->getOldCost($key)}"}' data-images='{$offer->getPhotosJson()}' data-offer-id='{$offer.id}' data-sticks='{$offer->getStickJson()}' {if !$product->isOffersUse()}data-type-offer="notExist"{/if}/>
        {/foreach}
        {if $product->isOffersUse()}
            <input value="{count($product.offers.items)}" type="hidden" name="hidden_offers" class="hidden_offers" id="offer_{count($product.offers.items)}" data-type-offer="notExist" data-info=" " {if $check_quantity}data-num="0"{/if} {if $catalog_config.use_offer_unit}data-unit="{$product.offers.items.0->getUnit()->stitle}"{/if} data-change-cost='{ ".offerBarcode": "{$product.barcode}", ".myCost": "{t}нет цены{/t}", ".lastPrice": " ", ".myCurrency": " "}' data-offer-id='0' data-sticks="[]"/>
        {/if}
        <input type="hidden" name="offer" value="0"/>
    {/if}

{elseif $product->isOffersUse()}
    {* Простые комплектации *}
    <div class="product-offers">
        <span class="product-offers_pname">{$product.offer_caption|default:t('Комплектация')}</span>
        <div class="product-offers_values">
            {if count($product.offers.items)>0}
                {* Если комплектаций много, то отобразим их в виде списка *}
                <select class='select' name="offer">
                    {foreach from=$product.offers.items key=key item=offer name=offers}
                        <option value="{$key}" {if $offer.num <= 0}class="nullComplects"{/if}{if $smarty.foreach.offers.first}checked{/if} {if $check_quantity}data-num="{$offer.num}"{/if} {if $catalog_config.use_offer_unit}data-unit="{$offer->getUnit()->stitle}"{/if} data-change-cost='{ ".offerBarcode": "{$offer.barcode|default:$product.barcode}", ".myCost": "{$product->getCost(null, $key)}", ".lastPrice": "{$product->getOldCost($key)}"}' data-images='{$offer->getPhotosJson()}' data-offer-id='{$offer.id}' data-sticks='{$offer->getStickJson()}'>{$offer.title}</option>
                    {/foreach}
                </select>
            {else}
                {* Если комплектаци мало, то отобразим их в виде радиокнопок *}
                {foreach $product.offers.items as $key => $offer}
                    <div class="product-offers_item">
                        <input value="{$key}" type="radio" name="offer" {if $offer@first}checked{/if} id="offer_{$key}" {if $check_quantity}data-num="{$offer.num}"{/if} {if $catalog_config.use_offer_unit}data-unit="{$offer->getUnit()->stitle}"{/if} data-change-cost='{ ".offerBarcode": "{$offer.barcode|default:$product.barcode}", ".myCost": "{$product->getCost(null, $key)}", ".lastPrice": "{$product->getOldCost($key)}"}' data-images='{$offer->getPhotosJson()}' data-offer-id='{$offer.id}' data-sticks='{$offer->getStickJson()}'>
                        <label {if $offer.num <= 0}class="nullComplects"{/if} for="offer_{$key}">{$offer.title}</label>
                    </div>
                {/foreach}
            {/if}
        </div>


        {if $product.type_product == "mat"}

            {addjs file="evasmart.changeoffer.js"}

            <span class="product-offers_pname">{$product.offer_caption|default:t('Материал')}</span>
            <div class="product-offers_values">
                <select class='select' name="color_mat" id="color_mat">
                    <option value="Соты" selected data-change="color_romb" data-sticks="">Соты</option>
                    <option value="Ромб" data-change="color_sota" data-sticks="">Ромб</option>
                </select>
            </div>

            <span class="product-offers_pname">{$product.offer_caption|default:t('Цвет основы')}</span>

            <div class="product-offers_values">
                <select class='select' name="color_sota" id="color_sota">
                    {foreach from=$color_sota key=key item=color name=color_sota}
                        <option  data-sticks="" value="{$color}" {if $smarty.foreach.color_sota.first}checked{/if}>{$color}</option>
                    {/foreach}
                </select>
                <select class='select' name="color_romb" id="color_romb" style="display: none">
                    {foreach from=$color_romb key=key item=color name=color_romb}
                        <option  data-sticks="" value="{$color}" {if $smarty.foreach.color_romb.first}checked{/if}>{$color}</option>
                    {/foreach}
                </select>

            </div>

            <span class="product-offers_pname">{$product.offer_caption|default:t('Цвет кантика')}</span>
            <div class="product-offers_values">
                <select class='select' name="color_kant" id="color_kant" >
                    {foreach from=$color_kant key=key item=color name=color_kant}
                        <option  data-sticks="" value="{$color}" {if $smarty.foreach.color_kant.first}checked{/if}>{$color}</option>
                    {/foreach}
                </select>
            </div>
        {/if}
    </div>
{/if}