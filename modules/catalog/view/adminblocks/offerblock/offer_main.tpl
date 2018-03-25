<h3>{t}Основная комплектация{/t}</h3>
<div class="main-offer-back">
    <table class="main-offer">
        <tbody class="offer-item">
            <tr>
               <td class="td title-td col2" rowspan="2">
                    <input type="hidden" name="offers[main][id]" value="{$main_offer.id}"/>
                    <p class="label">{t}Название основной комплектации (используйте, если есть дополнительные комплектации){/t}</p>
                    <input type="text" class="offers_title" name="offers[main][title]" value="{$main_offer.title}"/><br/>
                    {foreach $warehouses as $warehouse}
                        <p class="label">"{$warehouse.title}" - {t}остаток{/t}</p>
                        <input name="offers[main][stock_num][{$warehouse.id}]" type="text" value="{$main_offer.stock_num[$warehouse.id]}"/><br/>
                    {/foreach}
                    <input name="offers[main][xml_id]" type="hidden" value="{$main_offer.xml_id}">

                    {$other_fields_form}
                </td>
                <td class="td keyval-td col3">
                    {include file="%system%/admin/keyvaleditor.tpl" field_name="offers[main][_propsdata]" arr=$main_offer.propsdata_arr add_button_text=t('Добавить характеристику')}
                </td>
            </tr>
            <tr>
                {* Блок с фотографиями комплектаций *}
                <td class="images-row">
                   {$images=$elem->getImages()}
                      <div class="offer-images-line">
                      {if !empty($images)}
                          {foreach $images as $image}
                             {$is_act=is_array($main_offer.photos_arr) && in_array($image.id, $main_offer.photos_arr)}
                             <a data-id="{$image.id}" data-name="offers[main][photos_arr][]" class="{if $is_act}act{/if}"><img src="{$image->getUrl(30,30,'xy')}"/></a>
                             {if $is_act}<input type="hidden" name="offers[main][photos_arr][]" value="{$image.id}">{/if}
                          {/foreach}
                      {/if}
                      </div>
                </td>
                {* Блок с фотографиями комплектаций *}
            </tr>
        </tbody>
    </table>
</div>