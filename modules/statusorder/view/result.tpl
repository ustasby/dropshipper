{if $order_status!=''}
<p>Статус заказа №{$order_number} - <span class="order-status__span" style="color: {$order_status_color}">{$order_status}</span></p>
{else}
<div class="order-status__error">Заказ не найден</div>
{/if}
{if $tracking!=''}
<p class="order-status__info">Информация о доставке {$delivery}:</p>
<div class="order-status__tracking">
{if is_array($tracking)}
<div class="page-track">
	<div class="checkpoints">
	<ul class="tracking checkpoints">
	{foreach from=$tracking item=item key=i name=check}
		<li style="display: table; width:100%;table-layout: fixed;">
            <span class="datetime">
                <span class="date">{$item["date"]}</span>
                <span class="time">{$item["time"]}</span>
            </span>
            <span class="status {$item["status"]}">
            </span>
            <span class="info">
    	        {$item["title"]}
            	<em>
                	{$item["address"]}
            	</em>
            </span>
        </li>		
	{/foreach}
	</ul>
	</div>
</div>
{else}
<div class="page-track">
{$tracking}
</div>
{/if}
</div>
{/if}