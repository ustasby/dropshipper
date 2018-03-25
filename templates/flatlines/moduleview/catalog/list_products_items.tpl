{* Список товаров, который может быть в 2х видов *}
{if $view_as == 'blocks'}
    <div class="catalog-table">
        <div class="row">
            {foreach $list as $product}
                <div class="{$item_column|default:"col-xs-12 col-sm-6 col-md-4"}">

                    {include file="%catalog%/product_in_list_block.tpl" product=$product}

                </div>
            {/foreach}
        </div>
    </div>
{else}
    <div class="catalog-list">
        <div class="row">
            {foreach $list as $product}
                <div class="col-xs-12">

                    {include file="%catalog%/product_in_list_table.tpl" product=$product}

                </div>
            {/foreach}
        </div>
    </div>
{/if}