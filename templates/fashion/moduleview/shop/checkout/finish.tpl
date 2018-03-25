<div class="formStyle checkoutBox">
        <h3>Спасибо! Ваш заказ успешно оформлен</h3>                
        {if $user.id}
            <p class="thanks">Следить за изменениями статуса заказа можно в разделе <a href="{$router->getUrl('shop-front-myorders')}" target="_blank">история заказов</a>. 
        {/if}
        <p>Все уведомления об изменениях в данном заказе также будут отправлены на электронную почту покупателя.</p>
        <div class="coInfo">
            {$user=$order->getUser()}
            <div class="grayblock">            
                <h2>Сведения о заказе</h2>
                <table>
                    <tr>
                        <td class="key">Заказчик</td>
                        <td>{$user.surname} {$user.name}</td>
                    </tr>
                    <tr>
                        <td class="key">Телефон</td>
                        <td>{$user.phone}</td>
                    </tr>
                    <tr class="preSep">
                        <td class="key">e-mail</td>
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
                            <td class="key">Доставка</td>
                            <td>{$delivery.title}</td>
                        </tr>
                    {/if}
                    {if $order.only_pickup_points && $order.warehouse} {* Если только самовывоз *}
                        <tr>
                            <td class="key">Пункт самовывоза</td>
                            <td>{$order->getWarehouse()->adress}</td>
                        </tr>
                    {elseif $order.use_addr}
                        <tr>
                            <td class="key">Адрес</td>
                            <td>{$address->getLineView()}</td>
                        </tr>
                    {/if}
                    {if $order.payment}
                        <tr>
                            <td class="key">Оплата</td>
                            <td>{$pay.title}</td>
                        </tr>
                    {/if}
                    {$url=$order->getTrackUrl()}
                    {if !empty($url)}
                        <tr>
                            <td class="key">Данные заказа</td>
                            <td><a href="{$url}" target="_blank">Отследить заказ</a></td>
                        </tr>
                    {/if}
                </table>
            </div>

            {if $order->getPayment()->hasDocs()}
            <div class="docs grayblock">
                <h2>Документы на оплату</h2>
                <div class="border">
                    <p>Воспользуйтесь следующими документами для оплаты заказа. Эти документы всегда доступны в разделе история заказов.</p>
                    <br>
                    <div class="textCenter">
                    {$type_object=$order->getPayment()->getTypeObject()}
                    {foreach $type_object->getDocsName() as $key => $doc}
                    <a href="{$type_object->getDocUrl($key)}" target="_blank" class="button white">{$doc.title}</a>
                    {/foreach}
                    </div>
                </div>
            </div>            
            {/if}
            
        </div>            
        
        {assign var=orderdata value=$cart->getOrderData()}
        <div class="coItems">
            <p class="orderId">Заказ N {$order.order_num}</p>
            <p class="orderDate">от {$order.dateof|date_format:"d.m.Y"}</p>
            <table class="themeTable">
                <thead>
                    <tr>
                        <td>Товар</td>
                        <td>Количество</td>
                        <td class="price">Цена</td>
                    </tr>
                </thead>
                <tbody>
                    {foreach $orderdata.items as $n=>$item}
                    {$orderitem=$item.cartitem}
                    {$barcode=$orderitem.barcode}
                    {$offer_title=$orderitem.model}
                    {$multioffer_titles=$orderitem->getMultiOfferTitles()}
                    <tr>
                        <td>{$orderitem.title}
                            <div class="codeLine">
                                {if $barcode != ''}Артикул:<span class="value">{$barcode}</span><br>{/if}
                                {if $multioffer_titles || $offer_title}
                                    <div class="multioffersWrap">
                                        {foreach $multioffer_titles as $multioffer}
                                            <p class="value">{$multioffer.title} - <strong>{$multioffer.value}</strong></p>
                                        {/foreach}
                                        {if !$multioffer_titles}
                                            <p class="value"><strong>{$offer_title}</strong></p>
                                        {/if}
                                    </div>
                                {/if}
                            </div>
                        </td>
                        <td>
                            {$orderitem.amount} {$orderitem.data.unit}
                        </td>
                        <td class="price">
                            {$item.total}
                            <div class="discount">
                                {if $item.discount>0}
                                скидка {$item.discount}
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            <br>
            <table class="themeTable">
                <tbody>
                    {foreach $orderdata.other as $item}
                    <tr>
                        <td>{$item.cartitem.title}</td>
                        <td>{if $item.total != 0}{$item.total}{/if}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            <div class="summary">
                <span class="text">Итого: </span> 
                <span class="price">{$orderdata.total_cost}</span>
            </div>
        </div>
        <div class="clearBoth"></div>
        <div class="buttons">
            {if $order->canOnlinePay()}
                <a href="{$order->getOnlinePayUrl()}" class="button color">Перейти к оплате</a>
            {else}
                <a href="{$router->getRootUrl()}" class="button color">Завершить заказ</a>
            {/if}
        </div>
</div>