//------------------------------
//Picker
//------------------------------

jQuery(function() {
	jQuery( "#datepicker,#datepicker2,#datepicker3,#datepicker4,#datepicker5,#datepicker6,#datepicker7,#datepicker8,#datepicker9,#datepicker10,#datepicker11,#datepicker12,#datepicker13,#datepicker14,#datepicker15,#datepicker16" ).datepicker();
});


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
		jQuery('.mySelectBoxClass').trigger('update');
	}, 200);
}

jQuery(window).resize(function() {
	mySelectUpdate();
});


//------------------------------
//CaroufredSell
//------------------------------
jQuery(document).ready(function(jQuery){

	jQuery("#foo").carouFredSel({
		width: "100%",
		height: 240,
		items: {
			visible: 5,
			minimum: 1,
			start: 2
		},
		scroll: {
			items: 1,
			easing: "easeInOutQuad",
			duration: 500,
			pauseOnHover: true
		},
		auto: false,
		prev: {
			button: "#prev_btn",
			key: "left"
		},
		next: {
			button: "#next_btn",
			key: "right"
		},				
		swipe: true
	});
	
	
	jQuery("#foo2").carouFredSel({
		width: "100%",
		height: 240,
		items: {
			visible: 5,
			minimum: 1,
			start: 2
		},
		scroll: {
			items: 1,
			easing: "easeInOutQuad",
			duration: 500,
			pauseOnHover: true
		},
		auto: false,				
		prev: {
			button: "#prev_btn2",
			key: "left"
		},
		next: {
			button: "#next_btn2",
			key: "right"
		},				
		swipe: true
	});
	

});



//------------------------------
//Add rooms
//------------------------------
		function addroom2(){
			jQuery('.room2').addClass('block');
			jQuery('.room2').removeClass('none');
			jQuery('.addroom1').removeClass('block');
			jQuery('.addroom1').addClass('none');
			
		}
		function removeroom2(){
			jQuery('.room2').addClass('none');
			jQuery('.room2').removeClass('block');
			
			jQuery('.addroom1').removeClass('none');
			jQuery('.addroom1').addClass('block');
		}
		function addroom3(){
			jQuery('.room3').addClass('block');
			jQuery('.room3').removeClass('none');
			
			jQuery('.addroom2').removeClass('block');
			jQuery('.addroom2').addClass('none');
		}
		function removeroom3(){
			jQuery('.room3').addClass('none');
			jQuery('.room3').removeClass('block');
			
			jQuery('.addroom2').removeClass('none');
			jQuery('.addroom2').addClass('block');			
		}
	

	
	
	
//------------------------------
//Nice Scroll
//------------------------------
		jQuery(document).ready(function() {
		
			var nice = jQuery("html").niceScroll({
				cursorcolor:"#ccc",
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
//Slider parallax effect
//------------------------------
	
jQuery(document).ready(function(jQuery){
var jQueryscrollTop;
var jQueryheaderheight;
var jQueryloggedin = false;
	
if(jQueryloggedin == false){
  jQueryheaderheight = jQuery('.navbar-wrapper2').height() - 20;
} else {
  jQueryheaderheight = jQuery('.navbar-wrapper2').height() + 100;
}


jQuery(window).scroll(function(){
  var jQueryiw = jQuery('body').innerWidth();
  jQueryscrollTop = jQuery(window).scrollTop();	   
	  if ( jQueryiw < 992 ) {
	 
	  }
	  else{
	   jQuery('.navbar-wrapper2').css({'min-height' : 110-(jQueryscrollTop/2) +'px'});
	  }

  //jQuery(".sboxpurple").css({'opacity' : 1-(jQueryscrollTop/300)});
  jQuery(".scrolleffect").css({'top': ((- jQueryscrollTop / 5)+ jQueryheaderheight) + 30  + 'px' });
  jQuery(".tp-leftarrow").css({'left' : 20-(jQueryscrollTop/2) +'px'});
  jQuery(".tp-rightarrow").css({'right' : 20-(jQueryscrollTop/2) +'px'});
});

});
	
	
	
//------------------------------
//SCROLL ANIMATIONS
//------------------------------	

	jQuery(window).scroll(function(){            
		var jQueryiw = jQuery('body').innerWidth();
		
		if(jQuery(window).scrollTop() != 0){
			jQuery('.mtnav').stop().animate({top: '0px'}, 500);
			jQuery('.logo').stop().animate({width: '100px'}, 100);

		}       
		else {
			 if ( jQueryiw < 992 ) {
			  }
			  else{
			   jQuery('.mtnav').stop().animate({top: '30px'}, 500);
			  }
			
			
			jQuery('.logo').stop().animate({width: '120px'}, 100);		
	
		}
		
		
		//Social
		if(jQuery(window).scrollTop() >= 300){
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
//ROLLOVER
//------------------------------
	
var theSide = 'marginLeft';
var options = {};
options[theSide] = jQuery('.one').width()/2-15;
jQuery(".one")
	.mouseenter(function() {
		jQuery(".mhover", this).addClass( "block" );
		jQuery(".mhover", this).removeClass( "none" );
		jQuery(".icon", this).stop().animate(options, 100);
	})
jQuery(".one").mouseleave(function() {
		jQuery(".mhover", this).addClass( "none" );
		jQuery(".mhover", this).removeClass( "block" );
		jQuery(".icon", this).stop().animate({marginLeft:"0px"}, 100);
	});



	
	
	
//------------------------------
//TABS CHANGE
//------------------------------
jQuery(document).ready(function(){

	function mySelectUpdate(){
		setTimeout(function (){
			jQuery('.mySelectBoxClass').trigger('update');
		}, 500);
	}
	mySelectUpdate();
	jQuery('.hotelstab').removeClass('none');
	
	jQuery( "#optionsRadios1" ).click(function() {
		jQuery('.hotelstab').removeClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');	
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');								
		mySelectUpdate();
	});
	jQuery( "#optionsRadios2" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').removeClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');	
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');	
		mySelectUpdate();
	});						
	jQuery( "#optionsRadios3" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').removeClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');	
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios4" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').removeClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios5" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').removeClass('none');
		jQuery('.flighthotelcartab').addClass('none');
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios6" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').removeClass('none');
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');									
		mySelectUpdate();
	});			
	jQuery( "#optionsRadios7" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');
		jQuery('.flighthoteltab').removeClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios8" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').removeClass('none');								
		jQuery('.hotelcartab').addClass('none');									
		mySelectUpdate();
	});		
	jQuery( "#optionsRadios9" ).click(function() {
		jQuery('.hotelstab').addClass('none');
		jQuery('.flightstab').addClass('none');
		jQuery('.vacationstab').addClass('none');
		jQuery('.carstab').addClass('none');
		jQuery('.cruisestab').addClass('none');
		jQuery('.flighthotelcartab').addClass('none');
		jQuery('.flighthoteltab').addClass('none');								
		jQuery('.flightcartab').addClass('none');								
		jQuery('.hotelcartab').removeClass('none');									
		mySelectUpdate();
	});	

});