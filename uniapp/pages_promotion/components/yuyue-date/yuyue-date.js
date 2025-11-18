//字符串拼接
export function strFormat(str) {
	return str < 10 ? `0${str}` : str
}
// 获取当前时间
export function currentTime() {
	const myDate = new Date();
	const year = myDate.getFullYear()
	const m = myDate.getMonth() + 1;
	const d = myDate.getDate();
	// const date = year + '-' + strFormat(m) + '-' + strFormat(d); // 隐藏年
	const date = strFormat(m) + '-' + strFormat(d);

	const hour = myDate.getHours()
	const min = myDate.getMinutes()
	const secon = myDate.getSeconds()
	const time = strFormat(hour) + ':' + strFormat(min) + ':' + strFormat(secon);
	return {
		year,
		date,
		time
	}
}

//时间戳转日期
export function timeStamp(time) {
	const dates = new Date(time)
	const year = dates.getFullYear()
	const month = dates.getMonth() + 1
	const date = dates.getDate()
	const day = dates.getDay()
	const hour = dates.getHours()
	const min = dates.getMinutes()
	const days = ['日', '一', '二', '三', '四', '五', '六']
	return {
		allDate: `${year}/${strFormat(month)}/${strFormat(date)}`,
		date: `${strFormat(month)}-${strFormat(date)}`, //返回的日期 07-01，${strFormat(year)}-${strFormat(month)}-${strFormat(date)}
		day: `周${days[day]}`, //返回的礼拜天数  星期一
		hour: strFormat(hour) + ':' + strFormat(min) // + ':00' //返回的时钟 08:00
	}
}

//获取最近7天的日期和礼拜天数
export function initData(appointedDay = '') {
	const time = []
	const date = appointedDay ? new Date(appointedDay) : new Date()

	const now = date.getTime() //获取当前日期的时间戳
	let timeStr = 3600 * 24 * 1000 //一天的时间戳
	let obj = {
		0: "今天",
		1: "明天",
		2: "后天"
	}
	for (let i = 0; i < 7; i++) {
		const timeObj = {}
		timeObj.date = timeStamp(now + timeStr * i).date //保存日期
		timeObj.timeStamp = now + timeStr * i //保存时间戳
		timeObj.week = appointedDay == '' ? (obj[i] ?? timeStamp(now + timeStr * i).day) : timeStamp(now + timeStr * i).day
		time.push(timeObj)
	}
	return time
}

//时间数组
export function initTime(startTime = '09:00', endTime = '18:30', timeInterval = 1) {
	const time = []
	const date = timeStamp(Date.now()).allDate
	const startDate = `${date} ${startTime}`
	const endDate = `${date} ${endTime}`
	const startTimeStamp = new Date(startDate).getTime()
	const endTimeStamp = new Date(endDate).getTime()
	const timeStr = 3600 * 1000 * timeInterval
	for (let i = startTimeStamp; i <= endTimeStamp; i = i + timeStr) {
		const timeObj = {}
		timeObj.time = timeStamp(i).hour
		timeObj.disable = false
		time.push(timeObj)
	}
	return time
}

export function weekDate(){
	var now = new Date(); //当前日期 
	var nowDayOfWeek = now.getDay(); //今天本周的第几天 
	var nowDay = now.getDate(); //当前日 
	var nowMonth = now.getMonth(); //当前月 
	var nowYear = now.getYear(); //当前年 
	
	var weekStartDate = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek + 1); 
	var weekEndDate = new Date(nowYear, nowMonth, nowDay + (7 - nowDayOfWeek)); 
	
	var arr = [];
	arr[0] = strFormat(weekStartDate.getMonth()+1) + '-' + strFormat(weekStartDate.getDate());
	arr[1] = strFormat(weekEndDate.getMonth()+1) + '-' + strFormat(weekEndDate.getDate());
	return arr;
}
