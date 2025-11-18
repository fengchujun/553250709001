<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="nc-modify-content">
		<view class="modify">
			<view>
				<image v-if="newImg == ''" :src="memberImg ? $util.img(memberImg) : $util.getDefaultImage().head" @error="memberImg = $util.getDefaultImage().head" mode="aspectFill"/>
				<image v-else :src="$util.img(newImg)" @error="newImg = $util.getDefaultImage().head" mode="aspectFill" />
			</view>
		</view>
		<view class="opection-box">
			<block v-if="newImg == ''">
				<!-- #ifdef MP-ALIPAY -->
				<button type="primary" @click="uploadFace()">点击上传</button>
				<!-- #endif -->
				<!-- #ifndef MP-ALIPAY -->
				<button type="primary" @click="chooseImage()">点击上传</button>
				<!-- #endif -->
			</block>
			<block v-else>
				<view class="opec">
					<button size="mini" class="mini" type="primary" @click="save()">确认保存</button>
					<button size="mini" class="mini" type="primary" @click="chooseImage()">重新上传</button>
				</view>
			</block>
		</view>

		<img-cropping selWidth="300" selHeight="300" @upload="myUpload" ref="imgCropping"></img-cropping>
	</view>
</template>

<script>
	import imgCropping from '@/pages_tool/components/img-cropping/cropping.vue';

	export default {
		data() {
			return {
				memberImg: '',
				newImg: '',
				imgurl: ''
			};
		},
		components: {
			imgCropping
		},
		onShow() {
			if (!this.storeToken) {
				this.$util.redirectTo('/pages_tool/login/index', {
					back: '/pages_tool/member/modify_face'
				}, 'redirectTo');
				return;
			}

			this.memberImg = this.memberInfo.headimg;
			this.imgurl = this.memberInfo.headimg;
		},
		methods: {
			chooseImage() {
				this.$refs.imgCropping.fSelect();
			},
			//上传返回图片
			myUpload(rsp) {
				let app_type = 'h5';
				let app_type_name = 'H5';

				// #ifdef MP
				app_type = 'weapp';
				app_type_name = 'weapp';
				// #endif
				uni.request({
					url: this.$config.baseUrl + '/api/upload/headimgBase64',
					method: 'POST',
					data: {
						app_type: app_type,
						app_type_name: app_type_name,
						images: rsp.base64,
						token: this.$store.state.token || '',
					},
					header: {
						'content-type': 'application/x-www-form-urlencoded;application/json'
					},
					dataType: 'json',
					responseType: 'text',
					success: res => {
						if (res.data.code == 0) {
							this.newImg = res.data.data.pic_path;
							this.imgurl = res.data.data.pic_path;
						}
					},
					fail: () => {
						this.$util.showToast({
							title: '头像上传失败'
						});
					}
				});
			},
			previewImage() {
				uni.previewImage({
					current: 0,
					urls: this.images
				});
			},
			save() {
				this.$api.sendRequest({
					url: '/api/member/modifyheadimg',
					data: {
						headimg: this.imgurl
					},
					success: res => {
						if (res.code == 0) {
							this.memberInfo.headimg = this.imgurl;
							this.$store.commit('setMemberInfo', this.memberInfo);
							this.$util.showToast({
								title: '头像修改成功'
							});
							setTimeout(() => {
								this.$util.redirectTo('/pages_tool/member/info', {}, 'redirectTo');
							}, 2000);
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			uploadFace() {
				uni.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					success: (chooseImageRes) => {
						const tempFilePaths = chooseImageRes.tempFilePaths;
						this.$api.upload({
							url: '/api/upload/headimg',
							filePath: tempFilePaths[0],
							fileType: 'image',
							success: (res) => {
								if (res.code) {
									this.newImg = res.data.pic_path;
									this.imgurl = res.data.pic_path;
								}
							}
						})
					}
				});
			}
		}
	};
</script>

<style lang="scss">
	page {
		overflow: hidden;
	}

	.modify {
		position: relative;
		padding-top: 50rpx;

		view {
			width: 500rpx;
			height: 500rpx;
			margin: 0 auto;
			overflow: hidden;
			background-color: #ffffff;
			border: 4rpx solid #ffffff;
			border-radius: 100%;

			image {
				width: 100%;
				height: 100%;
			}
		}
	}

	.opection-box {
		margin-top: 50rpx;
	}

	.opec {
		width: 100%;
		padding: 0 10%;
		box-sizing: border-box;
		display: flex;
		justify-content: space-between;

		button {
			padding: 0 30rpx;
			height: 60rpx;
			line-height: 60rpx;
			border: none;
		}
	}
</style>