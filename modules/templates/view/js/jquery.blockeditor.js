/**
* Редактор блоков
*/

(function( $ ){
  $.fn.blockEditor = function(options) {  
      
      options = $.extend({
          sortSectionUrl: '',
          sortBlockUrl: ''
      }, options);
      
      return this.each(function() {
          var context = this;
          
          
          //Разворачиваем инструменты у широких блоков, секций, ...
          var expandTools = function() {
              $('.gs-manager .smart-dropdown').each(function() {
                  var common_tools = $(this).parent();
                  var width = 50;
                  $(this).find('.dropdown-menu li').each(function() {
                      width = width + 28;
                  });

                  var need_width = ($(this).offset().left - common_tools.offset().left) + width;
                  var is_need_wide = common_tools.width() > need_width;
                  $(this).closest('.block, .area, .row, .gs-manager').toggleClass('wide', is_need_wide);

                  if (is_need_wide) {
                      //Включаем tooltip
                      $('.dropdown-menu a', this).each(function() {
                          $(this).tooltip('enable');
                      });
                  } else {
                      //Выключаем tooltip
                      $('.dropdown-menu a', this).each(function() {
                          $(this).tooltip('disable');
                      });
                  }

              });
          }
          
          expandTools();
          
          var redrawColumns = function() {
              var current_device = $('.device-selector li.act').data('device');              
              var devices = ['lg', 'md', 'sm', 'xs'];
              $('.pageview .section-width').each(function() {
                  
                  var start = devices.indexOf(current_device);
                  var result = '';
                  for(var i=start; i<4; i++) {
                      var width = $(this).data(devices[i] + '-width');
                      if (width) {
                          result = width; 
                          break;
                      }
                  }
                  $(this).text(result ? result : '100%');
              });
          };
          
          redrawColumns();
          //Активируем переключатель устройств
          $('.device-selector li').off('.blockeditor').on('click.blockeditor', function() {
              var current_device = $(this).addClass('act').data('device');
                            
              $(this).siblings().removeClass('act');
              $('.pageview').removeClass('xs sm md lg').addClass(current_device);
              
              $.cookie('page-constructor-device', current_device);
              redrawColumns();
              expandTools();
          });
          
          //Активируем переключатель активности сетки
          $('.gs-manager .grid-switcher').off('.blockeditor').on('click.blockeditor', function() {
              var is_off = $(this).toggleClass('off').is('.off');
              var container = $(this).closest('.gs-manager').toggleClass('grid-disabled', is_off);
              $.cookie('page-constructor-disabled-' + container.data('container-id'), is_off ? 1 : null);
              expandTools();
          });

          //Активируем переключатель активности сетки
          $('.gs-manager .visible-switcher').off('.blockeditor').on('click.blockeditor', function() {
              var is_off = $(this).toggleClass('off').is('.off');
              var container = $(this).closest('.gs-manager').toggleClass('visible-disabled', is_off);
              $.cookie('page-visible-disabled-' + container.data('container-id'), is_off ? 1 : null);
          });
          
          $('.gs-manager .block .iswitch').off('.blockeditor').on('click.blockeditor', function() {
              var block = $(this).closest('.block');
              block.toggleClass('on');
              
              $.ajaxQuery({
                  url: options.toggleViewBlock,
                  data: {
                    id: block.data('blockId')
                  }
              });                  
          });

          //private
          var 
              sourceContainer,
              initSortBlocks = function() {
                  //Включаем сортировку блоков
                  $('.sort-blocks', context).sortable({
                      connectWith: '.workarea.sort-blocks',
                      placeholder: 'sortable-placeholder',
                      forcePlaceholderSize: true,
                      handle: '.drag-block-handler',
                      start: function(event, ui) {
                          ui.placeholder.addClass(ui.item.attr('class'));
                          ui.item.startParent = ui.item.parents('.area[data-section-id]:first');
                      },
                      change: function(event, ui) {
                          checkInsetAlign(ui.placeholder);
                      },
                      stop: function(event, ui) {
                          ui.item.stopParent = ui.item.parents('.area[data-section-id]:first');
                          $.ajaxQuery({
                              url: options.sortBlockUrl,
                              data: {
                                  block_id: ui.item.data('blockId'),

                                  parent_id: ui.item.parents('.area:first').data('sectionId'),
                                  position: ui.item.prevAll().length
                              }
                          });
                          checkInsetAlign(ui.item);

                          checkSectionSortType(ui.item.startParent);
                          checkSectionSortType(ui.item.stopParent);

                          initSortSections();
                          initSortBlocks();
                          expandTools();

                          ui.item.startParent = null;
                          ui.item.stopParent = null;
                      }
                  });
              },
              initSortSections = function() {
                  //Включаем сортировку секций
                  $('.sort-sections', context).sortable({
                      forcePlaceholderSize: true,
                      tolerance: 'pointer',
                      //cancel: '.container > .workarea',
                      connectWith: '.workarea.sort-sections:not(.container-workarea)',
                      placeholder: 'sortable-placeholder',
                      handle: '> .commontools .drag-handler',
                      start: function(event, ui) {
                          ui.placeholder.addClass(ui.item.attr('class')).append('<div class="border"></div>');
                          ui.item.startParent = ui.item.parents('[data-section-id]:first');
                      },
                      change: function(event, ui) {
                          checkAlphaOmega(ui.item.parents('[data-section-id]:first'));
                      },
                      stop: function(event, ui) {
                          ui.item.stopParent = ui.item.parents('[data-section-id]:first');

                          checkAlphaOmega(ui.item.startParent);
                          checkAlphaOmega(ui.item.stopParent);

                          $.ajaxQuery({
                              url: options.sortSectionUrl,
                              data: {
                                  section_id: ui.item.data('sectionId'),

                                  parent_id: ui.item.parents('[data-section-id]:first').data('sectionId'),
                                  position: ui.item.prevAll().length
                              }
                          });

                          checkSectionSortType(ui.item.startParent);
                          checkSectionSortType(ui.item.stopParent);

                          initSortSections();
                          initSortBlocks();
                          expandTools();

                          ui.item.startParent = null;
                          ui.item.stopParent = null;
                      }
                  });
              },
              checkInsetAlign = function(block) {
                  var parent = block.closest('[data-inset-align]');

                  block.removeClass('alignright alignleft');
                  if (parent.data('insetAlign') == 'right') {
                      block.addClass('alignright');
                  }
                  else if (parent.data('insetAlign') == 'left') {
                      block.addClass('alignleft');
                  }
              },
              checkSectionSortType = function(section) {
                  if (section.is('.row'))
                      return;

                  if (section.is('.area')) {
                      var has_sections = $('> .workarea [data-section-id]', section).length > 0;
                      var has_blocks = $('> .workarea [data-block-id]', section).length > 0;

                      if (has_sections) {
                          $('> .workarea', section).addClass('sort-sections').removeClass('sort-blocks');
                      }
                      if (has_blocks) {
                          $('> .workarea', section).removeClass('sort-sections').addClass('sort-blocks');
                      }
                      if (!has_sections && !has_blocks) {
                          $('> .workarea', section).addClass('sort-sections sort-blocks');
                      }
                  }
              },
              checkAlphaOmega = function(sectionParent) {
                  $('> .workarea > .area', sectionParent).removeClass('alpha omega');

                  if (!sectionParent.is('.gs-manager')) { //Если перемещение произошло в секции
                      $('> .workarea >.area:not(.ui-sortable-helper):first', sectionParent).addClass('alpha');
                      $('> .workarea >.area:not(.ui-sortable-helper):last', sectionParent).addClass('omega');
                  }
              },
              onStartContainerDrag = function(e) {
                  sourceContainer = $(this).closest('.gs-manager').get(0);
                  $(sourceContainer).addClass('sourceContainer');
                  $('.gs-manager:not(".sourceContainer")')
                    .bind('mouseenter.cDrag', onContainerEnter)
                    .bind('mouseleave.cDrag', onContainerLeave)
                    
                  
              },
              onContainerEnter = function(e) {
                  if (sourceContainer) {
                      $('.destinationContainer').removeClass('destinationContainer');
                      $(this).addClass('destinationContainer').append('<div class="dstOverlay" />');
                  }
              },
              onContainerLeave = function(e) {
                  if (sourceContainer) {
                      $(this).removeClass('destinationContainer');
                      $('.dstOverlay', this).remove();
                  }
              },
              onStopContainerDrag = function(e) {
                  if (sourceContainer) {
                      //Перемещаем контейнеры
                      var dst = $('.destinationContainer:first');
                      
                      if (dst.length) {
                          var dst_clone = dst.clone().insertAfter(dst);
                          dst.insertAfter(sourceContainer);
                          dst_clone.replaceWith(sourceContainer);
                          
                          var 
                            source_id = $(sourceContainer).data('containerId'),
                            destination_id = dst.data('containerId');
                          
                          $.ajaxQuery({
                              url: options.sortContainerUrl,
                              data: {
                                source_id: source_id,
                                destination_id: destination_id
                              }
                          });
                      }
                      
                      //Завершаем перемещение
                      $(sourceContainer).removeClass('sourceContainer');
                      sourceContainer = null;
                      $('.gs-manager').unbind('.cDrag');
                      $('.destinationContainer').removeClass('destinationContainer');
                      $('.dstOverlay').remove();
                  }
              };

          initSortSections();
          initSortBlocks();
          
          $('.gs-manager > .commontools > .drag-handler').on({
              mousedown: onStartContainerDrag,
          });
          
          $('body').mouseup(onStopContainerDrag);
          $(window).resize(function() {
              expandTools();
          });
      });
      
  };
})( jQuery );