/**
 * jQuery.Preload
 * https://github.com/htmlhero/jQuery.preload
 *
 * Created by Andrew Motoshin
 * http://htmlhero.ru
 *
 * Version: 1.5.0
 * Requires: jQuery 1.6+
 *
 */

(function($){

	$.preload = (function(sources, part, callback){

		// Plugin cache
		var cache = [];

		// Wrapper for cache
		var caching = function(image){

			for (var i = 0; i < cache.length; i++) {
				if (cache[i].src === image.src) {
					return cache[i];
				}
			}

			cache.push(image);
			return image;

		};

		// Execute callback
		var exec = function(sources, callback, last){

			if (typeof callback === 'function') {
				callback.call(sources, last);
			}

		};

		// Closure to hide cache
		return function(sources, part, callback){

			// Check input data
			if (typeof sources === 'undefined') {
				return;
			}

			if (typeof sources === 'string') {
				sources = [sources];
			}

			if (arguments.length === 2 && typeof part === 'function') {
				callback = part;
				part = 0;
			}

			// Split to pieces
			var total = sources.length,
				next;

			if (part > 0 && part < total) {

				next = sources.slice(part, total);
				sources = sources.slice(0, part);

				total = sources.length;

			}

			// If sources array is empty
			if (!total) {
				exec(sources, callback, true);
				return;
			}

			// Image loading callback
			var preload = arguments.callee,
				count = 0;

			var loaded = function(){

				count++;

				if (count !== total) {
					return;
				}

				exec(sources, callback, !next);
				preload(next, part, callback);

			};

			// Loop sources to preload
			var image;

			for (var i = 0; i < sources.length; i++) {

				image = new Image();
				image.src = sources[i];

				image = caching(image);

				if (image.complete) {
					loaded();
				} else {
					$(image).on('load error', loaded);
				}

			}

		};

	})();

	// Get URLs from DOM elements
	var getSources = function(items, options){

		var sources = [],
			reg = new RegExp('url\\([\'"]?([^"\'\)]*)[\'"]?\\)', 'i'),
			$this, imageList, image, url, i;

		if (options.recursive) {
			items = items.find('*').add(items);
		}

		items.each(function(){

			$this = $(this);

			imageList = $this.css('background-image') + ',' + $this.css('border-image-source');
			imageList = imageList.split(',');

			for (i = 0; i < imageList.length; i++) {

				image = imageList[i];

				if (image.indexOf('about:blank') !== -1 ||
					image.indexOf('data:image') !== -1) {
					continue;
				}

				url = reg.exec(image);

				if (url) {
					sources.push(url[1]);
				}

			}

			if (this.nodeName === 'IMG') {
				sources.push(this.src);
			}

		});

		return sources;

	};

	$.fn.preload = function(){

		var options, callback;

		// Make arguments flexible
		if (arguments.length === 1) {
			if (typeof arguments[0] === 'object') {
				options = arguments[0];
			} else {
				callback = arguments[0];
			}
		} else if (arguments.length > 1) {
			options = arguments[0];
			callback = arguments[1];
		}

		// Extend default options
		options = $.extend({
			recursive: true,
			part: 0
		}, options);

		var items = this,
			sources = getSources(items, options);

		$.preload(sources, options.part, function(last){

			if (last && typeof callback === 'function') {
				callback.call(items.get());
			}

		});

		return this;

	};

})(jQuery);