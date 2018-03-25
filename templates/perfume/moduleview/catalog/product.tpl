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
    <h1 itemprop="name" class="productTitle">{$product.title}</h1>
    {hook name="catalog-product:rating" title="{t}Карточка товара:рейтинг{/t}"}
        <div class="social">
            <span class="usersMark">Оценка покупателей</span>
            <span class="stars" title="{t}Средняя оценка{/t}: {$product->getRatingBall()}"><i style="width:{$product->getRatingPercent()}%"></i></span>
            <a href="#comments" class="comments">{t num=$product->getCommentsNum()}%num [plural:%num:отзыв|отзыва|отзывов]{/t}</a>
            <div class="share">
                <div class="handler"></div>
                <div class="block">
                    <p class="text">{t}Поделиться с друзьями{/t}:</p>
                    <script type="text/javascript" src="//yastatic.net/es5-shims/0.0.2/es5-shims.min.js" charset="utf-8"></script>
                    <script type="text/javascript" src="//yastatic.net/share2/share.js" charset="utf-8"></script>
                    <div class="ya-share2" data-services="vkontakte,facebook,odnoklassniki,moimir,twitter"></div>
                </div>
            </div>
        </div>    
    {/hook}

    <div class="card">
        <div class="images">
            {hook name="catalog-product:images" title="{t}Карточка товара:изображения{/t}"}
                {if !$product->hasImage()}
                    {$main_image=$product->getMainImage()}
                    <span class="main"><img src="{$main_image->getUrl(327,322)}" alt="{$main_image.title|default:"{$product.title}"}"/></span>
                {else}
                    {* Главные фото *}
                    {$images=$product->getImages()}
                    {if $product->isOffersUse()}
                       {* Назначенные фото у первой комлектации *}
                       {$offer_images=$product.offers.items[0].photos_arr}  
                    {/if}
                    {foreach $images as $key => $image}
                        <a href="{$image->getUrl(800,600,'xy')}" class="image main {if ($offer_images && ($image.id!=$offer_images.0)) || (!$offer_images && !$image@first)} hidden{/if}" data-n="{$key}" data-id="{$image.id}" target="_blank" {if ($offer_images && in_array($image.id, $offer_images)) || (!$offer_images)}rel="bigphotos"{/if} ><img src="{$image->getUrl(327,322,'xy')}" alt="{$image.title|default:"{$product.title} фото {$key+1}"}"></a>
                    {/foreach}
                    
                    {* Нижняя линейка фото *}
                    {if count($images)>1}
                    <div class="productGalleryWrap">
                        <div class="gallery">
                            <ul>
                                {$first = 0}
                                {foreach $images as $key => $image}
                                    <li data-id="{$image.id}" class="{if $offer_images && !in_array($image.id, $offer_images)}hidden{elseif !$first++} first{/if}"><a href="{$image->getUrl(800,600,'xy')}" class="preview" data-n="{$key}" target="_blank"><img src="{$image->getUrl(100, 100)}" alt="{$image.title|default:"{$product.title} фото {$key+1}"}"/></a></li>
                                {/foreach}
                            </ul>
                        </div>
                        <a class="control prev"></a>
                        <a class="control next"></a>
                     </div>
                     {/if}
                 {/if}
             {/hook}
        </div>
        <div class="information">
            {if $product.short_description}
            <p itemprop="description" class="descr">{$product.short_description}</p>
            {/if}
           
           {hook name="catalog-product:offers" title="{t}Карточка товара:комплектации{/t}"}
               {include "%catalog%/product_offers.tpl"}               
           {/hook}
                
           
           {if $shop_config}
                {* Блок с сопутствующими товарами *}
                {moduleinsert name="\Shop\Controller\Block\Concomitant"}
           {/if}
            
           {hook name="catalog-product:price" title="{t}Карточка товара:цены{/t}"}
               {assign var=last_price value=$product->getOldCost()}
               <div itemprop="offers" itemscope itemtype="http://schema.org/Offer" class="price">
                    {if $last_price>0}<p class="lastPriceWrap"><span class="lastPrice">{$last_price}</span> {$product->getCurrency()}</p>{/if}
                    <span itemprop="price" class="myCost" content="{$product->getCost(null, null, false)}">{$product->getCost()}</span><span class="myCurrency">{$product->getCurrency()}</span>
                    <span itemprop="priceCurrency" class="hidden">{$product->getCurrencyCode()}</span>
                    {* Если включена опция единицы измерения в комплектациях *}
                    {if $catalog_config.use_offer_unit && $product->isOffersUse()}
                        <span class="unitBlock">/ <span class="unit">{$product.offers.items[0]->getUnit()->stitle}</span></span>
                    {/if}
               </div>
           {/hook}
           
           {hook name="catalog-product:action-buttons" title="{t}Карточка товара:кнопки{/t}"}
               <p class="cartBlock">
                    {if $shop_config}
                        <a data-href="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="inDialog reserve hidden">{t}Заказать{/t}</a>
                        <span class="unobtainable hidden">{t}Нет в наличии{/t}</span>
                        <a data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="addToCart" data-add-text="Добавлено">{t}В корзину{/t}</a>
                    {/if}
                    
                    {if !$shop_config || (!$product->shouldReserve() && (!$check_quantity || $product.num>0))}
                        {if $catalog_config.buyinoneclick }
                            <a data-href="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" title="Купить в 1 клик" class="buyOneClick inDialog">{t}Купить в 1 клик{/t}</a>
                        {/if}
                    {/if}            
               </p>
               <div class="subActionBlock">
                   {if $THEME_SETTINGS.enable_compare}
                       <a class="compare{if $product->inCompareList()} inCompare{/if}">
                          <span>Сравнить</span>
                          <span class="already">{t}Добавлено{/t}</span>
                       </a>
                   {/if}
                   {if $THEME_SETTINGS.enable_favorite}
                       <a class="favorite inline{if $product->inFavorite()} inFavorite{/if}">
                           <span class="">{t}В избранное{/t}</span>
                           <span class="already">{t}В избранном{/t}</span>
                       </a>
                   {/if}                   
               </div>
           {/hook}
           
           {hook name="catalog-product:information" title="{t}Карточка товара:краткая информация{/t}"}
               {if $product.barcode}
               <p class="barcode"><span class="cap">{t}Артикул{/t}:</span> <span class="offerBarcode">{$product.barcode}</span></p>
               {/if}
               {if $product.brand_id}
               <p class="brand"><span class="cap">{t}Бренд{/t}:</span> <a class="brandTitle" href="{$product->getBrand()->getUrl()}">{$product->getBrand()->title}</a></p>
               {/if}                  
           {/hook}
           
           {if !$product->shouldReserve()} 
               {hook name="catalog-product:stock" title="{t}Карточка товара:остатки{/t}"}
                   {* Вывод наличия на складах *}
                   {assign var=stick_info value=$product->getWarehouseStickInfo()}
                   {if !empty($stick_info.warehouses)}
                        <div class="warehouseDiv">
                            <div class="title">Наличие:</div>
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
        </div>
    </div>
    <div class="clearboth"></div>
    {$tabs=[]}
    {if $product.properties || $product->isOffersUse()} {$tabs["property"] = t('Характеристики')} {/if}
    {if $product.description} {$tabs["description"] = t('Описание')} {/if}
    {if $files=$product->getFiles()}{$tabs["files"] = t('Файлы')} {/if}
    {$tabs["comments"] = t('Отзывы')}
    
    <div class="rsTabs mobile mt40">
        <ul class="tabList">
            {foreach $tabs as $key=>$tab}
            {if $tab@first}{$act_tab=$key}{/if}
            <li {if $tab@first}class="act"{/if} data-href=".tab-{$key}"><a>{$tab}</a></li>
            {/foreach}
        </ul>
        
        {if $tabs.property}
        <div class="tab tab-property {if $act_tab == 'property'}act{/if}">
            {hook name="catalog-product:properties" title="{t}Карточка товара:характеристики{/t}"}        
                <p class="mobileCaption">{$tabs.property}</p>
                {foreach $product.offers.items as $key=>$offer}
                    {if $offer.propsdata_arr}
                        <div class="offerProperty propertyGroup{if $key>0} hidden{/if}" data-offer="{$key}">
                            <p class="groupName">{t}Характеристики комплектации{/t}</p>
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
                    <div class="propertyGroup">
                        <p class="groupName">{$data.group.title|default:t("Характеристики")}</p>
                        <table class="kv">
                            {foreach $data.properties as $property}
                                {$prop_value = $property->textView()}
                                <tr>
                                    <td class="key">{$property.title}</td>
                                    <td class="value">{$prop_value} {$property.unit}</td>
                                </tr>
                            {/foreach}
                        </table>
                    </div>
                {/foreach}
            {/hook}
        </div>
        {/if}
        
        {if $tabs.description}
        <div class="tab tab-description textStyle {if $act_tab == 'description'}act{/if}">
            <p class="mobileCaption">{$tabs.description}</p>
            {hook name="catalog-product:description" title="{t}Карточка товара:описание{/t}"}
                {$product.description}
            {/hook}
        </div>
        {/if}
        
        {if $tabs.files}
            <div class="tab tab-files {if $act_tab == 'files'}act{/if}">
                <p class="mobileCaption">{$tabs.files}</p>      
                {hook name="catalog-product:files" title="{t}Карточка товара:файлы{/t}"}
                    <ul class="filesList">
                        {foreach $files as $file}
                        <li>
                            <a href="{$file->getUrl()}">{$file.name} ({$file.size|format_filesize})</a>
                            {if $file.description}<div class="fileDescription">{$file.description}</div>{/if}
                        </li>
                        {/foreach}
                    </ul>    
                {/hook}
            </div>
        {/if}
                
        {if $tabs.comments}
        <div class="tab tab-comments {if $act_tab == 'comments'}act{/if}">
            <p class="mobileCaption">{$tabs.comments}</p>                     
            {hook name="catalog-product:comments" title="{t}Карточка товара:комментарии{/t}"}
                {moduleinsert name="\Comments\Controller\Block\Comments" type="\Catalog\Model\CommentType\Product"} 
            {/hook}        
        </div>
        {/if}
    </div>    
</div>