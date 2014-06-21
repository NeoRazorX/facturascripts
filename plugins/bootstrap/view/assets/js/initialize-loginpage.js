		$(document).ready(function($){
			function onBgresize() {
				var $gfwidth = window.innerWidth;
				var $gfheight = window.innerHeight;
				
				var $loginw = $('.login-wrap').width();
				var $loginh = $('.login-wrap').height();
				
				$('.login-fullwidith').css({'width': $gfwidth +'px', 'height': $gfheight +'px'});
				
				$('.login-wrap').css({'margin-left': $gfwidth/2-$loginw/2 +'px', 'margin-top': $gfheight/2-$loginh/2 +'px'});
				
			}
			onBgresize();
			$(window).resize(function() {
				onBgresize();
			});
		});		