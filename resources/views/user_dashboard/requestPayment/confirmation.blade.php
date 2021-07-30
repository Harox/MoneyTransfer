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
							<h4>@lang('message.dashboard.send-request.request.confirmation.title')</h4>
						</div>
						<div class="wap-wed mt20 mb20">
							<p class="mb20">@lang('message.dashboard.send-request.request.confirmation.request-money-from')&nbsp;&nbsp;<strong>{{ isset($transInfo['email']) ? $transInfo['email'] : '' }}</strong></p>
							<div class="row mt20 mb20">
								<div class="col-md-12">
									<div class="pull-left"><strong>@lang('message.dashboard.send-request.request.confirmation.requested-amount')</strong></div>
									<div class="pull-right"><strong>{{  moneyFormat($transInfo['currSymbol'], formatNumber($transInfo['amount'])) }}</strong></div>
								</div>
							</div>
						</div>
						<div class="card-footer">
							<div class="text-center">
								<a href="#" class="request-create-confirm-back-link">
									<button class="btn btn-cust float-left request-create-confirm-back-btn"><strong><i class="fa fa-angle-left"></i>&nbsp;&nbsp;@lang('message.dashboard.button.back')</strong></button>
								</a>

								<a href="{{url('request-money-confirm')}}" class="request-create-confirm-submit-link">
									<button class="btn btn-cust float-right request-create-confirm-submit-btn">
								    	<i class="fa fa-spinner fa-spin" style="display: none;" id="spinner"></i>
								    	<strong>
								    		<span class="request-create-confirm-submit-btn-txt">
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

	$(document).on('click', '.request-create-confirm-submit-btn', function (e)
    {
    	$(".fa-spin").show();
    	$('.request-create-confirm-submit-btn-txt').text("{{__('Confirming...')}}");
    	$(this).attr("disabled", true);
    	$('.request-create-confirm-submit-link').click(function (e) {
            e.preventDefault();
        });

        //Make back button disabled and prevent click
        $('.request-create-confirm-back-btn').attr("disabled", true).click(function (e)
        {
            e.preventDefault();
        });

        //Make back anchor prevent click
        $('.request-create-confirm-back-link').click(function (e)
        {
            e.preventDefault();
        });
    });

    //Only go back by back button, if submit button is not clicked
    $(document).on('click', '.request-create-confirm-back-btn', function (e)
    {
        e.preventDefault();
        window.history.back();
    });

</script>

@endsection