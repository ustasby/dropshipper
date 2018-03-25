{if $params.order_consistent && $order.totalcost>0 && !$order.is_payed}
    <p><a data-href="{adminUrl do=orderQuery type="payment"
                                    order_id=$order.id
                                    operation="orderpay"
                                    payment_id={$order.payment}
                                    cost=$order.totalcost}" id="pay-from-personal-account" class="f-12 u-link">{t summ={$order.totalcost|format_price}}Списать с лицевого счета %summ{/t} {$order.currency_stitle}</a></p>

    <script>
        $(function() {
            $('#pay-from-personal-account').click(function() {
                if (confirm(lang.t('Вы действительно желаете оплатить заказ с лицевого счета пользователя на сумму %summ %curr', { summ: '{$order.totalcost|format_price}', curr:'{$order.currency_stitle}' }))) {
                    $.ajaxQuery({
                        url: $(this).data('href'),
                        success: function(response) {
                            if (response.success) {
                                $('input[name="is_payed"]').prop('checked', true);
                            }

                            $.orderEdit('refresh');
                        }
                    });
                } else {
                    return false;
                }

            });
        });
    </script>
{/if}