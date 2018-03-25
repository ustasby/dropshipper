{if !empty($photos)}
<div class="photoList">
    <h2>{t}Фото{/t}</h2>
    <ul>
        {foreach $photos as $photo}
        <li><a href="{$photo->getUrl(800, 600)}" title="{$photo.title}" class="photoitem" rel="photolist"><img src="{$photo->getUrl(64, 64)}" alt="{$photo.title}"/></a></li>
        {/foreach}             
    </ul>
</div>
<script type="text/javascript">
$(function() {
   $('.photoitem').colorbox({
       className: 'titleMargin',
       opacity:0.2
   });
});
</script>
{/if}