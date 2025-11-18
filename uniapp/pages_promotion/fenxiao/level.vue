<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="container">
		<view class="level-top">
			<image :src="$util.img('public/uniapp/level/level-top-bg.png')"></image>
		</view>

		<swiper :autoplay="false" :duration="500" class="level-swiper" previous-margin="50rpx" next-margin="50rpx" @change="swiperChange" :current="curr">
			<swiper-item v-for="(item, index) in levelList" :key="index">
				<view class="level-item" :class="{'curr': index == curr}">
					<view class="level-wrap">
						<view class="member-info">
							<view class="head-img">
								<image :src="fenxiaoInfo.headimg ? $util.img(fenxiaoInfo.headimg) : $util.getDefaultImage().head" @error="fenxiaoInfo.headimg = $util.getDefaultImage().head" mode="aspectFill"/>
							</view>
							<view class="nickname">{{ fenxiaoInfo.nickname }}</view>
							<view class="level-name">{{ item.level_name }}</view>
						</view>
						<view class="level-rate">
							<view class="rate-item" v-if="config.level > 0">
								<view class="title">一级分佣比率</view>
								<view class="rate">{{ item.one_rate }}<text class="percentage">%</text></view>
							</view>
							<view class="rate-item" v-if="config.level > 1">
								<view class="title">二级分佣比率</view>
								<view class="rate">{{ item.two_rate }}<text class="percentage">%</text></view>
							</view>
							<view class="rate-item" v-if="config.level > 2">
								<view class="title">三级分佣比率</view>
								<view class="rate">{{ item.three_rate }}<text class="percentage">%</text></view>
							</view>
						</view>

						<view class="not-unlocked" v-if="item.level_num > fenxiaoInfo.level_num">
							<text class="iconfont icon-suoding"></text>
						</view>
					</view>
				</view>
			</swiper-item>
		</swiper>

		<view class="level-condition" v-if="levelInfo">
			<view class="condition-title">
				<view class="title">快速升级技巧</view>
				<view class="rate price-font">
					<text class="complete">{{ levelInfo.complete > levelInfo.task_num ? levelInfo.task_num : levelInfo.complete }}</text>
					<text class="num">/{{ levelInfo.task_num }}</text>
				</view>
			</view>
			<view class="task">
				<view class="task-item" v-for="(item, index) in levelInfo.task" :key="index">
					<view class="flex-box">
						<view class="title">
							{{item.title}}
							<text class="iconfont icon-wenxiao" @click="openTips(item)"></text>
						</view>
						<view class="status" :class="{'complete': item.progress == 100}">
							{{ item.progress == 100 ? '已完成' : '未完成' }}</view>
					</view>
					<view class="progress">
						<progress :percent="item.progress" activeColor="#E7B667" stroke-width="4" />
					</view>
					<view class="flex-box">
						<view class="desc">{{item.desc}}</view>
						<view class="rate price-font">
							<text class="complete">{{ item.value }}</text>
							<text class="num">/{{ item.condition }}</text>
						</view>
					</view>
				</view>
			</view>
		</view>

		<uni-popup type="bottom" ref="tips">
			<view class="popup">
				<view class="popup-header">
					<text class="tit">提示</text>
					<text class="iconfont icon-close" @click="$refs.tips.close()"></text>
				</view>
				<view class="popup-body">
					<view>{{ tips }}</view>
					<view v-if="levelInfo">{{ levelInfo.upgrade_type == 1 ? '满足任意一条件即可升级' : '满足全部条件才能进行升级' }}</view>
				</view>
			</view>
		</uni-popup>

		<ns-goods-recommend route="fenxiao_level"></ns-goods-recommend>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	import fenxiaoWords from 'common/js/fenxiao-words.js';
	export default {
		data() {
			return {
				fenxiaoInfo: {
					condition: {
						last_level: null
					}
				},
				config: {},
				levelList: [],
				curr: 0,
				tips: ''
			};
		},
		mixins: [fenxiaoWords],
		computed: {
			levelInfo() {
				if (this.levelList.length) {
					let level = this.levelList[this.curr];
					level.task = [];
					level.complete = 0;

					if (level.one_fenxiao_order_num > 0) {
						let task = {
							title: '下级消费',
							desc: '下级消费单数满' + level.one_fenxiao_order_num + '单',
							tips: '分销商自己购买和自己推荐的直属会员购买的订单次数达到'+ level.one_fenxiao_order_num + '单',
							condition: level.one_fenxiao_order_num,
							value: this.fenxiaoInfo.one_fenxiao_order_num,
							progress: parseFloat(this.fenxiaoInfo.one_fenxiao_order_num) > parseFloat(level.one_fenxiao_order_num) ? 100 : (parseFloat(this.fenxiaoInfo.one_fenxiao_order_num) / parseFloat(level.one_fenxiao_order_num) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_fenxiao_total_order > 0) {
						let task = {
							title: '下级消费',
							desc: '下级消费金额满' + this.moneyFormat(level.one_fenxiao_total_order) + '元',
							tips: '分销商自己购买和推荐的直属会员购买的订单的总额达到'+ this.moneyFormat(level.one_fenxiao_total_order) + '元',
							condition: this.moneyFormat(level.one_fenxiao_total_order),
							value: this.fenxiaoInfo.one_fenxiao_total_order,
							progress: parseFloat(this.fenxiaoInfo.one_fenxiao_total_order) > parseFloat(level.one_fenxiao_total_order) ? 100 : (parseFloat(this.fenxiaoInfo.one_fenxiao_total_order) / parseFloat(level.one_fenxiao_total_order) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_fenxiao_order_money > 0) {
						let task = {
							title: '下级消费',
							desc: '下级消费产生佣金总额满' + this.moneyFormat(level.one_fenxiao_order_money) + '元',
							tips: '分销商自己购买和自己推荐的直属会员购买的订单佣金总额达到'+ this.moneyFormat(level.one_fenxiao_order_money) + '元',
							condition: this.moneyFormat(level.one_fenxiao_order_money),
							value: this.fenxiaoInfo.one_fenxiao_order_money,
							progress: parseFloat(this.fenxiaoInfo.one_fenxiao_order_money) > parseFloat(level.one_fenxiao_order_money) ? 100 : (parseFloat(this.fenxiaoInfo.one_fenxiao_order_money) / parseFloat(level.one_fenxiao_order_money) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.order_num > 0) {
						let task = {
							title: '自身消费',
							desc: '自身消费单数满' + level.order_num + '单',
							tips: '分销商自己购买的订单次数达到'+ level.order_num + '单',
							condition: level.order_num,
							value: this.fenxiaoInfo.order_num,
							progress: parseFloat(this.fenxiaoInfo.order_num) > parseFloat(level.order_num) ? 100 : (parseFloat(this.fenxiaoInfo.order_num) / parseFloat(level.order_num) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.order_money > 0) {
						let task = {
							title: '自身消费',
							desc: '自身消费金额满' + this.moneyFormat(level.order_money) + '元',
							tips: '分销商自己购买的订单总额满足'+ this.moneyFormat(level.order_money) + '元',
							condition: this.moneyFormat(level.order_money),
							value: this.fenxiaoInfo.order_money,
							progress: parseFloat(this.fenxiaoInfo.order_money) > parseFloat(level.order_money) ? 100 : (parseFloat(this.fenxiaoInfo.order_money) / parseFloat(level.order_money) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_child_num > 0) {
						let task = {
							title: '邀请好友',
							desc: '邀请好友人数达到' + level.one_child_num + '人',
							tips: '分销商的直属下级会员人数达到'+level.one_child_num+'人（包含已经申请成为分销商的）',
							condition: level.one_child_num,
							value: this.fenxiaoInfo.one_child_num,
							progress: parseFloat(this.fenxiaoInfo.one_child_num) > parseFloat(level.one_child_num) ? 100 : (parseFloat(this.fenxiaoInfo.one_child_num) / parseFloat(level.one_child_num) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_child_fenxiao_num > 0) {
						let task = {
							title: '邀请好友',
							desc: '邀请好友成为分销商人数达到' + level.one_child_fenxiao_num + '人',
							tips: '分销商的直属下级分销商人数达到'+ level.one_child_fenxiao_num + '人',
							condition: level.one_child_fenxiao_num,
							value: this.fenxiaoInfo.one_child_fenxiao_num,
							progress: parseFloat(this.fenxiaoInfo.one_child_fenxiao_num) > parseFloat(level.one_child_fenxiao_num) ? 100 : (parseFloat(this.fenxiaoInfo.one_child_fenxiao_num) / parseFloat(level.one_child_fenxiao_num) * 100).toFixed(2)
						}
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					level.task_num = level.upgrade_type == 1 ? 1 : level.task.length;
					return level;
				}
			}
		},
		onLoad() {},
		onShow() {
			setTimeout( () => {
				if (!this.addonIsExist.fenxiao) {
					this.$util.showToast({
						title: '商家未开启分销',
						mask: true,
						duration: 2000
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index');
					}, 2000);
				}
			},1000);

			if (this.fenxiaoWords && this.fenxiaoWords.fenxiao_name) this.$langConfig.title(this.fenxiaoWords.fenxiao_name + '等级');

			if (this.storeToken) {
				this.getFenxiaoInfo();
				this.getBasicsConfig();
			} else {
				this.$util.redirectTo('/pages_tool/login/index', {
					back: '/pages_promotion/fenxiao/level'
				});
			}
		},
		methods: {
			/**
			 * 获取分销等级信息
			 */
			getFenxiaoLevel() {
				this.$api.sendRequest({
					url: '/fenxiao/api/Level/lists',
					success: res => {
						if (res.code == 0 && res.data) {
							this.levelList = res.data;
							this.levelList.forEach((item, index) => {
								if (item.level_id == this.fenxiaoInfo.level_id) this.curr = index;
							})
						}
					}
				});
			},
			/**
			 * 获取分销商信息
			 */
			getFenxiaoInfo() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/detail',
					success: res => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						if (res.code >= 0 && res.data) {
							this.fenxiaoInfo = res.data;
							this.curr = this.fenxiaoInfo.level_num;
							this.getFenxiaoLevel();
						} else {
							this.$util.redirectTo('/pages_promotion/fenxiao/apply');
						}
					},
					fail: () => {
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			/**
			 * 获取分销基本配置
			 */
			getBasicsConfig() {
				this.$api.sendRequest({
					url: '/fenxiao/api/config/basics',
					success: res => {
						if (res.code >= 0) {
							this.config = res.data;
						}
					}
				});
			},
			swiperChange(e) {
				this.curr = e.detail.current;
			},
			moneyFormat(money) {
				if (isNaN(parseFloat(money))) return money;
				return parseFloat(money).toFixed(2);
			},
			openTips(data) {
				this.tips = data.tips;
				this.$refs.tips.open();
			}
		}
	};
</script>

<style lang="scss">
	@import './public/css/level.scss';
</style>
<style scoped lang="scss">
	/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		background: none;
		max-height: unset !important;
		overflow-y: hidden !important;
	}

	/deep/ .uni-popup__wrapper {
		border-radius: 20rpx 20rpx 0 0;
	}

	/deep/ .uni-popup {
		z-index: 8;
	}

	/deep/ .sku-layer .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		max-height: unset !important;
	}
</style>