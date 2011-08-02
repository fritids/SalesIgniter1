	$("input[name='referral']").live('change', function () {
		//alert($(this).val());
		var $elem = $(this).parent();
       showAjaxLoader($elem, 'xlarge');
		var linkParams = js_get_all_get_params(['app', 'appPage', 'action']);
		$.ajax({
            url: js_app_link(linkParams + 'rType=ajax&app=checkout&appPage=default&action=getRefCode'),
            data: 'refcode=' + $(this).val(),
            type: 'post',
            dataType: 'json',
            success: function (data) {
				//alert(data.couponcode);
				removeAjaxLoader($elem);
				if (data.success){
					$("input[name='redeem_code']").val(data.couponcode);
					$('#voucherRedeem').trigger('click');
				}else if (data.errMsg != '') {
					alert(data.errMsg);
				}
            }
        });
	});