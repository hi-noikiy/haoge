/**
 * Created by hasee on 2017/10/28.
 */


//添加省市区选择模块
$(function () {
    var $citypicker1 = $('#city-picker1');

    $citypicker1.citypicker();

    //添加常用联系人
    $('#add-mr img').click(function(){
        var  i = $('.my-contact-person').length;
        var node = $('body').find('.my-contact-person:last').clone(true);
        var reg =/[1-9]\d?|1000/;
        var a = $(node).find('span').eq(0).text().replace(reg,i+1);
        $(node).find('span').eq(0).text(a);
        var $b  = $(node).find('input');
        $b.each(function(){
            var c = $(this).attr("placeholder").replace(reg,i+1);
            $(this).attr("placeholder",c);
        });
        if(i!==1){
            $('.my-contact-person:last').after(node);
            $('#mv-img:first').remove();
        }else{
            var $rm =$('<img id="mv-img" src="image/move.jpg" />');
            $('.my-contact-person:last').after(node);
            $('.my-contact-person:last').children('.my-info-box').append($rm);
        }
    });

    //删除常用联系人
    $('body').on('click','#mv-img',function(){
        var i =$('.my-contact-person').length;
        var node = $('#mv-img').clone(true);
        if(i>2){
            $('.my-contact-person:last').prev().children(".my-info-box").append(node);
            $('.my-contact-person:last').remove();
        }else{
            $('.my-contact-person:last').remove();
        }
    })

});
