{if !empty($photos)}
<div class="photoList">
    <h2>Фото</h2>
    <ul>
        {foreach $photos as $photo}
        <li><a href="{$photo->getUrl(800, 600)}" title="{$photo.title}" class="photoitem" rel="lightbox"><img src="{$photo->getUrl(100, 100)}"></a></li>
        {/foreach}             
    </ul>
</div>
{/if}