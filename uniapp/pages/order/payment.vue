<template>
	<page-meta :page-style="themeColor"></page-meta>
	<view>
		<common-payment :api="api" create-data-key="orderCreateData" ref="payment"></common-payment>
	</view>
</template>

<script>
export default {
	data() {
		return {
			api: {
				payment: '/api/ordercreate/payment',
				calculate: '/api/ordercreate/calculate',
				create: '/api/ordercreate/create',
			}
		}
	},
	provide() {
	    return {
			promotion: this.promotion.bind(this)
	    }
	},
	onShow() {
		if (this.$refs.payment) this.$refs.payment.pageShow();
	},
	methods: {
		/**
		 * 处理活动信息 如不需要则定义为空方法
		 */
		promotion(data){
			if (data.promotion && data.promotion.manjian && data.promotion.manjian.length) {
				let promotionStr = {title: `满减送`, content: ''}
				data.promotion.manjian.forEach((mItem,mIndex)=>{
					let promotion = '';
					let content = {};
					let limit = mItem.type == 0 ? '元' : '件';
					if(mItem.rule){
						var item = mItem.discount_array.rule;
						item.limit = mItem.type == 0 ? parseFloat(item.limit).toFixed(2) : parseInt(item.limit);
						// 满减
						if (item.discount_money != undefined) {
							if (content.manjian == undefined) {
								content.manjian = '购买可享受满' + item.limit + limit + '减' + item.discount_money + '元';
							} else {
								content.manjian += '；满' + item.limit + limit + '减' + item.discount_money + '元';
							}
						}
						// 满送优惠券
						if (item.coupon && item.coupon_list) {
							let text = '';
							item.coupon_list.forEach((couponItem, couponIndex) => {
								if (couponItem.type == 'discount') {
									if (text == '') text = '送'+ couponItem.give_num +'张' + parseFloat(couponItem.discount) + '折优惠券';
									else text += '、送'+ couponItem.give_num +'张' + parseFloat(couponItem.discount) + '折优惠券';
								} else {
									if (text == '') text = '送'+ couponItem.give_num +'张' + parseFloat(couponItem.money) + '元优惠券';
									else text += '、送'+ couponItem.give_num +'张' + parseFloat(couponItem.money) + '元优惠券';
								}
							})
							if (content.mansong == undefined) {
								content.mansong = '购物满' + item.limit + limit + text;
							} else {
								content.mansong += '；' + '满' + item.limit + limit + text;
							}
						}
						// 满送积分
						if (item.point) {
							let point_text = '可得' + item.point + '积分';
							if(content.point_text == undefined) {
								content.point_text = '购物满' + item.limit + limit + point_text
							}else {
								content.point_text += '；' + '满' + item.limit + limit + point_text;
							}
						}
						// 包邮
						if (item.free_shipping != undefined) {
							if (content.free_shipping == undefined) {
								content.free_shipping = '购物满' + item.limit + limit + '包邮';
							}
						}
					}
					promotion = Object.values(content).join('\n');
					promotionStr.content = promotionStr.content + promotion + '\n';
				})
				return (promotionStr.content ? promotionStr : null);
			} 
		}
	}
};
</script>

<style scoped lang="scss">
/deep/ .uni-popup__wrapper.uni-custom .uni-popup__wrapper-box {
	background: none;
	max-height: unset !important;
	overflow-y: hidden !important;
}
/deep/ .uni-popup__wrapper {
	border-radius: 20rpx 20rpx 0 0;
}
/deep/ .uni-popup {
	z-index: 8;
}
</style>
