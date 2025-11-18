<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="page">
		<view class="detail-wrap" v-if="Object.keys(cardDetail).length">
			<view class="goods-item" :style="{backgroundImage: 'url(' + $util.img(cardDetail.goods_image || 'public/uniapp/cardservice/card_bg.png') + ')'}">
				<view class="conten">
					<view class="name using-hidden">{{cardDetail.goods_name}}</view>
					<view class="desc using-hidden">{{cardDetail.introduction}}</view>
					<view class="time-info" :class="cardDetail.status == 0 ? 'warning' : ''">
						<text class="indate-time" v-if="cardDetail.status == 1">{{parseInt(cardDetail.end_time) == 0 ? "长期有效" : '至 '+$util.timeStampTurnTime(cardDetail.end_time,'Y-m-d')}}</text>
						<text v-else>{{cardDetail.invalid_reason || '已失效'}}</text>
					</view>
				</view>
			</view>

			<view class="card-info" v-if="cardDetail.card_item.length">
				<view class="card-title">- 套餐包含以下的服务及商品 -</view>
				<view class="card-desc" v-if="cardDetail.card_type == 'commoncard'">
					卡项内项目/商品总的可用次数为{{cardDetail.total_num}}次，剩余{{ cardDetail.total_num -  cardDetail.total_use_num }}次可用
				</view>
				<view class="card-content">
					<view class="card-item" v-for="(item,index) in cardDetail.card_item" @click="$util.redirectTo('/pages/goods/detail',{'goods_id': item.goods_id})">
						<image :src="$util.img(item.sku_image)" mode="aspectFill"/>
						<view class="content">
							<view class="name multi-hidden">{{item.sku_name}}</view>
							<view class="total-num" v-if="cardDetail.card_type =='oncecard'">总次数：{{item.num}}</view>
							<text class="total-use-num" v-if="['timecard','oncecard'].includes(cardDetail.card_type)">{{cardDetail.card_type == 'timecard' ? "使用次数：不限次数" : ('已使用次数：'+item.use_num)}}</text>
						</view>
						<button type="default"
							v-if="cardDetail.status == 1 && ((cardDetail.card_type == 'commoncard' && cardDetail.total_num) || (cardDetail.card_type != 'commoncard' && (item.num - item.use_num > 0)))"
							:class="['button', {'charge-off': item.goods_class == 4},{'pick-goods': item.goods_class == 1}]"
							@click.stop="toUseFn(item)">{{item.goods_class == 4&&'去核销' || item.goods_class == 1&&'去提货'}}</button>
					</view>
				</view>
				<view class="card-off" @click="$util.redirectTo('/pages_promotion/cardservice/card/card_record',{'card_id':cardDetail.card_id})">
					<text>使用记录</text>
					<text class="icondiy icon-system-jiantouyou"></text>
				</view>
			</view>
			<view class="introduction" v-if="cardDetail.goods_content">
				<view class="title">- 商品详情 - </view>
				<view class="content">
					<ns-mp-html :content="cardDetail.goods_content"></ns-mp-html>
				</view>
			</view>
			<!-- 虚拟商品核销弹窗 -->
			<uni-popup ref="chargeOffPopup" type="center" class="charge-off" :mask-click="false">
				<view class="charge-off-wrap" :style="{backgroundImage:'url('+ $util.img('public/uniapp/cardservice/verify_bg.png') + ')'}">
					<view class="code">
						<image class="barcode" :src="virtualData.barcode" mode="aspectFit"></image>
						<image class="qrcode" :src="virtualData.verify_code_path" mode="aspectFit"></image>
					</view>
					<view class="qrcode-desc">向收银员展示此核销码</view>
					<view class="charge-list">
						<view class="charge-item">
							<text>名称</text>
							<text>{{virtualData.name}}</text>
						</view>
						<view class="charge-item">
							<text>剩余次数</text>
							<text>x {{virtualData.num}}</text>
						</view>
						<view class="charge-item">
							<text>核销码</text>
							<text @click="$util.copy(virtualData.verify_code)" class="color-base-text">点击复制</text>
						</view>
						<view class="charge-item">
							<text>有效期</text>
							<text>{{virtualData.time}}</text>
						</view>
					</view>
					<view class="close icondiy icon-system-guanbi" @click="$refs.chargeOffPopup.close();"></view>
				</view>
			</uni-popup>
		</view>
		<ns-login ref="login"></ns-login>
		<loading-cover ref="loadingCover"></loading-cover>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
	import htmlParser from '@/common/js/html-parser';
	export default {
		data() {
			return {
				cardId:0,
				cardDetail: {},
				emptyShow: false,
				virtualData: {
					name: '',
					num: '',
					time: '',
					barcode: ''
				}
			};
		},
		onLoad(option) {
			this.cardId = option.card_id;
		},
		onShow() {
			this.getData(this.cardId);
		},
		methods: {
			getData(id) {
				this.$api.sendRequest({
					url: '/cardservice/api/membercard/detail',
					data: {
						card_id: id
					},
					success: res => {
						if (res.code >= 0) {
							this.cardDetail = res.data;
							// this.cardDetail.goods_content = htmlParser(this.cardDetail.goods_content);
							if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						} else {
							this.$util.showToast({
								title: '未获取到卡信息！'
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages_promotion/cardservice/card/my_card');
							}, 1500);
						}
					},
					fail: res => {
						uni.stopPullDownRefresh();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				});
			},
			toUseFn(data) {
				if (data.goods_class == 1) {
					this.$util.redirectTo('/pages_promotion/cardservice/card/pick_goods', {
						card_id: data.card_id
					});
				} else if (data.goods_class == 4) {
					this.virtualData.name = data.sku_name;
					this.virtualData.num = data.num - data.use_num;
					this.virtualData.time = data.expire_time == 0 ? "永久" : this.$util.timeStampTurnTime(data.expire_time);
					this.virtualData.barcode = this.$util.img(data.barcode);
					this.virtualData.verify_code_path = this.$util.img(data.verify_code_data.h5.path);
					this.virtualData.verify_code = data.verify_code;
					this.$forceUpdate();
					this.$refs.chargeOffPopup.open();
				}
			},
			toDetail(item) {
				if (!this.storeToken) {
					var url = '/pages_promotion/cardservice/card/list';
					this.$refs.login.open(url);
				} else {
					this.$util.redirectTo('/pages/goods/detail', {
						goods_id: item.goods_id
					});
				}
			}
		}
	};
</script>

<style lang="scss" scoped>
	@mixin wrap {
		margin: 0 24rpx 24rpx;
		padding: 0 24rpx 30rpx;
		background-color: #fff;
		border-radius: 18rpx;
		box-sizing: border-box;
	}

	.detail-wrap {
		overflow: hidden;
		padding-top: 24rpx;

		.goods-item {
			position: relative;
			border-radius: 18rpx;
			height: 320rpx;
			margin: 0 30rpx 24rpx;
			background-repeat: no-repeat;
			background-size: cover;
			box-sizing: border-box;
			background-position: center;
			.conten {
				border-radius: 18rpx;
				width: 100%;
				height: 100%;
				padding: 44rpx 36rpx;
				box-sizing: border-box;
				display: flex;
				flex-direction: column;
				justify-content: space-between;
				background-color: rgba(0,0,0,0.4);
				.name {
					color: #fff;
					font-size: 34rpx;
					font-weight: bold;
					line-height: 1;
				}

				.desc {
					height: 52rpx;
					color: #fff;
					font-size: 28rpx;
					margin-top: 20rpx;
				}

				.time-info {
					margin-top: 20rpx;
					display: flex;
					justify-content: flex-end;
					color: #fff;
					font-size: $uni-font-size-sm;
					line-height: 1;
					&.warning{
						color:red;
					}
				}
			}
		}

		.card-info {
			@include wrap;

			.card-title {
				padding-top: 24rpx;
				text-align: center;
				font-weight: bold;
			}

			.card-desc {
				padding-left: 10rpx;
				font-size: $font-size-tag;
				color: $color-sub;
			}

			.card-content {
				margin-top: 20rpx;
				overflow: hidden;
			}

			.card-item {
				position: relative;
				margin-bottom: 28rpx;
				display: flex;
				padding: 20rpx;
				background-color: #FBF9FC;
				border-radius: 12rpx;

				&:last-of-type {
					margin-bottom: 0;
				}

				image {
					overflow: hidden;
					margin-right: 24rpx;
					width: 160rpx;
					height: 160rpx;
					border-radius: 10rpx;
				}

				.content {
					position: relative;
					flex: 1;
					display: flex;
					flex-direction: column;

					.name {
						padding-right: 30rpx;
						font-weight: bold;
						line-height: 1.3;
					}

					.total-num {
						margin-top: auto;
						line-height: 1.5;
					}

					.total-use-num,
					.total-num {
						font-size: $uni-font-size-sm;
						color: #666;
					}
				}

				.button {
					position: absolute;
					top: 70%;
					right: 10rpx;
					transform: translateY(-50%);
					padding: 0;
					margin: 0;
					width: 128rpx;
					height: 48rpx;
					line-height: 46rpx;
					border-radius: 50rpx;

					&.charge-off {
						color: $base-color;
						border: 2rpx solid $base-color;
						font-size: $uni-font-size-sm;
					}

					&.pick-goods {
						color: #fff;
						background-color: $base-color;
						font-size: $uni-font-size-sm;
					}
				}
			}

			.card-off {
				display: flex;
				justify-content: center;
				align-items: center;
				margin: 30rpx 0 10rpx;
				line-height: 1;

				text {
					font-size: $font-size-tag;
					color: #888;

					&:last-of-type {
						margin-left: 6rpx;
						font-size: $font-size-tag;
					}
				}
			}
		}

		.introduction {
			overflow: hidden;
			@include wrap;
			margin-bottom: 40rpx;

			.title {
				margin-top: 24rpx;
				text-align: center;
				font-size: 30rpx;
				font-weight: bold;
			}

			.content {
				margin-top: 30rpx;
				overflow: hidden;
				word-break: break-all;

				* {
					max-width: 100%;
					word-break: break-all;
				}
			}

		}
	}

	/deep/ .uni-popup__wrapper-box {
		max-width: 630rpx !important;
	}

	.charge-off {
		/deep/ .uni-popup__wrapper-box {
			overflow: initial !important;
			background-color: transparent !important;
		}

		.charge-off-wrap {
			padding: 20rpx 40rpx;
			width: 630rpx;
			height: 800rpx;
			box-sizing: border-box;
			background-size: contain;
			background-repeat: no-repeat;
			background-position: center;

			.code {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;

				.barcode {
					width: 480rpx;
					height: 100rpx;
				}

				.qrcode {
					margin-top: 50rpx;
					width: 330rpx;
					height: 330rpx;
				}
			}

			.qrcode-desc {
				margin: 10rpx 0;
				text-align: center;
				font-size: 30rpx;
				color: #666;
			}

			.charge-list {
				margin-top: 44rpx;
				font-size: $font-size-tag;

				.charge-item {
					display: flex;
					justify-content: space-between;
					align-items: center;
					height: 50rpx;
					line-height: 50rpx;

					text:first-of-type {
						color: #666;
					}
				}
			}

			.close {
				position: absolute;
				left: 50%;
				bottom: -146rpx;
				transform: translateX(-50%);
				color: #fff;
				font-size: 30px;
			}
		}
	}
</style>