export default {
	methods: {
		/**
		 * 删除订单
		 * @param {Object} orderData
		 */
		orderDelete(order_id, callback) {
			uni.showModal({
				title: '提示',
				content: '您确定要删除该订单吗？',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/api/order/delete',
							data: {
								order_id
							},
							success: res => {
								if (res.code >= 0) {
									this.$util.showToast({title:'删除订单成功'})
									typeof callback == 'function' && callback();
								} else {
									this.$util.showToast({
										title: '删除订单失败，' + res.message,
										duration: 2000
									})
								}
							}
						})
					}
				}
			})
		},
		/**
		 * 订单支付
		 * @param {Object} orderData
		 */
		orderPay(orderData) {
			if (orderData.adjust_money == 0) {
				this.pay();
			} else {
				uni.showModal({
					title: '提示',
					content: '商家已将支付金额调整为' + orderData.pay_money + '元，是否继续支付？',
					success: res => {
						if (res.confirm) {
							this.pay();
						}
					}
				})
			}
		},
		pay() {
			this.$api.sendRequest({
				url: '/api/order/pay',
				data: {
					order_ids: this.orderData.order_id
				},
				success: res => {
					if (res.code >= 0) {
						this.$refs.choosePaymentPopup.getPayInfo(res.data);
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			})
		},
		/**
		 * 关闭订单
		 * @param order_id
		 * @param callback
		 */
		orderClose(order_id, callback) {
			uni.showModal({
				title: '提示',
				content: '您确定要关闭该订单吗？',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/api/order/close',
							data: {
								order_id
							},
							success: res => {
								if (res.code >= 0) {
									typeof callback == 'function' && callback();
								} else {
									this.$util.showToast({
										title: '关闭失败，' + res.message,
										duration: 2000
									})
								}
							}
						})
					}
				}
			})
		},
		/**
		 * 订单收货
		 * @param orderData
		 * @param callback
		 */
		orderDelivery(orderData, callback) {
			// 如果不在微信小程序中
			// #ifndef MP-WEIXIN
			uni.showModal({
				title: '提示',
				content: '您确定已经收到货物了吗？',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/api/order/takedelivery',
							data: {
								order_id: orderData.order_id
							},
							success: res => {
								this.$util.showToast({
									title: res.message
								})
								typeof callback == 'function' && callback();
							}
						})
					}
				}
			})
			// #endif

			// #ifdef MP-WEIXIN
			// 检测微信小程序是否已开通发货信息管理服务
			if (orderData.pay_type == 'wechatpay' && wx.openBusinessView && orderData.is_trade_managed) {
				wx.openBusinessView({
					businessType: 'weappOrderConfirm',
					extraData: {
						merchant_id: orderData.pay_config.mch_id,
						merchant_trade_no: orderData.out_trade_no
					},
					success: res => {
						if (res.extraData.status == 'success') {
							this.$api.sendRequest({
								url: '/api/order/takedelivery',
								data: {
									order_id: orderData.order_id
								},
								success: res => {
									this.$util.showToast({
										title: res.message
									})
									typeof callback == 'function' && callback();
								}
							})
						} else {
							this.$api.sendRequest({
								url: '/api/order/takedelivery',
								data: {
									order_id: orderData.order_id
								},
								success: res => {
									this.$util.showToast({
										title: res.message
									})
									typeof callback == 'function' && callback();
								}
							})
						}
					},
					fail: function(res) {
						console.log('fail', res);
					}
				})
			} else {
				uni.showModal({
					title: '提示',
					content: '您确定已经收到货物了吗？',
					success: res => {
						if (res.confirm) {
							this.$api.sendRequest({
								url: '/api/order/takedelivery',
								data: {
									order_id: orderData.order_id
								},
								success: res => {
									this.$util.showToast({
										title: res.message
									})
									typeof callback == 'function' && callback();
								}
							})
						}
					}
				})
			}
			// #endif
		},
		/**
		 * 订单虚拟商品收货
		 * @param orderData
		 * @param callback
		 */
		orderVirtualDelivery(orderData, callback) {
			// 如果不在微信小程序中
			// #ifndef MP-WEIXIN
			uni.showModal({
				title: '提示',
				content: '您确定要进行收货吗？',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/api/order/membervirtualtakedelivery',
							data: {
								order_id: orderData.order_id
							},
							success: res => {
								this.$util.showToast({
									title: res.message
								})
								typeof callback == 'function' && callback();
							}
						})
					}
				},
			})
			// #endif

			// #ifdef MP-WEIXIN
			// 检测微信小程序是否已开通发货信息管理服务
			if (orderData.pay_type == 'wechatpay' && wx.openBusinessView && orderData.is_trade_managed) {
				wx.openBusinessView({
					businessType: 'weappOrderConfirm',
					extraData: {
						merchant_id: orderData.pay_config.mch_id,
						merchant_trade_no: orderData.out_trade_no
					},
					success: res => {
						if (res.extraData.status == 'success') {
							this.$api.sendRequest({
								url: '/api/order/membervirtualtakedelivery',
								data: {
									order_id: orderData.order_id
								},
								success: res => {
									this.$util.showToast({
										title: res.message
									})
									typeof callback == 'function' && callback();
								}
							})
						} else {
							this.$api.sendRequest({
								url: '/api/order/membervirtualtakedelivery',
								data: {
									order_id: orderData.order_id
								},
								success: res => {
									this.$util.showToast({
										title: res.message
									})
									typeof callback == 'function' && callback();
								}
							})
						}
					},
					fail: function(res) {}
				})
			} else {
				uni.showModal({
					title: '提示',
					content: '您确定要进行收货吗？',
					success: res => {
						if (res.confirm) {
							this.$api.sendRequest({
								url: '/api/order/membervirtualtakedelivery',
								data: {
									order_id: orderData.order_id
								},
								success: res => {
									this.$util.showToast({
										title: res.message
									})
									typeof callback == 'function' && callback();
								}
							})
						}
					},
				})
			}
			// #endif
		},
	}
}