window.yuanMenu = []; // 原菜单数据
window.baseMenu = []; // 处理后的菜单
window.firstMenu = []; //  一级菜单
window.secondMenu = []; // 二级菜单
window.forthMenu = []; // 四级菜单
window.crumbs = []; // 面包屑
window.crumbsName = []; // 面包屑
window.currentMenu = {}; // 当前菜单
window.quickAddonMenu = {}; // 营销活动快捷方式才是
window.isRefreshSecondMenu = false; // 是否刷新二级菜单

// layui对象
window.form = null;
window.laytpl = null;
window.element = null;

function getMenuList() {
	$.ajax({
		url: ns.url("shop/index/menu"),
		dataType: 'JSON',
		type: 'POST',
		data: {
			app_module: ns_url.appModule
		},
		success: function (data) {
			window.userIsAdmin = data.user_is_admin;
			window.yuanMenu = data.yuan_menu;
			window.quickAddonMenu = data.quick_addon_menu;
			loadMenu(getRoute().url)
		}
	});
}

/**
 * 加载菜单
 * @param url 页面路径
 */
function loadMenu(url) {
	if (window.yuanMenu && window.yuanMenu.length == 0) return;

	url = url.toLowerCase();

	window.baseMenu = []; // 原菜单
	window.firstMenu = []; //  一级菜单
	window.secondMenu = []; // 二级菜单
	window.forthMenu = []; // 四级菜单
	window.crumbs = []; // 面包屑
	window.crumbsName = []; // 面包屑
	window.currentMenu = {}; // 当前菜单

	var current = window.yuanMenu.filter(function (item) {
		if (item.url.toLowerCase() == url) {
			return item;
		}
	});

	current.sort((a, b) => {
		return b.level - a.level
	});

	window.currentMenu = current[0];

	if (window.currentMenu) {

		// 菜单树结构，面包屑
		getMenuTree(window.currentMenu.name);
		window.crumbs.sort((a, b) => {
			return a.level - b.level;
		});
	}

	window.baseMenu = initMenu(window.yuanMenu, '');

	// 四级菜单
	initForthMenu();

	renderMenu();
}

// 渲染菜单
function renderMenu() {
	// 渲染一级菜单
	var html = '';
	window.baseMenu.forEach(function (item, index) {
		html += '<li class="layui-nav-item">';
		html += `<a ${item.selected ? 'class="active"' : ''} href="${ns.href(item.url)}" data-menu-level="${item.level}" ${item.name == 'CASHIER' ? 'target="_blank"' : ''}>`;
		html += `<i class="iconfont ${item.icon}"></i>`;
		html += `<span>${item.title}</span>`;
		html += `</a>`;
		html += `</li>`;

		if (item.selected) {
			window.secondMenu = item.child_list;
			window.firstMenu = item;
		}
	});

	// 加载一级菜单
	$('.menu-first-wrap').html(html);

	// 顶部面包屑
	$('.layui-header-crumbs-first span').text(window.firstMenu.title);

	// 删除四级菜单
	$('.forth-menu-wrap').remove();

	// 加载二、三、四级菜单
	if (window.secondMenu.length) {

		// 处理营销活动菜单
		if (window.crumbs.length && window.crumbs[0]['name'] == 'PROMOTION_ROOT') {
			// 加载快捷方式的插件菜单
			window.secondMenu.forEach(function (item, index) {
				if (item.name == 'PROMOTION_CENTER') {
					handlePromotionMenu(index, 'promotion');
				} else if (item.name == 'PROMOTION_TOOL') {
					handlePromotionMenu(index, 'tool');
				}

			});
		}

		var secondHtml = '';
		window.secondMenu.forEach(function (item, index) {

			var childList = item.child_list;
			var childLength = childList.length;

			secondHtml += `<li class="layui-nav-item layui-nav-itemed ${item.selected ? 'layui-this' : ''}">`;
			secondHtml += `<a href="${childLength ? 'javascript:;' : ns.href(item.url)}" data-menu-level="${item.level}" class="layui-menu-tips ${childLength == 0 && item.selected ? 'second-selected-nav' : ''}">`;
			secondHtml += `<span>${item.title}</span>`;
			secondHtml += `</a>`;

			// 加载三级菜单
			if (childLength) {
				secondHtml += `<dl class="layui-nav-child">`;
				childList.forEach(function (third, thirdIndex) {
					secondHtml += `<dd class="${third.selected ? 'layui-this' : ''}">`;
					secondHtml += `<a href="${ns.href(third.url)}" data-menu-level="${third.level}" class="layui-menu-tips">`;
					secondHtml += `<span class="layui-left-nav">${third.title}</span>`;
					secondHtml += `</a>`;
					secondHtml += `</dd>`;
				});
				secondHtml += `</dl>`;

			}

			secondHtml += `</li>`;

		});

		$('.second-nav .layui-nav-tree').html(secondHtml);

		// 加载四级菜单
		if (window.forthMenu.length) {
			var forthMenuHtml = '';
			forthMenuHtml += `<div class="fourstage-nav layui-tab layui-tab-brief forth-menu-wrap">`;
			forthMenuHtml += `<ul class="layui-tab-title">`;
			window.forthMenu.forEach(function (item, index) {
				var query = getRoute().query;
				var arr = [];
				var href = item.url;
				for (var key in query) {
					arr.push(`${key}=${query[key]}`)
				}
				if (arr.length) {
					href += `?${arr.join('&')}`;
				}
				forthMenuHtml += `<li class="${item.selected ? 'layui-this' : ''}">`;
				forthMenuHtml += `<a href="${ns.href(href)}" data-menu-level="${item.level}">${item.title}</a>`;
				forthMenuHtml += `</li>`;
			});
			forthMenuHtml += `</ul>`;
			forthMenuHtml += `</div>`;

			$('.body-wrap.layui-body').prepend(forthMenuHtml)

		}

		var crumbHtml = '';
		window.crumbs.forEach(function (item, index) {
			if (index > 0) {
				crumbHtml += `<a href="${(index == window.crumbs.length - 1) ? 'javascript:;' : ns.href(item.url)}" data-menu-level="${item.level}">${item.title}</a>`;
			}
		});

		$('.layui-header-crumbs-second .layui-breadcrumb').html(crumbHtml);
		$('.layui-header-crumbs-second').show();

		$('.body-wrap').css({
			left: '256px',
			visibility:'visible'
		});

		$('.second-nav').show();
	} else {
		$('.second-nav').hide();
		$('.layui-header-crumbs-second').hide();

		$('.body-wrap').css({
			left: '124px',
			visibility:'visible'
		});
	}

	// 如果二级菜单发生了变化，滚动位置要回到顶部
	if (window.isRefreshSecondMenu) $('.second-nav .layui-side-scroll').scrollTop(0);

	// 切换功能，内容区域，滚动位置要回到顶部
	$('.layui-layout-admin .layui-body').scrollTop(0);

	if (window.crumbs.length) document.title = window.crumbs[window.crumbs.length - 1].title + ' - ' + window.ns_url.siteName;

	window.element.render('breadcrumb');
	window.element.init();
}

// a标签跳转点击事件
$('.layui-side').on('click', 'a', (function () {
	var href = $(this).attr('href'); // 跳转链接
	var target = $(this).attr('target');
	var level = $(this).attr('data-menu-level'); // 菜单等级，滚动定位
	var url = ''; // 页面地址
	var hash = '';

	// 拦截空链接
	if (!href) return;

	// 外链地址无须处理
	if (href.indexOf(ns_url.baseUrl) == -1) return;

	// 打开新窗口
	if (target == '_blank') {
		window.open(href);
		return false;
	}

	// 退出登录
	if (href.indexOf('shop/login/logout') != -1) {
		return;
	}

	var arr = href.split('#');

	// 网址无参数时进入概况页面
	if (arr.length == 1) {
		url = 'shop/index/index';
		hash = 'url=' + url;
	} else {
		hash = arr[1];
		// 找到当前页面地址
		var query = hash.split('&');
		for (var i = 0; i < query.length; i++) {
			if (query[i].indexOf('url=') != -1) {
				url = query[i].replace('url=', '');
				break;
			}
		}
	}

	window.isRefreshSecondMenu = level == 1;
	loadMenu(url);
	location.hash = hash;

	return false;
}));

window.onhashchange = function (event) {
	var oldUrl = searchUrl(event.oldURL);
	var newUrl = searchUrl(event.newURL);

	// 页面相同，只是表单筛选条件搜索，则不刷新页面
	if (oldUrl == newUrl && localStorage.getItem('formSubmit') == 'search') {
		// 表单搜索完成后清空
		localStorage.removeItem('formSubmit'); // 表单搜索标识
		return;
	}

	listenerHash();
};

// 监听回退键事件
window.addEventListener('popstate', function() {
	// 关闭弹框
	layer.closeAll();
});

//  获取哈希值数组
function getHashArr() {
	var hash = ns.urlReplace(location.hash);
	return hash.substr(1).split("&"); // 移除#
}

// 监听hash值变化，加载页面
function listenerHash(isLoadMenu = true) {
	var params = getRoute();
	//有的客户会出现同一个链接，直接页面访问 和 ajax访问 无法区分的情况，特此做区分
	params.query._type = 'html';
	var url = params.url;

	// 加载页面前，显示加载动画进行过渡
	var html = '<div class="common-loading-wrap">';
	html += '<i class="common-loading-layer layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i>';
	html += '</div>';
	$('.layui-layout-admin .layui-body .body-content').html(html);

	// 切换页面，删除弹框、依赖
	$('.el-popper').remove();
	$('.colorpicker-layer').remove();
	$('.layui-layer-shade').remove();
	$('.layui-layer-move').remove();
	$('#edui_fixedlayer').remove();
	$('.global-zeroclipboard-container').remove();
	$('.layui-layer-page').remove();

	var beforeUrl = ns.url(url); // 请求前的页面地址

	$.ajax({
		url: beforeUrl,
		data: params.query,
		dataType: 'html',
		type: 'get',
		success: function (html) {
			try {
				var res = JSON.parse(html);
				if (res.code == -1) {
					// 权限不足
					location.hash = 'url=' + res.data.url;
					return;
				}
			} catch (e) {
			}

			var afterUrl = ns.url(getRoute().url); // 请求后的的页面地址
			// 渲染页面时，检测当前打开页面和请求的是否一致
			if (beforeUrl == afterUrl) {

				$('.layui-layout-admin .layui-body .body-content').html(html).css('visibility','hidden');
				setTimeout(function () {
					$('.layui-layout-admin .layui-body .body-content').css('visibility','visible');
					window.form.render();
					tipsShow();
					loadImgMagnify();
				}, 20);

				// 是否加载菜单
				if (html !== '权限不足，请联系管理员' && isLoadMenu) {
					loadMenu(url);
				}
			}

		}
	});

}

// 找到当前页面路径
function searchUrl(url) {
	var result = '';
	var arr = url.split('#');
	if (arr.length > 1) {
		var hash = arr[1];
		var hash_arr = hash.split('&');
		for (var i = 0; i < hash_arr.length; i++) {
			var hash_data = hash_arr[i].split('=');
			if(hash_data[0] == 'url'){
				result = hash_data[1];
				break;
			}
		}
	}
	return result;
}

function initMenu(menus_list, parent) {
	var temp_list = [];
	if (menus_list.length) {
		menus_list.forEach(function (item, index) {
			if (crumbsName.indexOf(item.name) != -1) {
				item.selected = true;
			} else {
				item.selected = false;
			}

			if (item["parent"] == parent && item["is_show"] == 1 && item['type'] == 'page') {
				var temp_item = {
					'name': item['name'],
					'level': item['level'],
					'addon': item['addon'],
					'selected': item.selected,
					'url': item['url'],
					'title': item['title'],
					'icon': item['picture'],
					'icon_selected': item['picture_select'],
					'target': '',
					'parent': item['parent'],
					'sort': item['sort']
				};

				temp_item["child_list"] = initMenu(menus_list, item["name"]);//获取下级的菜单
				if(!userIsAdmin && temp_item["child_list"].length > 0){
					temp_item['url'] = temp_item["child_list"][0]['url'];
				}
				temp_list.push(temp_item);
			}

		})
	}
	return temp_list;
}

/**
 * 生成菜单树结构，面包屑
 * @param name
 */
function getMenuTree(name) {
	if (name) {
		var menu = window.yuanMenu.filter(function (item) {
			if (item.name == name) {
				return item;
			}
		});
		window.crumbs.push(menu[0]); // name 是唯一的，所以数组只有一项
		window.crumbsName.push(menu[0].name);
		getMenuTree(menu[0].parent);
	}
}

function initForthMenu() {
	if (window.crumbs.length == 0) return;
	var child = window.crumbs[window.crumbs.length - 1];

	if (child.is_show == 0) return;

	// 如果当前菜单是隐藏的，那就不显示
	var menu = window.yuanMenu.filter(function (item) {
		if (item.parent == child.parent && item.is_show == 1 && item.level > 3) {
			return item;
		}
	});
	menu.forEach(function (item) {
		item.selected = (item.name == child.name);
	});
	window.forthMenu = menu;
}

// 处理营销活动菜单
function handlePromotionMenu(index, key) {

	var emptyAddon = []; // 保留营销活动主菜单
	var promotionAddon = []; // 快捷方式中的有效插件菜单
	var currentAddon = []; // 当前选中插件菜单，不在快捷方式中，要展示出来
	var addon = window.crumbs[window.crumbs.length - 1].addon;

	window.secondMenu[index].child_list.forEach(function (menuItem, menuIndex) {
		if (menuItem.addon == '') {
			emptyAddon.push(menuItem);
		} else if (menuItem.addon) {
			if (window.quickAddonMenu[key].indexOf(menuItem.addon) != -1) {
				promotionAddon.push(menuItem);
			} else if (addon && addon == menuItem.addon && window.quickAddonMenu[key].indexOf(addon) == -1) {
				currentAddon.push(menuItem);
			}
		}
	});

	window.secondMenu[index].child_list = emptyAddon;

	if (currentAddon.length) window.secondMenu[index].child_list = window.secondMenu[index].child_list.concat(currentAddon);

	if (promotionAddon.length) window.secondMenu[index].child_list = window.secondMenu[index].child_list.concat(promotionAddon);

}

layui.use(['form', 'laytpl', 'element'], function () {
	window.form = layui.form;
	window.element = layui.element;
	window.laytpl = layui.laytpl;

	getMenuList();

	listenerHash(false);
});