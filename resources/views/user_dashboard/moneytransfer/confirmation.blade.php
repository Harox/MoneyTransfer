@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 260px) {
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
							<h4>@lang('message.dashboard.send-request.send.confirmation.title')</h4>
						</div>
						<div class="wap-wed mt20 mb20">
							<p class="mb20">@lang('message.dashboard.send-request.send.confirmation.send-to')&nbsp;&nbsp;<strong>{{ isset($transInfo['receiver']) ? $transInfo['receiver'] : '' }}</strong></p>
							<div class="h5"><strong>@lang('message.dashboard.confirmation.details')</strong></div>
							<div class="row mt20">
								<div class="col-md-12">
									<div class="pull-left">@lang('message.dashboard.send-request.send.confirmation.transfer-amount')</div>
									<div class="pull-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['amount'])) }}</strong></div>
								</div>
							</div>
							{{-- <div class="row mt10">
								<div class="col-md-12">
									<div class="pull-left">@lang('message.dashboard.confirmation.fee')</div>
									<div class="pull-right text-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['fee'])) }}</strong></div>
								</div>
							</div> --}}
							<hr />
							<div class="row">
								<div class="col-md-12">
									<div class="pull-left h6"><strong>@lang('message.dashboard.confirmation.total')</strong></div>
									<div class="pull-right text-right"><strong>{{ moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['totalAmount'])) }}</strong></div>
								</div>
							</div>
						</div>
						<div class="card-footer">
							<div class="text-center">
								<a href="#" class="send-money-confirm-back-link">
								 	<button class="btn btn-cust float-left send-money-confirm-back-button"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
								</a>
								<a href="{{url('send-money-confirm')}}" class="sendMoneyPaymentConfirmLink">
									<button class="btn btn-cust float-right sendMoneyConfirm">
								    	<i class="fa fa-spinner fa-spin" style="display: none;" id="spinner"></i>
								    	<strong>
								    		<span class="sendMoneyConfirmText">
								    			@lang('message.dashboard.button.confirm') &nbsp; <i class="fa fa-angle-right"></i>
								    		</span>
								    	</strong>
								    </button>
								</a>
							</div>
						</div>
					</div>
				</div>
				<!--/col-->
			</div>
			<!--/row-->
		</div>
	</section>
@endsection

@section('js')
<script type="text/javascript">

	$(document).on('click', '.sendMoneyConfirm', function (e)
    {
    	$(".fa-spin").show()
    	$('.sendMoneyConfirmText').text("{{__('Confirming...')}}");
    	$(this).attr("disabled", true);
    	$('.sendMoneyPaymentConfirmLink').click(function (e)
        {
            e.preventDefault();
        });

        //Make back button disabled and prevent click
        $('.send-money-confirm-back-button').attr("disabled", true).click(function (e)
        {
            e.preventDefault();
        });

        //Make back anchor prevent click
    	$('.send-money-confirm-back-link').click(function (e)
        {
            e.preventDefault();
        });
    });

	//Only go back by back button, if submit button is not clicked
    $(document).on('click', '.send-money-confirm-back-button', function (e)
    {
    	e.preventDefault();
        window.history.back();
    });

</script>
@endsection
