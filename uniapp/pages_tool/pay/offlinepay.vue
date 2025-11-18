<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view class="offlinepay">
		<view class="pay-info">
			<view class="title">实付金额</view>
			<view class="pay-price price-style">
				<text class="icon">￥</text>
				<text class="price">{{payInfo.pay_money?parseFloat(payInfo.pay_money).toFixed(2):'0.00'}}</text>
			</view>
			<view class="pay-time" v-if="actionStatus=='add'">
				<view class="text">支付剩余时间</view>
				<block v-if="payInfo.time">
					<view class="time" v-if="parseInt(payInfo.time.h)">{{payInfo.time.h}}</view>
					<view class="separator" v-if="parseInt(payInfo.time.h)">:</view>
					<view class="time">{{payInfo.time.i||'00'}}</view>
					<view class="separator">:</view>
					<view class="time">{{payInfo.time.s||'00'}}</view>
				</block>
				<block v-else>
					<view class="time">00</view>
					<view class="separator">:</view>
					<view class="time">00</view>
				</block>
			</view>
		</view>
		<view class="pay-type" v-if="config.length">
			<view class="top">
				<block v-for="(item,index) in config" :key="index">
					<view class="item" @click.stop="offlinepayTypeChange(index)">
						<view class="center":class="{'active':index===activeIndex}">
							{{item.key=='bank'?'银行卡':item.key=='wechat'?'微信支付':'支付宝'}}
						</view>
						<block v-if="activeIndex ==index">
							<image v-if="activeIndex==0" class="image left" :src="$util.img('public/uniapp/offlinepay/head_style_left.png')"></image>
							<image v-if="activeIndex==1" class="image center"  :src="$util.img('public/uniapp/offlinepay/head_style_center.png')"></image>
							<image v-if="activeIndex==2" class="image right" :src="$util.img('public/uniapp/offlinepay/head_style_right.png')"></image>
						</block>
					</view>
				</block>
			</view>
			<view v-if="config[activeIndex].key=='bank'" class="bank">
				<view class="item">
					<view class="label">银行名称：</view>
					<view class="center using-hidden">{{config[activeIndex].bank_name}}</view>
				</view>
				<view class="item">
					<view class="label">账号名称：</view>
					<view class="center using-hidden">{{config[activeIndex].account_name}}</view>
				</view>
				<view class="item">
					<view class="label">银行账号：</view>
					<view class="center using-hidden">
						<text>{{config[activeIndex].account_number}}</text>
						<text class="copy" @click.stop="copy(config[activeIndex].account_number)">复制</text>
					</view>
				</view>
				<view class="item">
					<view class="label">开户支行：</view>
					<view class="center using-hidden">{{config[activeIndex].branch_name}}</view>
				</view>
			</view>
			<view class="code" v-else>
				<view class="centent">
					<image class="image" :src="$util.img(config[activeIndex].payment_code)"></image>
				</view>
				<view class="bottom">{{config[activeIndex].account_name}}</view>
			</view>
		</view>
		<view class="pay-form" v-if="config.length">
			<view class="title">支付凭证（最多5张）</view>
			<view class="image-list">
				<view class="image-info-box" v-for="(item, index) in offlinepayInfo.imgList" :key="index">
					<image :src="$util.img(item)" mode="aspectFill" @click="preview(index)"></image>
					<view class="imgDel" @click="deleteImg(index)"><text class=" icon iconfont icon-delete"></text></view>
				</view>
				<view class="image-info-box active" @click="addImg" v-if="offlinepayInfo.imgList.length < 5">
					<text class="icon iconfont icon-zhaoxiangji"></text>
					<text>{{ offlinepayInfo.imgList.length ? 5 - offlinepayInfo.imgList.length : 0 }}/5</text>
				</view>
			</view>
			<view class="desc">
				<textarea v-model="offlinepayInfo.desc" class="input" placeholder-style="color:#999;font-weight: 400;font-size: 24rpx;" placeholder="请详细说明您的支付情况" :maxlength="200"/>
			</view>
		</view>
		<view class="pay-footer" v-if="config.length">
			<button class="back" @click.stop="back">返回</button>
			<button class="save" @click.stop="save">确定提交</button>
		</view>
	</view>
</template>

<script>
	export default {
		data(){
			return{
				config:[],
				payInfo:{},
				offlinepayInfo:{
					out_trade_no:'',
					imgs:'',
					imgList:[],
					desc:'',
					
				},
				actionStatus:'add',
				outTradeNo:'',
				time:null,
				activeIndex:0,
				repeat_flag:false,
				routePath:''
			}
		},
		onLoad(options) {
			this.outTradeNo = options.outTradeNo;
			this.offlinepayInfo.out_trade_no = options.outTradeNo;
			this.getOfflinepayConfig()
			this.getOfflinepayPayInfo(this.outTradeNo)
			this.getPayInfo(this.outTradeNo)
			
			// 获取当前页面栈实例数组
			const pages = getCurrentPages();
			// 数组中最后一个元素即为当前页面实例
			if(pages.length<1){
				this.getroutePath(this.outTradeNo)
			}
			
			
		},
		methods:{
			getOfflinepayConfig(){
				this.$api.sendRequest({
				    url: '/offlinepay/api/pay/config',
				    success: res => {
				        if (res.code >= 0 && res.data) { 
							let data = res.data.value
				            Object.keys(data).forEach(key=>{
								if(data[key].status=='1'){
									data[key].key = key
									this.config.push(data[key])
								}
							});
				        } else {
				            this.$util.showToast({
				                title: '未获取到支付配置！'
				            });
				        }
				    }
				});
			},
			getPayInfo(out_trade_no){
				this.$api.sendRequest({
				    url: '/api/pay/info',
					data:{out_trade_no},
				    success: res => {
				        if (res.code >= 0 && res.data) {
				            this.payInfo = res.data;
							this.payInfo.timestamp = res.timestamp
							if(this.payInfo.timestamp<this.payInfo.auto_close_time){
								this.payInfo.time = this.$util.countDown(this.payInfo.auto_close_time-this.payInfo.timestamp)
								this.time = setInterval(()=>{
									if(this.payInfo.timestamp>=this.payInfo.auto_close_time) clearInterval(this.time)
									this.payInfo.timestamp+=1
									this.payInfo.time = this.$util.countDown(this.payInfo.auto_close_time-this.payInfo.timestamp)
									this.$forceUpdate();
									
								},1000)	
							}
							
				        } else {
				            this.$util.showToast({
				                title: '未获取到支付信息！'
				            });
				        }
				    }
				});
			},
			getOfflinepayPayInfo(out_trade_no){
				this.$api.sendRequest({
				    url: '/offlinepay/api/pay/info',
					data:{out_trade_no},
				    success: res => {
				        if (res.code >= 0 && res.data) {
				            this.actionStatus = 'edit'
							this.offlinepayInfo = res.data
							this.offlinepayInfo.imgList = this.offlinepayInfo.imgs?this.offlinepayInfo.imgs.split(','):[]
							// this.offlinepayInfo.out_trade_no = this.outTradeNo
				        }
				    }
				});
			},
			//获取路由
			getroutePath(out_trade_no){
				this.$api.sendRequest({
				    url: '/api/pay/outTradeNoToOrderDetailPath',
					data:{out_trade_no},
				    success: res => {
				        if (res.code >= 0 && res.data) {
				            this.routePath = res.data
				        }
				    }
				});
				
			},
			offlinepayTypeChange(index){
				this.activeIndex = index
			},
			copy(text){
				this.$util.copy(text);
			},
			//添加图片
			addImg() {
				let size = this.offlinepayInfo.imgList.length
				this.$util.upload(5 - size, {
					path: ''
				}, res => {
					this.offlinepayInfo.imgList = this.offlinepayInfo.imgList.concat(res);
					this.offlinepayInfo.imgs = this.offlinepayInfo.imgList.toString()
				},'/offlinepay/api/pay/uploadimg');
			},
			//删除图片
			deleteImg(index, ) {
				this.offlinepayInfo.imgList.splice(index, 1);
				this.offlinepayInfo.imgs = this.offlinepayInfo.imgList.toString()
			},
			back(){
				const pages = getCurrentPages();
				// 数组中最后一个元素即为当前页面实例
				if(pages.length>1){
					uni.navigateBack({
						delta: 1
					});
				}else{
					this.$util.redirectTo(this.routePath,{},'redirectTo')
				}
				
			},
			save(){
				if(this.repeat_flag) return;
				if(!this.offlinepayInfo.imgList.length){
					uni.showToast({
						title: '请至少上传一张凭证',
						icon: 'none'
					})
					return;
				}
				this.repeat_flag = true
				// #ifdef MP
				this.$util.subscribeMessage('OFFLINEPAY_AUDIT_REFUSE', ()=>{
					this.saveSubmit();
				})
				// #endif
				// #ifndef MP
				this.saveSubmit();
				// #endif
			},
			saveSubmit(){
				this.$api.sendRequest({
				    url: '/offlinepay/api/pay/pay',
					data:this.offlinepayInfo,
				    success: res => {
						this.repeat_flag = false
				        if (res.code >= 0) {
							uni.setStorageSync('offlinepay','offlinepay')
				            this.back()
				        }else{
							uni.showToast({
								title: res.message,
								icon: 'none'
							})
						}
				    }
				});
			},
		}
	}
</script>

<style lang="scss" scoped>
	.offlinepay{
		width: 100%;
		min-height: 100vh;
		box-sizing: border-box;
		background: #F6F6F6;
		padding-top: 20rpx;
		padding-left: 30rpx;
		padding-right: 30rpx;
		padding-bottom: calc(164rpx  + constant(safe-area-inset-bottom)) !important;
		padding-bottom: calc(164rpx  + env(safe-area-inset-bottom)) !important;
		.pay-info{
			width: 100%;
			padding: 40rpx 30rpx;
			background-color: #fff;
			border-radius: 16rpx;
			box-sizing: border-box;
			.title{
				font-size: 24rpx;
				color: #666;
				line-height: 34rpx;
				font-weight: 600;
				text-align: center;
			}
			.pay-price{
				width: 100%;
				display: flex;
				justify-content: center;
				align-items: baseline;
				color: #EF000C;
				margin-top:6rpx;
				.icon{
					line-height: 33rpx;
					font-weight: bold;
					font-size: 26rpx;
				}
				.price{
					line-height: 59rpx;
					font-weight: bold;
					font-size: 46rpx;
				}
			}
			.pay-time{
				display: flex;
				justify-content: center;
				align-items: center;
				margin-top: 14rpx;
				.text{
					line-height: 36rpx;
					font-weight: 400;
					font-size: 26rpx;
					color: #666666;
					margin-right: 11rpx;
				}
				.time{
					width: 34rpx;
					height: 34rpx;
					// padding: 0 3rpx;
					background: #F0F0F3;
					border-radius: 2rpx 2rpx 2rpx 2rpx;
					font-weight: 500;
					font-size: 26rpx;
					color: #333333;
					line-height: 34rpx;
					text-align: center;
				}
				.separator{
					font-weight: 500;
					font-size: 28rpx;
					margin: 0 10rpx;
					height: 34rpx;
					line-height: 28rpx;
				}
			}
		}
		.pay-type{
			margin-top: 40rpx;
			.top{
				width: 100%;
				display: flex;
				align-items: center;
				height: 80rpx;
				background: #F1F2F5;
				border-radius: 16rpx 16rpx 0rpx 0rpx;
				.item{
					width: 230rpx;
					height: 80rpx;
					position: relative;
					.center{
						width: 100%;
						height: 100%;
						position: absolute;
						left: 0;
						bottom: 0;
						z-index: 9;
						line-height: 80rpx;
						text-align: center;
						font-weight: 400;
						font-size: 32rpx;
						&.active{
							color: var(--base-color);
							font-weight: bold;
						}
					}
					.image{
						height: 104rpx;
						width: 267rpx;
						position: absolute;
						bottom: 0;
						z-index: 2;
						&.left{
							left: -2rpx;
						}
						&.center{
							width: 315rpx;
							left: 50%;
							transform: translateX(-50%);
						}
						&.right{
							right:-2rpx;
						}
					}
				}
			}
			.bank{
				padding: 30rpx;
				background: #fff;
				border-radius: 0 0 16rpx 16rpx;
				.item{
					display: flex;
					align-items: center;
					margin-top: 30rpx;
					&:first-of-type{
						margin-top: 0 !important;
					}
					.label{
						line-height: 36rpx;
						font-weight: 400;
						font-size: 26rpx;
						color: #666666;
					}
					.center{
						width: 500rpx;
						line-height: 36rpx;
						font-weight: 500;
						font-size: 26rpx;
						.copy{
							color: var(--base-color);
							margin-left: 20rpx;
						}
					}
				}
			}
			.code{
				padding: 50rpx 0;
				display: flex;
				justify-content: center;
				align-items: center;
				flex-direction: column;
				background: #fff;
				border-radius: 0 0 16rpx 16rpx;
				.centent{
					width: 360rpx;
					height: 360rpx;
					border-radius: 16rpx;
					border: 1rpx solid #DEDEDE;
					padding: 30rpx;
					box-sizing: border-box;
					.image{
						width: 300rpx;
						height: 300rpx;
					}
				}
				.bottom{
					height: 39rpx;
					line-height: 39rpx;
					font-weight: 500;
					font-size: 28rpx;
					margin-top: 30rpx;
				}
			}
			
		}
		.pay-form{
			margin-top: 20rpx;
			padding: 30rpx;
			border-radius: 16rpx;
			background-color: #fff;
			.title{
				line-height: 33rpx;
				font-weight: 500;
				font-size: 26rpx;
				color: #333333;
			}
			.image-list{
				display: flex;
				align-items: center;
				margin-top: 30rpx;
				.image-info-box {
					width: 110rpx;
					height: 110rpx;
					display: flex;
					flex-direction: column;
					justify-content: center;
					align-items: center;
					margin-left: 20rpx;
					position: relative;
					-ms-flex-negative: 0;
					-webkit-flex-shrink: 0;
					flex-shrink: 0;
					&:first-of-type{
						margin-left: 0 !important;
					}
					image {
						width: 100%;
						border-radius: $border-radius;
					}
					.iconfont {
						font-size: 60rpx;
						color: #898989;
						line-height: 1;
					}
					text {
						line-height: 1;
					}
					.imgDel {
						width: 40rpx;
						height: 40rpx;
						position: absolute;
						right: -20rpx;
						top: -20rpx;
						display: flex;
						justify-content: center;
						align-items: center;
						.iconfont {
							font-size: $font-size-toolbar;
						}
					}
				}
				.image-info-box.active {
					border: 1rpx dashed #898989;
				}
				.image-info-box.active:active {
					background: rgba($color: #cccccc, $alpha: 0.6);
				}
			}
			.desc{
				margin-top: 40rpx;
				border-top: 1rpx dashed #898989;
				padding-top: 20rpx;
				.input{
					width: 100%;
					font-weight: 400;
					font-size: 24rpx;
				}
			}
		}
		.pay-footer{
			position: fixed;
			display: flex;
			justify-content: space-between;
			align-items: center;
			left: 0;
			right:0;
			bottom: 0;
			z-index: 10;
			padding: 28rpx 30rpx;
			padding-bottom: calc(28rpx  + constant(safe-area-inset-bottom)) !important;
			padding-bottom: calc(28rpx  + env(safe-area-inset-bottom)) !important;
			background: #F6F6F6;
			.back{
				width: 220rpx;
				height: 88rpx;
				line-height: 82rpx;
				font-size: 32rpx;
				font-weight: 500;
				color: var(--base-color);
				background: #FFFFFF;
				border-radius: 50rpx 50rpx 50rpx 50rpx;
				border: 3rpx solid var(--base-color);
				box-sizing: border-box;
			}
			.save{
				width: 430rpx;
				height: 88rpx;
				font-size: 32rpx;
				font-weight: 500;
				color: #fff;
				background: var(--base-color);
				border-radius: 50rpx 50rpx 50rpx 50rpx
			}
		}
	}
</style>