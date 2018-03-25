{addjs file="%catalog%/jquery.favorite.js"}

<div id="favoriteBlock" class="floatFavorite{if $countFavorite} active{/if}">
    <a class="favoriteLink" data-href="{$router->getUrl('catalog-front-favorite')}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
        <span class="text">{t}Избранное{/t}</span>
        <i class="icon"></i>
    </a>
    <div class="countFavorite">{$countFavorite}</div>
</div>