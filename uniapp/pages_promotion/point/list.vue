<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="conteiner">
		<!-- #ifdef MP-WEIXIN -->
		<view class="point-navbar" :style="{'padding-top': menuButtonBounding.top + 'px', height: menuButtonBounding.height + 'px' }">
			<view class="nav-wrap" :style="{width: menuButtonBounding.left + 'px'}">
				<view class="back" @click="back" :style="{width: menuButtonBounding.height + 'px', height: menuButtonBounding.height + 'px' }">
					<text class="iconfont icon-back_light"></text>
				</view>
				<view class="search" @click="$util.redirectTo('/pages_tool/goods/search')">
					<text class="iconfont icon-sousuo3"></text>
					<text class="tips">搜索商品</text>
				</view>
				<view class="sign" :style="{width: menuButtonBounding.height + 'px', height: menuButtonBounding.height + 'px' }" @click="redirect('/pages_tool/member/signin')">
					<image :src="$util.img('public/uniapp/point/navbar-sing-icon.png')" mode="widthFix"></image>
				</view>
			</view>
		</view>
		<view class="point-navbar-block" :style="{ height: menuButtonBounding.bottom + 'px' }"></view>
		<!-- #endif -->

		<scroll-view scroll-y="true" class="point-scroll-view" @scrolltolower="getData">
			<view class="point-wrap" :style="{'background-position-y': -menuButtonBounding.bottom + 'px'}">
				<view class="head-box">
					<view class="account-content">
						<view class="left">
							<image :src="$util.img('public/uniapp/point/point-icon.png')" mode="widthFix"></image>
							<view>我的积分</view>
						</view>
						<view class="right" @click="redirect('/pages_tool/member/point')">
							<text class="point price-font">{{point}}</text>
							<text class="text">积分</text>
							<text class="iconfont icon-right"></text>
						</view>
					</view>
					<view class="remark">
						<view class="label">提醒</view>
						<view class="text">积分兑好礼，每日上新换不停！</view>
					</view>
				</view>

				<view class="menu-wrap">
					<view class="menu-list">
						<view class="menu-item" @click="openPointPopup()">
							<image :src="$util.img('/public/uniapp/point/point-rule.png')" class="menu-img"></image>
							<image :src="$util.img('/public/uniapp/point/must-see.png')" class="menu-tag"></image>
							<view class="title">活动规则</view>
						</view>
						<view class="menu-item" @click="redirect('/pages_tool/recharge/list')">
							<image :src="$util.img('/public/uniapp/point/recharge.png')" class="menu-img"></image>
							<image :src="$util.img('/public/uniapp/point/high.png')" class="menu-tag"></image>
							<view class="title">储值赚积分</view>
						</view>
						<view class="menu-item" @click="redirect('/pages_promotion/point/order_list')">
							<image :src="$util.img('/public/uniapp/point/exchange-record.png')" class="menu-img"></image>
							<view class="title">兑换记录</view>
						</view>
						<view class="menu-item" @click="luckdraw">
							<image :src="$util.img('/public/uniapp/point/luck-draw.png')" class="menu-img"></image>
							<view class="title">积分抽奖</view>
						</view>
						<view class="menu-item" @click="redirect('/pages_tool/member/point_detail')">
							<image :src="$util.img('/public/uniapp/point/point-detail.png')" class="menu-img"></image>
							<view class="title">积分明细</view>
						</view>
					</view>
				</view>

				<view class="poster-wrap">
					<view class="poster-item" @click="redirect('/pages_tool/recharge/list')">
						<image :src="$util.img('/public/uniapp/point/recharge-poster.png')" mode="widthFix"></image>
					</view>
					<view class="poster-item" @click="luckdraw">
						<image :src="$util.img('/public/uniapp/point/luck-draw-poster.png')" mode="widthFix"></image>
					</view>
				</view>

				<view class="recharge-list-wrap" @click="redirect('/pages_tool/recharge/list')" v-if="rechargeList.length">
					<view class="item-wrap" v-for="(item, index) in rechargeList.slice(0, 4)" :key="index">
						<view class="recharge">储值{{ parseFloat(item.buy_price) }}元</view>
						<view class="point">可得{{ item.point }}积分</view>
						<view class="btn">去储值</view>
					</view>
				</view>

				<view class="body-wrap" :class="{ 'no-login': !storeToken }">
					<view class="point-exchange-wrap exchange-coupon" v-if="couponList.length > 0">
						<view class="card-category-title">
							<text class="before-line"></text>
							<text>积分换券</text>
							<text class="after-line"></text>
						</view>

						<view class="list-wrap">
							<view class="list-wrap-scroll" :class="{'single-row': couponList.length < 3}">
								<view class="list-wrap-item coupon-list-wrap-item" v-for="(couponItem, couponIndex) in couponList" :key="couponIndex" @click="toDetail(couponItem)">
									<view class="img-box">
										<image :src="$util.img('public/uniapp/point/coupon_' + themeStyle.name + '_bg1.png')"/>
									</view>
									<view class="content">
										<view class="coupon" :style="{ backgroundImage: 'url(' + $util.img('public/uniapp/point/coupon_theme-blue_bg1.jpg') + ')' }">
											<view class="coupon_left color-line-border">
												<view class="price price-font">
													<block v-if="couponItem.coupon_type == 'reward'">
														<text>￥</text>
														{{ parseFloat(couponItem.money) }}
													</block>
													<block v-if="couponItem.coupon_type == 'discount'">
														{{ parseFloat(couponItem.discount) }}<text>折</text>
													</block>
												</view>
												<view class="coupon-info">
													<view class="coupon_condition font-size-activity-tag">
														{{ couponItem.at_least == 0 ? '无门槛优惠券' : '满' + parseFloat(couponItem.at_least).toFixed(0) + '可用' }}
													</view>
													<view class="coupon_type font-size-activity-tag" v-if="couponItem.goods_type == 1">全场券</view>
													<view class="coupon_type font-size-activity-tag" v-else-if="couponItem.goods_type == 2||couponItem.goods_type == 3">指定券</view>
												</view>
											</view>
											<view class="coupon_right">
												<view class="coupon_num font-size-tag">{{ couponItem.point }}积分</view>
												<view class="coupon_btn">兑换</view>
											</view>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>

					<view class="point-exchange-wrap exchange-hongbao" v-if="hongbaoList.length > 0">
						<view class="card-category-title">
							<text class="before-line"></text>
							<text>积分换红包</text>
							<text class="after-line"></text>
						</view>

						<view class="list-wrap">
							<view class="list-wrap-item hongbao-list-wrap-item" v-for="(hongbaoItem, hongbaoIndex) in hongbaoList" :key="hongbaoIndex" @click="toDetail(hongbaoItem)">
								<view class="img-box">
									<image :src="$util.img('public/uniapp/point/hongbao_bg.png')"></image>
								</view>
								<view class="content">
									<view class="coupon hongbao">
										<view class="coupon_left">
											<view class="price price-font">
												<text>￥</text>
												{{ parseFloat(hongbaoItem.balance).toFixed(0) }}
											</view>
											<!-- <view class="coupon_condition font-size-activity-tag">{{ hongbaoItem.name }}</view> -->
										</view>
										<view class="coupon_right">
											<view class="coupon_num  font-size-tag">{{ hongbaoItem.point }}积分</view>
											<view class="coupon_btn">兑换</view>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>

					<view class="point-exchange-wrap" v-if="goodsList.length > 0">
						<view class="card-category-title">
							<text class="before-line"></text>
							<text>积分换礼品</text>
							<text class="after-line"></text>
						</view>

						<view class="list-wrap">
							<view class="goods-list double-column" v-if="goodsList.length">
								<view class="goods-item " v-for="(item, index) in goodsList" :key="index">
									<view class="goods-img" @click="toDetail(item)">
										<image :src="goodsImg(item)" mode="widthFix" @error="imgError(index)"></image>
									</view>
									<view class="info-wrap">
										<view class="name-wrap">
											<view class="goods-name" @click="toDetail(item)">{{ item.name }}</view>
										</view>
										<view class="lineheight-clear">
											<view class="discount-price">
												<view>
													<text class="unit price-font point">{{ item.point }}</text>
													<text class="unit  font-size-tag ">积分</text>
												</view>
												<block v-if="item.price > 0 && item.pay_type > 0">
													<text class="unit  font-size-tag">+</text>
													<view>
														<text class="font-size-tag">{{ parseFloat(item.price).toFixed(2).split(".")[0] }}</text>
														<text class="unit  font-size-tag">.{{ parseFloat(item.price).toFixed(2).split(".")[1] }}元</text>
													</view>
												</block>
											</view>
											<view class="btn" @click="toDetail(item)">兑换</view>
										</view>
										<view class="pro-info" v-if="item.stock_show || item.sale_show ">
											<view class="font-size-activity-tag color-tip" v-if="item.stock_show ">
												库存:{{ isNaN(parseInt(item.stock)) ? 0 : parseInt(item.stock) }}</view>
											<view class="font-size-activity-tag color-tip sale" v-if="item.sale_show ">
												已兑:{{ isNaN(parseInt(item.sale_num)) ? 0 : parseInt(item.sale_num) }}
											</view>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</scroll-view>

		<!-- 弹出规则 -->
		<view @touchmove.prevent.stop>
			<uni-popup ref="pointPopup" type="bottom">
				<view class="tips-layer">
					<view class="head" @click="closePointPopup()">
						<view class="title">积分说明</view>
						<text class="iconfont icon-close"></text>
					</view>
					<view class="body">
						<view class="detail margin-bottom">
							<view class="tip">积分的获取</view>
							<view class="font-size-base">1、积分可在注册、签到、分享、消费、充值时获得。</view>
							<view class="font-size-base">2、在购买部分商品时可获得积分。</view>
							<view class="tip">积分的使用</view>
							<view class="font-size-base">1、积分可用于兑换积分中心的商品。</view>
							<view class="font-size-base">2、积分可在参与某些活动时使用。</view>
							<view class="font-size-base">3、积分不得转让，出售，不设有效期。</view>
							<view class="tip">积分的查询</view>
							<view class="font-size-base">1、积分可在会员中心中查询具体数额以及明细。</view>
						</view>
					</view>
				</view>
			</uni-popup>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>
		<!-- 悬浮按钮 -->
		<hover-nav></hover-nav>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>
<script>
	import uniPopup from '@/components/uni-popup/uni-popup.vue';
	export default {
		components: {
			uniPopup
		},
		data() {
			return {
				mescroll: {
					num: 0,
					total: 1,
					loading: false
				},
				categoryList: [{
						id: 1,
						name: '积分换好物'
					},
					{
						id: 2,
						name: '积分换券'
					},
					{
						id: 3,
						name: '积分换红包'
					}
				],
				isLogin: false,
				goodsList: [],
				couponList: [],
				hongbaoList: [],
				point: 0,
				signState: 1, // 签到是否开启
				mpShareData: null, //小程序分享数据
				menuButtonBounding: {
					bottom: 0
				},
				rechargeList: [], //充值套餐
				newestGame: null
			};
		},
		onLoad(option) {
			setTimeout( () => {
				if (!this.addonIsExist.pointexchange) {
					this.$util.showToast({
						title: '商家未开启积分商城',
						mask: true,
						duration: 2000
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index');
					}, 2000);
				}
			},1000);

			// #ifdef MP-WEIXIN
			this.menuButtonBounding = uni.getMenuButtonBoundingClientRect();
			// #endif
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
			this.getData();
			this.getRechargeList();
			this.getNewestGame();
		},
		onShow() {
			//记录分享关系
			if (this.storeToken && uni.getStorageSync('source_member')) {
				this.$util.onSourceMember(uni.getStorageSync('source_member'));
			}

			//小程序分享
			// #ifdef MP-WEIXIN
			this.$util.getMpShare().then(res => {
				this.mpShareData = res;
			});
			// #endif

			if (this.storeToken) this.getAccountInfo();
			this.getCouponList();
			this.getHongbaoList();
			this.getSignState();
		},
		methods: {
			// 签到是否开启
			getSignState() {
				this.$api.sendRequest({
					url: '/api/membersignin/getSignStatus',
					success: res => {
						if (res.code == 0) {
							this.signState = res.data.is_use;
						}
					}
				});
			},
			jumpPage(url) {
				this.$util.redirectTo(url);
			},
			// 打开积分说明弹出层
			openPointPopup() {
				this.$refs.pointPopup.open();
			},
			// 打开积分说明弹出层
			closePointPopup() {
				this.$refs.pointPopup.close();
			},
			// 优惠券
			getCouponList() {
				this.$api.sendRequest({
					url: '/pointexchange/api/goods/page',
					data: {
						page_size: 0,
						type: 2
					},
					success: res => {
						if (res.code == 0 && res.data) {
							this.couponList = res.data.list;
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail() {
						//联网失败的回调
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			// 红包
			getHongbaoList() {
				this.$api.sendRequest({
					url: '/pointexchange/api/goods/page',
					data: {
						page_size: 0,
						type: 3
					},
					success: res => {
						if (res.code == 0 && res.data) {
							this.hongbaoList = res.data.list;
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail() {
						//联网失败的回调
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			//获取积分商品详情
			getData() {
				if (this.mescroll.loading || this.mescroll.num >= this.mescroll.total) return;
				this.mescroll.loading = true;
				this.$api.sendRequest({
					url: '/pointexchange/api/goods/page',
					data: {
						page: this.mescroll.num + 1,
						page_size: 10,
						type: 1
					},
					success: res => {
						let newArr = [];
						let msg = res.message;
						if (res.code == 0 && res.data) {
							newArr = res.data.list;
						} else {
							this.$util.showToast({
								title: msg
							});
						}
						//设置列表数据
						this.mescroll.loading = false;
						this.mescroll.total = res.data.page_count;
						this.mescroll.num += 1;
						if (this.mescroll.num == 1) this.goodsList = []; //如果是第一页需手动制空列表
						this.goodsList = this.goodsList.concat(newArr); //追加新数据
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail() {
						this.mescroll.loading = false;
						//联网失败的回调
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			//跳转至详情页面
			toDetail(item) {
				this.$util.redirectTo('/pages_promotion/point/detail', {
					id: item.id
				});
			},
			goGoodsList() {
				this.$util.redirectTo('/pages_promotion/point/goods_list');
			},
			//获取个人
			getAccountInfo() {
				this.$api.sendRequest({
					url: '/api/memberaccount/info',
					data: {
						account_type: 'point'
					},
					success: res => {
						if (res.code == 0 && res.data) {
							if (!isNaN(parseFloat(res.data.point))) {
								this.point = parseFloat(res.data.point).toFixed(0);
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			//跳转至登录页面
			login() {
				this.$refs.login.open('/pages_promotion/point/list');
			},
			imgError(index) {
				this.goodsList[index].image = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			},
			goodsImg(data) {
				let img = '';
				switch (data.type) {
					case 1:
						img = this.$util.img(data.image.split(',')[0], {
							size: 'mid'
						});
						break;
					case 2:
						img = data.image ? this.$util.img(data.image) : this.$util.img('public/uniapp/point/coupon.png');
						break;
					case 3:
						img = data.image ? this.$util.img(data.image) : this.$util.img('public/uniapp/point/hongbao.png');
						break;
				}
				return img;
			},
			/**
			 * 跳转
			 * @param {Object} url
			 */
			redirect(url) {
				if (!this.storeToken) {
					this.$refs.login.open(url);
				} else {
					this.$util.redirectTo(url);
				}
			},
			getRechargeList() {
				this.$api.sendRequest({
					url: '/memberrecharge/api/memberrecharge/page',
					data: {
						page_size: 100,
						page: 1
					},
					success: res => {
						if (res.code == 0 && res.data) {
							let rechargeList = [];
							res.data.list.forEach(item => {
								if (item.point > 0) rechargeList.push(item)
							});
							this.rechargeList = rechargeList;
						}
					}
				})
			},
			back() {
				if (getCurrentPages().length > 1) uni.navigateBack({
					delta: 1
				});
				else this.$util.redirectTo('/pages/index/index');
			},
			getNewestGame() {
				this.$api.sendRequest({
					url: '/api/game/newestgame',
					success: res => {
						if (res.code == 0 && res.data) this.newestGame = res.data;
					}
				})
			},
			luckdraw() {
				if (this.newestGame) {
					switch (this.newestGame.game_type) {
						case 'cards':
							this.$util.redirectTo('/pages_promotion/game/cards', {
								id: this.newestGame.game_id
							});
							break;
						case 'egg':
							this.$util.redirectTo('/pages_promotion/game/smash_eggs', {
								id: this.newestGame.game_id
							});
							break;
						case 'turntable':
							this.$util.redirectTo('/pages_promotion/game/turntable', {
								id: this.newestGame.game_id
							});
							break;
					}
				} else {
					this.$util.showToast({
						title: '暂无相关活动'
					});
				}
			}
		},
		//分享给好友
		onShareAppMessage() {
			return this.mpShareData.appMessage;
		},
		//分享到朋友圈
		onShareTimeline() {
			return this.mpShareData.timeLine;
		}
	};
</script>

<style lang="scss">
	@import './public/css/list.scss';
</style>
<style>
	.ns-adv>>>image {
		width: 100%;
		border-radius: 0;
	}
</style>