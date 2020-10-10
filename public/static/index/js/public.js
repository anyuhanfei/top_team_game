// 页面滚动
mui('.mui-scroll-wrapper').scroll({
		indicators: false,
		bounce: false,
		deceleration: 0.0005
})
// 底部导航栏点击跳转
mui('.mui-bar-tab').on('tap', 'a', function () { document.location.href = this.href; });

// 折叠栏（点击展开内容，再点击收缩内容）
$('p.titlt').click(function () {
	if (!$(this).siblings(".con").is(':visible')){
		$("p.titlt").siblings(".con").slideUp();
		$(this).siblings(".con").slideDown();
	} else {
		$(this).siblings(".con").slideUp();
	}
})
// 留言板显示隐藏
$('.signin').click(function () {
	// console.log('11111')
	// $('.signin_show').setAttribute('display','block');
	$('.signin_show').show();
})
$('.signin_show').click(function () {
	$('.signin_show').hide();
})
$('.reply').click(function () {
	$('.reply_show').show();
})
$('.reply_show').click(function () {
	$('.reply_show').hide();
})
// 点击放大图片按钮
$('.clickImg').click(function () {
	var src = $(this).attr('src');
	$('.bigImg').show();
	console.log($(this))
	$('.bigImg').find('img').attr('src',src);
})
$('.bigImg').click(function () {
	$(this).hide();
})
// 点击上传图片	upload(e, i)  e是this   i是img类名后的那个数字
function upload(e, i) {
	console.log(e.files[0],i)
	// var file = e.files[0]
	if (e.files) {
		var reader = new FileReader()
		reader.readAsDataURL(e.files[0]);
		reader.onload = function (e) {
			var src = this.result
			$('.img'+i+'').attr('src', src)
		}
	}
}
// 点击显示提示框
$('.w_toast').click(function () {
	mui.toast('正在开发中... <br/>being developed...');
})
// 时间戳转换年月日时分秒
function daytime(time){
	let time_chuo =time;
	let data_all =new Date(time_chuo * 1000);
	console.log(data_all)
	let y = data_all.getFullYear();
	let m =data_all.getMonth();
	m = m < 10 ? ('0' + m) : m;
	var d = data_all.getDate();
	d = d < 10 ? ('0' + d) : d;
	var h = data_all.getHours();
	h = h < 10 ? ('0' + h) : h;
	var minute = data_all.getMinutes();
	var second = data_all.getSeconds();
	minute = minute < 10 ? ('0' + minute) : minute;
	second = second < 10 ? ('0' + second) : second;
	return y + '-' + m + '-' + d + ' '+ h + ':' + minute + ':' + second;
}
