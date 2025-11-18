<template>
    <view>
        <view scroll-y="true" class="goods-detail" :class="isIphoneX ? 'active' : ''">
            <view class="goods-container">
                <!-- 弹幕 -->
                <pengpai-fadein-out v-if="goodsSkuDetail.barrage_show && goodsSkuDetail.barrageData" ref="pengpai" :duration="1600" :wait="1900" :top="200" :left="0" :radius="60" :loop="true" :info="goodsSkuDetail.barrageData"/>

                <!-- 商品媒体信息 -->
                <view class="goods-media" :style="{height: goodsSkuDetail.swiperHeight}">
                    <!-- 商品图片 -->
                    <view class="goods-img" :class="{ show: switchMedia == 'img' }">
                        <swiper class="swiper" @change="swiperChange" :interval="swiperInterval" :autoplay="swiperAutoplay" autoplay="true" interval="4000" circular="true">
                            <swiper-item v-for="(item, index) in goodsSkuDetail.sku_images" :key="index" :item-id="'goods_id_' + index">
                                <view class="item" @click="previewMedia(index)">
                                    <image :src="$util.img(item, { size: 'big' })" @error="swiperImageError(index)" mode="aspectFit" />
                                </view>
                            </swiper-item>
                        </swiper>
                        <view class="img-indicator-dots">
                            <text>{{ swiperCurrent }}</text>
                            <text v-if="goodsSkuDetail.sku_images">/{{ goodsSkuDetail.sku_images.length }}</text>
                        </view>
                    </view>

                    <!-- 商品视频 -->
                    <view class="goods-video" :class="{ show: switchMedia == 'video' }">
                        <video id="goodsVideo" :src="$util.img(goodsSkuDetail.video_url)" :poster="$util.img(goodsSkuDetail.sku_image, { size: 'big' })" objectFit="cover"></video>
                    </view>

                    <!-- 切换视频、图片 -->
                    <view class="media-mode" v-if="goodsSkuDetail.video_url != ''">
                        <text :class="{ 'color-base-bg': switchMedia == 'video' }" @click="switchMedia = 'video'">{{ $lang('video') }}</text>
                        <text :class="{ 'color-base-bg': switchMedia == 'img' }" @click="(switchMedia = 'img'), videoContext.pause()">{{ $lang('image') }}</text>
                    </view>
                </view>

                <!-- 价格区域 -->
                <view class="goods-gression">
                    <slot name="price"></slot>
                </view>
				
				
                <view class="newdetail margin-bottom">
                    <!-- 入口区域 -->
                    <slot name="entrance"></slot>

                    <!-- 配送 -->
                    <view class="item delivery-type" v-if="goodsSkuDetail.is_virtual == 0" @click="$refs.deliveryType.open()">
                        <view class="label">配送</view>
                        <block v-if="deliveryType">
                            <view class="box">
                                <block v-for="(item, index) in deliveryType" :key="index">
                                    <text v-if="goodsSkuDetail.support_trade_type.indexOf(index) != -1">{{ item.name }}</text>
                                </block>
                            </view>
                            <text class="iconfont icon-right"></text>
                        </block>
                        <block v-else>
                            <view class="box">商家未配置配送方式</view>
                        </block>
                    </view>

                    <!-- 门店 -->
                    <view class="item store-wrap" @click="openStoreListPopup()" v-if="addonIsExist.store && globalStoreInfo && isShowStore">
                        <view class="label">适用门店</view>
                        <view class="list-wrap" v-if="hasGlobalStore">
                            <view class="name-wrap">
                                <text class="icondiy icon-system-shop"></text>
                                <text class="name">{{ globalStoreInfo.store_name}}</text>
                            </view>
							<view class="close-desc" v-if="globalStoreInfo.status == 0 && globalStoreInfo.close_desc">
								{{ globalStoreInfo.close_desc }}
							</view>
                            <view class="other-wrap">
                                <text class="distance" v-if="parseFloat(globalStoreInfo.distance)">距离{{ globalStoreInfo.distance > 1 ? globalStoreInfo.distance + 'km' : globalStoreInfo.distance * 1000 + 'm' }}</text>
                                <text class="decorate" v-if="parseFloat(globalStoreInfo.distance)">.</text>
                                <view class="address">{{ globalStoreInfo.full_address + globalStoreInfo.address }}
                                </view>
                            </view>
                        </view>
						<view class="list-wrap" v-else-if="storeList && storeList.data.length">
						    <view class="name-wrap">
						        <text class="icondiy icon-system-shop"></text>
						        <text class="name">{{ storeList.data[0].store_name}}</text>
						    </view>
							<view class="close-desc" v-if="storeList.data[0].status == 0 && storeList.data[0].close_desc">
								{{ storeList.data[0].close_desc }}
							</view>
						    <view class="other-wrap">
						        <text class="distance" v-if="parseFloat(storeList.data[0].distance)">距离{{ storeList.data[0].distance > 1 ? storeList.data[0].distance + 'km' : storeList.data[0].distance * 1000 + 'm' }}</text>
						        <text class="decorate" v-if="parseFloat(storeList.data[0].distance)">.</text>
						        <view class="address">{{ storeList.data[0].full_address + storeList.data[0].address }}
						        </view>
						    </view>
						</view>
                        <text class="iconfont icon-right"></text>
                    </view>
					
                    <view class="item service" @click="openMerchantsServicePopup()" v-if="goodsSkuDetail.goods_service.length">
                        <view class="label">服务</view>
                        <view class="list-wrap">
                            <view class="item-wrap" v-for="(item, index) in goodsSkuDetail.goods_service" :key="index" v-if="index < 3">
                                <view class="item-wrap-box">
                                    <view class="item-wrap-icon">
                                        <text class="iconfont icon-dui" v-if="!item.icon || (!item.icon.imageUrl && !item.icon.icon)"></text>
                                        <image class="icon-img" v-else-if="item.icon.iconType == 'img'" :src=" $util.img(item.icon.imageUrl)" />
                                        <diy-icon class="icon-box" v-else-if="item.icon.iconType == 'icon'" :icon="item.icon.icon" :value="item.icon.style ? item.icon.style : null"></diy-icon>
                                    </view>
                                    <text>{{ item.service_name }}</text>
                                </view>
                            </view>
                        </view>
                        <text class="iconfont icon-right"></text>
                    </view>
                </view>

                <!-- 配送方式 -->
                <view @touchmove.prevent.stop>
                    <uni-popup ref="deliveryType" type="bottom">
                        <view class="deliverytype-popup-layer popup-layer">
                            <view class="head-wrap" @click="$refs.deliveryType.close()">
                                <text>配送</text>
                                <text class="iconfont icon-close"></text>
                            </view>
                            <scroll-view scroll-y class="type-body">
                                <block v-for="(item, index) in deliveryType" :key="index">
                                    <view class="type-item" :class="{ 'not-support': goodsSkuDetail.support_trade_type.indexOf(index) == -1 }">
                                        <text class="iconfont" :class="item.icon"></text>
                                        <view class="content">
                                            <view class="title">{{ item.name }}</view>
                                            <view class="desc">{{ item.desc }}</view>
                                        </view>
                                    </view>
                                </block>
                            </scroll-view>
                        </view>
                    </uni-popup>
                </view>

                <!-- 商品服务 -->
                <view @touchmove.prevent.stop>
                    <uni-popup ref="merchantsServicePopup" type="bottom">
                        <view class="goods-merchants-service-popup-layer popup-layer">
                            <view class="head-wrap" @click="closeMerchantsServicePopup()">
                                <text>商品服务</text>
                                <text class="iconfont icon-close"></text>
                            </view>
                            <scroll-view scroll-y>
                                <view class="item" :class="{ 'empty-desc': !item.desc }" v-for="(item, index) in goodsSkuDetail.goods_service" :key="index">
                                    <view class="item-icon" :class="{'empty-desc':!item.desc}">
                                        <text class="iconfont icon-dui color-base-text" v-if="!item.icon || (!item.icon.imageUrl && !item.icon.icon)"></text>
                                        <image class="icon-img" v-else-if="item.icon.iconType == 'img'" :src=" $util.img(item.icon.imageUrl)" />
                                        <diy-icon class="icon-box" v-else-if="item.icon.iconType == 'icon'" :icon="item.icon.icon" :value="item.icon.style ? item.icon.style : null"></diy-icon>
                                    </view>
                                    <view class="info-wrap">
                                        <text class="title">{{ item.service_name }}</text>
                                        <text class="describe" v-if="item.desc">{{ item.desc }}</text>
                                    </view>
                                </view>
                            </scroll-view>
                            <view class="button-box">
                                <button type="primary" @click="closeMerchantsServicePopup()">确定</button>
                            </view>
                        </view>
                    </uni-popup>
                </view>

                <!-- 门店列表 -->
                <view @touchmove.prevent.stop>
                    <uni-popup ref="storeListPopup" type="bottom">
                        <view class="goods-merchants-service-popup-layer popup-layer store-list-wrap">
                            <view class="head-wrap" @click="closeStoreListPopup()">
                                <text>适用门店</text>
                                <text class="iconfont icon-close"></text>
                            </view>
                            <scroll-view scroll-y>
                                <view class="store-list-content">
                                    <view class="list-item" v-for="(item, index) in storeList.data" :key="index" @click="selectStore(item)">
                                        <view class="item-box">
                                            <view class="item-image">
                                                <image :src="$util.img(item.store_image)" v-if="item.store_image"/>
                                                <image :src="$util.getDefaultImage().store" v-else/>
                                            </view>
                                            <view class="item-info">
                                                <view class="item-title">
                                                    <text class="title">{{ item.store_name }}</text>
                                                    <text class="distance color-base-text" v-if="item.distance">
                                                        距离{{ item.distance > 1 ? item.distance + 'km' : item.distance * 1000 + 'm' }}
                                                    </text>
                                                </view>
												<view class="item-close-desc" v-if="item.status == 0 && item.close_desc">{{ item.close_desc }}
												</view>
                                                <view class="item-time" v-if="item.open_date">营业时间：{{ item.open_date }}
                                                </view>
                                                <view class="item-address">{{ item.full_address + item.address }}</view>
                                            </view>
                                            <view class="item-right"><text class="iconfont icon-right"></text></view>
                                        </view>
                                    </view>
                                </view>
                            </scroll-view>
                        </view>
                    </uni-popup>
                </view>

                <!-- 业务区域 -->
                <slot name="business"></slot>

                <view class="detail-community" v-if="goodsSkuDetail.qr_data && goodsSkuDetail.qr_data.qr_state == 1">
                    <view class="community-box">
                        <image :src="$util.img('public/uniapp/goods/detail_erweiImage.png')" mode="aspectFill"></image>
                        <view class="community-content">
                            <view class="community-title">{{ goodsSkuDetail.qr_data.qr_name }}</view>
                            <view class="community-txt">{{ goodsSkuDetail.qr_data.community_describe }}</view>
                        </view>
                    </view>
                    <view class="community-btn" @click="onCommunity()">添加</view>
                </view>

                <!-- 促销 -->
                <view class="community-model" @touchmove.prevent.stop @click.stop="onCloseCommunity()" v-show="isCommunity">
                    <view class="community-model-content" @click.stop>
                        <view class="community-model-content-radius">
                            <view>添加社群</view>
                        </view>
                        <view class="community-model-content-draw" v-if="goodsSkuDetail.qr_data && goodsSkuDetail.qr_data.qr_img">
                            <image :src="goodsSkuDetail.qr_data.qr_img != '' && goodsSkuDetail.qr_data.qr_state == 1 ? $util.img(goodsSkuDetail.qr_data.qr_img) : $util.img('public/uniapp/goods/detail_erweiImage.png') " mode="aspectFill" show-menu-by-longpress="true"/>
                        </view>
                        <view class="community-model-content-text">长按识别二维码，添加社群</view>
                    </view>
                    <view class="community-model-close" @click.stop="onCloseCommunity()">
                        <text class="iconfont icon-close"></text>
                    </view>
                </view>

                <!-- 参与流程 -->
                <slot name="articipation"></slot>

                <!-- 商品评价 -->
                <view class="group-wrap" v-if="evaluateConfig.evaluate_show == 1">
                    <view class="goods-evaluate" @click="toEvaluateDetail(goodsSkuDetail.goods_id)">
                        <view class="tit">
                            <!-- <view class="tit" :class="{ active: goodsEvaluate.content }"> -->
                            <view>
                                <text class="color-title font-size-base">
                                    评价
                                    <text class="font-size-base">({{ evaluateCount }})</text>
                                </text>
                                <text class="evaluate-item-empty" v-if="!evaluateCount">暂无评价</text>
                                <view class="evaluate-item-empty" v-else>
                                    <text class="font-size-tag">查看全部</text>
                                    <text class="iconfont icon-right font-size-tag"></text>
                                </view>
                            </view>
                        </view>
                        <view class="evaluate-item" v-for="(item, index) in goodsEvaluate" :key="index" v-if="item.content">
                            <view class="evaluator">
                                <view class="evaluator-info">
                                    <view class="evaluator-face">
                                        <image v-if="item.member_headimg" :src="$util.img(item.member_headimg)" @error="item.member_headimg = $util.getDefaultImage().head" mode="aspectFill" />
                                        <image v-else :src="$util.getDefaultImage().head" @error="item.member_headimg = $util.getDefaultImage().head" mode="aspectFill" />
                                    </view>
                                    <view class="evaluator-name-wrap">
                                        <text class="evaluator-name using-hidden" v-if="item.member_name.length > 2 && item.is_anonymous == 1">
                                            {{ item.member_name[0] }}***{{ item.member_name[item.member_name.length - 1] }}
                                        </text>
                                        <text class="evaluator-name using-hidden" v-else>{{ item.member_name }}</text>
                                        <view v-if="item.scores" class="evaluator-xing">
                                            <xiaoStarComponent :starCount="item.scores * 2"></xiaoStarComponent>
                                        </view>
                                    </view>
                                </view>
                                <text class="time color-tip">{{ $util.timeStampTurnTime(item.create_time) }}</text>
                            </view>
                            <view class="cont margin-top">{{ item.content }}</view>
                            <scroll-view scroll-x="true">
                                <view class="evaluate-img" v-if="item.images">
                                    <view class="img-box" v-for="(img, img_index) in item.images" :key="img_index" @click="previewEvaluate(index, img_index, 'images')">
                                        <image :src="$util.img(img)" mode="aspectFill" />
                                    </view>
                                </view>
                            </scroll-view>
                        </view>
                    </view>
                </view>

                <view class="goods-attr" v-if="goodsSkuDetail.goods_attr_format && goodsSkuDetail.goods_attr_format.length > 0">
                    <view class="title">规格属性</view>
                    <view class="attr-wrap">
                        <block v-for="(item, index) in goodsSkuDetail.goods_attr_format" :key="index">
                            <view class="item" v-if="goodsAttrShow || (!goodsAttrShow && index < 4)">
                                <text class="attr-name">{{ item.attr_name }}</text>
                                <text class="value-name">{{ item.attr_value_name }}</text>
                            </view>
                        </block>
                        <view class="attr-action" v-if="goodsSkuDetail.goods_attr_format.length > 4" @click="switchGoodsAttr">
                            <block v-if="!goodsAttrShow">
                                展开<text class="iconfont icon-iconangledown"></text>
                            </block>
                            <block v-else>
                                收起<text class="iconfont icon-iconangledown-copy"></text>
                            </block>
                        </view>
                    </view>
                </view>

                <!-- 详情 -->
                <view class="goods-detail-tab">
                    <view class="detail-tab">
                        <view class="tab-item">商品详情</view>
                    </view>
                    <view class="detail-content active">
                        <view class="detail-content-item">
                            <view class="goods-details" v-if="goodsSkuDetail.goods_content">
								<ns-mp-html :content="goodsSkuDetail.goods_content"></ns-mp-html>
                            </view>
                            <view class="goods-details active" v-else>该商家暂无上传相关详情哦！</view>
                            <view class="goods-details" v-if="service && service.is_display == 1 && service.content">
								<ns-mp-html :content="service.content"></ns-mp-html>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 商品推荐 -->
                <ns-goods-recommend ref="goodrecommend" route="goods_detail"></ns-goods-recommend>

                <ns-copyright></ns-copyright>

                <!-- 海报 -->
                <view @touchmove.prevent.stop class="poster-layer">
                    <uni-popup ref="posterPopup" type="center">
                        <template v-if="poster != '-1'">
                            <view class="poster-wrap">
                                <view class="image-wrap">
                                    <image :src="$util.img(poster)" :show-menu-by-longpress="true" mode="widthFix" />
                                    <view class="close iconfont icon-close" @click="closePosterPopup()"></view>
                                </view>
                                <!-- #ifdef MP || APP-PLUS  -->
                                <view class="save-btn" @click="saveGoodsPoster()">保存图片</view>
                                <!-- #endif -->
                                <!-- #ifdef H5 -->
                                <view class="save-btn">长按图片进行保存</view>
                                <!-- #endif -->
                            </view>
                        </template>
                    </uni-popup>
                </view>

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

                <slot name="fixedbtn"></slot>
            </view>
        </view>

        <!-- 操作区域 -->
        <slot name="action"></slot>
        <to-top v-if="showTop" @toTop="scrollToTopNative()"></to-top>
        <ns-login ref="login"></ns-login>
    </view>
</template>

<script>
    // 商品详情视图
    import uniPopup from '@/components/uni-popup/uni-popup.vue';
    import nsGoodsRecommend from '@/components/ns-goods-recommend/ns-goods-recommend.vue';
    import pengpaiFadeinOut from '@/components/pengpai-fadein-out/pengpai-fadein-out.vue';
    import xiaoStarComponent from '@/components/xiao-star-component/xiao-star-component.vue';
    import scroll from '@/common/js/scroll-view.js';
    import toTop from '@/components/toTop/toTop.vue';
    import detail from './detail.js';

    export default {
        name: 'goods-detail-view',
        props: {
            goodsSkuDetail: {
                type: Object,
                default: () => {
                    return {};
                }
            }
        },
        components: {
            uniPopup,
            nsGoodsRecommend,
            pengpaiFadeinOut,
            toTop,
            xiaoStarComponent
        },
        mixins: [scroll, detail]
    };
</script>

<style lang="scss">
    @import '@/common/css/goods_detail.scss';
</style>
<style scoped></style>