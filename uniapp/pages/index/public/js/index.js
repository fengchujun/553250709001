export default {
	data() {
		return {
			wechatQrcode: '', // 公众号二维码
			diyRoute: '/pages/index/index',
			refresh:false,
		};
	},
	onLoad(option) {
		// #ifdef H5
		// H5地图选择位置回调数据
		if (option.module && option.module == 'locationPicker') {
			option.name = ''; // 清空地址
		}
		// #endif
		this.name = option.name || 'DIY_VIEW_INDEX'; // 根据店铺运营模式，打开平台（shop）或者连锁门店（stroe）页面，接口会自行判断

	},
	onShow() {
		this.getFollowQrcode();
	},
	methods: {
		changeCategoryNav(e){
			if(e == 0) this.refresh = !this.refresh;
		},
		// 关注公众号
		getFollowQrcode() {
			if (!this.$util.isWeiXin()) return;
			this.$api.sendRequest({
				url: '/wechat/api/wechat/followqrcode',
				success: res => {
					if (res.code >= 0 && res.data) {
						this.wechatQrcode = res.data.qrcode;
					}
				}
			});
		},
		officialAccountsOpen() {
			this.$refs.officialAccountsPopup.open();
		},
		officialAccountsClose() {
			this.$refs.officialAccountsPopup.close();
		},
	}
}