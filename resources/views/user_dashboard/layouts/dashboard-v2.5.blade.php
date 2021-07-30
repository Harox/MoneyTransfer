@extends('user_dashboard.layouts.app')

@section('css')
    <link href="//fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style type="text/css">
        .sync-crypto-balance {
            font-size: 16px !important;
            font-weight: bold;
            color: black;
        }
    </style>
@endsection

@section('content')
    <section class="section-06 history padding-30">
        <div class="container">

            @include('user_dashboard.layouts.common.alert') <!-- for express api merchant payment success/error message-->

            <div class="row">
                <div class="col-md-7 col-xs-12 col-sm-12 mb20 marginTopPlus">
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
                                            <td width="17%" class="text-left">
                                                <strong>@lang('message.dashboard.left-table.date')</strong></td>
                                            <td><strong>&nbsp;&nbsp;</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.description')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.status')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.currency')</strong></td>
                                            <td class="text-left">
                                                <strong>@lang('message.dashboard.left-table.amount')</strong></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($transactions->count()>0)
                                            @foreach($transactions as $key=>$transaction)
                                                <tr click="0" data-toggle="collapse" data-target="#collapseRow{{$key}}"
                                                    aria-expanded="false" aria-controls="collapseRow{{$key}}"
                                                    class="show_area" trans-id="{{$transaction->id}}" id="{{$key}}">
                                                    <td class="text-center arrow-size">
                                                        <strong>
                                                            <i class="fa fa-arrow-circle-right text-blue"
                                                               id="icon_{{$key}}"></i>
                                                        </strong>
                                                    </td>
                                                    <td class="text-left date_td" width="17%">{{ dateFormat($transaction->created_at) }}</td>
                                                        @if(empty($transaction->merchant_id))

                                                            @if($transaction->end_user_id)

                                                                @if(!empty($transaction->end_user->picture))
                                                                    <td class="text-left">
                                                                        <img src="{{url('public/user_dashboard/profile/thumb/'.$transaction->end_user->picture)}}" class="rounded-circle rounded-circle-custom-trans endUserStatus" data-endUserStatus="{{ $transaction->end_user->status }}">
                                                                    </td>
                                                                @else
                                                                    <td class="text-left">
                                                                        <img src="{{url('public/user_dashboard/images/avatar.jpg')}}" class="rounded-circle rounded-circle-custom-trans">
                                                                    </td>
                                                                @endif
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
                                                                            //$payment_method = 'Pay Money';
                                                                            //$img            = strtolower($transaction->payment_method->name) . '.jpg';
                                                                            $payment_method = getCompanyName();
                                                                            $img            = getCompanyLogoWithoutSession();
                                                                        }
                                                                        else
                                                                        {
                                                                            $payment_method = $transaction->payment_method->name;
                                                                            $img            = strtolower($payment_method) . '.jpg';
                                                                        }
                                                                    }
                                                                ?>
                                                                <td class="text-left">
                                                                    @if($transaction->transaction_type->name == 'Deposit')
                                                                        @if ($transaction->payment_method->name == 'Bank')
                                                                            @if (!empty($transaction->bank->file_id))
                                                                                @php $bank_logo = $transaction->bank->file->filename; @endphp
                                                                                <img src='{{url("public/uploads/files/bank_logos/$bank_logo")}}' class="rounded-circle rounded-circle-custom-trans">
                                                                            @else
                                                                                <img src='{{url("public/images/payment_gateway/bank.jpg")}}' class="rounded-circle rounded-circle-custom-trans">
                                                                            @endif
                                                                        @else
                                                                            @if(!empty($payment_method))
                                                                                @if ($transaction->payment_method_id == 1)
                                                                                    @if (!empty($img))
                                                                                        <img src="{{asset('public/images/logos/'.$img)}}" class="rounded-circle rounded-circle-custom-trans">
                                                                                    @else
                                                                                        <img src="{{asset('public/uploads/userPic/default-logo.jpg')}}" class="rounded-circle rounded-circle-custom-trans">
                                                                                    @endif
                                                                                @else
                                                                                    <img src='{{url("public/images/payment_gateway/thumb/$img")}}' class="rounded-circle rounded-circle-custom-trans">
                                                                                @endif
                                                                            @endif
                                                                        @endif

                                                                    @elseif($transaction->transaction_type->name == 'Withdrawal')
                                                                        @if(!empty($payment_method))
                                                                            @if ($transaction->payment_method_id == 1)
                                                                                @if (!empty($img))
                                                                                    <img src="{{asset('public/images/logos/'.$img)}}" class="rounded-circle rounded-circle-custom-trans">
                                                                                @else
                                                                                    <img src="{{asset('public/uploads/userPic/default-logo.jpg')}}" class="rounded-circle rounded-circle-custom-trans">
                                                                                @endif
                                                                            @else
                                                                                <img src='{{url("public/images/payment_gateway/thumb/$img")}}' class="rounded-circle rounded-circle-custom-trans">
                                                                            @endif
                                                                        @endif

                                                                    @elseif($transaction->transaction_type_id==Exchange_To || $transaction->transaction_type_id==Exchange_From)
                                                                        <img src='{{url("public/frontend/images/exchange.png")}}' class="rounded-circle rounded-circle-custom-trans">

                                                                    @elseif($transaction->transaction_type_id == Request_From || $transaction->transaction_type_id==Transferred)
                                                                        <img src="{{url('public/user_dashboard/images/avatar.jpg')}}" class="rounded-circle rounded-circle-custom-trans">

                                                                    @endif
                                                                </td>
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
                                                                @if($transaction->merchant->logo)
                                                                    <img src="{{url('public/user_dashboard/merchant/thumb').'/'.$transaction->merchant->logo}}"
                                                                         class="rounded-circle rounded-circle-custom-trans">
                                                                @else
                                                                    <img src="{{url('public/uploads/merchant/merchant.jpg')}}"
                                                                         class="rounded-circle rounded-circle-custom-trans">
                                                                @endif
                                                            </td>
                                                            <td class="text-left">
                                                                <p>{{ $transaction->merchant->business_name }}</p>
                                                                @if($transaction->transaction_type_id)
                                                                    <p>{{ __(str_replace('_',' ',$transaction->transaction_type->name)) }}</p>
                                                                @endif
                                                            </td>
                                                        @endif

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

                                                    <td class="text-left">
                                                        <p>{{ $transaction->currency->code }} </p>
                                                    </td>

                                                    @if($transaction->transaction_type_id == Deposit)
                                                        @if($transaction->subtotal > 0)
                                                            <td class="text-left text-success"><p>+{{ formatNumber($transaction->subtotal) }}</p></td>
                                                        @endif
                                                    @elseif($transaction->transaction_type_id == Payment_Received)
                                                        @if($transaction->subtotal > 0)
                                                            @if($transaction->status == 'Refund') <!-- fixed - pm_v2.3 -->
                                                                <td class="text-left text-danger"><p>-{{ formatNumber($transaction->subtotal) }}</p></td>
                                                            @else
                                                                <td class="text-left text-success"><p>+{{ formatNumber($transaction->subtotal) }}</p></td>
                                                            @endif
                                                        @elseif($transaction->subtotal == 0)
                                                            <td class="text-left">
                                                                <p>{{ formatNumber($transaction->subtotal) }}</p>
                                                            </td>
                                                        @elseif($transaction->subtotal < 0)
                                                            <td class="text-left text-danger">
                                                                <p>{{ formatNumber($transaction->subtotal) }}</p>
                                                            </td>
                                                        @endif
                                                    @else
                                                        @if($transaction->total > 0)
                                                            <td class="text-left text-success"><p>
                                                                +{{ formatNumber($transaction->total) }}</p>
                                                            </td>
                                                        @elseif($transaction->total == 0)
                                                            <td class="text-left">
                                                                <p>{{ formatNumber($transaction->total) }}</p>
                                                            </td>
                                                        @elseif($transaction->total < 0)
                                                            <td class="text-left text-danger">
                                                                <p>{{ formatNumber($transaction->total) }}</p>
                                                            </td>
                                                        @endif
                                                    @endif
                                                </tr>
                                                <tr id="collapseRow{{$key}}" class="collapse">
                                                    <td colspan="8" class="">
                                                        <div class="row activity-details" id="loader_{{$transaction->id}}"
                                                             style="min-height: 200px">

                                                            <div class="col-md-6 col-sm-12 text-left" id="html_{{$key}}">
                                                            </div>

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
                <div class="col-md-5 col-xs-12 col-sm-12 mb20 marginTopPlus">
                    <div class="flash-container">
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="float-left trans-inline">@lang('message.dashboard.right-table.title')</h4>
                            <div class="chart-list trans-inline float-right ">
                            </div>
                        </div>
                        <div>
                            @if($wallets->count()>0)
                                @foreach($wallets as $wallet)
                                @php
                                    $walletCurrencyCode = encrypt(strtolower($wallet->currency->code));
                                    $walletId = encrypt($wallet->id);
                                @endphp
                                <div class="set-Box clearfix">
                                    <ul>
                                        <li>
                                            @if(empty($wallet->currency->logo))
                                                <img src="{{asset('public/user_dashboard/images/favicon.png')}}" class="img-responsive">
                                            @else
                                                <img src='{{asset("public/uploads/currency_logos/".$wallet->currency->logo)}}' class="img-responsive">
                                            @endif

                                            @if($wallet->is_default == 'Yes')
                                                <span class="trans-inline sb-title"> <p>{{ $wallet->currency->name }}
                                                    <span class="badge badge-secondary">@lang('message.dashboard.right-table.default-wallet-label')</span></p>
                                                </span>
                                            @else
                                                @if ($wallet->currency->type != 'fiat')
                                                    <span class="trans-inline sb-title"> <p>{{ $wallet->currency->name }} </p></span>
                                                @else
                                                    <span class="trans-inline sb-title"> <p>{{ $wallet->currency->name }} </p></span>
                                                @endif
                                            @endif

                                        </li>
                                        <li class="text-right sb-title">
                                            @if($wallet->balance > 0)
                                                @if ($wallet->currency->type != 'fiat')
                                                    <p class="text-success">{{ moneyFormat($wallet->currency->code, '+'.formatNumber($wallet->balance)) }} <i class="material-icons sync-crypto-balance" data-wallet-Id="{{ $walletId }}"
                                                        data-wallet-currency-code="{{ $walletCurrencyCode }}">sync</i></p>
                                                @else
                                                    <p class="text-success">{{ moneyFormat($wallet->currency->code, '+'.formatNumber($wallet->balance)) }}</p>
                                                @endif
                                            @elseif($wallet->balance == 0)
                                                @if ($wallet->currency->type != 'fiat')
                                                    <p>{{ moneyFormat($wallet->currency->code,formatNumber($wallet->balance)) }} <i class="material-icons sync-crypto-balance" data-wallet-Id="{{ $walletId }}" data-wallet-currency-code="{{ $walletCurrencyCode }}">sync</i></p>
                                                @else
                                                    <p>{{ moneyFormat($wallet->currency->code,formatNumber($wallet->balance)) }}</p>
                                                @endif
                                            @elseif($wallet->balance < 0)
                                                @if ($wallet->currency->type != 'fiat')
                                                    <p class="text-danger">{{ moneyFormat($wallet->currency->code,formatNumber($wallet->balance)) }} <i class="material-icons sync-crypto-balance" data-wallet-Id="{{ $walletId }}" data-wallet-currency-code="{{ $walletCurrencyCode }}">sync</i></p>
                                                @else
                                                    <p class="text-danger">{{ moneyFormat($wallet->currency->code,formatNumber($wallet->balance)) }}</p>
                                                @endif
                                            @endif
                                        </li>

                                        <div class="row pb6">
                                            @if ($wallet->currency->type != 'fiat')
                                                <a href="{{ url("/crpto/send/".$walletCurrencyCode."/".$walletId) }}" class="btn btn-cust-crypto left">Send</a>
                                                <a href="{{ url("/crpto/receive/".$walletCurrencyCode."/".$walletId) }}" class="btn btn-cust-crypto right">Receive</a>
                                            @endif
                                        </div>

                                        @endforeach
                                    </ul>
                                </div>
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