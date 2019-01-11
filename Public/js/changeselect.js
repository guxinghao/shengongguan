$(function(){
    $('.changeColor').change(function(event) {
        $(this).css('color','red');
        $(this).find("option:not(:selected)").css('color','#000');
    });    
})