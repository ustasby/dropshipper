<div class="supportTopics">
    {$error=$supp->getErrors()}
    <div class="writeBlock mwHalf {if $error}open{/if}">
        <a href="#" class="button color handler" onclick="$(this).parent().addClass('open'); return false;">Написать сообщение</a>
        <div class="caption">
            <h2>Написать сообщение в поддержку</h2>
            <a class="iconX" onclick="$(this).closest('.writeBlock').removeClass('open'); return false;"></a>
        </div>      
        <form class="formStyle" method="POST">
            {if $error}
            <div class="pageError pbottom">
                {foreach $error as $err}
                <p>{$err}</p>
                {/foreach}
            </div>
            {/if}          
            <div class="formLine">
                <label>Тема</label>
                {if count($list)>0}
                    <select name="topic_id" id="topic_id">        
                        {foreach $list as $item}
                        <option value="{$item.id}" {if $item.id == $supp.topic_id}selected{/if}>{$item.title}</option>
                        {/foreach}
                        <option value="0" {if $supp.topic_id == 0}selected{/if}>Новая тема...</option>
                    </select><br>
                {/if}
            </div>
            <div class="formLine" id="newtopic" {if $supp.topic_id>0}style="display:none"{/if}>
                <input type="text" name="topic" class="newtopic" value="{$supp.topic}">
            </div>
            <div class="formLine">
                <label>Вопрос</label><br>
                <textarea name="message">{$supp.message}</textarea>
            </div>
            <div class="formLine">
                <input type="submit" value="Отправить">
            </div>
        </form>
    </div>
    <br>
    {if count($list)>0}    
    <table class="themeTable supportTable">
        <thead>
            <tr>
                <td>Обновлено</td>
                <td>Тема обращения</td>
                <td>Сообщений</td>
            </tr>
        </thead>
        <tbody>
            {foreach $list as $item}
            <tr data-id="{$item.id}">
                <td class="datetime">
                    <p class="date">{$item.updated|date_format:"%d.%m.%Y"}</p>
                    <p class="time">{$item.updated|date_format:"%H:%M"}</p>
                </td>
                <td class="title">
                    <a href="{$router->getUrl('support-front-support', [Act=>"viewTopic", id => $item.id])}">{$item.title}</a>
                </td>
                <td class="msgCount">
                    <a href="{$router->getUrl('support-front-support', [Act=>"viewTopic", id => $item.id])}" class="number">{$item.msgcount}{if $item.newcount>0} (новых: {$item.newcount}){/if}</a>
                </td>
                <td class="remove">
                    <a href="{$router->getUrl('support-front-support', ["Act" => "delTopic", "id" => $item.id])}" class="iconRemove" title="Удалить переписку по этой теме"></a>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    {/if}
</div>
            
<script>
    $(function() {
        $('#topic_id').change(function() {
            $('#newtopic').toggle( $(this).val() == 0 );
        });
        
        $('.supportTable .iconRemove').click(function(){
            if (!confirm('Вы действительно хотите удалить переписку по теме?')) return false;
            var block = $(this).closest('[data-id]').css('opacity', 0.5);
            var topic_id = block.data('id');
            
            $.getJSON($(this).attr('href'), function(response) {
                if (response.success) {
                    location.reload();
                }
            });
            return false;
        });
    });
</script>