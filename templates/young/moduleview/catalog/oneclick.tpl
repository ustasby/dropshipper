{assign var=catalog_config value=$this_controller->getModuleConfig()} 
<div class="oneClickWrapper">
    {if $success}
        <div class="authorization reserveForm">
            <h2 data-dialog-options='{ "width": "400" }'>{t}Заказ принят{/t}</h2>
            <p class="title">
                {$product.title}<br>
                {t}Артикул:{/t}{$product.barcode}</p>
            <p class="infotext">
                {t}В ближайшее время с Вами свяжется наш менеджер.{/t}
            </p>
        </div>    
    {else}
        <form enctype="multipart/form-data" method="POST" action="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" class="authorization formStyle reserveForm">
           {$this_controller->myBlockIdInput()}
           <input type="hidden" name="product_name" value="{$product.title}"/>
           <input type="hidden" name="offer_id" value="{$offer_fields.offer_id}">
           {hook name="catalog-oneclick:form" title="{t}Купить в один клик:форма{/t}"} 
           <div class="forms">
               <h2 class="dialogTitle" data-dialog-options='{ "width": "400" }'>{t}Купить в один клик{/t}</h2>
               <p class="infotext">
                   {t}Оставьте Ваши данные и наш консультант с вами свяжется.{/t}
               </p>  
               {if $error_fields}
                   <div class="pageError"> 
                   {foreach $error_fields as $error_field}
                       {foreach $error_field as $error}
                            <p>{$error}</p>
                       {/foreach}
                   {/foreach}
                   </div>
               {/if}
               
               <div class="center">
                  {if $product->isMultiOffersUse()}
                        <div class="formLine">
                            {$product.offer_caption|default:t('Комплектация')}
                        </div>
                        {assign var=offers_levels value=$product.multioffers.levels} 
                        {foreach $offers_levels as $level}
                            <div class="formLine">
                                <label class="fielName">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</label><br>
                                <input name="multioffers[{$level.prop_id}]" value="{$offer_fields.multioffer[$level.prop_id]}" readonly>
                            </div>
                        {/foreach}
                   {elseif $product->isOffersUse()}
                        {assign var=offers value=$product.offers.items}
                        <div class="formLine">
                            <label class="fielName">{$product.offer_caption|default:t('Комплектация')}</label><br>
                            <input name="offer" value="{$offer_fields.offer}" readonly>
                        </div>
                   {/if}
                   
                   <div class="formLine">
                        <label class="fielName">{t}Ваше имя{/t}</label><br>
                        <input type="text" class="inp {if $error_fields}has-error{/if}" value="{if $request->request('name','string')}{$request->request('name','string')}{else}{$click.user_fio}{/if}" maxlength="100" name="name">
                    </div>
                    <div class="formLine">
                        <label class="fielName">{t}Телефон{/t}</label><br>
                        <input type="text" class="inp {if $error_fields}has-error{/if}" value="{if $request->request('phone','string')}{$request->request('phone','string')}{else}{$click.user_phone}{/if}" maxlength="20" name="phone">
                    </div>
                   {foreach $oneclick_userfields->getStructure() as $fld}
                   <div class="formLine">
                        <label class="fielName">{$fld.title}</label><br>
                        {$oneclick_userfields->getForm($fld.alias)}
                    </div>
                    {/foreach}
                    {if !$is_auth}
                    <div class="formLine captcha">
                        <label class="fielName">{$click->__kaptcha->getTypeObject()->getFieldTitle()}</label><br>
                        {$click->getPropertyView('kaptcha')}
                    </div>
                   {/if}
               </div>
           </div>
           <input type="submit" value="Купить">
           <span class="noProduct">{t}Нет в наличии{/t}</span>
           <br><br><br>
           {/hook}
        </form>
    {/if}
</div>