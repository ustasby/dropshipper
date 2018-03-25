{addjs file="jquery.changeoffer.js"}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
{$list = $this_controller->api->addProductsDirs($list)}

<div id="favorite" {if $shop_config}class="shopVersion"{/if}>
    <h1 class="catTitle">{$category.name}</h1>
    {if $category.description}<div class="categoryDescription">{$category.description}</div>{/if}
    {if count($sub_dirs)}{assign var=one_dir value=reset($sub_dirs)}{/if}
    {if empty($query) || (count($sub_dirs) && $dir_id != $one_dir.id)}
        <nav class="subCategory">
            {foreach $sub_dirs as $item}
                <a href="{urlmake category=$item._alias p=null f=null bfilter=null}">{$item.name}</a>
            {/foreach}
        </nav>
    {/if}

    {if count($list)}
    <div class="viewOptions">
        <a href="{urlmake viewAs=table}" class="viewAs table{if $view_as == 'table'} act{/if}" rel="nofollow"></a>
        <a href="{urlmake viewAs=blocks}" class="viewAs blocks{if $view_as == 'blocks'} act{/if}" rel="nofollow"></a>                
        <div class="pageSizeBlock">
            {t}Показывать по{/t}:&nbsp;&nbsp;
            <div class="lineListBlock collapse720">
                <a class="lineTrigger rs-parent-switcher">{$page_size}</a>
                <ul class="lineList">
                    {foreach $items_on_page as $item}
                        <li><a href="{urlmake pageSize=$item}" class="item{if $page_size==$item} act{/if}"><i>{$item}</i></a></li>
                    {/foreach}
                </ul>
            </div>
        </div>
    </div>

    <div class="pagesLine before">
        {include file="%THEME%/paginator.tpl"}
        <div class="clearboth"></div>
    </div>

    <section class="catalog">
        <div class="productWrap">
            {include file="list_products_items.tpl"}
        </div>
    </section>
    {else}
        <div class="noProducts">
            {t}В избранном нет ни одного товара{/t}
        </div>
    {/if}
</div>