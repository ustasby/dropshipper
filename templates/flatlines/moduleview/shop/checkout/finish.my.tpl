{* Оформление заказа. Шаг - Завершение заказа *}

{addjs file="rs.order.js"}

{$catalog_config=ConfigLoader::byModule('catalog')}
{$user=$order->getUser()}
<div class="page-registration-steps t-registration-steps">
    <div class="form-style t-registration-end">

        <form method="POST" class="t-order-wrapper">

                <div class="t-order-top">
                    <h2 class="h2">Заказ успешно оформлен</h2>
                    <p>
                    {if $user.id}
                        Следить за изменениями статуса заказа можно в разделе <a href="{$router->getUrl('shop-front-myorders')}" target="_blank">история заказов</a>.
                    {/if}
                        Все уведомления об изменениях в данном заказе также будут отправлены на электронную почту покупателя.</p>
                </div>

                <div class="t-order-card_description">
                    <h2 class="h2">{t}Сведения о заказе{/t}</h2>

                    <table class="t-order_table">
                        <tr>
                            <td class="key">{t}Заказчик{/t}</td>
                            <td>{$user.surname} {$user.name}</td>
                        </tr>
                        <tr>
                            <td class="key">{t}Телефон{/t}</td>
                            <td>{$user.phone}</td>
                        </tr>
                        <tr class="preSep">
                            <td class="key">{t}E-mail{/t}</td>
                            <td>{$user.e_mail}</td>
                        </tr>
                        {$fmanager=$order->getFieldsManager()}
                        {if $fmanager->notEmpty()}
                            {foreach $fmanager->getStructure() as $field}
                                <tr class="{if $field@first}postSep{/if} {if $field@last}preSep{/if}">
                                    <td class="key">{$field.title}</td>
                                    <td>{$fmanager->textView($field.alias)}</td>
                                </tr>
                            {/foreach}
                        {/if}
                        {$delivery=$order->getDelivery()}
                        {$address=$order->getAddress()}
                        {$pay=$order->getPayment()}

                        {if $order.delivery}
                            <tr class="postSep">
                                <td class="key">{t}Доставка{/t}</td>
                                <td>{$delivery.title}</td>
                            </tr>
                        {/if}
                        {if $order.only_pickup_points && $order.warehouse}
                            <tr>
                                <td class="key">{t}Пункт самовывоза{/t}</td>
                                <td>{$order->getWarehouse()->adress}</td>
                            </tr>
                        {elseif $order.use_addr}
                            <tr>
                                <td class="key">{t}Адрес{/t}</td>
                                {$address=str_replace(array("-,", "000000,", "Украина", ","), array("", "", "", ""), $address->getLineView())}
                                <td>{$address}</td>
                            </tr>
                        {/if}
                        {if $order.payment}
                            <tr>
                                <td class="key">{t}Оплата{/t}</td>
                                <td>{$pay.title}</td>
                            </tr>
                        {/if}
                    </table>
                </div>

                {if $order->getPayment()->hasDocs()}
                    <div class="t-order-card_description">
                        <h2 class="h2">{t}Документы на оплату{/t}</h2>
                        <div class="border">
                            <p>{t}Воспользуйтесь следующими документами для оплаты заказа. Эти документы всегда доступны в разделе история заказов.{/t}</p>
                            <div class="textCenter">
                                {$type_object=$order->getPayment()->getTypeObject()}
                                {foreach $type_object->getDocsName() as $key => $doc}
                                    <a href="{$type_object->getDocUrl($key)}" target="_blank" class="link link-more">{$doc.title}</a>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                {/if}

                <div class="t-order_confirm">

                    <div class="t-order_head">
                        <h2 class="h2">{t}Состав заказа{/t}</h2>
                    </div>

                    {$orderdata=$cart->getOrderData()}
                    <div class="t-order_products t-order_confirm-box">
                        {hook name="shop-checkout-finish:products" title="{t}Подтверждение заказа:товары{/t}"}

                        {foreach $orderdata.items as $n=>$item}
                            {$orderitem=$item.cartitem}
                            {$barcode=$orderitem.barcode}
                            {$offer_title=$orderitem.model}
                            {$multioffer_titles=$orderitem->getMultiOfferTitles()}

                            {if !empty($orderitem.extra)}
                                {$extra=unserialize($orderitem.extra)}
                                {$mat=str_replace(',', '<br>', $extra.additional_uniq)}
                            {/if}
                            <div class="card card-order-product">
                                <div class="card-text">
                                    {$orderitem.title}
                                    <div class="code-line">
                                        {if $barcode != ''}{t}Артикул{/t}:<span class="value">{$barcode}</span><br>{/if}
                                        {if $multioffer_titles || $offer_title}
                                            <div class="multioffers-wrap">
                                                {foreach $multioffer_titles as $multioffer}
                                                    <p class="value">{$multioffer.title} - <strong>{$multioffer.value}</strong></p>
                                                {/foreach}
                                                {if !$multioffer_titles}
                                                    <p class="value"><strong>{$offer_title}</strong></p>

                                                {/if}
                                                {if $mat}<p  class="value">{$mat}</p><br> {/if}
                                            </div>
                                        {/if}
                                    </div>
                                </div>

                                <div class="card-quantity">
                                    {$orderitem.amount} {$orderitem.data.unit}
                                </div>

                                <div class="card-price">
                                    <span class="card-price_present">{$item.total}</span>
                                    {if $item.discount>0}
                                        <div class="card-price_discount">
                                            {t discount=$orderitem.discount}скидка %discount{/t}
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        {/foreach}

                        {foreach $orderdata.other as $item}
                            <div class="card card-order-product">
                                <div class="card-text">{$item.cartitem.title}</div>
                                <div class="card-quantity"></div>
                                <div class="card-price">
                                    {if $item.total != 0}
                                        <span class="card-price_present">{$item.total}</span>
                                    {/if}
                                </div>
                            </div>
                        {/foreach}
                        {/hook}
                    </div>
                    {if $current_user->isAdmin() || $current_user->inGroup('DS') }
                        <div class="t-order-card_end-sum">
                            <p>{t}Сумма заказа для дропшипера{/t}:</p>
                            <span>{$orderdata.total_cost}</span>
                        </div>
                    {else}
                        <div class="t-order-card_end-sum">
                            <p>{t}Сумма заказа{/t}:</p>
                            <span>{$orderdata.total_cost}</span>
                        </div>
                    {/if}
                    {if $order.order_type == 1 &&  ($current_user->isAdmin() || $current_user->inGroup('DS')) }
                        <div class="t-order-card_end-sum">
                            <p>{t}Стоимость заказа для конечного покупателя{/t}:</p>
                            <span>{$order.price_buyer} р.</span>
                        </div>
                        <div class="t-order-card_end-sum">
                            <p>{t}Стоимость доставки для конечного покупателя{/t}:</p>
                            <span>{$order.price_delivery_buyer} р.</span>
                        </div>
                        <div class="t-order-card_end-sum">
                            <p>{t}Предоплата от покупателя{/t}:</p>
                            <span>{$order.prepay_buyer} р.</span>
                        </div>
                        <div class="t-order-card_end-sum">
                            <p>{t}К оплате покупателю{/t}:</p>
                            <span>{$order.price_buyer + $order.price_delivery_buyer - $order.prepay_buyer } р.</span>
                        </div>

                    {/if}
                    <div class="t-order_button-block">
                        {if $order->canOnlinePay()}
                            <a href="{$order->getOnlinePayUrl()}" class="link link-more">{t}Перейти к оплате{/t}</a>
                        {else}
                            <a href="{$router->getRootUrl()}" class="link link-more">{t}Завершить заказ{/t}</a>
                        {/if}
                    </div>

                </div>

        </form>

    </div>
</div>