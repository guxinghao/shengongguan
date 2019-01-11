$(function() {
	//查看密码
	$(".eyes_box").click(function() {

		if($(this).attr("data-show") == 1) { //明文
			$(this).attr("data-show", "2");
			$(this).children("img").attr('src', 'img/open.png');
			$(this).parent("div").children(".mima_dd").hide();
			$(this).parent("div").children(".mima_wz").show();

			return;
		}
		if($(this).attr("data-show") == 2) { //密文
			$(this).attr("data-show", "1");
			$(this).children("img").attr('src', 'img/close.png');
			$(this).parent("div").children(".mima_dd").show();
			$(this).parent("div").children(".mima_wz").hide();

			return;
		}
	});

})
//倒计时
var wait = 60;

function time(o) {
	var mobile=$("#mobile").val();
	if(mobile==''){
		return;
	}else{
		if(wait <= 0) {
		o.removeAttribute("disabled");
		o.value = "重新发送";
//		o.setAttribute("class", 'code wid_7');
		wait = 60;

	} else {
		o.setAttribute("disabled", true);
		o.value = "" + wait + "s";
//		o.setAttribute("class", 'code wid_7 greyColor');
		wait--;

		setTimeout(function() {
			time(o)
		}, 1000)
		}
	}
	
}
//			已写字数显示
function title_len() {
	var value = $('#news_title').val().length;
	if(value >= 200) {
		var string = "<var>" + 200 + "/200</var>";
	} else {
		var string = "<var>" + value + "</var>/200";
	}
	$('#titlelen').html(string);
}