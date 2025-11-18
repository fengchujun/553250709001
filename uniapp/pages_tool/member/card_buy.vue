<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="member-level">
		<block v-if="levelList.length">
			<view class="level-top">
				<image :src="$util.img('public/uniapp/level/card-top-bg.png')"></image>
			</view>
			<view class="banner-container">
				<view class="memberInfo">
					<image :src="$util.img(memberInfo.headimg)" v-if="memberInfo.headimg" @error="headimgError" mode="aspectFill"/>
					<image :src="$util.getDefaultImage().head" v-else mode="aspectFill"></image>
					<view class="member-desc">
						<view class="font-size-toolbar">{{ memberInfo.nickname }}</view>
						<view class="font-size-tag expire-time" v-if="memberInfo.level_expire_time > 0">有效期至：{{ $util.timeStampTurnTime(memberInfo.level_expire_time) }}</view>
					</view>
				</view>
				<swiper :style="{ width: '100vw', height: '390rpx' }" class="margin-bottom"
					:indicator-dots="swiperConfig.indicatorDots" :indicator-color="swiperConfig.indicatorColor"
					:indicator-active-color="swiperConfig.indicatorActiveColor" :autoplay="false"
					:interval="swiperConfig.interval" :duration="swiperConfig.duration"
					:circular="swiperConfig.circular" :previous-margin="swiperConfig.previousMargin"
					:next-margin="swiperConfig.nextMargin" @change="swiperChange" @animationfinish="animationfinish"
					:current="curIndex">
					<swiper-item :class="levelList.length == 1 ? 'image-container-box' : ''" v-for="(item, i) in levelList" :key="i">
						<view class="image-container" :class="[
								curIndex === 0
									? i === 1
										? 'item-right'
										: i === listLen - 1
										? 'item-left'
										: 'item-center'
									: curIndex === listLen - 1
										? i === curIndex - 1
										? 'item-left'
										:  i === curIndex + 1
										? 'item-right'
										: i === 0
										? 'item-right'
										: i === listLen - 2
										? 'item-left'
										: 'item-center'
									: i === curIndex - 1
										? 'item-left'
										: i === curIndex + 1
										? 'item-right'
									: 'item-center'
							]">
							<view class="slide-image" style="background-size: 100% 100%;background-repeat:no-repeat"
								:style="{
									transform: curIndex === i ? 'scale(' + scaleX + ',' + scaleY + ')' : 'scale(1,1)',
									transitionDuration: '.3s',
									transitionTimingFunction: 'ease'
								}">
								<view class="bg-border"></view>

								<image v-if="item && item['level_picture']" :src="$util.img(item['level_picture'])"/>
								<image v-else :style="{backgroundColor:item['bg_color']}"/>

								<view class="info">
									<view class="level-detail" :style="{color:item['level_text_color']}">{{ item.level_name }}</view>
									<view class="growr-name" :style="{color:item['level_text_color']}">{{ item.level_name }}可享受消费折扣和</view>
									<view class="growr-value" :style="{color:item['level_text_color']}">会员大礼包等权益</view>
									<view class="growth-rules font-size-tag" @click="openExplainPopup" v-if="item.remark != ''">
										<text class="iconfont icon-wenhao font-size-tag"></text>
									</view>
								</view>
							</view>
						</view>
					</swiper-item>
				</swiper>

				<view class="card-content">
					<view class="card-content-head">
						<view class="line-box">
							<view class="line right"></view>
						</view>
						<view class="card-content-title">卡种选择</view>
						<view class="line-box">
							<view class="line"></view>
						</view>
						<view class="clear"></view>
					</view>
					<view class="card-time-list">
						<view class="card-item-box" :class="{ small: currCard.charge_rule_arr.length == 4 }" v-for="(item, index) in currCard.charge_rule_arr" :key="index">
							<view class="card-time-item" :class="{ active: choiceIndex == index }" @click="choice(index)">
								<image :src="$util.img('public/uniapp/level/card-icon.png')" mode="widthFix"></image>
								<view class="time-name">{{ cardType[item.key].name }}</view>
								<view class="time-price">
									{{ $lang('common.currencySymbol') }}
									<text class="price">{{ item.value }}</text>
									/{{ cardType[item.key].unit }}
								</view>
							</view>
						</view>
					</view>
				</view>

				<view class="card-content" v-if="currCard.is_free_shipping || currCard.consume_discount < 100 || currCard.point_feedback > 0">
					<view class="card-content-head">
						<view class="line-box">
							<view class="line right"></view>
						</view>
						<view class="card-content-title">会员权益</view>
						<view class="line-box">
							<view class="line"></view>
						</view>
						<view class="clear"></view>
					</view>
					<view class="card-privilege-list">
						<view class="card-privilege-item" v-if="currCard.is_free_shipping">
							<view class="card-privilege-icon">
								<text class="iconfont icon-tedianquanchangbaoyou"></text>
							</view>
							<view class="card-privilege-name">全场包邮</view>
							<view class="card-privilege-text">享受商品包邮服务</view>
						</view>
						<view class="card-privilege-item" v-if="currCard.consume_discount < 100">
							<view class="card-privilege-icon"><text class="iconfont icon-zhekou"></text></view>
							<view class="card-privilege-name">消费折扣</view>
							<view class="card-privilege-text">部分商品下单可享{{ currCard.consume_discount / 10 }}折优惠</view>
						</view>
						<view class="card-privilege-item" v-if="currCard.point_feedback > 0">
							<view class="card-privilege-icon"><text class="iconfont icon-jifen2 f32"></text></view>
							<view class="card-privilege-name">积分回馈</view>
							<view class="card-privilege-text">下单享{{ parseFloat(currCard.point_feedback) }}倍积分回馈</view>
						</view>
					</view>

					<view v-if="currCard.send_coupon != '' || currCard.send_point > 0 || currCard.send_balance > 0">
						<view class="card-content-head">
							<view class="line-box">
								<view class="line right"></view>
							</view>
							<view class="card-content-title">开卡礼包</view>
							<view class="line-box">
								<view class="line"></view>
							</view>
							<view class="clear"></view>
						</view>
						<view class="card-privilege-list">
							<view class="card-privilege-item" v-if="currCard.send_point > 0">
								<view class="card-privilege-icon"><text class="iconfont icon-jifen3"></text></view>
								<view class="card-privilege-name">积分礼包</view>
								<view class="card-privilege-text">赠送{{ currCard.send_point }}积分</view>
							</view>
							<view class="card-privilege-item" v-if="currCard.send_balance > 0">
								<view class="card-privilege-icon"><text class="iconfont icon-hongbao"></text></view>
								<view class="card-privilege-name">红包礼包</view>
								<view class="card-privilege-text">赠送{{ parseFloat(currCard.send_balance) }}元红包</view>
							</view>
							<view class="card-privilege-item" v-if="currCard.send_coupon != ''">
								<view class="card-privilege-icon"><text class="iconfont icon-youhuiquan1"></text></view>
								<view class="card-privilege-name">优惠券礼包</view>
								<view class="card-privilege-text">赠送{{ currCard.send_coupon.split(',').length }}张优惠券
								</view>
							</view>
						</view>
					</view>
				</view>

				<block v-if="currCard.charge_rule_arr.length">
					<view class="action-wrap" :class="{ 'bottom-safe-area': isIphoneX, 'have-agreement': agreement }">
					</view>
					<view class="action" :class="{ 'bottom-safe-area': isIphoneX, 'have-agreement': agreement }">
						<view class="action-btn" @click="create">
							<block v-if="currCard.level_id == levelId"><text class="bold title">立即续费</text></block>
							<block v-else>
								<text class="bold title" v-if="currCard.charge_type == 1">充值开通</text>
								<text class="bold title" v-else>立即开通</text>
							</block>
							<text class="font-size-tag">{{ $lang('common.currencySymbol') }}</text>
							<text class="bold">{{ currCard.charge_rule_arr[choiceIndex].value }}</text>
							<text>/{{ cardType[currCard.charge_rule_arr[choiceIndex].key].unit }}</text>
						</view>
						<view class="agreement" v-if="agreement">
							购买既视为同意
							<text @click="$util.redirectTo('/pages_tool/member/card_agreement')">《{{ agreement.title }}》</text>
						</view>
					</view>
				</block>
			</view>

			<!-- 弹出规则 -->
			<view @touchmove.prevent.stop>
				<uni-popup ref="explainPopup" type="bottom">
					<view class="tips-layer">
						<view class="head" @click="closeExplainPopup()">
							<view class="title">会员卡说明</view>
							<text class="iconfont icon-close"></text>
						</view>
						<view class="body">
							<view class="detail margin-bottom">
								<block v-if="currCard.remark != ''">
									<view class="tip">会员卡说明</view>
									<view class="font-size-base">{{ currCard.remark }}</view>
								</block>
							</view>
						</view>
					</view>
				</uni-popup>
			</view>

			<ns-payment ref="choosePaymentPopup" :payMoney="currCard.charge_rule_arr[choiceIndex].value" @confirm="toPay" v-if="currCard.charge_rule_arr.length"></ns-payment>
		</block>
		<block v-else><ns-empty text="暂无可开会员卡" :isIndex="false"></ns-empty></block>

		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
		<!-- 选择支付方式弹窗 -->
	</view>
</template>

<script>
	import scroll from '@/common/js/scroll-view.js';
	import uniPopup from '@/components/uni-popup/uni-popup.vue';

	export default {
		components: {
			uniPopup
		},
		mixins: [scroll],
		data() {
			return {
				isSub: false, // 是否已提交
				isIphoneX: false,
				couponPopList: [],
				curIndex: 0,
				isDescAnimating: false,
				scaleX: (634 / 540).toFixed(4),
				scaleY: (378 / 330).toFixed(4),
				swiperConfig: {
					indicatorDots: false,
					indicatorColor: 'rgba(255, 255, 255, .4)',
					indicatorActiveColor: 'rgba(255, 255, 255, 1)',
					interval: 3000,
					duration: 300,
					circular: false,
					previousMargin: '58rpx',
					nextMargin: '58rpx'
				},
				levelList: [],
				levelId: 0,
				cardType: {
					week: {
						name: '周卡',
						unit: '周'
					},
					month: {
						name: '月卡',
						unit: '月'
					},
					quarter: {
						name: '季卡',
						unit: '季'
					},
					year: {
						name: '年卡',
						unit: '年'
					}
				},
				choiceIndex: 0,
				outTradeNo: '',
				agreement: null
			};
		},
		computed: {
			listLen() {
				return this.levelList.length;
			},
			currCard() {
				if (this.levelList[this.curIndex]) {
					let card = this.levelList[this.curIndex];
					let charge_rule = card.charge_rule ? JSON.parse(card.charge_rule) : {};
					card.charge_rule_arr = [];
					Object.keys(charge_rule).forEach(key => {
						card.charge_rule_arr.push({
							key: key,
							value: charge_rule[key]
						});
					});
					return card;
				}
			}
		},
		onLoad() {
			//会员卡
			this.isIphoneX = this.$util.uniappIsIPhoneX();

			if (this.storeToken) {
				this.getCardList();
			} else {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_tool/member/card_buy');
				});
			}
			this.getAgreement();
		},
		onShow() {},
		watch: {
			storeToken: function(nVal, oVal) {
				if (nVal) {
					this.getCardList();
				}
			}
		},
		methods: {
			swiperChange(e) {
				this.curIndex = e.detail.current;
				this.choiceIndex = 0;
				this.isDescAnimating = true;
			},
			animationfinish(e) {
				this.isDescAnimating = false;
			},
			getCardList() {
				this.$api.sendRequest({
					url: '/supermember/api/membercard/lists',
					success: res => {
						if (res.code == 0 && res.data) {
							this.levelList = res.data;
							this.levelId = this.memberInfo.member_level;
							for (let i = 0; i < this.levelList.length; i++) {
								if (this.levelList[i].level_id == this.levelId) {
									this.curIndex = i;
									break;
								}
							}
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			choice(index) {
				this.choiceIndex = index;
			},
			/**
			 * 创建
			 */
			create() {
				if (this.memberInfo.member_level_type && this.memberInfo.member_level != this.currCard.level_id) {
					uni.showModal({
						title: '提示',
						content: '您有尚未过期的会员卡，再次购卡会覆盖掉之前的卡，是否继续？',
						success: res => {
							if (res.confirm) {
								this.$refs.choosePaymentPopup.open();
							}
						}
					});
				} else {
					this.$refs.choosePaymentPopup.open();
				}
			},
			/**
			 * 提交
			 */
			toPay() {
				if (this.isSub) return;
				this.isSub = true;

				this.$api.sendRequest({
					url: '/supermember/api/ordercreate/create',
					data: {
						level_id: this.currCard.level_id,
						period_unit: this.currCard.charge_rule_arr[this.choiceIndex].key
					},
					success: res => {
						if (res.data && res.code == 0) {
							this.outTradeNo = res.data.out_trade_no;
							uni.setStorageSync('paySource', 'membercard');
							this.$refs.choosePaymentPopup.getPayInfo(this.outTradeNo);
						} else {
							this.isSub = false;
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			headimgError() {
				this.memberInfo.headimg = this.$util.getDefaultImage().head;
			},
			/**
			 * 打开说明弹出层
			 */
			openExplainPopup() {
				this.$refs.explainPopup.open();
			},
			/**
			 * 打开说明弹出层
			 */
			closeExplainPopup() {
				this.$refs.explainPopup.close();
			},
			getAgreement() {
				this.$api.sendRequest({
					url: '/supermember/api/membercard/agreement',
					success: res => {
						if (res.code == 0 && res.data && res.data.title != '' && res.data.content != '') {
							this.agreement = res.data;
						}
					}
				});
			}
		},
		onBackPress(options) {
			if (options.from === 'navigateBack') {
				return false;
			}
			this.$util.redirectTo('/pages/member/index');
			return true;
		}
	};
</script>

<style lang="scss">
	@import './public/css/card.scss';
</style>