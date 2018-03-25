/**
* Обеспечивает работу страницы редактирования заказа в административной панели
*/
$.orderEdit = function( method ) 
{  
    var defaults = {
       
    },
    $this = $('#orderForm'),
    data = $this.data('orderEdit');
    
    if (!$this.length) return;
    if (!data) { //Инициализация
        data = { options: defaults };
        $this.data('orderEdit', data);
    }
    
    //public
    var methods = {
        init: function(initoptions) {
            data.options = $.extend(data.options, initoptions);

            $('.admin-comment-ta')
                .autogrow({vertical: true, horizontal: false})
                .keyup(function(e) {
                    $(this).parents('.admin-comment').toggleClass('filled', $(this).val() != '');
                });
            
            methods.bindEvents();

            $('html').on('change', '.pr-table .chk input[type="checkbox"]', function() {
                var is_selected = $('.pr-table tbody .chk input[type="checkbox"]:checked', $this).length>0;
                if (is_selected) {
                    $this.trigger('disableBottomToolbar', 'order-edit');
                } else {
                    $this.trigger('enableBottomToolbar', 'order-edit');
                }
            });
            
            $this
                .on('click', '.order-header .status a[data-id]', changeStatus)
                .on('change', 'select[name="use_currency"]', methods.refresh)
                .on('click', 'a.removeproduct', remove)
                .on('click', 'a.addcoupon', addCoupon)
                .on('click', 'a.addorderdiscount', addOrderDiscount)
                .on('click', 'a.addproduct', addProduct)
                .on('change', '.invalidate', invalidate)
                .on('click', '.show-change-offer', showChangeOffer)
                .on('change', '.product-offer', changeOffer)            //Смена комплектации
                .on('change', '.product-multioffer', changeMultiOffer)  //Смена значений многомерных комплектаций
                .on('click', '#printOrder', printOrder)
                .on('click', '#createUserFromNoRegister', createUserFromNoRegister)
                .on('click', '.dropdown-menu .zmdi', function(e) {
                    $(this).closest('.node').toggleClass('open');
                    e.stopPropagation();
                });
            

            $('.pr-table').lightGallery({
                selector:'a[rel="lightbox-products"]',
                thumbnail: false,
                autoplay: false,
                autoplayControls: false
            });

            checkAdditionalBlock();
        },
        
        /**
        * Привязывает события в редактировании страницы заказа
        * 
        */
        bindEvents: function() {
            $('.status-bar .node', $this).hover(function() {
                clearTimeout(this.outtimer);
                $(this).addClass('over');
            }, function() {
                var _this = this;
                this.outtimer = setTimeout(function() {
                    $(_this).removeClass('over');
                }, 200);
            })
        },
        
        /**
        * Обновялет страницу заказа
        */
        refresh: function() {
            var form = $('#orderForm'),
                data = form.serializeArray();  
            
            data.push({name:"refresh", value: 1});
            
            $.ajaxQuery({
                url: form.attr('action'),
                data: data,
                type: 'POST',
                success: function(response) {
                  form.find('.tinymce').each(function() {
                      if ($(this).tinymce()) {
                          $(this).tinymce().remove();
                      }
                  });
                  $this.trigger('enableBottomToolbar', 'order-edit');
                  form.html(response.html).trigger('new-content');
                  methods.bindEvents();
                }
            });
        }
    };
    
    //private
    var 
    genUniq = function() {
      return Math.floor((1 + Math.random()) * 0xffFFffFFff).toString(16).substring(1);
    },
    /**
    * Показвает блок изменения комплктаций и многомерных комплектаций у товара
    * 
    */
    showChangeOffer = function(){
        var item_div = $(this).closest('.item');
        $('.product-offer, .multiOffers, .change-offer-block', item_div).show();
    },
    /**
    * Смена комплектации у товара
    * 
    */
    changeOffer = function() {
        var THIS = this;
        // На смену комплектации обновляем цену
        var offer_index = $(this).val();
        
        //Поменяем единицу измерения, если это нужно
        var $selected = $('option:selected',$(THIS));
        if ((typeof($selected.data('unit')) != 'undefined') && ($selected.data('unit')!="")){
           $('.unit', $(THIS).closest('.item')).show().text($selected.data('unit'));  
        }else{
           $('.unit', $(THIS).closest('.item')).hide();  
        }
        
        $.ajaxQuery({
            url: $(this).data('url'),
            data: {offer_index: offer_index},
            type: 'POST',
            success: function(response) {
                var cost_select = $('.product-offer-cost', $(THIS).closest('.item')); 
                var apply_cost_btn = $('.apply-cost-btn', $(THIS).closest('.item')); 
                cost_select.empty();
                // Для каждой из цен этой комплектации
                for(var i in response.costs){
                    var title = response.costs[i].title;
                    var cost = response.costs[i].cost;
                    cost_select.append('<option value="'+cost+'">'+cost+' - '+title+'</option>');
                }
                // На клик по кнопке OK
                apply_cost_btn.click(function(){
                    $('input.single_cost', $(THIS).closest('.item')).val(cost_select.val());
                    methods.refresh();
                });
                cost_select.show();
                apply_cost_btn.show();
            }
        });
    },
    /**
    * Собираем информацию и сведения по комплектациям используемым для товара
    * 
    * @param jQuery object context - оборачивающий контейнер
    */
    getMultiOffersInfo = function (context){
        var multiOffersInfo = []; // Массив с информацией о комплектациях 
        
        //Соберём информацию для работы
        $('.hidden_offers',context).each(function(i){
            multiOffersInfo[i]          = {};
            multiOffersInfo[i]['id']    = this;
            multiOffersInfo[i]['info']  = $(this).data('info');
            multiOffersInfo[i]['num']   = $(this).data('num');
        });
        return multiOffersInfo;
    }
    
    /**
    * Смена значений выпадающего списка для многомерных комплектаций
    * 
    */
    changeMultiOffer = function() {
        
        var THIS    = this;
        var context = $(THIS).closest('.multiOffers');
                
        var selected = []; //Массив, что выбрано
        //Соберём информацию, что изменилось
        $('.product-multioffer',context).each(function(i){
            selected[i]          = {};
            selected[i]['title'] = $(this).data('prop-title');
            selected[i]['value'] = $(this).val();
        });
        
        //Найдём инпут с комплектацией
        var input_info = getMultiOffersInfo(context);
        var offer      = false; //Cпрятанная комплектация, которую мы выбрали
        
        for(var j=0;j<input_info.length;j++){
            var info = input_info[j]['info']; //Группа с информацией
            
            var found = 0;                //Флаг, что найдены все совпадения
            for(var m=0;m<info.length;m++){
                for(var i=0;i<selected.length;i++){
                   if ((selected[i]['title']==info[m][0])&&(selected[i]['value']==info[m][1])){
                       found++;
                   } 
                }
                if (found==selected.length){ //Если удалось найди совпадение, то выходим
                    offer = input_info[j]['id']
                    break;
                }
            }
        }
        
        //Отметим выбранную комплектацию
        var offer_val = 0;
        $('.hidden_offers').prop('selected',false);
        if (offer){ // Если комплектация выбранная присутствует
           offer_val = $(offer).val(); 
           $('.hidden_offers[value="'+offer_val+'"]',context).prop('selected',true);  
        }else{ // Если комплектации такой не нашлось, выберем нулевую компл.
           $('.hidden_offers[value="0"]',context).prop('selected',true); 
        }
        
        //Поменяем единицу измерения, если это нужно
        var $selected = $('.hidden_offers[value="'+offer_val+'"]',context);
        if ((typeof($selected.data('unit')) != 'undefined') && ($selected.data('unit')!="")){
           $('.unit', $($selected).closest('.item')).show().text($selected.data('unit'));  
        }else{
           $('.unit', $($selected).closest('.item')).hide();  
        }

        // На смену комплектации обновляем цену
        var offer_index = $('.product-offers',context).val();
        $.ajaxQuery({
            url: $(this).data('url'),
            data: {offer_index: offer_index},
            type: 'POST',
            success: function(response) {
                var cost_select    = $('.product-offer-cost', context); 
                var apply_cost_btn = $('.apply-cost-btn', context); 
                cost_select.empty();
                // Для каждой из цен этой комплектации
                for(var i in response.costs){
                    var title = response.costs[i].title; //Заголовок цены
                    var cost  = response.costs[i].cost;  //Значение цены
                    cost_select.append('<option value="'+cost+'">'+cost+' - '+title+'</option>');
                }
                // На клик по кнопке OK
                apply_cost_btn.click(function(){
                    $('input.single_cost', $(THIS).closest('.item')).val(cost_select.val());
                    methods.refresh();
                });
                cost_select.show();
                apply_cost_btn.show();
            }
        });
    },
    changeStatus = function() {
        $('.change-status-text', $this).css('background-color',
            $('.status-color', this).css('background-color')
        ).find('.value')
         .text( $(this).text() );


        $('#status_text', $this).text( $(this).text() );
        $('#status', $this).val( $(this).data('id') ).data( $(this).data() );
        checkAdditionalBlock();
    },

    checkAdditionalBlock = function() {
        var currentStatus = $('#status').data('type');
        $('#additionalBlockWrapper [data-depend-status]').each(function() {
           $(this).closest('tr')
                  .toggleClass('hidden', $(this)
                  .data('dependStatus').indexOf(currentStatus) == -1 );
        });

        $('#additionalBlockWrapper').toggleClass('hidden', $('#additionalBlockWrapper .otable tr:not(.hidden)').length == 0 );
    },
    
    moveFormData = function(form) {
        var dlgData = $(form).serializeArray();
        for(var i in dlgData) {
            $('[name="'+dlgData[i].name+'"]', $this).val(dlgData[i].value);
        }
    },
    
    
    remove = function() {
        var remove_items = $('.pr-table tr:has(input[name="chk[]"]:checked)', $this);
        if (remove_items.length>0
            && confirm(lang.t('Вы действительно желаете удалить выбранные позиции (%count)?', {
                count: remove_items.length
            })))
        {
            remove_items.remove();
            methods.refresh();
        }
    },
    
    /**
    * Добавление скидки на заказ
    * 
    */
    addCoupon = function() {
        if($('input.coupon').length > 0){
            $.messenger(lang.t('Купон на скидку уже был добавлен ранее. Невозможно добавить более одного купона'));
            return;
        }
        var code = prompt(lang.t("Введите купон на скидку"));
        if (!code || code.length){
            var uniq = genUniq();
            var $input = $('<input type="hidden">');
            $('.added-items').append($input.attr('name', 'items['+uniq+'][type]').val('coupon').clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][amount]').val(1).clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][code]').val(code).clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][title]').val(lang.t('Купон на скидку ')+code).clone());
            methods.refresh();    
        }
    },
    
    /**
    * Добавление скидки на заказ
    * 
    */
    addOrderDiscount = function() {
        if($('input.order_discount').length > 0){
            $.messenger(lang.t('Скидка на заказ уже добавлена'));
            return;
        }
        var discount = prompt(lang.t("Введите сумму скидки на заказ"));
        if (discount>0) {
            var uniq = genUniq();
            var $input = $('<input type="hidden">');
            $('.added-items').append($input.attr('name', 'items['+uniq+'][type]').val('order_discount').clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][amount]').val(1).clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][price]').val(discount).clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][discount]').val(discount).clone());
            $('.added-items').append($input.attr('name', 'items['+uniq+'][title]').val(lang.t('Скидка на заказ ')+discount).clone());
            methods.refresh();
        }
    },
                                              
    addProduct = function() {
        // Показываем диалог выбора продукта
        $('.product-group-container').selectProduct({
            showCostTypes: true,
            startButton: '.select-button',
            // По закрытию диалога
            onResult: function(){
                var cost_id = $('.my-buttons-pane select[name=costtype]').val();

                console.log($('.input-container input').length);

                // Для каждого вставленного продукта
                $('.input-container input').each(function(){
                    var prod_id = $(this).val();     
                   /* 
                    // Если такой товар уже добавлен, то увеличиваем число товаров на один и выходим
                    $prod_amount = $("input.num[data-product-id="+prod_id+"]");
                    if($prod_amount.length > 0){
                        var old_amount = parseInt($prod_amount.val());
                        $prod_amount.val(old_amount + 1);
                    }
                    else{*/
                        // Если такого товара еще нет в корзине
                        var uniq = genUniq();
                        var $input = $('<input type="hidden">');
                        $('.added-items').append($input.attr('name', 'items['+uniq+'][type]').val('product').clone());
                        $('.added-items').append($input.attr('name', 'items['+uniq+'][entity_id]').val(prod_id).clone());
                        $('.added-items').append($input.attr('name', 'items['+uniq+'][amount]').val(1).clone());
                        $('.added-items').append($input.attr('name', 'items['+uniq+'][cost_id]').val(cost_id).clone());
                        $('.added-items').append($input.attr('name', 'items['+uniq+'][single_weight]').val($(this).data('weight')).clone());
                    //}
                });
                methods.refresh();
            }
        });
        $(".select-button").click();
        
    },
    
    invalidate = function() {
        $('.summary', $this).hide();
        $('.refresh', $this).show();
    },

    /**
     * Печаь выбранной формы
     */
    printOrder = function() {
        var documents = $('input.printdoc:checked');
        
        if (documents.length) {
            documents.each(function() {
                window.open($(this).val(), '_blank');
        });
        } else {
            $('input.printdoc').prop('checked', true);
        }
    },

    /**
     * Создание пользователя из незарегистрированного
     */
    createUserFromNoRegister = function() {

        $.rs.loading.show();
        $.ajax({
            url: $(this).data('href'),
            data: $("#userBlockWrapper :input").serialize(),
            type: 'POST',
            dataType: 'json',
            success: function(response){
                $.rs.loading.hide();
                if ($.rs.checkAuthorization(response)
                    && $.rs.checkWindowRedirect(response)
                    && $.rs.checkMessages(response)
                    && response.success
                ){
                    $("#userBlockWrapperContent").replaceWith(response.html);
                }
            }
        });
        return false;
    };
    
    
    if ( methods[method] ) {
        methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
        return methods.init.apply( this, arguments );
    }       
}  

$(function() {
    $.orderEdit();
    
    $('body').on('click', '.collapse-block .collapse-title', function() {
        var parent = $(this).parent();
        if (parent.toggleClass('open').is('.open')) {
            $('.tinymce', parent).trigger('became-visible');
        }
    });
});