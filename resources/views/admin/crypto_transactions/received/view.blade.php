@extends('admin.layouts.master')

@section('title', 'View Crypto Received Transaction')
@section('head_style')
    <style type="text/css">
        #crypto_txid, #crypto_sender_address, #crypto_receiver_address, #crypto-sent-status, #crypto-received-status
        {
		    /*Bread word to new line*/
		    word-wrap: break-word !important; /* same as - overflow-wrap: break-word;*/
		}
    </style>
@endsection

@section('page_content')
	<div class="box">
		<div class="box-body">
			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							<div class="row">
								<div class="col-md-7">
									<h4 class="text-left">Crypto Received Transaction Details</h4>
								</div>

								<div class="col-md-2">
								</div>
								<div class="col-md-3">
									@if ($transaction->status)
										<h4 class="text-left">Status :
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
										</h4>
									@endif
								</div>
							</div>
						</div>

						<div class="panel-body">
							<div class="row">
								<form action="#" class="form-horizontal" id="transactions_form" method="POST">
									<div class="col-md-8">
										<div class="panel panel-default">
											<div class="panel-body">

												{{-- Sender --}}
												<div class="form-group">
													<label class="control-label col-sm-3" for="sender">Sender</label>
													<div class="col-sm-9">
													  <p class="form-control-static">{{ !empty($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}</p>
													</div>
												</div>

												{{-- Receiver --}}
												<div class="form-group">
													<label class="control-label col-sm-3" for="receiver">Receiver</label>
													<div class="col-sm-9">
													  <p class="form-control-static">{{ !empty($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}</p>
													</div>
												</div>

												<!-- Sender Address -->
												@if (isset($senderAddress))
													<div class="form-group">
														<label class="control-label col-sm-3" for="crypto_sender_address">Sender Address</label>
														<div class="col-sm-9">
														  <p class="form-control-static" id="crypto_sender_address">{{ $senderAddress }}</p>
														</div>
													</div>
												@endif

												<!-- Receiver Address -->
												@if (isset($receiverAddress))
													<div class="form-group">
														<label class="control-label col-sm-3" for="crypto_receiver_address">Receiver Address</label>
														<div class="col-sm-9">
														  <p class="form-control-static" id="crypto_receiver_address">{{ $receiverAddress }}</p>
														</div>
													</div>
												@endif

												<!-- Txid -->
												@if (isset($txId))
													<div class="form-group">
														<label class="control-label col-sm-3" for="crypto_txid">{{ $transaction->payment_method->name }} TxId</label>
														<div class="col-sm-9">
														  <p class="form-control-static" id="crypto_txid">{{ wordwrap($txId, 50, "\n", true) }}</p>
														</div>
													</div>
												@endif

												<!-- Confirmations -->
												@if (isset($confirmations))
													<div class="form-group">
														<label class="control-label col-sm-3" for="crypto_confirmations">Confirmations</label>
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $confirmations }}</p>
														</div>
													</div>
												@endif

												@if ($transaction->uuid)
								                    <div class="form-group">
														<label class="control-label col-sm-3" for="transactions_uuid">Transaction ID</label>
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $transaction->uuid }}</p>
														</div>
													</div>
												@endif

												@if ($transaction->currency)
													<div class="form-group">
														<label class="control-label col-sm-3" for="currency">Crypto Currency</label>
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $transaction->currency->code }}</p>
														</div>
													</div>
												@endif

												@if ($transaction->created_at)
													<div class="form-group">
														<label class="control-label col-sm-3" for="created_at">Date</label>
														<div class="col-sm-9">
														  <p class="form-control-static">{{ dateFormat($transaction->created_at) }}</p>
														</div>
													</div>
							               		@endif

											</div>
										</div>
									</div>

									<div class="col-md-4">
										<div class="panel panel-default">
											<div class="panel-body">

												@if ($transaction->subtotal)
								                    <div class="form-group">
														<label class="control-label col-sm-6" for="subtotal">Amount</label>
														<div class="col-sm-6">
														  <p class="form-control-static">
														  	{{ moneyFormat($transaction->currency->symbol, $transaction->subtotal) }}
														  </p>
														</div>
													</div>
												@endif

							                    <div class="form-group total-deposit-feesTotal-space">
													<label class="control-label col-sm-6" for="fee">Fees
														<span>
															<small class="transactions-edit-fee">
																({{ $transaction->percentage }}% + {{ $transaction->charge_fixed }})
															</small>
														</span>
													</label>

													@php
														$total_transaction_fees = $transaction->charge_percentage + $transaction->charge_fixed;
													@endphp
													<div class="col-sm-6">
														<p class="form-control-static">
															{{ moneyFormat($transaction->currency->symbol, $transaction->charge_fixed) }}
														</p>
													</div>
												</div>

												<hr class="increase-hr-height">

												@if ($transaction->total)
								                    <div class="form-group total-deposit-space">
														<label class="control-label col-sm-6" for="total">Total</label>
														<input type="hidden" class="form-control" name="total" value="{{ ($transaction->total) }}">
														<div class="col-sm-6">
														  	<p class="form-control-static">
																{{ moneyFormat($transaction->currency->symbol, str_replace("-",'',$transaction->total)) }}
															</p>
														</div>
													</div>
												@endif

											</div>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

