<template>
	<view :style="value.pageStyle" v-if="loading || (list && list.length)">
		<x-skeleton :type="skeletonType" :loading="loading" :configs="skeletonConfig">
			<view :class="['goods-list', goodsValue.template, goodsValue.style]" :style="goodsListWarpCss">
				<template v-if="goodsValue.template != 'horizontal-slide'">
					<view class="goods-item" v-for="(item, index) in list" :key="index" @click="toDetail(item)" :class="[goodsValue.ornament.type]" :style="goodsItemCss">
						<view class="goods-img-wrap">
							<image class="goods-img" :src="$util.img(item.goods_image, { size: goodsValue.template == 'large-mode' ? 'big' : 'mid' })" mode="widthFix" @error="imgError(index)" :style="{ borderRadius: goodsValue.imgAroundRadius * 2 + 'rpx' }"/>
							<view class="sell-out" v-if="item.goods_stock <= 0">
								<text class="iconfont icon-shuqing"></text>
							</view>
						</view>
						<view class="info-wrap" v-if="
								goodsValue.goodsNameStyle.control ||
									(goodsValue.tag && goodsValue.tag.value != 'hidden') ||
									goodsValue.priceStyle.mainControl ||
									goodsValue.priceStyle.lineControl ||
									goodsValue.btnStyle.control
							">
							<view v-if="goodsValue.goodsNameStyle.control" class="goods-name"
								:style="{ color: goodsValue.theme == 'diy' ? goodsValue.goodsNameStyle.color : '', fontWeight: goodsValue.goodsNameStyle.fontWeight ? 'bold' : '' }"
								:class="[{ 'using-hidden': goodsValue.nameLineMode == 'single' }, { 'multi-hidden': goodsValue.nameLineMode == 'multiple' }]">
								{{ item.goods_name }}
							</view>
							<template v-if="goodsValue.tag">
								<view class="tag-wrap" v-if="goodsValue.tag.value == 'label' && item.label_name">
									<text class="hollow-tag">{{ item.label_name }}</text>
								</view>
								<view class="tag-wrap" v-else-if="goodsValue.tag.value == 'diy'">
									<text class="hollow-tag">{{ goodsValue.tag.text }}</text>
								</view>
							</template>
							<view class="pro-info">
								<view class="discount-price">
									<view class="price-wrap" v-if="goodsValue.priceStyle.mainControl">
										<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">￥</text>
										<text class="price price-style large" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">{{ showPrice(item).split('.')[0] }}</text>
										<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">{{ '.' + showPrice(item).split('.')[1] }}</text>
									</view>
									<view class="member-price" v-if="(item.member_price && item.member_price == showPrice(item)) || item.promotion_type == 1">
										<image v-if="item.member_price && item.member_price == showPrice(item)" :src="$util.img('public/uniapp/index/VIP.png')"/>
										<image v-else-if="item.promotion_type == 1" :src="$util.img('public/uniapp/index/discount.png')"/>
									</view>
									<view v-if="goodsValue.priceStyle.lineControl && showMarketPrice(item)"
										class="delete-price price-font"
										:style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.lineColor : '' }">
										￥{{ showMarketPrice(item) }}
									</view>
									<view class="sale" v-if="goodsValue.saleStyle.control" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.saleStyle.color : '' }">
										已售{{ item.sale_num }}{{ item.unit ? item.unit : '件' }}
									</view>
								</view>

								<view class="cart-action-wrap" v-if="goodsValue.btnStyle.control">
									<!-- <text class="cart-num" v-if="cartList['goods_' + item.goods_id] && goodsValue.btnStyle.style != 'button'">{{ cartList['goods_' + item.goods_id].num }}</text> -->
									<!-- 购物车图标 -->
									<view v-if="goodsValue.btnStyle.style == 'icon-cart'" :style="{
											color: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.textColor : '',
											borderColor: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.textColor : ''
										}" class="cart shopping-cart-btn iconfont icon-gouwuche click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(goodsValue.btnStyle.cartEvent, item, $event)">
										<view class="click-event"></view>
									</view>

									<!--加号图标 -->
									<view v-else-if="goodsValue.btnStyle.style == 'icon-add'" :style="{
											color: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.textColor : '',
											borderColor: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.textColor : ''
										}" class="cart plus-sign-btn iconfont icon-add1 click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(goodsValue.btnStyle.cartEvent, item, $event)">
										<view class="click-event"></view>
									</view>

									<!-- 按钮 -->
									<view v-else-if="goodsValue.btnStyle.style == 'button'" :style="{
											backgroundColor: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.bgColor : '',
											color: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.textColor : '',
											fontWeight: goodsValue.btnStyle.theme == 'diy' ? (goodsValue.btnStyle.fontWeight ? 'bold' : 'normal') : '',
											padding: goodsValue.btnStyle.theme == 'diy' ? '0 ' + goodsValue.btnStyle.padding * 2 + 'rpx' : ''
										}" class="cart buy-btn click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(goodsValue.btnStyle.cartEvent, item, $event)">
										{{ goodsValue.btnStyle.text }}
										<view class="click-event"></view>
										<!-- <text class="cart-num" v-if="cartList['goods_' + item.goods_id]">{{ cartList['goods_' + item.goods_id].num }}</text> -->
									</view>

									<!--自定义图标 -->
									<view v-else-if="goodsValue.btnStyle.style == 'icon-diy'" :style="{
											color: goodsValue.btnStyle.theme == 'diy' ? goodsValue.btnStyle.textColor : ''
										}" class="icon-diy click-wrap" :id="'goods-' + item.id"
										@click.stop="$refs.goodsSkuIndex.addCart(goodsValue.btnStyle.cartEvent, item, $event)">
										<view class="click-event"></view>
										<diy-icon :icon="goodsValue.btnStyle.iconDiy.icon"
											:value="goodsValue.btnStyle.iconDiy.style ? goodsValue.btnStyle.iconDiy.style : null"></diy-icon>
									</view>
								</view>
							</view>
						</view>
					</view>
				</template>
				<scroll-view v-if="goodsValue.template == 'horizontal-slide' && goodsValue.slideMode == 'scroll'" class="scroll" :scroll-x="true" @scroll="scrollX">			
					<view :id="'scrollX-'+id">
						<view class="goods-item" v-for="(item, index) in list" :key="index" @click="toDetail(item)" :class="[goodsValue.ornament.type]" :style="goodsItemCss">
							<view class="goods-img-wrap">
								<image class="goods-img" :style="{ borderRadius: value.imgAroundRadius * 2 + 'rpx' }" :src="$util.img(item.goods_image, { size: 'mid' })" mode="widthFix" @error="imgError(index)" :lazy-load="true"/>
								<view class="sell-out" v-if="item.goods_stock <= 0">
									<text class="iconfont icon-shuqing"></text>
								</view>
							</view>
							<view :class="['info-wrap', { 'multi-content': value.nameLineMode == 'multiple' }]" v-if="
									goodsValue.goodsNameStyle.control ||
										(goodsValue.tag && goodsValue.tag.value != 'hidden') ||
										goodsValue.priceStyle.mainControl ||
										goodsValue.priceStyle.lineControl
								">
								<view v-if="goodsValue.goodsNameStyle.control" class="goods-name"
									:style="{ color: goodsValue.theme == 'diy' ? goodsValue.goodsNameStyle.color : '', fontWeight: goodsValue.goodsNameStyle.fontWeight ? 'bold' : '' }"
									:class="[{ 'using-hidden': goodsValue.nameLineMode == 'single' }, { 'multi-hidden': goodsValue.nameLineMode == 'multiple' }]">
									{{ item.goods_name }}
								</view>
								<template v-if="goodsValue.tag">
									<view class="tag-wrap" v-if="goodsValue.tag.value == 'label' && item.label_name">
										<text class="hollow-tag">{{ item.label_name }}</text>
									</view>
									<view class="tag-wrap" v-else-if="goodsValue.tag.value == 'diy'">
										<text class="hollow-tag">{{ goodsValue.tag.text }}</text>
									</view>
								</template>
								<view class="pro-info">
									<view class="discount-price">
										<view class="price-wrap" v-if="goodsValue.priceStyle.mainControl">
											<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">￥</text>
											<text class="price price-style large" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">{{ showPrice(item).split('.')[0] }}</text>
											<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">{{ '.' + showPrice(item).split('.')[1] }}</text>
										</view>
										<view class="member-price" v-if="(item.member_price && item.member_price == showPrice(item)) || item.promotion_type == 1">
											<image v-if="item.member_price && item.member_price == showPrice(item)" :src="$util.img('public/uniapp/index/VIP.png')"/>
											<image v-else-if="item.promotion_type == 1" :src="$util.img('public/uniapp/index/discount.png')"/>
										</view>
										<view v-if="goodsValue.priceStyle.lineControl && showMarketPrice(item)" class="delete-price price-font" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.lineColor : '' }">
											￥{{ showMarketPrice(item) }}
										</view>
										<view class="sale" v-if="goodsValue.saleStyle.control" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.saleStyle.color : '' }">
											已售{{ item.sale_num }}{{ item.unit ? item.unit : '件' }}
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
				<swiper v-if="goodsValue.template == 'horizontal-slide' && goodsValue.slideMode == 'slide'" :autoplay="false" class="swiper" :style="{ height: swiperHeight }" @change="swiperChange">
					<swiper-item v-for="(pageItem, pageIndex) in list" :key="pageIndex" :class="['swiper-item',  (list.length && [list[pageIndex].length / 3] >= 1) && 'flex-between']">
						<view class="goods-item" v-for="(dataItem, dataIndex) in list[pageIndex]" :key="dataIndex" @click="toDetail(dataItem)" :class="[goodsValue.ornament.type]" :style="goodsItemCss">
							<view class="goods-img-wrap">
								<image class="goods-img" :style="{ borderRadius: value.imgAroundRadius * 2 + 'rpx' }" :src="$util.img(dataItem.goods_image, { size: 'mid' })" mode="widthFix" @error="imgError(dataIndex)" :lazy-load="true"/>
								<view class="sell-out" v-if="dataItem.goods_stock <= 0">
									<text class="iconfont icon-shuqing"></text>
								</view>
							</view>
							<view :class="['info-wrap', { 'multi-content': value.nameLineMode == 'multiple' }]" v-if="
									goodsValue.goodsNameStyle.control ||
										(goodsValue.tag && goodsValue.tag.value != 'hidden') ||
										goodsValue.priceStyle.mainControl ||
										goodsValue.priceStyle.lineControl
								">
								<view v-if="goodsValue.goodsNameStyle.control" class="goods-name"
									:style="{ color: goodsValue.theme == 'diy' ? goodsValue.goodsNameStyle.color : '', fontWeight: goodsValue.goodsNameStyle.fontWeight ? 'bold' : '' }"
									:class="[{ 'using-hidden': goodsValue.nameLineMode == 'single' }, { 'multi-hidden': goodsValue.nameLineMode == 'multiple' }]">
									{{ dataItem.goods_name }}
								</view>
								<template v-if="goodsValue.tag">
									<view class="tag-wrap" v-if="goodsValue.tag.value == 'label' && dataItem.label_name">
										<text class="hollow-tag">{{ dataItem.label_name }}</text>
									</view>
									<view class="tag-wrap" v-else-if="goodsValue.tag.value == 'diy'">
										<text class="hollow-tag">{{ goodsValue.tag.text }}</text>
									</view>
								</template>
								<view class="pro-info">
									<view class="discount-price">
										<view class="price-wrap" v-if="goodsValue.priceStyle.mainControl">
											<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">￥</text>
											<text class="price price-style large" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">
												{{ showPrice(dataItem).split('.')[0] }}
											</text>
											<text class="unit price-style small" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.mainColor + '!important' : '' }">
												{{ '.' + showPrice(dataItem).split('.')[1] }}
											</text>
										</view>
										<view class="member-price" v-if="(dataItem.member_price && dataItem.member_price == showPrice(dataItem)) || dataItem.promotion_type == 1">
											<image v-if="dataItem.member_price && dataItem.member_price == showPrice(dataItem)" :src="$util.img('public/uniapp/index/VIP.png')"/>
											<image v-else-if="dataItem.promotion_type == 1" :src="$util.img('public/uniapp/index/discount.png')"/>
										</view>
										<view v-if="goodsValue.priceStyle.lineControl && showMarketPrice(dataItem)" class="delete-price" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.priceStyle.lineColor : '' }">
											￥{{ showMarketPrice(dataItem) }}
										</view>
										<view class="sale" v-if="goodsValue.saleStyle.control" :style="{ color: goodsValue.theme == 'diy' ? goodsValue.saleStyle.color : '' }">
											已售{{ dataItem.sale_num }}{{ dataItem.unit ? dataItem.unit : '件' }}
										</view>
									</view>
								</view>
							</view>
						</view>
					</swiper-item>
				</swiper>
			</view>
			<ns-goods-sku-index ref="goodsSkuIndex" @addCart="addCartPoint"></ns-goods-sku-index>
			<view :id="'goods-list-'+id"></view>
			<view class="cart-point" :style="{ left: item.left + 'px', top: item.top + 'px' }" :key="index" v-for="(item, index) in carIconList"></view>
		</x-skeleton>
	</view>
</template>

<script>
	import nsGoodsSkuIndex from '@/components/ns-goods-sku/ns-goods-sku-index.vue';
	export default {
		name: 'diy-goods-list',
		components: {
			nsGoodsSkuIndex
		},
		props: {
			value: {
				type: Object,
				default: () => {
					return {};
				}
			},
			index:{
				type: Number,
				default: 0
			},
			scrollTop:{
				type: Number,
				default: 0
			},
			refresh:{
				type: Boolean,
				default:false
			}
		},
		data() {
			return {
				loading: true,
				skeletonType: '',
				skeletonConfig: {},
				list: [],
				goodsValue: {},
				page: 1,
				params:{
					page:1,
					page_size:12,
					num:0,
					repeat_flag:false,
					end:false
				},
				id:0,
				carIconList: {},
				cartAnimation: {},
				isRefresh:false
			};
		},
		created() {
			this.goodsValue = this.value;
			this.id = this.$util.generateUUID()
			this.initSkeleton();
			// this.init();
			this.isRefresh = this.refresh;
		},
		watch: {
			isRefresh:function(nval,oval){
				this.init();
			},
			'globalStoreInfo.store_id': {
				handler(nval, oval) {
					if (nval != oval) {
						this.init();
					}
				},
				deep: true
			},
			//y轴无限滚动
			scrollTop:function(val){
				if(this.params.repeat_flag||this.params.end) return
				const { windowHeight } = uni.getSystemInfoSync(); // 获取页面高度
				if(Object.keys(this.goodsValue).length&&this.goodsValue.template != 'horizontal-slide') {
					
					const query = uni.createSelectorQuery().in(this);
					query.select('#goods-list-' + this.id).boundingClientRect(data => {
						if(data && data.top <= windowHeight){
							if(this.params.repeat_flag||this.params.end) return
							this.params.page+=1
							this.getGoodsList()
						}
						
					}).exec()
				}
			},
			// 组件刷新监听
			componentRefresh: function(nval) {
				this.goodsValue = this.value;
				this.initSkeleton();
				this.init();
			}
		},
		computed: {
			goodsListWarpCss() {
				var obj = '';
				obj += 'background-color:' + this.goodsValue.componentBgColor + ';';
				if (this.goodsValue.componentAngle == 'round') {
					obj += 'border-top-left-radius:' + this.goodsValue.topAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.goodsValue.topAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.goodsValue.bottomAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.goodsValue.bottomAroundRadius * 2 + 'rpx;';
				}
				return obj;
			},
			// 商品项样式
			goodsItemCss() {
				var obj = '';
				obj += 'background-color:' + this.goodsValue.elementBgColor + ';';
				if (this.goodsValue.elementAngle == 'round') {
					obj += 'border-top-left-radius:' + this.goodsValue.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-top-right-radius:' + this.goodsValue.topElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-left-radius:' + this.goodsValue.bottomElementAroundRadius * 2 + 'rpx;';
					obj += 'border-bottom-right-radius:' + this.goodsValue.bottomElementAroundRadius * 2 + 'rpx;';
				}
				if (this.goodsValue.ornament.type == 'shadow') {
					obj += 'box-shadow:' + '0 0 10rpx ' + this.goodsValue.ornament.color + ';';
				}
				if (this.goodsValue.ornament.type == 'stroke') {
					obj += 'border:' + '2rpx solid ' + this.goodsValue.ornament.color + ';';
				}
				const screenWidth = uni.getSystemInfoSync().windowWidth;
				if (this.value.template == 'horizontal-slide') {
					var width = '';
					if (this.value.slideMode == 'scroll' && this.value.goodsMarginType == 'diy') width = this.rpxUpPx(this.value.goodsMarginNum * 2);
					else width = [screenWidth - this.rpxUpPx(20) * 2 - this.rpxUpPx(200) * 3 - this.rpxUpPx(this.value.margin.both * 2) * 2] / 6;
					obj += 'margin-left:' + width + 'px;';
					obj += 'margin-right:' + width + 'px;';
				}
				return obj;
			},
			swiperHeight() {
				if (this.value.nameLineMode == 'multiple') {
					if (this.value.ornament.type == 'shadow') return '414rpx';
					else return '402rpx';
				}
				if (this.value.ornament.type == 'shadow') return '370rpx';
				else return '358rpx';
			},
			cartPosition() {
				return this.$store.state.cartPosition;
			}
		},
		
		methods: {
			//x轴滚动
			scrollX(e){
				let left = e.detail.scrollLeft
				if(this.params.repeat_flag||this.params.end) return
				const query = uni.createSelectorQuery().in(this);
				query.select('#scrollX-' + this.id).boundingClientRect(data => {
					let max_left = data.width*(this.list.length/3-1)-this.rpxToPx(298)
					if(left >=max_left){
						if(this.params.repeat_flag||this.params.end) return
						this.params.page+=1
						this.getGoodsList()
					}
					
				}).exec()
			},
			//x轴swiper
			swiperChange(e){
				let index = e.detail.current
				if(this.params.repeat_flag||this.params.end) return
				if(index==this.list.length-2){
					this.$nextTick(()=>{
						this.params.page+=1
						this.getGoodsList()
					})
				}
			},
			rpxToPx(rpx){
				const screenWidth = uni.getSystemInfoSync().screenWidth
				return(screenWidth*Number.parseInt(rpx))/750
			},
			initSkeleton() {
				if (this.goodsValue.template == 'row1-of1') {

					// 单列 风格
					this.skeletonType = 'list';
					this.skeletonConfig = {};

				} else if (this.goodsValue.template == 'row1-of2') {

					// 两列 风格
					this.skeletonType = 'waterfall';
					this.skeletonConfig = {
						headHeight: '320rpx',
						textRows: 2,
						textWidth: ['100%', '80%']
					};

				} else if (this.goodsValue.template == 'row1-of3') {

					// 三列 风格
					this.skeletonType = 'waterfall';
					this.skeletonConfig = {
						gridColumns: 3,
						headHeight: '200rpx',
						textRows: 2,
						textWidth: ['100%', '80%']
					};

				} else if (this.goodsValue.template == 'horizontal-slide') {

					// 横向滑动 风格
					this.skeletonType = 'waterfall';
					this.skeletonConfig = {
						gridRows: 1,
						gridColumns: 3,
						headHeight: '200rpx',
						textRows: 2,
						textWidth: ['100%', '80%']
					};

				} else if (this.goodsValue.template == 'large-mode') {

					// 大图 风格
					this.skeletonType = 'list';
					this.skeletonConfig = {
						itemDirection: 'column',
						headWidth: '100%',
						headHeight: '320rpx',
						textRows: 2,
						textWidth: ['100%', '80%']
					};

				}
			},
			rpxUpPx(res) {
				const screenWidth = uni.getSystemInfoSync().windowWidth;
				var data = (screenWidth * parseInt(res)) / 750;
				return Math.floor(data);
			},
			init(){
				this.params={
					page:1,
					page_size:12,
					num:0,
					repeat_flag:false,
					end:false
				}
				this.getGoodsList()
			},
			getGoodsList() {
				this.params.repeat_flag = true
				this.params.num = this.goodsValue.count
				if (this.goodsValue.sources == 'category') {
					this.params.category_id = this.goodsValue.categoryId;
					this.params.category_level = 1;
				} else if (this.goodsValue.sources == 'diy') {
					this.params.num = 0;
					this.params.goods_id_arr = this.goodsValue.goodsId.toString();
				}
				this.params.order = this.goodsValue.sortWay;
				this.$api.sendRequest({
					url: '/api/goodssku/pageComponents',
					data: this.params,
					success: res => {
						if (res.code == 0 && res.data) {
							let data = res.data.list;
							let list = data.map(item => {
								item.id = this.genNonDuplicate();
								return item;
							});
							if(this.params.page==1)this.list = []
							// 切屏滚动，每页显示固定数量
							if (this.goodsValue.template == 'horizontal-slide' && this.goodsValue.slideMode == 'slide') {
								let size = 3;
								if(this.params.num){
									let remain_num = this.params.num-this.list.length*size
									if(remain_num>0){
										list = list.splice(0,remain_num)
									}else{
										list = []
									}
								}
								
								
								let temp = [];
								this.page = Math.ceil(list.length / size);
								for (var i = 0; i < this.page; i++) {
									temp[i] = [];
									for (var j = i * size; j < list.length; j++) {
										if (temp[i].length == size) break;
										temp[i].push(list[j]);
									}
								}
								this.list = this.list.concat(temp);
							}else{
								if(this.params.num){
									let remain_num = this.params.num-this.list.length
									if(remain_num>0){
										list = list.splice(0,remain_num)
									}else{
										list = []
									}
									
								}
								this.list = this.list.concat(list);
								
							}
							if(list.length<this.params.page_size) this.params.end = true
						}
						this.params.repeat_flag = false
						this.loading = false;
						if(this.goodsValue.template != 'horizontal-slide'&&this.params.page==1){
							this.$nextTick(()=>{
								const { windowHeight } = uni.getSystemInfoSync(); // 获取页面高度
								const query = uni.createSelectorQuery().in(this);
								query.select('#goods-list-' + this.id).boundingClientRect(data => {
									if(data&&data.top<=windowHeight){
										this.params.page+=1
										this.getGoodsList()
									}
									
								}).exec()
							})
						}
					}
				});
			},
			toDetail(item) {
				this.$util.redirectTo('/pages/goods/detail', {
					goods_id: item.goods_id
				});
			},
			imgError(index) {
				if (this.list[index]) this.list[index].goods_image = this.$util.getDefaultImage().goods;
			},
			showPrice(data) {
				let price = data.discount_price;
				if (data.member_price && parseFloat(data.member_price) < parseFloat(price)) price = data.member_price;
				return price;
			},
			showMarketPrice(item) {
				let price = this.showPrice(item);
				if (item.market_price > 0) {
					return item.market_price;
				} else if (parseFloat(item.price) > parseFloat(price)) {
					return item.price;
				}
				return '';
			},
			/**
			 * 添加点
			 * @param {Object} id
			 */
			addCartPoint(id) {
				if (!this.cartPosition) return;

				const query = uni.createSelectorQuery().in(this);
				query.select('#' + id + ' .click-event').boundingClientRect(data => {
					if (data) {
						let left = data.left;
						let top = data.top;

						if (left < this.cartPosition.left) {
							var bezierPos = [{
								x: left,
								y: top
							}, {
								x: left + 50,
								y: top - 150
							}, {
								x: this.cartPosition.left,
								y: this.cartPosition.top
							}];
						} else {
							var bezierPos = [{
								x: left,
								y: top
							}, {
								x: left - 50,
								y: top - 150
							}, {
								x: this.cartPosition.left,
								y: this.cartPosition.top
							}];
						}

						let key = new Date().getTime();
						this.$set(this.carIconList, key, {
							left: left,
							top: top,
							index: 0,
							bezierPos: this.$util.bezier(bezierPos, 6).bezier_points,
							timer: null
						});
						this.startAnimation(key);
					}
				}).exec();
			},
			/**
			 * 执行动画
			 * @param {Object} key
			 */
			startAnimation(key) {
				let bezierPos = this.carIconList[key].bezierPos,
					index = this.carIconList[key].index;

				this.carIconList[key].timer = setInterval(() => {
					if (index < 6) {
						this.carIconList[key].left = bezierPos[index].x;
						this.carIconList[key].top = bezierPos[index].y;
						index++;
					} else {
						clearInterval(this.carIconList[key].timer);
						delete this.carIconList[key];
						this.$forceUpdate();

						// 购物车图标
						let animation = uni.createAnimation({
							duration: 200,
							timingFunction: 'ease'
						});
						animation.scale(1.2).step();
						this.cartAnimation = animation.export();
						setTimeout(() => {
							animation.scale(1).step();
							this.cartAnimation = animation.export();
						}, 300);
					}
				}, 50);
			},
			genNonDuplicate(len = 6) {
				return Number(Math.random().toString().substr(3, len) + Date.now()).toString(36);
			}
		}
	};
</script>

<style lang="scss" scoped>
	.goods-list {
		overflow: hidden;
		padding-bottom: 10rpx;

		.goods-item {
			position: relative;
			overflow: hidden;
			line-height: 1;

			.sale {
				align-self: baseline;
				color: $color-tip;
				font-size: $font-size-activity-tag;
			}

			.info-wrap {
				.goods-name {
					margin-bottom: 10rpx;
					line-height: 1.3;
				}

				.tag-wrap {
					margin-bottom: 20rpx;

					text {
						display: inline-block;
						font-size: 18rpx;
					}

					.hollow-tag {
						border: 2rpx solid $base-color;
						border-radius: 4rpx;
						margin-right: 10rpx;
						box-sizing: border-box;
						line-height: 1.2;
						padding: 2rpx 4rpx 0;
						max-width: 100%;
						color: $base-color;
					}
				}

				.member-price {
					display: flex;
					align-items: center;

					image {
						margin-left: 4rpx;
						width: 50rpx;
						height: 24rpx;
					}
				}
			}

			.shopping-cart-btn {
				font-size: 36rpx;
				border: 2rpx solid $base-color;
				border-radius: 50%;
				padding: 10rpx;
				color: $base-color;
				width: 36rpx;
				height: 36rpx;
				text-align: center;
				line-height: 36rpx;
			}

			.plus-sign-btn {
				font-size: 36rpx;
				border: 2rpx solid $base-color;
				border-radius: 50%;
				padding: 10rpx;
				color: $base-color;
				width: 36rpx;
				height: 36rpx;
				text-align: center;
				line-height: 36rpx;
			}

			.buy-btn {
				background-color: $base-color;
				color: var(--btn-text-color);
				border-radius: 50rpx;
				font-size: $font-size-tag;
				padding: 12rpx 30rpx;
				line-height: 1;
			}

			.icon-diy {
				font-size: 80rpx;
			}
			.sell-out{
				position: absolute;
				z-index: 1;
				width: 100%;
				height: 100%;
				top: 0;
				left: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				background: rgba(0, 0, 0, 0.5);
				text{
					color: #fff;
				}
			}
		}
	}

	// 商品列表单列样式
	.goods-list.row1-of1 {
		.goods-item {
			position: relative;
			background-color: #fff;
			display: flex;
			margin-bottom: 20rpx;
			padding: 16rpx;

			&:last-of-type {
				margin-bottom: 0;
			}

			&.shadow {
				margin: 8rpx 8rpx 20rpx 8rpx;
			}

			.goods-img-wrap {
				position: relative;
				overflow: hidden;
				width: 260rpx;
				height: 260rpx;
			}

			.goods-img {
				width: 260rpx;
			}

			.info-wrap {
				width: calc(100% - 260rpx);
				padding: 6rpx 0 6rpx 20rpx;
				flex: 1;
				display: flex;
				flex-direction: column;
				box-sizing: border-box;

				.pro-info {
					margin-top: auto;
					display: flex;
					flex-direction: row;
					justify-content: space-between;
					align-items: center;

					.discount-price {
						display: flex;
						justify-content: space-between;
						flex-direction: column;

						.price-wrap {
							white-space: nowrap;

							.unit {
								font-size: $font-size-tag !important;
							}

							.price {
								font-size: $font-size-toolbar;
							}

							text {
								font-weight: bold;
							}
						}
					}

					.delete-price {
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
					}
				}
			}
		}

		&.style-1 {
			.pro-info {
				position: relative;

				.price-wrap {
					line-height: 1;
				}

				.discount-price {
					align-items: flex-end;
				}

				.delete-price {
					margin: 4rpx 0;
					flex-basis: 100% !important;
				}
			}

			.buy-btn {
				min-width: 112rpx;
				height: 52rpx;
				padding: 0 20rpx;
				line-height: 52rpx;
				text-align: center;
				box-sizing: border-box;
			}
		}
		.sell-out{
			text{
				font-size: 200rpx;
			}
		}
	}

	// 两列（一行两列）
	.goods-list.row1-of2 {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;

		.goods-item {
			position: relative;
			// overflow: hidden;//排查开启此行回影响IOS字体颜色
			margin-top: 20rpx;
			width: calc(50% - 10rpx);
			display: flex;
			flex-direction: column;
			box-sizing: border-box;

			&:nth-child(2n) {
				margin-right: 0 !important;
			}

			&:nth-of-type(1),
			&:nth-of-type(2) {
				margin-top: 0;
			}

			&.shadow {
				width: calc(50% - 18rpx);

				&:nth-child(2n-1) {
					margin-left: 8rpx;
				}

				&:nth-child(2n) {
					margin-right: 8rpx !important;
				}

				&:nth-of-type(1),
				&:nth-of-type(2) {
					margin-top: 8rpx;
				}
			}

			.goods-img-wrap {
				position: relative;
				overflow: hidden;
				height: 344rpx;
			}

			.goods-img {
				width: 100%;
			}

			.info-wrap {
				display: flex;
				flex-direction: column;
				flex: 1;
				padding: 20rpx;

				.sale {
					flex-basis: 100%;
				}

				.pro-info {
					margin-top: auto;
					display: flex;
					flex-direction: row;
					justify-content: space-between;

					.discount-price {
						.price-wrap {
							white-space: nowrap;

							.unit {
								font-weight: bold;
								font-size: $font-size-tag !important;
							}

							.price {
								font-weight: bold;
								font-size: $font-size-toolbar !important;
							}
						}
					}

					.delete-price {
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
					}
				}
			}
		}

		&.style-1 {
			.pro-info {
				.discount-price {
					display: flex;
					flex-wrap: wrap;
					align-items: baseline;

					.price-wrap {
						display: inline-block;

						text {
							font-weight: bold;
						}
					}

					.price-wrap {
						line-height: 1;
					}
				}

				.delete-price {
					margin-top: 6rpx;
					flex-basis: 100% !important;
				}
			}
		}

		&.style-2 {
			.pro-info {
				position: relative;
				align-items: center;

				.price-wrap {
					line-height: 1;
				}

				.discount-price {
					display: flex;
					flex-wrap: wrap;
					align-items: baseline;
				}

				.delete-price {
					margin-top: 4rpx;
					flex-basis: 100% !important;
				}

				.sale {
					line-height: 1;
					margin-top: 10rpx;
				}

				.buy-btn {
					min-width: 140rpx;
					height: 52rpx;
					padding: 0 20rpx;
					line-height: 52rpx;
					text-align: center;
					box-sizing: border-box;
				}
			}
		}

		&.style-3 {
			.pro-info {
				.member-price {
					margin-right: auto;
					align-self: flex-end;
					margin-bottom: 4rpx;
				}

				.sale {
					line-height: 1;
					align-self: center;
					margin-top: 8rpx;
				}

				.discount-price {
					display: flex;
					flex-wrap: wrap;
					flex: 1;
					align-content: center;

					.price-wrap {
						display: flex;
						align-items: baseline;
						line-height: 1;
						align-self: center;
					}
				}
			}

			.swiper {
				padding: 20rpx 0;
			}
		}
		.sell-out{
			text{
				font-size: 250rpx;
			}
		}
	}

	// 商品列表三列样式
	.goods-list.row1-of3 {
		display: flex;
		flex-wrap: wrap;

		.goods-item {
			position: relative;
			display: flex;
			flex-direction: column;
			// overflow: hidden;
			margin-top: 20rpx;
			width: calc(33.3333333% - 14rpx);
			box-sizing: border-box;

			&:nth-child(3n + 3) {
				width: calc(33.33% - 15rpx);
			}

			&:nth-of-type(1),
			&:nth-of-type(2),
			&:nth-of-type(3) {
				margin-top: 0;
			}

			&:nth-child(3n) {
				width: calc(33.3333333% - 15rpx);
			}

			&:nth-child(3n-1) {
				margin-left: 20rpx;
				margin-right: 20rpx;
			}

			&.shadow {
				width: calc(33.3333333% - 18rpx);

				&:nth-of-type(1),
				&:nth-of-type(2),
				&:nth-of-type(3) {
					margin-top: 8rpx;
				}

				&:nth-child(1n) {
					margin-left: 8rpx;
				}

				&:nth-child(3n-1) {
					margin-left: 20rpx;
					margin-right: 20rpx;
				}

				&:nth-child(3n) {
					margin-right: 0;
					margin-left: 0;
				}
			}

			.goods-img-wrap {
				position: relative;
				overflow: hidden;
				height: 220rpx;
			}

			.goods-img {
				width: 100%;
			}

			.info-wrap {
				display: flex;
				flex-direction: column;
				flex: 1;
				padding: 10rpx;

				.pro-info {
					margin-top: auto;
					display: flex;
					flex-direction: column;
					justify-content: space-between;

					.discount-price {
						display: flex;
						justify-content: space-between;
						align-items: center;

						.price-wrap {
							white-space: nowrap;

							.unit {
								font-size: $font-size-tag !important;
							}

							.price {
								font-size: $font-size-toolbar;
							}

							text {
								font-weight: bold;
							}
						}
					}

					.delete-price {
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
					}
				}
			}
		}

		&.style-1 {
			.pro-info {
				.price-wrap {
					line-height: 1;
				}

				.discount-price {
					justify-content: unset !important;
					align-items: baseline !important;
					flex-wrap: wrap;
				}

				.delete-price {
					margin-left: 10rpx;
				}
			}
		}

		&.style-2 {
			.pro-info {
				position: relative;
				flex-direction: initial !important;
				align-items: center;

				.price-wrap {
					line-height: 1;
				}

				.discount-price {
					align-items: flex-end !important;
					flex-wrap: wrap;
					justify-content: unset !important;
				}

				.delete-price {
					margin: 20rpx 0;
					flex-basis: 100% !important;
				}

				.buy-btn {
					min-width: 112rpx;
					height: 52rpx;
					padding: 0 20rpx;
					line-height: 52rpx;
					text-align: center;
					box-sizing: border-box;
				}
			}
		}
		.sell-out{
			text{
				font-size: 150rpx;
			}
		}
	}

	// 商品列表横向滚动样式
	.goods-list.horizontal-slide {
		.scroll {
			width: calc(100% - 40rpx);
			padding: 20rpx;
			line-height: 1;
			white-space: nowrap;

			&.shadow {
				margin-bottom: 8rpx;
			}
		}

		.flex-between {
			justify-content: space-between;
		}

		.goods-item {
			position: relative;
			// overflow: hidden;
			width: 200rpx;
			display: inline-block;
			box-sizing: border-box;

			&:nth-child(3n + 3) {
				width: 198rpx;
			}

			&.shadow {
				margin-top: 8rpx;
			}
			.goods-img-wrap{
				position: relative;
				width: 100%;
				max-height: 200rpx;
				height: 200rpx;
			}
			.goods-img {
				width: 100%;
				max-height: 200rpx;
				height: 200rpx;
			}

			.info-wrap {
				padding: 10rpx;
				display: flex;
				flex-direction: column;
				justify-content: space-between;

				&.multi-content {
					height: 162rpx;
					box-sizing: border-box;
				}

				.goods-name {
					line-height: 1;
					margin-bottom: 10rpx;

					&.multi-hidden {
						white-space: break-spaces;
						line-height: 1.3;
					}
				}

				.pro-info {
					display: flex;
					flex-direction: column;
					justify-content: space-between;

					.discount-price {
						display: flex;
						flex-wrap: wrap;
						align-items: baseline;

						.price-wrap {
							line-height: 1;
							white-space: nowrap;

							.unit {
								font-size: $font-size-tag !important;
								// color: var(--price-color);
							}

							.price {
								font-size: $font-size-toolbar;
							}

							text {
								font-weight: bold;
								// color: var(--price-color);
							}
						}
					}

					.delete-price {
						margin-left: 6rpx;
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
						flex-basis: 100%;
					}
				}
			}
		}

		.swiper {
			width: 100%;
			white-space: nowrap;
			padding: 20rpx;
			box-sizing: border-box;

			.swiper-item {
				display: flex;
				align-items: baseline;
			}

			.goods-item {
				position: relative;
				width: 200rpx;

				.goods-img {
					width: 100%;
				}
			}
		}
		.sell-out{
			text{
				font-size: 190rpx;
			}
		}
	}

	// 商品列表大图样式
	.goods-list.large-mode {
		.goods-item {
			position: relative;
			background-color: #fff;
			margin-top: 20rpx;
			display: flex;
			flex-direction: column;

			&:first-child {
				margin-top: 0;
			}

			&.shadow {
				margin-left: 8rpx;
				margin-right: 8rpx;

				&:first-child {
					margin-top: 8rpx;
				}
			}

			.goods-img-wrap {
				position: relative;
				width: 100%;
			}
			.goods-img {
				width: 100%;
			}

			.info-wrap {
				padding: 20rpx;

				.sale {
					line-height: 1;
				}

				.pro-info {
					margin-top: 10rpx;
					display: flex;
					justify-content: space-between;
					align-items: center;

					.discount-price {
						.price-wrap {
							white-space: nowrap;
							line-height: 1;

							.unit {
								font-size: $font-size-tag !important;
								// color: var(--price-color);
							}

							.price {
								font-size: $font-size-toolbar;
							}

							text {
								font-weight: bold;
								// color: var(--price-color);
							}
						}
					}

					.delete-price {
						margin-left: 10rpx;
						text-decoration: line-through;
						flex: 1;
						line-height: 28rpx;
						color: $color-tip;
						font-size: $font-size-activity-tag;
					}
				}
			}
		}

		&.style-1 {
			.info-wrap {
				padding: 20rpx 20rpx 52rpx;
			}

			.pro-info {
				.discount-price {
					position: relative;
					flex: 1;
					display: flex;
					align-items: baseline;

					.sale {
						position: absolute;
						bottom: -34rpx;
						left: 6rpx;
					}

					.price-wrap {
						display: inline-block;
					}

					.delete-price {
						display: inline-block;
					}
				}
			}

			.buy-btn {
				min-width: 112rpx;
				height: 52rpx;
				padding: 0 20rpx;
				line-height: 52rpx;
				text-align: center;
				box-sizing: border-box;
			}
		}
		.sell-out{
			text{
				font-size: 500rpx;
			}
		}
	}

	.cart-action-wrap {
		position: relative;

		.cart-num {
			position: absolute;
			top: -16rpx;
			right: -10rpx;
			padding: 2rpx 4rpx;
			min-width: 30rpx;
			height: 30rpx;
			line-height: 30rpx;
			text-align: center;
			color: #fff;
			background-color: red;
			border-radius: 30rpx;
			font-size: $font-size-activity-tag;
		}
	}

	.cart-point {
		width: 26rpx;
		height: 26rpx;
		position: fixed;
		z-index: 1000;
		background: #f00;
		border-radius: 50%;
		transition: all 0.05s;
	}

	.click-wrap {
		.click-event {
			position: absolute;
			width: 2rpx;
			height: 2rpx;
			left: 50%;
			top: 50%;
			transform: translate(-50%, -50%);
			z-index: 5;
		}
	}
</style>