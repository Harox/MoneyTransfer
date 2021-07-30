@extends('admin.layouts.master')
@section('title', 'Edit Merchant')

@section('head_style')
	<!-- sweetalert -->
	<link rel="stylesheet" type="text/css" href="{{ asset('public/backend/sweetalert/sweetalert.css')}}">
@endsection

@section('page_content')
	<div class="box">
	   <div class="panel-body">
	        <ul class="nav nav-tabs cus" role="tablist">
	            <li class="active">
	              <a href='{{url("admin/merchant/edit/$merchant->id")}}'>Profile</a>
	            </li>

	            <li>
	              <a href="{{url("admin/merchant/payments/$merchant->id")}}">Payments</a>
	            </li>
	       </ul>
	      <div class="clearfix"></div>
	   </div>
	</div>

	<div class="row">
		<div class="col-md-10">
			<h4 class="pull-left">{{ $merchant->business_name }}</h4>
		</div>
		<div class="col-md-2">
			@if ($merchant->status)
				<h4 class="pull-right">@if ($merchant->status == 'Approved')<span class="text-green">Approved</span>@endif
				@if ($merchant->status == 'Moderation')<span class="text-blue">Moderation</span>@endif
				@if ($merchant->status == 'Disapproved')<span class="text-red">Disapproved</span>@endif</h4>
			@endif
		</div>
	</div>

	<div class="box">
		<div class="box-body">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<form action="{{ url('admin/merchant/update') }}" class="form-horizontal" id="merchant_edit_form" method="POST" enctype="multipart/form-data">
							{{ csrf_field() }}

					        <input type="hidden" value="{{ $merchant->id }}" name="id" id="id">

							<div class="col-md-7">
			                    @if ($merchant->user)
									<div class="form-group">
										<label class="control-label col-sm-3" for="user">User</label>
										<div class="col-sm-9">
											<p class="form-control-static">{{ isset($merchant->user) ? $merchant->user->first_name.' '.$merchant->user->last_name :"-" }}</p>
										</div>
									</div>
								@endif

								@if ($merchant->merchant_uuid)
									<div class="form-group">
										<label class="control-label col-sm-3" for="merchant_uuid">Merchant ID</label>
										<div class="col-sm-9">
											<p class="form-control-static">{{ $merchant->merchant_uuid }}</p>
										</div>
									</div>
								@endif

								@if ($merchant->type)
				                    <div class="form-group">
										<label class="control-label col-sm-3" for="type">Type</label>
										<div class="col-sm-9">
											<select class="select2" name="type" id="type">
												<option value="standard" {{ $merchant->type ==  'standard'? 'selected':"" }}>Standard</option>
												<option value="express"  {{ $merchant->type == 'express' ? 'selected':"" }}>Express</option>
											</select>
										</div>
									</div>
								@endif

								@if ($merchant->business_name)
				                    <div class="form-group">
										<label class="control-label col-sm-3" for="business_name">Business Name</label>
										<div class="col-sm-9">
											<input type="text" class="form-control" name="business_name" value="{{ $merchant->business_name }}">

											@if($errors->has('business_name'))
												<span class="error">
													<strong class="text-danger">{{ $errors->first('business_name') }}</strong>
												</span>
											@endif
										</div>
									</div>
								@endif

								@if ($merchant->site_url)
				                    <div class="form-group">
										<label class="control-label col-sm-3" for="site_url">Site Url</label>
										<div class="col-sm-9">
											<input type="text" class="form-control" name="site_url" value="{{ $merchant->site_url }}">

											@if($errors->has('site_url'))
												<span class="error">
													<strong class="text-danger">{{ $errors->first('site_url') }}</strong>
												</span>
											@endif
										</div>
									</div>
								@endif

								@if ($merchant->currency_id)
				                    <div class="form-group">
										<label class="control-label col-sm-3" for="site_url">Currency</label>
										<div class="col-sm-9">
											<select class="form-control select2" name="currency_id">
												<!--pm_v2.3-->
												@foreach($activeCurrencies as $result)
														<option value="{{ $result->id }}" {{ $merchant->currency_id == $result->id ? 'selected="selected"' : '' }}>{{ $result->code }}</option>
												@endforeach
											</select>

											@if($errors->has('currency_id'))
												<span class="error">
													<strong class="text-danger">{{ $errors->first('currency_id') }}</strong>
												</span>
											@endif
										</div>
									</div>
								@endif

								<div class="form-group">
	                                <label class="col-sm-3 control-label" for="merchantGroup">Group</label>
	                                <div class="col-sm-9">
	                                    <select class="select2" name="merchantGroup" id="merchantGroup">
	                                        @foreach ($merchantGroup as $group)
	                                          <option value='{{ $group->id }}' {{ isset($group) && $group->id == $merchant->merchant_group_id ? 'selected':""}}> {{ $group->name }}</option>
	                                        @endforeach
	                                    </select>
	                                </div>
	                            </div>

			                    <div class="form-group">
									<label class="control-label col-sm-3" for="site_url">Fee (%)</label>
									<div class="col-sm-9">
										<input type="text" class="form-control" name="fee" value="{{ number_format((float)$merchant->fee, $preference['decimal_format_amount'], '.', '') }}" id="fee"
										oninput="restrictNumberToPrefdecimal(this)">
										@if($errors->has('fee'))
											<span class="error">
												<strong class="text-danger">{{ $errors->first('fee') }}</strong>
											</span>
										@endif
										<div class="clearfix"></div>
										<small class="form-text text-muted"><strong>{{ allowedDecimalPlaceMessage($preference['decimal_format_amount']) }}</strong></small>
									</div>
								</div>


			                    <div class="form-group">
									<label class="control-label col-sm-3" for="logo">Logo</label>
									<div class="col-sm-9">
									  <input type="file" name="logo" class="form-control input-file-field" data-rel="{{ !empty($merchant->logo) ? $merchant->logo : '' }}" id="logo"
									  	value="{{ !empty($merchant->logo) ? $merchant->logo : '' }}">
									  	@if($errors->has('logo'))
											<span class="error">
												<strong class="text-danger">{{ $errors->first('logo') }}</strong>
											</span>
										@endif

										<div class="clearfix"></div>
                						<small class="form-text text-muted"><strong>{{ allowedImageDimension(100,80) }}</strong></small>

										@if (!empty($merchant->logo))
						                  <div class="setting-img">
						                    <img src='{{ url('public/user_dashboard/merchant/'.$merchant->logo) }}' width="100" height="80" id="merchant-logo-preview">
						                    <span class="remove_merchant_preview"></span>
						                  </div>
						                @else
						                	<div class="setting-img">
						                  		<img src='{{ url('public/uploads/userPic/default-image.png') }}' width="100" height="80" id="merchant-demo-logo-preview">
						                  	</div>
						                @endif
									</div>
								</div>

		                   		@if ($merchant->status)
			                   		<div class="form-group">
										<label class="control-label col-sm-3" for="status">Change Status</label>
										<div class="col-sm-9">
											<select class="select2" name="status" id="status">
												<option value="Approved" {{ isset($merchant->status) && $merchant->status ==  'Approved'? 'selected':"" }}>Approved</option>
												<option value="Moderation"  {{ isset($merchant->status) && $merchant->status == 'Moderation' ? 'selected':"" }}>Moderation</option>
												<option value="Disapproved"  {{ isset($merchant->status) && $merchant->status == 'Disapproved' ? 'selected':"" }}>Disapproved</option>
											</select>
										</div>
									</div>
								@endif
							</div>

							<div class="row">
								<div class="col-md-11">
									<div class="col-md-2"></div>
									<div class="col-md-2"><a id="cancel_anchor" class="btn btn-danger pull-left" href="{{ url('admin/merchants') }}">Cancel</a></div>
									<div class="col-md-1">
										<button type="submit" class="btn button-secondary pull-right" id="merchant_edit">
			                                <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="merchant_edit_text">Update</span>
			                            </button>
									</div>
								</div>
							</div>

						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('extra_body_scripts')

<!-- jquery.validate -->
<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

<!-- jquery.validate additional-methods -->
<script src="{{ asset('public/dist/js/jquery-validation-1.17.0/dist/additional-methods.min.js') }}" type="text/javascript"></script>

<!-- sweetalert -->
<script src="{{ asset('public/backend/sweetalert/sweetalert.min.js')}}" type="text/javascript"></script>

@include('common.restrict_number_to_pref_decimal')

@include('common.format_number_to_pref_decimal')

<!-- read-file-on-change -->
@include('common.read-file-on-change')

<script type="text/javascript">

	function getMerchantGroupFee(merchant_group_id)
    {
    	let currentMerchantGroupId = '{{ $merchant->merchant_group_id }}';
        if (currentMerchantGroupId != merchant_group_id)
        {
        	$.ajax({
	            headers:
	            {
	                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	            },
	            method: "POST",
	            url: SITE_URL+"/admin/merchants/change-fee-with-group-change",
	            dataType: "json",
	            data: {
	                'merchant_group_id':merchant_group_id,
	            }
	        })
	        .done(function(response)
	        {
				if(response.status == true)
				{
				 	$('#fee').val(formatNumberToPrefDecimal(response.fee));
				}
	        });
        }
        else
        {
        	let merchantFee = '{{ $merchant->fee }}';
        	$('#fee').val(formatNumberToPrefDecimal(merchantFee));
        }
    }

	$(window).on('load',function(){
		$(".select2").select2({});
        let merchant_group_id = $('#merchantGroup option:selected').val();
        getMerchantGroupFee(merchant_group_id);
	});

	$(document).on('change','#merchantGroup',function(e)
    {
        e.preventDefault();
        let merchant_group_id = $('#merchantGroup option:selected').val();
        getMerchantGroupFee(merchant_group_id);
    });

	// preview logo on change
    $(document).on('change','#logo', function()
    {
    	let orginalSource = '{{ url('public/uploads/userPic/default-image.png') }}';
    	let logo = $('#logo').attr('data-rel');
    	if (logo != '') {
    		readFileOnChange(this, $('#merchant-logo-preview'), orginalSource);
    		$('.remove_merchant_preview').remove();
    	}
        readFileOnChange(this, $('#merchant-demo-logo-preview'), orginalSource);
    });

	$(document).ready(function()
    {
      $('.remove_merchant_preview').click(function()
      {
        var logo = $('#logo').attr('data-rel');
        var merchant_id = $('#id').val();
        if(logo)
        {
          $.ajax(
          {
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type : "POST",
            url : SITE_URL+"/admin/merchant/delete-merchant-logo",
            // async : false,
            data: {
              'logo' : logo,
              'merchant_id' : merchant_id,
            },
            dataType : 'json',
            success: function(reply)
            {
              if (reply.success == 1)
              {
					swal({title: "Deleted!", text: reply.message, type: "success"},
	                   function(){
	                       location.reload();
	                   }
	                );
              }
              else
              {
                  alert(reply.message);
                  location.reload();
              }
            }
          });
        }
      });
    });

    $.validator.setDefaults({
        highlight: function(element) {
           $(element).parent('div').addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).parent('div').removeClass('has-error');
        },
    });

    $('#merchant_edit_form').validate({
        rules: {
            business_name: {
                required: true,
            },
            site_url: {
                required: true,
                url: true,
            },
            type: {
                required: true,
                lettersonly: true,
            },
            fee: {
                required: true,
                number: true,
            },
            logo: {
                extension: "png|jpg|jpeg|gif|bmp",
            },
        },
        messages: {
          logo: {
            extension: "Please select (png, jpg, jpeg, gif or bmp) file!"
          },
          type: {
            lettersonly: "Please enter letters only!"
          }
        },
        submitHandler: function(form)
	    {
	        $("#merchant_edit").attr("disabled", true);
	        $(".fa-spin").show();
	        $("#merchant_edit_text").text('Updating...');
	        $('#cancel_anchor').attr("disabled",true);
	        form.submit();
	    }
    });

</script>

@endpush