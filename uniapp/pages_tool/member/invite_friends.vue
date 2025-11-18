<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<view v-if="info" style="background-color: #fff;">

			<view class="invite_adv">
				<image :src="$util.img('public/uniapp/member/invite/top_bg.png')" mode="widthFix"></image>

				<view class="desc" @click="openRulePopup" v-if="info.remark != ''">
					<text class="iconfont icon-bangzhu"></text>
					活动说明
				</view>
				<image class="font" :src="$util.img('public/uniapp/member/invite/top_font.png')" mode="widthFix"/>
				<view class="time">
					活动时间：{{ $util.timeStampTurnTime(info.start_time, 'Y-m-d') }} ~ {{ $util.timeStampTurnTime(info.end_time, 'Y-m-d') }}
				</view>

				<view class="btn" :style="{ 'background-image': 'url(' + $util.img('public/uniapp/member/invite/top_btn.png') + ')' }" @click="openSharePopup">立即邀请</view>
			</view>
			<view class="content invite-list">
				<view class="title">我的好友</view>
				<block v-if="inviteList.length > 0">
					<view class="invitelist_block">
						<view class="invitelist">
							<view class="list-item" v-for="(item, index) in inviteList">
								<view class="img color-base-border">
									<image mode="aspectFit" :src="item.headimg == '' ? $util.img($util.getDefaultImage().head) : $util.img(item.headimg)"/>
								</view>
								<view class="list-left">
									<view class="info">
										<view class="name font-size-tag">{{ item.source_member_nickname }}</view>
										<view class="time font-size-activity-tag color-tip">
											{{ $util.timeStampTurnTime(item.create_time, 'Y-m-d') }}
										</view>
									</view>
									<view class="prize color-base-text font-size-activity-tag">
										<block v-if="item.balance > 0">{{ item.balance }}元现金红包</block>
										<block v-if="(item.point > 0 || item.coupon_num) && item.balance > 0">+</block>
										<block v-if="item.point > 0">{{ parseInt(item.point) }}积分</block>
										<block v-if="item.point > 0 && item.balance > 0 && item.coupon_num > 0">+</block>
										<block v-if="item.coupon_num > 0">{{ item.coupon_num }}张优惠券</block>
									</view>
								</view>
							</view>
						</view>
						<view class="more_invite color-tip font-size-tag " @click="moreList" v-if="isClick && total_num > page">
							查看更多
							<text class="iconfont icon-iconangledown"></text>
						</view>
						<view class="more_invite color-tip font-size-tag " @click="moreList" v-if="!isClick && inviteList.length > 5 && total_num <= page">没有更多数据了</view>
					</view>
				</block>
				<block v-else>
					<view class="empty">
						<view class="tip">您还没有邀请到新朋友哦</view>
						<view class="tip">邀请好友，赚取好礼。赶紧去试试吧~</view>
					</view>
				</block>
			</view>
			<view class="content">
				<view class="title">邀请好友奖励</view>
				<view class="invite_active">
					<view class="list">
						<view class="item" v-if="$util.inArray('balance', info.type) != -1">
							<image :src="$util.img('public/uniapp/member/invite/coupon_bg.png')" mode="aspectFill"/>
							<view class="desc">
								<view class="price">
									<text class="font-size-base">{{ $lang('common.currencySymbol') }}</text>
									<text>{{ info.balance }}</text>
								</view>
								<view class="type">现金红包</view>
							</view>
						</view>

						<view class="item" v-if="$util.inArray('point', info.type) != -1">
							<image :src="$util.img('public/uniapp/member/invite/coupon_bg.png')" mode="aspectFill"/>
							<view class="desc">
								<view class="price">
									<text>{{ parseInt(info.point) }}</text>
								</view>
								<view class="type">积分</view>
							</view>
						</view>

						<view class="item margin_right_none" v-if="$util.inArray('coupon', info.type) != -1">
							<image :src="$util.img('public/uniapp/member/invite/coupon_bg.png')" mode="aspectFill">
							</image>
							<view class="desc">
								<view class="price">
									<text>{{ info.coupon.split(',').length }}</text>
									张
								</view>
								<view class="type">优惠券</view>
							</view>
						</view>
					</view>
					<view class="desc">
						<view class="title_desc color-tip">分享给好友，让好友通过你的分享链接进入并注册登录，可获得以下奖励：</view>
						<view class="desc_list">
							<view v-if="$util.inArray('balance', info.type) != -1">
								<text>●</text>
								可得{{ info.balance }}元红包奖励
							</view>
							<view v-if="$util.inArray('point', info.type) != -1">
								<text>●</text>
								可得{{ info.point }}积分
							</view>
							<view v-if="$util.inArray('coupon', info.type) != -1">
								<text>●</text>
								可得{{ info.coupon.split(',').length }}张优惠券
							</view>

							<view v-if="info.max_fetch == 0">
								<text>●</text>
								可得奖励不受限制
							</view>
							<view v-else>
								<text>●</text>
								奖励上限为{{ info.max_fetch }}人
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="content">
				<view class="title">如何邀请好友</view>
				<view class="invite_active">
					<view class="step">
						<view>
							<view class="img">
								<image :src="$util.img('public/uniapp/member/invite/fenxiang.png')" mode="aspectFit"/>
							</view>
							<view class="text">分享链接给好友</view>
						</view>
						<view>
							<image :src="$util.img('public/uniapp/member/invite/jiantou.png')" class="jiantou"></image>
						</view>
						<view>
							<view class="img">
								<image :src="$util.img('public/uniapp/member/invite/shouji.png')" mode="aspectFit"/>
							</view>
							<view class="text">好友进入</view>
						</view>
						<view>
							<image :src="$util.img('public/uniapp/member/invite/jiantou.png')" class="jiantou"></image>
						</view>
						<view>
							<view class="img">
								<image :src="$util.img('public/uniapp/member/invite/hongbao.png')" mode="aspectFit"/>
							</view>
							<view class="text">好友注册成功获得奖励</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 海报 -->
			<view @touchmove.prevent.stop>
				<uni-popup ref="posterPopup" type="bottom" class="poster-layer">
					<template v-if="poster != '-1'">
						<view>
							<view class="image-wrap">
								<image :src="$util.img(poster)" mode="widthFix" :show-menu-by-longpress="true" />
							</view>
							<!-- #ifdef MP || APP-PLUS  -->
							<view class="save" @click="savePoster()">保存图片</view>
							<!-- #endif -->
							<!-- #ifdef H5 -->
							<view class="save">长按保存图片</view>
							<!-- #endif -->
						</view>
						<view class="close iconfont icon-close" @click="closePosterPopup()"></view>
					</template>
					<view v-else class="msg">{{ posterMsg }}</view>
				</uni-popup>
			</view>

			<!-- 分享弹窗 -->
			<view @touchmove.prevent.stop>
				<uni-popup ref="sharePopup" type="bottom" class="share-popup">
					<view>
						<view class="share-title">分享</view>
						<view class="share-content">
							<!-- #ifdef MP -->
							<view class="share-box">
								<button class="share-btn" :plain="true" open-type="share">
									<view class="iconfont icon-share-friend"></view>
									<text>分享给好友</text>
								</button>
							</view>
							<!-- #endif -->
							<view class="share-box" @click="openPosterPopup">
								<button class="share-btn" :plain="true">
									<view class="iconfont icon-pengyouquan"></view>
									<text>生成分享海报</text>
								</button>
							</view>
							<!-- #ifdef H5 -->
							<view class="share-box" @click="copyUrl">
								<button class="share-btn" :plain="true">
									<view class="iconfont icon-fuzhilianjie"></view>
									<text>复制链接</text>
								</button>
							</view>
							<!-- #endif -->
						</view>
						<view class="share-footer" @click="closeSharePopup"><text>取消分享</text></view>
					</view>
				</uni-popup>
			</view>

			<!-- 弹出规则 -->
			<view @touchmove.prevent.stop>
				<uni-popup ref="rulePopup" type="bottom">
					<view class="tips-layer">
						<view class="head" @click="closeRulePopup()">
							<view class="title">活动说明</view>
							<text class="iconfont icon-close"></text>
						</view>
						<view class="body">
							<view class="detail margin-bottom">{{ info.remark }}</view>
						</view>
					</view>
				</uni-popup>
			</view>
		</view>
		<ns-empty v-else text="暂无相关数据"></ns-empty>
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
	import inviteFriends from './public/js/invite_friends.js';
	export default {
		data() {
			return {
				inviteList: [],
				info: null,
				page: 1,
				page_size: 5,
				total_num: 0,
				isClick: true,
				poster: '-1', //海报
				posterMsg: '' //海报错误信息
			};
		},
		onLoad(option) {
			this.getBaseInfo();
			if (this.storeToken) {
				this.getList();
			} else {
				this.$nextTick(() => {
					this.$refs.login.open('/pages_tool/member/invite_friends');
				});
			}
		},
		onShow() {},
		mixins: [inviteFriends],
		onReady() {}
	};
</script>

<style lang="scss">
	@import './public/css/invite_friends.scss';
</style>
<style scoped>
	/deep/ .uni-popup__wrapper.bottom {
		border-radius: 24rpx 24rpx 0 0;
	}

	.poster-layer>>>.uni-popup__wrapper-box {
		max-height: initial !important;
	}
</style>