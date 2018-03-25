{if $article.id}
<div class="paymentBlock">
    <div class="image"></div>
    <div class="text">
        <p class="caption">Оплата и возврат</p>
        <div class="descr">{$article.content}</div>
    </div>
</div>
{else}
    {include file="%THEME%/block_stub.tpl"  class="blockArticlePayment" do=[
        [
            'title' => t("Добавьте статью об оплате"),
            'href' => {adminUrl do=false mod_controller="article-ctrl"}
        ],
        [
            'title' => t("Настройте блок"),
            'href' => {$this_controller->getSettingUrl()},
            'class' => 'crud-add'
        ]
    ]}
{/if}