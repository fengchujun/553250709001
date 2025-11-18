<template>
    <view class="contact-wrap">
        <slot></slot>
        <!-- #ifdef MP-ALIPAY -->
        <view class="contact-button" @click="contactServicer">
            <contact-button :tnt-inst-id="config.instid" :scene="config.scene" size="1000rpx" v-if="config.type == 'aliapp'"/>
        </view>
        <!-- #endif -->
        <!-- #ifndef MP-ALIPAY -->
        <button
            type="default"
            hover-class="none"
            :open-type="openType"
            class="contact-button"
            @click="contactServicer"
            :send-message-title="sendMessageTitle"
            :send-message-path="sendMessagePath"
            :send-message-img="sendMessageImg"
            :show-message-card="true"></button>
        <!-- #endif -->
        <uni-popup ref="servicePopup" type="center">
            <view class="service-popup-wrap">
                <view class="head-wrap" @click="$refs.servicePopup.close()">
                    <text>联系客服</text>
                    <text class="iconfont icon-close"></text>
                </view>
                <view class="body-wrap">{{ siteInfo.site_tel ? '请联系客服，客服电话是' + siteInfo.site_tel : '抱歉，商家暂无客服，请线下联系' }}</view>
            </view>
        </uni-popup>
    </view>
</template>

<!-- 客服组件 -->
<script>
    export default {
        name: 'ns-contact',
        props: {
            niushop: {
                type: Object,
                default: function() {
                    return {};
                }
            },
            sendMessageTitle: {
                type: String,
                default: ''
            },
            sendMessagePath: {
                type: String,
                default: ''
            },
            sendMessageImg: {
                type: String,
                default: ''
            }
        },
        data() {
            return {
                config: null,
                openType: ''
            };
        },
        created() {
            if (this.servicerConfig) {
                // #ifdef H5
                this.config = this.servicerConfig.h5;
                // #endif

                // #ifdef MP-WEIXIN
                this.config = this.servicerConfig.weapp;
                if (this.config.type == 'weapp') this.openType = 'contact';
                // #endif

                // #ifdef MP-ALIPAY
                this.config = this.servicerConfig.aliapp;
                if (this.config.type == 'aliapp') this.openType = 'contact';
                // #endif
            }
        },
        methods: {
            /**
             * 联系客服
             */
            contactServicer() {
                if (this.config.type == 'none') {
                    this.$refs.servicePopup.open();
                }
                if (this.openType == 'contact') return;

                switch (this.config.type) {
                    case 'wxwork':
                        // #ifdef H5
                        location.href = this.config.wxwork_url;
                        // #endif
                        // #ifdef MP-WEIXIN
                        wx.openCustomerServiceChat({
                            extInfo: { url: this.config.wxwork_url },
                            corpId: this.config.corpid,
                            showMessageCard: true,
                            sendMessageTitle: this.sendMessageTitle,
                            sendMessagePath: this.sendMessagePath,
                            sendMessageImg: this.sendMessageImg
                        });
                        // #endif
                        break;
                    case 'third':
                        location.href = this.config.third_url;
                        break;
                    case 'niushop':
                        this.$util.redirectTo('/pages_tool/chat/room', this.niushop);
                        break;
                    default:
                        this.makePhoneCall();
                }
            },
            /**
             * 店铺联系方式
             */
            makePhoneCall() {
                this.$api.sendRequest({
                    url: '/api/site/shopcontact',
                    success: res => {
                        if (res.code == 0 && res.data.mobile) uni.makePhoneCall({
                            phoneNumber: res.data.mobile
                        });
                    }
                });
            }
        }
    };
</script>

<style lang="scss">
    .contact-wrap {
        width: 100%;
        height: 100%;
        position: relative;

        .contact-button {
            width: 100%;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            z-index: 5;
            padding: 0;
            margin: 0;
            opacity: 0;
            overflow: hidden;
        }
    }

    .service-popup-wrap {
        width: 600rpx;

        .head-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30rpx;
            height: 90rpx;
        }

        .body-wrap {
            text-align: center;
            padding: 30rpx;
            height: 100rpx;
        }
    }
</style>