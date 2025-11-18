<template>
	<view :style="value.pageStyle">
		<x-skeleton type="banner" :loading="loading" :configs="skeletonConfig">
			<view class="diy-store-label">
				<block v-if="businessConfig.store_business == 'store'">
					<scroll-view scroll-x="true" :class="[value.contentStyle, { between: list.length == 3 }]" :style="storeLabelWrapCss" :enable-flex="true">
						<view v-for="(item, index) in storeLabel" :class="['item']">
							<diy-icon v-if="value.icon" class="icon-box" :icon="value.icon" :value="value.style ? value.style : 'null'"></diy-icon>
							<text class="label-name" :style="{ color: value.textColor, fontSize: value.fontSize * 2 + 'rpx', fontWeight: value.fontWeight }">{{ item }}</text>
						</view>
					</scroll-view>
				</block>
				<block v-else>
					<scroll-view scroll-x="true" :class="[value.contentStyle, { between: list.length == 3 }]" :style="storeLabelWrapCss" :enable-flex="true">
						<view v-for="(item, index) in list" :class="['item']">
							<diy-icon v-if="value.icon" class="icon-box" :icon="value.icon" :value="value.style ? value.style : 'null'"></diy-icon>
							<text class="label-name" :style="{ color: value.textColor, fontSize: value.fontSize * 2 + 'rpx', fontWeight: value.fontWeight }">{{ item.label_name }}</text>
						</view>
					</scroll-view>
				</block>
			</view>
		</x-skeleton>
	</view>
</template>
<script>
	// 门店标签
	export default {
		name: 'diy-store-label',
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			}
		},
		data() {
			return {
				loading: true,
				skeletonConfig: {
					gridRows: 1,
					gridRowsGap: '20rpx',
					headHeight: '40rpx',
					headBorderRadius: '0'
				},
				list: [],
				notice: '',
				storeLabel: [],
				businessConfig: ""
			};
		},
		created() {
			this.getData();
			this.getStoreConfig();
		},
		watch: {
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.getData();
			}
		},
		computed: {
			storeLabelWrapCss: function() {
				var obj = '';
				obj += 'background-color:' + this.value.componentBgColor + ';';
				if (this.value.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.value.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.value.bottomAroundRadius * 2 + 'rpx;';
				}
				return obj;
			}
		},
		methods: {
			getData() {
				var data = {
					page: 1,
					page_size: 0
				};

				if (this.value.sources == 'initial') {
					data.page_size = this.value.count;
				} else if (this.value.sources == 'diy') {
					data.label_id_arr = this.value.labelIds.toString();
				}
				this.$api.sendRequest({
					url: '/store/api/store/labelPage',
					data: data,
					success: res => {
						if (res.code == 0 && res.data) {
							this.list = res.data.list;
						}
						this.loading = false;
					}
				});
			},
			getStoreConfig() {
				this.$api.sendRequest({
					url: '/store/api/config/config',
					success: res => {
						if (res.code >= 0) {
							this.businessConfig = res.data.business_config;
							if (res.data.business_config.store_business == "store") {
								this.getStoreInfo();
							}
						}
					}
				});
			},
			getStoreInfo() {
				this.$api.sendRequest({
					url: '/api/store/info',
					success: res => {
						if (res.data) {
							let label_arr = res.data.label_name.split(",");
							let label_count = 3;
							if (this.value.sources == 'initial') label_count = this.value.count;
							for (let i = 0; i < label_arr.length; i++) {
								if (this.storeLabel.length < label_count && label_arr[i] != '') {
									this.storeLabel.push(label_arr[i])
								}
							}
						}
					}
				});
			},
		}
	};
</script>

<style lang="scss">
	.diy-store-label {
		.style-1 {
			display: flex;
			align-items: baseline;

			/deep/ .uni-scroll-view-content {
				display: flex;
				align-items: center;
			}

			&.between {
				justify-content: space-between;

				/deep/.uni-scroll-view-content {
					justify-content: space-between;
				}
			}

			.item {
				flex-shrink: 0;
				display: flex;
				align-items: center;
				padding-right: 20rpx;

				.icon-box {
					font-size: 50rpx;
					width: 40rpx;
					height: 40rpx;
					margin-right: 10rpx;
					margin-top: 2rpx;
				}

				.label-name {
					line-height: 40rpx;
				}

				&:last-of-type {
					padding-right: 0;
				}
			}
		}
	}
</style>