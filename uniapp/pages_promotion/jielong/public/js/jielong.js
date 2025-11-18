export default {
	data() {
		return {
			jielong_id: '',
			jielong_info: {},
			jielong_list: [{
				goods_image: '',
				member_headimg: []
			}],
			jielong_buy_page: {},
			time: '',
			timer: '',
			//底部选择选择详情
			num: '', //件数
			money: 0, //金额数
			specifications: {}, // 选规格弹窗内容
			specificationsItem: [{
				spec_name: '',
				spec_value_name: '',
				value: [{
					sku_id: '',
					spec_value_name: ''
				}]
			}], //规格尺寸、颜色列表
			index: 0, //规格尺寸、颜色选择
			zIndex: true, //弹出层层级判断
			shoplist: [], //商品列表
			display: false, //加减添加显示判断
			returnShoping: [], // 返回购物车列表
			shareImage: '', //点击分享后返回的图片
			switchImage: false, // 切换弹窗图片拼接判断
			seeMores: true, // 点击查看更多
			status: '', //判断活动是否开始结束等
			cutting: 5, //初始显示数量
			horseRace: true, //判断跑马灯是否显示
			//轮播图判断
			lantern: {}, //跑马当当前数据
			//分享时详情所用图片
			shareImg: ''
		};
	},
	onLoad(option) {
		this.jielong_id = option.jielong_id;
		if (option.scene) {
			var sceneParams = decodeURIComponent(option.scene);
			sceneParams = sceneParams.split('&');
			if (sceneParams.length) {
				sceneParams.forEach(item => {
					if (item.indexOf('jielong_id') != -1) this.jielong_id = item.split('-')[1];
				});
			}
		}

		//小程序分享接收source_member
		if (option.source_member) {
			uni.setStorageSync('source_member', option.source_member);
		}
		// 小程序扫码进入，接收source_member
		if (option.scene) {
			var sceneParams = decodeURIComponent(option.scene);
			sceneParams = sceneParams.split('&');
			if (sceneParams.length) {
				sceneParams.forEach(item => {
					if (item.indexOf('sku_id') != -1) this.skuId = item.split('-')[1];
					if (item.indexOf('m') != -1) uni.setStorageSync('source_member', item.split('-')[1]);
					if (item.indexOf('is_test') != -1) uni.setStorageSync('is_test', 1);
				});
			}
		}

		this.getJielongDetail();
		this.getJielongBuyPage();
		this.getShopList();
		this.openCartPopup();
	},
	onshow() {
		//记录分享关系
		if (this.storeToken && uni.getStorageSync('source_member')) {
			this.$util.onSourceMember(uni.getStorageSync('source_member'));
		}
	},
	/**
	 * 转发分享
	 */
	onShareAppMessage(res) {
		var title = this.jielong_info.jielong_name;
		let route = this.$util.getCurrentShareRoute(this.memberInfo ? this.memberInfo.member_id : 0);
		var path = route.path;
		return {
			title: title,
			path: path,
			imageUrl: '',
			success: res => {},
			fail: res => {}
		};
	},
	// 分享到微信朋友圈
	onShareTimeline() {
		let route = this.$util.getCurrentShareRoute(this.memberInfo ? this.memberInfo.member_id : 0);
		let query = route.query;
		return {
			title: this.jielong_info.jielong_name,
			query: query,
			imageUrl: ''
		};
	},
	methods: {
		// 登录操作
		toLogin() {
			if (!this.storeToken && this.$refs.login) {
				this.$refs.login.open('/pages_promotion/jielong/jielong?jielong_id=' + this.jielong_id);
			}
		},
		//商品渲染
		getJielongDetail() {
			this.$api.sendRequest({
				url: '/jielong/api/Goods/jielongDetail',
				data: {
					jielong_id: this.jielong_id
				},
				success: res => {
					if (res.code == 0 && res.data && res.data.info) {
						this.status = res.data.info.status;
						this.jielong_info = res.data.info;
						this.jielong_list = res.data.list;
						this.timer = res.timestamp;
						if (this.jielong_info.start_time > this.timer) {
							this.time = this.$util.countDown(this.jielong_info.start_time - this.timer);
						} else if (this.jielong_info.start_time < this.timer && this.jielong_info.end_time >
							this.timer) {
							this.time = this.$util.countDown(this.jielong_info.end_time - this.timer);
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: '未获取到活动信息'
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages/index/index');
						}, 1000);
					}
				}
			});
		},
		// 获取购买人员列表
		getJielongBuyPage() {
			this.$api.sendRequest({
				url: '/jielong/api/Goods/jielongBuyPage',
				data: {
					jielong_id: this.jielong_id
				},
				success: res => {
					if (res.code == 0 && res.data) {
						this.jielong_buy_page = res.data;
						if (this.jielong_buy_page.list.length > 0) {
							this.horseRace = true;
							this.running();
						} else {
							this.horseRace = false;
						}
					}
				}
			});
		},
		goodsImg(imgStr) {
			let imgs = imgStr.split(',');
			return imgs[0] ? this.$util.img(imgs[0], {
				size: 'mid'
			}) : this.$util.getDefaultImage().goods;
		},
		// 获取商品详情
		async getGoodsSkuDetail(skuId) {
			if (!this.storeToken) {
				this.$refs.login.open(this.currentRoute);
				return;
			}

			let res = await this.$api.sendRequest({
				url: '/api/goodssku/getInfoForCategory',
				async: false,
				data: {
					sku_id: skuId
				}
			});
			let data = res.data;

			if (data) {
				this.goodsSkuDetail = data;

				if (this.skuId == 0) this.skuId = this.goodsSkuDetail.sku_id;

				this.goodsSkuDetail.unit = this.goodsSkuDetail.unit || '件';

				this.goodsSkuDetail.show_price = this.goodsSkuDetail.discount_price;

				// 当前商品SKU规格
				if (this.goodsSkuDetail.sku_spec_format) this.goodsSkuDetail.sku_spec_format = JSON.parse(this.goodsSkuDetail.sku_spec_format);

				// 商品SKU格式
				if (this.goodsSkuDetail.goods_spec_format) this.goodsSkuDetail.goods_spec_format = JSON.parse(this.goodsSkuDetail.goods_spec_format);
			} else {
				this.$util.redirectTo('/pages/index/index');
			}
		},
		/**
		 * 结算
		 */
		settlement() {
			var data = [];
			if (this.shoplist.length == 0) return false;
			this.shoplist.forEach(item => {
				data.push(item.cart_id);
			});
			data = data.toString();
			uni.setStorage({
				key: 'orderCreateData',
				data: {
					cart_ids: data,
					jielong_id: this.jielong_id
				},
				success: () => {
					this.$util.redirectTo('/pages/order/payment');
				}
			});
		},
		// 打开弹出层
		CartPopup() {
			if (this.toLogin()) {
				this.toLogin();
				return;
			}
			this.$refs.cartPopup.open();
			this.openCartPopup();
		},
		// 获取购物车列表
		openCartPopup() {
			this.$api.sendRequest({
				url: '/jielong/api/cart/goodsLists',
				data: {
					jielong_id: this.jielong_id
				},
				success: res => {
					this.num = 0;
					this.money = 0;
					let data = res.data;

					if (data.length > 0) {
						data.forEach(item => {
							this.num = parseInt(item.num) + this.num;
							this.money = parseFloat((parseFloat(item.price) * parseInt(item.num) + parseFloat(this.money))).toFixed(2);
						});
					}
					this.returnShoping = data;
				}
			});
		},
		// 关闭优惠券弹出层
		closeCartPopup() {
			this.$refs.cartPopup.close();
		},
		// 减
		singleSkuReduce(data) {
			var sku_id = data.sku_id;
			this.shoplist.forEach(item => {
				var step = data.min_buy > 0 ? data.min_buy : 1;
				if (sku_id == item.sku_id) {
					if (item.num > step) {
						var num = --item.num;
						this.edit(item.cart_id, num);
					} else if (item.num === step || item.num < step) {
						this.deleteItem(item.cart_id);
					}
				}

			});
			this.getJielongDetail();
		},

		//加
		singleSkuPlus(data) {
			if (!this.storeToken) {
				this.toLogin();
				return false;
			}

			if (this.status == 0) {
				uni.showToast({
					title: '活动未开始',
					icon: 'none'
				});
				return false;
			}

			var sku_id = data.sku_id;
			var judge = true;
			var step = data.min_buy > 0 ? data.min_buy : 1;
			var maxBuy = data.goods_stock;
			if (data.max_buy > 0 && data.max_buy < data.goods_stock) {
				maxBuy = data.max_buy;
			}

			if (this.shoplist.length == 0) {
				// 第一次添加
				if (data.is_limit && data.limit_type === 2) {
					if (data.purchased_num + step > maxBuy) {
						uni.showToast({
							title: `您选择的商品，已超过最大限购量`,
							icon: 'none'
						});
						return false;
					}
				} else {
					if (step > maxBuy) {
						uni.showToast({
							title: '您选择的商品，已超过最大限购量',
							icon: 'none'
						});
						return false;
					}
				}
				this.addShop(sku_id, step);
			} else {
				var num = null
				var cart_id = null
				this.shoplist.forEach(item => {
					if (sku_id === item.sku_id) {
						num = item.num + 1;
						cart_id = item.cart_id
						judge = false;
					}
				});
				if (judge) {
					if (data.is_limit && data.limit_type === 2) {
						if (data.purchased_num + step > maxBuy) {
							uni.showToast({
								title: '您选择的商品，已超过最大限购量',
								icon: 'none'
							});
							return false;
						}
					} else {
						if (step > maxBuy) {
							uni.showToast({
								title: '您选择的商品，已超过最大限购量',
								icon: 'none'
							});
							return false;
						}
					}
					this.addShop(sku_id, step);
				} else {
					if (data.is_limit && data.limit_type === 2) {
						if (data.purchased_num + num > maxBuy) {
							uni.showToast({
								title: '您选择的商品，已超过最大限购量',
								icon: 'none'
							});
							return false;
						}
					} else {
						if (num > maxBuy) {
							uni.showToast({
								title: '您选择的商品，已超过最大限购量',
								icon: 'none'
							});
							return false;
						}
					}
					this.edit(cart_id, num);
				}
			}
			this.getJielongDetail();
		},
		// 清空
		clear() {
			if (!this.storeToken) {
				this.toLogin();
				return false;
			}
			this.$api.sendRequest({
				url: '/jielong/api/cart/clear',
				data: {
					jielong_id: this.jielong_id
				},
				success: res => {
					this.getShopList();
					this.openCartPopup();
					this.getJielongDetail();
				}
			});
		},

		// 打开选购弹窗
		codeView(value) {
			if (!this.storeToken) {
				this.toLogin();
				return false;
			}

			if (this.status == 0) {
				uni.showToast({
					title: '活动未开始',
					icon: 'none'
				});
				return false;
			}
			this.switchs(value);
			this.$refs.erWeiPopup.open();
			this.zIndex = false; //控制弹窗与底部结算层级
			this.display = false;
			if (this.shoplist.length == 0) {
				this.display = false;
			} else {
				this.shoplist.forEach(item => {
					// 购物车后内部加入、或加减显示判断
					if (value == item.sku_id) {
						this.display = true;
					}
				});
			}
		},
		// 关闭选购弹窗
		close() {
			this.$refs.erWeiPopup.close();
			this.zIndex = true;
		},
		// 规格切换
		switchs(sku) {
			this.title = '';
			this.getShopList();
			this.openCartPopup();
			this.$api.sendRequest({
				url: '/api/goodssku/getInfoForCategory',
				data: {
					sku_id: sku
				},
				success: res => {
					this.specifications = res.data;
					this.specificationsItem = JSON.parse(this.specifications.goods_spec_format);
					this.specificationsItem.forEach(items => {
						items.value.forEach(item => {
							if (item.sku_id == sku) {
								this.title = this.title + item.spec_value_name + '/';
							}
						});
					});
					this.specifications.title = this.title;
					this.switchImage = this.specifications.sku_image.includes('http');
				}
			});
			this.display = false;
			this.shoplist.forEach(item => {
				// 购物车后内部加入、或加减显示判断
				if (sku == item.sku_id) {
					this.display = true;
				}
			});
		},

		// 获取已选商品列表
		getShopList() {
			this.toLogin();
			this.$api.sendRequest({
				url: '/jielong/api/cart/lists',
				data: {
					jielong_id: this.jielong_id
				},
				success: res => {
					this.shoplist = res.data;
				}
			});
		},
		// 添加购物车商品
		addShop(sku, num = 1) {
			this.$api.sendRequest({
				url: '/jielong/api/cart/add',
				data: {
					jielong_id: this.jielong_id,
					num,
					sku_id: sku
				},
				success: res => {
					this.getShopList();
					this.openCartPopup();
					this.getJielongDetail();
					if (res.code == -1) {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		// 编辑购物车
		edit(cret, num) {
			this.$api.sendRequest({
				url: '/jielong/api/cart/edit',
				data: {
					jielong_id: this.jielong_id,
					num: num,
					cart_id: cret
				},
				success: res => {
					this.getShopList();
					this.openCartPopup();
					this.getJielongDetail();
					if (res.code == -1) {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		// 删除购物车
		deleteItem(cret) {
			this.$api.sendRequest({
				url: '/jielong/api/cart/delete',
				data: {
					num: 0,
					cart_id: cret
				},
				success: res => {
					this.getShopList();
					this.openCartPopup();
					this.getJielongDetail();
				}
			});
			this.display = false;
		},
		// 弹窗添加购物车事件
		shoppingCart(value) {
			this.display = true;
			let sku = value.sku_id;
			this.addShop(sku);
			this.openCartPopup();
			this.getJielongDetail();
		},
		// 分享
		share() {
			if (!this.storeToken) {
				this.toLogin();
				return false;
			}
			this.shareImage = '';
			uni.showLoading({
				title: '加载中'
			});
			this.$refs.share.open();
			let id = JSON.stringify({
				jielong_id: this.jielong_id
			});
			this.$api.sendRequest({
				url: '/jielong/api/goods/poster',
				data: {
					page: '/pages_promotion/jielong/jielong',
					qrcode_param: id
				},
				success: res => {
					uni.hideLoading();
					this.shareImage = res.data.poster_path;
				}
			});
		},
		// 订单
		order() {
			this.$util.redirectTo('/pages/order/list');
		},
		// 底部图片显示判断
		bottomImage(item) {
			if (item.includes('http')) {
				return true;
			} else {
				return false;
			}
		},
		//判断查看更多
		seeMore() {
			this.seeMores = !this.seeMores;
			if (this.seeMores) {
				this.cutting = 5;
			} else {
				this.cutting = this.jielong_buy_page.count;
			}
		},
		// 跑马灯效果
		running() {
			var data = JSON.parse(JSON.stringify(this.jielong_buy_page.list));
			var i = 1;
			var content = data[0].nickname;
			content = content.slice(0, 1);
			data[0].content = content + '**';
			this.lantern = data[0];
			setInterval(() => {
				if (i < data.length) {
					content = data[i].nickname;
					content = content.slice(0, 1);
					data[i].content = content + '**';
					this.lantern = data[i];
					i++;
				} else {
					i = 0;
				}
			}, 10000);
		}
	}
}