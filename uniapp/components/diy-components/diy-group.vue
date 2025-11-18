<template>
	<view class="diy-group">
		<view v-for="(item, index) in diyDataArray" :key="index">
			<view v-if="addonIsExist.store && item.componentName == 'StoreShow'">
				<!-- 门店展示 -->
				<diy-store :value="item"></diy-store>
			</view>
			<template v-if="addonIsExist.store && item.componentName == 'StoreLabel'">
				<!-- 门店标签 -->
				<diy-store-label :value="item"></diy-store-label>
			</template>

			<template v-if="item.componentName == 'Text'">
				<!-- 文本 -->
				<diy-text :value="item"></diy-text>
			</template>

			<template v-if="item.componentName == 'Notice'">
				<!-- 公告 -->
				<diy-notice :value="item"></diy-notice>
			</template>

			<template v-if="item.componentName == 'GraphicNav'">
				<!-- 图文导航 -->
				<diy-graphic-nav :value="item"></diy-graphic-nav>
			</template>

			<template v-if="item.componentName == 'ImageAds'">
				<!-- 图片广告 -->
				<diy-img-ads :value="item"></diy-img-ads>
			</template>

			<template v-if="item.componentName == 'Search'">
				<!-- 搜索 -->
				<diy-search :value="item" :topNavColor="topNavColor" :global="diyGlobalData.global" :haveTopCategory="haveTopCategory" :followOfficialAccount="followOfficialAccount"></diy-search>
			</template>

			<template v-if="item.componentName == 'RichText'">
				<!-- 富文本 -->
				<diy-rich-text :value="item"></diy-rich-text>
			</template>

			<template v-if="item.componentName == 'HorzLine'">
				<!-- 辅助线 -->
				<diy-horz-line :value="item"></diy-horz-line>
			</template>

			<template v-if="item.componentName == 'HorzBlank'">
				<!-- 辅助空白 -->
				<diy-horz-blank :value="item"></diy-horz-blank>
			</template>

			<template v-if="item.componentName == 'Coupon' && addonIsExist.coupon">
				<!-- 优惠券 -->
				<diy-coupon :value="item"></diy-coupon>
			</template>

			<template v-if="item.componentName == 'GoodsList'">
				<!-- 商品列表 -->
				<diy-goods-list :value="item" :index="index" :scrollTop="scrollTop" :refresh="refresh"></diy-goods-list>
			</template>

			<template v-if="item.componentName == 'ManyGoodsList'">
				<!-- 多商品组 -->
				<diy-many-goods-list :value="item" :index="index" :global="diyGlobalData.global" :scrollTop="scrollTop"></diy-many-goods-list>
			</template>

			<template v-if="item.componentName == 'RubikCube'">
				<!-- 魔方、橱窗 -->
				<diy-rubik-cube :value="item"></diy-rubik-cube>
			</template>

			<template v-if="item.componentName == 'Video'">
				<!-- 视频 -->
				<diy-video :value="item"></diy-video>
			</template>

			<template v-if="item.componentName == 'Seckill' && addonIsExist.seckill">
				<!-- 秒杀 -->
				<diy-seckill :value="item"></diy-seckill>
			</template>

			<template v-if="item.componentName == 'Pintuan' && addonIsExist.pintuan">
				<!-- 拼团 -->
				<diy-pintuan :value="item"></diy-pintuan>
			</template>

			<template v-if="item.componentName == 'Groupbuy' && addonIsExist.groupbuy">
				<!-- 团购 -->
				<diy-groupbuy :value="item"></diy-groupbuy>
			</template>

			<!-- 拼团返利 -->
			<template v-if="item.componentName == 'Pinfan' && addonIsExist.pinfan">
				<diy-pinfan :value="item"></diy-pinfan>
			</template>

			<template v-if="item.componentName == 'Bargain' && addonIsExist.bargain">
				<!-- 砍价 -->
				<diy-bargain :value="item"></diy-bargain>
			</template>

			<template v-if="item.componentName == 'Presale' && addonIsExist.bargain">
				<!-- 预售 -->
				<diy-presale :value="item"></diy-presale>
			</template>

			<template v-if="item.componentName == 'Notes' && addonIsExist.notes">
				<!-- 店铺笔记 -->
				<diy-notes :value="item"></diy-notes>
			</template>

			<template v-if="item.componentName == 'FloatBtn'">
				<!-- 浮动按钮 -->
				<diy-float-btn :value="item"></diy-float-btn>
			</template>

			<template v-if="item.componentName == 'LiveInfo'">
				<!-- 小程序直播 -->
				<!-- #ifdef MP-WEIXIN -->
				<diy-live :value="item"></diy-live>
				<!-- #endif -->
			</template>

			<template v-if="item.componentName == 'FenxiaoGoodsList'">
				<!-- 分销商品 -->
				<diy-fenxiao-goods-list :value="item"></diy-fenxiao-goods-list>
			</template>

			<template v-if="item.componentName == 'GoodsRecommend'">
				<!-- 商品推荐 -->
				<diy-goods-recommend :value="item"></diy-goods-recommend>
			</template>

			<template v-if="item.componentName == 'GoodsBrand'">
				<!-- 商品品牌 -->
				<diy-goods-brand :value="item"></diy-goods-brand>
			</template>

			<template v-if="item.componentName == 'Article'">
				<!-- 文章 -->
				<diy-article :value="item"></diy-article>
			</template>

			<template v-if="item.componentName == 'MemberInfo'">
				<!-- 自定义会员中心——会员信息 -->
				<diy-member-info ref="diyMemberIndex" :value="item" :global="diyGlobalData.global"></diy-member-info>
			</template>

			<template v-if="item.componentName == 'MemberMyOrder'">
				<!-- 自定义会员中心——我的订单 -->
				<diy-member-my-order ref="diyMemberMyOrder" :value="item"></diy-member-my-order>
			</template>

			<template v-if="item.componentName == 'QuickNav'">
				<!-- 快捷导航 -->
				<diy-quick-nav :value="item"></diy-quick-nav>
			</template>

			<template v-if="item.componentName == 'PaymentQrcode'">
				<!-- 付款码 -->
				<diy-payment-qrcode :value="item"></diy-payment-qrcode>
			</template>

			<template v-if="item.componentName == 'HotArea'">
				<!-- 热区 -->
				<diy-hot-area :value="item"></diy-hot-area>
			</template>

			<template v-if="item.componentName == 'FollowOfficialAccount'">
				<!-- 关注公众号 -->
				<diy-follow-official-account :value="item"></diy-follow-official-account>
			</template>

			<!-- 自定义扩展组件 -->
			<!-- <diy-comp-extend :value="item"></diy-comp-extend> -->
		</view>
	</view>
</template>

<script>
	export default {
		components: {},
		props: {
			diyData: {
				type: Object
			},
			scrollTop: {
				type: [String, Number],
				default: '0'
			},
			haveTopCategory: {
				type: Boolean
			},
			followOfficialAccount: {
				type: Object
			},
			refresh:{
				type: Boolean
			},
		},
		data() {
			return {
				diyGlobalData: null
			};
		},
		watch: {
		    diyData: {
		    	handler(newVal, oldVal) {
					this.diyGlobalData = JSON.parse(JSON.stringify(this.diyData));
		    	},
		    	deep: true
		    }
		},
		created() {
			this.diyGlobalData = JSON.parse(JSON.stringify(this.diyData));
		},
		computed: {
			topNavColor() {
				var color = '';
				if (this.diyData.global.topNavBg) {
					color = 'transparent';
					if (this.scrollTop > 20) {
						color = this.diyData.global.topNavColor;
					} else {
						color = 'transparent';
					}
				} else {
					color = this.diyData.global.topNavColor;
				}
				return color;
			},
			// 修改属性样式
			setPageStyle() {
				this.diyGlobalData.value.forEach((item, index) => {
					item.pageStyle = '';
					// 给每个组件增加位置属性，用于定位，搜索、分类导航等定位
					item.moduleIndex = index + 1;

					// 特殊处理搜索框 当显示位置为滚动至顶部固定时，只设置背景颜色
					if (item.componentName == 'Search' && item.positionWay == 'fixed') {
						// item.pageStyle = 'background-color:' + item.pageBgColor + ';';
						return false;
					}

					item.pageStyle += 'background-color:' + item.pageBgColor + ';';
					if (item.margin) {
						item.pageStyle += 'padding-top:' + item.margin.top * 2 + 'rpx' + ';';
						item.pageStyle += 'padding-bottom:' + item.margin.bottom * 2 + 'rpx' + ';';
						item.pageStyle += 'padding-right:' + item.margin.both * 2 + 'rpx' + ';';
						item.pageStyle += 'padding-left:' + item.margin.both * 2 + 'rpx' + ';';
					}
				});

				return this.diyGlobalData.value;
			},
			// 过滤组件的渲染
			diyDataArray() {
				let data = [],
					showModuleData = this.$store.state.diyGroupShowModule ? JSON.parse(this.$store.state.diyGroupShowModule) : '';

				if (showModuleData.length) {
					if (showModuleData.includes('null')) return [];

					let diyDataArr = this.setPageStyle;
					diyDataArr.forEach((item, index) => {
						if (showModuleData.includes(item.componentName)) {
							data.push(item);
						}
					});
				} else {
					data = this.setPageStyle;
				}
				return data;
			}
		},
		methods: {}
	};
</script>

<style lang="scss">
	.diy-group {
		width: 100%;
	}
</style>