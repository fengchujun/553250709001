<template>
	<view :style="value.pageStyle" v-if="loading || (computedCouponList && computedCouponList.length)">
		<x-skeleton type="banner" :loading="loading" :configs="skeletonConfig">
			<view class="coupon-wrap" :class="'coupon-box-' + value.style" :style="[
				value.couponType == 'img' && { backgroundImage: 'url(' + $util.img(value.couponBgUrl) + ')' },
				value.couponType == 'color' && { backgroundColor: value.couponBgColor }
			]">

				<template v-if="value.style == '1'">
					<swiper class="coupon-style-one" circular>
						<swiper-item v-for="(numItem, numIndex) in Math.ceil(computedCouponList.length / 3)" :key="numIndex" class="coupon-item-box">

							<view v-for="(item, index) in computedCouponList" class="coupon-item" v-if="index >= [numIndex * 3] && index < [(numIndex + 1) * 3]" :style="{
								color: value.moneyColor,
								backgroundImage: 'url(' + $util.img('public/uniapp/coupon/style1-bg.png') + ')',
								marginRight: couponItemHeight + 'px',
								marginLeft: couponItemHeight + 'px'
							}" @click="couponAction(item, index)">

								<view class="coupon-info">
									<view class="coupon-num" :style="{ color: value.moneyColor }" v-if="!parseInt(item.discount)">
										<text class="font-size-tag coupon-sign">￥</text>
										<text class="coupon-size">{{ item.money | moneyConduct }}</text>
									</view>
									<view class="coupon-num" :style="{ color: value.moneyColor }" v-else>
										<text class="coupon-size">{{ item.discount | moneyConduct }}</text>
										<text class="font-size-tag coupon-sign">折</text>
									</view>
									<view class="coupon-type font-size-tag" :style="{ color: value.limitColor }">
										{{ item.at_least > 0 ? '满' + Number(item.at_least) + '元可用' : '无门槛优惠券' }}
									</view>
								</view>
								
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 0" >{{ value.btnStyle.text }}</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 1" >去使用</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 2">已抢光</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 3">已失效</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 4">已使用</view>
							</view>

						</swiper-item>
					</swiper>
				</template>

				<template v-if="value.style == '2'">
					<swiper class="coupon-style-two" circular>
						<swiper-item v-for="(numItem, numIndex) in Math.ceil(computedCouponList.length / 3)" :key="numIndex" class="coupon-item-box">
							<view class="coupon-item" v-for="(item, index) in computedCouponList" :key="index"
								v-if="index >= [numIndex * 3] && index < [(numIndex + 1) * 3]" :style="{
								color: value.moneyColor,
								backgroundImage: 'url(' + $util.img('public/uniapp/coupon/coupon_bg1.png') + ')',
								marginRight: couponItemHeight + 'px',
								marginLeft: couponItemHeight + 'px'
							}" @click="couponAction(item, index)">
								<view class="coupon-info">
									<view class="coupon-num" :style="{ color: value.moneyColor }" v-if="!parseInt(item.discount)">
										<text class="font-size-tag coupon-sign">￥</text>
										<text class="coupon-size">{{ item.money | moneyConduct }}</text>
									</view>
									<view class="coupon-num" :style="{ color: value.moneyColor }" v-else>
										<text class="coupon-size">{{ item.discount | moneyConduct }}</text>
										<text class="font-size-tag coupon-sign">折</text>
									</view>
									<view class="coupon-type font-size-tag" :style="{ color: value.limitColor }">
										{{ item.at_least > 0 ? '满' + Number(item.at_least) + '元可用' : '无门槛优惠券' }}
									</view>
								</view>
								
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 0" >{{ value.btnStyle.text || '领取' }}</view>
								<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 1" >去使用</view>
								<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 2">已抢光</view>
								<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 3">已失效</view>
								<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 4">已使用</view>
							</view>
						</swiper-item>
					</swiper>
				</template>

				<template v-if="value.style == '3'">
					<swiper class="coupon-style-three" circular>
						<swiper-item v-for="(numItem, numIndex) in Math.ceil(computedCouponList.length / 3)" :key="numIndex" class="coupon-item-box">
							<view class="coupon-item" v-for="(item, index) in computedCouponList" :key="index"
								v-if="index >= [numIndex * 3] && index < [(numIndex + 1) * 3]" :style="{
								color: value.moneyColor,
								backgroundImage: 'url(' + $util.img('public/uniapp/coupon/coupon_shu.png') + ')',
								marginRight: couponItemHeight + 'px',
								marginLeft: couponItemHeight + 'px'
							}" @click="couponAction(item, index)">
								<view class="coupon-num" :style="{ color: value.moneyColor }" v-if="!parseInt(item.discount)">
									<text class="font-size-tag coupon-sign">￥</text>
									<text class="coupon-size">{{ item.money | moneyConduct }}</text>
								</view>
								<view class="coupon-num" :style="{ color: value.moneyColor }" v-else>
									<text class="coupon-size">{{ item.discount | moneyConduct }}</text>
									<text class="font-size-tag coupon-sign">折</text>
								</view>
								<view class="coupon-type font-size-tag">
									<text :style="{ color: value.limitColor }">{{ item.at_least > 0 ? '满' + Number(item.at_least) + '元可用' : '无门槛优惠券' }}</text>
									<view class="item-text">{{item.goods_type_name}}</view>
								</view>
								
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 0" >{{ value.btnStyle.text || '领取' }}</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 1" >去使用</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 2">已抢光</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 3">已失效</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 4">已使用</view>
							</view>
						</swiper-item>
					</swiper>
				</template>

				<template v-if="value.style == '4'">
					<swiper class="coupon-style-four" circular>
						<swiper-item v-for="(numItem, numIndex) in Math.ceil(computedCouponList.length / 3)" :key="numIndex" class="coupon-item-box">
							<view class="coupon-item" v-for="(item, index) in computedCouponList" :key="index"
								v-if="index >= [numIndex * 3] && index < [(numIndex + 1) * 3]" :style="{
								color: value.moneyColor,
								backgroundImage: 'url(' + $util.img('public/uniapp/coupon/style4_bg.png') + ')',
								marginRight: couponItemHeight + 'px',
								marginLeft: couponItemHeight + 'px'
							}" @click="couponAction(item, index)">
								<view class="coupon-info">
									<view class="coupon-num" :style="{ color: value.moneyColor }" v-if="!parseInt(item.discount)">
										<text class="font-size-tag coupon-sign">￥</text>
										<text class="coupon-size">{{ item.money | moneyConduct }}</text>
									</view>
									<view class="coupon-num" :style="{ color: value.moneyColor }" v-else>
										<text class="coupon-size">{{ item.discount | moneyConduct }}</text>
										<text class="font-size-tag coupon-sign">折</text>
									</view>
									<view class="coupon-type font-size-tag" :style="{ color: value.limitColor }">
										{{ item.at_least > 0 ? '满' + Number(item.at_least) + '元可用' : '无门槛优惠券' }}
									</view>
								</view>
								
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 0" >{{ value.btnStyle.text || '立即领取' }}</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 1" >去使用</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 2">已抢光</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 3">已失效</view>
								<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 4">已使用</view>
							</view>
						</swiper-item>
					</swiper>
				</template>

				<template v-if="value.style == '5'">
					<view class="coupon-style-five">
						<view class="coupon-all">
							<view class="coupon-box">
								<view class="coupon-list" v-for="(item, index) in computedCouponList" :key="index" @click="couponAction(item, index)">
									<image :src="$util.img('public/uniapp/coupon/style5_bg.png')"></image>
									<view class="coupon">
										<view class="coupon-info">
											<view class="coupon-num" v-if="item.discount == '0.00'" :style="{ color: value.moneyColor }">
												<text class="coupon-size">{{ item.money | moneyConduct }}</text>
												<text class="font-size-tag coupon-sign">元</text>
											</view>
											<view class="coupon-num" v-else :style="{ color: value.moneyColor }">
												<text class="coupon-size">{{ item.discount | moneyConduct }}</text>
												<text class="font-size-tag coupon-sign">折</text>
											</view>
										</view>
										<view class="coupon-line"></view>
										<view class="coupon-content">
											<view class="coupon-type">
												<view class="coupon-name" :style="{ color: value.nameColor }">{{ item.coupon_name }}</view>
												<text class="coupon-least" :style="{ color: value.limitColor }" v-if="item.at_least > 0">满{{ Number(item.at_least) }}元可用</text>
												<text class="coupon-least" :style="{ color: value.limitColor }" v-else>无门槛优惠券</text>
											</view>
											
											<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 0" >{{ value.btnStyle.text || '立即领取' }}</view>
											<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 1" >去使用</view>
											<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 2">已抢光</view>
											<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 3">已失效</view>
											<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 4">已使用</view>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</template>

				<template v-if="value.style == '6'">
					<swiper class="coupon-style-six" circular>
						<swiper-item class="style-six-wrap" v-for="(numItem, numIndex) in Math.ceil(computedCouponList.length / 3)" :key="numIndex">
							<view class="coupon" v-for="(item, index) in computedCouponList" :key="index"
								v-if="index >= [numIndex * 3] && index < [(numIndex + 1) * 3]" :style="{
								color: value.moneyColor,
								backgroundImage: 'url(' + $util.img('public/uniapp/coupon/style6-bg-1.png') + ')',
								marginRight: couponItemHeight + 'px',
								marginLeft: couponItemHeight + 'px'
							}" @click="couponAction(item, index)">
								<view class="coupon-content">
									<view class="price-wrap">
										<text class="price" :style="{ color: value.moneyColor }">{{ (item.discount == '0.00' ? item.money : item.discount) | moneyConduct }}</text>
										<text class="unit">{{ item.discount == '0.00' ? '元' : '折' }}</text>
									</view>
									<text class="text">优惠券</text>
								</view>
								<!-- <text class="btn" v-if="item.useState == 0" :style="{
									color: value.btnStyle.textColor,
									backgroundColor: value.btnStyle.bgColor,
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">{{ value.btnStyle.text }}</text>
								</text>
								<text class="btn" v-if="parseInt(item.useState)" :style="{
									color: value.btnStyle.textColor,
									backgroundColor: value.btnStyle.bgColor,
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">去使用</text>
								</text> -->
								
								<text class="btn" v-if="item.useState == 0" :style="{
									color: value.btnStyle.textColor,
									backgroundColor: value.btnStyle.bgColor,
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">{{ value.btnStyle.text }}</text>
								</text>
								<text class="btn to-use" v-if="item.useState == 1" :style="{
									color: value.btnStyle.textColor,
									backgroundColor: value.btnStyle.bgColor,
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">去使用</text>
								</text>
								<text class="btn disabled" v-if="item.useState == 2" :style="{
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">已抢光</text>
								</text>
								<text class="btn disabled" v-if="item.useState == 3" :style="{
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">已失效</text>
								</text>
								<text class="btn disabled" v-if="item.useState == 4" :style="{
									borderTopLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx',
									borderBottomLeftRadius: value.btnStyle.aroundRadius * 2 + 'rpx'
								}">
									<text class="btn-content">已使用</text>
								</text>
								
								<text class="limit" :style="{ color: value.limitColor }" v-if="parseFloat(item.at_least) > 0">满{{ item.at_least | moneyConduct }}元使用</text>
								<text class="limit" :style="{ color: value.limitColor }" v-else>无门槛使用</text>
							</view>

							<div v-if="computedCouponList.length <= 2" @click="$util.redirectTo('/pages/goods/category')"
								class="coupon coupon-null" :style="{
								color: value.moneyColor,
								backgroundImage: 'url(' + $util.img('public/uniapp/coupon/style6-bg-2.png') + ')',
								marginRight: couponItemHeight + 'px',
								marginLeft: couponItemHeight + 'px'
							}">
								<div class="coupon-content" :style="{ color: value.moneyColor }">
									<span class="price">+</span>
									<span class="text">暂无优惠券</span>
								</div>
								<span class="limit" :style="{ color: value.limitColor }">去逛逛</span>
							</div>
						</swiper-item>
					</swiper>
				</template>

				<template v-if="value.style == '7'">
					<scroll-view class="coupon-style-seven" scroll-x="true">
						<view class="wrap">
							<view class="coupon-list" v-for="(item, index) in computedCouponList" :key="index" @click="couponAction(item, index)">
								<image :src="$util.img('public/uniapp/coupon/style7_bg.png')"></image>
								<view class="coupon">
									<view class="coupon-info">
										<view class="coupon-num" v-if="item.discount == '0.00'" :style="{ color: value.moneyColor }">
											<text class="coupon-size">{{ item.money | moneyConduct }}</text>
											<text class="font-size-tag coupon-sign">元</text>
										</view>
										<view class="coupon-num" v-else :style="{ color: value.moneyColor }">
											<text class="coupon-size">{{ item.discount | moneyConduct }}</text>
											<text class="font-size-tag coupon-sign">折</text>
										</view>
									</view>
									<view class="coupon-type">
										<text class="coupon-name" :style="{ color: value.limitColor }" v-if="item.at_least > 0">满{{ Number(item.at_least) }}元可用</text>
										<text class="coupon-name" :style="{ color: value.limitColor }" v-else>无门槛优惠券</text>
										<view class="coupon-least" v-if="item.validity_type == 0" :style="{ color: value.limitColor }">有效期至{{ $util.timeStampTurnTime(item.end_time, 'Y-m-d') }}</view>
										<view class="coupon-least" v-else-if="item.validity_type == 1" :style="{ color: value.limitColor }">领取后{{ item.fixed_term }}天有效</view>
										<view class="coupon-least" v-else :style="{ color: value.limitColor }">领取后长期有效</view>
									</view>
									<view class="coupon-line"></view>
									
									<view class="coupon-get" :style="couponBtnStyle" v-if="item.useState == 0" >{{ value.btnStyle.text || '立即领取' }}</view>
									<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 1" >去使用</view>
									<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 2">已抢光</view>
									<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 3">已失效</view>
									<view class="coupon-get three-text" :style="couponBtnStyle" v-if="item.useState == 4">已使用</view>
								</view>
							</view>
						</view>
					</scroll-view>
				</template>

				<ns-login ref="login"></ns-login>

			</view>

		</x-skeleton>
	</view>
</template>

<script>
	// 优惠券
	export default {
		name: 'diy-coupon',
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			}
		},
		data() {
			return {
				couponList: [],
				isHave: true,
				couponBtnSwitch: false,
				isSuccess: false,
				loading: true,
				skeletonConfig: {
					gridRows: 2,
					gridRowsGap: '20rpx',
					headHeight: '160rpx'
				}
			};
		},
		created() {
			this.getCanReceiveCouponQuery();
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.getCanReceiveCouponQuery();
			}
		},
		computed: {
			computedCouponList() {
				var list = [];
				this.couponList.filter(function(item) {
					if (item.count != item.lead_count) list.push(item);
				});
				return list;
			},
			couponItemHeight() {
				var width = '';
				const screenWidth = uni.getSystemInfoSync().windowWidth;
				if (this.value.style == '1') width = [screenWidth - this.rpxUpPx(210) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
				else if (this.value.style == '2') width = [screenWidth - this.rpxUpPx(210) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
				else if (this.value.style == '3') width = [screenWidth - this.rpxUpPx(24) * 2 - this.rpxUpPx(194) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
				else if (this.value.style == '4') width = [screenWidth - this.rpxUpPx(206) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
				else if (this.value.style == '6') width = [screenWidth - this.rpxUpPx(208) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;

				return width;
			},
			couponBtnStyle() {
				var css = '';
				css += 'color:' + this.value.btnStyle.textColor + ';';
				css += 'background-color:' + this.value.btnStyle.bgColor + ';';
				css += 'border-radius:' + this.value.btnStyle.aroundRadius * 2 + 'rpx;';
				return css;
			}
		},
		methods: {
			rpxUpPx(res) {
				const screenWidth = uni.getSystemInfoSync().windowWidth;
				var data = (screenWidth * parseInt(res)) / 750;
				return Math.floor(data);
			},
			getCanReceiveCouponQuery() {
				var data = {
					num: this.value.count,
					can_receive: 1
				};
				if (this.value.sources == 'diy') {
					data.coupon_type_id_arr = this.value.couponIds.toString();
				}
				this.$api.sendRequest({
					url: '/coupon/api/coupon/typelists',
					data: data,
					success: res => {
						let data = res.data;
						if (data != null) {
							this.couponList = data;
							this.couponList.forEach(v => {
								// if (v.count == v.lead_count) v.useState = 2;
								// else if (v.max_fetch != 0 && v.member_coupon_num && v.member_coupon_num >= v.max_fetch) v.useState = 1;
								// else v.useState = 0;
								// if(v.received_type && v.received_type == 'expire'){
								// 	v.useState = 2;
								// }
								
								if (v.count == v.lead_count) v.useState = 2;
								else if (v.max_fetch == 0 || (v.max_fetch != 0 && !v.member_coupon_num) || (v.max_fetch != 0 && v.member_coupon_num && v.max_fetch > v.member_coupon_num)) v.useState = 0;
								else if (v.wait_coupon_num) v.useState = 1
								else if (v.lose_coupon_num) v.useState = 3;
								else if (v.use_coupon_num) v.useState = 4;
							});
						}
						this.loading = false;
					}
				});
			},
			couponAction(item, index) {
				if (item.useState == 0) {
					this.receiveCoupon(item, index);
				} else {
					this.couponTap(item, index);
				}

				
				// if(item.received_type == 'out'){
				// 	this.$util.showToast({
				// 		title: '该优惠券已抢光'
				// 	});
				// }else if(item.received_type == 'expire'){
				// 	this.$util.showToast({
				// 		title: '该优惠券已过期'
				// 	});
				// }else if(item.received_type == 'limit'){
				// 	this.$util.showToast({
				// 		title: '该优惠券领取已达上限'
				// 	});
				// }
			},
			couponTap(item, index) {
				if(item.useState == 2){
					this.$util.showToast({
						title: '该优惠券已抢光'
					});
					return;
				}
				if (item.useState == 0) this.receiveCoupon(item, index);
				else if (item.useState == 3 || item.useState == 4) this.$util.redirectTo('/pages_tool/member/coupon',{state: item.useState == 4 ? 2 : item.useState})
				else this.toGoodsList(item);
			},
			// 领取优惠券
			receiveCoupon(item, index) {
				let that = this;
				if (this.couponBtnSwitch) return;
				this.couponBtnSwitch = true;
				if (this.storeToken) {
					this.$api.sendRequest({
						url: '/coupon/api/coupon/receive',
						data: {
							coupon_type_id: item.coupon_type_id,
							get_type: 2 //获取方式:1订单2.直接领取3.活动领取
						},
						success: res => {
							var data = res.data;
							let msg = res.message;
							if (res.code == 0) {
								msg = '领取成功';
								if (res.data.is_exist == 1) {
									for (let i = 0; i < that.couponList.length; i++) {
										if (that.couponList[i].coupon_type_id == item.coupon_type_id) {
											that.$set(that.couponList[i], 'useState', 1);
										}
									}
								} else {
									for (let i = 0; i < that.couponList.length; i++) {
										if (that.couponList[i].coupon_type_id == item.coupon_type_id) {
											that.$set(that.couponList[i], 'useState', 2);
										}
									}
								}
							}
							this.$util.showToast({
								title: msg
							});

							this.$forceUpdate();
							this.couponBtnSwitch = false;
						},
						fail: res => {
							this.couponBtnSwitch = false;
						}
					});
				} else {
					this.$refs.login.open('/pages/index/index');
					this.couponBtnSwitch = false;
				}
			},
			//去使用
			toGoodsList(item) {
				if (item.goods_type != 1) {
					this.$util.redirectTo('/pages/goods/list', {
						coupon: item.coupon_type_id
					});
				} else {
					this.$util.redirectTo('/pages/goods/list', {});
				}
			}
		},
		filters: {
			moneyConduct(value) {
				var arr = value.split('.');
				var str = parseInt(arr[1].split('').reverse().join('')) + '';
				str = str.split('').reverse().join('');
				if (str == 0) return arr[0];
				else return arr[0] + '.' + str;
			}
		}
	};
</script>

<style lang="scss">
	.coupon-wrap {
		background-size: cover;
		background-repeat: no-repeat;
	}

	// 风格一
	/deep/.coupon-style-one {
		height: 110rpx;

		.coupon-item-box {
			display: flex;
			box-sizing: border-box;

			.coupon-item {
				display: flex;
				align-items: center;
				justify-content: space-between;
				width: 210rpx;
				height: 110rpx;
				background-repeat: no-repeat;
				background-size: contain;

				.coupon-get {
					position: relative;
					right: 14rpx;
					font-size: 24rpx;
					width: 26rpx;
					line-height: 1.1;
				}

				.coupon-info {
					width: 156rpx;
					display: flex;
					flex-direction: column;
					align-items: center;

					.coupon-type {
						margin-top: 14rpx;
						line-height: 1;
					}

					.coupon-num {
						margin-top: -6rpx;
						font-size: 40rpx;
						line-height: 1;
					}
				}
			}
		}
	}

	// 风格二
	/deep/.coupon-style-two {
		height: 96rpx;

		.coupon-item-box {
			display: flex;
			box-sizing: border-box;

			.coupon-item {
				display: flex;
				align-items: center;
				justify-content: space-between;
				width: 210rpx;
				height: 96rpx;
				background-repeat: no-repeat;
				background-size: contain;

				.coupon-get {
					position: relative;
					top: 0;
					right: 12rpx;
					font-size: 24rpx;
					letter-spacing: 16rpx;
					width: 26rpx;
					
					&.use {
						top: 0;
						right: 4rpx;
						letter-spacing: 4rpx;
					}
					
					&.three-text {
						line-height: 1.2;
					}
				}

				.coupon-info {
					width: 156rpx;
					display: flex;
					flex-direction: column;
					align-items: center;
					margin-top: -8rpx;

					.coupon-type {
						margin-top: 10rpx;
						line-height: 1;
					}

					.coupon-num {
						font-size: 40rpx;
						line-height: 1;
					}
				}
			}
		}
	}

	// 风格三
	.coupon-wrap.coupon-box-3 {
		background-size: 100% 100%;
	}

	/deep/.coupon-style-three {
		height: 284rpx;
		padding: 20rpx;
		box-sizing: border-box;

		.coupon-item-box {
			height: 250rpx !important;
			display: flex;

			.coupon-item {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				width: 206rpx;
				height: 250rpx;
				background-repeat: no-repeat;
				background-size: contain;

				.coupon-size {
					font-size: 40rpx;
				}

				.coupon-type {
					margin-top: 20rpx;
					margin-bottom: 6rpx;
					line-height: 1;
					text-align: center;
				}

				.coupon-num {
					margin-top: 8rpx;
					line-height: 1;
				}

				.coupon-get {
					width: 120rpx;
					height: 46rpx;
					line-height: 46rpx;
					text-align: center;
					font-size: 24rpx;
				}
			}
		}
	}

	// 风格四
	/deep/.coupon-style-four {
		height: 108rpx;

		.coupon-item-box {
			display: flex;
			box-sizing: border-box;

			.coupon-item {
				display: flex;
				align-items: center;
				justify-content: space-between;
				width: 206rpx;
				height: 108rpx;
				background-repeat: no-repeat;
				background-size: contain;

				.coupon-get {
					position: relative;
					top: 0;
					right: 12rpx;
					font-size: 24rpx;
					width: 26rpx;
					line-height: 1.1;
				}

				.coupon-info {
					width: 156rpx;
					display: flex;
					flex-direction: column;
					align-items: center;

					.coupon-type {
						margin-top: 10rpx;
						line-height: 1;
					}

					.coupon-num {
						font-size: 40rpx;
						line-height: 1;
					}
				}
			}
		}
	}

	/* 样式一 */
	.coupon-all {
		width: 100%;
		flex-direction: row;
		white-space: nowrap;
		box-sizing: border-box;
		line-height: 1;
	}

	.coupon-list {
		position: relative;
		line-height: 1;
	}

	.coupon {
		width: 254rpx;
		height: 114rpx;
		border-radius: $border-radius;
		overflow: hidden;
		display: flex;
		justify-content: space-between;
		position: absolute;
		left: 0;
		top: 0;

		.coupon-info {
			width: 71%;
			height: 100%;
			padding: 10rpx 15rpx;
			box-sizing: border-box;
			display: flex;
			flex-direction: column;
			justify-content: center;

			.coupon-type {
				color: #ffffff;
				font-size: $font-size-activity-tag;
				text-align: center;
				overflow: hidden;
				white-space: nowrap;
			}

			.coupon-num {
				display: flex;
				align-items: flex-end;
				justify-content: center;
				margin-bottom: 10rpx;

				.coupon-sign {
					margin-bottom: 4rpx;
				}

				.coupon-size {
					font-size: 40rpx;
				}

				text {
					line-height: 1;
				}
			}
		}

		.coupon-get {
			width: 26%;
			height: 100%;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;

			view {
				line-height: 1.2;
				font-size: $font-size-tag;
			}
		}
	}

	//风格五
	.coupon-style-five {
		.coupon-all {
			.coupon-box {
				width: 100%;
				display: inline-block;
				position: relative;

				.coupon-list {
					height: 160rpx;
					position: relative;
					padding: 10rpx 0;

					image {
						width: 100%;
						height: 100%;
					}

					.coupon {
						width: 100%;
						height: 100%;
						display: flex;
						align-items: center;
						justify-content: flex-start;

						.coupon-info {
							width: 25%;
							min-width: 25%;
							max-width: 25%;

							.coupon-num {
								margin: 0;
								overflow: hidden;
								white-space: nowrap;
								text-overflow: ellipsis;
								width: 100%;

								.coupon-size {
									font-weight: bold;
								}

								.coupon-sign {}
							}
						}

						.coupon-line {
							border-left: 1rpx dashed #999;
							height: 65%;
						}

						.coupon-content {
							display: flex;
							width: 100%;
							justify-content: space-between;
							padding: 0 20rpx;
							align-items: center;

							.coupon-type {
								.coupon-name {
									font-size: 32rpx;
									font-weight: bold;
									width: 300rpx;
									overflow: hidden;
									white-space: nowrap;
									text-overflow: ellipsis;
								}

								.coupon-least {
									font-size: 28rpx;
									color: #999;
								}
							}

							.coupon-get {
								color: #fff;
								background-color: #303133;
								border-radius: 6rpx;
								padding: 4rpx 8rpx;
								margin-right: 20rpx;
								font-size: $font-size-tag;
							}
						}
					}
				}
			}
		}
	}

	//风格六
	.coupon-style-six {
		height: 270rpx;
		box-sizing: border-box;

		.style-six-wrap {
			display: flex;
		}

		.coupon {
			margin-right: 20rpx;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			width: 208rpx;
			height: 270rpx;
			background-repeat: no-repeat;
			background-size: contain;
			box-sizing: border-box;
			position: relative;
			flex-shrink: 0;

			&::after {
				content: '';
				position: absolute;
				left: 26rpx;
				right: 26rpx;
				bottom: 70rpx;
				border-bottom: 2rpx dashed #999;
			}

			.btn {
				position: absolute;
				height: 36rpx;
				min-width: 62rpx;
				font-size: 24rpx;
				top: 20rpx;
				right: 0;
				display: flex;
				justify-content: center;
				align-items: center;
				line-height: 1;
				padding: 0 4rpx 0 6rpx;

				.btn-content {
					display: inline-block;
					line-height: 1;
					transform: scale(0.8);
				}
				
				&.disabled {
					background-color: #eee;
					color: #909399;
				}
				
			}

			.coupon-content {
				display: flex;
				flex-direction: column;
				justify-content: space-between;
				align-items: center;
				font-size: 24rpx;
				height: 200rpx;

				.price-wrap {
					margin-top: 60rpx;
				}

				.price {
					font-size: 60rpx;
					font-weight: bold;
					line-height: 1;
				}

				.text {
					position: relative;
					color: #401d00;
					margin-bottom: 16rpx;

					&::after {
						content: '';
						position: absolute;
						top: 50%;
						transform: translateY(-50%);
						width: 20rpx;
						right: -26rpx;
						border-bottom: 1px solid #401d00;
					}

					&::before {
						content: '';
						position: absolute;
						top: 50%;
						transform: translateY(-50%);
						width: 20rpx;
						left: -26rpx;
						border-bottom: 2rpx solid #401d00;
					}
				}

				.unit {
					line-height: 1;
					margin-top: 13px;
					color: #222;
					display: inline-block;
					transform: scale(0.8);
				}
			}

			.limit {
				display: flex;
				align-items: center;
				justify-content: center;
				height: 70rpx;
				font-size: 24rpx;
				font-weight: bold;
			}

			&.coupon-null {
				.limit {
					color: #333;
				}

				.unit {
					color: #6f5640;
				}

				.text {
					color: #6f5640;
				}

				.coupon-content .text::before,
				.coupon-content .text::after {
					border-color: #6f5640;
				}

				.coupon-content .price {
					color: #999;
					font-weight: inherit;
					border: 4rpx solid #999;
					border-radius: 50%;
					height: 42rpx;
					width: 42rpx;
					line-height: 38rpx;
					text-align: center;
					font-size: 50rpx;
					margin-top: 60rpx;
				}
			}
		}
	}

	//风格七
	/deep/ .coupon-style-seven {
		.wrap {
			display: flex;
		}

		.coupon-list {
			margin-right: 16rpx;
			flex-shrink: 0;
			width: 608rpx;
			height: 160rpx;
			position: relative;
			padding: 10rpx 0;

			image {
				width: 100%;
				height: 100%;
			}

			.coupon {
				width: 100%;
				height: 100%;
				display: flex;
				align-items: center;
				justify-content: space-between;

				.coupon-info {
					min-width: 25%;
					max-width: 30%;
					padding: 0;

					.coupon-num {
						margin: 0;
						overflow: hidden;
						white-space: nowrap;
						text-overflow: ellipsis;
						width: 100%;

						.coupon-size {
							font-size: 60rpx;
							font-weight: bold;
							margin-right: 4rpx;
						}
					}
				}

				.coupon-line {
					border-left: 1rpx dashed #fd463e;
					height: 65%;
				}

				.coupon-type {
					.coupon-name {
						display: block;
						font-size: 32rpx;
						font-weight: bold;
						width: 280rpx;
						overflow: hidden;
						white-space: nowrap;
						text-overflow: ellipsis;
					}

					.coupon-least {
						font-size: $font-size-tag;
						color: #999;
					}
				}

				.coupon-get {
					padding: 4rpx 40rpx;
					width: 130rpx;
					font-size: $font-size-tag;
					box-sizing: border-box;
					flex-shrink: 0;
					
					&.three-text {
						writing-mode: vertical-rl;
					}
					
				}
			}
		}
	}

	/deep/.uni-scroll-view ::-webkit-scrollbar {
		/* 隐藏滚动条，但依旧具备可以滚动的功能 */
		display: none;
		width: 0;
		height: 0;
		color: transparent;
		background: transparent;
	}

	/deep/::-webkit-scrollbar {
		display: none;
		width: 0;
		height: 0;
		color: transparent;
		background: transparent;
	}
</style>
<style scoped>
	.coupon-all>>>.uni-scroll-view::-webkit-scrollbar {
		display: none;
	}
</style>