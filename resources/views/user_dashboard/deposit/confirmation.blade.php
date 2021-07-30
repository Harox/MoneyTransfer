@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 240px) {
            .pull-right {
            float: left;
            }
        }
    </style>
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">
            <div class="row">
				<div class="col-md-7 col-xs-12 mb20 marginTopPlus">
                <div class="card">
					<div class="card-header">
					   <h4>@lang('message.dashboard.deposit.title')</h4>
                    </div>
                    <div class="wap-wed mt20 mb20">

                        <p class="mb20">@lang('message.dashboard.deposit.deposit-via')&nbsp;&nbsp;<strong><img src="{{asset("public/images/payment_gateway")}}/{{ strtolower($transInfo['payment_name']) }}"/></strong></p>
                        <div class="h5"><strong>@lang('message.dashboard.confirmation.details')</strong></div>

                        <div class="row mt20">
                            <div class="col-md-12">
                                <div class="pull-left">@lang('message.dashboard.deposit.deposit-amount')</div>
                                <div class="pull-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['amount'])) }}</strong></div>
                            </div>
                        </div>

                        <div class="row mt10">
                            <div class="col-md-12">
                                <div class="pull-left">@lang('message.dashboard.confirmation.fee')</div>
                                <div class="pull-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['fee'])) }}</strong></div>
                            </div>
                        </div>
                        <hr />

                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-left h6"><strong>@lang('message.dashboard.confirmation.total')</strong></div>
                                <div class="pull-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['totalAmount'])) }}</strong></div>
                            </div>
                        </div>
					</div>

                    <div class="card-footer" style="margin-left: 0 auto">
					    <div style="float: left;">
                            <a href="#" class="deposit-confirm-back-link">
                                <button class="btn btn-cust deposit-confirm-back-btn"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
                            </a>
						</div>
					    <div style="float: right;">
                            <form action="{{url('deposit/store')}}" style="display: block;" method="POST" accept-charset="UTF-8" id="deposit_form" novalidate="novalidate" enctype="multipart/form-data">
                                <input value="{{csrf_token()}}" name="_token" id="token" type="hidden">
                                <input value="{{$transInfo['payment_method']}}" name="method" id="method" type="hidden">
                                <input value="{{$transInfo['totalAmount']}}" name="amount" id="amount" type="hidden">
								<button type="submit" class="btn btn-cust" id="deposit-money-confirm">
		                  			<i class="spinner fa fa-spinner fa-spin" style="display: none;"></i> <span id="deposit-money-confirm-text" style="font-weight: bolder;">@lang('message.dashboard.button.confirm')&nbsp; <i class="fa fa-angle-right"></i></span>
		                  		</button>
							</form>
						</div>
                    </div>
                </div>
            </div>
                <!--/col-->
            </div>
            <!--/row-->
        </div>
    </section>
@include('user_dashboard.layouts.common.help')
@endsection

@section('js')
<script src="{{asset('public/user_dashboard/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script src="{{asset('public/user_dashboard/js/additional-methods.min.js')}}" type="text/javascript"></script>

<script>

    function depositBack()
    {
        window.localStorage.setItem("depositConfirmPreviousUrl",document.URL);
        window.history.back();
    }

    jQuery.extend(jQuery.validator.messages, {
        required: "{{__('This field is required.')}}",
    })

    $('#deposit_form').validate({
        rules: {
            amount: {
                required: false,
            },
            method: {
                required: false,
            },
        },
        submitHandler: function(form)
        {
            $("#deposit-money-confirm").attr("disabled", true);
            $(".spinner").show();
            var pretext=$("#deposit-money-confirm-text").text();
            $("#deposit-money-confirm-text").text("{{__('Confirming...')}}");

            //Make back button disabled and prevent click
            $('.deposit-confirm-back-btn').attr("disabled", true).click(function (e)
            {
                e.preventDefault();
            });

            //Make back anchor prevent click
            $('.deposit-confirm-back-link').click(function (e)
            {
                e.preventDefault();
            });

            form.submit();

            setTimeout(function(){
                $("#deposit-money-confirm").removeAttr("disabled");
                $(".spinner").hide();
                $("#deposit-money-confirm-text").text(pretext);
            },10000);
        }
    });

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.deposit-confirm-back-btn', function (e)
    {
        e.preventDefault();
        depositBack();
    });

</script>
@endsection