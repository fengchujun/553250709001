export default {
	data() {
		return {
			orderId: null,
			orderNo: "",
			isAnonymous: 0, //是否匿名发布  1.匿名，0.公开

			goodsList: [], //订单列表
			goodsEvalList: [], //评价列表

			imgList: [],
			isEvaluate: 0, //判断是否为追评

			flag: false, //防止重复点击 ,
			evaluateConfig: {
				evaluate_audit: 1,
				evaluate_show: 0,
				evaluate_status: 1
			},

			isIphoneX: false //判断手机是否是iphoneX以上
		};
	},
	onLoad(options) {
		//接收订单号，订单是否是追评等信息
		options.order_id ? (this.orderId = options.order_id) : this.$util.redirectTo('/pages/order/list');

		this.isIphoneX = this.$util.uniappIsIPhoneX();
		if (!this.storeToken) {
			this.$util.redirectTo('/pages_tool/login/index', {
				back: '/pages_tool/order/evaluate?order_id=' + this.orderId
			}, 'redirectTo');
		}
		this.getEvaluateConfig();
		this.getOrderInfo();
	},
	onShow() {
		//初始化重复点击
		this.flag = false;
	},
	methods: {
		//获取订单信息
		getOrderInfo() {
			//获取订单信息
			let data = {
				order_id: this.orderId
			};
			this.$api.sendRequest({
				url: '/api/order/evluateinfo',
				data: data,
				success: res => {
					if (res.code == 0) {
						this.isEvaluate = res.data.evaluate_status
						this.goodsList = res.data.list;
						if (this.goodsList.length) {
							this.orderNo = res.data.list[0].order_no;
						}
						if (this.isEvaluate) {
							for (let i = 0; i < res.data.list.length; i++) {
								let array = [];
								this.imgList.push(array)
								this.goodsEvalList.push({
									order_goods_id: res.data.list[i].order_goods_id,
									goods_id: res.data.list[i].goods_id,
									sku_id: res.data.list[i].sku_id,
									again_content: "",
									again_images: ""
								});
							}
						} else {
							for (let i = 0; i < res.data.list.length; i++) {
								let array = [];
								this.imgList.push(array)
								this.goodsEvalList.push({
									content: '', // 评价内容
									images: "", //图片数组
									scores: 5, // 评分
									explain_type: 1, // 评价类型
									order_goods_id: res.data.list[i].order_goods_id,
									goods_id: res.data.list[i].goods_id,
									sku_id: res.data.list[i].sku_id,
									sku_name: res.data.list[i].sku_name,
									sku_price: res.data.list[i].price,
									sku_image: res.data.list[i].sku_image
								});
							}
						}
					} else {
						this.$util.showToast({
							title: "未获取到订单数据"
						})
						setTimeout(() => {
							this.$util.redirectTo('/pages/order/list', {}, "redirectTo");
						}, 1000)
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				},
				fail() {
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		//监听评分变化
		setStar(e) {
			this.goodsEvalList[e.index].scores = e.value;
			if (e.value >= 4) {
				this.goodsEvalList[e.index].explain_type = 1;
			} else if (1 < e.value && e.value < 4) {
				this.goodsEvalList[e.index].explain_type = 2
			} else {
				this.goodsEvalList[e.index].explain_type = 3
			}
		},
		//切换，是否匿名
		isAll() {
			this.isAnonymous ? this.isAnonymous = 0 : this.isAnonymous = 1
		},
		//添加图片
		addImg(e) {
			let size = this.imgList[e].length ? this.imgList[e].length : 0
			this.$util.upload(6 - size, {
				path: 'evaluateimg'
			}, res => {
				let arr = this.imgList[e]
				arr = arr.concat(res);
				this.imgList[e] = [];
				this.$set(this.imgList, e, arr)
				if (this.isEvaluate) {
					this.goodsEvalList[e].again_images = this.imgList[e].toString()
				} else {
					this.goodsEvalList[e].images = this.imgList[e].toString()
				}
			});
		},
		//删除图片
		deleteImg(i, index, j) {
			this.imgList[index].splice(j, 1);
			if (this.isEvaluate) {
				this.goodsEvalList[index].again_images = this.imgList[index].toString()
			} else {
				this.goodsEvalList[index].images = this.imgList[index].toString()
			}
		},
		// 图片预览
		preview(i, j) {
			let urls = this.imgList[j];
			for (let k = 0; k < urls.length; k++) {
				urls[k] = this.$util.img(urls[k])
			}
			uni.previewImage({
				urls: urls,
				current: i
			});
		},

		//提交评价
		save() {
			if (this.evaluateConfig.evaluate_status == 0) {
				this.$util.showToast({
					title: "商家未开启商品评价功能"
				});
				return;
			}
			for (let i = 0; i < this.goodsEvalList.length; i++) {
				if (this.isEvaluate) {
					if (!this.goodsEvalList[i].again_content.trim().length) {
						this.$util.showToast({
							title: "商品的评价不能为空哦"
						});
						return;
					}
				} else {
					if (!this.goodsEvalList[i].content.trim().length) {
						this.$util.showToast({
							title: "商品的评价不能为空哦"
						});
						return;
					}
				}
			}
			let goodsEvaluate = JSON.stringify(this.goodsEvalList)
			let data = {
				order_id: this.orderId,
				goods_evaluate: goodsEvaluate,
			};
			if (!this.isEvaluate) {
				data.order_no = this.orderNo;
				data.member_name = this.memberInfo.nickname;
				data.member_headimg = this.memberInfo.headimg;
				data.is_anonymous = this.isAnonymous;
			}
			if (this.flag) return;
			this.flag = true;
			this.$api.sendRequest({
				url: this.isEvaluate ? '/api/goodsevaluate/again' : '/api/goodsevaluate/add',
				data: data,
				success: res => {
					if (res.code == 0) {
						this.$util.showToast({
							title: "评价成功"
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages/order/list', {}, "redirectTo");
						}, 1000);
					} else {
						this.$util.showToast({
							title: res.message
						});
						this.flag = false;
					}
				},
				fail: res => {
					this.flag = false;
				}
			});
		},
		imageError(index) {
			this.goodsList[index].sku_image = this.$util.getDefaultImage().goods;
			this.$forceUpdate();
		},
		getEvaluateConfig() {
			this.$api.sendRequest({
				url: '/api/goodsevaluate/config',
				success: res => {
					if (res.code == 0) {
						var data = res.data;
						this.evaluateConfig = data;
					}
				}
			});
		}
	}
};