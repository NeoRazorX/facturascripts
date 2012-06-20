(function($)
{
	// This script was written by Steve Fenton
	// http://www.stevefenton.co.uk/Content/Jquery-Side-Content/
	// Feel free to use this jQuery Plugin
	// Version: 1.1.2
    // Contributions by:
    //     Hug Capella
	
	$.fn.charts = function (settings) {
	
		var config = {
			classmodifier: "charts",
			charttype: "bars",
			direction: "horizontal",
			labelcolumn: 0,
			valuecolumn: 1,
			groupcolumn: -1,
			duration: 2000,
			showoriginal: false,
			chartbgcolours: ["#336699", "#669933", "#339966"],
			chartfgcolours: ["#FFFFFF", "#FFFFFF", "#FFFFFF"],
			chartpadding: 8,
			chartheight: 300,
			showlabels: true,
			showgrid: false,
			gridlines: 8,
            gridvalues: true
		};
		
		if (settings) {
			$.extend(config, settings);
		}
		
		var labelTimer;
		
		function RoundToTwoDecimalPlaces(number) {
			number = Math.round(number * 100);
			number = number / 100;
			return number;
		}
		
		function GetHorizontalBarsOutput(groupArray, labelArray, valueArray, totalValue, largestValue, labelTextArray) {
			var output = "";
			var colourIndex = 0;
			
			output += "<ul class='jqbar'>";
			
			for (var i = 0; i < valueArray.length; i++) {
			
				if (colourIndex >= config.chartbgcolours.length) {
					colourIndex = 0;
				}
			
				var percent = Math.round((valueArray[i] / totalValue) * 100);
				var barWidth = Math.round((valueArray[i] / largestValue) * 100);
				
				var displayLabel = "";
				if (config.showlabels) {
					displayLabel = labelArray[i] + "<br>";
				}
				
				output += "<li>" + displayLabel + "<span class=\"" + config.classmodifier + "bar\" style=\"display: block; width: 0%; background-color: " + config.chartbgcolours[colourIndex] + "; color: " + config.chartfgcolours[colourIndex] + "; padding: " + config.chartpadding + "px 0; text-align: right;\" rel=\"" + barWidth + "\" title=\"" + labelTextArray[i] + " - " + valueArray[i] + " (" + percent + "%)" + "\">" + valueArray[i] + "&nbsp;</span></li>";
				
				colourIndex++;
			}
			
			output += "</ul>";
			
			return output;
		}
		
		function GetVerticalBarsOutput(groupArray, labelArray, valueArray, totalValue, largestValue, labelTextArray) {
			var output = "";
			var colourIndex = 0;
			var leftShim = 0;
			var shimAdjustment = RoundToTwoDecimalPlaces((100 + config.chartpadding) / labelArray.length);
			var widthAdjustment = shimAdjustment - config.chartpadding;
			
			var groupName = "";
			var useGroups = false;
			if (groupArray.length > 0) {
				useGroups = true;
			}
			
			output += "<div style=\"height: " + config.chartheight + "px; position: relative;\">";
			
			if (config.showgrid) {
               
                var gridLineCount = config.gridlines;
                var gridLineValue = (largestValue / gridLineCount);
                var gridLineHeight = (gridLineValue / largestValue) * 100;
                
                // All grid sections should be same height
				for (var i = 0; i < gridLineCount; i++) {
					var alternatingClass = "odd";
					if (i%2 == 0) {
						alternatingClass = "even";
					}
					output += "<div class=\"" + config.classmodifier + "gridline " + alternatingClass + "\" style=\"height: " + gridLineHeight + "%;\">";
                    if (config.gridvalues) {
                        var value = (gridLineCount - i) * gridLineValue;
                        output += "<span style=\"display: inline-block; width: 3em; position: relative; left: -3em; border-top: 1px solid Gray;\">" + value + "</span>";
                    }
                    output += "</div>";
				}
			}
			
			for (var i = 0; i < valueArray.length; i++) {
			
				if (colourIndex >= config.chartbgcolours.length) {
					colourIndex = 0;
				}
			
				var percent = Math.round((valueArray[i] / totalValue) * 100);
				// Fix suggested by Jaime Casto
				if (isNaN(percent)) {
					percent = 0;
				}
				
				var barHeight = Math.round((valueArray[i] / largestValue) * 100);

				// Group headings
				if (useGroups) {
					if (groupArray[i] != groupName) {
						groupName = groupArray[i];
						colourIndex = 0;
						groupWidth = 0;
						for (var j = i; j < valueArray.length; j++) {
							if (groupArray[j] == groupName) {
								groupWidth = groupWidth + (shimAdjustment -0.3);
							}
						}
						output += '<div class="' + config.classmodifier + 'group" style="text-align: center; z-index: 1000; position: absolute; bottom: -1.5em; left: ' + leftShim + '%; display: block; background-color: ' + config.chartbgcolours[colourIndex] + '; color: ' + config.chartfgcolours[colourIndex] + '; width: ' + groupWidth + '%;">' + groupName + '</div>';
					}
				}
				
				// Labels
				var displayLabel = "";
				if (config.showlabels) {
					displayLabel = "<span style=\"display: block; width: 100%; position: absolute; bottom: 0; text-align: center; background-color: " + config.chartbgcolours[colourIndex] + ";\">" + labelArray[i] + "</span>"
				}
				
				// Column
				output += "<div class=\"" + config.classmodifier + "bar\" style=\"position: absolute; bottom: 0; left: " + leftShim + "%; display: block; height: 0%; background-color: " + config.chartbgcolours[colourIndex] + "; color: " + config.chartfgcolours[colourIndex] + "; width: " + widthAdjustment + "%; text-align: left;\" rel=\"" + barHeight + "\" title=\"" + labelTextArray[i] + " - " + valueArray[i] + " (" + percent + "%)" + "\"><div style=\"text-align:center\">" + valueArray[i] + "</div>" + displayLabel + "</div>"

				leftShim = leftShim + shimAdjustment;
				
				colourIndex++;
			}
			
			output += "</div>";
			
			return output;
		}
		
		function GetWaterfallOutput(labelArray, valueArray, totalValue, largestValue, labelTextArray) {
			var output = "";
			var colourIndex = 0;
			var leftShim = 0;
			var shimAdjustment = RoundToTwoDecimalPlaces(100 / labelArray.length);
			var widthAdjustment = shimAdjustment - 1;
			
			output += "<div style=\"height: " + config.chartheight + "px; position: relative;\">";
			
			var runningTotal = 0;
			
			for (var i = 0; i < valueArray.length; i++) {
				
				var positiveValue = valueArray[i];
				var isPositive = true;
				var colourIndex = 1;
				
				if (positiveValue < 0) {
					positiveValue = positiveValue * -1;
					isPositive = false;
					if (config.chartbgcolours.length > 2) {
						colourIndex = 2;
					}
				}
			
				var percent = RoundToTwoDecimalPlaces((positiveValue / totalValue) * 100);
				var barHeight = RoundToTwoDecimalPlaces((positiveValue / largestValue) * 100);
				
				var bottomPosition = runningTotal - barHeight; // Negative column
				if (i == 0 || i == (valueArray.length - 1)) {
					bottomPosition = 0; // first or last column
					colourIndex = 0;
				} else if (isPositive) {
					bottomPosition = runningTotal;
				}

				// Labels
				var displayLabel = "";
				if (config.showlabels) {
					displayLabel = "<span style=\"display: block; width: 100%; position: absolute; bottom: 0; text-align: center; background-color: " + config.chartbgcolours[colourIndex] + ";\">" + labelArray[i] + "</span>"
				}
				
				// Column
				output += "<div class=\"" + config.classmodifier + "bar\" style=\"position: absolute; bottom: " + bottomPosition + "%; left: " + leftShim + "%; display: block; height: 0%; background-color: " + config.chartbgcolours[colourIndex] + "; color: " + config.chartfgcolours[colourIndex] + "; width: " + widthAdjustment + "%; text-align: center;\" rel=\"" + barHeight + "\" title=\"" + labelTextArray[i] + " - " + valueArray[i] + " (" + percent + "%)" + "\">" + valueArray[i] + displayLabel + "</div>"

				leftShim = leftShim + shimAdjustment;
				
				if (isPositive) {
					runningTotal = runningTotal + barHeight;
				} else {
					runningTotal = runningTotal - barHeight;
				}
			}
			
			output += "</div>";
			
			return output;
		}
		
		return this.each(function () {
			
			// Validate settings
			if (config.chartbgcolours.length != config.chartfgcolours.length) {
				alert("Invalid settings, chartfgcolours must be same length as chartbgcolours");
			}
			
			var $table = $(this);
			
			// Caption
			var caption = $table.children("caption").text();
			
			// Headers
			var maxColumn = Math.max(config.valuecolumn, config.labelcolumn, config.groupcolumn);
			var headers = $table.find("thead th");
			if (headers.length <= maxColumn) {
				alert("Header count doesn't match settings");
			}
			
			// Values
			var values = $table.find("tbody tr");
         if (config.direction=='vertical') config.chartpadding*= (values.length-1)*100/$table.parent().width()/values.length
			
			var labelArray = new Array();
			var labelTextArray = new Array();
			var valueArray = new Array();
			var groupArray = new Array();
			
			var totalValue = 0;
			var largestValue = 0;
			var currentGroup = "";
			
			// Creates a list of values and a total (and sets groups if required)
			for (var i = 0; i < values.length; i++) {
				if (config.groupcolumn > -1) {
					groupArray[groupArray.length] = $(values[i]).children("td").eq(config.groupcolumn).html();
				}
				var valueString = $(values[i]).children("td").eq(config.valuecolumn).text();
				if (valueString.length > 0) {
					var valueAmount = parseFloat(valueString, 10);
					labelArray[labelArray.length] = $(values[i]).children("td").eq(config.labelcolumn).html();
					labelTextArray[labelTextArray.length] = $(values[i]).children("td").eq(config.labelcolumn).text();
					valueArray[valueArray.length] = valueAmount;
					totalValue = totalValue + valueAmount;
					if (valueAmount > largestValue) {
						largestValue = valueAmount;
					}
				}
			}
			
			// Containing division
			var output = "<h1 class='jqbar'>" + caption + "</h1>" +
				"<div class=\"" + config.classmodifier + "container\">" +
				"<div class=\"" + config.classmodifier + "label\">&nbsp;</div>";
			
			// Get output based on chart type
			switch (config.charttype) {
			
				case 'bars':
				
					switch (config.direction) {
						
						case 'horizontal':
							// Horizontal Bars
							output += GetHorizontalBarsOutput(groupArray, labelArray, valueArray, totalValue, largestValue, labelTextArray);
							break;
							
						case 'vertical':
							// Vertical Bars
							output += GetVerticalBarsOutput(groupArray, labelArray, valueArray, totalValue, largestValue, labelTextArray);
							break;

					}
					break;
					
				case 'waterfall':
				
					switch (config.direction) {
					
						case 'horizontal':
							// Horizontal Bars
							alert("Horizontal waterfall charts not yet supported!");
							break;

						case 'vertical':
							// Waterfall chart
							output += GetWaterfallOutput(labelArray, valueArray, totalValue, largestValue, labelTextArray);
							break;
					}
					break;
			
			}
			
			// Close container
			output += "</div>";
			
			// Show the chart
			$table.after(output);
			
			if (!config.showoriginal) {
				$table.hide();
			}
			
			//$("." + config.classmodifier + "gridline").each( function () {
			//	$This = $(this);
			//	if ($This.hasClass("even")) {
			//		$This.css({ opacity: 0.5 });
			//	} else {
			//		$This.css({ opacity: 0.2 });
			//	}
			//});
			
			// Animation
			$("." + config.classmodifier + "bar").each( function() {
				var calculatedSize = $(this).attr("rel");
				
				switch (config.direction) {
					case "horizontal":
						$(this).animate({ width: calculatedSize+"%" }, config.duration);
						break;
					case "vertical":
						$(this).animate({ height: calculatedSize+"%" }, config.duration);
						break;
				}
			});
			
			// Labels
			$("." + config.classmodifier + "bar").mouseover( function() {
				window.clearTimeout(labelTimer);
				var $Label = $(this).parents("." + config.classmodifier + "container").find("." + config.classmodifier + "label");
				var $Bar = $(this);
				$Label.html("<div style=\"width: 70%; margin: 0 auto;\">" + $Bar.attr("title") + "</div>");
				$Label.find("div").css({ "text-align": "center", color: $Bar.css("background-color"), "background-color": $Bar.css("color") });
				var labelHeight = $Label.find("div").height();
				$Label.css({ height: labelHeight });
				return false;
			});
			
			$("." + config.classmodifier + "bar").mouseleave( function() {
				var $Bar = $(this);
				labelTimer = window.setTimeout(function () {
					$Bar.parents("." + config.classmodifier + "container").find("." + config.classmodifier + "label div").fadeOut("slow");
				}, 1000);
				return false;
			});
			
		});
		
		return this;
	};
})(jQuery);
