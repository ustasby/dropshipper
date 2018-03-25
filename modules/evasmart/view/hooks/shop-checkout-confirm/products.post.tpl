{if $current_user->isAdmin() || $current_user->inGroup('DS') }
    <div class="t-order_comments">
        <div class="form-group">
            <label>Стоимость заказа для конечного покупателя</label>
            <input name="price_buyer" value="" type="text">

        </div>
        <div class="form-group">
            <label>Стоимость доставки для конечного покупателя</label>
            <input name="price_delivery_buyer" value="" type="text">
        </div>
        <div class="form-group">
            <label>Предоплата от покупателя</label>
            <input name="prepay_buyer" value="" type="text">
        </div>

    </div>
{/if}