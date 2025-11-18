<template>
	<view>
		<!-- <uni-popup ref="timePopup" type="bottom"> -->
		<view class="content">
			<!-- <view class="head">
					<text class="title">选择时间</text>
					<text class="iconfont iconclose" @click="close()"></text>
				</view> -->
			<view class="container">
				<view class="date-list-wrap">
					<!-- 日期列表 -->
					<scroll-view scroll-x>
						<block v-for="(item, index) in dateArr" :key="index">
							<div class="flex-box" @click="selectDateEvent(index, item)">
								<view class="date-box" :style="{ color: index == dateActive ? selectedTabColor : '#909399' }">
									<text>{{ item.week }} {{ item.date }}</text>
								</view>
							</div>
						</block>
					</scroll-view>
					<div class="appointed-day">
						<uni-datetime-picker type="date" :start="pickerStartDay" :end="pickerEndDay" @change="change">
							<text class="day-box">指定日期</text>
							<text class="iconfont iconyoujiantou"></text>
						</uni-datetime-picker>
					</div>
				</view>

				<!-- 时间选项 -->
				<view class="time-box" v-if="!isSection">
					<block v-for="(item, _index) in timeArr" :key="_index">
						<view class="item">
							<view class="item-box diy" :class="{ disable: item.disable, active: isMultiple ? item.isActive : _index == timeActive }" @click="selectTimeEvent(_index, item)">
								<!-- :style="{ color: isMultiple ? (item.isActive ? selectedItemColor : '#333') : _index == timeActive ? selectedItemColor : '#333' }" -->
								<text>{{ item.time }}</text>
								<!-- <text class="all">{{ item.disable ? disableText : undisableText }}</text> -->
							</view>
						</view>
					</block>
				</view>

				<!-- 预约时间段 -->
				<view class="time-box" v-else>
					<block v-for="(item, _index) in timeArr" :key="_index">
						<view class="item">
							<view class="item-box" :class="{ disable: item.disable || item.isInclude, active: item.time == timeQuanBegin || item.time == timeQuanEnd }" @click="handleSelectQuantum(_index, item)">
								<!-- :style="{ color: item.time == timeQuanBegin || item.time == timeQuanEnd ? selectedItemColor : '#333' }" -->
								<text>{{ item.time }}</text>
								<text class="all">{{ item.disable ? disableText : undisableText }}</text>
							</view>
						</view>
					</block>
				</view>
			</view>
			<!-- <view class="bottom">
					<view class="show-time" v-if="!isMultiple && !isSection">
						<text>预约时间：</text>
						<text class="color-base-text">{{ orderDateTime }}</text>
					</view>
					<button form-type="submit" type="primary" size="mini" class="submit-btn" @click="handleSubmit">确认预约</button>
				</view> -->
		</view>
		<!-- <view class="yuyue-date-desc">如不想线上预约，可致电商家电话预约</view> -->
		<!-- </uni-popup> -->
	</view>
</template>

<script>
	// 插件地址：https://ext.dcloud.net.cn/plugin?id=3593
	import {
		initData,
		initTime,
		timeStamp,
		currentTime,
		strFormat,
		weekDate
	} from './yuyue-date.js';
	export default {
		name: 'times',
		model: {
			prop: 'showPop',
			event: 'change'
		},
		props: {
			isMultiple: {
				//是否多选
				type: Boolean,
				default: false
			},
			isSection: {
				//预约时间段
				type: Boolean,
				default: false
			},
			advanceTime: {
				//提前预约时间
				type: [String, Number],
				default: "0"
			},
			disableText: {
				//禁用显示的文本
				type: String,
				default: '已约满'
			},
			undisableText: {
				//未禁用显示的文本
				type: String,
				default: '可预约'
			},
			timeInterval: {
				// 时间间隔，小时为单位
				type: String,
				default: "1"
			},
			selectedTabColor: {
				// 日期栏选中的颜色
				type: String,
				default: '#303133'
			},
			selectedItemColor: {
				// 时间选中的颜色
				type: String,
				default: '#D50AEF'
			},
			beginTime: {
				type: String,
				default: '09:00'
			},
			endTime: {
				type: String,
				default: '19:00'
			},
			appointTime: {
				// 预约的时间
				type: Array,
				default () {
					return [];
				}
			},
			disableTimeSlot: {
				// 预约开始和结束时间，来禁用时间段
				type: Object,
				default () {
					return {};
				}
			},
			disableWeek: {
				// 限制周几不可以预约
				type: Array,
				default () {
					return [];
				}
			}
		},
		watch: {
			appointTime: {
				handler(val) {
					if (val && val.length) {
						this.initOnload();
					}
				}
			},
			beginTime: function(nVal, oVal) {
				this.initOnload();
				this.handleSubmit();
			},
			endTime: function(nVal, oVal) {
				this.initOnload();
				this.handleSubmit();
			},
		},
		data() {
			return {
				pickerStartDay: '', // 指定开始日期
				pickerEndDay: '', // 指定结束日期
				orderDateTime: '暂无选择', // 选中时间
				orderTimeArr: {}, //多选的时间
				dateArr: [], //日期数据
				timeArr: [], //时间数据
				nowDate: '', // 当前日期
				dateActive: 0, //选中的日期索引
				timeActive: 0, //选中的时间索引
				timeQuanBeginIndex: 0, //时间段开始的下标
				selectDate: '', //选择的日期
				selectTime: '', //选择的时间
				timeQuanBegin: '', //时间段开始时间
				timeQuanEnd: '' //时间段结束时间
			};
		},
		created(props) {
			this.selectDate = this.nowDate = currentTime().date;
			this.pickerStartDay = currentTime().year + '-' + currentTime().date;
			const now = new Date(this.pickerStartDay).getTime(); //获取当前日期的时间戳
			let timeStr = 3600 * 24 * 1000; //一天的时间戳
			let day = 90; // 未来3个月
			this.pickerEndDay = timeStamp(now + timeStr * day).allDate;
			this.initOnload();
			this.dateArr = initData(); // 日期栏初始化
		},
		methods: {
			open() {
				this.$refs.timePopup.open();
			},
			close() {
				this.$refs.timePopup.close();
			},
			// 指定时间
			change(e) {
				let date = e.split("-");
				date = date[1] + "-" + date[2];
				if (this.disableWeek.length && date >= weekDate()[0] && date <= weekDate()[1]) {
					let weekBox = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
					let index = new Date(e).getDay();
					if (this.disableWeek.indexOf(weekBox[index]) != -1) {
						uni.showToast({
							title: weekBox[index] + '不可以预约',
							icon: 'none'
						})
						return false;
					}
				}
				this.initOnload(e);
				this.dateArr = initData(e); // 日期栏初始化
				this.selectDateEvent(0, this.dateArr[0]);
			},
			initOnload(appointedDay) {
				this.timeArr = initTime(this.beginTime, this.endTime, parseFloat(this.timeInterval)); //时间选项初始化
				this.timeQuanBegin = this.timeQuanEnd = '';
				let isFullTime = true;
				this.timeArr.forEach((item, index) => {
					// 判断默认是不能选择的周，则都禁止选中
					if (this.disableWeek.length && this.selectDate >= weekDate()[0] && this.selectDate <=
						weekDate()[1]) {
						let weekBox = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
						let date = currentTime().year + '-' + this.selectDate;
						let index = new Date(date).getDay();
						if (this.disableWeek.indexOf(weekBox[index]) != -1) {
							item.disable = true;
						}
					}

					//判断是当前这一天，选中时间小于当前时间则禁用
					if (this.selectDate == this.nowDate && currentTime().time > item.time) {
						item.disable = true;
					}

					// 将提前预约的时间禁用 advanceTime
					var advTime = new Date(new Date().setMinutes(new Date().getMinutes() + parseInt(this.advanceTime) * 60));
					var advTimeStr = strFormat(advTime.getHours()) + ":" + strFormat(advTime.getMinutes()) + ":" + strFormat(advTime.getSeconds());
					var advTimeStr1 = strFormat(advTime.getMonth() + 1) + "-" + strFormat(advTime.getDate());
					if (this.selectDate == advTimeStr1 && advTimeStr > item.time || advTimeStr1 > this.selectDate) {
						item.disable = true;
					}

					// 将预约的时间禁用
					this.appointTime.forEach(t => {
						let [date, time] = t.split(' ');
						time = time.slice(0, -3);

						if (date == currentTime().year + '-' + this.selectDate && item.time == time ||
							date == currentTime().year + '-' + advTimeStr1 && item.time == time) {
							item.disable = true;
						}
					});

					// 禁用时间段
					const cur_time = `${this.selectDate} ${item.time}`;
					const {
						begin_time,
						end_time
					} = this.disableTimeSlot;
					if (begin_time && end_time && (begin_time <= cur_time && cur_time <= end_time)) {
						item.disable = true;
					}

					// 判断是否当前日期时间都被预约
					if (!item.disable) {
						isFullTime = false;
					}
					this.isSection && (item.isInclude = false);
				});

				this.orderDateTime = isFullTime ? '暂无选择' : this.selectDate;
				this.timeActive = -1;
				for (let i = 0, len = this.timeArr.length; i < len; i++) {
					if (!this.timeArr[i].disable) {
						this.orderDateTime = {
							data: `${this.selectDate}`,
							time: `${this.timeArr[i].time}`
						};
						this.timeActive = i;
						return;
					}
				}
			},

			// 日期选择事件
			selectDateEvent(index, item) {
				if (this.disableWeek.length && item.date >= weekDate()[0] && item.date <= weekDate()[1]) {
					let weekBox = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
					let index = new Date(item.timeStamp).getDay();
					if (this.disableWeek.indexOf(weekBox[index]) != -1) {
						uni.showToast({
							title: weekBox[index] + '不可以预约',
							icon: 'none'
						})
						return false;
					}
				}

				this.dateActive = index;
				this.selectDate = item.date;
				this.initOnload();
				this.handleSubmit();
			},

			// 时间选择事件
			selectTimeEvent(index, item) {
				if (item.disable) return;
				if (this.isMultiple) {
					item.isActive = !item.isActive;
					this.timeArr = this.timeArr.slice();
					this.orderTimeArr[this.selectDate] = this.timeArr.reduce((prev, cur) => {
						cur.isActive && prev.push(cur.time);
						return prev;
					}, []);
				} else {
					this.timeActive = index;
					this.selectTime = item.time;
					this.orderDateTime = {
						data: `${this.selectDate}`,
						time: `${item.time}`
					};
				}
				this.handleSubmit();
			},

			// 选择时间段
			handleSelectQuantum(index, item) {
				if (item.disable) return;

				function clearTime() {
					this.timeQuanBeginIndex = index;
					this.timeQuanBegin = item.time;
					this.timeQuanEnd = '';
				}

				if (!this.timeQuanBegin) {
					clearTime.call(this);
					return;
				}
				if (!this.timeQuanEnd && this.timeQuanBegin) {
					let isDisble = false;
					let start = this.timeQuanBeginIndex;
					let end = index;
					start > end && ([start, end] = [end, start]);
					for (let i = start + 1; i < end; i++) {
						if (this.timeArr[i].disable) {
							isDisble = true;
							clearTime.call(this);
							return;
						}
					}
					if (!isDisble) {
						for (let i = start + 1; i < end; i++) {
							this.timeArr[i].isInclude = true;
						}
					}
					this.timeQuanEnd = item.time;
					return;
				}

				if (this.timeQuanBegin && this.timeQuanEnd) {
					this.timeArr.forEach(t => {
						t.isInclude = false;
					});
					clearTime.call(this);
				}
			},
			handleChange() {
				this.timeQuanBegin > this.timeQuanEnd && ([this.timeQuanBegin, this.timeQuanEnd] = [this.timeQuanEnd, this.timeQuanBegin]);
			},
			handleSubmit() {
				if (this.isSection) {
					this.handleChange();
					this.$emit('change', {
						beginTime: `${this.selectDate} ${this.timeQuanBegin}`,
						endTime: `${this.selectDate} ${this.timeQuanEnd}`
					});
					return;
				}

				if (this.isMultiple) {
					let time = [];
					for (let date in this.orderTimeArr) {
						this.orderTimeArr[date].forEach(item => {
							time.push(`${date} ${item}`);
						});
					}
					this.$emit('change', time);
				} else {
					// this.$emit('change', currentTime().year + '-' + this.orderDateTime);
					this.$emit('change', {
						date: currentTime().year + '-' + this.orderDateTime.data,
						time: this.orderDateTime.time
					});
				}
			}
		}
	};
</script>
<style lang="scss" scoped>
	@import './yuyue-date.scss';
</style>