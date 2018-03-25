{addjs file="jquery.changeoffer.js"}
{$shop_config=ConfigLoader::byModule('shop')}
{$check_quantity=$shop_config.check_quantity}
{$list = $this_controller->api->addProductsDirs($list)}
{if $THEME_SETTINGS.enable_favorite}
{$list = $this_controller->api->addProductsFavorite($list)}
{/if}

{if $no_query_error}
    <div class="noQuery">
        Не задан поисковый запрос
    </div>      
{else}
    <div id="products" {if $shop_config}class="shopVersion"{/if}>
        {if !empty($query)}
            <h1>Результаты поиска</h1>
        {else}
            <h1>{$category.name}</h1>
        {/if}
        {if $category.description && !$THEME_SETTINGS.cat_description_bottom}<article class="categoryDescription">{$category.description}</article>{/if}
        {if count($sub_dirs)}{assign var=one_dir value=reset($sub_dirs)}{/if}
        {if empty($query) || (count($sub_dirs) && $dir_id != $one_dir.id)}
            <nav class="subCategory">
                {foreach $sub_dirs as $item}
                    <a href="{urlmake category=$item._alias p=null pf=null bfilter=null}">{$item.name}</a>
                {/foreach}
            </nav>
        {/if}

        {if count($list)}
            {hook name="catalog-list_products:options" title="{t}Просмотр категории продукции:параметры отображения{/t}"}
                <div class="sortLine">
                    <div class="viewAs">
                        <a href="{urlmake viewAs=table}" class="viewAs table{if $view_as == 'table'} act{/if}" rel="nofollow"></a>
                        <a href="{urlmake viewAs=blocks}" class="viewAs blocks{if $view_as == 'blocks'} act{/if}" rel="nofollow"></a>                
                    </div>
                    <div class="sort">
                        Сортировать по 
                        <div class="ddList">
                            <span class="value">{if $cur_sort=='sortn'}{t}умолчанию{/t}
                                                {elseif $cur_sort=='dateof'}{t}по дате{/t}
                                                {elseif $cur_sort=='rating'}{t}популярности{/t}
                                                {elseif $cur_sort=='title'}{t}названию{/t}
                                                {elseif $cur_sort=='num'}{t}наличию{/t}
                                                {elseif $cur_sort=='rank'}{t}релевантности{/t}
                                                {else}{t}цене{/t}{/if}</span>
                            <ul>
                                <li><a href="{urlmake sort="sortn" nsort=$sort.sortn}" class="item{if $cur_sort=='sortn'} {$cur_n}{/if}" rel="nofollow">{t}умолчанию{/t}</a></li>
                                <li><a href="{urlmake sort="cost" nsort=$sort.cost}" class="item{if $cur_sort=='cost'} {$cur_n}{/if}" rel="nofollow">{t}цене{/t}</a></li>
                                <li><a href="{urlmake sort="rating" nsort=$sort.rating}" class="item{if $cur_sort=='rating'} {$cur_n}{/if}" rel="nofollow">{t}популярности{/t}</a></li>
                                <li><a href="{urlmake sort="dateof" nsort=$sort.dateof}" class="item{if $cur_sort=='dateof'} {$cur_n}{/if}" rel="nofollow">{t}дате{/t}</a></li>
                                <li><a href="{urlmake sort="num" nsort=$sort.num}" class="item{if $cur_sort=='num'} {$cur_n}{/if}" rel="nofollow">{t}наличию{/t}</a></li>
                                <li><a href="{urlmake sort="title" nsort=$sort.title}" class="item{if $cur_sort=='title'} {$cur_n}{/if}" rel="nofollow">{t}названию{/t}</a></li>
                                {if $can_rank_sort}
                                <li><a href="{urlmake sort="rank" nsort=$sort.rank}" class="item{if $cur_sort=='rank'} {$cur_n}{/if}" rel="nofollow">{t}релевантности{/t}</a></li>
                                {/if}                                    
                            </ul>
                        </div>
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
            {/hook}    
        
            {include file="list_products_items.tpl"}

        {else}    
            <div class="noProducts">
                {if !empty($query)}
                Извините, ничего не найдено
                {else}
                В данной категории нет ни одного товара
                {/if}
            </div>
        {/if}
        {if $category.description && $THEME_SETTINGS.cat_description_bottom}<article class="categoryDescription">{$category.description}</article>{/if}
    </div>
{/if}