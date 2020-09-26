var jump = document.referrer
var jump_arr = jump.toString().split('/')
var jump_str = jump_arr[jump_arr.length-1];
console.log(jump_str)
if(jump_str=='index.html'||jump_str=='my.html'||jump_str=='more.html'){
	sessionStorage.setItem('href',jump)
}
	var href_to = sessionStorage.getItem('href')
	$('.mui-icon-left-nav').attr('href',href_to);
	console.log(href_to)