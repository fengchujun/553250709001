
var config = {
	// api请求地址 https://abc.com
	baseUrl: 'https://test.ayatimes.com',
	// 图片域名 https://abc.com
	imgDomain: 'https://test.ayatimes.com',
	// H5端域名 默认部署 https://abc.com/h5 独立部署 https://abc.com
	h5Domain: '{{$h5Domain}}',
	// 腾讯地图key 后台设置->其他设置->地图配置
	mpKey: '{{$mpKey}}',
	//客服地址 wss://abc.com/wss
	webSocket: '{{$webSocket}}',
	//本地端主动给服务器ping的时间, 0 则不开启 , 单位秒
	pingInterval: 1500,
	// 版本号
	version: '5.5.3',
	// 缓存前缀
	storagePrefix:'h5_',
};

export default config;