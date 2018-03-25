{addjs file="jquery.changeoffer.js"}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
{$list = $this_controller->api->addProductsDirs($list)}

<div id="favorite" class="productList">
    <h1>Избранные товары</h1>
    {if $list}    
        <div class="sortLine">
            <div class="viewAs">
                <a href="{urlmake viewAs=table}" class="viewAs table{if $view_as == 'table'} act{/if}" rel="nofollow"></a>
                <a href="{urlmake viewAs=blocks}" class="viewAs blocks{if $view_as == 'blocks'} act{/if}" rel="nofollow"></a>                
            </div>
            <div class="pageSize">
                Показывать по 
                <div class="ddList">
                    <span class="value">{$page_size}</span>
                    <ul>
                        {foreach $items_on_page as $item}
                            <li class="{if $page_size==$item} act{/if}"><a href="{urlmake pageSize=$item}">{$item}</a></li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        </div>

        {include file="list_products_items.tpl"}
    {else}
        <div class="noProducts">
            У вас нет избранных товаров
        </div>
    {/if}
</div>