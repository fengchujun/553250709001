<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<view>
			<view class="head-wrap">
				<image :src="$util.img('public/uniapp/game/cards_head.png')" mode="widthFix"></image>
				<view class="rule-mark" @click="openRulePopup">规则</view>
			</view>
			<!-- 刮卡区域 -->
			<view class="prize-area">
				<view class="content-wrap">
					<view>
						<view class="guide-wrap" v-if="showGuide">
							<block v-if="gameInfo.status == 1">
								<view class="text">刮开试试手气</view>
								<view class="btn color-base-bg" :class="{ disabled: gameInfo.surplus_num == 0 }"
									@click="lottery">
									点我刮奖{{ '(' + gameInfo.points + '积分)' }}
								</view>
							</block>
							<view class="text" v-else-if="gameInfo.status == 0">活动尚未开始</view>
							<view v-else class="text">活动已经结束</view>
						</view>
						<!-- #ifdef MP -->
						<view class="canvas-shade" v-if="showGuide"></view>
						<canvas :disable-scroll="true" class="canvas" canvas-id="cardsCanvas" id="canvas"
							@touchmove="touchMove" @touchstart="targbtn" @touchend="touchend"
							v-show="!showGuide && !popState"></canvas>
						<!-- #endif -->

						<!-- #ifdef H5 -->
						<canvas :disable-scroll="true" class="canvas" canvas-id="cardsCanvas" id="canvas"
							@touchmove="touchMove" :class="{ 'add-point': add_point == 1 }"></canvas>
						<!-- #endif -->
						<view class="result-wrap" @touchmove="contentMove">
							<block v-if="result.is_winning">
								<view class="title">恭喜您中奖了</view>
								<view class="text color-base-text">{{ result.award_name }}</view>
								<button type="warn" class="warn" @click="again">再刮一次</button>
								<view class="tips">可到“我的中奖记录”中查看</view>
							</block>
							<block v-else>
								<view class="text color-base-text">{{ gameInfo.no_winning_desc }}</view>
								<button type="warn" class="warn" id="btn" @click="again">再刮一次</button>
							</block>
						</view>
					</view>
				</view>
				<image :src="$util.img('public/uniapp/game/cards_bg.png')" mode="widthFix"></image>
			</view>
			<!-- 操作 -->
			<view class="action-text">
				<view class="point">我的积分：{{ point }}</view>
				<view class="record" @click="$util.redirectTo('/pages_promotion/game/record', { id: id })">我的中奖记录</view>
			</view>
			<!-- 中奖名单 -->
			<view class="record-wrap" v-if="gameInfo.is_show_winner && gameInfo.draw_record.length">
				<view class="body-shade"></view>
				<view class="head">中奖名单</view>
				<view class="body">
					<view class="wrap" :class="{ animate: animate }">
						<view v-for="(item, index) in gameInfo.draw_record" :key="index">
							<view class="tit">{{ item.member_nick_name | cover }}</view>
							<view class="txt">
								<l-time :text="item.create_time * 1000"></l-time>
								获得 {{ item.award_name }}
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 活动规则 -->
		<uni-popup ref="rulePopup" type="center" :maskClick="false" @change="popChange">
			<view class="rule-wrap">
				<view class="content-wrap">
					<image :src="$util.img('public/uniapp/game/rule_head.png')" mode="widthFix" class="rule-head" />
					<scroll-view scroll-y="true" class="rule">
						<view>
							<view class="tit">活动时间</view>
							<view class="text">{{ $util.timeStampTurnTime(gameInfo.start_time) }} - {{
		$util.timeStampTurnTime(gameInfo.end_time) }}</view>
							<view class="tit">参与规则</view>
							<view class="text" v-if="gameInfo.join_type == 0">每个用户活动期间共有{{ gameInfo.join_frequency
								}}次抽奖机会。</view>
							<view class="text" v-else>每个用户活动期间每天都有{{ gameInfo.join_frequency }}次抽奖机会，每天0点更新。</view>
							<view class="text">每次抽奖需消耗 {{ gameInfo.points }} 积分</view>
							<view class="text" v-if="gameInfo.level_id != 0">该活动只有{{ gameInfo.level_name }}等级的会员可参与。
							</view>
							<block v-if="gameInfo.remark != ''">
								<view class="tit">活动说明</view>
								<view class="text">{{ gameInfo.remark }}</view>
							</block>
						</view>
					</scroll-view>
					<text class="iconfont icon-round-close" @click="closeRulePopup"></text>
				</view>
			</view>
		</uni-popup>

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
import LTime from '@/pages_promotion/components/l-time/l-time.vue';
export default {
	components: {
		uniPopup,
		LTime
	},
	data() {
		return {
			ctxData: null,
			ctx: null,
			ctxW: 0,
			ctxH: 0,
			canvasX: 0,
			canvasY: 0,
			scaleRatio: 1,
			showGuide: true,
			id: 0,
			gameInfo: {
				// 游戏数据
				no_winning_desc: '',
				surplus_num: 0
			},
			result: {
				// 抽奖结果
				is_winning: 0
			},
			isClick: false,
			point: 0,
			animate: false,
			scrollTimer: null,
			popState: false,
			add_point: 0,
			//分享时详情所用图片
			shareImg: ''
		};
	},
	onLoad(option) {
		setTimeout(() => {
			if (!this.addonIsExist.cards) {
				this.$util.showToast({
					title: '商家未开启刮刮乐',
					mask: true,
					duration: 2000
				});
				setTimeout(() => {
					this.$util.redirectTo('/pages/index/index');
				}, 2000);
			}
		}, 1000);

		if (option.id) this.id = option.id;

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
					if (item.indexOf('id') != -1) this.id = item.split('-')[1];
					if (item.indexOf('sku_id') != -1) this.skuId = item.split('-')[1];
					if (item.indexOf('m') != -1) uni.setStorageSync('source_member', item.split('-')[1]);
					if (item.indexOf('is_test') != -1) uni.setStorageSync('is_test', 1);
				});
			}
		}
		this.getGameInfo();
	},
	onShow() {
		//记录分享关系
		if (this.storeToken && uni.getStorageSync('source_member')) {
			this.$util.onSourceMember(uni.getStorageSync('source_member'));
		}

		if (this.storeToken) {
			this.getMemberPointInfo();
		}
	},

	/**
	 * 转发分享
	 */
	onShareAppMessage(res) {
		var title = this.gameInfo.game_name;
		var route = this.$util.getCurrentShareRoute(this.memberInfo ? this.memberInfo.member_id : 0);
		var path = route.path;
		return {
			title: title,
			path: path,
			imageUrl: '',
			success: res => { },
			fail: res => { }
		};
	},
	// 分享到微信朋友圈
	// #ifdef MP-WEIXIN
	onShareTimeline() {
		var title = this.gameInfo.game_name;
		var route = this.$util.getCurrentShareRoute(this.memberInfo ? this.memberInfo.member_id : 0);
		var query = route.query;
		return {
			title: title,
			query: query,
			imageUrl: ''
		};
	},
	// #endif
	watch: {
		storeToken: function (nVal, oVal) {
			if (nVal) {
				this.getMemberPointInfo();
				this.getGameInfo();
			}
		}
	},
	methods: {
		// addPoint(){
		// 	this.add_point = 1;
		// },
		// removePoint(){
		// 	this.add_point = 0;
		// },
		// #ifdef MP-WEIXIN
		targbtn(e) {
			// 在触摸开始时记录触摸起始点的坐标
			this.canvasX = e.touches[0].x
			this.canvasY = e.touches[0].y
		},
		// #endif
		pxToRpx(px) {
			const screenWidth = uni.getSystemInfoSync().screenWidth
			return (750 * Number.parseInt(px)) / screenWidth
		},
		again() {
			this.$util.redirectTo('/pages_promotion/game/cards', {
				id: this.id
			}, 'redirectTo');
		},
		popChange(e) {
			this.popState = e.show;
		},
		getMemberPointInfo() {
			this.$api.sendRequest({
				url: '/api/memberaccount/info',
				data: {
					account_type: 'point'
				},
				success: res => {
					if (res.data) {
						this.point = parseInt(res.data.point);
					}
				}
			});
		},
		createCtx() {
			this.ctx = uni.createCanvasContext('cardsCanvas', this);
			this.ctx.setFillStyle('#e5e5e5');
			this.ctx.fillRect(0, 0, this.ctxW, this.ctxH);
			this.ctx.globalCompositeOperation = 'destination-out';
			this.ctx.draw();
		},
		contentMove(event) {
			if (event) event.preventDefault();
			this.add_point = 0;
		},
		touchMove(event) {
			// console.log(event)
			this.add_point = 1;
			event.preventDefault();
			var canvasRect = this.ctxData;
			this.ctx.beginPath();
			this.ctx.arc(event.touches[0].x, event.touches[0].y, 10 * this.scaleRatio, 0, Math.PI * 2);
			this.ctx.globalCompositeOperation = 'destination-out';
			this.ctx.setFillStyle('#fff');
			this.ctx.fill();
			this.ctx.draw(true);


		},
		touchend(e) {
			// #ifdef MP-WEIXIN
			// 在触摸移动时判断是否为拖拽操作
			const moveX = e.changedTouches[0].x
			const moveY = e.changedTouches[0].y
			const deltaX = moveX - this.canvasX
			const deltaY = moveY - this.canvasY

			// 判断移动距离是否超过阈值，此处设为10px
			if ((Math.abs(deltaX) <= 10 && Math.abs(deltaX) >= -10) || (Math.abs(deltaY) <= 10 && Math.abs(deltaY) >= -10)) {
				// 触发拖拽操作，可以在这里进行相应处理
				if (this.add_point == 1) {
					let x = this.pxToRpx(e.changedTouches[0].x), y = this.pxToRpx(e.changedTouches[0].y)
					if (x >= 172 && x <= 172 + this.pxToRpx(88) && y >= 158 && y <= 158 + this.pxToRpx(42)) {
						this.again()
					}
				}
			}
			// #endif
		},
		// 抽奖
		lottery() {
			if (!this.storeToken) {
				this.$refs.login.open('/pages_promotion/game/cards?id=' + this.id);
				return;
			}

			if (this.gameInfo.status != 1) return;

			if (this.gameInfo.surplus_num <= 0) {
				this.$util.showToast({
					title: '您的抽奖次数已用完'
				});
				return;
			}
			if (this.gameInfo.points > 0 && this.point < this.gameInfo.points) {
				this.$util.showToast({
					title: '积分不足'
				});
				return;
			}
			if (this.isClick) return;
			this.isClick = true;
			this.$api.sendRequest({
				url: '/cards/api/cards/lottery',
				data: {
					id: this.id
				},
				success: res => {
					if (res.code >= 0) {
						this.showGuide = false;
						this.result = res.data;

						// #ifdef MP
						const query = uni.createSelectorQuery().in(this);
						query
							.select('.guide-wrap')
							.boundingClientRect(data => {
								this.ctxData = data;
								this.ctxW = data.width;
								this.ctxH = data.height;
								this.createCtx();
							})
							.exec();
						// #endif

						// 扣除次数 积分
						this.point -= this.gameInfo.points;
						this.gameInfo.surplus_num -= 1;
					} else {
						this.$util.showToast({
							title: res.message
						});
						this.isClick = false;
					}
				}
			});
		},
		getGameInfo() {
			this.$api.sendRequest({
				url: '/cards/api/cards/info',
				data: {
					id: this.id
				},
				success: res => {
					if (res.code >= 0 && res.data) {
						this.gameInfo = res.data;
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						if (this.gameInfo.is_show_winner && this.gameInfo.draw_record.length > 6) {
							this.scrollTimer = setInterval(this.scrollRecord, 2000);
						}
					} else {
						this.$util.showToast({
							title: '未获取到活动信息！'
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages/index/index');
						}, 1500);
					}
				},
				fail: res => {
					this.$util.showToast({
						title: '未获取到活动信息！'
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index');
					}, 1500);
				}
			});
		},
		openRulePopup() {
			this.$refs.rulePopup.open();
		},
		closeRulePopup() {
			this.$refs.rulePopup.close();
		},
		scrollRecord() {
			this.animate = true;
			setTimeout(() => {
				this.gameInfo.draw_record.push(this.gameInfo.draw_record[0]); // 将数组的第一个元素添加到数组的
				this.gameInfo.draw_record.shift();
				this.animate = false;
			}, 1000);
		},
		reCards() {
			if (this.showGuide) return;
			let data = {
				ctxData: null,
				ctx: null,
				ctxW: 0,
				ctxH: 0,
				scaleRatio: 1,
				showGuide: true,
				gameInfo: {
					// 游戏数据
					no_winning_desc: '',
					surplus_num: 0
				},
				result: {
					// 抽奖结果
					is_winning: 0
				},
				isClick: false,
				point: 0,
				animate: false,
				scrollTimer: null
			};
			Object.assign(this.$data, data);
			this.getGameInfo();
			if (this.storeToken) {
				this.getMemberPointInfo();
			}
			// #ifdef H5
			const query = uni.createSelectorQuery().in(this);
			query
				.select('#canvas')
				.boundingClientRect(data => {
					this.ctxData = data;
					this.ctxW = data.width;
					this.ctxH = data.height;
					this.createCtx();
				})
				.exec();
			// #endif
		}
	},
	onReady() {
		// #ifdef H5
		const query = uni.createSelectorQuery().in(this);
		query.select('#canvas').boundingClientRect(data => {
			this.ctxData = data;
			this.ctxW = data.width;
			this.ctxH = data.height;
			this.createCtx();
		}).exec();
		// #endif
	},
	filters: {
		cover(str) {
			// if (typeof str == 'string' && str.length > 0) {
			// 	return str.substr(0, 1) + '******' + str.substr(-1);
			// } else {
			// 	return '';
			// }
			return str.substr(0, 1) + '******' + str.substr(-1);
		}
	},
	onHide() {
		clearInterval(this.scrollTimer);
	}
};
</script>

<style lang="scss">
@import './public/css/cards.scss';
</style>
<style scoped>
>>>.uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
	background: none !important;
}

>>>.uni-popup__wrapper.uni-custom.center .uni-popup__wrapper-box {
	overflow: unset;
}
</style>