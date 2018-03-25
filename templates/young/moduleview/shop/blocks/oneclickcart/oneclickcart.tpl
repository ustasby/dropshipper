{if $success}
    <p class="oneClickCartSuccess">{t}Спасибо!{/t}<br/> {t}В ближайшее время с Вами свяжется наш менеджер.{/t}</p>
{else}
    {assign var=catalog_config value=ConfigLoader::byModule('catalog')} 
    <div class="oneClickCart">
        <div id="toggleOneClickCart" class="oneClickCartWrapper" style="display:none;">
            <div class="togglePhoneWrapper formStyle "> 
                <form class="oneClickCartForm forms" action="{$router->getUrl('shop-block-oneclickcart')}">
                    {$this_controller->myBlockIdInput()}
                    <div class="center">
                        {if !empty($errors)}
                            <p class="pageError">
                            {foreach $errors as $error}
                                {$error}<br>
                            {/foreach}
                            </p>
                        {/if}                    
                        <div class="formLine">
                            <label class="fielName">{t}Ваше имя{/t}</label><br>
                            <input type="text" value="{$name}" size="30" maxlength="100" name="name" class="inp"/>
                        </div>
                        <div class="formLine">
                            <label class="fielName">{t}Ваш телефон{/t}</label><br>
                            <input type="text" value="{$phone}" size="30" maxlength="100" name="phone" class="inp"/>
                        </div>
                        {foreach from=$oneclick_userfields->getStructure() item=fld}
                            <div class="formLine">
                                <label class="fielName">{$fld.title}</label><br>
                                {$oneclick_userfields->getForm($fld.alias)}
                            </div>
                        {/foreach}
                        {if !$is_auth && $use_captcha && ModuleManager::staticModuleEnabled('kaptcha')}
                           <div class="email-captcha">
                               <label class="hidden-xs">{t}Введите код, указанный на картинке{/t}</label>
                               <img height="42" width="100" src="{$router->getUrl('kaptcha', ['rand' => rand(1, 9999999)])}" alt=""/>
                               <input type="text" name="kaptcha" class="kaptcha">
                           </div>
                        {/if}
                        <div class="oh">
                            <input type="submit" value="{t}Отправить{/t}"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(function(){ 
            $.oneClickCart('bindChanges');
        });
    </script>
{/if}
