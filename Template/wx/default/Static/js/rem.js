// JSON' in window
// true
// "meSSSA" in window
// false
// 判断当前方法是不是属于window  是true 不是false 注意要用字符串
	(function(doc,win){
		var docEl=doc.documentElement;//html
		// 'orientationchange'  横屏
		var resizeEvt='orientationchange' in window?'orientationchange':'resize';

		// 事件部分
		var recalc=function(){
			var clientWidth=docEl.clientWidth;//视口宽度
			if(!clientWidth)return;
			docEl.style.fontSize=20/375*clientWidth+"px";
		}
		// 触发部分
		if(!doc.addEventListener)return;
		win.addEventListener(resizeEvt,recalc,false);
		// 'DOMContentLoaded'     页面加载完执行
		doc.addEventListener('DOMContentLoaded',recalc,false);
			// $(function(){

			// })
			// window.onload

	})(document,window);