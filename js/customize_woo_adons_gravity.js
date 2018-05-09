jQuery(document).ready(function ($) {


		$("#woocommerce-gravityforms-meta select").select2(); 
		$( document.body ).on( "click", function() {
        $("#woocommerce-gravityforms-meta select").select2(); 
       });
	    $(document).on('click', '.deletediv', function(e){
          e.preventDefault();
          var el = $(this).closest('.gravityform-custom-div');
          cuteHide(el);
        });
        $(document).on('click', '.editgenBox', function(e){
          if($(this).val() == "on")
          {
            $(this).val("off");
              $(this).closest('.gravityform-custom-div').find('.tabwrapstart').slideDown();
          }else{
            $(this).val("on");
             $(this).closest('.gravityform-custom-div').find('.tabwrapstart').slideUp();
          }
        });
        
        $(document).on('click', '.deletedivcontroller', function(e){
          e.preventDefault();
          var el = $(this).closest('.gravityform-display_title_field');
          cuteHide(el);
        });
        function cuteHide(el) {
            el.animate({opacity: '0'}, 350, function(){
            el.animate({height: '0px'}, 350, function(){
              el.remove();
            });
            });
          }

        $(document).on('click', '.copycontrollername', function(e){
          e.preventDefault();
          var downloadbale_file_nameData = $(this).closest(".gravityform-custom-div").find(".downloadbale_file_name").html();
          
          $(this).closest(".gravityform-custom-div").find('.controllername').val(downloadbale_file_nameData);
          
        });
        
      //Sorting Script Start

      $(document).on('click', '.datasorting', function(e){
        e.preventDefault();
        var sorttype = $(this).data('sortby');
        var el = $(this).closest(".gravityform-custom-div");
        var gencontrollerdata  =  el.find('.gencontrollerdata').val();
        var indexKey  =  el.find('.rules_indexkey').val();
        var action = 'datasorting';
        $.ajax({
        type: "POST",
        url:  ajax_custom.ajaxurl,
        data: {action:action,gencontrollerdata:gencontrollerdata,sorttype:sorttype},
        success: function (response) {
            el.find('.controllfilenameresponse').html();
            el.find('.controllfilenameresponse').html(response);
        }
        });
        
      });
      
      //Sorting Script End  
      var gfuser_settingsselectedval =  $('.gfuser_settings :selected').val();
      if(gfuser_settingsselectedval == 'traverse'  )
      {
        $(".add_more_button").show();
      }else{
        $('.useraddcontrol_number').show(); 
        $(".add_more_button").hide();
      }
      
      $(document).on('change', '.gfuser_settings', function(e){
      	console.log('call me gfuser_settings');
        if($(this).val()  != 'traverse')
        {
          $(".add_more_button").hide();
          $(this).closest(".gravityform-custom-div").find('.useraddcontrol_number').show();
        }else{
        
          $(".add_more_button").show();
        }
      });
      $(document).on('click', '.only_btn_inner', function(e){
		e.preventDefault(); 
		$(this).hide(500);
		$(".all_import_data").show(500);
	  });
	  $(document).on('click', '.rules_import_action', function(e){
		e.preventDefault();
		var product_id = $(this).data("product_id");
		var gravityform_id = $(this).data("gravityform_id");
		var imageUrl = ajax_custom.US_DIR + '/images/dots.gif';
		$(".gravityform_inport_export_response").html('');
		$(".gravityform_inport_export_response").append("<img src="+ imageUrl +" alt='Lodding' style='width: 60px;text-align: center;'/>");
		var fileurl = $(this).data("file_url");
		var action = 'rules_import_by_file_url';
		$.ajax({
			type: "POST",
			url:  ajax_custom.ajaxurl,
			data: {action:action,gravityform_id:gravityform_id,product_id:product_id,fileurl:fileurl},
			dataType: "json",
			success: function (response) {
         $( ".gravityform_inport_export_response" ).html( response.message);
			 if(response.success == true)
			 {
				// $(".generate_controllerfiles").trigger('click');
				/* while ajax call and update datain database just relode this page and reflateed updateed data;*/
				 location.reload();
				 
			 }
			
			 },  
			 error: function (jqXHR, exception) {
				var msg = '';
				if (jqXHR.status === 0) {
					msg = 'Not connect.\n Verify Network.';
				} else if (jqXHR.status == 404) {
					msg = 'Requested page not found. [404]';
				} else if (jqXHR.status == 500) {
					msg = 'Internal Server Error [500].';
				} else if (exception === 'parsererror') {
					msg = 'Requested JSON parse failed.';
				} else if (exception === 'timeout') {
					msg = 'Time out error.';
				} else if (exception === 'abort') {
					msg = 'Ajax request aborted.';
				} else {
					msg = 'Uncaught Error.\n' + jqXHR.responseText;
				}
				alert(msg);
			}
			});
	  
	  });
		$(document).on('click', '.rules_selectfile', function(e){
			e.preventDefault();
			 var image_frame;
				 if(image_frame){
					 image_frame.open();
				 }
				 // Define image_frame as wp.media object
				 image_frame = wp.media({title: 'Select xls file',multiple : false});
				 image_frame.on('close',function()
				 {
					 var selection =  image_frame.state().get('selection');
					 var gallery_ids = new Array();
					 var my_index = 0;
					 attachment = image_frame.state().get('selection').first().toJSON();
					 $('input#rules_import').attr("value",attachment.url);
					 $('.rules_import_action').attr("data-file_url",attachment.url);
				});
			image_frame.open();
			
		});
		 
        $(document).on('click', '.rules_export', function(e){
		 e.preventDefault();
		 var r = confirm("Are you sure you want to export this rules");
   		 if (r != true) {
			return false;
		 } 

		  var product_id = $(this).data("product_id");
		  var gravityform_id = $(this).data("gravityform_id");
		  var action = 'rules_exports_by_id';
		  var imageUrl = ajax_custom.US_DIR + '/images/dots.gif';
		 $(".gravityform_inport_export_response").html('');
		 $(".gravityform_inport_export_response").append("<img src="+ imageUrl +" alt='Lodding' style='width: 60px;text-align: center;'/>");
		 $.ajax({
			type: "POST",
			url:  ajax_custom.ajaxurl,
			data: {action:action,gravityform_id:gravityform_id,product_id:product_id},
			dataType: "json",
			success: function (response) {
			 if(response.success == true)
			 {
				document.location.href =(response.filename);
			 }
			 $( ".gravityform_inport_export_response" ).html( response.message); 
			
			 },  
			 error: function (jqXHR, exception) {
				var msg = '';
				if (jqXHR.status === 0) {
					msg = 'Not connect.\n Verify Network.';
				} else if (jqXHR.status == 404) {
					msg = 'Requested page not found. [404]';
				} else if (jqXHR.status == 500) {
					msg = 'Internal Server Error [500].';
				} else if (exception === 'parsererror') {
					msg = 'Requested JSON parse failed.';
				} else if (exception === 'timeout') {
					msg = 'Time out error.';
				} else if (exception === 'abort') {
					msg = 'Ajax request aborted.';
				} else {
					msg = 'Uncaught Error.\n' + jqXHR.responseText;
				}
				alert(msg);
			}
			});
		});
		 
        $(document).on('click', '.generate_controllerfiles', function(e){
        e.preventDefault();
        
        var el = $(this).closest(".gravityform-custom-div");
        var totele = el.find('.controllfilenameresponse .gravityform-display_title_field').length;
        if(totele != 0 ){
         var process = confirm("Are you sure you want to replace all  controller number");
         if(process == false)
         {
          return false;
         }
        }

        if(el.find('.tabwrapstart').is(':hidden'))
        {
           el.find('.tabwrapstart').slideDown();
        }
        el.find('ui-tabs').tabs({active:0});  
        el.find('.controllfilenameresponse').html('<img align="center" width="50px" height="50px" src="'+ajax_custom.US_DIR+'images/200w_s.gif">');
        var action = 'generate_controllerfiles';
        //var controllername =  el.find('.downloadbale_file_name').html();

        var controllername =  el.find('.controllername').val();
		
		if(controllername == '')
		{
			controllername = el.find('.controllername').text('downloadbale_file_name');
		}
        var userselectednamerules  =  el.find('.downloadbale_file_array').val();
        var rules_indexkey =  el.find('.rules_indexkey').val();
        var gfuser_settings =  el.find('.gfuser_settings :selected').val();
        var is_bundle_type =  el.find('.is_producttypebundle').val();
		var gravity_form_id =  el.find('.gravity_form_id').val();
		
		    var useraddcontrol_number = [];
        el.find('.bundle_data_number').each(function(index){
            useraddcontrol_number.push([$(this).data("rules_indexkey"), $(this).data('product_id'),$(this).val()]);
            }
          );
		
		if(gfuser_settings  != 'traverse')
        {
          $(".add_more_button").hide();
          $(this).closest(".gravityform-custom-div").find('.useraddcontrol_number').show();
        }else{
          $(".add_more_button").show();
        }
        //var controllernamerules  =  el.find('.gravityData_all').val();
        var productsku  =  $('#_sku').val();
		    var bundle_data = JSON.stringify(useraddcontrol_number);
        $.ajax({
        type: "POST",
        url:  ajax_custom.ajaxurl,
        //data: {action:action,userselectednamerules:userselectednamerules,controllername:controllername,controllernamerules:controllernamerules,productsku:productsku,rules_indexkey:rules_indexkey,gfuser_settings:gfuser_settings,useraddcontrol_number:bundle_data,gravity_form_id:gravity_form_id},
        data: {action:action,userselectednamerules:userselectednamerules,controllername:controllername,productsku:productsku,rules_indexkey:rules_indexkey,gfuser_settings:gfuser_settings,useraddcontrol_number:bundle_data,gravity_form_id:gravity_form_id},
		success: function (response) {
          el.find('img:first-child').remove();
          el.find('.controllfilenameresponse').html();
          el.find('.controllfilenameresponse').html(response);
          $( ".gravityform-custom-div select" ).trigger( "refresh" );
          
        },  
		error: function (jqXHR, exception) {
			var msg = '';
			if (jqXHR.status === 0) {
				msg = 'Not connect.\n Verify Network.';
			} else if (jqXHR.status == 404) {
				msg = 'Requested page not found. [404]';
			} else if (jqXHR.status == 500) {
				msg = 'Internal Server Error [500].';
			} else if (exception === 'parsererror') {
				msg = 'Requested JSON parse failed.';
			} else if (exception === 'timeout') {
				msg = 'Time out error.';
			} else if (exception === 'abort') {
				msg = 'Ajax request aborted.';
			} else {
				msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			alert(msg);
			}
        });
        
        });
        $(document).on('change', '.gravityform-custom-div-innerFiled', function(e){ 
          var gravity_Control = [];
          var gravity_ControlJson = [];
            $(this).closest(".gravityform-custom-div-innerFiled").find('select').each(function(index){
            if ($(this).has('option:selected') && $(this).val() !== 'skip' ){
               gravity_Control.push([$(this).attr("name"), $(this).val(),$(this).data('gravity_filed_id')]);  
                gravity_ControlJson.push([$(this).attr("name"), $(this).val(),$(this).data('gravity_filed_id')]);
              
            }else{
               gravity_ControlJson.push([$(this).attr("name"), $(this).val(),$(this).data('gravity_filed_id')]);
            }
          });
          
          var jsonString = JSON.stringify(gravity_ControlJson);
          $(this).closest(".gravityform-custom-div").find('.downloadbale_file_array').val(jsonString);
          var downloadbale_file_name = '';
          var downloadbale_file_arraydata = '';
          for (var i = 0; i < gravity_Control.length; i++)
          {
            downloadbale_file_name = downloadbale_file_name + gravity_Control[i][1]+'_';
            downloadbale_file_arraydata = downloadbale_file_arraydata + gravity_Control[i][1]+'_';
             
          }
          var productSKU = $("#_sku").val();
          downloadbale_file_name = downloadbale_file_name + productSKU;

          $(this).closest(".gravityform-custom-div").find('.downloadbale_file_name').html(downloadbale_file_name);
        });
        $(document).on('click', '.add_more_button', function(e){
          e.preventDefault();
          var fromID = $(this).data('gravity_form');
		 // alert(fromID);
		//return false;
          AjaxCall(fromID,'OnClickEvent');
          return false;
        });
        

        /*$('#gravityform-id').change(function () {*/
          
		 // $('#gravityform-id').on('select2:select', function (e) {
		  
		 //$(".gravityform_class_select").on("change", function () { 
		 $('body').on('change', '.gravityform_class_select', function() { 
		  if ($(this).val() != '') {
            $('.gforms-panel').show();
             AjaxCall($(this).val(),'OnChangeEvent');
            
            //$("#gravityforms_data_response_custom").html(DATA);
          } else {
            $('.gforms-panel').hide();
          }
        })
        function AjaxCall(fromID,eventType){
		//alert(eventType);
		//return false;
          var action = 'get_fromdataByID';
          var product_sku = $("#_sku").val();
		   var product_id = $("#post_ID").val();
		   
		   var loopCount = $("#gravityforms_data_response_custom .gravityform-custom-div").length;
		   if(eventType == "OnChangeEvent"){
		   		loopCount = 0;
		   }

		   if(eventType == "OnChangeEvent"){
			$("#gravityforms_data_response").html('<img align="center" width="50px" height="50px" src="'+ajax_custom.US_DIR+'images/200w_s.gif">');
		   }else{
			   $('.input_fields_container').append('<img align="center" width="50px" height="50px" src="'+ajax_custom.US_DIR+'images/200w_s.gif">');
		   }	  
		   
            $.ajax({
            type: "POST",
            url:  ajax_custom.ajaxurl,
            data: {action:action,fromID:fromID,product_sku:product_sku,product_id:product_id,loopCount:loopCount},
            //dataType: 'json',
            success: function (response) {
              if(eventType == 'OnClickEvent'){
				$('body .input_fields_container').find('img').remove(); 
                $("#gravityforms_data_response_custom").append(response); 
				
              }else if(eventType == "OnChangeEvent"){
              $("#gravityforms_data_response").html();  
              $("#gravityforms_data_response").html(response);  
              }
              $("#woocommerce-gravityforms-meta select").select2(); 
              //setTimeout( function(){$( ".gravityform-custom-div select" ).trigger( "change" );}, 2000 );
            },  
			 error: function (jqXHR, exception) {
				var msg = '';
				if (jqXHR.status === 0) {
					msg = 'Not connect.\n Verify Network.';
				} else if (jqXHR.status == 404) {
					msg = 'Requested page not found. [404]';
				} else if (jqXHR.status == 500) {
					msg = 'Internal Server Error [500].';
				} else if (exception === 'parsererror') {
					msg = 'Requested JSON parse failed.';
				} else if (exception === 'timeout') {
					msg = 'Time out error.';
				} else if (exception === 'abort') {
					msg = 'Ajax request aborted.';
				} else {
					msg = 'Uncaught Error.\n' + jqXHR.responseText;
				}
				alert(msg);
			}
          });
  
        }  
      });