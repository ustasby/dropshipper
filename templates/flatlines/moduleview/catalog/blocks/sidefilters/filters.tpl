{* Фильтры по характеристикам товаров *}

{addcss file="libs/nouislider.css"}
{addjs file="libs/nouislider.min.js"}
{addjs file="libs/wnumb.js"}
{addjs file="rs.filter.js"}
{$catalog_config=ConfigLoader::byModule('catalog')}

<div class="sidebar sec-filter rs-filter-section{if $smarty.cookies.filter} expand{/if}" data-query-value="{$url->get('query', $smarty.const.TYPE_STRING)}">
    <div class="sec-filter_overlay"></div>

    <a class="sec-filter_toggle  visible-xs visible-sm" data-toggle-class="expand"
                                                        data-target-closest=".sec-filter"
                                                        data-toggle-cookie="filter">
        <i class="pe-2x pe-7s-filter pe-va"></i>
        <span class="expand-text">{t}Открыть фильтр{/t}</span>
        <span class="collapse-text">{t}Свернуть фильтр{/t}</span>
    </a>

    <form method="GET" class="filters rs-filters" action="{urlmake pf=null bfilter=null p=null}" autocomplete="off">
        <div class="sidebar_menu">

            {if $param.show_cost_filter}
                <div class="filter filter-interval rs-type-interval {if $basefilters.cost || (is_array($param.expanded) && in_array('cost', $param.expanded))}open{/if}">
                    <a class="expand">
                        <span class="right-arrow"><i class="pe-2x pe-7s-angle-down-circle"></i></span>
                        <span>{t}Цена{/t}</span>
                    </a>
                    <div class="detail">
                        {if $catalog_config.price_like_slider && ($moneyArray.interval_to>$moneyArray.interval_from)}
                            <input type="hidden" data-slider='{ "from":{$moneyArray.interval_from}, "to":{$moneyArray.interval_to}, "step": "{$moneyArray.step}", "round": {$moneyArray.round}, "dimension": " {$moneyArray.unit}", "heterogeneity": [{$moneyArray.heterogeneity}]  }' value="{$basefilters.cost.from|default:$moneyArray.interval_from};{$basefilters.cost.to|default:$moneyArray.interval_to}" class="rs-plugin-input"/>
                        {/if}

                        <div class="filter-fromto">
                            <div class="input-wrapper">
                                <label>{t}от{/t}</label>
                                <input type="number" min="{$moneyArray.interval_from}" max="{$moneyArray.interval_to}" class="rs-filter-from" name="bfilter[cost][from]" value="{if !$catalog_config.price_like_slider}{$basefilters.cost.from}{else}{$basefilters.cost.from|default:$moneyArray.interval_from}{/if}" data-start-value="{if $catalog_config.price_like_slider}{$moneyArray.interval_from|intval}{/if}">
                            </div>
                            <div class="input-wrapper">
                                <label>{t}до{/t}</label>
                                <input type="number" min="{$moneyArray.interval_from}" max="{$moneyArray.interval_to}" class="rs-filter-to" name="bfilter[cost][to]" value="{if !$catalog_config.price_like_slider}{$basefilters.cost.to}{else}{$basefilters.cost.to|default:$moneyArray.interval_to}{/if}" data-start-value="{if $catalog_config.price_like_slider}{$moneyArray.interval_to|intval}{/if}">
                            </div>
                        </div>
                    </div>
                </div>
            {/if}

            {if $param.show_is_num}
                <div class="filter filter-radio {if $basefilters.isnum != '' || (is_array($param.expanded) && in_array('num', $param.expanded))}open{/if}">
                    <a class="expand">
                        <span class="right-arrow"><i class="pe-2x pe-7s-angle-down-circle"></i></span>
                        <span>{t}Наличие{/t}</span>
                    </a>
                    <div class="detail">
                        <ul class="filter-radio_content">
                            <li>
                                <input type="radio" {if !isset($basefilters.isnum)}checked{/if} name="bfilter[isnum]" value="" data-start-value id="rb_is_num_no">
                                <label for="rb_is_num_no">{t}Неважно{/t}</label>
                            </li>
                            <li>
                                <input type="radio" {if $basefilters.isnum == '1'}checked{/if} name="bfilter[isnum]" value="1" id="rb_is_num_1">
                                <label for="rb_is_num_1">{t}Есть{/t}</label>
                            </li>
                            <li>
                                <input type="radio" {if $basefilters.isnum == '0'}checked{/if} name="bfilter[isnum]" value="0" id="rb_is_num_0">
                                <label for="rb_is_num_0">{t}Нет{/t}</label>
                            </li>
                        </ul>
                    </div>
                </div>
            {/if}

            {if $param.show_brand_filter && count($brands)>1}
                <div class="filter filter-checkbox rs-type-multiselect {if $basefilters.brand || (is_array($param.expanded) && in_array('brand', $param.expanded))}open{/if}">
                    <a class="expand">
                        <span class="right-arrow"><i class="pe-2x pe-7s-angle-down-circle"></i></span>
                        <span>{t}Бренд{/t} <span class="filter-remove rs-remove hidden" title="{t}Сбросить выбранные параметры{/t}"><i class="pe-va pe-7s-close-circle"></i></span></span>
                    </a>
                    <div class="detail">
                        <ul class="filter-checkbox_selected rs-selected hidden"></ul>
                        <div class="filter-checkbox_container">
                            <ul class="filter-checkbox_content rs-content">
                                {$i = 1}
                                {foreach $brands as $brand}
                                    <li style="order: {$i++};">
                                        <input type="checkbox" {if is_array($basefilters.brand) && in_array($brand.id, $basefilters.brand)}checked{/if} name="bfilter[brand][]" value="{$brand.id}" class="cb" id="cb_{$brand.id}_{$smarty.foreach.i.iteration}">
                                        <label for="cb_{$brand.id}_{$smarty.foreach.i.iteration}">{$brand.title}</label>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                    </div>
                </div>
            {/if}

            {foreach $prop_list as $item}
                {foreach $item.properties as $prop}
                    {include file="%catalog%/blocks/sidefilters/type/{$prop.type}.tpl"}
                {/foreach}
            {/foreach}
        </div>

        <div class="sidebar_menu_buttons">
            <button type="submit" class="theme-btn_search rs-apply-filter">{t}Применить{/t}</button>
            <button type="reset" class="theme-btn_reset rs-clean-filter{if empty($filters) && empty($basefilters)} hidden{/if}"><i class="pe-7s-close-circle"></i>{t}Сбросить фильтр{/t}</button>
        </div>
    </form>
</div>