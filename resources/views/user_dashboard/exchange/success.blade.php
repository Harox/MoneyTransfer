@extends('user_dashboard.layouts.app')

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
            <div class="text-center">
              <div class="confirm-btns"><i class="fa fa-check mt3"></i></div>
            </div>
            <div class="text-center">
              <div class="h3 mt10 text-success">@lang('message.dashboard.deposit.success')!</div>
            </div>

            {{-- @php
              $mailErrorText = trans('message.dashboard.mail-not-sent');
            @endphp --}}
            <!-- check mail error message-->
            {{-- @if (!empty($errorMessage))
             <div class="text-center mb-2"><p><strong>Money Exchanged Successfully {{ str_replace("mails","mail",$mailErrorText) }}.</strong></p></div>
            @else
             <div class="text-center mb-2"><p><strong>Money Exchanged Successfully.</strong></p></div>
            @endif --}}

            <p style="text-align: center; line-height:25px;" class="mb-2">
              <strong>{{  moneyFormat($fromWallet->currency->symbol, isset($transInfo['defaultAmnt']) ? formatNumber($transInfo['defaultAmnt']) : 0.00) }}</strong>
              @lang('message.dashboard.exchange.confirm.has-exchanged-to') <strong>
                {{  moneyFormat($transInfo['currSymbol'], isset($transInfo['finalAmount']) ? formatNumber($transInfo['finalAmount']) : 0.00) }}
              </strong><br/>@lang('message.dashboard.exchange.confirm.exchange-rate'):<strong> 1 {{$fromWallet->currency->code}}</strong> = <strong>{{ ($transInfo['dCurrencyRate']) }}</strong>
            {{$transInfo['currCode']}}</p>
          </div>

          <div class="card-footer" style="border-top:0;">
            <div class="text-center">
              <a href="{{url('exchange-money/print')}}/{{$transInfo['trans_ref_id']}}" target="_blank" class="btn btn-cust"><i class="fa fa-print"></i> &nbsp;
                <strong>@lang('message.dashboard.vouchers.success.print')</strong>
              </a>
              <a href="{{url('exchange')}}" class="btn btn-cust"><strong>@lang('message.dashboard.exchange.confirm.exchange-money-again')</strong></a>
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
    $(document).ready(function() {
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };
    });
    //disabling F5
    function disable_f5(e)
    {
      if ((e.which || e.keyCode) == 116)
      {
          e.preventDefault();
      }
    }
    $(document).ready(function(){
        $(document).bind("keydown", disable_f5);
    });

    //disabling ctrl+r
    function disable_ctrl_r(e)
    {
      if(e.keyCode == 82 && e.ctrlKey)
      {
        e.preventDefault();
      }
    }
    $(document).ready(function(){
        $(document).bind("keydown", disable_ctrl_r);
    });
</script>
@endsection