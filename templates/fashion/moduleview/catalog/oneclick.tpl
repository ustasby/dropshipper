{assign var=catalog_config value=$this_controller->getModuleConfig()} 
<div class="oneClickWrapper">
    {if $success}
        <div class="authorization reserveForm">
            <h1 data-dialog-options='{ "width": "400" }'>Заказ принят</h1>
            <div class="infotext white">
                <p class="title">{$product.title}<br>
                Артикул:{$product.barcode}</p>            
                В ближайшее время с Вами свяжется наш менеджер
            </div>
        </div>    
    {else}
        <form enctype="multipart/form-data" method="POST" action="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id])}" class="authorization formStyle reserveForm">
           {$this_controller->myBlockIdInput()}
           <input type="hidden" name="product_name" value="{$product.title}"/>
           <input type="hidden" name="offer_id" value="{$offer_fields.offer_id}">
           {hook name="catalog-oneclick:form" title="{t}Купить в один клик:форма{/t}"} 
           <h1 data-dialog-options='{ "width": "400" }'>Купить в один клик</h1>               
           <p class="infotext">
                Оставьте Ваши данные и наш консультант с вами свяжется.
           </p>
           <div class="forms">
               {if $error_fields}
                   <div class="pageError">
                   {foreach $error_fields as $error_field}
                       {foreach $error_field as $error}
                            <p>{$error}</p>
                       {/foreach}
                   {/foreach}
                   </div>
               {/if}
                  {if $product->isMultiOffersUse()}
                        <div class="formLine">
                            {$product.offer_caption|default:'Комплектация'}
                        </div>
                        {assign var=offers_levels value=$product.multioffers.levels} 
                        {foreach $offers_levels as $level}
                            <div class="formLine">
                                <label class="fieldName">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</label>
                                <input name="multioffers[{$level.prop_id}]" value="{$offer_fields.multioffer[$level.prop_id]}" readonly>
                            </div>
                        {/foreach}
                   {elseif $product->isOffersUse()}
                        {assign var=offers value=$product.offers.items}
                        <div class="formLine">
                            <label class="fieldName">{$product.offer_caption|default:'Комплектация'}</label>
                            <input name="offer" value="{$offer_fields.offer}" readonly>
                        </div>
                   {/if}
                   
                   <div class="formLine">
                        <label class="fieldName">Ваше имя</label>
                        <input type="text" class="inp {if $error_fields}has-error{/if}" value="{if $request->request('name','string')}{$request->request('name','string')}{else}{$click.name}{/if}" maxlength="100" name="name">
                    </div>
                    <div class="formLine">
                        <label class="fieldName">Телефон</label>
                        <input type="text" class="inp {if $error_fields}has-error{/if}" value="{if $request->request('phone','string')}{$request->request('phone','string')}{else}{$click.phone}{/if}" maxlength="20" name="phone">
                    </div>
                   {foreach $oneclick_userfields->getStructure() as $fld}
                   <div class="formLine">
                        <label class="fieldName">{$fld.title}</label>
                        {$oneclick_userfields->getForm($fld.alias)}
                    </div>
                    {/foreach}
                    {if !$is_auth}
                    <div class="formLine captcha">
                        <label class="fieldName">{$click->__kaptcha->getTypeObject()->getFieldTitle()}</label>
                        {$click->getPropertyView('kaptcha')}
                    </div>
                   {/if}
                   <div class="buttons">
                       {if $CONFIG.enable_agreement_personal_data}
                           {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Купить{/t}"}
                       {/if}
                        <input type="submit" value="Купить">
                        <span class="unobtainable">Нет в наличии</span>
                   </div>
               </div>
           {/hook}
        </form>
    {/if}
</div>