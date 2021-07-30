<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <title>
            Transactions
        </title>
    </head>
    <style>
        body {
        font-family: "DeJaVu Sans", Helvetica, sans-serif;
        color: #121212;
        line-height: 15px;
    }

    table, tr, td {
        padding: 6px 6px;
        border: 1px solid black;
    }

    tr {
        height: 40px;
    }

    </style>

    <body>
        <div style="width:100%; margin:0px auto;">
            <div style="height:80px">
                <div style="width:80%; float:left; font-size:13px; color:#383838; font-weight:400;">
                    <div>
                        <strong>
                            {{ ucwords(Session::get('name')) }}
                        </strong>
                    </div>
                    <br>
                    <div>
                        Period : {{ $date_range }}
                    </div>
                    <br>
                    <div>
                        Print Date : {{ dateFormat(now())}}
                    </div>
                </div>
                <div style="width:20%; float:left;font-size:15px; color:#383838; font-weight:400;">
                    <div>
                        <div>
                            @if (!empty($company_logo))
                                <img src="{{ url('public/images/logos/'.$company_logo) }}" width="288" height="90" alt="Logo"/>
                            @else
                                <img src="{{ url('public/uploads/userPic/default-logo.jpg') }}" width="288" height="90">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div style="clear:both">
            </div>
            <div style="margin-top:30px;">
                <table style="width:100%; border-radius:1px; border-collapse: collapse; text-align:center;"> <!-- Add Text Aligned here -->
                    <tr style="background-color:#f0f0f0; font-size:12px; font-weight:bold;"> <!-- Remove Text Aligned from here -->
                        <td>Date</td>
                        <td>User</td>
                        <td>Type</td>
                        <td>Amount</td>
                        <td>Fees</td>
                        <td>Total</td>
                        <td>Currency</td>
                        <td>Receiver</td>
                        <td>Status</td>
                    </tr>

                    @foreach($transactions as $transaction)
                        <tr style="background-color:#fff; text-align:center; font-size:12px; font-weight:normal;">

                            <td>{{ dateFormat($transaction->created_at) }}</td>

                            {{-- User --}}
                            @if (in_array($transaction->transaction_type_id, [Deposit, Transferred, Exchange_From, Exchange_To, Request_From, Withdrawal, Payment_Sent, Crypto_Sent, Crypto_Received]))
                                <td>{{ isset($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}</td>
                            @elseif (in_array($transaction->transaction_type_id, [Received, Request_To, Payment_Received, Crypto_Sent, Crypto_Received]))
                                <td>{{ isset($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}</td>
                            @endif

                            <td>{{ ($transaction->transaction_type->name == "Withdrawal") ? "Payout" : str_replace('_', ' ', $transaction->transaction_type->name) }}</td>

                            <td>{{ $transaction->currency->type != 'fiat' ? $transaction->subtotal : formatNumber($transaction->subtotal) }}</td>

                            <td>{{ (($transaction->charge_percentage == 0) && ($transaction->charge_fixed == 0) ? '-' : ($transaction->currency->type != 'fiat' ? $transaction->charge_fixed : formatNumber($transaction->charge_percentage + $transaction->charge_fixed))) }}</td>

                            @if ($transaction->total > 0)
                                <td>{{ '+'.($transaction->currency->type != 'fiat' ? $transaction->total : formatNumber($transaction->total)) }}</td>
                            @else
                                <td>{{ $transaction->currency->type != 'fiat' ? $transaction->total : formatNumber($transaction->total) }}</td>
                            @endif

                            <td>{{ $transaction->currency->code }}</td>

                            {{-- Receiver --}}
                            @switch($transaction->transaction_type_id)
                                @case(Deposit)
                                @case(Exchange_From)
                                @case(Exchange_To)
                                @case(Withdrawal)
                                @case(Crypto_Sent)
                                    <td>{{ isset($transaction->end_user) ? $transaction->end_user->first_name . ' ' . $transaction->end_user->last_name : "-" }}</td>
                                    @break
                                @case(Transferred)
                                @case(Received)
                                    <td>
                                        @if ($transaction->transfer->receiver)
                                        {{ $transaction->transfer->receiver->first_name.' '.$transaction->transfer->receiver->last_name }}
                                        @elseif ($transaction->transfer->email)
                                            {{ $transaction->transfer->email }}
                                        @elseif ($transaction->transfer->phone)
                                            {{ $transaction->transfer->phone }}
                                        @else
                                            {{ '-' }}
                                        @endif
                                    </td>
                                    @break
                                @case(Request_From)
                                @case(Request_To)
                                    <td>{{ isset($transaction->request_payment->receiver) ? $transaction->request_payment->receiver->first_name.' '.$transaction->request_payment->receiver->last_name : $transaction->request_payment->email }}</td>
                                    @break
                                @case(Payment_Sent)
                                    <td>{{ isset($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}</td>
                                    @break
                                @case(Payment_Received)
                                @case(Crypto_Received)
                                    <td>{{ isset($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}</td>
                                    @break
                            @endswitch

                            <td>{{ (($transaction->status == 'Blocked') ? "Cancelled" :(($transaction->status == 'Refund') ? "Refunded" : $transaction->status)) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </body>
</html>
