// 素材
function openMedia(callback, imgNum) {
	layui.use(['layer'], function () {
		layer.open({
			type: 2,
			title: '素材管理',
			area: ['1050px', '600px'],
			fixed: false, //不固定
			btn: ['保存', '返回'],
			content: ns.url("giftcard://shop/media/media", {
				request_mode: 'iframe',
				mediaIds: mediaIds.toString(),
				imgNum: imgNum
			}),
			yes: function (index, layero) {
				var iframeWin = document.getElementById(layero.find('iframe')[0]['name']).contentWindow;//得到iframe页的窗口对象，执行iframe页的方法：
				iframeWin.selectGiftCardMediaListener(function (obj) {
					if (typeof callback == "string") {
						try {
							eval(callback + '(obj)');
							layer.close(index);
						} catch (e) {
							console.error('回调函数' + callback + '未定义');
						}
					} else if (typeof callback == "function") {
						callback(obj);
						layer.close(index);
					}

				});
			}
		});
	});
}