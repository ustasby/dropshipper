{strip}
{if count($data->oneclick.products)==1}{* Если только один товар *}
    {$first_product=current($data->oneclick.products)}
    {t d=$first_product.barcode}Ваш заказ на товар с артикулом №%d принят.{/t}
{else}
    {t}Ваш заказ принят. Скоро с Вами свяжутся.{/t}
{/if}
{/strip}