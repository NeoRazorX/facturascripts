

// ##################################
// INICTIALIZE CAROUSEL DETAILS PAGE
// #################################

		$(function() {
			var $carousel = $('#carousel'),
				$pager = $('#pager');

			function getCenterThumb() {
				var $visible = $pager.triggerHandler( 'currentVisible' ),
					center = Math.floor($visible.length / 2);
				
				return center;
			}

			$carousel.carouFredSel({
				responsive: true,
				items: {
					visible: 1,
					width: 800,
					height: (500/800*100) + '%'
				},
				scroll: {
					fx: 'crossfade',
					onBefore: function( data ) {
						var src = data.items.visible.first().attr( 'src' );
						src = src.split( '/large/' ).join( '/small/' );

						$pager.trigger( 'slideTo', [ 'img[src="'+ src +'"]', -getCenterThumb() ] );
						$pager.find( 'img' ).removeClass( 'selected' );
					},
					onAfter: function() {
						$pager.find( 'img' ).eq( getCenterThumb() ).addClass( 'selected' );
					}
				},
				prev: {
					button: "#prev_btn2",
					key: "left"
				},
				next: {
					button: "#next_btn2",
					key: "right"
				},	
			});
			$pager.carouFredSel({
				width: '100%',
				auto: false,
				height: 120,
				items: {
					visible: 'odd'
				},
				onCreate: function() {
					var center = getCenterThumb();
					$pager.trigger( 'slideTo', [ -center, { duration: 0 } ] );
					$pager.find( 'img' ).eq( center ).addClass( 'selected' );
				}
			});
			$pager.find( 'img' ).click(function() {
				var src = $(this).attr( 'src' );
				src = src.split( '/small/' ).join( '/large/' );
				$carousel.trigger( 'slideTo', [ 'img[src="'+ src +'"]' ] );
			});
		});








