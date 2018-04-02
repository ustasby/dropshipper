<article itemprop="description">{$product.description}

    {if $current_user->isAdmin() || $current_user->inGroup('DS') }
        <p>{$product.desc1}</p>
        <p>Крепеж: {$product.desc2}</p>
        <p>Примечание: {$product.desc3}</p>

    {/if}</article>