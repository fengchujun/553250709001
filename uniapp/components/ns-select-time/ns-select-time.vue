<template>
	<view class="ns-time">
		<uni-popup type="bottom" ref="selectTime">
			<view class="box">
				<view class="title">
					<block v-if="obj.delivery && obj.delivery.delivery_type == 'local'">选择送达时间</block>
					<block v-if="obj.delivery && obj.delivery.delivery_type == 'store'">选择自提时间</block>
					<text class="iconfont icon-close" @click="close"></text>
				</view>
				<view class="body">
					<!-- 左侧日期选择 -->
					<scroll-view :scroll-y="true" class="left">
						<view class="item" :class="index == keyJudge ? 'itemDay' : ''" v-for="(item, index) in dayData" :key="index" @click="selectTime('days', index, 'yes')">
							<block v-if="item.title">{{ item.title }}</block>
							<block v-else>{{ item.month }}</block>
							<text class="itemtext">{{ item.Day }}</text>
						</view>
					</scroll-view>
					<!-- 右侧时间选择 -->
					<scroll-view :scroll-y="true" class="right">
						<view class="item" :class="key == keyJudge && index == keys ? 'itemTime' : ''" v-for="(item, index) in timeData" :key="index" @click="selectTime('time', index, 'yes')">
							{{ item }}
							<text v-if="key == keyJudge && index == keys" class="iconfont icon-yuan_checked color-base-text"></text>
						</view>
					</scroll-view>
				</view>
			</view>
		</uni-popup>
	</view>
</template>

<script>
import uniPopup from '@/components/uni-popup/uni-popup.vue';
export default {
	name: "nsSelectTime",
	components: {
		uniPopup
	},
	data() {
		return {
			//选中日期的键值
			key: 0,
			//选中时间的键值
			keys: 0,
			//渲染用数据
			obj: {},
			//左侧渲染时间
			dayData: [],
			// 右侧渲染数据
			timeData: [],
			//判断弹窗打开
			judge: false,
			//判断左侧列表是否为日期选中列表
			keyJudge: 0,
			//当前时间时间戳
			dayTime: 0
		};
	},
	methods: {
		refresh(){
			this.key = 0;
			this.keys = 0;
			this.keyJudge = 0;
		},
		/**
		 * 弹窗打开
		 */
		open(obj, type) {
			this.dayData = [];
			this.timeData = [];
			this.obj = obj;
			this.toDay(obj.dataTime.time_type, obj.dataTime.time_week);
			if (this.judge) {
				if (type == 'no') {
					this.selectTime('', '', type);
				} else {
					this.$refs.selectTime.open();
				}
			}
		},
		/**
		 * 时间选择
		 */
		selectTime(type, index, judge) {
			if (type == 'days') {
				this.keyJudge = index;
				this.toTime();
			} else if (type == 'time') {
				this.keys = index;
				this.key = this.keyJudge;
				let obj = this.dayData[this.key];
				obj.time = this.timeData[this.keys];
				let time = obj.time.replace('立即配送（','').replace('）','');
				let dateTime = new Date();
				
				let format = time.split('-');
				let startHours = format[0].split(':');
				let endHours = format[1].split(':');

				let timeData = obj.month.split('月');
				let month = timeData[0];
				let date = timeData[1].split('日')[0];

				// 开始时间戳
				dateTime.setHours(startHours[0],startHours[1],0,0);
				obj.start_time = dateTime.getTime()/1000;
				obj.start_date = dateTime.getFullYear() + '-' + month + '-' + date + ' ' + format[0];

				// 结束时间戳
				dateTime.setHours(endHours[0],endHours[1],0,0);
				obj.end_time = dateTime.getTime()/1000;
				obj.end_date = dateTime.getFullYear() + '-' + month + '-' + date + ' ' + format[1];

				this.$emit('selectTime', { data: obj, type: judge });
				this.$refs.selectTime.close();
			}

			if (judge == 'no') {
				this.toTime(judge);
				let obj = this.dayData[0];
				obj.time = this.timeData[0];
				let dateTime = new Date();
				let format = obj.time.replace('立即配送（','').replace('）','').split('-');
				let startHours = format[0].split(':');
				let endHours = format[1].split(':');

				let timeData = obj.month.split('月');
				let month = timeData[0];
				let date = timeData[1].split('日')[0];

				// 开始时间戳
				dateTime.setHours(startHours[0],startHours[1],0,0);
				obj.start_time = dateTime.getTime()/1000;
				obj.start_date = dateTime.getFullYear() + '-' + month + '-' + date + ' ' + format[0];
				// 结束时间戳
				dateTime.setHours(endHours[0],endHours[1],0,0);
				obj.end_time = dateTime.getTime()/1000;
				obj.end_date = dateTime.getFullYear() + '-' + month + '-' + date + ' ' + format[1];
				this.$emit('selectTime', { data: obj, type: judge });
			}
			this.$forceUpdate();
		},
		/**
		 * 弹窗关闭
		 */
		close() {
			this.$refs.selectTime.close();
		},
		/**
		 * 左侧数据处理
		 */
		toDay(type, obj) {
			let today = new Date();
			if (this.obj.dataTime.advance_day) {
				today = new Date(today.getTime() + (this.obj.dataTime.advance_day * 86400000));
			}
			let nowYear = today.getFullYear(); //当前年
			let nowMonth = today.getMonth() + 1; //当前月
			let nowDate = today.getDate(); //当前日
			let nowDay = today.getDay(); //当前星期几
			let endDay = new Date(nowYear, nowMonth, 0).getDate(); //当月多少天
			let Hours = today.getHours();
			let Minutes = today.getMinutes();
			this.dayTime = this.obj.dataTime.advance_day ? 0 : Number(Hours) * 3600 + Number(Minutes) * 60; //获取到当前时分秒的时间戳
			let judge = false;
			let num = 1; //记录循环执行过的次数
			let mostDay = this.obj.dataTime.most_day ? this.obj.dataTime.most_day + 1 : 1;  // 最多可预约天数
			let startTime = parseInt(today.getTime() / 1000);
			let week = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
			if (obj.time_week && obj.time_week.length == 7) {
				//判断是否七天全有
				judge = true;
			}

			for (let i = 0; i < mostDay; i++) {
				let objects = {};
				let dayStr = week[ nowDay ];
				// 判断最大可预约时间
				if (this.obj.dataTime.most_day > 0 && ((startTime + num * 86400) > (startTime + this.obj.dataTime.most_day * 86400) ) ) {
					this.judge = true;
					break;
				}
				//判断当天是否能够配送、自提
				if (type == 0 || judge || obj.indexOf(nowDay.toString()) != -1) {

					let endTime = this.obj.dataTime.delivery_time[ (this.obj.dataTime.delivery_time.length - 1) ].end_time;
						endTime -= this.obj.dataTime.time_interval * 60;
					switch (num) {
						case 1:
							if (i == 0) {
								if (endTime < this.dayTime) {
									i = i - 1;
								} else {
									objects = {
										title: this.obj.dataTime.advance_day == 0 ? '今天' : '',
										type: 'special',
										month: nowMonth + '月' + nowDate + '日',
										Day: '(' + dayStr + ')'
									};
									this.dayData.push(objects); //左侧日期数据处理
								}
							}
							break;
						case 2:
							if (i == 0 || i == 1) {
								objects = {
									title: this.obj.dataTime.advance_day == 0 ? '明天' : '',
									month: nowMonth + '月' + nowDate + '日',
									Day: '(' + dayStr + ')'
								};
								this.dayData.push(objects); //左侧日期数据处理
							}
							break;
						default:
							objects = {
								title: '',
								month: nowMonth + '月' + nowDate + '日',
								Day: '(' + dayStr + ')'
							};
							this.dayData.push(objects); //左侧日期数据处理
					}
				} else {
					i = i - 1;
				}

				if (nowDate != endDay) {
					nowDate += 1;
				} else {
					if (nowMonth != 12) {
						nowMonth += 1;
					} else {
						nowMonth = 1;
					}
					nowDate = 1;
				}

				if (nowDay != 6) {
					nowDay += 1;
				} else {
					nowDay = 0;
				}
				num += 1;
				
				if (this.obj.dataTime.most_day == 0 && i == 0) {
					this.judge = true;
				}
			}
			this.toTime(); //处理右侧时间数据
		},
		/**
		 * 处理右侧时间数据
		 */
		toTime(judge) {
			//并非打开弹窗进入时，所有数据置零
			if (judge == 'no') {
				this.key = 0;
				this.keys = 0;
				this.keyJudge = 0;
			}

			let timeData = [];
			
			if (!this.obj.dataTime.delivery_time) {
				this.obj.dataTime.delivery_time = [ {start_time: this.obj.dataTime.start_time, end_time: this.obj.dataTime.end_time} ]
			}
			
			//判断选中是否为当天
			let remainder = 0;
			//当天配送自提的话，向后推迟30分钟
			let newDayTime = JSON.parse(JSON.stringify(this.dayTime));
			// newDayTime = Math.ceil(this.dayTime / 600) * 600 + 1800;
			
			//判断选中是否为当天
			let timeJudage = false;
			if (this.dayData[this.keyJudge] && this.dayData[this.keyJudge].type && newDayTime > this.obj.dataTime.start_time) timeJudage = true;
			
			let timeInterval = this.obj.dataTime.time_interval ? this.obj.dataTime.time_interval * 60 : 1200;
			
			this.obj.dataTime.delivery_time.forEach(item => {
				item.end_time = item.end_time ? item.end_time : 86400; 
				let num = parseInt((parseInt(item.end_time) - parseInt(item.start_time)) / timeInterval);
				let time = timeJudage ? parseInt(newDayTime) : parseInt(item.start_time);
				for (let i = 0; i < num; i++) {
					if (parseInt(time) + parseInt(timeInterval) > item.end_time) break;
					if (timeJudage) {
						if (time >= newDayTime) {
							if (this.obj.dataTime.time_interval) {
								if (time <= item.end_time) {
									let text = '';
									if (this.obj.delivery.delivery_type == 'local' && i == 0) {
										text = '立即配送（'+ this.$util.getTimeStr(time) + '-' + this.$util.getTimeStr(time + timeInterval) + '）';
									} else {
										text = this.$util.getTimeStr(time) + '-' + this.$util.getTimeStr(time + timeInterval);
									}
									timeData.push(text);
								} 
							} else {
								timeData.push(this.$util.getTimeStr(time));
							}
						}
					} else {
						if (this.obj.dataTime.time_interval) {
							if (time <= item.end_time) timeData.push(this.$util.getTimeStr(time) + '-' + this.$util.getTimeStr(time + timeInterval));
						} else {
							timeData.push(this.$util.getTimeStr(time));
						}
					}
					time = parseInt(time) + timeInterval;
				}
			})
			this.timeData = timeData;
			this.$forceUpdate();
		}
	}
};
</script>

<style lang="scss" scoped>
.box {
	height: 728rpx;
	.title {
		padding: 0 30rpx;
		box-sizing: border-box;
		text-align: center;
		font-size: 28rpx;
		font-weight: bold;
		position: relative;
		height: 90rpx;
		line-height: 90rpx;
		border-bottom: 1rpx solid #f7f4f4;
		.icon-close {
			font-size: 26rpx;
			color: #909399;
			position: absolute;
			right: 30rpx;
			top: 50%;
			transform: translateY(-50%);
		}
	}
	.body {
		width: 100%;
		height: calc(100% - 90rpx);
		display: flex;
		align-items: center;
		.left {
			width: 230rpx;
			background: #f8f8f8;
			height: 100%;
			.item {
				width: 100%;
				padding: 16rpx 30rpx;
				box-sizing: border-box;
				text-align: center;
				font-size: 24rpx;
				display: flex;
				align-items: center;
			}
			.itemDay {
				background: #ffffff;
			}
		}
		.right {
			width: calc(100% - 230rpx);
			height: 100%;
			padding: 0 30rpx;
			box-sizing: border-box;
			.item {
				width: 100%;
				font-size: 24rpx;
				border-bottom: 1rpx solid #eeeeee;
				display: flex;
				align-items: center;
				justify-content: space-between;
				height: 72rpx;
				.icon-yuan_checked {
					font-size: 38rpx;
					margin-right: 30rpx;
				}
			}
			.itemTime {
				color: var(--main-color);
			}
		}
	}
}
</style>
