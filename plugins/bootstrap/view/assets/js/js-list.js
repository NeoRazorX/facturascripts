//------------------------------
//Picker
//------------------------------
jQuery(function() {
	jQuery( "#datepicker,#datepicker2,#datepicker3,#datepicker4,#datepicker5,#datepicker6,#datepicker7,#datepicker8" ).datepicker();
});


//------------------------------
//Counter
//------------------------------

jQuery(function(jQuery) {
	jQuery('.countprice').countTo({
		from: 5,
		to: 36,
		speed: 1000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});
	jQuery('.counthotel').countTo({
		from: 1,
		to: 53,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});			
});



//------------------------------
//Custom select
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
//Niciscroll
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
//slider parallax effect
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
  jQuery('#dajy').css({'top': ((- jQueryscrollTop / 5)+ jQueryheaderheight)  + 'px' });
  //jQuery(".sboxpurple").css({'opacity' : 1-(jQueryscrollTop/300)});
  jQuery(".scrolleffect").css({'top': ((- jQueryscrollTop / 5)+ jQueryheaderheight) + 50  + 'px' });
  jQuery(".tp-leftarrow").css({'left' : 20-(jQueryscrollTop/2) +'px'});
  jQuery(".tp-rightarrow").css({'right' : 20-(jQueryscrollTop/2) +'px'});
});

});


//------------------------------
//Animations for this page
//------------------------------
jQuery(document).ready(function(){
	jQuery('.tip-arrow').css({'bottom':1+'px'});
	jQuery('.tip-arrow').animate({'bottom':-9+'px'},{ duration: 700, queue: false });	
	
	jQuery('.bookfilters').css({'margin-top':-40+'px'});
	jQuery('.bookfilters').animate({'margin-top':0+'px'},{ duration: 1000, queue: false });	
	
	jQuery('.topsortby').css({'opacity':0});
	jQuery('.topsortby').animate({'opacity':1},{ duration: 1000, queue: false });	

	jQuery('.itemscontainer').css({'opacity':0});
	jQuery('.itemscontainer').animate({'opacity':1},{ duration: 1000, queue: false });			
});





//------------------------------
//Scroll animations
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
	if(jQuery(window).scrollTop() >= 900){
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
//Change Tabs
//------------------------------
jQuery(document).ready(function(){

	function mySelectUpdate(){
		setTimeout(function (){
			jQuery('.mySelectBoxClass').trigger('update');
		}, 500);
	}
	mySelectUpdate();
	
	jQuery('.hotelstab2').removeClass('none');
	
	jQuery( "#optionsRadios1" ).click(function() {
		jQuery('.hotelstab2').removeClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');	
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');								
		mySelectUpdate();
	});
	jQuery( "#optionsRadios2" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').removeClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');	
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');	
		mySelectUpdate();
	});						
	jQuery( "#optionsRadios3" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').removeClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');	
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios4" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').removeClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios5" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').removeClass('none');
		jQuery('.flighthotelcartab2').addClass('none');
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios6" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').removeClass('none');
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');									
		mySelectUpdate();
	});			
	jQuery( "#optionsRadios7" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');
		jQuery('.flighthoteltab2').removeClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').addClass('none');									
		mySelectUpdate();
	});	
	jQuery( "#optionsRadios8" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').removeClass('none');								
		jQuery('.hotelcartab2').addClass('none');									
		mySelectUpdate();
	});		
	jQuery( "#optionsRadios9" ).click(function() {
		jQuery('.hotelstab2').addClass('none');
		jQuery('.flightstab2').addClass('none');
		jQuery('.vacationstab2').addClass('none');
		jQuery('.carstab2').addClass('none');
		jQuery('.cruisestab2').addClass('none');
		jQuery('.flighthotelcartab2').addClass('none');
		jQuery('.flighthoteltab2').addClass('none');								
		jQuery('.flightcartab2').addClass('none');								
		jQuery('.hotelcartab2').removeClass('none');									
		mySelectUpdate();
	});	

});







//------------------------------
// List Hover Animations
//------------------------------
jQuery(document).ready(function(jQuery){
	


	function StartAnime2() {
		var jQuerywlist = jQuery('.listitem').width();
		var jQueryhlist = jQuery('.listitem').height();

		jQuery('.liover').css({
			"width":100+"%",
			"height":100+"%",
			"background-color":"#ff9900",
			"position":"absolute",
			"top":0+"px", 
			"left":jQuerywlist+"px", 
			"opacity":0.5, 
		});
		jQuery('.fav-icon').css({
			"top":jQueryhlist/2-11+"px",
			"left":-25+"px",
		});
		jQuery('.book-icon').css({
			"top":jQueryhlist/2-11+"px",
			"left":-25+"px",
		});
		
		jQuery( ".listitem" )
			.mouseenter(function() {
				jQuery(this).find('.liover').stop().animate({ "left":10+"%","top":10+"%","width":80+"%","height":80+"%"  });
				jQuery(this).find('.book-icon').stop().animate({ "left":jQuerywlist/2+18+"px" });
				jQuery(this).find('.fav-icon').stop().animate({ "left":jQuerywlist/2-42+"px" },{ duration: 1000, queue: false });


			})
			.mouseleave(function() {
				jQuery(this).find('.liover').stop().animate({ "left":jQuerywlist+"px","top":0+"px","width":100+"%","height":100+"%"  });
				jQuery(this).find('.book-icon').stop().animate({ "left":-25+"px" },{ duration: 1000, queue: false });
				jQuery(this).find('.fav-icon').stop().animate({ "left":-25+"px" });
			
			});	
		
	}
	
	StartAnime2();
	
	jQuery(window).resize(function() {
		StartAnime2();					
	});
	


});

				












