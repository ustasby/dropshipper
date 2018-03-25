{addjs file="{$mod_js}comments.js" basepath="root"}
<div class="comments{if !empty($error) || !$total} on{/if}">
    <a name="comments"></a>    
    <div class="commentHead">
        <h3>Отзывы {if $total}({$total}){/if}</h3>
        <a href="#" class="addComment" onclick="$(this).closest('.comments').toggleClass('on');return false;">{t}Написать отзыв{/t}</a>
    </div>  
    <div class="writeComment">
        {if $mod_config.need_authorize == 'Y' && !$is_auth}
            <span class="needAuth">{t}Чтобы оставить отзыв необходимо авторизоваться{/t}</span>
        {else}
            {if !empty($error)}
                <div class="errors">
                    {foreach $error as $one}
                    <p>{$one}</p>
                    {/foreach}
                </div>
            {/if}                    
        
            <form method="POST" class="formStyle">               
                {$this_controller->myBlockIdInput()}
                <div class="message">
                    <i class="corner"></i>
                    <textarea name="message">{$comment.message}</textarea>
                </div>
                {if $already_write}<div class="already">{t}Разрешен один отзыв на товар, предыдущий отзыв будет заменен{/t}</div>{/if}
                <div class="rating">
                    <input class="inp_rate" type="hidden" name="rate" value="{$comment.rate}">
                    <span class="text">{t}Ваша оценка{/t}</span>
                    <span class="rate">
                        <span class="stars">
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                        </span>
                    </span>
                </div>
                <div class="nameBlock">
                    <i class="lines"></i>
                    <div class="oh {if $is_auth}authorized{/if}">
                        <div class="name">
                            <label class="fielName">{t}Ваше имя{/t}</label><br>
                            <input type="text" name="user_name" value="{$comment.user_name}">
                        </div>
                        {if !$is_auth}
                        <div class="captcha">
                            <label class="fielName">{$comment->__captcha->getTypeObject()->getFieldTitle()}</label><br>
                            {$comment->getPropertyView('captcha')}
                        </div>
                        {/if}
                    </div>
                </div>
                <div class="buttons">
                    <input type="submit" value="{t}Отправить{/t}">
                </div>
            </form>
        {/if}
    </div>
    {if $total}
    <ul class="commentList">
        {$list_html}
    </ul>
    {else}
        <div class="noComments">{t}нет отзывов{/t}</div>
    {/if}
    {if $paginator->total_pages > $paginator->page}
        <a data-pagination-options='{ "appendElement":".commentList" }' data-href="{$router->getUrl('comments-block-comments', ['_block_id' => $_block_id, 'cp' => $paginator->page+1, 'aid' => $aid])}" class="oneMore ajaxPaginator">{t}еще комментарии...{/t}</a>
    {/if}
</div>
