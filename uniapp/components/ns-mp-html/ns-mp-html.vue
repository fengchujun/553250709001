<template>
	<view class="mp-html">
		<mp-html :content="content"></mp-html>
	</view>
</template>

<script>
	export default {
		props: {
		    content: {
		        type: String,
		        default: '',
		    }
		},
	}
</script>

<style lang="scss">
	.mp-html{
		/deep/ img{
			width:100% !important;
			display:block;
		}
	}
</style>