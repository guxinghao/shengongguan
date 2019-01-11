(function($){
	$.ShowPopWindow=function(option)
	{
		option = $.extend({
			title: "标题",
			html:null,
			closeBtn:null,
			closeFun:null,
			submitBtn:null,
			submitFun:null
		}, option || {});
		
		var html='<div class="popblock"><dl><dt><span>'+option.title+'</span><a href="javascript:;" title="关闭">关闭</a></dt><dd id="popDd"></dd></dl></div>';
		var bgDiv=document.createElement("DIV");
        var selfObj=$(".popblock");
        if(selfObj.length==0)
        {
            $("body").append(html);
            selfObj=$(".popblock");
        }
		
		$("#popDd").html(option.html);
		
        $("body").append(bgDiv);
        $(bgDiv).css({position:"absolute",width:$(document).width(), height:$(document).height(),top:"0",left:"0",opacity:0.8,background:"#fff",display:"none","z-index":100});
		
        $.windowCenter(selfObj);
        selfObj.show();
        $(bgDiv).show();
        $(bgDiv).bgiframe();
        $(bgDiv).click(function(){
            $(bgDiv).remove();
            selfObj.css({display:"none"});
			if(option.closeFun)
               option.closeFun.call(this);
        });
		
		$("dt a",selfObj).click(function(){
            $(bgDiv).remove();
            selfObj.css({display:"none"});
			if(option.closeFun)
               option.closeFun.call(this);
        });
		
		if(option.closeBtn)
		{
			$(option.closeBtn).click(function(){
				$(bgDiv).remove();
				selfObj.css({display:"none"});
				if(option.closeFun)
				   option.closeFun.call(this);
			});
		}
		
		if(option.submitBtn)
		{
			$(option.submitBtn).click(function(){
				$(bgDiv).remove();
				selfObj.css({display:"none"});
				if(option.submitFun)
				   option.submitFun.call(this);
			});
		}
		
		$(window).scroll(function(){
			if(selfObj.css("display") != "none")
			{
				$.windowCenter(selfObj);
				$(bgDiv).css({width:$(document).width(), height:$(document).height()});
			}
		});
	}
	
	$.windowCenter=function(obj)
	{
		var windowWidth=$.support.cssFloat ? window.innerWidth : document.documentElement.clientWidth;
		var windowHeight=$.support.cssFloat ? window.innerHeight : document.documentElement.clientHeight;
		var objWidth=obj.width();
		var objHeight=obj.height();
		var objTop=(windowHeight - objHeight ) / 2 + $.getBodyScrollTop();
		var objLeft=(windowWidth - objWidth ) / 2;
		obj.css({position:"absolute",display:"block","z-index":1000,top:objTop,left:objLeft});
		obj.bgiframe();
	}
	
	$.getBodyScrollTop=function(){
        var scrollPos; 
        if (typeof window.pageYOffset != 'undefined') { 
            scrollPos = window.pageYOffset; 
        } 
        else if (typeof document.compatMode != 'undefined' && 
            document.compatMode != 'BackCompat') { 
            scrollPos = document.documentElement.scrollTop; 
        } 
        else if (typeof document.body != 'undefined') { 
            scrollPos = document.body.scrollTop; 
        } 
        return scrollPos;
    }
})(jQuery);