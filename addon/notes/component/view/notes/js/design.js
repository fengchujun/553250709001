var notesSetHtml = '<div style="display:none;"></div>';

Vue.component("notes-set", {
	template: notesSetHtml,
	data: function () {
		return {
			data: this.$parent.data,
			goodsSources: {
				initial: {
					text: "默认",
					icon: "iconmofang"
				},
				diy: {
					text: "手动选择",
					icon: "iconshoudongxuanze"
				},
			},
			showContentList: [
				{
					title: "亮点",
					name: "notesLabel"
				},
				{
					title: "阅读数",
					name: "readNum"
				},
				{
					title: "更新时间",
					name: "uploadTime"
				}
			]
		}
	},
	created: function () {
		if (!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法

		this.$parent.data.ignore = ['textColor', 'componentAngle', 'elementBgColor']; //加载忽略内容 -- 其他设置中的属性设置
		this.$parent.data.ignoreLoad = true; // 等待忽略数组赋值后加载

		// 组件所需的临时数据
		this.$parent.data.tempData = {
			goodsSources: this.goodsSources,
			showContentList: this.showContentList,
			methods: {
				selectStyle: this.selectStyle,
				addNotes: this.addNotes,
				changeStatus: this.changeStatus
			}
		};
	},
	methods: {
		changeStatus: function (field) {
			this.$parent.data[field] = this.$parent.data[field] ? 0 : 1;
		},
		addNotes: function () {
			var self = this;
			notesSelect(function (res) {
				self.$parent.data.noteId = [];
				for (var i = 0; i < res.length; i++) {
					self.$parent.data.noteId.push(res[i]);
				}
			}, self.$parent.data.noteId, {});
		},
		selectStyle: function () {
			var self = this;
			layer.open({
				type: 1,
				title: '风格选择',
				area: ['930px', '630px'],
				btn: ['确定', '返回'],
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .notes-list-style").html(),
				success: function (layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.style);
					$(".layui-layer-content input[name='style_name']").val(self.data.styleName);
					$("body").off("click", ".layui-layer-content .style-list-con-notes .style-li-notes").on("click", ".layui-layer-content .style-list-con-notes .style-li-notes", function () {
						$(this).addClass("selected border-color").siblings().removeClass("selected border-color");
						$(".layui-layer-content input[name='style']").val($(this).index() + 1);
						$(".layui-layer-content input[name='style_name']").val($(this).find("span").text());
					});
				},
				yes: function (index, layero) {
					self.data.style = $(".layui-layer-content input[name='style']").val();
					self.data.styleName = $(".layui-layer-content input[name='style_name']").val();
					layer.closeAll()
				}
			});
		}
	}
});