<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view :style="{ backgroundColor: bgColor, minHeight: openBottomNav ? 'calc(100vh - 55px)' : '' }" class="page-img">
		<view class="site-info-box" v-if="$util.isWeiXin() && followOfficialAccount && followOfficialAccount.isShow && wechatQrcode">
			<view class="site-info">
				<view class="img-box" v-if="siteInfo.logo_square">
					<image :src="$util.img(siteInfo.logo_square)" mode="aspectFill"/>
				</view>
				<view class="info-box" :style="{ color: '#ffffff' }">
					<text class="font-size-base">{{ siteInfo.site_name }}</text>
					<text>{{ followOfficialAccount.welcomeMsg }}</text>
				</view>
			</view>
			<view class="dite-button" @click="officialAccountsOpen">关注公众号</view>
		</view>

		<view class="page-header" v-if="diyData.global && diyData.global.navBarSwitch" :style="{ backgroundImage: bgImg }">
			<ns-navbar :title-color="textNavColor" :data="diyData.global" :scrollTop="scrollTop" :isBack="false"/>
		</view>

		<diy-index-page v-if="topIndexValue" ref="indexPage" :value="topIndexValue" :bgUrl="bgUrl" :scrollTop="scrollTop" :diyGlobal="diyData.global" class="diy-index-page" @changeCategoryNav="changeCategoryNav">
			<template v-slot:components>
				<diy-group ref="diyGroup" v-if="diyData.value" :refresh="refresh" :diyData="diyData" :scrollTop="scrollTop" :haveTopCategory="true" :followOfficialAccount="followOfficialAccount"/>
			</template>
			<template v-slot:default>
				<ns-copyright v-show="isShowCopyRight"/>
			</template>
		</diy-index-page>

		<view v-else class="bg-index" :style="{ backgroundImage: backgroundUrl, paddingTop: paddingTop, marginTop: marginTop }">
			<diy-group ref="diyGroup" v-if="diyData.value" :diyData="diyData" :scrollTop="scrollTop" :followOfficialAccount="followOfficialAccount"/>
			<ns-copyright v-show="isShowCopyRight"/>
		</view>

		<template v-if="diyData.global && diyData.global.popWindow && diyData.global.popWindow.count != -1 && diyData.global.popWindow.imageUrl">
			<view @touchmove.prevent.stop>
				<uni-popup ref="uniPopupWindow" type="center" class="wap-floating" :maskClick="false">
					<view class="image-wrap">
						<image :src="$util.img(diyData.global.popWindow.imageUrl)" :style="popWindowStyle" @click="uniPopupWindowFn()" mode="aspectFit"/>
					</view>
					<text class="iconfont icon-round-close" @click="closePopupWindow"></text>
				</uni-popup>
			</view>
		</template>

		<!-- 底部tabBar -->
		<view class="page-bottom" v-if="openBottomNav">
			<diy-bottom-nav @callback="callback" :name="name"/>
		</view>

		<!-- 关注公众号弹窗 -->
		<view @touchmove.prevent class="official-accounts-inner" v-if="wechatQrcode">
			<uni-popup ref="officialAccountsPopup" type="center">
				<view class="official-accounts-wrap">
					<image class="content" :src="$util.img(wechatQrcode)" mode="aspectFit"></image>
					<text class="desc">关注了解更多</text>
					<text class="close iconfont icon-round-close" @click="officialAccountsClose"></text>
				</view>
			</uni-popup>
		</view>

		<!-- 收藏 -->
		<uni-popup ref="collectPopupWindow" type="top" class="wap-floating wap-floating-collect">
			<view v-if="showTip" class="collectPopupWindow" :style="{ marginTop: (collectTop + statusBarHeight) * 2 + 'rpx' }">
				<image :src="$util.img('public/uniapp/index/collect2.png')" mode="aspectFit"/>
				<text @click="closeCollectPopupWindow">我知道了</text>
			</view>
		</uni-popup>

		<!-- 选择门店弹出框，定位当前位置，展示最近的一个门店 -->
		<view @touchmove.prevent.stop class="choose-store">
			<uni-popup ref="chooseStorePopup" type="center" :maskClick="false" class="choose-store">
				<view class="choose-store-popup" v-if="currentStore">
					<view class="head-wrap">请确认门店</view>
					<view class="position-wrap">
						<text class="iconfont icon-dizhi"></text>
						<text class="address">{{ currentPosition || currentStore.show_address }}</text>
						<view class="reposition" @click="reGetLocation" v-if="globalStoreConfig && globalStoreConfig.is_allow_change == 1">
							<text class="iconfont icon-dingwei"></text>
							<text>重新定位</text>
						</view>
					</view>
					<view class="store-wrap" v-if="currentStore">
						<text class="tag">当前门店</text>
						<view class="store-name">{{ currentStore.store_name }}</view>
						<view class="store-close-desc" v-if="currentStore.status == 0 && currentStore.close_desc">{{ currentStore.close_desc }}</view>
						<view class="address">{{ currentStore.show_address }}</view>
						<view class="distance" v-if="currentStore.distance">
							<text class="iconfont icon-dizhi"></text>
							<text>{{ currentStore.distance > 1 ? currentStore.distance + 'km' : currentStore.distance * 1000 + 'm' }}</text>
						</view>
					</view>
					<button type="primary" @click="closeChooseStorePopup">确认进入</button>
					<view class="other-store" @click="chooseOtherStore" v-if="globalStoreConfig && globalStoreConfig.is_allow_change == 1">
						<text>选择其他门店</text>
						<text class="iconfont icon-right"></text>
					</view>
				</view>
			</uni-popup>
		</view>
		<!-- 连锁门店未开启定位或定位失败弹框 -->
		<view @touchmove.prevent.stop class="chain-stores">
			<uni-popup ref="getLocationFailRef" type="bottom" :maskClick="false" class="choose-store">
				<view class="chain-store-popup">
					<view class="title">获取位置失败</view>
					<view class="body">
						<view class="center">
							<view class="image">
								<image width="341rpx" :src="$util.img('public/uniapp/index/no_location_tips.png')" mode="aspectFit"/>
							</view>
							<view class="text-top">系统暂时定位不到您的位置</view>
							<view class="text-bottom" v-if="mapConfig.wap_is_open == 1">请确认定位服务已经打开或者您可手动选择附近的门店以便我们提供更精确的服务</view>
							<view class="text-bottom" v-else>请手动选择附近的门店以便我们提供更精确的服务</view>
							<view class="footer">
								<button :type="mapConfig.wap_is_open == 1?'default':'primary'" @click="chooseStore">选择门店</button>
								<button v-if="mapConfig.wap_is_open == 1" type="primary" class="btn-right" @click="openSetting">开启定位</button>
							</view>
							
						</view>
					</view>
				</view>
			</uni-popup>
		</view>
		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->

	</view>
</template>

<script>
	import uniPopup from '@/components/uni-popup/uni-popup.vue';
	import nsNavbar from '@/components/ns-navbar/ns-navbar.vue';
	import diyJs from '@/common/js/diy.js';
	import indexJs from './public/js/index.js';

	export default {
		components: {
			uniPopup,
			nsNavbar
		},
		mixins: [diyJs, indexJs]
	};
</script>

<style lang="scss">
	@import '@/common/css/diy.scss';
	@import './public/css/index.scss';
</style>
<style scoped>
	.wap-floating>>>.uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		background: none !important;
	}

	.choose-store>>>.goodslist-uni-popup-box {
		width: 80%;
	}

	/deep/.diy-index-page .uni-popup .uni-popup__wrapper-box {
		border-radius: 0;
	}

	/deep/ .placeholder {
		height: 0;
	}

	/deep/::-webkit-scrollbar {
		width: 0;
		height: 0;
		background-color: transparent;
		display: none;
	}

	/deep/ .sku-layer .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		max-height: unset !important;
	}
	/deep/ .chain-stores .uni-popup__mask{
		backdrop-filter: blur(10rpx);
	}
	/deep/ .chain-stores .uni-popup__wrapper.uni-custom.bottom .uni-popup__wrapper-box, .uni-popup__wrapper.uni-custom.top .uni-popup__wrapper-box{
		max-height: 100vh !important;
	}
</style>