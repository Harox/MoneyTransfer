@extends('admin.layouts.master')
@section('title', 'Edit Transaction')

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
								<h4 class="text-left">Transaction Details</h4>
							</div>

							<div class="col-md-2">
								@if (isset($dispute))
									@if( $transaction->transaction_type_id == Payment_Sent && $transaction->status == 'Success' && $dispute->status != 'Open')
	                                    <a id="dispute_{{$transaction->id}}" href="{{ url('admin/dispute/add/'.$transaction->id) }}" class="btn button-secondary btn-sm pull-right">Open Dispute</a>
	                                @endif
								@endif
							</div>
							<div class="col-md-3">
								@if ($transaction->status)
									<h4 class="text-left">Status :
									@if ($transaction->transaction_type_id == Deposit)
		                        			@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif

		                        	@elseif ($transaction->transaction_type_id == Withdrawal)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif

									@elseif ($transaction->transaction_type_id == Transferred)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Refund')<span class="text-orange">Refunded</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif

									@elseif ($transaction->transaction_type_id == Received)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Refund')<span class="text-orange">Refunded</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif


									@elseif ($transaction->transaction_type_id == Exchange_From)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif

									@elseif ($transaction->transaction_type_id == Exchange_To)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif

									@elseif ($transaction->transaction_type_id == Request_From)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif
		                        			@if ($transaction->status == 'Refund')<span class="text-orange">Refunded</span>@endif

									@elseif ($transaction->transaction_type_id == Request_To)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Blocked')<span class="text-red">Cancelled</span>@endif
		                        			@if ($transaction->status == 'Refund')<span class="text-orange">Refunded</span>@endif

									@elseif ($transaction->transaction_type_id == Payment_Sent)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Refund')<span class="text-orange">Refunded</span>@endif

									@elseif ($transaction->transaction_type_id == Payment_Received)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
		                        			@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif
		                        			@if ($transaction->status == 'Refund')<span class="text-orange">Refunded</span>@endif

		                        	@elseif ($transaction->transaction_type_id == Crypto_Sent)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
											@if ($transaction->status == 'Pending')<span class="text-blue">Pending</span>@endif

									@elseif ($transaction->transaction_type_id == Crypto_Received)
											@if ($transaction->status == 'Success')<span class="text-green">Success</span>@endif
									@endif</h4>
								@endif
							</div>
						</div>
					</div>

					<div class="panel-body">
						<div class="row">
							<form action="{{ url('admin/transactions/update/'.$transaction->id) }}" class="form-horizontal" id="transactions_form" method="POST">
								{{ csrf_field() }}
						        <input type="hidden" value="{{ $transaction->id }}" name="id" id="id">
						        <input type="hidden" value="{{ $transaction->transaction_type_id }}" name="transaction_type_id" id="transaction_type_id">
						        <input type="hidden" value="{{ $transaction->transaction_reference_id }}" name="transaction_reference_id" id="transaction_reference_id">
						        <input type="hidden" value="{{ $transaction->uuid }}" name="uuid" id="uuid">
						        <input type="hidden" value="{{ $transaction->user_id }}" name="user_id" id="user_id">
						        <input type="hidden" value="{{ $transaction->end_user_id }}" name="end_user_id" id="end_user_id">
						        <input type="hidden" value="{{ $transaction->currency_id }}" name="currency_id" id="currency_id">
						        <input type="hidden" value="{{ ($transaction->percentage) }}" name="percentage" id="percentage">
						        <input type="hidden" value="{{ ($transaction->charge_percentage) }}" name="charge_percentage" id="charge_percentage">
						        <input type="hidden" value="{{ ($transaction->charge_fixed) }}" name="charge_fixed" id="charge_fixed">
						        <input type="hidden" value="{{ base64_encode($transaction->payment_method_id) }}" name="payment_method_id" id="payment_method_id">

						        <input type="hidden" value="{{ base64_encode($transaction->merchant_id) }}" name="merchant_id" id="merchant_id">

						        <!--MerchantPayment-->
								@if (isset($transaction->merchant_payment))
									<input type="hidden" value="{{ base64_encode($transaction->merchant_payment->gateway_reference) }}" name="gateway_reference" id="gateway_reference">
							        <input type="hidden" value="{{ $transaction->merchant_payment->order_no }}" name="order_no" id="order_no">
							        <input type="hidden" value="{{ $transaction->merchant_payment->item_name }}" name="item_name" id="item_name">
								@endif

								<div class="col-md-8">
									<div class="panel panel-default">
										<div class="panel-body">

											{{-- User --}}
												<div class="form-group">
														@if($transaction->transaction_type_id == Deposit
															|| $transaction->transaction_type_id == Exchange_From
															|| $transaction->transaction_type_id == Exchange_To
															|| $transaction->transaction_type_id == Withdrawal
															|| $transaction->transaction_type_id == Payment_Sent
															|| $transaction->transaction_type_id == Payment_Received)
														<label class="control-label col-sm-3" for="user">User</label>

														@elseif($transaction->transaction_type_id == Transferred)
															<label class="control-label col-sm-3" for="user">Paid By</label>

														@elseif($transaction->transaction_type_id == Crypto_Sent || $transaction->transaction_type_id == Crypto_Received)
															<label class="control-label col-sm-3" for="user">Sender</label>

														@elseif($transaction->transaction_type_id == Received)
															<label class="control-label col-sm-3" for="user">Paid By</label>

														@elseif($transaction->transaction_type_id == Request_From)
															<label class="control-label col-sm-3" for="user">Request From</label>

														@elseif($transaction->transaction_type_id == Request_To)
															<label class="control-label col-sm-3" for="user">Request From</label>
														@endif

													<input type="hidden" class="form-control" name="user" value="
														@if (in_array($transaction->transaction_type_id, [Deposit, Transferred, Exchange_From, Exchange_To, Request_From, Withdrawal, Payment_Sent, Crypto_Sent]))
							                                {{ isset($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}
							                            @elseif (in_array($transaction->transaction_type_id, [Received, Request_To, Payment_Received, Crypto_Received]))
							                                {{ isset($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}
							                            @endif
														">

													<div class="col-sm-9">
													  <p class="form-control-static">
														@if (in_array($transaction->transaction_type_id, [Deposit, Transferred, Exchange_From, Exchange_To, Request_From, Withdrawal, Payment_Sent, Crypto_Sent]))
							                                {{ isset($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}
							                            @elseif (in_array($transaction->transaction_type_id, [Received, Request_To, Payment_Received, Crypto_Received]))
							                                {{ isset($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}
							                            @endif
														</p>
													</div>
												</div>

											{{-- Receiver --}}
												<div class="form-group">
													@if($transaction->transaction_type_id == Deposit
															|| $transaction->transaction_type_id == Exchange_From
															|| $transaction->transaction_type_id == Exchange_To
															|| $transaction->transaction_type_id == Withdrawal
															|| $transaction->transaction_type_id == Payment_Sent
															|| $transaction->transaction_type_id == Payment_Received
															|| $transaction->transaction_type_id == Crypto_Sent
															|| $transaction->transaction_type_id == Crypto_Received)
														<label class="control-label col-sm-3" for="receiver">Receiver</label>

													@elseif($transaction->transaction_type_id == Transferred)
														<label class="control-label col-sm-3" for="receiver">Paid To</label>

													@elseif($transaction->transaction_type_id == Received)
														<label class="control-label col-sm-3" for="user">Paid to</label>

													@elseif($transaction->transaction_type_id == Request_From)
														<label class="control-label col-sm-3" for="receiver">Request To</label>

													@elseif($transaction->transaction_type_id == Request_To)
														<label class="control-label col-sm-3" for="receiver">Request To</label>
													@endif

													<input type="hidden" class="form-control" name="receiver" value="
														 @switch($transaction->transaction_type_id)
							                                @case(Deposit)
							                                @case(Exchange_From)
							                                @case(Exchange_To)
							                                @case(Withdrawal)
							                                @case(Crypto_Sent)
							                                    {{ isset($transaction->end_user) ? $transaction->end_user->first_name . ' ' . $transaction->end_user->last_name : "-" }}
							                                    @break
							                                @case(Transferred)
							                                @case(Received)

							                                        @if ($transaction->transfer->receiver)
							                                        {{ $transaction->transfer->receiver->first_name.' '.$transaction->transfer->receiver->last_name }}
							                                        @elseif ($transaction->transfer->email)
							                                            {{ $transaction->transfer->email }}
							                                        @elseif ($transaction->transfer->phone)
							                                            {{ $transaction->transfer->phone }}
							                                        @else
							                                            {{ '-' }}
							                                        @endif

							                                    @break
							                                @case(Request_From)
							                                @case(Request_To)
							                                    {{ isset($transaction->request_payment->receiver) ? $transaction->request_payment->receiver->first_name.' '.$transaction->request_payment->receiver->last_name : $transaction->request_payment->email }}
							                                    @break
							                                @case(Payment_Sent)
							                                    {{ isset($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}
							                                    @break
							                                @case(Payment_Received)
							                                @case(Crypto_Received)
							                                    {{ isset($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}
							                                    @break
							                            @endswitch
															">

													<div class="col-sm-9">
													  	<p class="form-control-static">
														  	@switch($transaction->transaction_type_id)
								                                @case(Deposit)
								                                @case(Exchange_From)
								                                @case(Exchange_To)
								                                @case(Withdrawal)
								                                @case(Crypto_Sent)
								                                    {{ isset($transaction->end_user) ? $transaction->end_user->first_name . ' ' . $transaction->end_user->last_name : "-" }}
								                                    @break
								                                @case(Transferred)
								                                @case(Received)

								                                        @if ($transaction->transfer->receiver)
								                                        {{ $transaction->transfer->receiver->first_name.' '.$transaction->transfer->receiver->last_name }}
								                                        @elseif ($transaction->transfer->email)
								                                            {{ $transaction->transfer->email }}
								                                        @elseif ($transaction->transfer->phone)
								                                            {{ $transaction->transfer->phone }}
								                                        @else
								                                            {{ '-' }}
								                                        @endif

								                                    @break
								                                @case(Request_From)
								                                @case(Request_To)
								                                    {{ isset($transaction->request_payment->receiver) ? $transaction->request_payment->receiver->first_name.' '.$transaction->request_payment->receiver->last_name : $transaction->request_payment->email }}
								                                    @break
								                                @case(Payment_Sent)
								                                    {{ isset($transaction->end_user) ? $transaction->end_user->first_name.' '.$transaction->end_user->last_name :"-" }}
								                                    @break
								                                @case(Payment_Received)
								                                @case(Crypto_Received)
								                                    {{ isset($transaction->user) ? $transaction->user->first_name.' '.$transaction->user->last_name :"-" }}
								                                    @break
								                            @endswitch
														</p>
													</div>
												</div>

											<!-- Sender Address -->
											@if (isset($senderAddress))
												<div class="form-group">
													<label class="control-label col-sm-3" for="crypto_sender_address">Sender Address</label>
													<input type="hidden" class="form-control" name="crypto_sender_address" value="{{ $senderAddress }}">
													<div class="col-sm-9">
													  <p class="form-control-static" id="crypto_sender_address">{{ $senderAddress }}</p>
													</div>
												</div>
											@endif

											<!-- Receiver Address -->
											@if (isset($receiverAddress))
												<div class="form-group">
													<label class="control-label col-sm-3" for="crypto_receiver_address">Receiver Address</label>
													<input type="hidden" class="form-control" name="crypto_receiver_address" value="{{ $receiverAddress }}">
													<div class="col-sm-9">
													  <p class="form-control-static" id="crypto_receiver_address">{{ $receiverAddress }}</p>
													</div>
												</div>
											@endif

											<!-- Txid -->
											@if (isset($txId))
												<div class="form-group">
													<label class="control-label col-sm-3" for="crypto_txid">{{ $transaction->payment_method->name }} TxId</label>
													<input type="hidden" class="form-control" name="crypto_txid" value="{{ $txId }}">
													<div class="col-sm-9">
													  <p class="form-control-static" id="crypto_txid">{{ wordwrap($txId, 50, "\n", true) }}</p>
													</div>
												</div>
											@endif

											<!-- Confirmations -->
											@if (isset($confirmations))
												<div class="form-group">
													<label class="control-label col-sm-3" for="crypto_confirmations">Confirmations</label>
													<input type="hidden" class="form-control" name="crypto_confirmations" value="{{ $confirmations }}">
													<div class="col-sm-9">
													  <p class="form-control-static" id="crypto_confirmations">{{ $confirmations }}</p>
													</div>
												</div>
											@endif

											@if ($transaction->uuid)
							                    <div class="form-group">
													<label class="control-label col-sm-3" for="transactions_uuid">Transaction ID</label>
													<input type="hidden" class="form-control" name="transactions_uuid" value="{{ $transaction->uuid }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ $transaction->uuid }}</p>
													</div>
												</div>
											@endif

											@if ($transaction->transaction_type_id)
							                    <div class="form-group">
													<label class="control-label col-sm-3" for="type">Type</label>
													<input type="hidden" class="form-control" name="type" value="{{ str_replace('_', ' ', $transaction->transaction_type->name) }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ ($transaction->transaction_type->name == "Withdrawal") ? "Payout" : str_replace('_', ' ', $transaction->transaction_type->name) }}</p>
													</div>
												</div>
											@endif

											@if ($transaction->currency)
												<div class="form-group">
													<label class="control-label col-sm-3" for="currency">Currency</label>
													<input type="hidden" class="form-control" name="currency" value="{{ $transaction->currency->code }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ $transaction->currency->code }}</p>
													</div>
												</div>
											@endif

											@if (isset($transaction->payment_method_id))
												<div class="form-group">
													<label class="control-label col-sm-3" for="payment_method">Payment Method</label>
													<input type="hidden" class="form-control" name="payment_method" value="{{ ($transaction->payment_method->name == "Mts") ? getCompanyName() : $transaction->payment_method->name }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ ($transaction->payment_method->name == "Mts") ? getCompanyName() : $transaction->payment_method->name }}</p>
													</div>
												</div>
											@endif

											@if ($transaction->bank)
							                    <div class="form-group">
													<label class="control-label col-sm-3" for="bank_name">Bank Name</label>
													<input type="hidden" class="form-control" name="bank_name" value="{{ $transaction->bank->bank_name }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ $transaction->bank->bank_name }}</p>
													</div>
												</div>

							                    <div class="form-group">
													<label class="control-label col-sm-3" for="bank_branch_name">Branch Name</label>
													<input type="hidden" class="form-control" name="bank_branch_name" value="{{ $transaction->bank->bank_branch_name }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ $transaction->bank->bank_branch_name }}</p>
													</div>
												</div>

							                    <div class="form-group">
													<label class="control-label col-sm-3" for="account_name">Account Name</label>
													<input type="hidden" class="form-control" name="account_name" value="{{ $transaction->bank->account_name }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ $transaction->bank->account_name }}</p>
													</div>
												</div>
											@endif

											@if ($transaction->file)
												<div class="form-group">
													<label class="control-label col-sm-3" for="attached_file">Attached File</label>
													<div class="col-sm-9">
													  <p class="form-control-static">
										                  <a href="{{ url('public/uploads/files/bank_attached_files').'/'.$transaction->file->filename }}" download={{ $transaction->file->filename }}><i class="fa fa-fw fa-download"></i>
										                  	{{ $transaction->file->originalname }}
										                  </a>
													  </p>
													</div>
												</div>
											@endif


											@if ($transaction->transaction_type_id == Withdrawal)
												@if ($transaction->payment_method->name == 'Bank')
													<div class="form-group">
														<label class="control-label col-sm-3" for="account_name">Account Name</label>
														<input type="hidden" class="form-control" name="account_name" value="{{ $transaction->withdrawal->withdrawal_detail->account_name }}">
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $transaction->withdrawal->withdrawal_detail->account_name }}</p>
														</div>
													</div>

													<div class="form-group">
														<label class="control-label col-sm-3" for="account_number">Account Number/IBAN</label>
														<input type="hidden" class="form-control" name="account_number" value="{{ $transaction->withdrawal->withdrawal_detail->account_number }}">
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $transaction->withdrawal->withdrawal_detail->account_number }}</p>
														</div>
													</div>

													<div class="form-group">
														<label class="control-label col-sm-3" for="swift_code">SWIFT Code</label>
														<input type="hidden" class="form-control" name="swift_code" value="{{ $transaction->withdrawal->withdrawal_detail->swift_code }}">
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $transaction->withdrawal->withdrawal_detail->swift_code }}</p>
														</div>
													</div>

													<div class="form-group">
														<label class="control-label col-sm-3" for="bank_name">Bank Name</label>
														<input type="hidden" class="form-control" name="bank_name" value="{{ $transaction->withdrawal->withdrawal_detail->bank_name }}">
														<div class="col-sm-9">
														  <p class="form-control-static">{{ $transaction->withdrawal->withdrawal_detail->bank_name }}</p>
														</div>
													</div>
												@endif
											@endif


											@if ($transaction->created_at)
												<div class="form-group">
													<label class="control-label col-sm-3" for="created_at">Date</label>
													<input type="hidden" class="form-control" name="created_at" value="{{ $transaction->created_at }}">
													<div class="col-sm-9">
													  <p class="form-control-static">{{ dateFormat($transaction->created_at) }}</p>
													</div>
												</div>
						               		@endif

						               		@if ($transaction->status)
						                   		<div class="form-group">
													<label class="control-label col-sm-3" for="status">Change Status</label>
													<div class="col-sm-9">

														@if (isset($transaction->refund_reference) && isset($transactionOfRefunded))
								                          	<p class="form-control-static"><span class="label label-success">Already Refunded</span></p>
								                          	<p class="form-control-static"><span class="label label-danger">Refund Reference: <i>
										                          	<a id="transactionOfRefunded" href="{{ url("admin/transactions/edit/$transactionOfRefunded->id") }}">( {{ $transaction->refund_reference }} )</a>
										                          </i>
										                      </span>
										                  	</p>
										                @elseif ($transaction->transaction_type_id == Crypto_Sent)
								                          	<p class="form-control-static"><span class="label label-danger" id="crypto-sent-status" style="white-space: unset !important;">Crypto Sent Status Cannot Be Changed</span></p>
								                        @elseif ($transaction->transaction_type_id == Crypto_Received)
								                          	<p class="form-control-static"><span class="label label-danger" id="crypto-received-status" style="white-space: unset !important;">Crypto Received Status Cannot Be Changed</span></p>
									                    @else
															<select class="form-control select2" name="status" style="width: 60%;">

											                        @if ($transaction->transaction_type_id == Deposit)
																		<option value="Success" {{ $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
												                        <option value="Pending"  {{ $transaction->status == 'Pending' ? 'selected':"" }}>Pending</option>
											                            <option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>

																	@elseif ($transaction->transaction_type_id == Transferred || $transaction->transaction_type_id == Received)
										                            	@if ($transaction->status == 'Success')
																			<option value="Success" {{ $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
											                            	<option value="Pending"  {{ $transaction->status == 'Pending' ? 'selected':"" }}>Pending</option>
											                            	<option value="Refund" {{ $transaction->status ==  'Refund' ? 'selected':"" }}>Refund</option>
											                            	<option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>
											                        	@else
											                        		<option value="Success" {{ $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
											                            	<option value="Pending"  {{ $transaction->status == 'Pending' ? 'selected':"" }}>Pending</option>
											                            	<option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>
											                        	@endif

																	@elseif ($transaction->transaction_type_id == Exchange_From || $transaction->transaction_type_id == Exchange_To)
																		<option value="Success" {{ $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
											                            <option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>

																	@elseif ($transaction->transaction_type_id == Request_From || $transaction->transaction_type_id == Request_To)
																	    @if ($transaction->status == 'Pending')
												                        	<option value="Pending" {{ $transaction->status ==  'Pending'? 'selected':"" }}>Pending</option>
																			<option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>

																		@elseif ($transaction->status == 'Blocked')
												                        	<option value="Pending" {{ $transaction->status ==  'Pending'? 'selected':"" }}>Pending</option>
																			<option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>

																		@elseif ($transaction->status == 'Success')
												                        	<option value="Success" {{ $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
																			<option value="Refund"  {{ $transaction->status == 'Refund' ? 'selected':"" }}>Refund</option>
																		@endif

																	@elseif ($transaction->transaction_type_id == Withdrawal)
																			<option value="Success" {{ $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
													                        <option value="Pending"  {{ $transaction->status == 'Pending' ? 'selected':"" }}>Pending</option>
												                            <option value="Blocked"  {{ $transaction->status == 'Blocked' ? 'selected':"" }}>Cancel</option>

												                    @elseif ($transaction->transaction_type_id == Payment_Sent || $transaction->transaction_type_id == Payment_Received)
														                    @if ($transaction->status ==  'Success')
												                        		<option value="Success" {{ isset($transaction->status) && $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
																				<option value="Pending"  {{ isset($transaction->status) && $transaction->status == 'Pending' ? 'selected':"" }}>Pending</option>
																				<option value="Refund"  {{ isset($transaction->status) && $transaction->status == 'Refund' ? 'selected':"" }}>Refund</option>
												                        	@else
												                        		<option value="Success" {{ isset($transaction->status) && $transaction->status ==  'Success'? 'selected':"" }}>Success</option>
																				<option value="Pending"  {{ isset($transaction->status) && $transaction->status == 'Pending' ? 'selected':"" }}>Pending</option>
												                        	@endif
																	@endif
															</select>
								                        @endif
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
													<input type="hidden" class="form-control" name="subtotal" value="{{ $transaction->subtotal }}">
													<div class="col-sm-6">
													  <p class="form-control-static">
													  	{{ $transaction->currency->type != 'fiat' ? moneyFormat($transaction->currency->symbol, $transaction->subtotal) :
													  	moneyFormat($transaction->currency->symbol, formatNumber($transaction->subtotal)) }}
													  </p>
													</div>
												</div>
											@endif

						                    <div class="form-group total-deposit-feesTotal-space">
												<label class="control-label col-sm-6" for="fee">Fees
													<span>
														<small class="transactions-edit-fee">
															@if (isset($transaction))
																@if ($transaction->currency->type != 'fiat')
																	({{(($transaction->transaction_type->name == "Payment_Sent") ? "0" : ($transaction->percentage))}}% + {{($transaction->charge_fixed)}})
																@else
																	({{(($transaction->transaction_type->name == "Payment_Sent") ? "0" : formatNumber($transaction->percentage))}}% + {{formatNumber($transaction->charge_fixed)}})
																@endif
															@else
																({{0}}%+{{0}})
															@endif
														</small>
													</span>
												</label>

												@php
													$total_transaction_fees = $transaction->charge_percentage + $transaction->charge_fixed;
												@endphp

												<input type="hidden" class="form-control" name="fee" value="{{ ($total_transaction_fees) }}">
												<div class="col-sm-6">
													<p class="form-control-static">
														{{ $transaction->currency->type != 'fiat' ? moneyFormat($transaction->currency->symbol, $transaction->charge_fixed) :
														moneyFormat($transaction->currency->symbol, formatNumber($total_transaction_fees)) }}
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
															{{ $transaction->currency->type != 'fiat' ? moneyFormat($transaction->currency->symbol, str_replace("-",'',$transaction->total)) :
															moneyFormat($transaction->currency->symbol, str_replace("-",'',formatNumber($transaction->total))) }}
														</p>
													</div>
												</div>
											@endif

										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-11">
										<div class="col-md-2"></div>
										<div class="col-md-2"><a id="cancel_anchor" class="btn btn-danger pull-left" href="{{ url('admin/transactions') }}">Cancel</a></div>
										@if ($transaction->transaction_type_id != Crypto_Sent && $transaction->transaction_type_id != Crypto_Received)
											@if (!isset($transaction->refund_reference))
												<div class="col-md-1">
													<button type="submit" class="btn button-secondary pull-right" id="request_payment">
														<i class="spinner fa fa-spinner fa-spin"></i> <span id="request_payment_text">Update</span>
													</button>
												</div>
											@endif
										@endif
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

@push('extra_body_scripts')

<script type="text/javascript">

	$(window).on('load', function()
	{
		$(".select2").select2({});
	});

	// disabling submit and cancel button after clicking it
	$(document).ready(function ()
	{
	    $('form').submit(function ()
	    {
	        $("#transactions_edit").attr("disabled", true);
	        $('#cancel_anchor').attr("disabled", "disabled");
	        $(".fa-spin").show();
	        $("#transactions_edit_text").text('Updating...');

	        // Click False
	        $('#transactions_edit').click(false);
	        $('#cancel_anchor').click(false);
	    });

	    $('#transactionOfRefunded').css('color', 'white');
	});

</script>

@endpush
