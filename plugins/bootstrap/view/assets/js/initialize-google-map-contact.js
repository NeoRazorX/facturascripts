	function initialize() {
		  // Create an array of styles.
		var styles = [
			{
				featureType: 'road.highway',
				elementType: 'all',
				stylers: [
					{ hue: '#e5e5e5' },
					{ saturation: -100 },
					{ lightness: 72 },
					{ visibility: 'simplified' }
				]
			},{
				featureType: 'water',
				elementType: 'all',
				stylers: [
					{ hue: '#30a5dc' },
					{ saturation: 47 },
					{ lightness: -31 },
					{ visibility: 'simplified' }
				]
			},{
				featureType: 'road',
				elementType: 'all',
				stylers: [
					{ hue: '#cccccc' },
					{ saturation: -100 },
					{ lightness: 44 },
					{ visibility: 'on' }
				]
			},{
				featureType: 'landscape',
				elementType: 'all',
				stylers: [
					{ hue: '#ffffff' },
					{ saturation: -100 },
					{ lightness: 100 },
					{ visibility: 'on' }
				]
			},{
				featureType: 'poi.park',
				elementType: 'all',
				stylers: [
					{ hue: '#d2df9f' },
					{ saturation: 12 },
					{ lightness: -4 },
					{ visibility: 'on' }
				]
			},{
				featureType: 'road.arterial',
				elementType: 'all',
				stylers: [
					{ hue: '#e5e5e5' },
					{ saturation: -100 },
					{ lightness: 56 },
					{ visibility: 'on' }
				]
			},{
				featureType: 'administrative.locality',
				elementType: 'all',
				stylers: [
					{ hue: '#000000' },
					{ saturation: 0 },
					{ lightness: 0 },
					{ visibility: 'on' }
				]
			}
		];

		
		var myLatlng = new google.maps.LatLng(36.5068, -4.8825);


	  // Create a new StyledMapType object, passing it the array of styles,
	  // as well as the name to be displayed on the map type control.
	  var styledMap = new google.maps.StyledMapType(styles,
		{name: "Styled Map"});


	  // Create a map object, and include the MapTypeId to add
	  // to the map type control.
	  var mapOptions = {
		zoom: 15,
		center: myLatlng,
		mapTypeControl: true,
		mapTypeControlOptions: {
		  mapTypeIds: [google.maps.MapTypeId.ROADMAP, 'map_style'],
		  
			style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
			position: google.maps.ControlPosition.RIGHT_TOP
		},
		panControl: true,
		panControlOptions: {
			position: google.maps.ControlPosition.RIGHT_CENTER
		},
		zoomControl: true,
		zoomControlOptions: {
			style: google.maps.ZoomControlStyle.LARGE,
			position: google.maps.ControlPosition.LEFT_CENTER
		},

	  };

	  var map = new google.maps.Map(document.getElementById('map-canvas2'),
		mapOptions);
		
	  var marker = new google.maps.Marker({
		  position: myLatlng,
		  map: map,
		  title: 'Hello World!'
	  });


	  //Associate the styled map with the MapTypeId and set it to display.
	  map.mapTypes.set('map_style', styledMap);
	  map.setMapTypeId('map_style');
	}

	


		  $('#map-canvas2').css({'display':'block'});
		  var script = document.createElement('script');
		  script.type = 'text/javascript';
		  script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&' +
			  'callback=initialize';
		  document.body.appendChild(script);
		  
		  google.maps.event.trigger(map, 'resize');

	