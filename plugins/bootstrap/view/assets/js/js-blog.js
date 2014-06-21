//------------------------------
//About Page
//------------------------------
$(function() {
	$('.chart').easyPieChart({
		animate: 2000,
		barColor:   "#ff6633",
		trackColor: "#e9f3f7",
		scaleColor: false,
		lineCap: "square",
		lineWidth: 1,								
		size:50
	});
	$('.chart2').easyPieChart({
		animate: 2000,
		barColor:   "#66cccc",
		trackColor: "#e9f3f7",
		scaleColor: false,
		lineCap: "square",
		lineWidth: 1,								
		size:50
	});
	$('.chart3').easyPieChart({
		animate: 2000,
		barColor:   "#72bf66",
		trackColor: "#e9f3f7",
		scaleColor: false,
		lineCap: "square",
		lineWidth: 1,								
		size:50
	});
	
});


function updateAbOver(){
	$(document).ready(function() {
		$aboverw = $('.abover').innerWidth();
		$aboverh = $('.abover').innerHeight();
		
		$abover2w = $('.abover-off').innerWidth();
		$abover2h = $('.abover-off').innerHeight();
		
		$('.abbg').css({'width':$aboverw +'px','height':$aboverh +'px','left':-$aboverw+'px'});
		$('.socials-container').css({'left':$aboverw/2-28+'px','top':$aboverh/2-9+'px'});
		
		//position of mouseover circle from blogpage
		$('.blogpost-hover').css({'left':$aboverw/2-25+'px','top':$aboverh/2-25+'px'});
		
		$('.blogpost-hover2').css({'left':$abover2w/2-25+'px','top':$abover2h/2-25+'px'});
		
		
		
		
		$( ".abover" )
			.mouseenter(function() {
				$(this).find('.abbg').stop().animate({'left':0+'px'},500);

			})
			.mouseleave(function() {
				$(this).find('.abbg').stop().animate({'left':-$aboverw+'px'},500);
			
			});	
		
		
	
	});
}

updateAbOver();

//------------------------------
//ON RESIZE
//------------------------------
$(window).resize(function() {
	updateAbOver();
});


//jQuery(function ($) {
//	$("a").tooltip()
//});





//------------------------------
//Skills ( Speciality )animations
//------------------------------
$(document).ready(function(){
	$('.pbar1 , .pbar2 ,.pbar3 , .pbar4').css({'width':35 +'%'})
	$('.pbar1').animate({'width':90 +'%'}, 500);
	$('.pbar2').animate({'width':100 +'%'}, 1000);
	$('.pbar3').animate({'width':85 +'%'}, 1500);
	$('.pbar4').animate({'width':95 +'%'}, 2000);
});









//------------------------------
//Picker
//------------------------------
jQuery(function() {
	jQuery( "#datepicker,#datepicker2,#datepicker3,#datepicker4,#datepicker5,#datepicker6,#datepicker7,#datepicker8" ).datepicker();
});

//------------------------------
//Load Animo
//------------------------------
	function errorMessage(){
		$('.loginbox').animo( { animation: 'tada' } );
	}
	
//------------------------------
//Custom Select
//------------------------------
jQuery(document).ready(function(){
	jQuery('.mySelectBoxClass').customSelect();

	/* -OR- set a custom class name for the stylable element */
	//jQuery('.mySelectBoxClass').customSelect({customClass:'mySelectBoxClass'});
});

function mySelectUpdate(){
	setTimeout(function (){
		$('.mySelectBoxClass').trigger('update');
	}, 200);
}

$(window).resize(function() {
	mySelectUpdate();
});

//------------------------------
//Nicescroll
//------------------------------
jQuery(document).ready(function() {

	var nice = jQuery("html").niceScroll({
		cursorcolor:"#ccc",
		//background:"#fff",	
		cursorborder :"0px solid #fff",			
		railpadding:{top:0,right:0,left:0,bottom:0},
		cursorwidth:"5px",
		cursorborderradius:"0px",
		cursoropacitymin:0,
		cursoropacitymax:0.7,
		boxzoom:true,
		autohidemode:false
	});  
	
	jQuery(".hotelstab").niceScroll({horizrailenabled:false});
	jQuery(".flightstab").niceScroll({horizrailenabled:false});
	jQuery(".vacationstab").niceScroll({horizrailenabled:false});
	jQuery(".carstab").niceScroll({horizrailenabled:false});
	jQuery(".cruisestab").niceScroll({horizrailenabled:false});
	jQuery(".flighthotelcartab").niceScroll({horizrailenabled:false});
	jQuery(".flighthoteltab").niceScroll({horizrailenabled:false});
	jQuery(".flightcartab").niceScroll({horizrailenabled:false});
	jQuery(".hotelcartab").niceScroll({horizrailenabled:false});
	

	jQuery('html').addClass('no-overflow-y');
	
});



//------------------------------
//slider parallax effect
//------------------------------
	  jQuery(document).ready(function($){
		var $scrollTop;
		var $headerheight;
		var $loggedin = false;
			
		if($loggedin == false){
		  $headerheight = $('.navbar-wrapper2').height() - 20;
		} else {
		  $headerheight = $('.navbar-wrapper2').height() + 100;
		}
		
		
		$(window).scroll(function(){
		  var $iw = $('body').innerWidth();
		  $scrollTop = $(window).scrollTop();	   
			  if ( $iw < 992 ) {
			 
			  }
			  else{
			   $('.navbar-wrapper2').css({'min-height' : 110-($scrollTop/2) +'px'});
			  }
		  $('#dajy').css({'top': ((- $scrollTop / 5)+ $headerheight)  + 'px' });
		  //$(".sboxpurple").css({'opacity' : 1-($scrollTop/300)});
		  $(".scrolleffect").css({'top': ((- $scrollTop / 5)+ $headerheight) + 50  + 'px' });
		  $(".tp-leftarrow").css({'left' : 20-($scrollTop/2) +'px'});
		  $(".tp-rightarrow").css({'right' : 20-($scrollTop/2) +'px'});
		});
		
	  });

	  
	  
//------------------------------
//On scroll animations
//------------------------------
		jQuery(window).scroll(function(){            
			var $iw = $('body').innerWidth();
			
			if(jQuery(window).scrollTop() != 0){
				jQuery('.mtnav').stop().animate({top: '0px'}, 500);
				jQuery('.logo').stop().animate({width: '100px'}, 100);
			}       
			else {	
				 if ( $iw < 992 ) {
				  }
				  else{
				   jQuery('.mtnav').stop().animate({top: '30px'}, 500);
				  }
				jQuery('.logo').stop().animate({width: '120px'}, 100);		
			}
			
			//Social
 			if(jQuery(window).scrollTop() >= 700){
				jQuery('.social1').stop().animate({top:'0px'}, 100);
				
				setTimeout(function (){
					jQuery('.social2').stop().animate({top:'0px'}, 100);
				}, 100);
				
				setTimeout(function (){
					jQuery('.social3').stop().animate({top:'0px'}, 100);
				}, 200);
				
				setTimeout(function (){
					jQuery('.social4').stop().animate({top:'0px'}, 100);
				}, 300);
				
				setTimeout(function (){
					jQuery('.gotop').stop().animate({top:'0px'}, 200);
				}, 400);				
				
			}       
			else {
				setTimeout(function (){
					jQuery('.gotop').stop().animate({top:'100px'}, 200);
				}, 400);	
				setTimeout(function (){
					jQuery('.social4').stop().animate({top:'-120px'}, 100);				
				}, 300);
				setTimeout(function (){
					jQuery('.social3').stop().animate({top:'-120px'}, 100);		
				}, 200);	
				setTimeout(function (){
				jQuery('.social2').stop().animate({top:'-120px'}, 100);		
				}, 100);	

				jQuery('.social1').stop().animate({top:'-120px'}, 100);			

			}
			
			
		});	

		
//------------------------------
//Add rooms
//------------------------------

function addroom2(){
	$('.room2').addClass('block');
	$('.room2').removeClass('none');
	$('.addroom1').removeClass('block');
	$('.addroom1').addClass('none');
	
}
function removeroom2(){
	$('.room2').addClass('none');
	$('.room2').removeClass('block');
	
	$('.addroom1').removeClass('none');
	$('.addroom1').addClass('block');
}
function addroom3(){
	$('.room3').addClass('block');
	$('.room3').removeClass('none');
	
	$('.addroom2').removeClass('block');
	$('.addroom2').addClass('none');
}
function removeroom3(){
	$('.room3').addClass('none');
	$('.room3').removeClass('block');
	
	$('.addroom2').removeClass('none');
	$('.addroom2').addClass('block');			
}
	
	
//------------------------------
//Popover tooltips
//------------------------------

  $(function (){
	 $("#name").popover({placement:'top', trigger:'hover'});
	 $("#username").popover({placement:'top', trigger:'focus'});
	 $("#email").popover({placement:'top', trigger:'focus'});
  });
		
		
