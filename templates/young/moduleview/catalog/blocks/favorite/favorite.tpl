{if $THEME_SETTINGS.enable_favorite}
    {addjs file="%catalog%/jquery.favorite.js" basepath="root"}
    <a id="favoriteBlock" class="favoriteLink" data-href="{$router->getUrl('catalog-front-favorite')}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
        <span class="title">{t}Избранное{/t}</span>
        <span class="countFavorite">{$countFavorite}</span>
    </a>
{/if}