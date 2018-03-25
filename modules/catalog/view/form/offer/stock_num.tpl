{static_call var=warehouses callback=['\Catalog\Model\WareHouseApi', 'getWarehousesList']}
<table class="otable offer-stock-num">
    {foreach $warehouses as $warehouse}
        <tr>
            <td class="otitle">{$warehouse.title}</td>
            <td><input name="stock_num[{$warehouse.id}]" type="text" value="{$elem.stock_num[$warehouse.id]}"/></td>
        </tr>
    {/foreach}
</table>