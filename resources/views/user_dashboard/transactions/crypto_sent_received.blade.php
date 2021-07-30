<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        @if ($transaction->transaction_type_id == Crypto_Sent)
        	<title>{{ __("Crypto Sent") }}</title>
        @else
        	<title>{{ __("Crypto Received") }}</title>
        @endif
    </head>
    <style>
        body{ font-family: 'Lato', sans-serif; color:#121212;}
        hr { border-top:1px solid #f0f0f0;}
        table { border-collapse:collapse;}
        .code td{ padding:5px;}
    </style>
    <body>
        <div style="width:900px; margin:auto; top:20px; position:relative;">
            <table style="margin-bottom:40px;">
                <tr>
                    <td>
                        @if (!empty($companyInfo['value']))
                        <img src='{{ public_path("/images/logos/".$companyInfo["value"]) }}' width="288" height="90" alt="Logo"/>
                        @else
                        <img src="{{ url('public/uploads/userPic/default-logo.jpg') }}" width="288" height="90">
                        @endif
                    </td>
                </tr>
            </table>
            @if ($transaction->transaction_type_id == Crypto_Sent)
                <table>
                    <tr>
                        <td>
                            <table style="margin-top:20px;">
                                @if (isset($receiverAddress))
                                    <tr>
                                        {{-- TODO: translation --}}
                                        <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.crypto.transactions.receiver-address')</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ $receiverAddress }}</td>
                                    </tr>
                                    <br><br>
                                @endif
                                @if (isset($confirmations))
                                    <tr>
                                        {{-- TODO: translation --}}
                                        <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.crypto.transactions.confirmations')</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ $confirmations }}</td>
                                    </tr>
                                    <br><br>
                                @endif
                                <tr>
                                    <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.transaction-id')</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{$transaction->uuid}}</td>
                                </tr>
                                <br><br>
                                <tr>
                                    <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.transaction-date')</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ dateFormat($transaction->created_at) }}</td>
                                </tr>
                                <br><br>
                                <tr>
                                    <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.form.status')</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ __($transaction->status) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table style="margin-top:20px; width:300px;">
                                <tr>
                                    <td colspan="2" style="font-size:16px; color:#000000; font-weight:bold;">@lang('message.dashboard.left-table.details')</td>
                                </tr>
                                <tr>
                                    {{-- TODO: translation --}}
                                    <td style="font-size:15px; color:#000000;">@lang('message.dashboard.crypto.send.confirm.sent-amount')</td>
                                    <td style="font-size:15px; color:#4e5c6e; text-align:right;">{{ moneyFormat($transaction->currency->symbol, $transaction->subtotal) }}</td>
                                </tr>
                                <tr style="padding-bottom:10px;">
                                    {{-- TODO: translation --}}
                                    <td style="font-size:15px; color:#000000;">@lang('message.dashboard.crypto.send.confirm.network-fee')</td>
                                    <td style="font-size:15px; color:#4e5c6e; text-align:right;">{{ moneyFormat($transaction->currency->symbol, $transaction->charge_fixed) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-top:1px solid #eaeaea; padding-top:0; margin-bottom:3px;"></td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#000000; font-weight:bold;">@lang('message.dashboard.left-table.total')</td>
                                    <td style="font-size:15px; color:#4e5c6e; text-align:right; font-weight:bold;">{{ moneyFormat($transaction->currency->symbol, $transaction->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @else
                <table>
                    <tr>
                        <td>
                            <table style="margin-top:20px;">
                                @if (isset($senderAddress))
                                    <tr>
                                        {{-- TODO: translation --}}
                                        <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.crypto.transactions.sender-address')</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ $senderAddress }}</td>
                                    </tr>
                                    <br><br>
                                @endif
                                @if (isset($confirmations))
                                    <tr>
                                        {{-- TODO: translation --}}
                                        <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.crypto.transactions.confirmations')</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ $confirmations }}</td>
                                    </tr>
                                    <br><br>
                                @endif
                                <tr>
                                    <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.transaction-id')</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{$transaction->uuid}}</td>
                                </tr>
                                <br><br>
                                <tr>
                                    <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.transaction-date')</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ dateFormat($transaction->created_at) }}</td>
                                </tr>
                                <br><br>
                                <tr>
                                    <td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.form.status')</td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ __($transaction->status) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table style="margin-top:20px; width:300px;">
                                <tr>
                                    <td colspan="2" style="font-size:16px; color:#000000; font-weight:bold;">@lang('message.dashboard.left-table.details')</td>
                                </tr>
                                <tr>
                                    {{-- TODO: translation --}}
                                    <td style="font-size:15px; color:#000000;">@lang('message.dashboard.left-table.received.received-amount')</td>
                                    <td style="font-size:15px; color:#4e5c6e; text-align:right;">{{ moneyFormat($transaction->currency->symbol, $transaction->subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-top:1px solid #eaeaea; padding-top:0; margin-bottom:3px;"></td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px; color:#000000; font-weight:bold;">@lang('message.dashboard.left-table.total')</td>
                                    <td style="font-size:15px; color:#4e5c6e; text-align:right; font-weight:bold;">{{ moneyFormat($transaction->currency->symbol, $transaction->subtotal) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endif
        </div>
    </body>
</html>