export default {
	data() {
		return {
			diyRoute: '/pages/member/index'
		};
	},
	computed: {},
	watch: {
		storeToken: function(nVal, oVal) {
			if (nVal) {
				this.initData();
				if (uni.getStorageSync('source_member')) this.$util.onSourceMember(uni.getStorageSync('source_member'));
			}
		}
	},
	onLoad(data) {
		uni.hideTabBar();
		this.name = 'DIY_VIEW_MEMBER_INDEX';
		if (data.code) {
			this.$api.sendRequest({
				url: '/wechat/api/wechat/authcodetoopenid',
				data: {
					code: data.code
				},
				success: res => {
					if (res.code >= 0) {
						if (res.data.userinfo.nickName) this.modifyNickname(res.data.userinfo.nickName);
						if (res.data.userinfo.avatarUrl) this.modifyHeadimg(res.data.userinfo.avatarUrl);
					}
				}
			});
		}
	},
	onShow() {
		// 刷新会员数据
		if (this.$refs.diyGroup) {
			if (this.$refs.diyGroup.$refs.diyMemberIndex) this.$refs.diyGroup.$refs.diyMemberIndex[0].init();
			if (this.$refs.diyGroup.$refs.diyMemberMyOrder) this.$refs.diyGroup.$refs.diyMemberMyOrder[0].getOrderNum();
		}
	},
	methods: {
		/**
		 * 查询会员信息
		 */
		initData() {
			if (this.storeToken) {
				this.$nextTick(() => {
					let callback = () => {
						// 刷新会员数据
						if (this.$refs.diyGroup) {
							if (this.$refs.diyGroup.$refs.diyMemberIndex) {
								this.$refs.diyGroup.$refs.diyMemberIndex[0].init();
							}
						}
					}
					this.$refs.nsNewGift.init(callback);
					this.$refs.birthdayGift.init(callback);
				});
			}
		},
		/**
		 * 修改昵称
		 * @param {Object} nickName
		 */
		modifyNickname(nickName) {
			this.$api.sendRequest({
				url: '/api/member/modifynickname',
				data: {
					nickname: nickName
				},
				success: res => {
					if (res.code == 0) {
						this.memberInfo.nickname = nickName;
						this.$store.commit('setMemberInfo', this.memberInfo);
					}
				}
			});
		},
		/**
		 * 修改头像
		 */
		modifyHeadimg(headimg) {
			this.$api.sendRequest({
				url: '/api/member/modifyheadimg',
				data: {
					headimg: headimg
				},
				success: res => {
					if (res.code == 0) {
						this.memberInfo.headimg = headimg;
						this.$store.commit('setMemberInfo', this.memberInfo);
					}
				}
			});
		},
	},
};