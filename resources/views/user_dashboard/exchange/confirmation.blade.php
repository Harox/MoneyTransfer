@extends('user_dashboard.layouts.app')

@section('css')
    <style>
        @media only screen and (max-width: 300px) {
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
						<h4>@lang('message.dashboard.exchange.confirm.title')</h4>
					</div>
					<div class="wap-wed mt20 mb20">
						<p class="mb20">@lang('message.dashboard.exchange.confirm.exchanging') <strong>{{ $fromCurrency->code }}</strong>
							@lang('message.dashboard.exchange.confirm.of') <strong>{{ isset($transInfo['defaultAmnt']) ? formatNumber($transInfo['defaultAmnt']) : 0.00 }}</strong>
							@lang('message.dashboard.exchange.confirm.equivalent-to') <strong>{{ isset($transInfo['finalAmount']) ? formatNumber($transInfo['finalAmount']) : 0.00 }} {{ $transInfo['currCode'] }}</strong><br/>@lang('message.dashboard.exchange.confirm.exchange-rate'):  &nbsp;<strong>1 {{$fromCurrency->code}} </strong>= <strong>
							{{ ($transInfo['dCurrencyRate']) }} {{ $transInfo['currCode'] }}</strong></p>

						<div class="h5"><strong>@lang('message.dashboard.confirmation.details')</strong></div>
						<div class="confn-border">
							<div class="row mt20">
								<div class="col-md-12">
									<div class="pull-left">@lang('message.dashboard.exchange.confirm.amount')</div>
									<div class="pull-right"><strong>{{  moneyFormat($fromCurrency->symbol, isset($transInfo['defaultAmnt']) ? formatNumber($transInfo['defaultAmnt']) : 0.00) }}</strong></div>
								</div>
							</div>

							<div class="row mt10">
								<div class="col-md-12">
									<div class="pull-left">@lang('message.dashboard.confirmation.fee')</div>
									<div class="pull-right"><strong>{{  moneyFormat($fromCurrency->symbol, isset($transInfo['fee']) ? formatNumber($transInfo['fee']) : 0.00) }}</strong></div>
								</div>
							</div>
							<hr />
							<div class="row">
								<div class="col-md-12">
									<div class="pull-left h6"><strong>@lang('message.dashboard.confirmation.total')</strong></div>
									<div class="pull-right"><strong>{{  moneyFormat($fromCurrency->symbol, isset($transInfo['totalAmount']) ? formatNumber($transInfo['totalAmount']) : 0.00) }}</strong></div>
								</div>
							</div>
						</div>
					</div>

					<div class="card-footer">
						<div class="text-center">
							<a href="#" class="exchange-confirm-back-link">
								<button class="btn btn-cust float-left exchange-confirm-back-btn"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
							</a>
                            <a href="{{url('exchange-of-money-success')}}" class="exchange-confirm-submit-link">
								<button class="btn btn-cust float-right exchange-confirm-submit-btn">
							    	<i class="fa fa-spinner fa-spin" style="display: none;" id="spinner"></i>
							    	<strong>
							    		<span class="exchange-confirm-submit-btn-txt">
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

	function exchangeBack()
	{
		localStorage.setItem("previousUrl",document.URL);
		window.history.back();
	}

	$(document).on('click', '.exchange-confirm-submit-btn', function (e)
    {
    	$(".fa-spin").show();
    	$('.exchange-confirm-submit-btn-txt').text("{{__('Confirming...')}}");
    	$(this).attr("disabled", true);
    	$('.exchange-confirm-submit-link').click(function (e) {
            e.preventDefault();
        });

        //Make back button disabled and prevent click
        $('.exchange-confirm-back-btn').attr("disabled", true).click(function (e)
        {
            e.preventDefault();
        });

        //Make back anchor prevent click
        $('.exchange-confirm-back-link').click(function (e)
        {
            e.preventDefault();
        });
    });

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.exchange-confirm-back-btn', function (e)
    {
        e.preventDefault();
        exchangeBack();
    });

</script>

@endsection