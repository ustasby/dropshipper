$(window).load(function() {
    var carousel = $('.orderViewProducts .scrollWrapper').jcarousel().swipeCarousel();
     
    $('.control').on({
        'inactive.jcarouselcontrol': function() {
            $(this).addClass('disabled');
        },
        'active.jcarouselcontrol': function() {
            $(this).removeClass('disabled');
        }
    });
    $('.control.prev').jcarouselControl({
        target: '-=1'
    });
    $('.control.next').jcarouselControl({
        target: '+=1'
    });
});