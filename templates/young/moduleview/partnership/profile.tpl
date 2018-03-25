{if count($partner->getNonFormErrors())>0}
    <div class="pageError">
        {foreach $partner->getNonFormErrors() as $item}
        <p>{$item}</p>
        {/foreach}
    </div>
{/if}    

{if $result}
    <div class="formResult success">{$result}</div>
{/if}

<form method="POST" enctype="multipart/form-data" class="formStyle profile">
    {$this_controller->myBlockIdInput()}
    <div class="formLine">    
        <label class="fielName">{t}Увеличение стоимости, в %{/t}</label><br>
        {$partner->getPropertyView('price_inc_value')}
        <div class="help">{t}Число от 0 до 100{/t}</div>
    </div>
    <div class="formLine">    
        <label class="fielName">{t}Логотип{/t}</label><br>
        {$partner->getPropertyView('logo')}
        <div class="help">{t}Изображение в форматах GIF, PNG, JPG{/t}</div>
    </div>    
    <div class="formLine">    
        <label class="fielName">{t}Слоган{/t}</label><br>
        {$partner->getPropertyView('slogan')}
    </div>        
    <div class="formLine">    
        <label class="fielName">{t}Контактная информация{/t}</label><br>
        <textarea style="width:100%; height:200px" name="contacts">{$partner.contacts}</textarea>
    </div>            

    <div class="buttons cboth">
        <input type="submit" value="{t}Сохранить{/t}">
    </div>
</form>