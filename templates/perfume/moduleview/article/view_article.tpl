<article>
    <p class="date">{$article.dateof|dateformat:"@date @time"}</p>
    <h1>{$article.title}</h1>
    
    {if !empty($article.image)}
    <img class="mainImage" src="{$article.__image->getUrl(700, 304, 'xy')}" alt="{$article.title}"/>
    {/if}
    {$article.content}
</article>
{moduleinsert name="\Photo\Controller\Block\PhotoList" type="article" route_id_param="article_id"}