{$steps=[["key" => "address", "text" => t("Адрес и<br> контакты")]]}
{$config=$this_controller->getModuleConfig()}
{if !$config.hide_delivery}{$steps[]=["key" => "delivery", "text" => t("Доставка")]}{/if}
{if !$config.hide_payment}{$steps[]=["key" => "payment", "text" => t("Оплата")]}{/if}
{$steps[]=["key" => "confirm", "text" => t("Подтверждение")]}
{$cnt=count($steps)}
{if $THEME_SHADE != 'yellow'}
    {$SHADE="{$THEME_SHADE}/"}
{/if}

<ul class="steps">
    {foreach $steps as $n=>$item}
    <li class="{if $n>$cnt-2}last{/if}{if $step>=$n+1} already{/if}">
        {if $n+1>$step || $step>$cnt}<span class="item">{else}
        <a class="item" href="{$router->getUrl('shop-front-checkout', ['Act' => $item.key])}">
        {/if}
        <span class="img"><img src="{$THEME_IMG}/{if $step>=$n+1}{$SHADE}{/if}co_step{$n+1}{if $step>=$n+1}_act{/if}.png" alt="{$item.text}"/></span><br>
        <span>{$item.text}</span>
        {if $n+1>$step || $step>$cnt}</span>{else}</a>{/if}
    </li>
    {/foreach}               
</ul>