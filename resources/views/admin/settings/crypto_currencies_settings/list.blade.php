@extends('admin.layouts.master')
@section('title', 'BlockIo '.$network.' Settings')

@section('page_content')
    <!-- Main content -->
	<div class="row">
	    <div class="col-md-3 settings_bar_gap">
	        @include('admin.common.settings_bar')
	    </div>
	    <div class="col-md-9">
	        <div class="box box-info">
	            <div class="nav-tabs-custom">
	                <ul class="nav nav-tabs" id="tabs">
	                    <li <?= strtoupper($network) == 'BTC' || strtoupper($network) == 'BTCTEST' ? 'class="active"' : ''?>><a href="{{ url('admin/settings/crypto-currencies-settings/BTC')}}">BlockIo BTC/BTCTEST Settings</a></li>
	                    <li <?= strtoupper($network) == 'DOGE' || strtoupper($network) == 'DOGETEST' ? 'class="active"' :'' ?>><a href="{{ url('admin/settings/crypto-currencies-settings/DOGE')}}">BlockIo DOGE/DOGETEST Settings</a></li>
	                    <li <?= strtoupper($network) == 'LTC' || strtoupper($network) == 'LTCTEST' ? 'class="active"' :'' ?>><a href="{{ url('admin/settings/crypto-currencies-settings/LTC')}}">BlockIo LTC/LTCTEST Settings</a></li>
	                </ul>
	                <div class="tab-content">
	                    <div class="tab-pane fade in active" id="tab_1">
	                        <div class="card">
	                            <div class="card-header"><h4></h4></div>
	                            <div class="container-fluid">
	                                <form action='{{ url('admin/settings/crypto-currencies-settings/update') }}' class="form-horizontal" method="POST" id="crypto-currency-settings-form">
	                                    {!! csrf_field() !!}

	                                    <input type="hidden" name="network" value="{{ $network }}" id="network">

	                                    <!-- Version -->
	                                    <!-- This will be value = 2 set from back-end -->

	                                    <div class="box-body">
	                                        <!-- API Key -->
	                                        <div class="form-group">
	                                            <label class="col-sm-4 control-label" for="api_key">API Key</label>
	                                            <div class="col-sm-6">
	                                                <input class="form-control api_key" name="api_key" type="text" placeholder="Please enter valid api key"
	                                                value="{{ isset($cryptoCurrenciesSetting->network_credentials) ? json_decode($cryptoCurrenciesSetting->network_credentials)->api_key : '' }}" id="api_key"
	                                                onkeyup="this.value = this.value.replace(/\s/g, '')">
	                                                @if ($errors->has('api_key'))
	                                                    <span class="help-block">
	                                                        <strong>{{ $errors->first('api_key') }}</strong>
	                                                    </span>
	                                                @endif
	                                                <div class="clearfix"></div>
	                                                <small class="form-text text-muted"><strong>*Network/Crypto Currency is generated according to api key.</strong></small>
	                                                <div class="clearfix"></div>
	                                                <small class="form-text text-muted"><strong>*Updating API key will update corresponding crypto currency.</strong></small>
	                                            </div>
	                                        </div>

	                                        <!-- PIN -->
	                                        <div class="form-group">
	                                            <label class="col-sm-4 control-label" for="pin">PIN</label>
	                                            <div class="col-sm-6">
	                                                <input class="form-control pin" name="pin" type="text" placeholder="Please enter valid pin"
	                                                value="{{ isset($cryptoCurrenciesSetting->network_credentials) ? json_decode($cryptoCurrenciesSetting->network_credentials)->pin : '' }}" id="pin"
	                                                onkeyup="this.value = this.value.replace(/\s/g, '')">
	                                                @if ($errors->has('pin'))
	                                                    <span class="help-block">
	                                                        <strong>{{ $errors->first('pin') }}</strong>
	                                                    </span>
	                                                @endif
	                                            </div>
	                                        </div>

	                                        <!-- Address -->
	                                        <div class="form-group">
	                                            <label class="col-sm-4 control-label" for="address">Merchant Address</label>
	                                            <div class="col-sm-6">
	                                                <input class="form-control address" name="address" type="text" placeholder="Please enter valid merchant address"
	                                                value="{{ isset($cryptoCurrenciesSetting->network_credentials) ? json_decode($cryptoCurrenciesSetting->network_credentials)->address : '' }}" id="address"
	                                                onkeyup="this.value = this.value.replace(/\s/g, '')">
	                                                <span class="address-validation-error" style="color: red;font-weight: bold"></span>
	                                                @if ($errors->has('address'))
	                                                    <span class="help-block">
	                                                        <strong>{{ $errors->first('address') }}</strong>
	                                                    </span>
	                                                @endif
	                                            </div>
	                                        </div>

	                                        <!-- Status -->
	                                        <div class="form-group">
	                                            <label class="col-sm-4 control-label" for="status">Status</label>
	                                            <div class="col-sm-6">
	                                                <select class="form-control" name="status" id="status">
	                                                    <option value=''>Select Status</option>
	                                                    <option value='Active' {{ isset($cryptoCurrenciesSetting->status) && $cryptoCurrenciesSetting->status == 'Active' ? 'selected':"" }}>Active</option>
	                                                    <option value='Inactive' {{ isset($cryptoCurrenciesSetting->status) && $cryptoCurrenciesSetting->status == 'Inactive' ? 'selected':"" }}>Inactive</option>
	                                                </select>
	                                                <div class="clearfix"></div>
	                                                <small class="form-text text-muted"><strong>*Updating status will update corresponding crypto currency.</strong></small>
	                                            </div>
	                                        </div>
	                                    </div>
	                                    <div class="box-footer">
	                                        <a id="cancel-link" href="{{ url("admin/settings/crypto-currencies-settings/BTC") }}" class="btn btn-danger btn-flat">Cancel</a>
	                                        <button class="btn btn-primary btn-flat pull-right crypto-currencies-settings-warning" data-target="#crypto-currencies-settings-warning-modal" data-title="Update CryptoCurrency Setting" data-toggle="modal" title="Update"
	                                        type="button" id="crypto-currency-settings-submit-btn">
	                                        <i class="fa fa-spinner fa-spin" style="display: none;"></i> <span id="crypto-currency-settings-submit-btn_-ext">Update</span></button>
	                                    </div>

										<!-- Crypto Currencies Setting Warning Modal-->
										<div class="modal fade" id="crypto-currencies-settings-warning-modal" role="dialog" aria-labelledby="crypto-currencies-settings-warning-modal-label" aria-hidden="true">
										    <div class="modal-dialog">
										        <div class="modal-content">
										            <div class="modal-header">
										                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
										                <h4 class="modal-title"></h4>
										            </div>
										            <div class="modal-body">
										                <p></p>
										                <strong></strong>
										            </div>
										            <div class="modal-footer">
										                <button type="button" class="btn btn-danger" id="crypto-currencies-settings-warning-modal-confirm">Yes</button>
										                <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
										            </div>
										        </div>
										    </div>
										</div>
	                                </form>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
@endsection

@push('extra_body_scripts')
	<!-- jquery.validate -->
	<script src="{{ asset('public/dist/js/jquery.validate.min.js') }}" type="text/javascript"></script>

	<!-- Crypto Currencies Setting Warning Modal-->
	<script type="text/javascript">
		$('#crypto-currencies-settings-warning-modal').on('show.bs.modal', function (e) {
			// show modal title & message
			$(this).find('.modal-body p').text('Are you sure you want to update this setting?')
			$(this).find('.modal-title').html(`<h3><b>Warning!</b></h3>`).css({
				'color': 'red',
				'font-weight': 'bold'
			});;

			// Pass form reference to modal for submission on yes/ok
			$(this).find('.modal-footer #crypto-currencies-settings-warning-modal-confirm').data('form', $(e.relatedTarget).closest('form'));
		});

		$('#crypto-currencies-settings-warning-modal').find('.modal-footer #crypto-currencies-settings-warning-modal-confirm').on('click', function(e)
		{
			e.preventDefault();
			// hide modal
  			$('#crypto-currencies-settings-warning-modal').modal('hide');
  			// submit form
			$(this).data('form').submit();
		});
	</script>

	<script type="text/javascript">

	    var addressErrorFlag = false;
	    function checkSubmitBtn()
	    {
	        if (!addressErrorFlag)
	        {
	            $('#crypto-currency-settings-submit-btn').attr("disabled", false);
	        }
	        else
	        {
	            $('#crypto-currency-settings-submit-btn').attr("disabled", true);
	        }
	    }

	    function checkMerchantAddress()
	    {
	        var api_key = $('#api_key').val().trim();
	        var pin = $('#pin').val().trim();
	        var address = $('#address').val().trim();

	        if (api_key.length > 0 && pin.length > 0 && address.length > 0)
	        {
	            $.ajax(
	            {
	                method: "GET",
	                url: SITE_URL + "/admin/settings/crypto-currencies-settings/check-merhant-network-address",
	                dataType: "json",
	                data:
	                {
	                    'api_key': api_key,
	                    'pin': pin,
	                    'address': address,
	                },
	            })
	            .done(function(res)
	            {
	                // console.log(res)
	                if (res.status == 400)
	                {
	                    $('.address-validation-error').text(res.message);
	                    addressErrorFlag = true;
	                }
	                else
	                {
	                    $('.address-validation-error').text('');
	                    addressErrorFlag = false;

	                    // Update existing network value (THIS IS NEEDED WHEN SWITCHING BETWEEN NORMAL & TESTNET)
	                    $('#network').val(res.network)
	                }
	                checkSubmitBtn();
	            })
	            .fail(function(err)
	            {
	                console.log(err);
	            });
	        }
	        else
	        {
	            $('.address-validation-error').text('');
	            addressErrorFlag = false;
	            checkSubmitBtn();
	        }
	    }

	    // Check Merchant Api Key
	    $(document).on('blur', '#api_key', function ()
	    {
	        checkMerchantAddress();
	    });

	    // Check Merchant Pin
	    $(document).on('blur', '#pin', function ()
	    {
	        checkMerchantAddress();
	    });

	    // Check Merchant Network Address
	    $(document).on('blur', '#address', function ()
	    {
	        checkMerchantAddress();
	    });


	    $.validator.setDefaults({
	        highlight: function(element) {
	            $(element).parent('div').addClass('has-error');
	        },
	        unhighlight: function(element) {
	            $(element).parent('div').removeClass('has-error');
	        },
	        errorPlacement: function (error, element) {
	            error.insertAfter(element);
	        }
	    });

	    $('#crypto-currency-settings-form').validate({
	        rules: {
	            api_key:{
	              required: true,
	            },
	            pin:{
	              required: true,
	            },
	            address:{
	              required: true,
	            },
	            status:{
	               required: true,
	            },
	        },
	        submitHandler: function(form)
	        {
	            $("#crypto-currency-settings-submit-btn").attr("disabled", true);
	            $('#cancel-link').attr("disabled", true);
	            $(".fa-spin").show();
	            $("#crypto-currency-settings-submit-btn-text").text('Updating...');
	            $('#crypto-currency-settings-submit-btn, #cancel-link').click(function (e) {
	                e.preventDefault();
	            });
	            form.submit();
	        }
	    });
	</script>
@endpush

