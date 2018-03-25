{addjs file="jquery.slider.min.js" no_compress=true}
{addjs file="{$mod_js}jquery.filter.js" basepath="root"}
{assign var=catalog_config value=ConfigLoader::byModule('catalog')}

<section class="filterSection" data-query-value="{$url->get('query', $smarty.const.TYPE_STRING)}">
    <div class="loadOverlay"></div>
    <a href="#" class="blackLink filterHandler rs-parent-switcher" data-cookie-id="sideFilter" data-on-text="Свернуть расширенный фильтр"><span>{t}Развернуть расширенный фильтр{/t}</span></a>
    <form method="GET" class="filters" action="{urlmake pf=null bfilter=null p=null escape=true}" autocomplete="off">
        {if $param.show_cost_filter}
            <div class="filter typeInterval">
                <h4>{t}Цена:{/t}</h4>
                {if $catalog_config.price_like_slider && ($moneyArray.interval_to>$moneyArray.interval_from)} {* Если нужно показать как слайдер*}
                    <input type="hidden" data-slider='{ "from":{$moneyArray.interval_from}, "to":{$moneyArray.interval_to}, "step": "{$moneyArray.step}", "round": {$moneyArray.round}, "dimension": " {$moneyArray.unit}", "heterogeneity": [{$moneyArray.heterogeneity}]  }' value="{$basefilters.cost.from|default:$moneyArray.interval_from};{$basefilters.cost.to|default:$moneyArray.interval_to}" class="pluginInput" data-closest=".fromToPrice" data-start-value="{$basefilters.cost.from|default:$moneyArray.interval_from};{$basefilters.cost.to|default:$moneyArray.interval_to}"/>
                {/if}
                <table class="fullwidth fromToLine">
                    <tr>
                        <td class="p50">{t}от{/t}</td>
                        <td></td>
                        <td class="p50">{t}до{/t}</td>
                    </tr>
                    <tr>
                        <td><input type="number" min="{$moneyArray.interval_from}" max="{$moneyArray.interval_to}" class="textinp fromto" name="bfilter[cost][from]" value="{if !$catalog_config.price_like_slider}{$basefilters.cost.from}{else}{$basefilters.cost.from|default:$moneyArray.interval_from}{/if}" data-start-value="{if $catalog_config.price_like_slider}{$moneyArray.interval_from|intval}{/if}"></td>
                        <td class="padd4">&mdash;</td>
                        <td><input type="number" min="{$moneyArray.interval_from}" max="{$moneyArray.interval_to}" class="textinp fromto" name="bfilter[cost][to]" value="{if !$catalog_config.price_like_slider}{$basefilters.cost.to}{else}{$basefilters.cost.to|default:$moneyArray.interval_to}{/if}" data-start-value="{if $catalog_config.price_like_slider}{$moneyArray.interval_to|intval}{/if}"></td>
                    </tr>
                </table>
            </div>
        {/if}
        {if $param.show_is_num}
            <div class="filter">
                <h4>{t}Наличие{/t}:</h4>
                <select class="yesno" name="bfilter[isnum]" data-start-value="">
                     <option value="">{t}Неважно{/t}</option>
                     <option value="1" {if $basefilters.isnum == '1'}selected{/if}>{t}Есть{/t}</option>
                     <option value="0" {if $basefilters.isnum == '0'}selected{/if}>{t}Нет{/t}</option>
                </select>
            </div>
        {/if}
        {if $param.show_brand_filter && count($brands)>1}
            <div class="filter typeMultiselect">
                <h4>{t}Бренд{/t}: <a class="removeBlockProps hidden" title="{t}Сбросить выбранные параметры{/t}"></a></h4>
                <ul class="propsContentSelected hidden"></ul>
                <div class="propsContainer">
                    <ul class="propsContent">
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
        {/if}
        {foreach $prop_list as $item}
        {foreach $item.properties as $prop}
            {* Подключаем фильтры по характеристикам *}
            {include file="%catalog%/blocks/sidefilters/type/{$prop.type}.tpl"}
        {/foreach}
        {/foreach}
        <input type="submit" value="Применить" class="onemore submitFilter">
        <a href="{urlmake pf=null p=null bfilter=null escape=true}" class="onemore cleanFilter{if empty($filters) && empty($basefilters)} hidden{/if}">{t}очистить фильтр{/t}</a>
        
        <script type="text/javascript">
            $(function() {
                $('.typeInterval .pluginInput').each(function() {
                    var $this = $(this);
                    var fromTo = $this.siblings('.fromToLine');
                    $this.jslider( $.extend( $(this).data('slider'), { callback: function(value) {
                        var values = value.split(';');
                        $('input[name$="[from]"]', fromTo).val(values[0]);
                        $('input[name$="[to]"]', fromTo).val(values[1]);
                        $this.trigger('change');
                    }}));
                    
                    $('input[name$="[from]"], input[name$="[to]"]', fromTo).change(function() {
                        var from = $('input[name$="[from]"]', fromTo).val();
                        var to = $('input[name$="[to]"]', fromTo).val();
                        $this.jslider('value', from, to);
                    });
                });
            });
        </script>
    </form>
</section>