function _initScroll(opts) {
/*	var defalut = {
	}
	opts = opts ? Object.assign(opts, defalut):defalut;
	new IScroll('#wrapper', opts);*/
}
function _preview(srcs, callback) {
	var count = 0;
	srcs.forEach(function (v, i) {
		var img = new Image();
		img.onload = load;
		img.onerror = load;
		img.src = v;
		console.log(v);
		function load () {
			if (count >= srcs.length-1) {
				callback && callback();
			}
			count++;
		}
	});
}
function shopcart(seletor, callback) {
	var maskContainer = $('#mask-container'),
		mask = $('.mask'),
		close = $('.close-hook'),
		wrapper = $('#wrapper'),
		minus = maskContainer.find('.minus'),
		add = maskContainer.find('.add'),
		input = maskContainer.find('.input'),
		items = $('#items');
		
	items.on('click', 'dd',itemclick);
	wrapper.on('click', seletor, showModal);
	close.click(hideModal);
	add.click(function () {
		var $input = $(this).prev();
		var value = $input.val();
		
		$input.val(++value);
	});
	
	input.blur(function () {
		var value = parseInt(this.value);
		if (isNaN(value)) {
			this.value = 1;
		}
	});
	
	minus.click(function () {
		var $input = $(this).next();
		var value = $input.val();
		var value = Math.max(1, --value);
		
		$input.val(value);
	});
	
	maskContainer[0].addEventListener('webkitAnimationEnd', function() {
		if($(this).hasClass('slideout')) {
			mask.hide();
			maskContainer.hide();
		}
	})
		
	/* 规格条目点击 */
	function itemclick() {
		$(this).addClass('active').siblings().removeClass('active');
	}
	
	/* 显示弹出层 */
	function showModal(e) {
		var df = $.Deferred();
		callback && callback(df, this);
		df.done(function () {
			mask.show();
			maskContainer.show().addClass('slidein').removeClass('slideout');
		});
		return false;
	}
	
	/* 隐藏弹出层 */
	function hideModal() {
		maskContainer.removeClass('slidein').addClass('slideout');
	}
		
	return {
		showModal: showModal,
		hideModal: hideModal
	}
}
function infinite(callback) {
	var $window = $(window);
	var wh = $window.height();
	var setLoadding = false;
	$window.scroll(function () {
		var $this = $(this);
		var scrollTop = $this.scrollTop();　　
		var scrollHeight = $(document).height();　　
		var windowHeight = $this.height();
		if(scrollTop + windowHeight == scrollHeight && !setLoadding) {
			var df = $.Deferred();
			setLoadding = true;
			df.done(function () {
				setLoadding = false;
			})
			callback && callback(df);
		}
	});
	
	return setLoadding;
}
function renderLoadding(text, isCom, ele) {
	var str = isCom?'':'<span class="icon"></span>';
	var dom = $('<div id="loadding-wrapper" class="loadding-wrapper">'+ str +'<span class="text">'+ text +'</span></div>');
	var currentEle = ele?ele:'#wrapper';
	$(currentEle).append(dom);
}
function removeLoadding() {
	$('#loadding-wrapper').remove();
}
function showEmpty() {
	$('#empty').show();
}
function hideEmpty() {
	$('#empty').hide();
}
function lazyloadimg() {
	$("img.lazyload").lazyload({
		placeholder: "/haoge/img/default.jpg",
		effect: "fadeIn",
		container: window
	});
}