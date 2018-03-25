{addjs file="jcarousel/jquery.jcarousel.min.js"}
{addjs file="product.js"}
{assign var=shop_config value=ConfigLoader::byModule('shop')}
{assign var=check_quantity value=$shop_config.check_quantity}
{assign var=catalog_config value=$this_controller->getModuleConfig()} 
{if $product->isVirtualMultiOffersUse()} {* Если используются виртуальные многомерные комплектации *}
    {addjs file="jquery.virtualmultioffers.js"}
{/if} 
{$product->fillOffersStockStars()} {* Загружаем сведения по остаткам на складах *}

<div id="updateProduct" itemscope itemtype="http://schema.org/Product" class="product{if !$product->isAvailable()} notAvaliable{/if}{if $product->canBeReserved()} canBeReserved{/if}{if $product.reservation == 'forced'} forcedReserve{/if}" data-id="{$product.id}">
    <h1 itemprop="name">{$product.title}</h1>
    <div class="left">
        {hook name="catalog-product:images" title="{t}Карточка товара:изображения{/t}"}
            <div class="images">
                {if !$product->hasImage()}
                    {$main_image=$product->getMainImage()}
                    <span class="image"><img src="{$main_image->getUrl(310,310,'xy')}" alt="{$main_image.title|default:"{$product.title}"}"/></span>
                {else}
                    {* Главные фото *}
                    {$images=$product->getImages()}
                    {if $product->isOffersUse()}
                       {* Назначенные фото у первой комлектации *}
                       {$offer_images=$product.offers.items[0].photos_arr}  
                    {/if}
                    {foreach $images as $key => $image}
                        <a href="{$image->getUrl(800,600,'xy')}" class="image main {if ($offer_images && ($image.id!=$offer_images.0)) || (!$offer_images && !$image@first)} hidden{/if}" data-n="{$key}" data-id="{$image.id}" target="_blank" {if ($offer_images && in_array($image.id, $offer_images)) || (!$offer_images)}rel="bigphotos"{/if} ><img src="{$image->getUrl(344,344,'xy')}" alt="{$image.title|default:"{$product.title} фото {$key+1}"}"/></a>
                    {/foreach}
                    
                    {* Нижняя линейка фото *}
                    {if count($images)>1}
                    <div class="gallery">
                        <div class="scrollWrap">
                            <ul class="scrollBlock">
                                {$first = 0}
                                {foreach $images as $key => $image}
                                    <li data-id="{$image.id}" class="{if $offer_images && !in_array($image.id, $offer_images)}hidden{elseif !$first++} first{/if}">
                                        <a href="{$image->getUrl(800,600,'xy')}" class="preview" data-n="{$key}" target="_blank"><img src="{$image->getUrl(100, 100)}"  alt="{$image.title|default:"{$product.title} фото {$key+1}"}"/></a>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                        <a href="#" class="control prev"></a>
                        <a href="#" class="control next"></a>
                    </div>
                    {/if}
                {/if}
            </div>
        {/hook}
    </div>
    <div class="right">
        <div class="share">
            <div class="handler"></div>
            <div class="block">
                <p class="text">{t}Поделиться с друзьями:{/t}</p>
                <script type="text/javascript" src="//yastatic.net/es5-shims/0.0.2/es5-shims.min.js" charset="utf-8"></script>
                <script type="text/javascript" src="//yastatic.net/share2/share.js" charset="utf-8"></script>
                <div class="ya-share2" data-services="vkontakte,facebook,odnoklassniki,moimir,twitter"></div>
            </div>
        </div>
        
        {$last_price=$product->getOldCost()}
        <div itemprop="offers" itemscope itemtype="http://schema.org/Offer" class="priceLine">
            <div class="prices">
                {hook name="catalog-product:price" title="{t}Карточка товара:цены{/t}"}
                    {if $last_price>0}<p class="lastPrice">{$last_price} {$product->getCurrency()}</p>{/if}
                    <p class="price"><span itemprop="price" class="myCost" content="{$product->getCost(null, null, false)}">{$product->getCost()}</span><span class="myCurrency">{$product->getCurrency()}</span>
                        <span itemprop="priceCurrency" class="hidden">{$product->getCurrencyCode()}</span>
                        {* Если включена опция единицы измерения в комплектациях *}
                        {if $catalog_config.use_offer_unit && $product->isOffersUse()}
                            <span class="unitBlock">/ <span class="unit">{$product.offers.items[0]->getUnit()->stitle}</span></span>
                        {/if}
                    </p>
                {/hook}
                
                {hook name="catalog-product:offers" title="{t}Карточка товара:комплектации{/t}"}
                    {include "%catalog%/product_offers.tpl"}
                {/hook}
            </div>
            
            {hook name="catalog-product:rating" title="{t}Карточка товара:рейтинг{/t}"}
                <div class="rating">
                    <p>{t}Оценка покупателей{/t}</p>
                    <span class="stars" title="Средняя оценка: {$product->getRatingBall()}">
                        <i style="width:{$product->getRatingPercent()}%"></i>
                    </span>
                    <a href="#comments" class="comments">{t n={$product->getCommentsNum()}}%n [plural:%n:отзыв|отзыва|отзывов]{/t}</a>
                </div>
            {/hook}
            <div class="clearboth"></div>
        </div>
        
        {if $shop_config}
            {* Блок с сопутствующими товарами *}
            {moduleinsert name="\Shop\Controller\Block\Concomitant"}
        {/if}
        
        {hook name="catalog-product:action-buttons" title="{t}Карточка товара:кнопки{/t}"}        
            <div class="buttons">
                {if $shop_config}
                    <a data-href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="redButton inDialog reservation reserve">{t}Заказать{/t}</a>
                    <span class="noProduct">{t}Нет в наличии{/t}</span>
                    <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="addToCart" data-add-text="Добавлено">{t}В корзину{/t}</a>
                {/if}
                
                {if !$shop_config || (!$product->shouldReserve() && (!$check_quantity || $product.num>0))}
                    {if $catalog_config.buyinoneclick }
                        <a data-href="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" title="{t}Купить в 1 клик{/t}" class="buyOneClick blueButton inDialog">{t}Купить в 1 клик{/t}</a>
                    {/if}
                {/if}            
            </div>
            <div class="subActionBlock">
                {if $THEME_SETTINGS.enable_compare}
                    <a class="compare inline {if $product->inCompareList()} inCompare{/if}"><span>{t}Сравнить{/t}</span><span class="already">{t}Добавлено{/t}<br><i class="ext doCompare">{t}Сравнить{/t}</i></span></a>
                {/if}
                
                {if $THEME_SETTINGS.enable_favorite}
                    <a class="favorite inline {if $product->inFavorite()} inFavorite{/if}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
                        <span class="">{t}В избранное{/t}</span>
                        <span class="already">{t}В избранном{/t}</span>
                    </a>
                {/if}
            </div>
            
        {/hook}
        
        {hook name="catalog-product:information" title="{t}Карточка товара:краткая информация{/t}"}
            <ul class="params">
                {if $product.barcode}
                <li>{t}Артикул:{/t} <span class="offerBarcode">{$product.barcode}</span></li>
                {/if}
                {if $product.brand_id}
                <li>{t}Бренд:{/t} <a href="{$product->getBrand()->getUrl()}">{$product->getBrand()->title}</a></li>
                {/if}            
            </ul>
        {/hook}

        {if !$product->shouldReserve()}
            {hook name="catalog-product:stock" title="{t}Карточка товара:остатки{/t}"}
                {* Вывод наличия на складах *}
                {assign var=stick_info value=$product->getWarehouseStickInfo()}
                {if !empty($stick_info.warehouses)}
                    <div class="warehouseDiv">
                        <div class="title">{t}Наличие{/t}</div>
                        {foreach from=$stick_info.warehouses item=warehouse}
                            <div class="warehouseRow" data-warehouse-id="{$warehouse.id}">
                                <div class="stickWrap">
                                {foreach from=$stick_info.stick_ranges item=stick_range}
                                     {$sticks=$product.offers.items.0.sticks[$warehouse.id]}
                                     <span class="stick {if $sticks>=$stick_range}filled{/if}"></span>          
                                {/foreach}
                                </div>
                                <a class="title" href="{$warehouse->getUrl()}"><span>{$warehouse.title}</span></a>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            {/hook}
        {/if}
        
        <p itemprop="description" class="shortDescription">
            {$product.short_description}
        </p>
        <div class="bottomRight">
            {if $files=$product->getFiles()}
                <h3>{t}Файлы{/t}</h3>
                <ul class="filesList">
                    {foreach $files as $file}
                        <li>
                            <a href="{$file->getUrl()}">{$file.name} ({$file.size|format_filesize})</a>
                            {if $file.description}<div class="fileDescription">{$file.description}</div>{/if}
                        </li>
                    {/foreach}
                </ul>
            {/if}

            {hook name="catalog-product:comments" title="{t}Карточка товара:комментарии{/t}"}
                {moduleinsert name="\Comments\Controller\Block\Comments" type="\Catalog\Model\CommentType\Product"}
            {/hook}

            {moduleinsert name="\Catalog\Controller\Block\Recommended"}
        </div>
    </div>
    
    <div class="bottomLeft">
        <h3>{t}Описание{/t}</h3>
        <div class="properties">
            {hook name="catalog-product:properties" title="{t}Карточка товара:характеристики{/t}"}
                {foreach $product.offers.items as $key=>$offer}
                {if $offer.propsdata_arr}
                <div class="offerProperty{if $key>0} hidden{/if}" data-offer="{$key}">
                    <h4>{t}Характеристики комплектации{/t}</h4>
                    <table class="kv">
                        {foreach $offer.propsdata_arr as $pkey=>$pval}
                        <tr>
                            <td class="key"><span>{$pkey}</span></td>
                            <td class="value">{$pval}</td>
                        </tr>
                        {/foreach}
                    </table>
                </div>
                {/if}
                {/foreach}
            
                {foreach $product.properties as $data}
                    <h4>{$data.group.title|default:t("Характеристики")}</h4>
                    <table class="kv">
                        {foreach $data.properties as $property}
                            {$prop_value = $property->textView()}
                            <tr>
                                <td class="key">{$property.title}</td>
                                <td class="value">{$prop_value} {$property.unit}</td>
                            </tr>
                        {/foreach}
                    </table>
                {/foreach}
            {/hook}
        </div>
        
        {hook name="catalog-product:description" title="{t}Карточка товара:описание{/t}"}
            <article class="description">
                {$product.description}
            </article>
        {/hook}
    </div>
    

    <div class="clearboth"></div>
</div>