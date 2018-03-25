{$steps=[["key" => "address", "text" => "Адрес и<br> контакты"]]}
{$config=$this_controller->getModuleConfig()}
{if !$config.hide_delivery}{$steps[]=["key" => "delivery", "text" => "Доставка"]}{/if} 
{if !$config.hide_payment}{$steps[]=["key" => "payment", "text" => "Оплата"]} {/if}   
{$steps[]=["key" => "confirm", "text" => "Подтверждение"]} 
{$cnt=count($steps)}

<ul class="checkoutSteps nstep{$step}">
    {foreach $steps as $n=>$item}
    <li class="step{$n+1}{if $step==$n+1} current{/if}{if $step>$n+1} already{/if}">
        {if $n+1>$step || $step>$cnt}<span class="item">{else}
        <a class="item" href="{$router->getUrl('shop-front-checkout', ['Act' => $item.key])}">
        {/if}
        <span class="circle">{$n+1}</span><br>
        <span>{$item.text}</span>
        {if $n>0}<i class="arrow"></i>{/if}
        {if $n+1>$step || $step>$cnt}</span>{else}</a>{/if}
    </li>
    {/foreach}               
</ul>