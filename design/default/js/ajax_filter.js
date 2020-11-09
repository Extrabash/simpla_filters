// Показать в каталоге
$('aside .form .checkbox label').click(function(e){
	if(!$(this).parent().hasClass('disabled')){

		var posTop = $(this).position('aside .filter').top
		$('.show_box').css({'top': posTop})
	}
});


$('#filter input[type=checkbox]').live('change', function(e){
	e.preventDefault();
	console.log('nen1f');
	bash_filter();
});


$('#filter').on('change', function(){

});

function bash_filter(){
	console.log('nenf');
	filter = $('#filter');

	//$('.show_box').hide();

	serialized_query = filter.serialize();
	console.log('serialized_query: '+serialized_query);

	//filter.find('.checkbox').addClass('disabled');
	//filter.find('.checkbox input').attr("disabled", true);

	//var range_slider = $('#price_range').data("ionRangeSlider");

	//range_slider.update({"disable": true});

	category_id = filter.attr('category_id');
	console.log('category_id: '+category_id);

	$.ajax({
		url: 'ajax/filter_products.php',
		data: {serialized_query: serialized_query, category_id: category_id},
		dataType: 'json',
		success: function(data){
				console.log('data: ' + data);
				//console.log(data);
				//$('#filter_box').html($(data).find('#filter_box').html());

				//var min_val = parseInt( $(data).find('.price_range input.ot').attr('min_val') );
				//var max_val = parseInt( $(data).find('.price_range input.do').attr('max_val') );
				//var actual_min_val = parseInt( $(data).find('.price_range input.ot').val() );
				//var actual_max_val = parseInt( $(data).find('.price_range input.do').val() );

				//console.log(actual_min_val + '<' + min_val);
				//console.log(actual_max_val + '>' + max_val);

				//if(actual_min_val < min_val)
				//	actual_min_val = min_val;

				//if(actual_max_val > max_val)
				//	actual_max_val = max_val;



				//console.log(min_val);
				//console.log(max_val);
				//console.log(actual_min_val);
				//console.log(actual_max_val);
				/*
				if (min_val != max_val)
				{
					range_slider.update({
						"min"      : min_val,
						"max"      : max_val,
						"from"     : actual_min_val,
						"to"       : actual_max_val
					});

					filter.find('.price_range input.ot').val( actual_min_val );
					filter.find('.price_range input.do').val( actual_max_val );
					
					range_slider.update({"disable": false});
				}*/

				//filter.find('.checkbox').removeClass('disabled');
				//filter.find('.checkbox input').attr("disabled", false);
				//$('.show_box').show();
			}
		});
}