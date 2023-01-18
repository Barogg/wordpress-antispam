jQuery(document).ready(function(){

	console.table('apbctDashboardWidget',apbctDashboardWidget)

	// Set "refresh" link handler
	jQuery(".ct_widget_refresh_link").on('click', function(){
		jQuery('.ct_preloader').show();
		setTimeout(function(){window.scrollTo(0, jQuery('#ct_widget_wrapper').offset().top - 130);}, 1);
		setTimeout(function(){jQuery("#ct_refresh_form").submit();}, 2500);
	});

	if(location.hash == '#ct_widget')
		setTimeout(function(){window.scrollTo(0, jQuery('#ct_widget_wrapper').offset().top - 130);}, 1);

	// Fixing default wrapper style
	jQuery("#ct_widget_wrapper").parent().css('padding', 0);

	function reformatWidgetData(apbctDashboardWidget){
		apbctDashboardWidget.forEach(function(row){
			row['x'] = new Date(row['0'])
			row['y'] = row['1']
			row['color'] = '#0085ba'
		})
		console.table('apbctDashboardWidget',apbctDashboardWidget)
	}
	//cnvas start
	reformatWidgetData(apbctDashboardWidget.data)

	var chart = new CanvasJS.Chart("ct_widget_chart", {
		animationEnabled: true,
		theme: "light1", // "light1", "light2", "dark1", "dark2"
		dataPointMaxWidth:20,
		title:{
			text: "Spam Attacks"
		},
		axisY: {
			title: "Spam count"
		},
		data: [{
			type: "column",
			showInLegend: false,
			dataPoints: apbctDashboardWidget.data
		}]
	});
	chart.render();

});