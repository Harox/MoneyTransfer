@extends('user_dashboard.layouts.app')

@section('css')
    <link href="//fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style type="text/css">
        @media only screen and (min-width: 768px) {
            /*.wallet-currency-div {
                padding: 18px 12px 5px 14px !important;
            }*/
        }
    </style>
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">

            <!-- for express api merchant payment success/error message-->
            @include('user_dashboard.layouts.common.alert')

            <div class="row">
                <div class="col-md-8 col-xs-12 col-sm-12 mb20 marginTopPlus">
                    <div class="flash-container">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">@lang('message.dashboard.left-table.title')</h4>
                        </div>
                        <div>
                            <div class="table-responsive">
                                <table class="table recent_activity">
                                    <thead>
                                        <tr>
                                            <td></td>
                                            <td width="25%" class="text-left">
                                                <strong>@lang('message.dashboard.left-table.date')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.description')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.status')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.amount')</strong></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($transactions->count()>0)
                                            @foreach($transactions as $key=>$transaction)
                                                <tr click="0" data-toggle="collapse" data-target="#collapseRow{{$key}}" aria-expanded="false" aria-controls="collapseRow{{$key}}"
                                                    class="show_area" trans-id="{{$transaction->id}}" id="{{$key}}">

                                                    <!-- Arrow -->
                                                    <td class="text-center arrow-size">
                                                        <strong>
                                                            <i class="fa fa-arrow-circle-right text-blue"
                                                               id="icon_{{$key}}"></i>
                                                        </strong>
                                                    </td>

                                                    <!-- Created At -->
                                                    <td class="text-left date_td" width="17%">{{ dateFormat($transaction->created_at) }}</td>

                                                    <!-- Transaction Type -->
                                                    @if(empty($transaction->merchant_id))

                                                        @if(!empty($transaction->end_user_id))
                                                            <td class="text-left">
                                                                @if($transaction->transaction_type_id)
                                                                    @if($transaction->transaction_type_id==Request_From)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Request Sent')</p>
                                                                    @elseif($transaction->transaction_type_id==Request_To)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Request Received')</p>

                                                                    @elseif($transaction->transaction_type_id == Transferred)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Transferred')</p>

                                                                    @elseif($transaction->transaction_type_id == Received)
                                                                        <p>
                                                                            {{ $transaction->end_user->first_name.' '.$transaction->end_user->last_name }}
                                                                        </p>
                                                                        <p>@lang('Received')</p>
                                                                    @else
                                                                        <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        @else

                                                           <?php
                                                                if (isset($transaction->payment_method->name))
                                                                {
                                                                    if ($transaction->payment_method->name == 'Mts')
                                                                    {
                                                                        $payment_method = getCompanyName();
                                                                    }
                                                                    else
                                                                    {
                                                                        $payment_method = $transaction->payment_method->name;
                                                                    }
                                                                }
                                                            ?>
                                                            <td class="text-left">
                                                                <p>
                                                                    @if($transaction->transaction_type->name == 'Deposit')
                                                                        @if ($transaction->payment_method->name == 'Bank')
                                                                            {{ $payment_method }} ({{ $transaction->bank->bank_name }})
                                                                        @else
                                                                            @if(!empty($payment_method))
                                                                                {{ $payment_method }}
                                                                            @endif
                                                                        @endif
                                                                    @endif

                                                                    @if($transaction->transaction_type->name == 'Withdrawal')
                                                                        @if(!empty($payment_method))
                                                                            {{ $payment_method }}
                                                                        @endif
                                                                    @endif

                                                                    @if($transaction->transaction_type->name == 'Transferred' || $transaction->transaction_type->name == 'Request_From' && $transaction->user_type = 'unregistered')
                                                                        {{ ($transaction->email) ? $transaction->email : $transaction->phone }} <!--for send money by phone - mobile app-->
                                                                    @endif
                                                                </p>

                                                                @if($transaction->transaction_type_id)
                                                                    @if($transaction->transaction_type_id==Request_From)
                                                                        <p>@lang('Request Sent')</p>
                                                                    @elseif($transaction->transaction_type_id==Request_To)
                                                                        <p>@lang('Request Received')</p>

                                                                    @elseif($transaction->transaction_type_id == Withdrawal)
                                                                        <p>@lang('Payout')</p>
                                                                    @else
                                                                        <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        @endif
                                                    @else
                                                        <td class="text-left">
                                                            <p>{{ $transaction->merchant->business_name }}</p>
                                                            @if($transaction->transaction_type_id)
                                                                <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                            @endif
                                                        </td>
                                                    @endif

                                                    <!-- Status -->
                                                    <td class="text-left">
                                                        <p id="status_{{$transaction->id}}">
                                                            {{
                                                                (
                                                                    ($transaction->status == 'Blocked') ? __("Cancelled") :
                                                                    (
                                                                        ($transaction->status == 'Refund') ? __("Refunded") : __($transaction->status)
                                                                    )
                                                                )
                                                            }}
                                                        </p>
                                                    </td>

                                                    <!-- Amount -->
                                                    @if($transaction->transaction_type_id == Deposit)
                                                        @if($transaction->subtotal > 0)
                                                            <td>
                                                                <p class="text-left text-success">+{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @endif
                                                    @elseif($transaction->transaction_type_id == Withdrawal)
                                                        <td>
                                                            <p class="text-left text-danger">-{{ formatNumber($transaction->subtotal) }}</p>
                                                            <p class="text-left">{{ $transaction->currency->code }}</p>
                                                        </td>
                                                    @elseif($transaction->transaction_type_id == Payment_Received)
                                                        @if($transaction->subtotal > 0)
                                                            @if($transaction->status == 'Refund')
                                                                <td>
                                                                    <p class="text-left text-danger">-{{ formatNumber($transaction->subtotal) }}</p>
                                                                    <p class="text-left">{{ $transaction->currency->code }}</p>
                                                                </td>
                                                            @else
                                                                <td>
                                                                    <p class="text-left text-success">+{{ formatNumber($transaction->subtotal) }}</p>
                                                                    <p class="text-left">{{ $transaction->currency->code }}</p>
                                                                </td>
                                                            @endif
                                                        @elseif($transaction->subtotal == 0)
                                                            <td class="text-left">
                                                                <p>{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @elseif($transaction->subtotal < 0)
                                                            <td>
                                                                <p class="text-left text-danger">{{ formatNumber($transaction->subtotal) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @endif
                                                    @else
                                                        @if($transaction->total > 0)
                                                            <td>
                                                                <p class="text-left text-success">{{ $transaction->currency->type != 'fiat' ? "+".$transaction->total : "+".formatNumber($transaction->total) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @elseif($transaction->total == 0)
                                                            <td class="text-left">
                                                                <p>{{ formatNumber($transaction->total) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @elseif($transaction->total < 0)
                                                            <td>
                                                                <p class="text-left text-danger">{{ $transaction->currency->type != 'fiat' ? $transaction->total : formatNumber($transaction->total) }}</p>
                                                                <p class="text-left">{{ $transaction->currency->code }}</p>
                                                            </td>
                                                        @endif
                                                    @endif
                                                </tr>

                                                <tr id="collapseRow{{$key}}" class="collapse">
                                                    <td colspan="8" class="">
                                                        <div class="row activity-details" id="loader_{{$transaction->id}}"
                                                             style="min-height: 200px">
                                                            <div class="col-md-7 col-sm-12 text-left" id="html_{{$key}}"></div>
                                                            <div class="col-md-3 col-sm-12">
                                                                <div class="right">
                                                                    @if( $transaction->transaction_type_id == Payment_Sent && $transaction->status == 'Success' && !isset($transaction->dispute->id))
                                                                        <a id="dispute_{{$transaction->id}}" href="{{url('/dispute/add/').'/'.$transaction->id}}" class="btn btn-secondary btn-sm">@lang('message.dashboard.transaction.open-dispute')</a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3 col-sm-12">
                                                            </div>

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6"> @lang('message.dashboard.left-table.no-transaction')</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-center ash-color"><a class="font-weight-bold" href="{{url('transactions')}}">@lang('message.dashboard.left-table.view-all')</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xs-12 col-sm-12 mb20 marginTopPlus">
                    <div class="flash-container">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">@lang('message.dashboard.right-table.title')</h4>
                            <div class="chart-list trans-inline float-right ">
                            </div>
                        </div>
                        <div class="wap-wed" style="width: 100%;">
                            @if($wallets->count()>0)
                                @foreach($wallets as $wallet)
                                    @php
                                        $walletCurrencyCode = encrypt(strtolower($wallet->currency->code));
                                        $walletId = encrypt($wallet->id);
                                    @endphp
                                    <div class="set-Box clearfix" style="border-bottom: 1px solid #CCCCCC;">
                                        <div class="row">
                                            <div class="col-md-12 wallet-currency-div" style="padding: 18px 25px 5px 25px;">
                                                <!--LOGO & Currency Code-->
                                                <div class="float-left" style="width: 55%;">
                                                    <!--LOGO-->
                                                    @if(empty($wallet->currency->logo))
                                                        <img src="{{asset('public/user_dashboard/images/favicon.png')}}" class="img-responsive" style="float: none;">
                                                    @else
                                                        <img src='{{asset("public/uploads/currency_logos/".$wallet->currency->logo)}}' class="img-responsive" style="float: none;">
                                                    @endif

                                                    <!--Currency Code-->
                                                    @if ($wallet->currency->type == 'fiat' && $wallet->is_default == 'Yes')
                                                        <span>{{ $wallet->currency->code }}&nbsp;<span class="badge badge-secondary">@lang('message.dashboard.right-table.default-wallet-label')</span></span>
                                                    @else
                                                        <span>{{ $wallet->currency->code }}</span>
                                                    @endif
                                                </div>
                                                <!--BALANCE-->
                                                <span class="float-right" style="position: relative;top: 7px;">
                                                    @if($wallet->balance > 0)
                                                        @if ($wallet->currency->type != 'fiat')
                                                            <span class="text-success">{{ '+'.$wallet->balance }}</span>
                                                        @else
                                                            <span class="text-success">{{ '+'.formatNumber($wallet->balance) }}</span>
                                                        @endif
                                                    @elseif($wallet->balance == 0)
                                                        @if ($wallet->currency->type != 'fiat')
                                                            <span>{{ $wallet->balance }}</span>
                                                        @else
                                                            <span>{{ '+'.formatNumber($wallet->balance) }}</span>
                                                        @endif
                                                    @elseif($wallet->balance < 0)
                                                        @if ($wallet->currency->type != 'fiat')
                                                            <span class="text-danger">{{ $wallet->balance }}</span>
                                                        @else
                                                            <span class="text-danger">{{ '+'.formatNumber($wallet->balance) }}</span>
                                                        @endif
                                                    @endif
                                                </span>
                                            </div>

                                            <!--Crypto Send & Receiv Buttons-->
                                            @if ($wallet->currency->type != 'fiat' && $wallet->currency->status == 'Active')
                                                <div class="col-md-12" style="padding: 10px 44px 14px 44px;">
                                                    <div class="text-center">
                                                        <a href="{{ url("/crpto/send/".$walletCurrencyCode."/".$walletId) }}" class="btn btn-cust-crypto float-left">@lang('message.dashboard.right-table.crypto-send')</a>
                                                        <a href="{{ url("/crpto/receive/".$walletCurrencyCode."/".$walletId) }}" class="btn btn-cust-crypto float-right">@lang('message.dashboard.right-table.crypto-receive')</a>
                                                    </div>
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            @else
                                @lang('message.dashboard.right-table.no-wallet')
                            @endif

                            <div class="clearfix"></div>
                        </div>
                        <div class="card-footer">
                            <div class="dash-btn row pb6">
                                <div class="left col-md-8 pb6">
                                    <small class="form-text text-muted"><strong>*Fiat Currencies Only</strong></small>
                                </div>
                            </div>

                            <div class="dash-btn row">
                                @if(Common::has_permission(auth()->id(),'manage_deposit'))
                                    <div class="left col-md-6 pb6">
                                        <a href="{{url('deposit')}}" class="btn btn-cust col-md-12">
                                            <img src="{{asset('public/user_dashboard/images/deposit.png')}}"
                                                 class="img-responsive" style="margin-top:3px;">
                                            &nbsp;@lang('message.dashboard.button.deposit')
                                        </a>
                                    </div>
                                @endif
                                @if(Common::has_permission(auth()->id(),'manage_withdrawal'))
                                    <div class="right col-md-6">
                                        <a href="{{url('payouts')}}" class="btn btn-cust col-md-12 ">
                                            <img src="{{asset('public/user_dashboard/images/withdrawal.png')}}" class="img-responsive"> &nbsp;@lang('message.dashboard.button.payout')
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <div class="clearfix"></div>

                            <br>
                            <div class="dash-btn row">
                                @if(Common::has_permission(auth()->id(),'manage_exchange'))
                                    <div class="center col-md-6">
                                        <a href="{{url('exchange')}}" class="btn btn-cust col-md-12">
                                            <img src="{{asset('public/user_dashboard/images/exchange.png')}}" class="img-responsive" style="margin-top:3px;">
                                            @lang('message.dashboard.button.exchange')
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')

<!-- sweetalert -->
<script src="{{asset('public/user_dashboard/js/sweetalert/sweetalert-unpkg.min.js')}}" type="text/javascript"></script>

@include('user_dashboard.layouts.common.check-user-status')

@include('common.user-transactions-scripts')

@endsection