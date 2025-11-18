<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="content">
		<mescroll-uni ref="mescroll" @getData="getGoodsList">
			<block slot="list">
				<view class="goods-list" :style="{ backgroundImage: 'url(' + $util.img('public/uniapp/fenxiao/promote/promote_bg.png') + ')' }">
					<scroll-view class="quick-nav" scroll-x="true">
						<!-- #ifdef MP -->
						<view class="uni-scroll-view-content">
							<!-- #endif -->
							<view class="quick-nav-item" :class="{ selected: categoryId == 0 }" @click="changeCategory(0)">全部</view>
							<view
								class="quick-nav-item"
								v-for="item in categoryList"
								:class="{ selected: categoryId == item.category_id }"
								@click="changeCategory(item.category_id)"
							>
								{{ item.category_name }}
							</view>
							<!-- #ifdef MP -->
						</view>
						<!-- #endif -->
					</scroll-view>
					<view v-for="(item, index) in goodsList" :key="index" class="goods-item" @click="navToDetailPage(item)">
						<view class="image-wrap">
							<image :src="$util.img(item.sku_image, { size: 'mid' })" @error="imageError(index)" mode="aspectFill" />
						</view>
						<view class="goods-content">
							<view class="goods-name">
								<text class="name">{{ item.sku_name }}</text>
								<view class="label-list" v-if="item.label_name">
									<text class="label-item">{{ item.label_name }}</text>
								</view>
							</view>
							<view class="goods-bottom">
								<view class="goods-price color-base-text">
									<text class="font-size-tag">￥</text>
									{{ item.discount_price }}
								</view>
								<view class="goods-share" @click.stop="shareFn('goods', index)">
									<text class="icondiy icon-system-share"></text>
									<text class="txt" v-if="!is_fenxiao">分享</text>
									<text class="txt" v-else>赚{{ item.commission_money }}元</text>
								</view>
							</view>
						</view>
					</view>
					<view class="empty" v-if="goodsList.length == 0">
						<ns-empty :isIndex="false" text="暂无分销商品" textColor="#fff"></ns-empty>
					</view>
				</view>
			</block>
		</mescroll-uni>
		<view class="active-btn" v-if="goodsList.length">
			<!-- #ifdef MP -->
			<button class="share-btn" :plain="true" open-type="share">分享好友</button>
			<text class="tag">|</text>
			<!-- #endif -->
			<text class="btn" @click="shareFn('fenxiao')">生成海报</text>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>

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

						<!-- #ifdef MP-WEIXIN -->
						<view class="share-box" v-if="goodsCircle">
							<button class="share-btn" :plain="true" @click="openBusinessView">
								<view class="iconfont icon-haowuquan"></view>
								<text>分享到好物圈</text>
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
		<!-- 海报 -->
		<view @touchmove.prevent.stop>
			<uni-popup ref="posterPopup" type="bottom" class="poster-layer">
				<template v-if="poster != '-1'">
					<view>
						<view class="image-wrap">
							<image :src="$util.img(poster)" :show-menu-by-longpress="true" />
						</view>
						<!-- #ifdef MP || APP-PLUS  -->
						<view class="save" @click="saveGoodsPoster()">保存图片</view>
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
		<!-- 悬浮按钮 -->
		<hover-nav></hover-nav>

		<!-- #ifdef MP-WEIXIN -->
		<!-- 小程序隐私协议 -->
		<privacy-popup ref="privacyPopup"></privacy-popup>
		<!-- #endif -->
	</view>
</template>

<script>
import list from './public/js/goods_list.js';
import fenxiaoWords from 'common/js/fenxiao-words.js';

export default {
	mixins: [list, fenxiaoWords]
};
</script>

<style>
.quick-nav >>> .uni-scroll-view-content {
	display: flex;
}
</style>
<style lang="scss">
/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
	max-height: unset !important;
}
.quick-nav {
	margin-bottom: 20rpx;
	.quick-nav-item {
		display: flex;
		align-items: center;
		padding: 0 18rpx;
		box-sizing: border-box;
		flex-shrink: 0;
		border-radius: 40rpx;
		margin-right: 20rpx;
		height: 48rpx;
		font-size: 24rpx;
		background: #fff;
		color: $base-color;

		&.selected {
			background: $base-color;
			color: #fff;
		}

		&:last-child {
			margin-right: 0;
		}
	}
}
.content {
	overflow: hidden;
	padding: 0 30rpx 160rpx;
	min-height: 100vh;
	background-color: #ff2d46;

	.goods-list {
		min-height: 100vh;
		padding: 420rpx 30rpx 0;
		background-size: 100%;
		background-repeat: no-repeat;
		box-sizing: border-box;
		.goods-item {
			margin-bottom: 20rpx;
			background: #ffffff;
			padding: $padding;
			display: flex;
			border-radius: 10rpx;
			&:last-child {
				margin-bottom: 0;
			}
		}

		.image-wrap {
			display: inline-block;
			width: 200rpx;
			height: 200rpx;
			line-height: 200rpx;
			border-radius: 10rpx;
			overflow: hidden;
			flex-shrink: 0;
			image {
				width: 100%;
				height: 100%;
				opacity: 1;
				border-radius: 20rpx;
			}
		}

		.goods-content {
			width: calc(100% - 200rpx);
			min-height: 160rpx;
			padding-left: $padding;
			box-sizing: border-box;
			display: flex;
			flex-direction: column;
			justify-content: space-between;

			.goods-name {
				width: 100%;
				line-height: 1.3;
				.name {
					line-height: 1.3;
					word-break: break-all;
					text-overflow: ellipsis;
					overflow: hidden;
					display: -webkit-box;
					-webkit-line-clamp: 2;
					-webkit-box-orient: vertical;
				}
				.label-list {
					display: flex;
					align-items: center;
					margin-top: 6rpx;
					.label-item {
						padding: 4rpx 10rpx;
						font-size: $font-size-tag;
						color: $base-color;
						border: 2rpx solid $base-color;
						border-radius: 6rpx;
						line-height: 1;
					}
				}
			}

			.goods-bottom {
				width: 100%;
				display: flex;
				justify-content: space-between;
				align-items: center;

				.goods-price {
					line-height: 1.3;
					font-size: $font-size-base;
					font-weight: bold;
				}

				.goods-share {
					height: 50rpx;
					display: flex;
					justify-content: center;
					align-items: center;
					padding: 0 $padding;
					border-radius: 50rpx;
					border: 2rpx solid $base-color;
					text {
						color: $base-color;
						border-radius: 40rpx;
						font-size: $font-size-tag;
					}

					.icondiy {
						margin-right: 4rpx;
						font-size: $font-size-base;
					}
				}
			}
		}
	}
	.active-btn {
		position: fixed;
		bottom: 40rpx;
		left: 80rpx;
		right: 80rpx;
		height: 80rpx;
		display: flex;
		justify-content: center;
		align-items: center;
		z-index: 2;
		border-radius: 50rpx;
		background-color: $base-color;
		color: #fff;
		.btn {
			flex: 1;
			text-align: center;
		}
		.share-btn {
			margin: 0;
			padding: 0;
			flex: 1;
			text-align: center;
			border: 0;
			color: #fff;
		}
	}

	.share-popup,
	.uni-popup__wrapper-box {
		.share-title {
			line-height: 60rpx;
			font-size: $font-size-toolbar;
			padding: 15rpx 0;
			text-align: center;
		}

		.share-content {
			display: flex;
			display: -webkit-flex;
			-webkit-flex-wrap: wrap;
			-moz-flex-wrap: wrap;
			-ms-flex-wrap: wrap;
			-o-flex-wrap: wrap;
			flex-wrap: wrap;
			padding: 80rpx 15rpx;

			.share-box {
				flex: 1;
				text-align: center;

				.share-btn {
					margin: 0;
					padding: 0;
					border: none;
					line-height: 1;
					height: auto;
					text {
						margin-top: 20rpx;
						font-size: $font-size-tag;
						display: block;
						color: $color-title;
					}
				}

				.iconfont {
					font-size: 80rpx;
					line-height: initial;
				}
				.icon-fuzhilianjie,
				.icon-pengyouquan,
				.icon-haowuquan,
				.icon-share-friend {
					color: #07c160;
				}
			}
		}

		.share-footer {
			height: 90rpx;
			line-height: 90rpx;
			border-top: 2rpx solid $color-line;
			text-align: center;
		}
	}
	.poster-layer {
		.generate-poster {
			padding: 40rpx 0;
			.iconfont {
				font-size: 80rpx;
				color: #07c160;
				line-height: initial;
			}
			> view {
				text-align: center;
				&:last-child {
					margin-top: 20rpx;
				}
			}
		}
		.image-wrap {
			width: 64%;
			height: 854rpx;
			margin: 60rpx auto 40rpx auto;
			box-shadow: 0 0 32rpx rgba(100, 100, 100, 0.3);
			image {
				width: 480rpx;
				height: 854rpx;
			}
		}
		.msg {
			padding: 40rpx;
		}
		.save {
			text-align: center;
			height: 80rpx;
			line-height: 80rpx;
		}
		.close {
			position: absolute;
			top: 0;
			right: 20rpx;
			width: 40rpx;
			height: 80rpx;
			font-size: 50rpx;
		}
	}
}
</style>
