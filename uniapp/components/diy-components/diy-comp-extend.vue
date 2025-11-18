<template>
	<view :style="value.pageStyle">
	<view></view>
	</view>
</template>

<script>
// 自定义扩展组件
export default {
	name: 'diy-comp-extend',
	props: {
		value: {
			type: Object,
			default: () => {
				return {};
			}
		}
	},
	data() {
		return {};
	},
	computed: {},
	created() {},
	methods: {}
};
</script>

<style></style>
