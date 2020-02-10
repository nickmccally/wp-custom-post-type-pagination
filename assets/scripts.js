jQuery(document).ready(function($){
	$('.paginateLink').click(function(){

		var paginationAction = $(this).attr('data-action');
		var paginationWrapper = $(this).parent('.sliderPagination');

		var currentPage = paginationWrapper.attr('data-currentpage');
		var total_pages = paginationWrapper.attr('data-totalpage');
		var uid = paginationWrapper.attr('data-uid');

		var widgetObject = window['paging_'+uid];
		$('.ajaxPreLoader').show();
		if(paginationAction == 'next'){
			var page_number = parseInt(currentPage)+ parseInt(1);
			if(page_number > total_pages){
				page_number = parseInt(currentPage);
			}
		}else if(paginationAction == 'previous' && currentPage > 1){
			var page_number = parseInt(currentPage) - parseInt(1);
		}


		var pagingData = {
			'action': 'get_recent_post',
			'token': widgetObject.pagination_token,
			'posts_per_page': widgetObject.posts_per_page,
			'order_by': widgetObject.order_by,
			'order': widgetObject.order,
			'category': widgetObject.category,
			'symbol': widgetObject.symbol,
			'post_type': widgetObject.post_type,
			'is_thumbail': widgetObject.is_thumbail,
			'is_excerpt': widgetObject.is_excerpt,
			'is_date': widgetObject.is_date,
			'is_author': widgetObject.is_author,
			'thumb_height': widgetObject.thumb_height,
			'thumb_width': widgetObject.thumb_width,
			'content_limit': widgetObject.content_limit,
			'page_number': page_number,
		}



		$.ajax ({
			url:widgetObject.ajax_url,

			type:"POST",
			data:pagingData,
			dataType: 'html',
			//contentType:"text/html; charset=utf-8",
			success: function(resultData){

				let d = JSON.parse(resultData);
				if(d['status'] == 'success'){

					$('#list'+uid).html(d['response']);
					paginationWrapper.attr('data-currentpage' , page_number);
					$('.ajaxPreLoader').hide();


				}else if(d['status'] == 'failed'){
					paginationWrapper.attr('data-currentpage' , currentPage);
					alert('Error');
				}
			}

		});
	})
})
