{foreach $commentlist as $comment}
    <li {$comment->getDebugAttributes()}>
        <div class="info">
            <p class="date">{$comment.dateof|dateformat:"@date @time"}</p>
            <p class="name">{$comment.user_name}</p>
            <p class="starsSection" title="{$comment->getRateText()}"><span class="stars"><i class="mark{$comment.rate}"></i></span></p>
        </div>
        <div class="comment">
            <i class="corner"></i>
            <p>{$comment.message|nl2br}</p>
        </div>
    </li>
{/foreach}