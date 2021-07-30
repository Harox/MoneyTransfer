<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <title>
            Crypto Sent Transactions
        </title>
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
    </head>

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
                        <td>Sender</td>
                        <td>Amount</td>
                        <td>Fees</td>
                        <td>Total</td>
                        <td>Crypto Currency</td>
                        <td>Receiver</td>
                        <td>Status</td>
                    </tr>

                    @foreach($getCryptoSentTransactions as $transaction)
                        <tr style="background-color:#fff; text-align:center; font-size:12px; font-weight:normal;">

                            <td>{{ dateFormat($transaction->created_at) }}</td>

                            {{-- User --}}
                            <td>{{ !empty($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}</td>

                            <td>{{ $transaction->subtotal }}</td>

                            <td>{{ $transaction->charge_fixed }}</td>

                            @if ($transaction->total > 0)
                                <td>{{ '+'.$transaction->total }}</td>
                            @else
                                <td>{{ $transaction->total }}</td>
                            @endif

                            <td>{{ $transaction->currency->code }}</td>

                            {{-- Receiver --}}
                            <td>{{ !empty($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}</td>

                            <td>{{ $transaction->status }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </body>
</html>
