// 商品详情业务
import {
	Weixin
} from '@/common/js/wx-jssdk.js';

export default {
	data() {
		return {
			skuId: 0,
			goodsId: 0,
			isIphoneX: false, //判断手机是否是iphoneX以上
			whetherCollection: 0,

			//是否开启预览，0：不开启，1：开启
			preview: 0,
			videoContext: '',

			// 媒体,图片,视频

			// 解决每个商品SKU图片数量不同时，无法切换到第一个，导致轮播图显示不出来
			swiperInterval: 1,
			swiperAutoplay: false,
			swiperCurrent: 1,
			switchMedia: 'img',

			//评价
			goodsEvaluate: [{
				member_headimg: '',
				member_name: '',
				content: '',
				images: [],
				create_time: 0,
				sku_name: ''
			}],
			evaluateConfig: {
				evaluate_audit: 1,
				evaluate_show: 0,
				evaluate_status: 1
			},

			// 是否可分享到好物圈
			goodsCircle: false,
			service: null,
			shareUrl: '', // 分享链接
			source_member: 0, //分享人的id
			isCommunity: false, //社群弹窗

			poster: "-1", //海报
			posterMsg: "", //海报错误信息
			posterHeight: 0,
			posterParams: {}, //海报所需参数
			goodsRoute: '',
			posterApi: '',
			goodsAttrShow: false, // 商品属性是否展开

			//门店列表
			storeList: {
				data: [],
				page: 1,
				page_size: 10
			},
			isShowStore: false,
			latitude: null, // 纬度
			longitude: null, // 经度
			evaluateCount: 0, // 商品评论数量
			deliveryType: null, // 配送方式
			isVirtual: 0 ,//是否为虚拟商品
			hasGlobalStore:false,
			saleStore:'all',
			isInitStoreData:false,
		}
	},
	created() {
		this.isIphoneX = this.$util.uniappIsIPhoneX();

		if (this.location) {
			this.latitude = this.location.latitude;
			this.longitude = this.location.longitude;
		}
	},
	watch: {
		location: function (nVal) {
			if (nVal) {
				this.latitude = nVal.latitude;
				this.longitude = nVal.longitude;
				this.getStoreData();
			}
		}
	},
	methods: {
		init(params) {
			this.skuId = params.sku_id;
			this.goodsId = params.goods_id;
			this.preview = params.preview;
			this.source_member = params.source_member;
			this.whetherCollection = params.whetherCollection;
			this.posterParams = params.posterParams;
														
			this.shareUrl = params.shareUrl;
			this.goodsRoute = params.goodsRoute;
			this.posterApi = params.posterApi;
			this.isVirtual = params.isVirtual;
			this.deliveryType = params.deliveryType;
			this.evaluateConfig = params.evaluateConfig;
			this.saleStore = params.sale_store;
			if (this.evaluateConfig.evaluate_show == 1) {
				//商品评论
				this.getGoodsEvaluate(params.evaluateList);
				this.evaluateCount = params.evaluateCount;
			}
			if(	params.goods_class != 2 && params.goods_class !=3) this.isShowStore = true;
			if(!this.isInitStoreData){
				this.isInitStoreData = true;
				this.getStoreData();
			}
			this.getService();

			this.videoContext = uni.createVideoContext('goodsVideo');

			// #ifdef MP-WEIXIN
			this.goodsSyncToGoodsCircle();
			// #endif

		},
		swiperChange(e) {
			this.swiperCurrent = e.detail.current + 1;
		},

		//-------------------------------------服务-------------------------------------

		openMerchantsServicePopup() {
			this.$refs.merchantsServicePopup.open();
		},
		closeMerchantsServicePopup() {
			this.$refs.merchantsServicePopup.close();
		},

		//-------------------------------------门店列表-------------------------------------
		openStoreListPopup() {
			this.$refs.storeListPopup.open();
		},
		closeStoreListPopup() {
			this.$refs.storeListPopup.close();
		},
		getStoreData(){
			//门店列表
			let data = {
				page: this.storeList.page,
				page_size: this.storeList.page_size,
				store_ids:this.saleStore
			};
			if (this.latitude && this.longitude) {
				data.latitude = this.latitude;
				data.longitude = this.longitude;
			}
			this.$api.sendRequest({
				url: '/api/store/page',
				data: data,
				success: res => {
					if (this.storeList.page == 1) this.storeList.data == [];
					if (res.code >= 0 && res.data) {
						this.storeList.data = this.storeList.data.concat(res.data.list);
						res.data.list.forEach(item=>{
							if(item.store_id == this.globalStoreInfo.store_id) this.hasGlobalStore = true;
						})
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		selectStore(item) {
			this.$util.redirectTo('/pages_tool/store/detail', {
				store_id: item.store_id
			});
			this.closeStoreListPopup();
		},
		//-------------------------------------属性-------------------------------------

		switchGoodsAttr() {
			this.goodsAttrShow = !this.goodsAttrShow;
		},
		//-------------------------------------评价-------------------------------------
		//商品评论列表
		getGoodsEvaluate(list) {
			if (list) {
				this.goodsEvaluate = list;
				this.goodsEvaluate.forEach((item, index) => {
					if (this.goodsEvaluate[index].images) this.goodsEvaluate[index].images = this.goodsEvaluate[index].images.split(",");
					if (this.goodsEvaluate[index].is_anonymous == 1) this.goodsEvaluate[index].member_name = this.goodsEvaluate[index].member_name.replace(this.goodsEvaluate[index].member_name.substring(1, this.goodsEvaluate[index].member_name.length - 1), '***')
				})
			}
		},
		// 预览评价图片
		previewEvaluate(index, img_index, field) {
			var paths = [];
			for (let i = 0; i < this.goodsEvaluate[index][field].length; i++) {
				paths.push(this.$util.img(this.goodsEvaluate[index][field][i]));
			}
			uni.previewImage({
				current: img_index,
				urls: paths
			});
		},
		//-------------------------------------关注-------------------------------------
		async collection() {
			if (this.preview) return; // 开启预览，禁止任何操作和跳转

			if (this.storeToken) {

				//未关注添加关注
				if (this.whetherCollection == 0) {
					let res = await this.$api.sendRequest({
						url: "/api/goodscollect/add",
						data: {
							sku_id: this.skuId,
							goods_id: this.goodsSkuDetail.goods_id,
							sku_name: this.goodsSkuDetail.sku_name,
							sku_price: this.goodsSkuDetail.show_price,
							sku_image: this.goodsSkuDetail.sku_image
						},
						async: false,
					});
					var data = res.data;
					if (data > 0) {
						this.whetherCollection = 1;
					}
				} else {
					//已关注取消关注
					let res = await this.$api.sendRequest({
						url: "/api/goodscollect/delete",
						data: {
							goods_id: this.goodsSkuDetail.goods_id
						},
						async: false,
					});
					var data = res.data;
					if (data > 0) {
						this.whetherCollection = 0;
					}
				}
				return this.whetherCollection;
			} else {
				if (this.source_member) {
					this.$refs.login.open(this.shareUrl + '&source_member=' + this.source_member);
				} else {
					this.$refs.login.open(this.shareUrl);
				}
			}
		},
		//-------------------------------------分享-------------------------------------
		// 打开分享弹出层
		openSharePopup() {
			this.$refs.sharePopup.open();
		},
		// 关闭分享弹出层
		closeSharePopup() {
			this.$refs.sharePopup.close();
		},
		copyUrl() {
			let text = this.$config.h5Domain + this.shareUrl;
			if (this.memberInfo && this.memberInfo.member_id) text += '&source_member=' + this.memberInfo.member_id;
			var store_info = this.$store.state.globalStoreInfo;
			if (store_info) text += '&store_id=' + store_info.store_id;
			this.$util.copy(text, () => {
				this.closeSharePopup();
			});
		},

		//-------------------------------------海报-------------------------------------
		// 打开海报弹出层
		openPosterPopup() {
			this.getGoodsPoster();
			this.$refs.sharePopup.close();
		},
		// 关闭海报弹出层
		closePosterPopup() {
			this.$refs.posterPopup.close();
			this.poster = ''
		},
		//生成海报
		getGoodsPoster() {
			uni.showLoading({
				'title': '海报生成中...'
			});
			//活动海报信息
			if (this.memberInfo && this.memberInfo.member_id) this.posterParams.source_member = this.memberInfo.member_id;
			var store_info = this.$store.state.globalStoreInfo;
			if (store_info)  this.posterParams.store_id= store_info.store_id;
			this.$api.sendRequest({
				url: this.posterApi,
				data: {
					page: this.goodsRoute,
					qrcode_param: JSON.stringify(this.posterParams)
				},
				success: res => {
					if (res.code == 0) {
						this.$refs.posterPopup.open();
						this.poster = res.data.path + "?time=" + new Date().getTime();
					} else {
						this.posterMsg = res.message;
						this.$util.showToast({
							title: res.message
						})
					}
					uni.hideLoading();
				},
				fail: err => {
					uni.hideLoading();
				}
			});
		},
		// 预览图片
		previewMedia(index) {
			var paths = [];
			for (let i = 0; i < this.goodsSkuDetail.sku_images.length; i++) {
				paths.push(this.$util.img(this.goodsSkuDetail.sku_images[i], {
					size: 'big'
				}));
			}
			uni.previewImage({
				current: index,
				urls: paths,
				// longPressActions: {
				// 	itemList: ['发送给朋友', '保存图片', '关注'],
				// 	success: function(data) {
				// 		console.log('选中了第' + (data.tapIndex + 1) + '个按钮,第' + (data.index + 1) + '张图片');
				// 	},
				// 	fail: function(err) {
				// 		console.log(err.errMsg);
				// 	}
				// }
			});
		},
		swiperImageError(index) {
			this.goodsSkuDetail.sku_images[index] = this.$util.getDefaultImage().goods;
			this.$forceUpdate();
		},
		// #ifdef MP || APP-PLUS
		//小程序中保存海报
		saveGoodsPoster() {
			let url = this.$util.img(this.poster);
			uni.downloadFile({
				url: url,
				success: (res) => {
					if (res.errMsg == "downloadFile:ok") {
						uni.saveImageToPhotosAlbum({
							filePath: res.tempFilePath,
							success: () => {
								this.$util.showToast({
									title: "保存成功"
								});
							},
							fail: (err) => {
								this.$util.showToast({
									title: "保存失败，请稍后重试"
								});
							}
						});
					}
				},
				fail: (err) => {
					this.$util.showToast({
						title: "保存失败，请稍后重试"
					});
				}
			});
		},
		// #endif
		//售后保障查询
		getService() {
			this.$api.sendRequest({
				url: '/api/goods/aftersale',
				success: res => {
					if (res.code == 0 && res.data) {
						this.service = res.data;
					}
				}
			});
		},

		// #ifdef MP-WEIXIN
		/**
		 *    将商品同步到微信圈子
		 */
		goodsSyncToGoodsCircle() {
			this.$api.sendRequest({
				url: '/goodscircle/api/goods/sync',
				data: {
					goods_id: this.goodsSkuDetail.goods_id
				},
				success: res => {
					if (res.code == 0) {
						this.goodsCircle = true;
					}
				}
			})
		},
		/**
		 * 将商品推荐到微信圈子
		 */
		openBusinessView() {
			if (wx.openBusinessView) {
				wx.openBusinessView({
					businessType: 'friendGoodsRecommend',
					extraData: {
						product: {
							item_code: this.goodsSkuDetail.goods_id,
							title: this.goodsSkuDetail.sku_name,
							image_list: this.goodsSkuDetail.sku_images.map((ele) => {
								return this.$util.img(ele);
							})
						}
					},
					success: function (res) {
						console.log('success', res);
					},
					fail: function (res) {
						console.log('fail', res);
					}
				})
			}
		},
		// #endif
		toEvaluateDetail(id) {
			this.$util.redirectTo('/pages_tool/goods/evaluate', {
				goods_id: id
			});
		},
		showImg(e) {
			//拿到图片的路径里面的内容放在我们数组中
			let contentimg = e.target.dataset.nodes;
			let arrImg = [];
			for (var i = 0; i < contentimg.length; i++) {
				var img = contentimg[i].children;
				if (Array.isArray(img)) {
					for (var j = 0; j < img.length; j++) {
						if (img[j].attrs && img[j].name == "img") {
							if (img[j].attrs.src) {
								arrImg.push(img[j].attrs.src)
							}
						}
					}
				}
			}
			//最后一步就是把我们的所有图片放在预览的api中就可以了
			uni.previewImage({
				current: arrImg,
				urls: arrImg,
			})
		},

		//-------------------------------------社群-------------------------------------

		//添加福利群
		onCommunity() {
			this.isCommunity = true
		},
		onCloseCommunity() {
			this.isCommunity = false
		},

	}

}