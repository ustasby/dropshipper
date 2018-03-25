{if count($order_list)}
<div class="rsTabs">
    <ul class="tabList">
        <li class="act"><a href="">{t}Заказы{/t}</a></li>
    </ul>
    <div class="tab act">
        <table class="themeTable orderTable">
            {foreach $order_list as $order}
                {$cart=$order->getCart()}
                {$products=$cart->getProductItems()}
                {$order_data=$cart->getOrderData()}
                
                {$products_first=array_slice($products, 0, 5)}
                {$products_more=array_slice($products, 5)}            
            <tr>
                <td class="number">
                    <div class="info">
                        <a href="{$router->getUrl('shop-front-myorderview', ["order_id" => $order.order_num])}" class="more">№ {$order.order_num}</a>
                        <span class="date">{$order.dateof|date_format:"d.m.Y"}</span>
                    </div>
                    <span class="orderStatus" style="background: {$order->getStatus()->bgcolor}">{$order->getStatus()->title}</span>
                </td>
                <td class="items">
                    {hook name="shop-myorders:products" title="{t}Мои заказы:список товаров одного заказа{/t}"}
                        <ul>
                            {foreach $products_first as $item}
                            {$multioffer_titles=$item.cartitem->getMultiOfferTitles()}
                            <li>
                                {$main_image=$item.product->getMainImage()}
                                {if $item.product.id>0}
                                    <a href="{$item.product->getUrl()}" class="image"><img src="{$main_image->getUrl(56, 56, 'xy')}" alt="{$main_image.title|default:"{$item.cartitem.title}"}"/></a>
                                    <a href="{$item.product->getUrl()}" class="title">{$item.cartitem.title}</a>
                                {else}
                                    <span class="image"><img src="{$main_image->getUrl(56, 56, 'xy')}" alt="{$main_image.title|default:"{$item.cartitem.title}"}"/></span>
                                    <span class="title">{$item.cartitem.title}</span>
                                {/if}
                                {if $multioffer_titles || $item.cartitem.model}
                                    <div class="multioffersWrap">
                                        {foreach from=$multioffer_titles item=multioffer}
                                        {$multioffer.value}{if !$multioffer@last}, {/if}
                                        {/foreach}
                                        {if !$multioffer_titles}
                                            {$item.cartitem.model}
                                        {/if}
                                    </div>
                                {/if}
                            </li>
                            {/foreach}
                        </ul>
                        {if !empty($products_more)}
                        <div class="moreItems">
                            <a class="expand rs-parent-switcher">{t}показать все{/t}...</a>
                            <ul class="items">
                                {foreach $products_more as $item}
                                <li>
                                    {$main_image=$item.product->getMainImage()}
                                    {if $item.product.id>0}
                                        <a href="{$item.product->getUrl()}" class="image"><img src="{$main_image->getUrl(56, 56, 'xy')}" alt="{$main_image.title|default:"{$item.cartitem.title}"}"/></a>
                                        <a href="{$item.product->getUrl()}" class="title">{$item.cartitem.title}</a>
                                    {else}
                                        <span class="image"><img src="{$main_image->getUrl(56, 56, 'xy')}" alt="{$main_image.title|default:"{$item.cartitem.title}"}"/></span>
                                        <span class="title">{$item.cartitem.title}</span>
                                    {/if}
                                </li>
                                {/foreach}
                            </ul>
                            <a class="collapse rs-parent-switcher">{t}показать кратко{/t}</a>
                        </div>
                        {/if}
                    {/hook}
                </td>
                <td class="price">{$order_data.total_cost}</td>
                <td class="actions">
                    {hook name="shop-myorders:actions" title="{t}Мои заказы:действия над одним заказом{/t}"}
                        {if $order->getPayment()->hasDocs()}
                            {assign var=type_object value=$order->getPayment()->getTypeObject()}
                            {foreach $type_object->getDocsName() as $key=>$doc}
                            <a href="{$type_object->getDocUrl($key)}" target="_blank">{$doc.title}</a><br>
                            {/foreach}            
                        {/if}
                        {if $order->canOnlinePay()}
                            <a href="{$order->getOnlinePayUrl()}">{t}оплатить{/t}</a><br>
                        {/if}
                    {/hook}
                    <a href="{$router->getUrl('shop-front-myorderview', ["order_id" => $order.order_num])}" class="more">{t}подробнее{/t}</a>
                </td>
            </tr>
            {/foreach}
        </table>
    </div>
</div>
{else}
<div class="noEntity">
    {t}Еще не оформлено ни одного заказа{/t}
</div>
{/if}
{include file="%THEME%/paginator.tpl"}