{* Просмотр списка товаров в категории, просмотр результатов поиска *}

{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
{$list = $this_controller->api->addProductsDirs($list)}

{if $THEME_SETTINGS.enable_favorite}
    {$list = $this_controller->api->addProductsFavorite($list)}
{/if}

{if $no_query_error}
    <div class="empty-list">
        {t}Не задан поисковый запрос{/t}
    </div>      
{else}
    <div id="products" class="catalog {if $shop_config}shopVersion{/if}">
        {if !empty($query)}
            <h1 class="m-t-0 hidden-xs hidden-sm">{t}Результаты поиска{/t}</h1>
        {else}
            <h1 class="m-t-0 hidden-xs hidden-sm">{$category.name}</h1>
        {/if}
        {if $category.description && $paginator->page == 1 && !$THEME_SETTINGS.cat_description_bottom}<article class="catalog-description">{$category.description}</article>{/if}

        {if count($sub_dirs)}{$one_dir = reset($sub_dirs)}{/if}
        {if empty($query) || (count($sub_dirs) && $dir_id != $one_dir.id)}
            <nav class="catalog-subcategory">
                {foreach $sub_dirs as $item}
                    <a href="{urlmake category=$item._alias p=null pf=null bfilter=null}">{$item.name}</a>
                {/foreach}
            </nav>
        {/if}

        {if count($list)}
            {hook name="catalog-list_products:options" title="{t}Просмотр категории продукции:параметры отображения{/t}"}
                <div class="catalog-sort">
                    <div class="pull-left">

                        <div class="catalog-sort_order sort-order">
                            <span class="hidden-xs">{t}Сортировать{/t}</span> {t}по{/t}
                            <div class="dropdown">
                                <span class="dropdown-toggle" data-toggle="dropdown">
                                    <span class="dashed">{if $cur_sort=='sortn'}{t}умолчанию{/t}
                                                    {elseif $cur_sort=='dateof'}{t}новизне{/t}
                                                    {elseif $cur_sort=='rating'}{t}популярности{/t}
                                                    {elseif $cur_sort=='title'}{t}названию{/t}
                                                    {elseif $cur_sort=='num'}{t}наличию{/t}
                                                    {elseif $cur_sort=='rank'}{t}релевантности{/t}
                                                    {else}{t}цене{/t}{/if}</span>
                                    <span class="sort-order_direction"><i class="pe-7s-angle-{if $cur_n == 'asc'}up{else}down{/if}"></i></span>
                                </span>

                                <ul class="dropdown-menu">
                                    <li><a data-href="{urlmake sort="sortn" nsort="asc"}" class="sort-order_item{if $cur_sort=='sortn'} {$cur_n}{/if}" rel="nofollow">{t}умолчанию{/t}</a></li>
                                    <li><a data-href="{urlmake sort="cost" nsort="asc"}" class="sort-order_item{if $cur_sort=='cost'} {$cur_n}{/if}" rel="nofollow">{t}возрастанию цены{/t}</a></li>
                                    <li><a data-href="{urlmake sort="cost" nsort="desc"}" class="sort-order_item{if $cur_sort=='cost'} {$cur_n}{/if}" rel="nofollow">{t}убыванию цены{/t}</a></li>
                                    <li><a data-href="{urlmake sort="rating" nsort="desc"}" class="sort-order_item{if $cur_sort=='rating'} {$cur_n}{/if}" rel="nofollow">{t}популярности{/t}</a></li>
                                    <li><a data-href="{urlmake sort="dateof" nsort="desc"}" class="sort-order_item{if $cur_sort=='dateof'} {$cur_n}{/if}" rel="nofollow">{t}новизне{/t}</a></li>
                                    <li><a data-href="{urlmake sort="num" nsort="desc"}" class="sort-order_item{if $cur_sort=='num'} {$cur_n}{/if}" rel="nofollow">{t}наличию{/t}</a></li>
                                    <li><a data-href="{urlmake sort="title" nsort="asc"}" class="sort-order_item{if $cur_sort=='title'} {$cur_n}{/if}" rel="nofollow">{t}названию{/t}</a></li>
                                    {if $can_rank_sort}
                                        <li><a href="{urlmake sort="rank" nsort=$sort.rank}" class="sort-order_item{if $cur_sort=='rank'} {$cur_n}{/if}" rel="nofollow">{t}релевантности{/t}</a></li>
                                    {/if}
                                </ul>
                            </div>
                        </div>

                        <div class="catalog-sort_pagesize">
                            {t}Показывать по{/t}
                            <div class="dropdown">
                                <span class="dropdown-toggle" data-toggle="dropdown"><span class="dashed">{$page_size}</span></span>
                                <ul class="dropdown-menu">
                                    {foreach $items_on_page as $item}
                                        <li><a href="{urlmake pageSize=$item}">{$item}</a></li>
                                    {/foreach}
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="pull-right">
                        <a data-href="{urlmake viewAs=table}" class="catalog-sort_list{if $view_as == 'table'} active{/if}" rel="nofollow"><i class="i-svg i-svg-view-table"></i></a>
                        <a data-href="{urlmake viewAs=blocks}" class="catalog-sort_table{if $view_as == 'blocks'} active{/if}" rel="nofollow"><i class="i-svg i-svg-view-blocks"></i></a>
                    </div>
                </div>
            {/hook}

            {include file="list_products_items.tpl"}

            <div class="pull-right">
                {include file="%THEME%/paginator.tpl"}
            </div>

        {else}    
            <div class="empty-list">
                {if !empty($query)}
                    {t}Извините, ничего не найдено{/t}
                {else}
                    {t}В данной категории нет ни одного товара{/t}
                {/if}
            </div>
        {/if}
        {if $category.description && $paginator->page == 1 && $THEME_SETTINGS.cat_description_bottom}<article class="catalog-description">{$category.description}</article>{/if}
    </div>
{/if}