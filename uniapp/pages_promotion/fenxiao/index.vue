<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="fenxiao-index">
		<block v-if="info.fenxiao_id > 0">
			<!-- 头部 -->
			<view class="fenxiao-index-header" :class="{ 'not-level': !levelList.length }" :style="{ background: 'url(' + $util.img('public/uniapp/fenxiao/index/header_bg.png') + ') no-repeat top left / 100% 100%' }">
				<view class="member">
					<view class="member-pic">
						<image :src="info.headimg ? $util.img(info.headimg) : $util.getDefaultImage().head" @error="info.headimg = $util.getDefaultImage().head" mode="aspectFill"/>
						<!-- <text class="level-name ">{{ info.level_name }}</text> -->
					</view>
					<view class="member-info">
						<view @click="toLevel()" class="member-info-box">
							<text class="name">{{ info.fenxiao_name }}</text>
						</view>
						<view class="recommend" v-if="info.level_num > 0">{{ info.level_name }}</view>
					</view>
					<view class="promote-rules font-size-tag" @click="$util.redirectTo('/pages_promotion/fenxiao/promote')">
						<text class="iconfont icon-wenhao font-size-tag"></text>
						推广规则
					</view>
				</view>
				<view class="fenxiao-level-wrap" v-if="levelList.length">
					<image :src="$util.img('public/uniapp/fenxiao/index/level_icon.png')" mode="widthFix" class="level-icon"/>
					<view class="level-wrap" @click="$util.redirectTo('/pages_promotion/fenxiao/level')">
						<view class="title">{{ info.level_num > 0 ? info.level_name : '等级未解锁' }}</view>
						<view class="desc">下单、邀请好友均可提升等级</view>
					</view>
					<view class="btn" @click="$refs.taskPopup.open()" v-if="info.condition.last_level">做任务</view>
				</view>
			</view>

			<view class="fenxiao-index-allmoney">
				<view class="allmoney-top-money">
					<view class="allmoney-top">
						<view class="font-size-sub">可{{ fenxiaoWords.withdraw }}（元）</view>
						<view class="withdrawal-record" @click="$util.redirectTo('/pages_promotion/fenxiao/withdraw_list')">
							提现记录
							<text class="iconfont icon-right"></text>
						</view>
					</view>
					<view class="total-commission price-font">{{ info.account }}</view>
				</view>

				<view class="allmoney-bottom">
					<view class="allmoney-all-wrap" @click="$util.redirectTo('/pages_promotion/fenxiao/bill')">
						<view class="title">总{{ fenxiaoWords.account }}</view>
						<view class="money price-font">{{ info.total_commission | moneyFormat }}</view>
					</view>
					<view class="allmoney-all-wrap" @click="$util.redirectTo('/pages_promotion/fenxiao/withdraw_list')">
						<view class="title">已提现{{ fenxiaoWords.account }}</view>
						<view class="money price-font">{{ info.account_withdraw | moneyFormat }}</view>
					</view>
					<view class="allmoney-all-wrap" @click="$util.redirectTo('/pages_promotion/fenxiao/withdraw_list')">
						<view class="title">提现中{{ fenxiaoWords.account }}</view>
						<view class="money price-font">{{ info.account_withdraw_apply | moneyFormat }}</view>
					</view>
					<view class="allmoney-all-wrap" @click="$util.redirectTo('/pages_promotion/fenxiao/order', { type: 1 })">
						<view class="title">待结算{{ fenxiaoWords.account }}</view>
						<view class="money price-font">{{ info.in_progress_money | moneyFormat }}</view>
					</view>
				</view>
				<view class="withdraw-btn" @click="$util.redirectTo('/pages_promotion/fenxiao/withdraw_apply')">申请提现
				</view>
			</view>

			<view class="fenxiao-team">
				<view class="fenxiao-index-other">
					<view @click="$util.redirectTo('/pages_promotion/fenxiao/team')" class="all-money-item">
						<view class="img-wrap">
							<image :src="$util.img('public/uniapp/fenxiao/index/team1.png')" mode="aspectFill"></image>
						</view>
						<view class="all-money-tit-wrap">
							<text class="all-money-tit">{{ fenxiaoWords.my_team }}</text>
							<text class="all-money-num">{{ teamNum.num }}人</text>
						</view>
					</view>
				</view>
				<view class="fenxiao-index-other">
					<view @click="$util.redirectTo('/pages_promotion/fenxiao/child_fenxiao')" class="all-money-item">
						<view class="img-wrap">
							<image :src="$util.img('public/uniapp/fenxiao/index/team2.png')" mode="aspectFill"></image>
						</view>
						<view class="all-money-tit-wrap">
							<text class="all-money-tit">{{ fenxiaoWords.fenxiao_name }}</text>
							<text class="all-money-num">{{ teamNum.fenxiao_num }}人</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 功能列表 -->
			<view class="fenxiao-menu-list">
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/withdraw_list')">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/tixian.png')" mode="widthFix"></image>
					</view>
					<view class="info">
						<view class="title">{{ fenxiaoWords.withdraw }}明细</view>
						<view class="desc">累计{{ fenxiaoWords.account }}￥{{ info.total_commission | moneyFormat }}</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/order')">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/order.png')" mode="widthFix"></image>
					</view>
					<view class="info">
						<view class="title">{{ fenxiaoWords.concept + '订单' }}</view>
						<view class="desc">{{ fenxiaoWords.concept + '订单' }}明细</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/promote')">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/code.png')" mode="widthFix"></image>
					</view>
					<view class="info">
						<view class="title">邀请好友</view>
						<view class="desc">邀好友赚好礼</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/bill')">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/bill.png')" mode="widthFix"></image>
					</view>
					<view class="info">
						<view class="title">账单报表</view>
						<view class="desc">{{ fenxiaoWords.account }}变更明细</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/goods_list', { templateId: templateId })">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/market.png')" mode="widthFix"></image>
					</view>
					<view class="info">
						<view class="title">{{ fenxiaoWords.concept }}商品</view>
						<view class="desc">{{ fenxiaoWords.concept }}商品</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/ranking_list', { type: 'profit' })">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/commission_rank.png')" mode="widthFix">
						</image>
					</view>
					<view class="info">
						<view class="title">{{ fenxiaoWords.account }}排行</view>
						<view class="desc">您的排名为第{{ profitRanking }}位</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/ranking_list', { type: 'invited_num' })">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/promoter_rank.png')" mode="widthFix">
						</image>
					</view>
					<view class="info">
						<view class="title">推广人排行</view>
						<view class="desc">您的排名为{{ invitedNumRanking }}位</view>
					</view>
				</view>
				<view class="menu-item" @click="$util.redirectTo('/pages_promotion/fenxiao/level')" v-if="levelList.length">
					<view class="icon-wrap">
						<image :src="$util.img('public/uniapp/fenxiao/index/team_reward.png')" mode="widthFix"></image>
					</view>
					<view class="info">
						<view class="title">等级说明</view>
						<view class="desc">{{ fenxiaoWords.concept }}等级说明</view>
					</view>
				</view>
			</view>
		</block>
		<ns-copyright></ns-copyright>
		<view class="empty" v-if="!info.fenxiao_id > 0 && showEmpty">
			<image :src="$util.img('public/uniapp/fenxiao/index/no-fenxiao.png')" mode="widthFix"></image>
			<text>您还不是{{ fenxiaoWords.fenxiao_name }}，请先提交申请</text>
			<view @click="$util.redirectTo('/pages_promotion/fenxiao/apply')" class="color-base-bg">立即加入</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
		<ns-login ref="login"></ns-login>

		<uni-popup ref="taskPopup" type="bottom">
			<view class="task-popup popup-layer" v-if="levelInfo">
				<view class="head-wrap">
					<text>快速升级技巧</text>
					<text class="iconfont icon-close" @click="$refs.taskPopup.close()"></text>
				</view>
				<view class="body-wrap">
					<view class="equity-content" v-for="(item, index) in levelInfo.task" :key="index">
						<text class="subordinate-consumption">
							{{ item.title }}
							<text class="iconfont icon-wenhao" @click="openTips(item)"></text>
							<text class="incomplete">{{ item.progress == 100 ? '已完成' : '未完成' }}</text>
						</text>
						<view class="circle">
							<progress :percent="item.progress" activeColor="#E7B667" stroke-width="4" />
						</view>
						<text class="zero">{{ item.value }}</text>
						<text>/{{ item.condition }}</text>
						<text class="to-complete-box" @click="$util.redirectTo('/pages_promotion/fenxiao/promote')">
							<text class="to-complete">{{ item.progress == 100 ? '已完成' : '去完成' }}</text>
						</text>
					</view>
				</view>
			</view>
		</uni-popup>

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
	</view>
</template>

<script>
	import fenxiaoWords from '@/common/js/fenxiao-words.js';
	import uniPopup from '@/components/uni-popup/uni-popup.vue';

	export default {
		components: {
			uniPopup
		},
		data() {
			return {
				info: {
					fenxiao_name: '',
					fenxiao_id: 0
				},
				showEmpty: false,
				teamNum: {
					fenxiao_num: 0,
					member_num: 0
				},
				//初始化获取到的模板id
				templateId: '',
				tips: '',
				profitRanking: 0,
				invitedNumRanking: 0,
				levelList: []
			};
		},
		mixins: [fenxiaoWords],
		onShow() {
			if (this.fenxiaoWords && this.fenxiaoWords.concept) this.$langConfig.title(this.fenxiaoWords.concept + '中心');

			if (this.storeToken) {
				this.checkFenxiaoIsStart();
			} else {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_promotion/fenxiao/index');
				});
			}

			this.getTemplateId();
		},
		onLoad() {
			setTimeout( () => {
				if (!this.addonIsExist.fenxiao) {
					this.$util.showToast({
						title: '商家未开启分销',
						mask: true,
						duration: 2000
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index', {}, 'redirectTo');
					}, 2000);
				}
			},1000);
			this.getFenxiaoLevel();
		},
		methods: {
			/**
			 * 检测分销是否开启
			 */
			checkFenxiaoIsStart() {
				this.$api.sendRequest({
					url: '/fenxiao/api/config/basics',
					success: res => {
						if (res.code == 0 && res.data) {
							if (res.data.level > 0) {
								this.getFenxiaoDetail();
							} else {
								this.$util.showToast({
									title: '商家未开启分销',
									mask: true,
									duration: 2000
								});
								setTimeout(() => {
									this.$util.redirectTo('/pages/member/index');
								}, 2000);
							}
						}
					}
				});
			},
			// 获取分销商信息
			getFenxiaoDetail() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/detail',
					success: res => {
						if (res.data) {
							if (res.data.status == -1) {
								this.$util.showToast({
									title: '当前分销商已冻结'
								});
								setTimeout(() => {
									this.$util.redirectTo('/pages/member/index');
								}, 2000);
								return;
							}
							if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
							this.info = res.data;
							this.getTeamNum();
							this.getRanking('invited_num');
							this.getRanking('profit');
							this.showEmpty = true;
						} else {
							this.$util.redirectTo('/pages_promotion/fenxiao/apply', {}, 'redirectTo');
						}
					},
					fail: res => {
						this.showEmpty = true;
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			//去提现
			goTixian() {
				this.$util.redirectTo('/pages_promotion/fenxiao/withdraw_apply');
			},
			toLevel() {
				this.$util.redirectTo('/pages_promotion/fenxiao/level');
			},
			close() {
				this.$refs.taskPopup.close();
			},
			getTeamNum() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/teamnum',
					success: res => {
						if (res.code >= 0) {
							this.teamNum = res.data;
						}
					}
				});
			},
			/**
			 * 获取分享海报模板id
			 */
			getTemplateId() {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/posterTemplateIds',
					success: res => {
						if (res.code >= 0) {
							this.templateId = [...res.data].join();
						}
					}
				});
			},
			moneyFormat(money) {
				if (isNaN(parseFloat(money))) return money;
				return parseFloat(money).toFixed(2);
			},
			openTips(data) {
				this.tips = data.tips;
				this.$refs.tips.open();
			},
			getRanking(type) {
				this.$api.sendRequest({
					url: '/fenxiao/api/fenxiao/ranking',
					data: {
						type: type
					},
					success: res => {
						if (res.code >= 0) {
							if (type == 'profit') this.profitRanking = res.data;
							if (type == 'invited_num') this.invitedNumRanking = res.data;
						}
					}
				});
			},
			getFenxiaoLevel() {
				this.$api.sendRequest({
					url: '/fenxiao/api/Level/lists',
					success: res => {
						if (res.code == 0 && res.data) {
							this.levelList = res.data;
						}
					}
				});
			}
		},
		onBackPress(options) {
			if (options.from === 'navigateBack') {
				return false;
			}
			this.$util.redirectTo('/pages/member/index', {}, 'reLaunch');
			return true;
		},
		watch: {
			storeToken: function(nVal, oVal) {
				if (nVal) {
					this.checkFenxiaoIsStart();
				}
			}
		},
		computed: {
			levelInfo() {
				if (this.info && this.info.condition && this.info.condition.last_level) {
					let level = this.info.condition.last_level;
					level.task = [];
					level.complete = 0;

					if (level.one_fenxiao_order_num > 0) {
						let task = {
							title: '下级消费',
							desc: '下级消费单数满' + level.one_fenxiao_order_num + '单',
							tips: '分销商自己购买和自己推荐的直属会员购买的订单次数',
							condition: level.one_fenxiao_order_num,
							value: this.info.one_fenxiao_order_num,
							progress: parseFloat(this.info.one_fenxiao_order_num) > parseFloat(level.one_fenxiao_order_num) ?
								100 :
								((parseFloat(this.info.one_fenxiao_order_num) / parseFloat(level.one_fenxiao_order_num)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_fenxiao_total_order > 0) {
						let task = {
							title: '下级消费',
							desc: '下级消费金额满' + this.moneyFormat(level.one_fenxiao_total_order) + '元',
							tips: '分销商自己购买和推荐的直属会员购买的订单的总额',
							condition: this.moneyFormat(level.one_fenxiao_total_order),
							value: this.info.one_fenxiao_total_order,
							progress: parseFloat(this.info.one_fenxiao_total_order) > parseFloat(level.one_fenxiao_total_order) ?
								100 :
								((parseFloat(this.info.one_fenxiao_total_order) / parseFloat(level.one_fenxiao_total_order)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_fenxiao_order_money > 0) {
						let task = {
							title: '下级消费',
							desc: '下级消费产生' + this.fenxiaoWords.account + '总额满' + this.moneyFormat(level
								.one_fenxiao_order_money) + '元',
							tips: '分销商自己购买和自己推荐的直属会员购买的订单' + this.fenxiaoWords.account + '总额',
							condition: this.moneyFormat(level.one_fenxiao_order_money),
							value: this.info.one_fenxiao_order_money,
							progress: parseFloat(this.info.one_fenxiao_order_money) > parseFloat(level.one_fenxiao_order_money) ?
								100 :
								((parseFloat(this.info.one_fenxiao_order_money) / parseFloat(level.one_fenxiao_order_money)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.order_num > 0) {
						let task = {
							title: '自身消费',
							desc: '自身消费单数满' + level.order_num + '单',
							tips: '分销商自己购买的订单次数',
							condition: level.order_num,
							value: this.info.order_num,
							progress: parseFloat(this.info.order_num) > parseFloat(level.order_num) ? 100 : ((parseFloat(this.info.order_num) / parseFloat(level.order_num)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.order_money > 0) {
						let task = {
							title: '自身消费',
							desc: '自身消费金额满' + this.moneyFormat(level.order_money) + '元',
							tips: '分销商自己购买的订单总额',
							condition: this.moneyFormat(level.order_money),
							value: this.info.order_money,
							progress: parseFloat(this.info.order_money) > parseFloat(level.order_money) ?
								100 :
								((parseFloat(this.info.order_money) / parseFloat(level.order_money)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_child_num > 0) {
						let task = {
							title: '邀请好友',
							desc: '邀请好友人数达到' + level.one_child_num + '人',
							tips: '分销商的直属下级会员人数（包含已经申请成为分销商的）',
							condition: level.one_child_num,
							value: this.info.one_child_num,
							progress: parseFloat(this.info.one_child_num) > parseFloat(level.one_child_num) ?
								100 :
								((parseFloat(this.info.one_child_num) / parseFloat(level.one_child_num)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					if (level.one_child_fenxiao_num > 0) {
						let task = {
							title: '邀请好友',
							desc: '邀请好友成为分销商人数达到' + level.one_child_fenxiao_num + '人',
							tips: '分销商的直属下级分销商人数',
							condition: level.one_child_fenxiao_num,
							value: this.info.one_child_fenxiao_num,
							progress: parseFloat(this.info.one_child_fenxiao_num) > parseFloat(level.one_child_fenxiao_num) ?
								100 :
								((parseFloat(this.info.one_child_fenxiao_num) / parseFloat(level.one_child_fenxiao_num)) * 100).toFixed(2)
						};
						if (task.progress == 100) level.complete += 1;
						level.task.push(task);
					}
					level.task_num = level.upgrade_type == 1 ? 1 : level.task.length;
					return level;
				}
			}
		}
	};
</script>

<style lang="scss">
	@import './public/css/index.scss';
</style>
<style scoped lang="scss">
	/deep/ .uni-popup__wrapper {
		height: auto;
	}

	/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box,
	.uni-popup__wrapper.uni-custom.bottom .uni-popup__wrapper-box {
		background: none;
		max-height: unset !important;
		overflow-y: hidden !important;
	}

	/deep/ .uni-popup__wrapper {
		border-radius: 20rpx 20rpx 0 0;
	}
</style>