{addcss file="%catalog%/virtual_dir.css"}
{addjs file="%catalog%/virtual_dir.js"}
{$brands = $field->callPropertyFunction('getBrands')}
{$properties = $field->callPropertyFunction('getProperties')}

<div class="virtual-dir" id="virtualDir" data-urls='{ "addPropertyUrl": "{adminUrl do="addVirtualDirPropery"}" }'>
    <div class="vt-switcher">
        <input type="checkbox" name="is_virtual" class="is-virtual" value="1" {if $elem.is_virtual}checked{/if} id="is-virtual">
        <label for="is-virtual">{t}Включить подбор товаров{/t}</label>
        <div class="fieldhelp">
            {t}Включение опции означает, что при просмотре данной категории будут отображены товары, соответствующие указанным критериям. Выбранные товары могут находиться в том числе и в других категориях, таким образом вы сможете создать "виртуальную" категорию с нужной выборкой товаров, уникальными meta-тегами и URL-адресом.{/t}
        </div>
    </div>
    <div class="vt-fields" {if !$elem.is_virtual}style="display:none"{/if}>
        <table class="otable">
            <tr>
                <td class="otitle">{t}Категория{/t}
                    <div class="fieldhelp">{t}Удерживая CTRL можно <br>выбрать несколько категорий{/t}</div>
                </td>
                <td>
                    <select name="virtual_data_arr[dirs][]" size="10" multiple style="min-width:250px">
                        {html_options options=$elem.__parent->getList() selected=$elem.virtual_data_arr.dirs}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="otitle">{t}Бренд{/t}
                    <div class="fieldhelp">{t}Удерживая CTRL можно <br>выбрать несколько брендов{/t}</div>
                </td>
                <td>
                    <select name="virtual_data_arr[brands][]" size="10" multiple style="min-width:250px">
                        {html_options options=$brands selected=$elem.virtual_data_arr.brands}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="otitle" style="vertical-align:top; line-height:30px;">{t}Характеристики{/t}</td>
                <td>         
                    <select class="vt-add-prop-id">
                        {html_options options=$properties}
                    </select>
                    <a class="btn btn-default vt-add-prop">{t}Добавить фильтр по характеристике{/t}</a>

                    <div class="vt-props">
                        {if $elem.virtual_data_arr.properties}
                            {foreach $elem.virtual_data_arr.properties as $prop_id => $data}
                                {$elem->getVirtualDir()->getPropertyFilterForm($prop_id, $data)}
                            {/foreach}
                        {/if}
                        <div class="vt-props-item vt-empty {if $elem.virtual_data_arr.properties}hidden{/if}">
                            {t}Характеристики не выбраны{/t}
                        </div>                        
                    </div>       
                </td>
            </tr>
        </table>
    </div>
</div>