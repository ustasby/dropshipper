{addjs file="%catalog%/jquery.favorite.js"}

<div id="favoriteBlock" class="floatFavorite{if $countFavorite} active{/if}">
    <a class="favoriteLink" data-href="{$router->getUrl('catalog-front-favorite')}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">Избранное</a>
    <div class="countFavorite">{$countFavorite}</div>
</div>