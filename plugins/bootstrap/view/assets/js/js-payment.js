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

