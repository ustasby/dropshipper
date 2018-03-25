{static_call var=warehouses callback=['\Catalog\Model\WareHouseApi', 'getWarehousesList']}
<div id="meNum" class="multi_edit_rightcol coveron menum">
    {if !empty($warehouses)}
        {foreach $warehouses as $warehouse} 
            <div>                                           
                 <p class="label">{$warehouse.title}</p>       
                 <input type="text" name="num[{$warehouse.id}]" value=""/>
            </div>
        {/foreach}
    {/if}
</div>
     