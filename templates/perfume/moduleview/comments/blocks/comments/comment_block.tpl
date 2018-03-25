{addjs file="{$mod_js}comments.js" basepath="root"}

<div class="commentBlock">
    <a name="comments"></a>
    <div class="commentFormBlock{if !empty($error) || !$total} open{/if}">
        {if $mod_config.need_authorize == 'Y' && !$is_auth}
            <span class="needAuth">{t alias="Комментарии - Чтобы оставить отзыв" href=$router->getUrl('users-front-auth', ['referer' => $referer])}Чтобы оставить отзыв необходимо <a href="%href" class="inDialog">авторизоваться</a>{/t}</span>
        {else}
            <a href="#" class="colorButton handler" onclick="$(this).closest('.commentFormBlock').toggleClass('open');return false;">{t}Написать отзыв и оценить{/t}</a>
            <div class="caption">
                {t}Оставить отзыв{/t}
                <a class="close" onclick="$(this).closest('.commentFormBlock').toggleClass('open');return false;"></a>
            </div>
            <form method="POST" class="formStyle" action="#comments">
                {if !empty($error)}
                    <div class="errors">
                        {foreach $error as $one}
                        <p>{$one}</p>
                        {/foreach}
                    </div>
                {/if}
                {$this_controller->myBlockIdInput()}
                <div class="oh">
                    <div class="rating">
                        <p>{t}Ваша оценка{/t}</p>
                        <input class="inp_rate" type="hidden" name="rate" value="{$comment.rate}">
                        <div class="starsBlock">
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                        </div>
                        <div class="desc">{$comment->getRateText()}</div>
                    </div>
                    <div class="formWrap">
                        <i class="corner"></i>
                        <textarea name="message">{$comment.message}</textarea>
                    </div>
                    {if $already_write}<div class="already">{t}Разрешен один отзыв , предыдущий отзыв будет заменен{/t}</div>{/if}
                </div>
                <p class="name">
                    <label>{t}Ваше имя{/t}</label>
                    <input type="text" name="user_name" value="{$comment.user_name}">
                </p>
                {if !$is_auth}
                    <div class="captcha">
                        <label class="fielName">{$comment->__captcha->getTypeObject()->getFieldTitle()}</label><br>
                        {$comment->getPropertyView('captcha')}
                    </div>
                {/if}
                <div class="buttons">
                    <input type="submit" value="{t}Оставить отзыв{/t}">
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
        <a data-pagination-options='{ "appendElement":".commentList" }' data-href="{$router->getUrl('comments-block-comments', ['_block_id' => $_block_id, 'cp' => $paginator->page+1, 'aid' => $aid])}" class="button oneMore ajaxPaginator">{t}еще комментарии{/t}...</a>
    {/if}    
</div>
<script type="text/javascript">
    $(function() {
        $('.commentBlock').comments({
            stars: '.starsBlock i',
            rateDescr: '.rating .desc'
        });
    });
</script>