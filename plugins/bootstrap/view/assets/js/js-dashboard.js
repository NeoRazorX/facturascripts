


//------------------------------
//Animations
//------------------------------
$('.dashboard-right').css({'margin-right':-100+'px'});
$('.dashboard-right').animate({'margin-right':0+'px'}, 1500);

$('.dashboard-left').css({'top':-100+'px'});
$('.dashboard-left').animate({'top':0+'px'}, 1500);


function updateGraph(){
	$(function() {
		/** This code runs when everything has been loaded on the page */
		/* Inline sparklines take their values from the contents of the tag */
		$('.inlinesparkline').sparkline(); 

		/* Sparklines can also take their values from the first argument 
		passed to the sparkline() function */
		var myvalues = [10,8,5,7,4,4,1];
		$('.dynamicsparkline').sparkline(myvalues);

		/* The second argument gives options such as chart type */
		$('.dynamicbar').sparkline(myvalues, {type: 'bar', barColor: 'green'} );

		/* Use 'html' instead of an array of values to pass options 
		to a sparkline with data in the tag */
		$('.inlinebar').sparkline('html', {type: 'bar', barColor: 'red'} );
		
		
		$(".stats").sparkline([0,0,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000,2100,1700,2020,2050,1800,1850,1100,1400,1750,1500], {
			type: 'line',
			width: '100%',
			height: '260px',
			lineColor: '#17408c',
			fillColor: '#ebf5f9',
			spotColor: '#17408c',
			minSpotColor: '#17408c',
			maxSpotColor: '#17408c',
			highlightSpotColor: '#189300',
			highlightLineColor: '#72bf66',
			spotRadius: 4,
			chartRangeMin: 5,
			chartRangeMax: 10,
			chartRangeMinX: 5,
			chartRangeMaxX: 5,
			normalRangeMin: 5,
			normalRangeMax: 5,
			normalRangeColor: '#ebf5f9',
			drawNormalOnTop: true
			
			});
			
		$(".stats2").sparkline([0,0,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000,2100,1700,2020,2050,1800,1850,1100,1400,1750,1500,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000,2100,1700,2020,2050,1800,1850,1100,1400,1750,1500,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000,2100,1700,2020,2050,1800,1850,1100,1400,1750,1500,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000,2100,1700,2020,2050,1800,1850,1100,1400,1750,1500,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000], {
			type: 'bar',
			width: '100%',
			height: '100px',
			type: 'bar',
			barWidth: 10,
			barColor: '#66CCCC',
			zeroColor: '#08d30b'
			
			});
			
		$(".cvisits").sparkline([0,0,1000,1250,3000,2500,2100,2500,2450,4000,2200,2300,2000,2100,1700,2020,2050,1800,1850,1100,1400,1750,1500], {
			type: 'line',
			width: '90px',
			height: '25px',
			lineColor: '#4bb0d2',
			fillColor: '#ebf5f9',
			spotColor: '#17408c',
			minSpotColor: '#17408c',
			maxSpotColor: '#17408c',
			highlightSpotColor: '#189300',
			highlightLineColor: '#72bf66',
			spotRadius: 0,
			chartRangeMin: 5,
			chartRangeMax: 10,
			chartRangeMinX: 5,
			chartRangeMaxX: 5,
			normalRangeMin: 5,
			normalRangeMax: 5,
			normalRangeColor: '#ebf5f9',
			drawNormalOnTop: true								
		});
		
		$(".cpreview").sparkline([1,21,17,20,50,18,16,1,5,20,14,12,11,25,7,3,35,23,16,12,7,16,25], {
			type: 'line',
			width: '90px',
			height: '25px',
			lineColor: '#4bb0d2',
			fillColor: '#ebf5f9',
			spotColor: '#17408c',
			minSpotColor: '#17408c',
			maxSpotColor: '#17408c',
			highlightSpotColor: '#189300',
			highlightLineColor: '#72bf66',
			spotRadius: 0,
			chartRangeMin: 5,
			chartRangeMax: 10,
			chartRangeMinX: 5,
			chartRangeMaxX: 5,
			normalRangeMin: 5,
			normalRangeMax: 5,
			normalRangeColor: '#ebf5f9',
			drawNormalOnTop: true								
		});
		
		$(".cvisits2").sparkline([8,9,10,8,7,8,9,7,8,7,9,8,7,8,7,8,9,10,8,9,8,10,9], {
			type: 'line',
			width: '90px',
			height: '15px',
			lineColor: '#4bb0d2',
			fillColor: '#ebf5f9',
			spotColor: '#17408c',
			minSpotColor: '#17408c',
			maxSpotColor: '#17408c',
			highlightSpotColor: '#189300',
			highlightLineColor: '#72bf66',
			spotRadius: 0,
			chartRangeMin: 5,
			chartRangeMax: 10,
			chartRangeMinX: 5,
			chartRangeMaxX: 5,
			normalRangeMin: 5,
			normalRangeMax: 5,
			normalRangeColor: '#ebf5f9',
			drawNormalOnTop: true								
		});
		
	});
	
	
}

function updateDropsize(){
	$(document).ready(function() {
		$dashleft = $('.dashboard-left').innerWidth();
		$('.lftwidth').css({'width':$dashleft +'px','margin-top':-65+'px'});
	});
}

updateGraph();
updateDropsize();


//------------------------------
//ON RESIZE
//------------------------------
$(window).resize(function() {
	updateGraph();
	updateDropsize();
});

setTimeout(function (){
	jQuery(document).ready(function() {
		jQuery(".stats2container").niceScroll({horizrailenabled:true,cursorwidth:"3px",cursorcolor:"#ccc",});
		jQuery(".fixedtopic").niceScroll({horizrailenabled:false,cursorwidth:"3px",cursorcolor:"#ccc",});
		jQuery(".dashboard-left").niceScroll({horizrailenabled:false,cursorwidth:"3px",cursorcolor:"#ccc",});
	});
}, 1500);	

//------------------------------
//POPOVER
//------------------------------
$(function (){
	$("#messages").popover({placement:'bottom', trigger:'click',html : true});
	//$("#messages").popover('show');
	$("#notifications").popover({placement:'bottom', trigger:'click',html : true});
	$("#tasks").popover({placement:'bottom', trigger:'click',html : true});
});

//------------------------------
//COUNT VISITORS
//------------------------------
$('.chart').easyPieChart({
	animate: 2000,
	barColor:   "#ff6633",
	trackColor: "#e9f3f7",
	scaleColor: false,
	lineCap: "square",
	lineWidth: 7,								
	size:85
});
$('.chart2').easyPieChart({
	animate: 2000,
	barColor:   "#66cccc",
	trackColor: "#e9f3f7",
	scaleColor: false,
	lineCap: "square",
	lineWidth: 7,								
	size:85
});
$('.chart3').easyPieChart({
	animate: 2000,
	barColor:   "#72bf66",
	trackColor: "#e9f3f7",
	scaleColor: false,
	lineCap: "square",
	lineWidth: 7,								
	size:85
});



//------------------------------
//COUNT VISITORS
//------------------------------
$(function($) {
	$('.countvisitors').countTo({
		from: 1,
		to: 1023,
		speed: 1000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});
	$('.countrevenue').countTo({
		from: 1,
		to: 112500,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});		
	$('.countemail').countTo({
		from: 0,
		to: 1,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});	
	$('.countbookings').countTo({
		from: 0,
		to: 56,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});	
	$('.countbouncerate').countTo({
		from: 0,
		to: 69,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});	
	$('.countnewvisits').countTo({
		from: 0,
		to: 81,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});		
	$('.countsearchtrafic').countTo({
		from: 0,
		to: 33,
		speed: 2000,
		refreshInterval: 50,
		onComplete: function(value) {
			console.debug(this);
		}
	});									
});		






