<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Print</title>
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
	     	@if(!empty($companyInfo['value']))
		      	<img src='{{ public_path("/images/logos/".$companyInfo["value"]) }}' width="288" height="90" alt="Logo"/>
		    @else
                <img src="{{ url('public/uploads/userPic/default-logo.jpg') }}" width="288" height="90">
            @endif
	     </td>
	   </tr>
	 </table>

<table>
	<tr>
	  <td>
	   <table>
		  <tr>
			<td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.exchange-from.exchange-from-title')</td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{$currencyExchange->fromWallet->currency->code}}</td>
		  </tr>
		  <br><br>

		  <tr>
			<td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.exchange-from.exchange-to-title')</td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{$currencyExchange->toWallet->currency->code}}</td>
		  </tr>
		  <br><br>

		  <tr>
			<td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.exchange.right.title')</td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#4e5c6e; line-height:22px;">1 {{$currencyExchange->fromWallet->currency->code}} = {{ (float)($currencyExchange->exchange_rate) }} {{$currencyExchange->toWallet->currency->code}}</td>
		  </tr>
		  </table>
	  </td>
	  </tr>

	<tr>
	  <td>
	   <table style="margin-top:20px;">
		  <tr>
			<td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.transaction-id')</td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{$currencyExchange->uuid}}</td>
		  </tr>
		  <br><br>

		  <tr>
			<td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.dashboard.left-table.transaction-date')</td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ dateFormat($currencyExchange->created_at) }}</td>
		  </tr>
		  <br><br>

		  <tr>
			<td style="font-size:16px; color:#000000; line-height:25px; font-weight:bold;">@lang('message.form.status')</td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#4e5c6e; line-height:22px;">{{ (($currencyExchange->status == 'Blocked') ? __("Cancelled") :(($currencyExchange->status == 'Refund') ? __("Refunded"):
								__($currencyExchange->status))) }}</td>
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
			<td style="font-size:15px; color:#000000;">@lang('message.dashboard.left-table.exchange-from.exchange-from-amount')</td>
			<td style="font-size:15px; color:#4e5c6e; text-align:right;">{{ moneyFormat($currencyExchange->fromWallet->currency->symbol, formatNumber($currencyExchange->amount)) }}</td>
		  </tr>

			@if ($currencyExchange->fee > 0)
				<tr style="padding-bottom:10px;">
					<td style="font-size:15px; color:#000000;">@lang('message.dashboard.left-table.fee')</td>
					<td style="font-size:15px; color:#4e5c6e; text-align:right;">{{ moneyFormat($currencyExchange->fromWallet->currency->symbol, formatNumber($currencyExchange->fee)) }}</td>
				</tr>
			@endif

		  <tr>
		    <td colspan="2" style="border-top:1px solid #eaeaea; padding-top:0; margin-bottom:3px;"></td>
		  </tr>
		  <tr>
			<td style="font-size:15px; color:#000000; font-weight:bold;">@lang('message.dashboard.left-table.total')</td>
			<td style="font-size:15px; color:#4e5c6e; text-align:right; font-weight:bold;">{{ moneyFormat($currencyExchange->fromWallet->currency->symbol, formatNumber($currencyExchange->amount + $currencyExchange->fee)) }}</td>
		  </tr>
		  </table>
	    </td>
	  </tr>
</table>

   </div>
</body>
</html>
