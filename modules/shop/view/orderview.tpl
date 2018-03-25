{if !$refresh_mode}
    {addcss file="{$mod_css}orderview.css?v=2" basepath="root"}
    {addcss file="{$catalog_folders.mod_css}selectproduct.css" basepath="root"}

    {addcss file="common/lightgallery/css/lightgallery.min.css" basepath="common"}
    {addjs file="lightgallery/lightgallery-all.min.js" basepath="common"}
    {addjs file="jquery.ns-autogrow/jquery.ns-autogrow.min.js"}

    {addjs file="{$catalog_folders.mod_js}selectproduct.js" basepath="root"}
    {addjs file="jquery.rs.userselect.js" basepath="common"}
    {addjs file="{$mod_js}jquery.orderedit.js" basepath="root"}
    <form id="orderForm" class="crud-form" method="post" action="{urlmake}">
{/if}

{$catalog_config = ConfigLoader::byModule('catalog')}
{$delivery = $elem->getDelivery()}
{$address = $elem->getAddress()}
{$cart = $elem->getCart()}
{$order_data = $cart->getOrderData(true, false)}
{$products = $cart->getProductItems()}

    <input type="hidden" name="order_id" value="{$elem.id}">
    <input type="hidden" name="user_id" value="{$elem.user_id|default:0}">
    <input type="hidden" name="delivery" value="{$elem.delivery|default:0}">
    <input type="hidden" name="use_addr" value="{$elem.use_addr|default:0}">
    <input type="hidden" name="address[zipcode]" value="{$address.zipcode}">
    <input type="hidden" name="address[country]" value="{$address.country}">
    <input type="hidden" name="address[region]" value="{$address.region}">
    <input type="hidden" name="address[city]" value="{$address.city}">
    <input type="hidden" name="address[address]" value="{$address.address}">

    <input type="hidden" name="address[house]" value="{$address.house}">
    <input type="hidden" name="address[block]" value="{$address.block}">
    <input type="hidden" name="address[apartment]" value="{$address.apartment}">
    <input type="hidden" name="address[entrance]" value="{$address.entrance}">
    <input type="hidden" name="address[entryphone]" value="{$address.entryphone}">
    <input type="hidden" name="address[floor]" value="{$address.floor}">
    <input type="hidden" name="address[subway]" value="{$address.subway}">

    <input type="hidden" name="address[region_id]" value="{$address.region_id}">
    <input type="hidden" name="address[country_id]" value="{$address.country_id}">
    <input type="hidden" name="user_delivery_cost" value="{$elem.user_delivery_cost}">
    <input type="hidden" name="payment" value="{$elem.payment|default:0}">
    <input type="hidden" name="status" id="status" value="{$elem.status}" data-type="{$elem->getStatus()->type}">
    {if $elem.id>0}
        <input type="hidden" name="show_delivery_buttons" id="showDeliveryButtons" value="{$show_delivery_buttons|default:1}"/>
    {/if}

    <div class="order-header">
        {hook name="shop-orderview:header" title=t('Редактирование заказа(админ. панель):Верх')}
            <h2 class="title">
                {if $elem.id>0}
                    <a data-side-panel="{adminUrl do="ajaxQuickShowOrders" exclude_id=$elem.id}" title="{t}Показать другие заказы{/t}"><i class="zmdi zmdi-tag-more c-black"></i></a>
                    <span>{t num=$elem.order_num}Редактировать заказ №%num{/t}</span>
                {else}
                    {t}Создание заказа{/t}
                {/if}
            </h2>

            {if $elem.id>0}
            <div class="status dropdown">
                {$status = $elem->getStatus()}
                <div class="change-status-text" style="background-color:{$status->bgcolor}" data-toggle="dropdown">
                    <span class="value">{$status->title}</span>
                </div>
                <ul class="dropdown-menu dropdown-menu-right">
                    {foreach $status_list as $item}
                        <li {if !empty($item.child)}class="node"{/if}>
                            <a data-id="{$item.fields.id}" data-type="{$item.fields.type}">
                                <i class="status-color vertMiddle" style="background:{$item.fields.bgcolor}"></i> {$item.fields.title}
                            </a>
                            {if !empty($item.child)}
                                <i class="zmdi"></i>
                                <ul class="dropdown-submenu">
                                    {foreach $item.child as $subitem}
                                        <li>
                                            <a data-id="{$subitem.fields.id}" data-type="{$subitem.fields.type}">
                                                <i class="status-color vertMiddle" style="background:{$subitem.fields.bgcolor}"></i> {$subitem.fields.title}
                                            </a>
                                        </li>
                                    {/foreach}
                                </ul>
                            {/if}
                        </li>
                    {/foreach}
                </ul>
            </div>
            {/if}
        {/hook}
    </div>

    <div class="admin-comment{if $elem.admin_comments != ''} filled{/if}">
        <textarea placeholder="{t}Комментарий администратора (не отображается у покупателя){/t}" name="admin_comments" class="admin-comment-ta">{$elem.admin_comments}</textarea>
    </div>

    <div class="order-columns" style="margin-bottom:20px">
        <div class="o-leftcol">

            <div id="additionalBlockWrapper" class="hidden">
                <div class="bordered">
                    <h3>{t}Дополнительные параметры{/t}</h3>
                    {hook name="shop-orderview:additional-params" title=t('Редактирование заказа(админ. панель):Дополнительные параметры')}
                        {$order_depend_fields}
                    {/hook}
                </div>
            </div>

            <div class="bordered userBlock">
                <h3>{t}Покупатель{/t}</h3>
                <div id="userBlockWrapper">
                    {hook name="shop-orderview:user" title=t('Редактирование заказа(админ. панель):Блок информации о пользователе')}
                        {$user = $elem->getUser()}
                        {include file="%shop%/form/order/user.tpl" user=$user router=$router order=$elem user_num_of_order=$user_num_of_order}
                    {/hook}
                </div>
                {$order_user_fields}
            </div>

            {if $elem.id>0}
                <div class="bordered">
                    <h3>{t}Информация о заказе{/t}</h3>
                    {hook name="shop-orderview:info" title=t('Редактирование заказа(админ. панель):Блок с информацией')}
                        <table class="otable">
                            <tr>
                                <td class="otitle">
                                    {t}Номер{/t}
                                </td>
                                <td>{$elem.order_num}</td>
                            </tr>
                            <tr>
                                <td class="otitle">
                                    {t}Дата оформления{/t}
                                </td>
                                <td>{$elem.dateof|dateformat:"@date @time:@sec"}</td>
                            </tr>
                            <tr>
                                <td class="otitle">
                                    {t}Последнее обновление{/t}
                                </td>
                                <td>{$elem.dateofupdate|dateformat:"@date @time:@sec"}</td>
                            </tr>
                            <tr>
                                <td class="otitle">
                                    {t}IP пользователя{/t}
                                </td>
                                <td>{$elem.ip}</td>
                            </tr>
                            <tr>
                                <td class="otitle">
                                    {t}Статус:{/t}
                                </td>
                                <td height="20"><strong id="status_text">{$elem->getStatus()->title}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="otitle">
                                    {t}Заказ оформлен в валюте:{/t}
                                </td>
                                <td>{$elem.currency}</td>
                            </tr>
                            <tr>
                                <td class="otitle">
                                    {t}Комментарий к заказу:{/t}
                                </td>
                                <td>{$elem.comments}</td>
                            </tr>                        
                            {foreach from=$elem->getExtraInfo() item=item}
                            <tr>
                                <td class="otitle">
                                    {$item.title}:
                                </td>
                                <td>{$item.value}</td>
                            </tr>                         
                            {/foreach}                        
                            {$fm = $elem->getFieldsManager()}
                           
                            {foreach $fm->getStructure() as $item}
                                <tr>
                                    <td class="otitle">
                                        {$item.title}
                                    </td>
                                    <td > {$fm->getForm($item.alias)} </td>
                                </tr>
                            {/foreach}
                            {$url=$elem->getTrackUrl()}
                            {if !empty($url)}
                                <tr>
                                    <td class="otitle">{t}Ссылка для отслеживания пользователю{/t}</td>
                                    <td><a href="{$url}" target="_blank">{t}Перейти{/t}</a></td>
                                </tr>     
                            {/if}
                            <tr>
                                <td class="otitle">{t}Менеджер заказа{/t}</td>
                                <td>{$elem.__manager_user_id->formView()}</td>
                            </tr>

                            {$order_info_fields}
                        </table>   
                    {/hook}
                </div>    
            {/if}
            <input type="checkbox" name="notify_user" value="1" id="notify_user" checked >&nbsp;
            <label for="notify_user">{t}Уведомить пользователя об изменениях в заказе.{/t}</label>
            
        </div> <!-- leftcol -->

        <div class="o-rightcol">
            <div id="documentsBlockWrapper">
                {if $elem.id>0}
                    <div class="bordered">
                        {hook name="shop-orderview:documents" title=t('Редактирование заказа(админ. панель):Блок документы')}
                        <h3>{t}Документы{/t}</h3>

                        <ul class="order-documents">
                            {foreach $elem->getPrintForms() as $id => $print_form}
                                <li>
                                    <input type="checkbox" id="op_{$id}" value="{adminUrl do=printForm order_id=$elem.id type=$id}" class="printdoc">&nbsp;
                                    <label for="op_{$id}">{$print_form->getTitle()}</label>
                                </li>
                            {/foreach}
                        </ul>
                        <div class="input-group">
                            <button type="button" id="printOrder" class="btn btn-default"><i class="zmdi zmdi-print m-r-5"></i> {t}Печать{/t}</button>
                        </div>
                        {/hook}
                    </div>
                {/if}
            </div>
                    
            <div id="deliveryBlockWrapper" class="bordered">
                {hook name="shop-orderview:delivery" title=t('Редактирование заказа(админ. панель):Блок доставки')}
                    {include file="%shop%/form/order/delivery.tpl" delivery=$delivery address=$address elem=$elem warehouse_list=$warehouse_list}
                {/hook}
            </div>
            
            <div id="paymentBlockWrapper" class="bordered">
                {$pay = $elem->getPayment()}
                {hook name="shop-orderview:payment" title=t('Редактирование заказа(админ. панель):Блок оплаты')}
                    {include file="%shop%/form/order/payment.tpl" pay=$pay elem=$elem order_consistent=true}
                {/hook}
            </div>
        </div> <!-- right col -->
        
    </div> <!-- -->

    <h3>{t}Состав заказа{/t}</h3>

    {if $elem.id>0 && !$elem->canEdit()}
        <div class="notice-box notice-danger">
            {t}Редактирование списка товаров невозможно, так как были удалены некоторые элементы заказа{/t}
        </div>
    {/if}

    <div class="order-beforetable-tools">
        <a class="btn btn-alt btn-primary va-m-c m-r-10 {if ($elem.id>0) && !$elem->canEdit()}disabled{/if} addproduct">
            <i class="zmdi zmdi-plus f-21"></i>
            <span class="m-l-5 hidden-xs">{t}Добавить товар{/t}</span>
        </a>
        <a class="btn btn-alt btn-primary va-m-c m-r-10 {if ($elem.id>0) && !$elem->canEdit()}disabled{/if} addcoupon">
            <i class="zmdi zmdi-labels f-21"></i>
            <span class="m-l-5 hidden-xs">{t}Добавить купон на скидку{/t}</span>
        </a>
        <a class="btn btn-alt btn-primary va-m-c m-r-10 {if ($elem.id>0) && !$elem->canEdit()}disabled{/if} addorderdiscount">
            <i class="zmdi zmdi-money-off f-18"></i>
            <span class="m-l-5 hidden-xs">{t}Добавить скидку на заказ{/t}</span>
        </a>
    </div>

    <div class="anti-viewport">
        <div class="table-mobile-wrapper">
            {hook name="shop-orderview:cart" title=t('Редактирование заказа(админ. панель): Корзина') order_data=$order_data products=$products catalog_config=$catalog_config}
                <table class="pr-table">
                    <thead>
                    <tr>
                        <th class="l-w-space"></th>
                        <th class="chk" style="text-align:center" width="20">
                            <input type="checkbox" data-name="chk[]" class="chk_head select-page" title="{t}Выбрать все товары{/t}">
                        </th>
                        <th></th>
                        <th>{t}Наименование{/t}</th>
                        <th>{t}Код{/t}</th>
                        <th>{t}Вес{/t} ({$catalog_config->getShortWeightUnit()})</th>
                        <th>{t}Цена{/t}</th>
                        <th>{t}Кол-во{/t}</th>
                        <th>{t}Стоимость{/t}</th>
                        <th class="r-w-space"></th>
                    </tr>
                    </thead>
                    <tbody class="ordersEdit">
                        {if !empty($order_data.items)}
                            {foreach $order_data.items as $n => $item}
                            {$product = $products[$n].product}
                            <tr data-n="{$n}" class="item">
                                <td class="l-w-space"></td>
                                <td class="chk">
                                    <input type="checkbox" name="chk[]" value="{$n}" {if !$elem->canEdit()}disabled{/if}>
                                    <input type="hidden" name="items[{$n}][uniq]" value="{$n}">
                                    <input type="hidden" name="items[{$n}][title]" value="{$item.cartitem.title}">
                                    <input type="hidden" name="items[{$n}][entity_id]" value="{$item.cartitem.entity_id}">
                                    <input type="hidden" name="items[{$n}][type]" value="{$item.cartitem.type}">
                                    <input type="hidden" name="items[{$n}][single_weight]" value="{$item.cartitem.single_weight}">
                                </td>
                                <td>
                                    {if $product->hasImage()}
                                        <a href="{$product->getMainImage(800, 600, 'xy')}" rel="lightbox-products" data-title="{$item.cartitem.title}"><img src="{$product->getMainImage(36,36, 'xy')}"></a>
                                    {else}
                                        <img src="{$product->getMainImage(36,36, 'xy')}">
                                    {/if}
                                </td>
                                <td>
                                    {hook name="shop-orderview:cart-body-product-title" title=t('Редактирование заказа(админ. панель):Название товара в корзине заказа')}
                                        {if $product.id}
                                            <a href="{$product->getUrl()}" target="_blank" class="title">{$item.cartitem.title}</a>
                                        {else}
                                            {$item.cartitem.title}
                                        {/if}
                                        <br>
                                        {if !empty($item.cartitem.model)}{t}Модель{/t}: {$item.cartitem.model}{/if}
                                        {if $product.multioffers.use && $elem->canEdit()}
                                            {$multioffers_values = unserialize($item.cartitem.multioffers)}
                                            <div>
                                                {foreach $product.multioffers.levels as $level}
                                                    {foreach $level.values as $value}
                                                        {if $value.val_str == $multioffers_values[$level.prop_id].value}
                                                           <div class="offer_subinfo">
                                                             {if $level.title}{$level.title}{else}{$level.prop_title}{/if} : {$value.val_str}
                                                           </div>
                                                        {/if}
                                                    {/foreach}
                                                {/foreach}
                                            </div>
                                            <a class="show-change-offer btn btn-default">{t}изменить{/t}</a>

                                            <div class="change-offer-block unvisible">
                                                <div class="multiOffers unvisible">
                                                {foreach $product.multioffers.levels as $level}
                                                    {if !empty($level.values)}
                                                        <div class="title">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</div>
                                                        <select name="items[{$n}][multioffers][{$level.prop_id}]" class="product-multioffer " data-url="{adminUrl do="getOfferPrice" product_id=$product.id}" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}">
                                                            {foreach $level.values as $value}
                                                                <option value="{$value.val_str}" {if $value.val_str == $multioffers_values[$level.prop_id].value}selected="selected"{/if}>{$value.val_str}</option>
                                                            {/foreach}
                                                        </select>
                                                    {/if}

                                                {/foreach}

                                                {if $product->isOffersUse()}
                                                    {* Комплектации к многомерным комлектациям *}

                                                    <select name="items[{$n}][offer]" class="product-offers unvisible">
                                                        {foreach from=$product.offers.items item=offer key=key}
                                                            <option value="{$offer.sortn}" id="offer_{$n}_{$key}" class="hidden_offers" {if $offer.sortn == $item.cartitem.offer}selected="selected"{/if} {if $catalog_config.use_offer_unit}data-unit="{$product.offers.items[$key]->getUnit()->stitle}"{/if} data-info='{$offer->getPropertiesJson()}' data-num="{$offer.num}">{$offer.title}</option>
                                                        {/foreach}
                                                    </select>

                                                    {* Комплектации к многомерным комлектациям *}

                                                    <select class="product-offer-cost unvisible">{*Сюда будут вставлены цены комплектации*}</select>
                                                    <input type="button" value="OK" class="apply-cost-btn unvisible"/>
                                                {/if}
                                            </div>
                                            </div>
                                        {elseif $product->isOffersUse() && $elem->canEdit()}
                                            <a class="show-change-offer btn btn-default">{t}изменить{/t}</a>

                                            <div class="change-offer-block unvisible">
                                                <select name="items[{$n}][offer]" class="product-offer unvisible" data-url="{adminUrl do="getOfferPrice" product_id=$product.id}">
                                                {foreach $product.offers.items as $key => $offer}
                                                    <option value="{$offer.sortn}" {if $offer.sortn == $item.cartitem.offer}selected="selected"{/if} {if $catalog_config.use_offer_unit}data-unit="{$product.offers.items[$key]->getUnit()->stitle}"{/if}>{$offer.title}</option>
                                                {/foreach}
                                                </select>
                                                <select class="product-offer-cost unvisible">{*Сюда будут вставлены цены комплектации*}</select>
                                                <input type="button" value="OK" class="btn btn-default apply-cost-btn unvisible"/>
                                            </div>
                                        {/if}
                                    {/hook}
                                </td>
                                <td>{$item.cartitem.barcode}</td>
                                <td>{$item.cartitem.single_weight}</td>
                                <td><input type="text" name="items[{$n}][single_cost]" class="invalidate single_cost" value="{$item.single_cost_noformat}" size="10" {if !$elem->canEdit()}disabled{/if}></td>
                                <td>
                                    <input type="text" name="items[{$n}][amount]" class="invalidate num" value="{$item.cartitem.amount}" size="4" data-product-id="{$product.id}" {if !$elem->canEdit()}disabled{/if}>
                                    {if $catalog_config.use_offer_unit}
                                        <span class="unit">
                                            {$item.cartitem.data.unit}
                                        </span>
                                    {/if}
                                </td>
                                <td>
                                    <span class="cost">{$item.total}</span>
                                    {if $item.discount>0}<div class="discount">{t discount=$item.discount}скидка %discount{/t}</div>{/if}
                                </td>
                                <td class="r-w-space"></td>
                            </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td class="l-w-space"></td>
                                <td colspan="8" align="center">{t}Добавьте товары к заказу{/t}</td>
                                <td class="r-w-space"></td>
                            </tr>
                        {/if}
                    </tbody>

                    <tbody class="additems">
                 
                        {foreach from=$order_data.other key=n item=item}
                              <tr>
                            <td class="l-w-space"></td>
                            {if $item.cartitem.type=='coupon'}
                            <td class="chk">
                                <input type="checkbox" name="chk[]" value="{$n}" {if !$elem->canEdit()}disabled{/if}>
                                <input type="hidden" name="items[{$n}][uniq]" value="{$n}" class="coupon">
                                <input type="hidden" name="items[{$n}][type]" value="coupon">
                                <input type="hidden" name="items[{$n}][entity_id]" value="{$item.cartitem.entity_id}">
                                <input type="hidden" name="items[{$n}][title]" value="{$item.cartitem.title}">
                            </td>
                            {/if}
                            {if $item.cartitem.type=='order_discount'}
                            <td class="chk">
                                <input type="checkbox" name="chk[]" value="{$n}" {if !$elem->canEdit()}disabled{/if}>
                                <input type="hidden" name="items[{$n}][uniq]" value="{$n}" class="order_discount">
                                <input type="hidden" name="items[{$n}][type]" value="order_discount">
                                <input type="hidden" name="items[{$n}][entity_id]" value="{$item.cartitem.entity_id}">
                                <input type="hidden" name="items[{$n}][title]" value="{$item.cartitem.title}">
                                <input type="hidden" name="items[{$n}][price]" value="{$item.cartitem.price}">
                                <input type="hidden" name="items[{$n}][discount]" value="{$item.cartitem.discount}">
                            </td>

                            {/if}
                            <td colspan="{if $item.cartitem.type=='coupon' || $item.cartitem.type=='order_discount'}6{else}7{/if}">{$item.cartitem.title}</td>
                            <td>{if $item.total>0}{$item.total}{/if}</td>
                            <td class="r-w-space"></td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            {/hook}
        </div>
    </div>

    <div class="order-footer">
        <div>
            <a class="btn btn-danger btn-alt va-m-c removeproduct {if ($elem.id>0) && !$elem->canEdit()}disabled{/if}">
                <i class="zmdi zmdi-delete f-21"></i>
                <span class="m-l-5 hidden-xs">{t}Удалить выбранное{/t}</span>
            </a>
        </div>
        <div>
            <span class="weight m-r-15">
                {t}Вес:{/t} <span class="total_weight">{$order_data.total_weight}</span> ({$catalog_config->getShortWeightUnit()})
            </span>
            <span class="total-price">
                {t}Итого:{/t} <span class="summary">{$order_data.total_cost}</span>
                <a class="btn btn-warning refresh" onclick="$.orderEdit('refresh')">{t}пересчитать{/t}</a>
            </span>
        </div>
    </div>

    {if count($returned_items) > 0}
        <h3>{t}Возвращенные товары{/t}</h3>
        <div class="table-mobile-wrapper">
            <table class="rs-table">
                <thead>
                <tr>
                    <th>{t}Название{/t}</th>
                    <th>{t}Количество{/t}</th>
                    <th>{t}Номер возврата{/t}</th>
                </tr>
                </thead>
                <tbody class="ordersEdit">
                {foreach $returned_items as $item}
                    <tr class="item">
                        <td class="l-w-space">{$item.title}</td>
                        <td class="l-w-space">{$item.amount}</td>
                        <td class="l-w-space">{$item->getReturn()->return_num}</td>
                    </tr>
                {/foreach}
                </tbody>
                </table>
            </div>
    {/if}
        {* Сюда будут вставлены элементы через "Добавить купон" и "Добавить товар" *}
    <div class="added-items"></div>
     
     {*  Блок-контейнер для инициализации диалога добавления товара  *}
     
     <div class="product-group-container hide-group-cb hidden" data-urls='{ "getChild": "{adminUrl mod_controller="catalog-dialog" do="getChildCategory"}", "getProducts": "{adminUrl mod_controller="catalog-dialog" do="getProducts"}", "getDialog": "{adminUrl mod_controller="catalog-dialog" do=false}" }'>
        <a href="JavaScript:;" class="select-button"></a><br>
        <div class="input-container"></div>
     </div>
     <br><br>

     {hook name="shop-orderview:footer" title=t('Редактирование заказа(админ. панель):Подвал')}
         <div class="collapse-block{if $elem.user_text} open{/if}">
            <div class="collapse-title">
                <i class="zmdi zmdi-chevron-right"></i><!--
                --><h3>{t}Текст для покупателя{/t}</h3><!--
                --><span class="help-text">{t}(будет виден покупателю на странице просмотра заказа){/t}</span>
            </div>
            <div class="collapse-content">
                {$elem.__user_text->formView()}
            </div>
         </div>
              
         {* Здесь отображаются поля с контекстом видимости footer *}
         {$order_footer_fields}
     {/hook}     
{if !$refresh_mode}
</form>
{/if}