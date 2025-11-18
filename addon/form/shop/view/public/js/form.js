var vue = new Vue({
	el: "#diyView",
	data: {
		list: [],
		currIndex: 0
	},
	methods: {
		addComponent(name){
			var data = ns.deepclone(formComponents[name]);
			data.id = ns.gen_non_duplicate(6) + this.list.length // 生成随机值
			this.list.push(data);
			this.currIndex = this.list.length - 1;
		},
		verify(){
			var verify = true;
			for (var i = 0; i < this.list.length; i++) {
				var item = this.list[i];
				if (this.$refs[ item.id ] && typeof this.$refs[ item.id ][0].verify == 'function') {
					verify = this.$refs[ item.id ][0].verify();
					if (!verify) {
						this.currIndex = i;
						break;   
					}
				}
			}
			return verify;
		}
	},
	mounted(){
		var oldIndex = 0, self = this;
		$('.diy-view-wrap .draggable-wrap').DDSort({
			//拖拽数据源
			target: '.draggable-element',
			down: function (index) {
				oldIndex = index;
			},
			//拖拽结束
			up: function () {
				var index = $(this).index();
				var temp = self.list.splice(oldIndex, 1);
				self.list.splice(index, 0, temp[0]);
				self.currIndex = index;
			}
		});
	},
	computed: {
		editData: function(){
			return this.list[this.currIndex];
		}
	}
})


// **************************************************** 单行文本框 ******************************************************
var formText = `<div class="form-gorup">
					<div class="label">{{ value.title }}</div>    
					<input type="text" class="input" :placeholder="value.placeholder" v-model="value.default" readonly>
				</div>`;
		
Vue.component('form-text', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	},
	template: formText
})

var formTextEdit = `<div class="attr-wrap" style="height: 730px;">
						<div class="restore-wrap layui-form">
							<h2 class="attr-title">{{ value.title }}</h2>
							<div class="layui-form-item">
								<label class="layui-form-label sm">标题</label>
								<div class="layui-input-block">
									<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
								</div>
							</div>
							<div class="layui-form-item" v-if="value.value.default != undefined">
								<label class="layui-form-label sm">默认值</label>
								<div class="layui-input-block">
									<input type="text" class="layui-input" v-model="value.value.default" placeholder="请输入默认值">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">提示语</label>
								<div class="layui-input-block">
									<input type="text" class="layui-input" v-model="value.value.placeholder" placeholder="请输入提示语">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">是否必填</label>
								<div class="layui-input-block">
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
								</div>
							</div>
						</div>
					</div>`;

Vue.component('form-text-edit', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	template: formTextEdit
})
// **************************************************** 单行文本框 ******************************************************

// **************************************************** 多行文本框 ******************************************************
var formTextarea = `<div class="form-gorup">
						<div class="label">{{ value.title }}</div>
						
						<div class="checkbox-list">
							<textarea class="textarea" :placeholder="value.placeholder" readonly style="resize:none" maxlength="150">{{ value.default }}</textarea>
						</div>
					</div>`;
			
Vue.component('form-textarea', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	},
	template: formTextarea
})

var formTextareaEdit = `<div class="attr-wrap" style="height: 730px;">
							<div class="restore-wrap layui-form">
								<h2 class="attr-title">{{ value.title }}</h2>
								<div class="layui-form-item">
									<label class="layui-form-label sm">标题</label>
									<div class="layui-input-block">
										<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
									</div>
								</div>
								<div class="layui-form-item" v-if="value.value.default != undefined">
									<label class="layui-form-label sm">默认值</label>
									<div class="layui-input-block">
										<textarea class="layui-textarea textarea" placeholder="请输入默认值" v-model="value.value.default" maxlength="150"></textarea>
									</div>
								</div>
								<div class="layui-form-item">
									<label class="layui-form-label sm">提示语</label>
									<div class="layui-input-block">
										<input type="text" class="layui-input" v-model="value.value.placeholder" placeholder="请输入提示语">
									</div>
								</div>
								<div class="layui-form-item">
									<label class="layui-form-label sm">是否必填</label>
									<div class="layui-input-block">
										<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
										<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
									</div>
								</div>
							</div>
						</div>`;

Vue.component('form-textarea-edit', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	mounted: function(){

	},
	template: formTextareaEdit
})
// **************************************************** 多行文本框 ******************************************************

// **************************************************** 下拉框 ******************************************************
var formSelect = `<div class="form-gorup">
					<div class="label">{{ value.title }}</div>   
					<div class="more">
						<span>请选择</span>
						<i class="iconfont iconyoujiantou"></i>
					</div> 
				</div>`;
		
Vue.component('form-select', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			
			for (var i=0; i<this.value.options.length; i++) {
				if (this.value.options[i] == '') {
					layer.msg('选项不能为空', {icon: 5});
					return false;
				}
			}
			
			return true;
		}
	},
	template: formSelect
})

var formSelectEdit = `<div class="attr-wrap" style="height: 730px;">
						<div class="restore-wrap layui-form">
							<h2 class="attr-title">{{ value.title }}</h2>
							<div class="layui-form-item">
								<label class="layui-form-label sm">标题</label>
								<div class="layui-input-block">
									<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">选项</label>
								<div class="layui-input-block">
									<div class="options-wrap">
										<div class="option-item" v-for="(item, index) in value.value.options" :key="index">
											<input class="layui-input" type="text"  v-model="value.value.options[index]" placeholder="请输入选项">
											<i class="iconfont icontrash text-color" v-if="value.value.options.length > 1" @click="delItem(index)"></i>
										</div>
									</div>
									
									<div class="add-item-btn-box">
										<div class="add-item-btn" @click="addItem()">+ 添加单个选项</div>
										<div class="add-item-btn" @click="addItems()">+ 批量添加选项</div>
										
										<div class="add-items-box" :class="{'layui-hide': isHide == 1}">
											<p class="add-items-title">批量添加选项</p>
											<p class="add-items-desc">可添加多个选项，每个选项之间用英文","隔开</p>
											<textarea class="layui-textarea" v-model="add_items"></textarea>
											<div class="add-items-btn">
												<button class="layui-btn layui-btn-primary" @click="closeAddItems()">取消</button>
												<button class="layui-btn" @click="confirmAddItems()">确定</button>
											</div>
										</div>
									</div>
									
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">是否必填</label>
								<div class="layui-input-block">
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
								</div>
							</div>
						</div>
					</div>`;

Vue.component('form-select-edit', {
	template: formSelectEdit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.value.options,
			isHide: 1,
			add_items: ''
		}
	},
	mounted: function(){
	},
	methods: {
		// 添加单个选项
		addItem: function() {
			this.value.value.options.push('');
		},
		// 批量添加选项
		addItems: function() {
			this.isHide = this.isHide ? 0 : 1;
		},
		// 取消批量添加
		closeAddItems: function() {
			this.isHide = 1;
		},
		// 确定批量添加
		confirmAddItems: function() {
			var arr = this.add_items.split(",");
			for (var i=0; i<arr.length; i++) {
				this.value.value.options.push(arr[i]);
			}
			this.isHide = 1;
		},
		// 删除选项
		delItem: function(i) {
			this.value.value.options.splice(i, 1);
		}
	},
})
// **************************************************** 下拉框 ******************************************************


// **************************************************** 多选框 ******************************************************
var formCheckbox = 
	`<div class="form-gorup">
		<div class="label">{{ value.title }}</div>
		<div class="checkbox-list">
			<div class="checkbox-item" v-for="(item, index) in list" :key="index">
				<div class="checkbox" :class="{'bg-color border-color': index == 0}">
					<i v-if="index == 0" class="iconfont iconseleted"></i>
				</div>
				<div class="checkbox-title">{{item}}</div>
			</div>
		</div>
	</div>`;
		
Vue.component('form-checkbox', {
	template: formCheckbox,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.options,
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			
			for (var i=0; i<this.value.options.length; i++) {
				if (this.value.options[i] == '') {
					layer.msg('选项不能为空', {icon: 5});
					return false;
				}
			}
			
			return true;
		}
	}
})

var formCheckboxEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label sm">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">选项</label>
				<div class="layui-input-block">
					<div class="options-wrap">
						<div class="option-item" v-for="(item, index) in value.value.options">
							<input class="layui-input" type="text" v-model="value.value.options[index]" placeholder="请输入选项">
							<i class="iconfont icontrash text-color" v-if="value.value.options.length > 1" @click="delItem(index)"></i>
						</div>
					</div>
					
					<div class="add-item-btn-box">
						<div class="add-item-btn" @click="addItem()">+ 添加单个选项</div>
						<div class="add-item-btn" @click="addItems()">+ 批量添加选项</div>
						
						<div class="add-items-box" :class="{'layui-hide': isHide == 1}">
							<p class="add-items-title">批量添加选项</p>
							<p class="add-items-desc">可添加多个选项，每个选项之间用英文","隔开</p>
							<textarea class="layui-textarea" v-model="add_items"></textarea>
							<div class="add-items-btn">
								<button class="layui-btn layui-btn-primary" @click="closeAddItems()">取消</button>
								<button class="layui-btn" @click="confirmAddItems()">确定</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-checkbox-edit', {
	template: formCheckboxEdit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.value.options,
			isHide: 1,
			add_items: ''
		}
	},
	mounted: function(){

	},
	methods: {
		// 添加单个选项
		addItem: function() {
			this.value.value.options.push('');
		},
		// 批量添加选项
		addItems: function() {
			this.isHide = this.isHide ? 0 : 1;
		},
		// 取消批量添加
		closeAddItems: function() {
			this.isHide = 1;
		},
		// 确定批量添加
		confirmAddItems: function() {
			var arr = this.add_items.split(",");
			for (var i=0; i<arr.length; i++) {
				this.value.value.options.push(arr[i]);
			}
			this.isHide = 1;
		},
		// 删除选项
		delItem: function(i) {
			this.value.value.options.splice(i, 1);
		}
	},
})
// **************************************************** 多选框 ******************************************************


// **************************************************** 单选框 ******************************************************
var formRadio = 
	`<div class="form-gorup">
		<div class="label">{{ value.title }}</div>
		<div class="checkbox-list">
			<div class="checkbox-item" v-for="(item, index) in list" :key="index">
				<div class="checkbox radio" :class="{'bg-color border-color': index == 0}">
					<i v-if="index == 0" class="iconfont iconseleted"></i>
				</div>
				<div class="checkbox-title">{{item}}</div>
			</div>
		</div>
	</div>`;
		
Vue.component('form-radio', {
	template: formRadio,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.options,
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			
			for (var i=0; i<this.value.options.length; i++) {
				if (this.value.options[i] == '') {
					layer.msg('选项不能为空', {icon: 5});
					return false;
				}
			}
			
			return true;
		}
	}
})

var formRadioEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label sm">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">选项</label>
				<div class="layui-input-block">
					<div class="options-wrap">
						<div class="option-item" v-for="(item, index) in value.value.options">
							<input class="layui-input" type="text" v-model="value.value.options[index]" placeholder="请输入选项">
							<i class="iconfont icontrash text-color" v-if="value.value.options.length > 1" @click="delItem(index)"></i>
						</div>
					</div>
					
					<div class="add-item-btn-box">
						<div class="add-item-btn" @click="addItem()">+ 添加单个选项</div>
						<div class="add-item-btn" @click="addItems()">+ 批量添加选项</div>
						
						<div class="add-items-box" :class="{'layui-hide': isHide == 1}">
							<p class="add-items-title">批量添加选项</p>
							<p class="add-items-desc">可添加多个选项，每个选项之间用英文","隔开</p>
							<textarea class="layui-textarea" v-model="add_items"></textarea>
							<div class="add-items-btn">
								<button class="layui-btn layui-btn-primary" @click="closeAddItems()">取消</button>
								<button class="layui-btn" @click="confirmAddItems()">确定</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-radio-edit', {
	template: formRadioEdit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.value.options,
			isHide: 1,
			add_items: ''
		}
	},
	mounted: function(){

	},
	methods: {
		// 添加单个选项
		addItem: function() {
			this.value.value.options.push('');
		},
		// 批量添加选项
		addItems: function() {
			this.isHide = this.isHide ? 0 : 1;
		},
		// 取消批量添加
		closeAddItems: function() {
			this.isHide = 1;
		},
		// 确定批量添加
		confirmAddItems: function() {
			var arr = this.add_items.split(",");
			for (var i=0; i<arr.length; i++) {
				this.value.value.options.push(arr[i]);
			}
			this.isHide = 1;
		},
		// 删除选项
		delItem: function(i) {
			this.value.value.options.splice(i, 1);
		}
	},
})
// **************************************************** 单选框 ******************************************************


// **************************************************** 图片 ******************************************************
var formImg = 
	`<div class="form-gorup">
		<div class="label">{{ value.title }}</div>
		<div class="checkbox-list">
			<div class="img-box">
				<img src="${ns_url.staticImg}/shape.png" />
				<div class="close"><i class="iconfont iconclose_light"></i></div>
			</div>
			<div class="img-box"><i class="iconfont iconadd_light"></i></div>
		</div>
	</div>`;

Vue.component('form-img', {
	template: formImg,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	}
})

var formImgEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label sm">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">最多上传</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.max_count" placeholder="请输入最大上多图片数">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-img-edit', {
	template: formImgEdit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	methods: {
		
	},
})
// **************************************************** 图片 ******************************************************


// **************************************************** 日期 ******************************************************
var formDate = `<div class="form-gorup">
					<div class="label">{{ value.title }}</div>
					<input v-if="value.is_show_default && value.is_current" type="text" class="input" v-model="current_date" readonly>
					<input v-else-if="value.is_show_default && !value.is_current" type="text" class="input" :placeholder="value.placeholder" v-model="value.default" readonly>
					<input v-else="!value.is_show_default" type="text" class="input" :placeholder="value.placeholder" readonly>
					<i class="iconfont iconyoujiantou"></i>
				</div>`;
		
Vue.component('form-date', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			current_date: ''
		}
	},
	created() {
		var day = new Date();
		day.setTime(day.getTime());
		var month = day.getMonth() + 1;
		if (month < 10) {
			month = '0' + month;
		}
		this.current_date = day.getFullYear()+"-" + month + "-" + (day.getDate() < 10 ? '0' + day.getDate() : day.getDate());
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	},
	template: formDate
})

var formDateEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label sm">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item" v-if="value.value.default != undefined">
				<label class="layui-form-label sm">默认值</label>
				<div class="layui-input-block">
					<div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_show_default}" @click="value.value.is_show_default = true"><i class="layui-anim layui-icon"></i><div>显示</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_show_default}" @click="value.value.is_show_default = false"><i class="layui-anim layui-icon"></i><div>隐藏</div></div>
					</div>
					
					<div v-if="value.value.is_show_default">
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_current}" @click="value.value.is_current = true"><i class="layui-anim layui-icon"></i><div>当天日期</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_current}" @click="value.value.is_current = false"><i class="layui-anim layui-icon"></i><div>指定日期</div></div>
					</div>
					
					<div class="date-input" :class="{'layui-hide': !value.value.is_show_default || value.value.is_current}">
						<input type="text" class="layui-input" :id="value.controller" v-model="value.value.default" placeholder="请选择日期" readonly>
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">提示语</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.placeholder" placeholder="请输入提示语">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-date-edit', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	mounted() {
		var self = this;
		var form, laydate;
		layui.use(['form', 'laydate'], function(){
			form = layui.form;
			laydate = layui.laydate;
			
			form.render();
			
			//执行一个laydate实例
			laydate.render({
				elem: '#' + self.value.controller, //指定元素
				done: function (value, date, endDate) {
					self.value.value.default = value;
				}
			});
		});
	},
	template: formDateEdit
})
// **************************************************** 日期 ******************************************************


// **************************************************** 日期范围 ******************************************************
var formDateLimit = 
	`<div class="form-gorup">
		<div class="label">{{ value.title }}</div>
		<div class="checkbox-list">
			<div class="checkbox-item date-limit-box date">
				<input v-if="value.is_show_default_start && value.is_current_start" type="text" class="input" v-model="current_date" readonly>
				<input v-else-if="value.is_show_default_start && !value.is_current_start" type="text" class="input" :placeholder="value.placeholder_start" v-model="value.default_start" readonly>
				<input v-else="!value.is_show_default_start" type="text" class="input" :placeholder="value.placeholder_start" readonly>
			</div>
			<div class="interval">-</div>
			<div class="checkbox-item date-limit-box date">
				<input v-if="value.is_show_default_end && value.is_current_end" type="text" class="input" v-model="current_date" readonly>
				<input v-else-if="value.is_show_default_end && !value.is_current_end" type="text" class="input" :placeholder="value.placeholder_end" v-model="value.default_end" readonly>
				<input v-else="!value.is_show_default_end" type="text" class="input" :placeholder="value.placeholder_end" readonly>
			</div>
		</div>
	</div>`;
		
Vue.component('form-date-limit', {
	template: formDateLimit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.options,
			current_date: ''
		}
	},
	created() {
		var day = new Date();
		day.setTime(day.getTime());
		var month = day.getMonth() + 1;
		if (month < 10) {
			month = '0' + month;
		}
		this.current_date = day.getFullYear()+"-" + month + "-" + (day.getDate() < 10 ? '0' + day.getDate() : day.getDate());
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	}
})

var formDateLimitEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form date-limit-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">起始日期提示语</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.placeholder_start" placeholder="请输入提示语">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">结束日期提示语</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.placeholder_end" placeholder="请输入提示语">
				</div>
			</div>
			<div class="layui-form-item" v-if="value.value.default_start != undefined">
				<label class="layui-form-label">起始日期默认值</label>
				<div class="layui-input-block">
					<div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_show_default_start}" @click="value.value.is_show_default_start = true"><i class="layui-anim layui-icon"></i><div>显示</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_show_default_start}" @click="value.value.is_show_default_start = false"><i class="layui-anim layui-icon"></i><div>隐藏</div></div>
					</div>
					
					<div v-if="value.value.is_show_default_start">
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_current_start}" @click="value.value.is_current_start = true"><i class="layui-anim layui-icon"></i><div>当天日期</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_current_start}" @click="value.value.is_current_start = false"><i class="layui-anim layui-icon"></i><div>指定日期</div></div>
					</div>
					
					<div class="date-input" :class="{'layui-hide': !value.value.is_show_default_start || value.value.is_current_start}">
						<input type="text" class="layui-input" :id="'start' + value.controller" v-model="value.value.default_start" placeholder="请选择日期" readonly>
					</div>
				</div>
			</div>
			<div class="layui-form-item" v-if="value.value.default_end != undefined">
				<label class="layui-form-label">结束日期默认值</label>
				<div class="layui-input-block">
					<div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_show_default_end}" @click="value.value.is_show_default_end = true"><i class="layui-anim layui-icon"></i><div>显示</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_show_default_end}" @click="value.value.is_show_default_end = false"><i class="layui-anim layui-icon"></i><div>隐藏</div></div>
					</div>
					
					<div v-if="value.value.is_show_default_end">
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_current_end}" @click="value.value.is_current_end = true"><i class="layui-anim layui-icon"></i><div>当天日期</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_current_end}" @click="value.value.is_current_end = false"><i class="layui-anim layui-icon"></i><div>指定日期</div></div>
					</div>
					
					<div class="date-input" :class="{'layui-hide': !value.value.is_show_default_end || value.value.is_current_end}">
						<input type="text" class="layui-input" :id="'end' + value.controller" v-model="value.value.default_end" placeholder="请选择日期" readonly>
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-date-limit-edit', {
	template: formDateLimitEdit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.value.options,
			isHide: 1,
			add_items: ''
		}
	},
	mounted() {
		var self = this;
		layui.use(['form', 'laydate'], function(){
			var form = layui.form,
				laydate = layui.laydate;
			
			form.render();
			
			//执行一个laydate实例
			laydate.render({
				elem: '#start' + self.value.controller, //指定元素
				done: function (value, date, endDate) {
					self.value.value.default_start = value;
				}
			});
			
			laydate.render({
				elem: '#end' + self.value.controller, //指定元素
				done: function (value, date, endDate) {
					self.value.value.default_end = value;
				}
			});
		});
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			
			return true;
		},
		
	},
})
// **************************************************** 日期范围 ******************************************************


// **************************************************** 时间 ******************************************************
var formTime = `<div class="form-gorup">
					<div class="label">{{ value.title }}</div>
					<input v-if="value.is_show_default && value.is_current" type="text" class="input" v-model="current_date" readonly>
					<input v-else-if="value.is_show_default && !value.is_current" type="text" class="input" :placeholder="value.placeholder" v-model="value.default" readonly>
					<input v-else="!value.is_show_default" type="text" class="input" :placeholder="value.placeholder" readonly>
					<i class="iconfont iconyoujiantou"></i>
				</div>`;
		
Vue.component('form-time', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			current_date: '00:00'
		}
	},
	created() {
		var date = new Date();
		var hour = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours());
		var minutes = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes());
		this.current_date = hour + ":" + minutes;
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	},
	template: formTime
})

var formTimeEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label sm">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item" v-if="value.value.default != undefined">
				<label class="layui-form-label sm">默认值</label>
				<div class="layui-input-block">
					<div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_show_default}" @click="value.value.is_show_default = true"><i class="layui-anim layui-icon"></i><div>显示</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_show_default}" @click="value.value.is_show_default = false"><i class="layui-anim layui-icon"></i><div>隐藏</div></div>
					</div>
					
					<div v-if="value.value.is_show_default">
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_current}" @click="value.value.is_current = true"><i class="layui-anim layui-icon"></i><div>当前时间</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_current}" @click="value.value.is_current = false"><i class="layui-anim layui-icon"></i><div>指定时间</div></div>
					</div>
					
					<div class="date-input" :class="{'layui-hide': !value.value.is_show_default || value.value.is_current}">
						<input type="text" class="layui-input" :id="value.controller" v-model="value.value.default" placeholder="请选择时间" readonly>
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">提示语</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.placeholder" placeholder="请输入提示语">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label sm">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-time-edit', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	mounted() {
		var self = this;
		var form, laydate;
		layui.use(['form', 'laydate'], function(){
			form = layui.form;
			laydate = layui.laydate;
			
			form.render();
			
			//执行一个laydate实例
			laydate.render({
				elem: '#' + self.value.controller, //指定元素
				type: 'time',
				format: 'HH:mm',
				done: function (value, date, endDate) {
					self.value.value.default = value;
				}
			});
		});
	},
	template: formTimeEdit
})
// **************************************************** 时间 ******************************************************


// **************************************************** 时间范围 ******************************************************
var formTimeLimit = 
	`<div class="form-gorup">
		<div class="label">{{ value.title }}</div>
		<div class="checkbox-list">
			<div class="checkbox-item date-limit-box time">
				<input v-if="value.is_show_default_start && value.is_current_start" type="text" class="input" v-model="current_date" readonly>
				<input v-else-if="value.is_show_default_start && !value.is_current_start" type="text" class="input" :placeholder="value.placeholder_start" v-model="value.default_start" readonly>
				<input v-else="!value.is_show_default_start" type="text" class="input" :placeholder="value.placeholder_start" readonly>
			</div>
			<div class="interval">-</div>
			<div class="checkbox-item date-limit-box time">
				<input v-if="value.is_show_default_end && value.is_current_end" type="text" class="input" v-model="current_date" readonly>
				<input v-else-if="value.is_show_default_end && !value.is_current_end" type="text" class="input" :placeholder="value.placeholder_end" v-model="value.default_end" readonly>
				<input v-else="!value.is_show_default_end" type="text" class="input" :placeholder="value.placeholder_end" readonly>
			</div>
		</div>
	</div>`;
		
Vue.component('form-time-limit', {
	template: formTimeLimit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.options,
			current_date: '00:00'
		}
	},
	created() {
		var date = new Date();
		var hour = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours());
		var minutes = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes());
		this.current_date = hour + ":" + minutes;
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	}
})

var formTimeLimitEdit = 
	`<div class="attr-wrap" style="height: 730px;">
		<div class="restore-wrap layui-form date-limit-form">
			<h2 class="attr-title">{{ value.title }}</h2>
			<div class="layui-form-item">
				<label class="layui-form-label">标题</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">起始时间提示语</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.placeholder_start" placeholder="请输入提示语">
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">结束时间提示语</label>
				<div class="layui-input-block">
					<input type="text" class="layui-input" v-model="value.value.placeholder_end" placeholder="请输入提示语">
				</div>
			</div>
			<div class="layui-form-item" v-if="value.value.default_start != undefined">
				<label class="layui-form-label">起始时间默认值</label>
				<div class="layui-input-block">
					<div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_show_default_start}" @click="value.value.is_show_default_start = true"><i class="layui-anim layui-icon"></i><div>显示</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_show_default_start}" @click="value.value.is_show_default_start = false"><i class="layui-anim layui-icon"></i><div>隐藏</div></div>
					</div>
					
					<div v-if="value.value.is_show_default_start">
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_current_start}" @click="value.value.is_current_start = true"><i class="layui-anim layui-icon"></i><div>当前时间</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_current_start}" @click="value.value.is_current_start = false"><i class="layui-anim layui-icon"></i><div>指定时间</div></div>
					</div>
					
					<div class="date-input" :class="{'layui-hide': !value.value.is_show_default_start || value.value.is_current_start}">
						<input type="text" class="layui-input" :id="'start' + value.controller" v-model="value.value.default_start" placeholder="请选择时间" readonly>
					</div>
				</div>
			</div>
			<div class="layui-form-item" v-if="value.value.default_end != undefined">
				<label class="layui-form-label">结束时间默认值</label>
				<div class="layui-input-block">
					<div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_show_default_end}" @click="value.value.is_show_default_end = true"><i class="layui-anim layui-icon"></i><div>显示</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_show_default_end}" @click="value.value.is_show_default_end = false"><i class="layui-anim layui-icon"></i><div>隐藏</div></div>
					</div>
					
					<div v-if="value.value.is_show_default_end">
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.is_current_end}" @click="value.value.is_current_end = true"><i class="layui-anim layui-icon"></i><div>当前时间</div></div>
						<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.is_current_end}" @click="value.value.is_current_end = false"><i class="layui-anim layui-icon"></i><div>指定时间</div></div>
					</div>
					
					<div class="date-input" :class="{'layui-hide': !value.value.is_show_default_end || value.value.is_current_end}">
						<input type="text" class="layui-input" :id="'end' + value.controller" v-model="value.value.default_end" placeholder="请选择时间" readonly>
					</div>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label">是否必填</label>
				<div class="layui-input-block">
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
					<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
				</div>
			</div>
		</div>
	</div>`;

Vue.component('form-time-limit-edit', {
	template: formTimeLimitEdit,
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0,
			list: this.value.value.options,
			isHide: 1,
			add_items: ''
		}
	},
	mounted() {
		var self = this;
		layui.use(['form', 'laydate'], function(){
			var form = layui.form,
				laydate = layui.laydate;
			
			form.render();
			
			//执行一个laydate实例
			laydate.render({
				elem: '#start' + self.value.controller, //指定元素
				type: 'time',
				format: 'HH:mm',
				done: function (value, date, endDate) {
					self.value.value.default_start = value;
				}
			});
			
			laydate.render({
				elem: '#end' + self.value.controller, //指定元素
				type: 'time',
				format: 'HH:mm',
				done: function (value, date, endDate) {
					self.value.value.default_end = value;
				}
			});
		});
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			
			return true;
		},
		
	},
})
// **************************************************** 时间范围 ******************************************************


// **************************************************** 城市 ******************************************************
var formCity = `<div class="form-gorup">
					<div class="label">{{ value.title }}</div>    
					
					<div class="checkbox-list">
						<input type="text" class="input" :placeholder="value.placeholder" v-model="value.default" readonly>
					</div>
				</div>`;
		
Vue.component('form-city', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	methods: {
		verify: function(){
			if (!/[\S]+/.test(this.value.title)) {
				layer.msg('标题不能为空', {icon: 5});
				return false;
			}
			return true;
		}
	},
	template: formCity
})

var formCityEdit = `<div class="attr-wrap" style="height: 730px;">
						<div class="restore-wrap layui-form">
							<h2 class="attr-title">{{ value.title }}</h2>
							<div class="layui-form-item">
								<label class="layui-form-label sm">标题</label>
								<div class="layui-input-block">
									<input type="text" class="layui-input" v-model="value.value.title" placeholder="请输入标题">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">默认值</label>
								<div class="layui-input-block">
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.default_type}" @click="value.value.default_type = 1"><i class="layui-anim layui-icon"></i><div>省市</div></div>
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.default_type}" @click="value.value.default_type = 0"><i class="layui-anim layui-icon"></i><div>省市区</div></div>
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">提示语</label>
								<div class="layui-input-block">
									<input type="text" class="layui-input" v-model="value.value.placeholder" placeholder="请输入提示语">
								</div>
							</div>
							<div class="layui-form-item">
								<label class="layui-form-label sm">是否必填</label>
								<div class="layui-input-block">
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': value.value.required}" @click="value.value.required = true"><i class="layui-anim layui-icon"></i><div>是</div></div>
									<div class="layui-unselect layui-form-radio" :class="{'layui-form-radioed': !value.value.required}" @click="value.value.required = false"><i class="layui-anim layui-icon"></i><div>否</div></div>
								</div>
							</div>
						</div>
					</div>`;

Vue.component('form-city-edit', {
	props: {
		value: {
			type: Object,
			dafault: {}
		}
	},
	data: function () {
		return {
			count: 0
		}
	},
	template: formCityEdit
})
// **************************************************** 城市 ******************************************************