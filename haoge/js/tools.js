(function($) {

	var tools = {
		IN_HIDE_TIME: 200, //隐藏加载时间
		ALERT_MODE: 0, //弹出框模式
		TIPS_MODE: 1, //展示错误形式
		BLUR_MODE: 2, //失去焦点验证模式
		TOP_SHOW_MODE: 3, //从上至下弹出
		TOAST_MODE: 5, //toast模式
		TOGGLE_MODE: 6, //切换模式
		ACTIVE_MODE: 7, //active模式
		//表单验证
		validForm: function(_this, mode) {
			var b = true,
				tips = '',
				toptips = '';
			if(typeof mode === 'undefined') mode = Tools.ALERT_MODE;
			$(_this).find('*').each(function() {
				if(typeof($(this).attr("verify")) !== "undefined") {
					var parent = $(this).parent();
					var data = $.trim($(this).val());
					if(mode === Tools.TIPS_MODE) {
						parent.find('.input-err-tip').remove();
						$(this).blur(function() {
							Tools.validForm(_this, Tools.TIPS_MODE);
						})
					}
					if(data.length < 1) {
						var msg = $(this).attr("msg-empty");
						b = false;
						if(mode === Tools.ALERT_MODE) {
							Tools.alert(msg);
							return b;
						} else if(mode === Tools.TIPS_MODE) {
							tips = $('<div class="input-err-tip" style="color: red; font-size: 12px; line-height: 20px; ">' + msg + '</div>');
							if(parent.find('.input-err-tip').length <= 0) parent.append(tips);
						} else if(mode === Tools.TOP_SHOW_MODE) {
							tips = $('<div id="show_top_wrap" style="position: absolute;top:0;bottom:0;left:0;right:0; background-color: rgba(0,0,0,.08)"><div id="show_top_err" style="position: absolute;top: -44px;left: 0;right: 0;line-height: 44px;color: #333;padding-left: 30px;background-color: #fff;box-shadow: 2px 2px 20px #ddd;-webkit-box-shadow: 2px 2px 20px #ddd;z-index: 100;letter-spacing: 2px;font-size: 14px; transition: all .3s ease">' + msg + '</div></div>');
							if($('#show_top_wrap').length <= 0) $('body').append(tips);
							setTimeout(function() {
								$('#show_top_err').css('top', 0);
							}, 50)
							setTimeout(function() {
								$('#show_top_err').css('top', -44);
								setTimeout(function() {
									$('#show_top_wrap').remove();
								}, 300);
							}, 1500);
							return b;
						} else if(mode === Tools.TOAST_MODE) {
							Tools.toast(msg);
							$()
							return b;
						}
					} else {
						//  合法校验
						var msg = $(this).attr("msg");
						b = validData(data, $(this).attr("verify"));
						if(!b) {
							if(mode === Tools.ALERT_MODE) {
								Tools.alert(msg);
								return b;
							} else if(mode === Tools.TIPS_MODE) {
								tips = $('<div class="input-err-tip" style="color: red; font-size: 12px; line-height: 20px; ">' + msg + '</div>');
								if(parent.find('.input-err-tip').length <= 0) parent.append(tips);
							} else if(mode === Tools.TOP_SHOW_MODE) {
								tips = $('<div id="show_top_wrap" style="position: absolute;top:0;bottom:0;left:0;right:0; background-color: rgba(0,0,0,.03)"><div id="show_top_err" style="position: absolute;top: -44px;left: 0;right: 0;line-height: 44px;color: #333;padding-left: 30px;background-color: #fff;box-shadow: 2px 2px 20px #ddd;z-index: 100;letter-spacing: 2px;font-size: 14px; transition: all .3s ease">' + msg + '</div></div>');
								if($('#show_top_wrap').length <= 0) $('body').append(tips);
								setTimeout(function() {
									$('#show_top_err').css('top', 0);
								}, 50)
								setTimeout(function() {
									$('#show_top_err').css('top', -44);
									setTimeout(function() {
										$('#show_top_wrap').remove();
									}, 300);
								}, 1500);
								return b;
							} else if(mode === Tools.TOAST_MODE) {
								Tools.toast(msg);
								return b;
							}
						}
					}
				}
			});
			return b;

			function validData(value, type) {
				//  验证数字：
				if(type == 'number') {
					var reg = /^[0-9]*$/
					if(!reg.test(value)) {
						return false;
					}
				}
				//  验证Email地址：
				if(type == 'email') {
					var reg = /^w+([-+.]w+)*@w+([-.]w+)*.w+([-.]w+)*$/
					if(!reg.test(value)) {
						return false;
					}
				}

				//  手机号码：^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$或 var regMobile=/^1[3,5,8]\d{9}$/;
				if(type == 'phone') {
					var reg = /^1[34578]\d{9}$/
					if(!reg.test(value)) {
						return false;
					}
				}

				//  验证电话号码：：--正确格式为：XXXX-XXXXXXX，XXXX-XXXXXXXX，XXX-XXXXXXX，XXX-XXXXXXXX，XXXXXXX，XXXXXXXX。
				if(type == 'telephone') {
					var reg = /^((d{3,4})|d{3,4}-)?d{7,8}$/
					if(!reg.test(value)) {
						return false;
					}
				}

				//  验证身份证号（15位或18位数字）：
				if(type == 'ID') {
					var reg = /^[1-9]{1}[0-9]{14}$|^[1-9]{1}[0-9]{16}([0-9]|[xX])$/
					if(!reg.test(value)) {
						return false;
					}
				}

				if(type == 'bank') {
					var reg = /\d{15}|\d{19}/
					if(!reg.test(value)) {
						return false;
					}
				}

				//  非负浮点数（正浮点数 + 0）：^\d+(\.\d+)?$ 或 ^[1-9]\d*\.\d*|0\.\d*[1-9]\d*|0?\.0+|0$
				if(type == 'decimal') {
					var reg = /^\d+(\.\d{0,5})?$/
					if(!reg.test(value)) {
						return false;
					}
				}

				//  验证用户密码: 正确格式为：以字母开头，长度在6-18之间，只能包含字符、数字和下划线。
				if(type == 'password') {
					var reg = /^[\w]{6,16}$/
					if(!reg.test(value)) {
						return false;
					}
				}
				return true;
			}
		},
		getFormData: function() {
			var data = {};
			//  遍历页面中，所有符合条件的input 输入类型控件，eg：<input type="..." column="columnA"/>
			$("input").each(function(i, e) {
				if(typeof($(e).attr("column")) != "undefined") {
					data["" + $(e).attr('column')] = $(e).val();
				}
			});
			//  遍历页面中，所有符合条件的select 输入类型控件，eg：<select column="columnA">...</select>
			$("select").each(function(i, e) {
				if(typeof($(e).attr("column")) != "undefined") {
					data["" + $(e).attr('column')] = $(e).val();
				}
			});

			//  遍历页面中，所有符合条件的textarea 输入类型控件，eg：<textarea column="columnA">...</textarea>
			$("textarea").each(function(i, e) {
				if(typeof($(e).attr("column")) != "undefined") {
					data["" + $(e).attr('column')] = $(e).val();
				}
			});
			//  转译为字符串 ，以“dd”标识，用于后台controller接收，eg： "[{'columnA':'valueA','columnB':'valueB'}]"
			return data;
		},
		/**
		 * tab标签切换
		 * params: object {
		 * 		tab: '.tab'
		 * 		tabs: 'tabs'
		 * 		tabListClass: 'tab-sec'
		 * 		callback: function
		 * }
		 */
		tabSwitch: function(params) {
			var tab = $(params.tab) || $('.tab');
			var tabs = $(params.tabs) || $('.tabs');
			var tabListclass = params.tabListClass || 'tab-sec';
			var active = $('.item.active');
			tab.find('.item').click(function(e) {
				var index = $(this).index();
				$(this).addClass('active').siblings().removeClass('active');
				tabs.find('.' + tabListclass).eq(index).show().siblings().hide();
				if(params.callback) params.callback();
			})
		},
		alert: function(msg, fun, btn, effect, maskclose) {
			effect = typeof effect === 'undefined' ? true : effect;
			maskclose = typeof maskclose === 'undefined' ? false : maskclose;
			var cModal = maskclose ? ' close-modal ' : '';
			var scale = effect ? 'transition: all .3s ease-out; transform: scale(0);' : '';
			var btn = btn ? '<p class="close-modal btn-active" style="margin: 0;padding: 0;width: 100%;padding: 0 10px;border-radius: 2px;">取消</p>' : '';
			var mask = $('<div id="mask-alert-id"><div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,.5);z-index:100;" class="' + cModal + '"></div><div id="mask-effect" style="' + scale + 'position: fixed;width: 80%;left: 50%;top: 30%;border-radius: 5px;margin-left: -40%;background-color: #fff; z-index: 110"><div ><h2 style="text-align: center;font-size: 16px;font-weight: normal;margin: 10px;">提示</h2><p style="margin: 10px;text-align: center;font-size: 14px;word-break: break-all;">' + msg + '</p><div style="display: flex;display: -webkit-flex;justify-content: space-between;height:40px;line-height:40px;box-sizing: border-box;text-align: center;font-size: 14px;border-top: 1px solid #ddd;"><p id="sure-btn" class="btn-active" style="margin: 0;padding: 0;width: 100%;padding: 0 10px;border-right: 1px solid #ddd;border-radius: 2px;">确定</p>' + btn + '</div><div class="close-modal"></div></div></div></div>');

			show();
			$('.close-modal').on('click', function(e) {
				hide();
			});

			$('#sure-btn').on('click', function() {
				fun && fun();
				hide();
			});

			function show() {
				if(effect) {
					setTimeout(function() {
						$('#mask-effect').css('transform', 'scale(1)');
					}, 50);
				}
				$('body').append(mask.eq(0));
			}

			function hide() {
				if(!effect) {
					$('#mask-alert-id').remove();
					return;
				}
				$('#mask-effect').css('transform', 'scale(0)');
				setTimeout(function() {
					$('#mask-alert-id').remove();
				}, 200);
			}
		},
		toast: function(a) {
			
			var body = $('body');
			var dom = $('<p id="toast_4598" style="position:fixed; left: 50%; bottom: 20%;color: #fff;background-color: rgba(0, 0, 0, .7);padding: 5px 10px;border-radius: 6px; font-size: 14px; text-align: center;z-index: 9999">' + a + '</p>');
			
			if ($('#toast_4598').length <= 0) {
				dom.fadeIn(200);
				body.append(dom);
				var _this = $('#toast_4598');
				var _width = _this.outerWidth();
				_this.css('margin-left', -_width / 2);
				setTimeout(function() {
					_this.fadeOut(200, function() {
						$(this).remove();
					});
				}, 2000)
			}
		},
		showIndicator: function() {
			var SRC = '/haoge/img/lodding.gif';
			var dom = $('<div id="loadding-dom" class="fhv" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0" ><img style="border-radius: 50%" src="'+ SRC +'" width="90" height="90" /></div>');
			$('body').append(dom);
		},
		hideIndicator: function() {
			$('#loadding-dom').remove();
		},
		/**
		 *	cookie操作
		 */
		cookieUtil: function() {
			this.getCookie = getCookie;
			this.setCookie = setCookie;
			this.checkCookie = checkCookie;
			this.delCookie = delCookie;
			this.clearCookies = clearCookies;
			this.editCookie = editCookie;

			return {
				getCookie: this.getCookie,
				setCookie: this.setCookie,
				checkCookie: this.checkCookie,
				delCookie: this.delCookie,
				clearCookies: this.clearCookies,
				editCookie: this.editCookie
			};

			function clearCookies() { //删除所有cookies
				var cookies = document.cookie.split(";");
				for(var i = 0; i < cookies.length; i++) {
					var exp = new Date();
					exp.setTime(exp.getTime() - 1);
					var cookie = cookies[i];
					var eqPos = cookie.indexOf("=");
					var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
					var domain = location.host.substr(location.host.indexOf('.'));
					document.cookie = name +
						"=;expires=" + exp.toGMTString();
				}
			}

			function delCookie(name) { //删除cookies   
				var exp = new Date();
				exp.setTime(exp.getTime() - 1);
				var cval = getCookie(name);
				if(cval != null)
					document.cookie = name + "=" + cval + ";expires=" +
					exp.toGMTString();
			}

			function getCookie(c_name) { //获取cookie  
				if(document.cookie.length > 0) {
					c_start = document.cookie.indexOf(c_name + "=");
					if(c_start != -1) {
						c_start = c_start + c_name.length + 1;
						c_end = document.cookie.indexOf(";", c_start);
						if(c_end == -1) {
							c_end = document.cookie.length
						}
						return unescape(document.cookie.substring(c_start, c_end))
					}
				}
				return ""
			}

			//修改cookie的值
			function editCookie(name, value, expiresHours) {
				var cookieString = name + "=" + escape(value);
				if(expiresHours > 0) {
					var date = new Date();
					date.setTime(date.getTime() + expiresHours * 1000); //单位是毫秒
					cookieString = cookieString + ";expires=" + date.toGMTString();
				}
				document.cookie = cookieString;
			}

			function setCookie(c_name, value, expiredays) { //设置cookie  
				var exdate = new Date();
				exdate.setDate(exdate.getTime() + expiredays * 1000);
				document.cookie = c_name +
					"=" +
					escape(value) +
					((expiredays == null) ? "" : ";expires=" +
						exdate.toGMTString());
			}

			function checkCookie(name) { //检测cookie是否存在  
				var username = getCookie(name);
				if(username != null && username != "") {
					alert('Welcome again ' + username + '!');
				} else {
					username = prompt('Please enter your name:', "");
					if(username != null && username != "") {
						setCookie('username', username, 365);
					}
				}
			}
		},
		/**
		 * 发送验证码功能
		 * @param
		 * 	__this: 发送验证码按钮,必须是input
		 * 	params: {
		 * 		ccokiename cookie名称',
		 * 		disabledcolor 不能点击按钮的背景
		 * 		color 发送按钮的颜色
		 * 		disabledtext 不能点击按钮的提示文字
		 * 		text 发送按钮的文字
		 * 		Number 发送验证码之前需要验证的手机号
		 * 		time cookie时间默认60秒
		 * 	}
		 * @return function checkIsSendding 对外暴露接口
		 * 	
		 */
		sendSms: function(__this, params) {
			var DEFAULT = {
				cookiename: 'time', //cookiename
				disabledcolor: '#ddd', //按钮失效颜色
				color: '#f7cd4a', //按钮自身颜色
				disabledtext: '重新发送', //按钮失效后文字提示
				text: '获取验证码', //按钮文字
				number: '', //验证的手机号码
				time: 60 //倒计时时间（秒）
			};
			var tool = new Tools.cookieUtil();
			var _this = this;

			this.params = $.extend({}, DEFAULT, params);

			$(__this).click(function() {
				if(_this.params.number) {
					var value = $(_this.params.number).val();
					var reg = /^1[34578]\d{9}$/
					if($.trim(value) === '' || !reg.test(value)) {
						Tools.toast('手机号码格式不正确!');
						return;
					}
				}
				_this.sendCode($(this));
			});

			var countdown;
			_this.settime = function(obj) {
				countdown = tool.getCookie(_this.params.cookiename);
				if(countdown == 0) {
					obj.removeAttr("disabled");
					obj.css('background-color', _this.params.color).val(_this.params.text);
					return;
				} else {
					obj.attr("disabled", true).css('background-color', _this.params.disabledcolor);
					obj.val(_this.params.disabledtext + "(" + countdown + "秒)");
					countdown--;
					tool.editCookie(_this.params.cookiename, countdown, countdown + 1);
				}
				setTimeout(function() {
					_this.settime(obj)
				}, 1000) //每1000毫秒执行一次
			}

			_this.sendCode = function(obj) {
				tool.setCookie(_this.params.cookiename, _this.params.time, _this.params.time);
				_this.settime(obj); //开始倒计时

				if(_this.params.ajax) {
					_this.params.ajax(obj);
				}
			}

			_this.checkIsSendding = function() {
				if(tool.getCookie(_this.params.cookiename) > 0) {
					_this.settime($(__this));
				}
			}

			_this.checkIsSendding();

			if(this.params.number === '') {
				//Tools.alert('手机号码不能为空!');
				return;
			}

			if(isMobile(this.params.number)) {
				//Tools.alert('手机号码格式不正确!');
				return;
			}

			function isMobile(value) {
				var reg = /^1[34578]\d{9}$/
				if(!reg.test(value)) {
					//$.alert(msg+"不是手机号码");
					return false;
				}
			}

			return {
				checkIsSendding: _this.checkIsSendding
			};
		},
		/**
		 * 将日期格式化成指定格式的字符串
		 * @param date 要格式化的日期，不传时默认当前时间，也可以是一个时间戳
		 * @param fmt 目标字符串格式，支持的字符有：y,M,d,q,w,H,h,m,S，默认：yyyy-MM-dd HH:mm:ss
		 * @returns 返回格式化后的日期字符串
		 */
		formatDate: function(date, fmt) {
			date = date == undefined ? new Date() : date;
			date = typeof date == 'number' ? new Date(date) : date;
			fmt = fmt || 'yyyy-MM-dd HH:mm:ss';
			var obj = {
				'y': date.getFullYear(), // 年份，注意必须用getFullYear
				'M': date.getMonth() + 1, // 月份，注意是从0-11
				'd': date.getDate(), // 日期
				'q': Math.floor((date.getMonth() + 3) / 3), // 季度
				'w': date.getDay(), // 星期，注意是0-6
				'H': date.getHours(), // 24小时制
				'h': date.getHours() % 12 == 0 ? 12 : date.getHours() % 12, // 12小时制
				'm': date.getMinutes(), // 分钟
				's': date.getSeconds(), // 秒
				'S': date.getMilliseconds() // 毫秒
			};
			var week = ['天', '一', '二', '三', '四', '五', '六'];
			for(var i in obj) {
				fmt = fmt.replace(new RegExp(i + '+', 'g'), function(m) {
					var val = obj[i] + '';
					if(i == 'w') return(m.length > 2 ? '星期' : '周') + week[val];
					for(var j = 0, len = val.length; j < m.length - len; j++) val = '0' + val;
					return m.length == 1 ? val : val.substring(val.length - m.length);
				});
			}
			return fmt;
		},
		compareTime: function(startDate, endDate, isCompareNow) {
			var st = startDate;
			var et = endDate;
			isCompareNow = typeof isCompareNow === 'undefined' ? true : isCompareNow;
			var now = new Date();
			var stdt = new Date(st.replace("-", "/"));
			var etdt = new Date(et.replace("-", "/"));

			if(stdt > etdt) {
				Tools.toast("开始时间必须小于结束时间");
				return false;
			} else if((stdt < now || etdt < now) && isCompareNow) {
				Tools.toast("所选时间必须大于当前时间");
				return false;
			}

			return true;
		},
		/**
		 * 给输入框设置最大长度
		 * 调用方法： onInput="Tools.checkTextLength(this, 10)"
		 */
		checkTextLength: function(obj, length) {
			var ele = $(obj);
			ele.get(0).oninput = function() {
				if(this.value.length >= length) {
					ele.val(this.value.substr(0, length));
					Tools.toast('亲~最多输入字数为' + length);
				}
			};
		},
		/**
		 * swiper 
		 */
		swipe: function(_this) {
			new Swiper(_this, {
				pagination: '.swiper-pagination',
				autoplay: 3000,
				autoplayDisableOnInteraction: false,
				loop: true,
				lazyLoading: true
			});
		},
		/** 
		 * 解决输入框弹起被顶起
		 */
		wResize: function(eles, size) {
			var winHeight = $(window).height();
			$(window).resize(function() {
				var thisHeight = $(this).height();
				if(winHeight - thisHeight > 50) {
					$(eles).css('bottom', 1000);
				} else {
					$(eles).css('bottom', size);
				}
			});
		},
		/**
		 * 对选择条目进行操作
		 *
		 **/
		selItems: function(ele, mode, callback) {
			mode = typeof mode === 'undefined' ? Tools.ACTIVE_MODE : mode;

			$(ele).click(function(e) {
				e.stopPropagation();
				var _this = $(this);
				if(mode === Tools.ACTIVE_MODE) _this.addClass('active').siblings().removeClass('active');
				else if(mode === Tools.TOGGLE_MODE) {
					if(_this.hasClass('active')) _this.removeClass('active');
					else _this.addClass('active')
				}
				callback && callback(this);
			})
		},
		/**
		 * 图片上传
		 */
		UploadImg: function(params) {
			var _this = this;
			var def = {
				wrap: 'body',
				dom: window, //触发元素
				isMulti: true, //是否多图上传
				max: undefined, //最多上传多少张
				img: undefined, //只单图上传有效
			};
		
			def = $.extend(def, params);
			this.wrap = $(def.wrap);
			this.dom = $(def.dom);
			if(def.isMulti) {
				this.dom.attr('multiple', 'true');
			}
			this.dom.change(function() {
				rander(this);
			});
		
			function rander(__this) {
		
				if(def.isMulti) {
					(function() {
						if(upload() > def.max || __this.files.length > def.max) {
							Tools.alert('最多上传' + def.max + '张图片');
							return;
						}
						for(var i = 0; i < __this.files.length; i++) {
							var dom = $('<div class="item sq-img"><img /><div class="upload-close">x</div></div>');
							var objUrl = getObjectURL(__this.files[i]);
							if(!/image\/\w+/.test(__this.files[i].type)) {
								Tools.alert("文件必须为图片！");
								return false;
							}
							if(objUrl) {
								dom.find('img')[0].src = objUrl;
							}
							$(__this).parent().before(dom);
							def.success && def.success(objUrl);
						}
						$('.upload-close').click(function(e) {
							$(this).parent().remove();
							_this.dom.parent().show();
							def.close && def.close(this);
						});
					})()
				} else {
					//单图上传
					if(def.img) {
						var file = __this.files[0];
						document.querySelector(def.img).src = getObjectURL(file);
					}
				}
			};
		
			function upload() {
				if(def.max) {
					var count = def.wrap.find('.item').length;
		
					if(count >= def.max) {
						_this.dom.parent().hide();
					} else {
						_this.dom.parent().show();
					}
					if(count > def.max) {
						count = def.max;
					}
				}
				return count;
			}
		
			function getObjectURL(file) {
				//处理浏览器兼容
				var url = null;
				if(window.createObjectURL != undefined) {
					url = window.createObjectURL(file);
				} else if(window.URL != undefined) {
					url = window.URL.createObjectURL(file);
				} else if(window.webkitURL != undefined) {
					url = window.webkitURL.createObjectURL(file);
				}
				return url;
			}
		}

	}

	window.Tools = tools;
})(jQuery);