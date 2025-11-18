<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view :style="{ backgroundColor: bgColor, minHeight: openBottomNav ? 'calc(100vh - 55px)' : '' }" class="page-img">
		<view class="page-header" v-if="diyData.global && diyData.global.navBarSwitch" :style="{ backgroundImage: bgImg }">
			<ns-navbar :title-color="textNavColor" :data="diyData.global" :scrollTop="scrollTop" :isBack="true"/>
		</view>

		<diy-index-page v-if="topIndexValue" ref="indexPage" :value="topIndexValue" :bgUrl="bgUrl" :scrollTop="scrollTop" :diyGlobal="diyData.global" class="diy-index-page">
			<diy-group ref="diyGroup" v-if="diyData.value" :diyData="diyData" :scrollTop="scrollTop" :haveTopCategory="true"/>
			<ns-copyright v-show="isShowCopyRight"/>
		</diy-index-page>

		<view v-else class="bg-index" :style="{ backgroundImage: backgroundUrl, paddingTop: paddingTop, marginTop: marginTop }">
			<diy-group ref="diyGroup" v-if="diyData.value" :diyData="diyData" :scrollTop="scrollTop"/>
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

		<!-- 收藏 -->
		<uni-popup ref="collectPopupWindow" type="top" class="wap-floating wap-floating-collect">
			<view v-if="showTip" class="collectPopupWindow" :style="{ marginTop: (collectTop + statusBarHeight) * 2 + 'rpx' }">
				<image :src="$util.img('public/uniapp/index/collect2.png')" mode="aspectFit"/>
				<text @click="closeCollectPopupWindow">我知道了</text>
			</view>
		</uni-popup>

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
	import microPageJs from './public/js/diy.js';

	export default {
		components: {
			uniPopup,
			nsNavbar
		},
		mixins: [diyJs, microPageJs]
	};
</script>

<style lang="scss">
	@import '@/common/css/diy.scss';
</style>

<style scoped>
	.wap-floating>>>.uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
		background: none !important;
	}

	/deep/.diy-index-page .uni-popup .uni-popup__wrapper-box {
		border-radius: 0;
	}

	.choose-store>>>.goodslist-uni-popup-box {
		width: 80%;
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
</style>