<div id="merecommended">
    <table class="otable">                                              
        <tr class="editrow">
            <td class="ochk" width="20">
                <input id="me-product-recommended" title="{t}Отметьте, чтобы применить изменения по этому полю{/t}" type="checkbox" class="doedit" name="doedit[]" value="{$elem.__recommended_arr->getName()}" {if in_array($elem.__recommended_arr->getName(), $param.doedit)}checked{/if}></td>
            <td class="otitle"><label for="me-product-recommended">{t}Изменить рекомендуемые товары{/t}</label></td>
            <td>
                <div class="multi_edit_rightcol coveron">
                    <div class="cover"></div>
                    {$elem->getProductsDialog()->getHtml()}
                </div>        
            </td>
        </tr>
    </table>
</div>