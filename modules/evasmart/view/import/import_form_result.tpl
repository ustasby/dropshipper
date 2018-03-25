<div id="import-csv-result" class="crud-form" data-dialog-options='{ "width":500, "height":400 }'>
    {if $next_step === false}
    <br>
    <div class="inform-block">{t}Произошла ошибка:{/t} {$error}</div>
    <br>
    {else}
        {if $next_step === true}
            <div class="link-blog" >
                <span class="main-link"><a class="crud-get crud-close-dialog">{t}Закрыть окно и обновить список товаров{/t}</a></span>
            </div>
        {/if}
    {/if}


</div>
{if $next_step !== true}
<script>
    $.allReady(function() {
        $.ajaxQuery({
            url: '{adminUrl mod_controller="evasmart-importcsv" do="ajaxProcess"}',
            data: { 'step_data': {json_encode($next_step)} },
            type: 'POST',
            success: function(response) {
                $('#import-csv-result')
                    .replaceWith(response.html)
                    .trigger('new-content')
                    .trigger('initContent')
                    .trigger('contentSizeChanged');
            }
        });
    });
</script>
{/if}