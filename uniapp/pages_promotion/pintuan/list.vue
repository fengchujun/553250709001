<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page" :style="{ background: bgColor }">
		<mescroll-uni @getData="getData" ref="mescroll" :size="10" v-if="addonIsExist.pintuan">
			<block slot="list">
				<view class="ns-adv"><ns-adv keyword="NS_PINTUAN"></ns-adv></view>
				<view class="goods-list single-column" v-if="dataList.length">
					<view class="goods-item margin-bottom" v-for="(item, index) in dataList" :key="index">
						<view class="goods-img" @click="toDetail(item)">
							<image :src="goodsImg(item.goods_image)" mode="widthFix" @error="imgError(index)"/>
							<view class="color-base-bg goods-tag" v-if="goodsTag(item) != ''">{{ goodsTag(item) }}</view>
						</view>
						<view class="info-wrap">
							<view class="name-wrap">
								<view class="goods-name" @click="toDetail(item)">
									<text class="name-label">{{ item.pintuan_num }}人团</text>
									{{ item.goods_name }}
								</view>
								<view class="pintuan-info">
									<text class="pintuan-num">已团{{ item.sale_num }}件</text>
								</view>
							</view>
							<view class="lineheight-clear">
								<view class="discount-price">
									<text class="unit price-style small">{{ $lang('common.currencySymbol') }}</text>
									<text class="price  price-style large">{{ parseFloat(item.pintuan_price).toFixed(2).split('.')[0] }}</text>
									<text class="unit  price-style small">.{{ parseFloat(item.pintuan_price).toFixed(2).split('.')[1] }}</text>
								</view>
							</view>
							<view class="pro-info">
								<view class="delete-price font-size-activity-tag color-tip price-font">
									<text class="font-size-tag lineheight-clear txt"></text>
									<text class="unit">{{ $lang('common.currencySymbol') }}</text>
									{{ item.price }}
								</view>
								<view @click="toDetail(item)"><button type="primary" class="mini" size="mini">去拼团</button></view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="!dataList.length"><ns-empty textColor="#fff" :isIndex="false" text="暂无拼团"></ns-empty></view>
			</block>
		</mescroll-uni>
		<!-- 悬浮按钮 -->
		<hover-nav></hover-nav>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	import nsAdv from '@/components/ns-adv/ns-adv.vue';
	export default {
		components: {
			nsAdv
		},
		data() {
			return {
				dataList: [],
				mpShareData: null, //小程序分享数据
				bgColor: ''
			};
		},
		onLoad(option) {
			setTimeout( () => {
				if (!this.addonIsExist.pintuan) {
					this.$util.showToast({
						title: '商家未开启拼团',
						mask: true,
						duration: 2000
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/index/index');
					}, 2000);
				}
			},1000);

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
		},
		async onShow() {
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

			await this.getZoneConfig();
		},
		//分享给好友
		onShareAppMessage() {
			return this.mpShareData.appMessage;
		},
		//分享到朋友圈
		onShareTimeline() {
			return this.mpShareData.timeLine;
		},
		methods: {
			// 活动页面配置
			async getZoneConfig() {
				let res = await this.$api.sendRequest({
					url: '/api/config/promotionZoneConfig',
					data: {
						name: 'pintuan'
					},
					async: false
				});
				let data = res.data;
				if (data) {
					this.bgColor = data.bg_color;
				}
			},
			getData(mescroll) {
				this.$api.sendRequest({
					url: '/pintuan/api/goods/page',
					data: {
						page_size: mescroll.size,
						page: mescroll.num
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
						mescroll.endSuccess(newArr.length);
						//设置列表数据
						if (mescroll.num == 1) this.dataList = []; //如果是第一页需手动制空列表
						this.dataList = this.dataList.concat(newArr); //追加新数据
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					},
					fail() {
						//联网失败的回调
						mescroll.endErr();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			toDetail(e) {
				this.$util.redirectTo('/pages_promotion/pintuan/detail', {
					pintuan_id: e.pintuan_id
				});
			},
			imgError(index) {
				this.dataList[index].goods_image = this.$util.getDefaultImage().goods;
				this.$forceUpdate();
			},
			goodsImg(imgStr) {
				let imgs = imgStr.split(',');
				return imgs[0] ? this.$util.img(imgs[0], {
					size: 'mid'
				}) : this.$util.getDefaultImage().goods;
			},
			goodsTag(data) {
				return data.label_name || '';
			}
		}
	};
</script>

<style lang="scss">
	@import './public/css/list.scss';
</style>