var form, table, laydate, laytpl, repeat_flag = false, //防重复标识
	currentDate = new Date(),
	minDate = "",
	reg = {
		required: /[\S]+/,
		mobile: /^1([38][0-9]|4[579]|5[0-3,5-9]|6[6]|7[0135678]|9[0-9])\d{8}$/
	}, layCascader, areaTree = [];

layui.use(['form', 'layCascader', 'laydate', 'laytpl'], function () {
	form = layui.form;
	laydate = layui.laydate;
	laytpl = layui.laytpl;
	layCascader = layui.layCascader;
	form.render();

	currentDate.setDate(currentDate.getDate() - 7);

	var birthday = $(".birthday").val();
	$("input[name=birthday]").attr("value", ns.time_to_date(birthday, "Y-m-d"));

	//开始时间
	laydate.render({
		elem: '#start_date',
		type: 'datetime'
	});

	//结束时间
	laydate.render({
		elem: '#end_date',
		type: 'datetime'
	});

	form.verify({
		mobile: function (value) {
			if (value == '') {
				return;
			}
			if (!ns.parse_mobile(value)) {
				return '请输入正确的手机号码!';
			}
		},
		isemail: function (value) {
			var reg = /^[a-z0-9]+([._\\-]*[a-z0-9])*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+.){1,63}[a-z0-9]+$/;
			if (value == '') {
				return;
			}
			if (!reg.test(value)) {
				return '请输入正确的邮箱!';
			}
		},
		num: function (value) {
			var arrMen = value.split(".");
			var val = 0;
			if (arrMen.length == 2) {
				val = arrMen[1];
			}

			if (value == "") {
				return false;
			}
			if (val.length > 2) {
				return '保留小数点后两位'
			}
		}
	});

	var upload = new Upload({
		elem: '#headImg'
	});

	form.on('submit(save)', function (data) {

		data.field.member_level_name = $(".member_level").find("option[value=" + data.field.member_level + "]").text();

		if (data.field.status == undefined) {
			data.field.status = 0;
		}

		// 删除图片
		if (!data.field.headimg) upload.delete();

		if (repeat_flag) return false;
		repeat_flag = true;

		$.ajax({
			url: ns.url("shop/member/editMember"),
			data: data.field,
			dataType: 'JSON', //服务器返回json格式数据
			type: 'POST', //HTTP请求类型
			success: function (res) {
				repeat_flag = false;
				if (res.code == 0) {
					layer.confirm('编辑成功', {
						title: '操作提示',
						btn: ['返回列表', '继续操作'],
						yes: function(index, layero) {
							location.hash = ns.hash("shop/member/memberList")
							layer.close(index);
						},
						btn2: function(index, layero) {
							listenerHash(); // 刷新页面
							layer.close(index);
						}
					});
				} else {
					layer.msg(res.message);
				}
			}
		});
	});

	//根据账户类型获取来源类型
	form.on('select(account_type)', function (data) {
		$.ajax({
			type: "POST",
			url: ns.url("shop/member/getfromtype"),
			data: {type: data.value},
			dataType: 'JSON',
			success: function (res) {

				var html = '<option value="">请选择</option>';
				$.each(res, function (k, v) {
					html += '<option value="' + k + '">' + v.type_name + '</option>';
				});

				$('.from_type').html(html);
				form.render();
			}
		});
	});

	form.on('submit(search)', function (data) {
		table.reload({
			page: {
				curr: 1
			},
			where: data.field
		});
		return false;
	});

	form.on('submit(savePoint)', function (data) {
		if (repeat_flag) return false;
		repeat_flag = true;
		if (data.field.adjust_num == 0) {
			layer.msg('调整数值不能为0');
			repeat_flag = false;
			return;
		}

		var after_value = point * 1 + data.field.adjust_num * 1;
		if (after_value < 0) {
			layer.msg('调整后积分不可以为负数');
			repeat_flag = false;
			return;
		}
		if(after_value >= 99999999){
			layer.msg('调整后积分整数位不能超出99999999');
			repeat_flag = false;
			return;
		}

		$.ajax({
			type: "POST",
			url: ns.url("shop/member/adjustPoint"),
			data: data.field,
			dataType: 'JSON',
			success: function (res) {
				layer.msg(res.message);
				if (res.code == 0) {
					$("#member_point").html(res.data.point);
					$("#member_point").next().attr('data-num', res.data.point);
					point = res.data.point;
					layer.closeAll('page');
					table.reload();
				} else {
					repeat_flag = false;
				}
			}
		});
	});

	form.on('submit(saveBalance)', function (data) {
		if (repeat_flag) return false;
		repeat_flag = true;
		if (data.field.adjust_num == 0) {
			layer.msg('调整数值不能为0');
			repeat_flag = false;
			return;
		}

		var after_value = balance * 1 + data.field.adjust_num * 1;
		if (after_value < 0) {
			layer.msg('调整后储值余额不可以为负数');
			repeat_flag = false;
			return;
		}
		if(after_value >= 99999999){
			layer.msg('调整后储值余额整数位不能超出99999999');
			repeat_flag = false;
			return;
		}

		$.ajax({
			type: "POST",
			url: ns.url("shop/member/adjustBalance"),
			data: data.field,
			dataType: 'JSON',
			success: function (res) {
				repeat_flag = false;
				if(res.code == 0) {
					$("#member_balance").html(res.data.balance);
					$("#member_balance").next().attr('data-num', res.data.balance);
					balance = res.data.balance;
					layer.closeAll('page');
					table.reload();
				}else{
					layer.msg(res.message);
				}
			}
		});
	});

	form.on('submit(saveGrowth)', function (data) {
		if (repeat_flag) return false;
		repeat_flag = true;
		if (data.field.adjust_num == 0) {
			layer.msg('调整数值不能为0');
			repeat_flag = false;
			return;
		}
		var after_value = growth * 1 + data.field.adjust_num * 1;
		if (after_value < 0) {
			layer.msg('调整后成长值不可以为负数');
			repeat_flag = false;
			return;
		}
		if(after_value >= 99999999){
			layer.msg('调整后成长值整数位不能超出99999999');
			repeat_flag = false;
			return;
		}
		$.ajax({
			type: "POST",
			url: ns.url("shop/member/adjustGrowth"),
			data: data.field,
			dataType: 'JSON',
			success: function (res) {
				layer.msg(res.message);

				if (res.code == 0) {
					$("#member_growth").html(res.data.growth);
					$("#member_growth").next().attr('data-num', res.data.growth);
					growth = res.data.growth;
					layer.closeAll('page');
					table.reload();
				} else {
					repeat_flag = false;
				}
			}
		});
	});

	form.on('checkbox(memberlevel)', function (data) {
		$('#setMemberLevel input[type="checkbox"]').prop('checked', false);
		$(data.elem).prop('checked', true);
		form.render();
	})

});

/**
 * 重新渲染结束时间
 * */
function reRender() {
	$("#reg_end_date").remove();
	$(".end-time").html('<input type="text" class="layui-input" name="reg_end_date" id="reg_end_date" placeholder="请输入结束时间">');
	laydate.render({
		elem: '#reg_end_date',
		min: minDate
	});
}

function savePoint(e) {
	var point = $(e).attr('data-num');
	var data = {
		point: point
	};
	laytpl($("#point").html()).render(data, function (html) {
		layer.open({
			title: '调整积分',
			skin: 'layer-tips-class',
			type: 1,
			area: ['800px'],
			content: html,
			end: function () {
				repeat_flag = false;
			}
		});
	});

	$(".integral-bounced .amount input").on("input propertychange", function (val) {
		var newIntegral = parseInt($(this).val());
		if (!isNaN(newIntegral)) $(this).val(newIntegral);
		var currIntegral = parseInt($(".integral-bounced .account-value").text());

		if (newIntegral + currIntegral < 0) {
			layer.msg("调整数额与当前值积分数相加不能小于0");
			$(this).val(-currIntegral);
			return false;
		}

	})
}

function saveBalance(e) {
	var balance = $(e).attr('data-num');
	var data = {
		balance: balance
	};
	laytpl($("#balance").html()).render(data, function (html) {
		layer.open({
			title: '调整储值余额',
			skin: 'layer-tips-class',
			type: 1,
			area: ['800px'],
			content: html,
			end: function () {
				repeat_flag = false;
			}
		});
	});
}

function saveGrowth(e) {
	var growth = $(e).attr('data-num');
	var data = {
		growth: growth
	};
	laytpl($("#growth").html()).render(data, function (html) {
		layer.open({
			title: '调整成长值',
			skin: 'layer-tips-class',
			type: 1,
			area: ['800px'],
			content: html,
			end: function () {
				repeat_flag = false;
			}
		});
	});
}

function editMember(data, callback) {
	if (repeat_flag) return false;
	repeat_flag = true;

	data.member_id = member_id;

	$.ajax({
		url: ns.url("shop/member/editMember"),
		data: data,
		dataType: 'JSON', //服务器返回json格式数据
		type: 'POST', //HTTP请求类型
		success: function (res) {
			repeat_flag = false;
			typeof callback == 'function' && callback(res);
		}
	});
}

function editMemberLevel() {
	laytpl($("#memberLevel").html()).render({}, function (html) {
		layer.open({
			title: '设置会员等级',
			skin: 'select-level-layer',
			type: 1,
			area: ['400px', '300px'],
			content: html,
			success: function () {
				form.render();

				form.on('radio(level_type)', function (data) {
					$('.level-type').hide();
					$('.level-type.type-' + data.value).show();
				})

				form.on('select(member_card)', function (data) {
					$('.member-card').hide();
					$('.member-card-' + data.value).show();
				})
			},
			btn: ['保存', '取消'],
			yes: function () {
				layer.confirm('是否确定变更该客户的会员等级？', {title: '会员等级变更确认'}, function (index) {
					var data = {
						level_type: $('[name="level_type"]:checked').val() || 0,
						member_id: member_id
					}
					data.level_id = data.level_type == 0 ? $('[name="member_level"]').val() : $('[name="member_card"]').val();
					if (data.level_id == 0) {
						layer.msg((data.level_type == 0 ? '请选择会员等级' : '请选择会员卡'));
						return;
					}
					if (data.level_type == 1) data.period_unit = $('[name="member_card_' + data.level_id + '"]:checked').val();
					layer.close(index);
					$.ajax({
						type: "POST",
						url: ns.url("shop/member/handleMember"),
						data: data,
						dataType: 'JSON',
						success: function (res) {
							listenerHash(); // 刷新页面
							layer.msg(res.message);
							layer.closeAll();
						}
					});
					layer.closeAll();
				});
			}
		});
	});
}

function editNickname(event) {
	var nickname = $(event).prev('span').text();
	var html = `<div class="layui-form form-wrap">
						<div class="layui-form-item">
							<label class="layui-form-label" style="width:auto"><span class="required">*</span>昵称：</label>
							<div class="layui-input-block" style="margin-left: 0;">
								<input name="nickname" type="text" lay-verify="required" value="` + nickname + `" class="layui-input len-mid">
							</div>
						</div>
					</div>`;
	layer.open({
		title: '编辑昵称',
		skin: 'edit-member-layer',
		type: 1,
		area: '360px',
		content: html,
		success: function () {
			form.render();
		},
		btn: ['保存', '取消'],
		yes: function () {
			var data = {nickname: $.trim($('[name="nickname"]').val())};
			if (!reg.required.test(data.nickname)) {
				layer.msg('请输入昵称', {icon: 5});
				return;
			}
			editMember(data, function (res) {
				if (res.code == 0) {
					$(event).prev('span').text(data.nickname);
					layer.closeAll();
				} else {
					layer.msg(res.message);
				}
			});
		}
	})
}


function editRealName(event) {
	var realname = $.trim($(event).prev('span').text()) != '暂无' ? $.trim($(event).prev('span').text()) : '';
	var html = `<div class="layui-form form-wrap">
						<div class="layui-form-item">
							<label class="layui-form-label" style="width:auto"><span class="required">*</span>真实姓名：</label>
							<div class="layui-input-block" style="margin-left: 0;">
								<input name="realname" type="text" lay-verify="required" value="` + realname + `" class="layui-input len-mid">
							</div>
						</div>
					</div>`;
	layer.open({
		title: '真实姓名',
		skin: 'edit-member-layer',
		type: 1,
		area: '380px',
		content: html,
		success: function () {
			form.render();
		},
		btn: ['保存', '取消'],
		yes: function () {
			var data = {realname: $.trim($('[name="realname"]').val())};
			if (!reg.required.test(data.realname)) {
				layer.msg('请输入真实姓名', {icon: 5});
				return;
			}
			editMember(data, function (res) {
				if (res.code == 0) {
					$(event).prev('span').text(data.realname);
					layer.closeAll();
				} else {
					layer.msg(res.message);
				}
			});
		}
	})
}

function editMobile(event) {
	var mobile = $.trim($(event).prev('span').text()) != '暂无' ? $.trim($(event).prev('span').text()) : '';
	var html = `<div class="layui-form form-wrap">
						<div class="layui-form-item">
							<label class="layui-form-label" style="width:auto"><span class="required">*</span>手机号：</label>
							<div class="layui-input-block" style="margin-left: 0;">
								<input name="mobile" type="text" lay-verify="required" value="` + mobile + `" class="layui-input len-mid">
							</div>
						</div>
					</div>`;
	layer.open({
		title: '编辑手机号',
		skin: 'edit-member-layer',
		type: 1,
		area: '370px',
		content: html,
		success: function () {
			form.render();
		},
		btn: ['保存', '取消'],
		yes: function () {
			var data = {mobile: $.trim($('[name="mobile"]').val())};
			if (!reg.required.test(data.mobile)) {
				layer.msg('手机号', {icon: 5});
				return;
			}
			if (!ns.parse_mobile(data.mobile)) {
				layer.msg('请输入正确的手机号', {icon: 5});
				return;
			}
			editMember(data, function (res) {
				if (res.code == 0) {
					$(event).prev('span').text(data.mobile);
					layer.closeAll();
				} else {
					layer.msg(res.message);
				}
			});
		}
	})
}

function editBirthday(event) {
	var birthday = $(event).prev('span').attr('data-value');
	var html = `<div class="layui-form form-wrap">
						<div class="layui-form-item">
							<label class="layui-form-label" style="width:auto"><span class="required">*</span>生日：</label>
							<div class="layui-input-block" style="margin-left: 0;">
								<input name="birthday" type="text" id="birthday" value="" class="layui-input len-mid">
							</div>
						</div>
					</div>`;
	layer.open({
		title: '生日',
		skin: 'edit-member-layer',
		type: 1,
		area: '360px',
		content: html,
		success: function () {
			laydate.render({
				elem: '#birthday',
				max: 0
			});
			form.render();
		},
		btn: ['保存', '取消'],
		yes: function () {
			var data = {birthday: $('[name="birthday"]').val()};
			if (!reg.required.test(data.birthday)) {
				layer.msg('请选择生日', {icon: 5});
				return;
			}
			editMember(data, function (res) {
				if (res.code == 0) {
					$(event).prev('span').text(data.birthday);
					// $(event).prev('span').attr('data-value', data.sex);
					layer.closeAll();
				} else {
					layer.msg(res.message);
				}
			});
		}
	})
}

function editSex(event) {
	var sex = $(event).prev('span').attr('data-value');
	var html = `<div class="layui-form form-wrap">
						<div class="layui-form-item">
							<label class="layui-form-label" style="width:auto"><span class="required">*</span>性别：</label>
							<div class="layui-input-block" style="margin-left: 0;">
								<input type="radio" name="sex" value="1" title="男" ` + (sex == 1 ? 'checked' : '') + `>
								<input type="radio" name="sex" value="2" title="女" ` + (sex == 2 ? 'checked' : '') + `>
							</div>
						</div>
					</div>`;
	layer.open({
		title: '性别',
		skin: 'edit-member-layer',
		type: 1,
		area: '270px',
		content: html,
		success: function () {
			form.render();
		},
		btn: ['保存', '取消'],
		yes: function () {
			var data = {sex: $('[name="sex"]:checked').val()};
			if (!data.sex) {
				layer.msg('请选择性别', {icon: 5});
				return;
			}
			editMember(data, function (res) {
				if (res.code == 0) {
					$(event).prev('span').text($('[name="sex"]:checked').attr('title'));
					$(event).prev('span').attr('data-value', data.sex);
					layer.closeAll();
				} else {
					layer.msg(res.message);
				}
			});
		}
	})
}

function editMemberAddress(event) {
	var html = `<div class="layui-form form-wrap">
			<div class="layui-form-item">
				<label class="layui-form-label" style="width:auto"><span class="required">*</span>所在地区：</label>
				<div class="layui-input-inline len-mid" style="margin: 0;">
					<input name="show_full_address" type="text" lay-verify="required" value="${full_address}" class="layui-input len-mid">
				</div>
			</div>
		</div>
		<input name="province_id" type="hidden" value="${province_id}" />
		<input name="city_id" type="hidden" value="${city_id}" />
		<input name="district_id" type="hidden" value="${district_id}" />
		<input name="full_address" type="hidden" value="${full_address}" />
		<div class="layui-form form-wrap">
			<div class="layui-form-item">
				<label class="layui-form-label" style="width:auto"><span class="required">*</span>详细地址：</label>
				<div class="layui-input-block" style="margin-left: 0;">
					<input name="address" type="text" lay-verify="required" value="${address}" class="layui-input len-mid">
				</div>
			</div>
		</div>`;
	layer.open({
		title: '会员地址',
		skin: 'edit-member-layer',
		type: 1,
		area: '380px',
		content: html,
		success: function () {
			form.render();
			if (!areaTree.length) {
				$.ajax({
					url: ns.url("shop/express/getareatree"),
					dataType: 'JSON',
					type: 'POST',
					async: false,
					success: function (res) {
						areaTree = res.data;
					}
				})
			}

			if($('[name="show_full_address"]').length) {
				var _cascader = layCascader({
					elem: $('[name="show_full_address"]'),
					options: areaTree,
					separator: '-',
					props: {
						value: 'id',
						label: 'name',
						children: 'children'
					}
				});

				if (full_address) {
					_cascader.setValue(parseInt(district_id));
				}

				_cascader.changeEvent(function (value, node) {
					$('[name="province_id"]').val(node.path[0] ? node.path[0].data.id : 0)
					$('[name="city_id"]').val(node.path[1] ? node.path[1].data.id : 0)
					$('[name="district_id"]').val(node.path[2] ? node.path[2].data.id : 0)
					var fullAddress = [];
					node.path.forEach(function (item) {
						fullAddress.push(item.data.name);
					})
					$('[name="full_address"]').val(fullAddress.join('-'));
				});
			}
		},
		btn: ['保存', '取消'],
		yes: function () {
			var data = {
				province_id: $.trim($('[name="province_id"]').val()),
				city_id: $.trim($('[name="city_id"]').val()),
				district_id: $.trim($('[name="district_id"]').val()),
				address: $.trim($('[name="address"]').val()),
				full_address: $.trim($('[name="full_address"]').val())
			};
			if (!reg.required.test(data.full_address)) {
				layer.msg('请选择所在地区', {icon: 5});
				return;
			}
			if (!reg.required.test(data.address)) {
				layer.msg('请输入详细地址', {icon: 5});
				return;
			}
			editMember(data, function (res) {
				if (res.code == 0) {
					$(event).prev('span').text(data.realname);
					layer.closeAll();
					listenerHash(); // 刷新页面
				} else {
					layer.msg(res.message);
				}
			});
		}
	})
}