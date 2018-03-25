<div class="topicView">
    {foreach $list as $item}
        {$user=$item->getUser()}
        {if $item@first}
            <p class="date">{$item.dateof|dateformat:"%e %v %Y, в %H:%M"}</p>
            <h2>{$topic.title}</h2>
            <p class="quest">{$item.message}</p>
        {else}
            <div class="message">
                <div class="author">
                    <span class="user">{if $item.is_admin}{$user.name} {$user.surname}, {t}администратор{/t}{else}{t}Я{/t}{/if}</span>
                    <span class="date">{$item.dateof|dateformat:"%e %v %Y, в %H:%M"}</span>
                </div>
                <div class="content">
                    <i class="corner"></i>
                    <p>{$item.message}</p>
                </div>
            </div>
        {/if}
    {/foreach}               
    <form method="POST" class="answer formStyle">
        {if count($supp->getErrors())>0}
            <div class="pageError">
                {foreach $supp->getErrors() as $err}
                <p>{$err}</p>
                {/foreach}
            </div>
        {/if}     
        <div class="formLine">
            <label>{t}Ответить{/t}</label><br>
            <textarea name="message">{$supp.message}</textarea><br>
        </div>
        <div class="formLine alignRight">
            <input type="submit" value="{t}Отправить{/t}">
        </div>
    </form>
</div>
