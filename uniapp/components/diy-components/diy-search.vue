<template>
	<view :style="value.pageStyle">
		<view class="diy-search">
			<view class="diy-search-wrap" :class="value.positionWay" :style="fixedCss">
				<view :class="['search-box','search-box-'+value.searchStyle]" :style="searchWrapCss" @click="search()">
					<block v-if="[1,2].includes(value.searchStyle)">
						<view class="img" v-if="value.searchStyle == 2 && value.iconType == 'img'">
							<image :src="$util.img(value.imageUrl)" mode="heightFix"/>
						</view>
						<diy-icon class="icon" v-if="value.searchStyle == 2 && value.iconType == 'icon'" :icon="value.icon"
							:value="value.style ? value.style : 'null'"
							:style="{ maxWidth: 30 * 2 + 'rpx', maxHeight: 30 * 2 + 'rpx' }"></diy-icon>
						<view class="search-content" :style="inputStyle">
							<input type="text" class="uni-input ns-font-size-base" maxlength="50" :placeholder="value.title" v-model="searchText" @confirm="search()" readonly :placeholderStyle="placeholderStyle" @click.stop="search()" disabled />
							<text class="iconfont icon-sousuo3" @click.stop="search()" :style="{ color: value.textColor ? value.textColor : 'rgba(0,0,0,0)' }"></text>
						</view>
					</block>
					<block v-if="value.searchStyle == 3">
						<view class="search-content" :style="inputStyle" @click.stop="search()">
							<text class="iconfont icon-sousuo3" :style="{ color: value.textColor ? value.textColor : 'rgba(0,0,0,0)' }"></text>
							<input type="text" class="uni-input ns-font-size-base" maxlength="50" :placeholder="value.title" v-model="searchText" @confirm="search()" readonly @click.stop="search()" :placeholderStyle="placeholderStyle" disabled />
							<text class="search-content-btn" @click.stop="search()" :style="{ 'backgroundColor': value.pageBgColor ? value.pageBgColor : 'rgba(0,0,0,0)' }">搜索</text>
						</view>
						<view class="img" v-if="value.iconType == 'img'" @click.stop="redirectTo(value.searchLink)"><image :src="$util.img(value.imageUrl)" mode="heightFix"/>
						</view>
						<diy-icon class="icon" v-if="value.iconType == 'icon'" :icon="value.icon"
							:value="value.style ? value.style : 'null'"
							:style="{ maxWidth: 30 * 2 + 'rpx', maxHeight: 30 * 2 + 'rpx' }"
							@click.stop="redirectTo(value.searchLink)"></diy-icon>
					</block>
				</view>
			</view>
			<!-- 解决fixed定位后导航栏塌陷的问题 -->
			<view v-if="value.positionWay == 'fixed'" class="u-navbar-placeholder" :style="{ width: '100%', paddingTop: moduleHeight }"></view>
			<ns-login ref="login"></ns-login>
		</view>
	</view>
</template>

<script>
	// 获取系统状态栏的高度
	let systemInfo = uni.getSystemInfoSync();
	let menuButtonInfo = {};
	// 如果是小程序，获取右上角胶囊的尺寸信息，避免导航栏右侧内容与胶囊重叠(支付宝小程序非本API，尚未兼容)
	// #ifdef MP-WEIXIN || MP-BAIDU || MP-TOUTIAO || MP-QQ
	menuButtonInfo = uni.getMenuButtonBoundingClientRect();
	// #endif

	// 搜索
	export default {
		name: 'diy-search',
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			},
			topNavColor: String,
			global: {
				type: Object,
				default: () => {
					return {};
				}
			},
			haveTopCategory: {
				type: Boolean
			},
			followOfficialAccount: {
				type: Object
			},
		},
		data() {
			return {
				searchText: '',
				menuButtonInfo: menuButtonInfo,
				height: 0,
				placeholderHeight: 0,
				moduleHeight: 0
			};
		},
		computed: {
			fixedCss() {
				var obj = '';
				if (this.value.positionWay == 'fixed') {
					let top = this.fixedTop;
					// 固定定位
					if (this.global.topNavBg) obj += 'background-color:' + (this.topNavColor == 'transparent' ? this.value.pageBgColor : this.topNavColor) + ';';
					else obj += 'background-color:' + this.value.pageBgColor + ';';
					obj += 'top:' + top + ';';
					obj += 'padding-top:' + this.value.margin.top * 2 + 'rpx;';
					obj += 'padding-left:' + this.value.margin.both * 2 + 'rpx;';
					obj += 'padding-right:' + this.value.margin.both * 2 + 'rpx;';
					obj += 'padding-bottom:' + this.value.margin.bottom * 2 + 'rpx;';

				}
				return obj;
			},
			searchWrapCss() {
				var obj = '';
				obj += 'background-color:' + this.value.componentBgColor + ';';
				obj += 'text-align:' + this.value.textAlign + ';';
				return obj;
			},
			inputStyle() {
				var obj = '';
				obj += 'background-color:' + this.value.elementBgColor + ';';
				if (this.value.borderType == 2) {
					obj += 'border-radius:' + '40rpx;';
				}
				return obj;
			},
			placeholderStyle() {
				var obj = '';
				if (this.value.textColor) {
					obj += 'color:' + this.value.textColor;
				} else {
					obj += 'color: rgba(0,0,0,0)';
				}
				return obj;
			},
			fixedTop() {
				let diyPositionObj = this.$store.state.diyGroupPositionObj;
				let data = 0;
				if (diyPositionObj.diySearch && diyPositionObj.diyIndexPage && diyPositionObj.nsNavbar) {
					if (diyPositionObj.diySearch.moduleIndex > diyPositionObj.diyIndexPage.moduleIndex) {
						data = diyPositionObj.nsNavbar.originalVal + diyPositionObj.diyIndexPage.originalVal;
					} else {
						data = diyPositionObj.nsNavbar.originalVal;
					}
				} else if (diyPositionObj.diySearch && diyPositionObj.nsNavbar) {
					data = diyPositionObj.nsNavbar.originalVal;
				}

				data += 'px';
				return data;
			}
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function (nval) {
			}
		},
		created() {
			setTimeout(() => {
				// 获取组件的高度，默认高度为45（45是在375屏幕上的高度）
				const query = uni.createSelectorQuery();
				// #ifdef H5
				let cssSelect = '.page-header .u-navbar';
				// #endif

				// #ifdef MP
				let cssSelect = '.page-header >>> .u-navbar';
				// #endif
				query.select(cssSelect).boundingClientRect(data => {
					if (this.global.navBarSwitch) {
						this.height = data ? data.height : 45;
					} else {
						this.height = data ? data.height : 0;
					}
					// 如果存在分类导航组件，则追加该组件的高度
					if (this.haveTopCategory) {
						this.height += 49;
					}
				}).exec();
			});
			if (this.value.positionWay == 'fixed') this.navbarPlaceholderHeight();
		},
		mounted() {
			if (this.value.positionWay == 'fixed') this.setModuleLocationFn();
		},
		methods: {
			search() {
				this.$util.redirectTo('/pages_tool/goods/search');
			},
			redirectTo(link) {
				if (link.wap_url) {
					if (this.$util.getCurrRoute() == 'pages/member/index' && !this.storeToken) {
						this.$refs.login.open(link.wap_url);
						return;
					}
				}
				this.$util.diyRedirectTo(link);
			},
			navbarPlaceholderHeight() {
				let height = 0;
				setTimeout(() => {
					const query = uni.createSelectorQuery().in(this);
					query.select('.diy-search-wrap').boundingClientRect(data => {
						// 获取搜索框自身高度
						this.placeholderHeight = data.height;
						// 通过搜索框自身高度 - 定位模式下的多出的padding-bottom高度
						if (this.placeholderHeight) this.placeholderHeight -= this.value.margin.bottom;
					}).exec();
				});
			},
			// 向vuex中的diyIndexPositionObj增加搜索组件定位位置
			setModuleLocationFn() {
				this.$nextTick(() => {
					const query = uni.createSelectorQuery().in(this);
					query.select('.diy-search-wrap').boundingClientRect(data => {
						let diySearch = {
							originalVal: data.height || 0, //自身高度 px
							currVal: 0, //定位高度
							moduleIndex: this.value.moduleIndex //组件在diy-group的位置
						};
						this.moduleHeight = (data.height || 0) + 'px';
						this.$store.commit('setDiyGroupPositionObj', {
							'diySearch': diySearch
						});
					}).exec();
				})
			}
		}
	};
</script>

<style lang="scss">
	/deep/ .uni-input-placeholder {
		overflow: initial;
	}

	.diy-search-wrap {
		overflow: hidden;
	}

	.fixed {
		position: fixed;
		left: 0;
		right: 0;
		top: 0;
		z-index: 991;
		transition: background 0.3s;
	}

	.search-box {
		position: relative;
		display: flex;
		align-items: center;

		.img {
			height: 60rpx;
			margin-right: 20rpx;

			image {
				width: 100%;
				height: 100%;
			}
		}

		.icon {
			width: 170rpx;
			height: 60rpx;
			margin-right: 20rpx;
		}
	}

	.search-box-3 {
		.search-content {
			display: flex;
			align-items: center;
			height: 68rpx;

			.iconfont {
				position: initial;
				transform: translateY(0);
				width: auto;
				margin-left: 26rpx;
				margin-right: 12rpx;
				font-size: $font-size-base;
				line-height: 1;
			}

			.uni-input {
				flex: 1;
				padding-left: 0;
				height: 68rpx;
			}

			.search-content-btn {
				margin-right: 8rpx;
				width: 116rpx;
				height: 54rpx;
				line-height: 54rpx;
				text-align: center;
				color: #fff;
				border-radius: 30rpx;
			}
		}

		.diy-icon {
			margin-left: 20rpx;
			margin-right: 0;
			width: auto;
			font-size: 74rpx;
		}

		.img {
			margin-left: 20rpx;
			margin-right: 0;
		}
	}

	.search-content {
		flex: 1;
	}

	.search-content input {
		box-sizing: border-box;
		display: block;
		height: 64rpx;
		width: 100%;
		padding: 0 20rpx 0 40rpx;
		color: #333333;
		background: none;
	}

	.search-content .iconfont {
		position: absolute;
		top: 50%;
		right: 4rpx;
		transform: translateY(-50%);
		font-size: 30rpx;
		z-index: 10;
		width: 80rpx;
		font-weight: bold;
		text-align: center;
	}
</style>