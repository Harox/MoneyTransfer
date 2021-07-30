@extends('admin.layouts.master')

@section('title', 'Crypto Send Success')

@section('page_content')

<style type="text/css">
/*---------------------confirmation---------------------*/
.confirm-btns {
    width: 35px;
    height: 35px;
    background-color: #58c42b !important;
    border-radius: 50%;
    border: 1px solid #247701;
    color: #FFFFFF;
    text-align: center;
    line-height: 25px;
    font-size: 25px;
    text-shadow: #009933;
    margin: 0 auto;
}
</style>


<div class="box">
   <div class="panel-body">
        <ul class="nav nav-tabs cus" role="tablist">
            <li class="active">
              <a href='{{url("admin/users/edit/$userId")}}'>Profile</a>
            </li>

            <li>
              <a href="{{url("admin/users/transactions/$userId")}}">Transactions</a>
            </li>
            <li>
              <a href="{{url("admin/users/wallets/$userId")}}">Wallets</a>
            </li>
            <li>
              <a href="{{url("admin/users/tickets/$userId")}}">Tickets</a>
            </li>
            <li>
              <a href="{{url("admin/users/disputes/$userId")}}">Disputes</a>
            </li>
       </ul>
      <div class="clearfix"></div>
   </div>
</div>

<div class="row">
    <div class="col-md-2">
    </div>
    <div class="col-md-6"></div>
    <div class="col-md-4">
        <div class="pull-right">
            <h3>{{ $user_full_name }}</h3>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-7">

                        <div class="panel panel-default">
                            <div class="panel-body">

                                <div class="text-center">
                                  <div class="confirm-btns"><i class="fa fa-check"></i></div>
                                </div>
                                <div class="text-center">
                                    <div class="h3 mt6 text-success"> Success!</div>
                                </div>
                                <div class="text-center"><p><strong> {{ $walletCurrencyCode }} Sent Successfully.</strong></p></div>
                                <div class="text-center"><p><strong> Amount will be added after {{ $confirmations }} confirmations.</strong></p></div>
                                <div class="text-center"><p> Address: {{ $receiverAddress }}</p></div>
                                <h5 class="text-center mt10">Sent Amount : {{ moneyFormat($currencySymbol, number_format($amount, 8, '.', '')) }}</h5>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div style="margin-left: 0 auto">
                            <div style="float: left;">
                                  <a href="{{ url("admin/users/deposit/crypto/send-receive/print/".encrypt($transactionId)) }}" target="_blank" class="btn button-secondary"><strong>Print</strong></a>
                            </div>
                            <div style="float: right;">
                                <a href="{{url("admin/users/crypto/send/$userId")}}" class="btn button-secondary"><strong>Send {{ $walletCurrencyCode }} Again</strong></a>
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
<script type="text/javascript">
</script>
@endpush