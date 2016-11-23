$(document).ready(function(){
        var swiperHead = new Swiper('.swiper-container.head', {
            slidesPerView: 4,
            paginationClickable: true,
            spaceBetween:0,
            slideToClickedSlide: true,
            //loop : true,
            //slidesPerView : 'auto',
            //loopedSlides :6,
            
        });
        swiperHead.on('click', function(evt){
            swiperPanel.slideTo(swiperHead.clickedIndex);
            $(".swiper-container.head").find(".swiper-slide").eq(swiperHead.clickedIndex).addClass("active").siblings().removeClass("active");
        });
        
//���ݻ���
        var swiperPanel = new Swiper('.swiper-container.panel', {
            slidesPerColumn : 1,
            slidesPerColumnFill : 'row',
           //slidesPerView: 1,
            //loop : true,
            //slidesPerView : 'auto',
            //loopedSlides :6,
            autoHeight: true ,
        });
        swiperPanel.on('slideChangeEnd', function(evt){
            swiperHead.slideTo(swiperPanel.activeIndex-1);
            $(".swiper-container.head").find(".swiper-slide").eq(swiperPanel.activeIndex).addClass("active").siblings().removeClass("active");
        });

});
$(function(){

    var head_top = ''; 
    var like_tops='';
    $(window).scroll(function(){  
        var scroH = $(this).scrollTop();
        if(head_top == ''){
            head_top = $('#box1').offset().top-70;
        }

        if(like_tops == ''){
            like_tops = $('#like_top').offset().top;
        }
        //console.log($('#box1').offset().top);
        if(scroH>=head_top){  
 
            $(".head").css({"position":"fixed","top":"60px"});
            if(like_tops<=scroH){
            $(".head").css({"position":"static"});  
        }
     
        }else if(scroH<head_top){  
     
        $(".head").css({"position":"static"});  
     
        }  
    
    })
});