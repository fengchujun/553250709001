<template>
	<view class="goods-sku">
		<ns-login ref="login"></ns-login>
		<!-- sku选择 -->
		<ns-goods-sku v-if="goodsDetail.goods_id" ref="goodsSku" :goods-id="goodsDetail.goods_id" :goods-detail="goodsDetail" :max-buy="goodsDetail.max_buy" :min-buy="goodsDetail.min_buy" @refresh="refreshGoodsSkuDetail"></ns-goods-sku>
	</view>
</template>

<script>
	import nsGoodsSku from '@/components/ns-goods-sku/ns-goods-sku.vue';
	// 商品SKU
	export default {
		name: 'ns-goods-sku-index',
		components: {
			nsGoodsSku
		},
		data() {
			return {
				timeout: {},
				isRepeat: false,
				goodsDetail: {}
			};
		},
		created() {},
		methods: {
			/**
			 * 添加购物车
			 * @param {Object} config 购物车事件（detail-详情，cart-加入购物车）
			 * @param {Object} data 商品项
			 */
			addCart(config, data, event) {
				if (!this.storeToken) {
					this.$refs.login.open('/pages/index/index')
					return;
				}
				if (config == "detail" || data.is_virtual) {
					this.$util.redirectTo('/pages/goods/detail', {
						goods_id: data.goods_id
					});
					return false;
				}
				// 多规格
				if (data.goods_spec_format) {
					this.multiSpecificationGoods(data);
				} else {
					this.singleSpecificationGoods(data, event);
				}
			},
			/**
			 * 单规格
			 * @param {Object} data 商品项
			 */
			singleSpecificationGoods(data, event) {
				let cart = this.cartList['goods_' + data.goods_id] && this.cartList['goods_' + data.goods_id]['sku_' + data.sku_id] ? this.cartList['goods_' + data.goods_id]['sku_' + data.sku_id] : null;
				
				let cartNum = cart ? cart.num : 0;
				let api = cart && cart.cart_id ? '/api/cart/edit' : '/api/cart/add';
				let minBuy = data.min_buy > 0 ? data.min_buy : 1;
				let num = cartNum >= minBuy ? cartNum : minBuy;
				let _num = num;
				if(cart && cart.cart_id){
					_num = _num + (data.min_buy > 0 ? data.min_buy : 1)
				}
				
				let cart_id = cart ? cart.cart_id : 0;
				if (_num > parseInt(data.stock)) {
					this.$util.showToast({
						title: '商品库存不足'
					});
					return;
				}
				if (data.is_limit && data.max_buy && _num > parseInt(data.max_buy)) {
					this.$util.showToast({
						title: `该商品每人限购${data.max_buy}${data.unit || '件'}`
					});
					return;
				}

				if (cart) {
					this.cartList['goods_' + data.goods_id]['sku_' + data.sku_id].num = _num;
				} else {

					// 如果商品第一次添加，则初始化数据
					if (!this.cartList['goods_' + data.goods_id]) {
						this.cartList['goods_' + data.goods_id] = {};
					}

					let discount_price = data.discount_price;
					if (data.member_price > 0 && Number(data.member_price) <= Number(data.discount_price)) {
						discount_price = data.member_price;
					}

					this.cartList['goods_' + data.goods_id]['sku_' + data.sku_id] = {
						cart_id,
						goods_id: data.goods_id,
						sku_id: data.sku_id,
						num: _num,
						discount_price
					};
				}

				if (this.isRepeat) return;
				this.isRepeat = true;

				this.$emit('addCart', event.currentTarget.id);

				this.$api.sendRequest({
					url: api,
					data: {
						cart_id,
						sku_id: data.sku_id,
						num: _num
					},
					success: res => {
						this.isRepeat = false;
						if (res.code == 0) {
							if (cart_id == 0) {
								this.cartList['goods_' + data.goods_id]['sku_' + data.sku_id].cart_id = res.data;
							}
							this.$util.showToast({
								title: "商品添加购物车成功"
							});
							this.$store.commit('setCartChange');
							this.$store.dispatch('cartCalculate');
							this.$emit("cartListChange", this.cartList);
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			},
			/**
			 * 多规格
			 * @param {Object} data 商品项
			 */
			multiSpecificationGoods(data) {
				this.$api.sendRequest({
					url: '/api/goodssku/getInfoForCategory',
					data: {
						sku_id: data.sku_id
					},
					success: res => {
						if (res.code >= 0) {
							let item = res.data;
							item.unit = item.unit || '件';

							if (item.sku_images) item.sku_images = item.sku_images.split(',');
							else item.sku_images = [];

							// 多规格时合并主图
							if (item.goods_spec_format && item.goods_image) {
								item.goods_image = item.goods_image.split(',');
								item.sku_images = item.goods_image.concat(item.sku_images);
							}

							// 当前商品SKU规格
							if (item.sku_spec_format) item.sku_spec_format = JSON.parse(item.sku_spec_format);

							// 商品SKU格式
							if (item.goods_spec_format) item.goods_spec_format = JSON.parse(item.goods_spec_format);

							// 限时折扣
							if (item.promotion_type == 1) {
								item.discountTimeMachine = this.$util.countDown(item.end_time - res.timestamp);
							}

							if (item.promotion_type == 1 && item.discountTimeMachine) {
								if (item.member_price > 0 && Number(item.member_price) <= Number(item.discount_price)) {
									item.show_price = item.member_price;
								} else {
									item.show_price = item.discount_price;
								}
							} else if (item.member_price > 0) {
								item.show_price = item.member_price;
							} else {
								item.show_price = item.price;
							}
							this.goodsDetail = item;

							this.$nextTick(() => {
								if (this.$refs.goodsSku) {
									this.$refs.goodsSku.show("join_cart", (res) => {

										let goods = this.cartList['goods_' + res.goods_id];
										let cart = null;
										if (goods && goods['sku_' + res.sku_id]) {
											cart = goods['sku_' + res.sku_id];
										}

										if (cart) {
											this.cartList['goods_' + res.goods_id]['sku_' + res.sku_id].num = res.num;
										} else {

											// 如果商品第一次添加，则初始化数据
											if (!this.cartList['goods_' + res.goods_id]) {
												this.cartList['goods_' + res.goods_id] = {};
											}

											this.cartList['goods_' + res.goods_id]['sku_' + res.sku_id] = {
												cart_id: res.cart_id,
												goods_id: res.goods_id,
												sku_id: res.sku_id,
												num: res.num,
												discount_price: res.discount_price
											};

										}

										this.$store.dispatch('cartCalculate');
										this.$emit("cartListChange", this.cartList);

										// 加入购物车动效
										setTimeout(() => {
											this.$store.commit('setCartChange');
										}, 100);

									});
								}
							});
						}
					}
				});
			},
			refreshGoodsSkuDetail(data) {
				this.goodsDetail = Object.assign({}, this.goodsDetail, data);
			}
		}
	};
</script>