{* Шаблон карточки товара *}

{$shop_config = ConfigLoader::byModule('shop')}
{$check_quantity = $shop_config.check_quantity}
{$catalog_config = $this_controller->getModuleConfig()}

{addcss file="libs/owl.carousel.min.css"}
{addcss file="common/lightgallery/css/lightgallery.min.css" basepath="common"}

{addjs file="libs/owl.carousel.min.js"}
{addjs file="lightgallery/lightgallery-all.min.js" basepath="common"}
{addjs file="rs.product.js"}

{if $product->isVirtualMultiOffersUse()} {* Если используются виртуальные многомерные комплектации *}
    {addjs file="rs.virtualmultioffers.js"}
{/if}
{$product->fillOffersStockStars()} {* Загружаем сведения по остаткам на складах *}

<div id="updateProduct" itemscope itemtype="http://schema.org/Product" class="product
                                                                              {if !$product->isAvailable()} rs-not-avaliable{/if}
                                                                              {if $product->canBeReserved()} rs-can-be-reserved{/if}
                                                                              {if $product.reservation == 'forced'} rs-forced-reserve{/if}" data-id="{$product.id}">
    <div class="wrapper_product-card">
        <div class="col-xs-12 col-sm-6">
            <div class="row">
                <div class="page-product_gallery">
                    {hook name="catalog-product:images" title="{t}Карточка товара:изображения{/t}"}
                        <div class="product-gallery-full rs-gallery-full">
                            {$images = $product->getImages()}

                            {if !$product->hasImage()}
                                {$main_image = $product->getMainImage()}
                                <span class="rs-item">
                                    <img src="{$main_image->getUrl(675, 445, 'xy')}" alt="{$main_image.title|default:"{$product.title}"}"/>
                                </span>
                            {else}
                                {* Главное фото *}
                                {if $product->isOffersUse()}
                                    {* Назначенные фото у первой комлектации *}
                                    {$offer_images = $product.offers.items[0].photos_arr}
                                {/if}
                                {foreach $images as $key => $image}
                                    <a href="{$image->getUrl(1300, 1000,'xy')}" data-id="{$image.id}" class="rs-item {if ($offer_images && ($image.id!=$offer_images.0)) || (!$offer_images && !$image@first)} hidden{/if} rs-main-picture" {if ($offer_images && in_array($image.id, $offer_images)) || (!$offer_images)}rel="bigphotos"{/if} data-n="{$key}" target="_blank"><img class="winImage" src="{$image->getUrl(675,445,'xy')}" alt="{$image.title|default:"{$product.title} фото {$image@iteration}"}"></a>
                                {/foreach}
                            {/if}
                        </div>

                        {if count($images)>1}
                            {* Контейнер для всех фотографий карусели *}
                            <div class="hidden rs-gallery-source">
                                {foreach $images as $key => $image}
                                    <button data-id="{$image.id}" class="rs-item {if $offer_images && !in_array($image.id, $offer_images)}hidden{/if}" data-n="{$key}"><img src="{$image->getUrl(61,73,'xy')}" alt="" class="center-block"></button>
                                {/foreach}
                            </div>

                            {* Контейнер для видимых элементов карусели. Необходимо для совместимости с owlCarousel *}
                            <div class="owl-carousel product-gallery-thumb rs-gallery-thumb"></div>
                        {/if}
                    {/hook}
                </div>

            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="row">
                <div class="page-product_description">

                    <div class="page-product_description_left">
                        {if $THEME_SETTINGS.enable_favorite}
                            <a class="page-product_description_icon rs-favorite{if $product->inFavorite()} rs-in-favorite{/if}"><span class="i-svg i-svg-favorite icon-favorite"></span></a>
                        {/if}
                        {if $THEME_SETTINGS.enable_compare}
                            <a class="page-product_description_icon rs-compare{if $product->inCompareList()} rs-in-compare{/if}"><span class="i-svg i-svg-compare icon-compare"></span></a>
                        {/if}
                    </div>

                    <div class="page-product_description_right">
                        <h1 itemprop="name">{$product.title}</h1>

                        {if $product.short_description}
                            <div class="page-product_description_short">
                                {$product.short_description}
                            </div>
                        {/if}

                        {hook name="catalog-product:information" title="{t}Карточка товара:краткая информация{/t}"}
                        <ul class="page-product_description_characteristics">
                            {if $product.barcode}
                                <li>{t}Артикул{/t}: <span class="page-product_barcode rs-product-barcode offerBarcode ">{$product.barcode}</span></li>
                            {/if}
                            {if $product.brand_id}
                                <li>{t}Бренд{/t}: <a href="{$product->getBrand()->getUrl()}">{$product->getBrand()->title}</a></li>
                            {/if}

                            {if $list_properties=$product->getListProperties($product->getMainDir())}
                                {foreach $list_properties as $prop}
                                <li>{$prop.title}{if $prop.unit}({$prop.unit}){/if}: {$prop->textView()}</li>
                                {/foreach}
                            {/if}

                            {if !$product->shouldReserve()}
                                <li><a class="rs-stock-count-text-container">
                                        {if $n=$product->getAvailableWarehouses()}
                                            {t n=$n}Наличие на %n [plural:%n:складе|складах|складах]{/t}
                                        {/if}
                                    </a></li>
                            {/if}
                        </ul>
                        {/hook}

                        <div class="page-product_offers">
                            {hook name="catalog-product:offers" title="{t}Карточка товара:комплектации{/t}"}
                                {include "%catalog%/product_offers.tpl"}
                            {/hook}
                        </div>

                        <div class="page-product_description_price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                            {hook name="catalog-product:price" title="{t}Карточка товара:цены{/t}"}

                                {$new_cost=$product->getCost()}
                            {if $old_cost = $product->getOldCost()}
                                    {if $old_cost != $new_cost}
                                     <span class="card-price_old"><span class="rs-price-old lastPrice">{$old_cost}</span> <span class="card-price_currency">{$product->getCurrency()}</span></span>
                                    {/if}
                            {/if}
                                <span class="card-price_new">
                                    <span itemprop="price" class="rs-price-new  myCost" content="{$product->getCost(null, null, false)}">{$new_cost}</span>
                                    <span class="card-price_currency ">{$product->getCurrency()}</span>
                                    <meta itemprop="priceCurrency" content="{$product->getCurrencyCode()}">
                                        {* Если включена опция единицы измерения в комплектациях *}
                                    {if $catalog_config.use_offer_unit && $product->isOffersUse()}
                                        <span class="rs-unit-block">/ <span class="rs-unit">{$product.offers.items[0]->getUnit()->stitle}</span></span>
                                    {/if}
                                </span>
                            {/hook}

                            {hook name="catalog-product:action-buttons" title="{t}Карточка товара:кнопки{/t}"}
                            {if $THEME_SETTINGS.enable_amount_in_product_card}
                                <div class="page-product_quantity rs-product-amount">
                                    <div class="quantity-text">{t}Кол-во:{/t}</div>
                                    <div class="quantity">
                                        <input type="number" step="{$product->getAmountStep()}" value="{$product->getAmountStep()}" name="amount" class="rs-field-amount">
                                        <div class="quantity-nav rs-unit-block">
                                            <div class="quantity-unit rs-unit">
                                                {if $catalog_config.use_offer_unit}
                                                    {$product.offers.items[0]->getUnit()->stitle}
                                                {else}
                                                    {$product->getUnit()->stitle}
                                                {/if}
                                            </div>
                                            <div class="quantity-button quantity-up rs-inc" data-amount-step="{$product->getAmountStep()}">+</div>
                                            <div class="quantity-button quantity-down rs-dec" data-amount-step="{$product->getAmountStep()}">-</div>
                                        </div>
                                    </div>
                                </div>
                            {/if}

                            {if $shop_config}
                                <a data-url="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="link link-one-click rs-reserve rs-in-dialog">{t}Заказать{/t}</a>
                                <span class="rs-unobtainable">{t}Нет в наличии{/t}</span>
                                <a data-url="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" class="link link-more rs-to-cart" data-add-text="{t}Добавлено{/t}">{t}В корзину{/t}</a>
                            {/if}
                               
                            {if !$shop_config || (!$product->shouldReserve() && (!$check_quantity || $product.num>0))}
                                <link itemprop="availability" href="http://schema.org/InStock"> {* Товар есть в наличии *}
                                {if $catalog_config.buyinoneclick }
                                    <a data-url="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" title="{t}Купить в 1 клик{/t}" class="link link-one-click rs-buy-one-click rs-in-dialog">{t}Купить в 1 клик{/t}</a>
                                {/if}
                            {/if}
                            {/hook}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="col-xs-12">
    <div class="row">

        {$tabs=[]}
        {$properties = $product->fillProperty()}
        {if $product.description} {$tabs["description"] = t('О товаре')} {/if}
        {if $properties || ($product->checkPropExist() == 'true')} {$tabs["property"] = t('Характеристики')} {/if}
        {if $THEME_SETTINGS.enable_comments}{$tabs["comments"] = t('Отзывы')}{/if}
        {if $files=$product->getFiles()} {$tabs["files"] = t('Файлы')}   {/if}
        {$stick_info=$product->getWarehouseStickInfo()}
        {if !$product->shouldReserve() && !empty($stick_info.warehouses)}  {$tabs["stock"] = t('Наличие')} {/if}

        <div class="page-product_content">
            <ul class="nav nav-tabs hidden-xs hidden-sm">
                {foreach $tabs as $key=>$tab}
                    {if $tab@first}{$act_tab=$key}{/if}
                    <li {if $tab@first}class="active"{/if}><a data-toggle="tab" href="#tab-{$key}">{$tab}{if $key == 'comments'} <span>{$product->getCommentsNum()}</span>{/if}
                                                                                                         {if $key == 'files'}<span>{count($files)}</span>{/if}</a></li>
                {/foreach}
            </ul>

            {if !empty($tabs)}
                <div class="tab-content">
                    {if $tabs.description}
                        <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs open"><span>{t}О товаре{/t}</span>
                            <div class="right-arrow"><i class="pe-2x pe-7s-angle-up-circle"></i></div>
                        </div>
                        <div id="tab-description" class="tab-pane fade {if $act_tab == 'description'}active in{/if}">
                            <h2 class="h1">{$product.title}</h2>
                            {hook name="catalog-product:description" title="{t}Карточка товара:описание{/t}"}
                            <article itemprop="description">{$product.description}</article>
                            {/hook}
                        </div>
                    {/if}
                    {if $tabs.property}
                    <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs"><span>{t}Технические характеристики{/t}</span>
                        <div class="right-arrow"><i class="pe-2x pe-7s-angle-up-circle"></i></div>
                    </div>
                    <div id="tab-property" class="tab-pane fade {if $act_tab == 'property'}active in{/if}">
                        {hook name="catalog-product:properties" title="{t}Карточка товара:характеристики{/t}"}
                        <table class="tab-content_table_character">
                                {foreach $product.offers.items as $key=>$offer}
                                    {if $offer.propsdata_arr}

                                        <tbody class="rs-offer-property {if $key>0} hidden{/if}" data-offer="{$key}">
                                            <tr>
                                                <td colspan="2" class="tab-content_table_character-title">{t}Характеристики комплектации{/t}</td>
                                            </tr>
                                            {foreach $offer.propsdata_arr as $pkey=>$pval}
                                            <tr class="tab-content_table_character-text">
                                                <td><span>{$pkey}</span></td>
                                                <td><span>{$pval}</span></td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    {/if}
                                    {if empty($offer.propsdata_arr) && empty($properties)}

                            <tbody class="rs-offer-property {if $key>0} hidden{/if}" data-offer="{$key}">
                            <tr>
                                <td colspan="2" class="tab-content_table_character-title">{t}  У данной комплектации товара отсутстсвуют персональные характеристики{/t}</td>
                            </tr>
                            </tbody>
                                    {/if}
                                {/foreach}
                            <tbody>
                                {foreach $product->fillProperty() as $data}
                                    {if !$data.group.hidden && !empty($data.group)}
                                        <tr>
                                            <td colspan="2" class="tab-content_table_character-title">{$data.group.title|default:"Общие"}</td>
                                        </tr>
                                        {foreach $data.properties as $property}
                                            {$prop_value = $property->textView()}
                                            {if !$property.hidden && $prop_value != '' && !empty($prop_value)}
                                                <tr class="tab-content_table_character-text">
                                                    <td><span>{$property.title} {if $property.unit}({$property.unit}){/if}</span></td>
                                                    <td><span>{$prop_value}</span></td>
                                                </tr>
                                            {/if}
                                        {/foreach}
                                    {/if}
                                {/foreach}
                            </tbody>
                        </table>
                        {/hook}
                    </div>
                    {/if}

                    {if $tabs["comments"]}
                        <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs"><span>{t}Отзывы{/t}<b>{$product->getCommentsNum()}</b></span>
                            <div class="right-arrow"><i class="pe-2x pe-7s-angle-up-circle"></i></div>
                        </div>
                        <div id="tab-comments" class="tab-pane fade {if $act_tab == 'comments'}active in{/if}">
                            <a name="comments"></a>
                            {hook name="catalog-product:rating" title="{t}Карточка товара:рейтинг{/t}"}
                            <div class="card-product_rating">
                                <span class="h1">{t product_name=$product.title}Отзывы о &laquo;%product_name&raquo;{/t}</span>
                                {if $ball = $product->getRatingBall()}
                                    <div class="nav-tabs_rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
                                        <span class="nav-tabs_rating_title">{t}Средняя оценка товара{/t}</span>
                                        <span class="nav-tabs_rating_num" itemprop="ratingValue">{$ball}</span>
                                        <span class="rating">
                                            <span style="width:{$product->getRatingPercent()}%" class="value"></span>
                                        </span>
                                    </div>
                                {/if}
                            </div>
                            {/hook}

                            {hook name="catalog-product:comments" title="{t}Карточка товара:комментарии{/t}"}
                                {moduleinsert name="\Comments\Controller\Block\Comments" type="\Catalog\Model\CommentType\Product"}
                            {/hook}
                        </div>
                    {/if}
                    
                    {if $tabs.files}
                        <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs"><span>{t}Файлы{/t} <b>{count($files)}</b></span>
                            <div class="right-arrow"><i class="pe-2x pe-7s-angle-up-circle"></i></div>
                        </div>
                        <div id="tab-files" class="tab-pane fade {if $act_tab == 'files'}active in{/if}">
                            {hook name="catalog-product:files" title="{t}Карточка товара:файлы{/t}"}
                                <table class="table tab-content_table_files">
                                    <thead class="hidden-xs hidden-sm">
                                        <tr>
                                            <td>{t}Файл{/t}</td>
                                            <td></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    {foreach $files as $file}
                                        <tr>
                                            <td class="file-name">
                                                {$file.name} ({$file.size|format_filesize})
                                                {if $file.description}
                                                    <p>{$file.description}</p>
                                                {/if}
                                            </td>
                                            <td>
                                                <a href="{$file->getUrl()}" class="link link-more">{t}Скачать{/t}</a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            {/hook}
                        </div>
                    {/if}

                    {if $tabs.stock}
                        <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs"><span>{t}Наличие на складах{/t}</span>
                            <div class="right-arrow"><i class="pe-2x pe-7s-angle-up-circle"></i></div>
                        </div>
                        <div id="tab-stock" class="tab-pane fade {if $act_tab == 'stock'}active in{/if}">
                            {hook name="catalog-product:stock" title="{t}Карточка товара:остатки{/t}"}
                                <span class="h1">{t}Доступно на следующих складах{/t}</span>
                                <table class="table tab-content_table_existence">
                                    <thead class="hidden-xs hidden-sm">
                                    <tr>
                                        <td>{t}Адрес магазина{/t}</td>
                                        <td class="hidden-xs">{t}Режим работы{/t}</td>
                                        <td>{t}Наличие{/t}</td>
                                        <td></td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {foreach $stick_info.warehouses as $warehouse}
                                        {$number=$product.offers.items[0].sticks[$warehouse.id]}
                                        <tr class="rs-warehouse-row{if !$number} rs-warehouse-empty{/if}" data-warehouse-id="{$warehouse.id}">
                                            <td>{$warehouse.adress}</td>
                                            <td class="hidden-xs hidden-sm">{$warehouse.work_time}</td>
                                            <td>
                                                <div class="rs-stick-wrap stick-wrap">
                                                    {foreach $stick_info.stick_ranges as $stick_range}
                                                        {$sticks=$product.offers.items[0].sticks[$warehouse.id]}
                                                        <span class="rs-stick stick {if $sticks>=$stick_range}filled{/if}"></span>
                                                    {/foreach}
                                                </div>
                                                <span class="red rs-stick-empty">{t}Нет в наличии{/t}</span>
                                            </td>
                                            <td class="tab-content_table_existence_more"><a href="{$warehouse->getUrl()}" class="link link-more">{t}Подробнее о складе{/t}</a></td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            {/hook}
                        </div>
                    {/if}
                </div>
            {/if}
        </div>
    </div>
</div>
</div>