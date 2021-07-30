<?php

$getCompanyName = getCompanyName();

return [

    'sidebar'              => [
        'dashboard'    => 'Dashboard',
        'users'        => 'Users',
        'transactions' => 'Transactions',
        'settings'     => 'Settings',
    ],

    'footer'               => [
        'follow-us'      => 'Follow Us',
        'related-link'   => 'Related links',
        'categories'     => 'Categories',
        'language'       => 'Language',
        'copyright'      => 'Copyright',
        'copyright-text' => 'All Rights Reserved',
    ],

    '2sa'                  => [
        'title-short-text'             => '2-FA',
        'title-text'                   => '2-Factor Authentication',
        'extra-step'                   => 'This extra step shows it\'s really you trying to sign in.',
        'extra-step-settings-verify'   => 'This extra step shows it\'s really you trying to verify.',
        'confirm-message'              => 'A text message with a 6-digit authentication code was just sent to',
        'confirm-message-verification' => 'A text message with a 6-digit verification code was just sent to',
        'remember-me-checkbox'         => 'Remember me on this browser',
        'verify'                       => 'Verify',
    ],

    'personal-id'          => [
        'title'                 => 'Identity Verification',
        'identity-type'         => 'Identity Type',
        'select-type'           => 'Select Type',
        'driving-license'       => 'Driving License',
        'passport'              => 'Passport',
        'national-id'           => 'National ID',
        'identity-number'       => 'Identity Number',
        'upload-identity-proof' => 'Upload Identity Proof',
    ],

    'personal-address'     => [
        'title'                => 'Address Verification',
        'upload-address-proof' => 'Upload Address Proof',
    ],

    'google2fa'            => [
        'title-text'     => 'Google Two Factor Authentication(2FA)',
        'subheader-text' => 'Scan QR Code with Google Authenticator App.',
        'setup-a'        => 'Set up your Google Authenticator app before continuing.',
        'setup-b'        => 'You will be unable to verify otherwise.',
        'proceed'        => 'Proceed To Verification',
        'otp-title-text' => 'One Time Password(OTP)',
        'otp-input'      => 'Enter the 6-digit OTP from Google Authenticator App',
    ],

    'form'                 => [
        'button'                   => [
            'sign-up' => 'Sign Up',
            'login'   => 'Login',
        ],
        'forget-password-form'     => 'Forgot Password',
        'reset-password'           => 'Reset Password',
        'yes'                      => 'Yes',
        'no'                       => 'No',
        'add'                      => 'Add New',
        'category'                 => 'Category',
        'unit'                     => 'Units',
        'category_create'          => 'Create Category',
        'category_edit'            => 'Edit Category',
        'location_create'          => 'Create Location',
        'location_edit'            => 'Edit Location',
        'location_name'            => 'Location Name',
        'location_code'            => 'Location Code',
        'delivery_address'         => 'Delivery Address',
        'default_loc'              => 'Default Location',
        'phone_one'                => 'Phone One',
        'phone_two'                => 'Phone Two',
        'fax'                      => 'Fax',
        'email'                    => 'Email',
        'username'                 => 'Username',
        'contact'                  => 'Contact',
        'item_create'              => 'Create Item',
        'unit_create'              => 'Create Unit',
        'unit_edit'                => 'Edit Unit',
        'item_id'                  => 'Item ID',
        'item_name'                => 'Item Name',
        'quantity'                 => 'Quantity',
        'item_des'                 => 'Item Description',
        'picture'                  => 'Picture',
        'location'                 => 'Location',
        'add_stock'                => 'Add Stock',
        'select_one'               => 'Select One',
        'memo'                     => 'Memo',
        'close'                    => 'Close',
        'remove_stock'             => 'Remove Stock',
        'move_stock'               => 'Move Stock',
        'location_from'            => 'Location From',
        'location_to'              => 'Location To',
        'item_edit'                => 'Edit Item',
        'copy'                     => 'Copy',
        'store_in'                 => 'Store In',
        'order_items'              => 'Order Items',
        'delivery_from'            => 'Delivery From Location',
        'user_role_create'         => 'Create User Role',
        'permission'               => 'Permission',
        'section_name'             => 'Section Name',
        'areas'                    => 'Areas',
        'Add'                      => 'Add',
        'Edit'                     => 'Edit',
        'Delete'                   => 'Delete',
        'name'                     => 'Name',
        'request_to'               => 'Requested To',
        'request_from'             => 'Requested From',
        'full_name'                => 'Full Name',
        'password'                 => 'Password',
        'old_password'             => 'Old Password',
        'set_password'             => 'Set Password',
        'new_password'             => 'New Password',
        'update_password'          => 'Update Password',
        'confirm_password'         => 'Confirm Password',
        're_password'              => 'Repeat Password',
        'change_password'          => 'Change Password',
        'settings'                 => 'Settings',
        'change_password_form'     => 'Change Password Form',
        'user_create_form'         => 'Create User',
        'user_update_form'         => 'Update User',
        'submit'                   => 'Submit',
        'update'                   => 'Update',
        'cancel'                   => 'Cancel',
        'sign_out'                 => 'Sign Out',
        'delete'                   => 'Delete',
        'company_create'           => 'Create Company',
        'company'                  => 'Company',
        'db_host'                  => 'Host',
        'db_user'                  => 'Database User',
        'db_password'              => 'Database Password',
        'db_name'                  => 'Database Name',
        'new_company_password'     => 'New script Admin Password',
        'pdf'                      => 'PDF',
        'customer'                 => 'Customer',
        'customer_branch'          => 'Customer Branch',
        'payment_type'             => 'Payment Type',
        'from_location'            => 'Location',
        'add_item'                 => 'Add Item',
        'sales_invoice_items'      => 'Sales Invoice Items',
        'purchase_invoice_items'   => 'Purchase Invoice Items',
        'supplier'                 => 'Supplier',
        'order_item'               => 'Order Item',
        'item_tax_type'            => 'Tax Type',
        'currency'                 => 'Currency',
        'sales_type'               => 'Sales Type',
        'price'                    => 'Price',
        'supplier_unit_of_messure' => 'Suppliers Unit of Measure',
        'conversion_factor'        => 'Conversion Factor (to our UOM)',
        'supplier_description'     => "Supplier's Code or Description",
        'next'                     => 'Next',
        'add_branch'               => 'Add Branch',
        'payment_term'             => 'Payment Term',
        'site_name'                => 'Site Name',
        'site_short_name'          => 'Site Short Name',
        'source'                   => 'Source',
        'destination'              => 'Destination',
        'stock_move'               => 'Stock Transfer',
        'after'                    => 'After',
        'status'                   => 'Status',
        'date'                     => 'Date',
        'qty'                      => 'Qty',
        'terms'                    => 'Term',
        'add_new_customer'         => 'Add New Customer',
        'add_new_order'            => 'Add New Order',
        'add_new_invoice'          => 'Add New Invoice',
        'group_name'               => 'Group Name',
        'edit'                     => 'Edit',
        'title'                    => 'Title',
        'description'              => 'Description',
        'reminder'                 => 'Reminder Date',
    ],

    'home'                 => [
        'title-bar'       => [
            'home'      => 'Home',
            'send'      => 'Send',
            'request'   => 'Request',
            'developer' => 'Developer',
            'login'     => 'Login',
            'register'  => 'Register',
            'logout'    => 'Logout',
            'dashboard' => 'Dashboard',
        ],
        'banner'          => [
            'title'      => 'Simple money transfer to :br your loved ones ',
            'sub-title1' => 'Simple to :br Integrate',
            'sub-title2' => 'Multiple :br Wallet',
            'sub-title3' => 'Advanced :br Security',
        ],
        'choose-us'       => [
            'title'      => 'Why choose us?',
            'sub-title1' => 'We are not bank. With us you get low fees & real-time exchange rates.',
            'sub-title2' => 'Get your money to family & friends instant, you just need an email address.',
            'sub-title3' => 'To transfer money, withdrawal & exchange currency -our fees are in low cost.',
        ],
        'payment-gateway' => [
            'title' => 'Payment Processors',
        ],
        'services'        => [
            't1' => 'Payment API',
            's1' => 'It will manage customers ' . $getCompanyName . ' experience by integrating our seamless API interface within your website.',
            't2' => 'Online Payments',
            's2' => 'Whatever it is credit, debit or bank account you can pay by your way.',
            't3' => 'Currency Exchange',
            's3' => 'Default currency to another you can change it easily.',
            't4' => 'Payment Request',
            's4' => 'By these systems now you can request for payment money from any country to any country.',
            't6' => 'Fraud Detection',
            's6' => 'It means we help to keep your account more secured & reliable. Enjoy safe online payments.',
        ],
        'how-work'        => [
            'title'      => 'How it works',
            'sub-title1' => 'First, create a deposit to your account.',
            'sub-title2' => 'Decide how much amount you want to send & choose wallet.',
            'sub-title3' => 'Write down the email address with a short note if you want.',
            'sub-title4' => 'Click on send money.',
            'sub-title5' => 'You can exchange your currency too.',
        ],
    ],

    'send-money'           => [
        'banner'    => [
            'title'     => 'Send Money The Way That Suits You',
            'sub-title' => 'Quickly and easily send and receive money.',
            'sign-up'   => 'Sign Up to ' . $getCompanyName,
            'login'     => 'Log In Now',
        ],
        'section-a' => [
            'title'         => 'Sending Money Globally Within A Few Minutes With Multiple Currency Just In a Few
                            Clicks.',
            'sub-section-1' => [
                'title'     => 'Register Account',
                'sub-title' => 'At first be an register user, then login into your account and enter your card or bank
                            details information which is required for you.',
            ],
            'sub-section-2' => [
                'title'     => 'Select Your Recipient',
                'sub-title' => 'Enter your recipient email address that won\'t be share with others and remain secured, then
                            add an amount with currency to send securely.',
            ],
            'sub-section-3' => [
                'title'     => 'Send Money',
                'sub-title' => 'After sending money the recipient will be notify via an email when the money has been
                            transferred into their account.',
            ],
        ],
        'section-b' => [
            'title'     => 'Send Money Within A Seconds.',
            'sub-title' => 'Anyone with an email address can send/recive payment request whether they have a account or
                            not. They can pay with a credit card or bank account',
        ],
        'section-c' => [
            'title'     => 'Send Money To Anyone, Anywhere, Instantly Using ' . $getCompanyName . ' System', //edited by parvez
            'sub-title' => 'Transfer funds to your friends & family globally through the ' . $getCompanyName . ' mobile app, bank account or others payment gateway.Funds directly go into your account whether the recipient have any account or not. You can send/request for money via different type of Payment Gateway with different currencies.',
        ],
        'section-d' => [
            'title'   => 'Faster, Simpler, Safer – Send Money To Those You Love Today.',
            'sign-up' => 'SignUp To ' . $getCompanyName,
        ],
        'section-e' => [
            'title'     => 'Start Sending Money.',
            'sub-title' => 'Now, you have no trouble for having cash money. Anyone can send money from their card, bank
                            account, paypal balance or other payment gateway. You will notify via an simple email.',
        ],
    ],

    //end the first
    'request-money'        => [
        'banner'    => [
            'title'          => 'Request Money From Around The World :br With ' . $getCompanyName,
            'sub-title'      => 'Make a reminder people to send moneyback.',
            'sign-up'        => 'Sign Up To ' . $getCompanyName,
            'already-signed' => 'Already signed up?',
            'login'          => 'log in',
            'request-money'  => 'to request money.',
        ],
        'section-a' => [
            'title'         => 'User Friendly Money Request System.',
            'sub-title'     => 'Requesting money is an efficient and polite way of asking for money you\'re owed. :br Use
                            ' . $getCompanyName . ' system to send money, receive money or transfer money from your nearest & dearest ones.',

            'sub-section-1' => [
                'title'     => 'Register Account',
                'sub-title' => 'At first be an register user, then login into your account and enter your card or bank
                            details information which is required for you to request money.',
            ],
            'sub-section-2' => [
                'title'     => 'Select Your Recipient',
                'sub-title' => 'Entre your recipient email address that won\'t be share with others and remain secured, then
                            add an amount with currency to send securely.',
            ],
            'sub-section-3' => [
                'title'     => 'Request Money',
                'sub-title' => 'After requesting money the recipient will be notify via an email when the money has been
                            transferred from their account.',
            ],
        ],
        'section-b' => [
            'title'     => 'Can Send Money By Mobile Phone',
            'sub-title' => 'Now, you have no trouble for having cash money. Anyone can send money from their card, bank
                            account, paypal balance or other payment gateway. You will notify via an simple email.',
        ],
        'section-c' => [
            'title'     => 'Use the ' . $getCompanyName . ' Mobile App To Easily Request Money.',
            'sub-title' => 'Anyone with an email address can receive a payment request, whether they have an account or not. They can pay you with PayPal, strip, 2checkout and many more payment corridors.',
        ],
        'section-d' => [
            'title'     => 'Request Money To Anyone, Anywhere, Instantly Using ' . $getCompanyName . ' System',
            'sub-title' => 'Transfer funds to your friends & family globally through the ' . $getCompanyName . ' mobile app, bank account or others payment gateway. Funds directly go into your account whether the recipient have any account or not. You can send/request for money via different type of Payment Gateway with different currencies.',
        ],
        'section-e' => [
            'title'   => 'Faster, Simpler, Safer – Send Money To Those You Love Today.',
            'sign-up' => 'Sign Up to ' . $getCompanyName,
        ],
    ],

    'login'                => [
        'title'           => 'Login',
        'form-title'      => 'Sign In',
        'email'           => 'Email',
        'phone'           => 'Phone',
        'email_or_phone'  => 'Email or Phone',
        'password'        => 'Password',
        'forget-password' => 'Forget password?',
        'no-account'      => 'Don\'t have an account?',
        'sign-up-here'    => 'Sign up here',
    ],

    'registration'         => [
        'title'                => 'Registration',
        'form-title'           => 'Create New User',
        'first-name'           => 'First Name',
        'last-name'            => 'Last Name',
        'email'                => 'Email',
        'phone'                => 'Phone',
        'password'             => 'Password',
        'confirm-password'     => 'Confirm Password',
        'terms'                => 'By clicking Signup, You agree to our Terms, Data Policy and Cookie Policy.',
        'new-account-question' => 'Already have an account?',
        'sign-here'            => 'Sign in here',
        'type-title'           => 'User Type',
        'type-user'            => 'User',
        'type-merchant'        => 'Merchant',
        'select-user-type'     => 'Select User Type',
    ],

    'dashboard'            => [
        'mail-not-sent' => 'but mails could not be sent',
        'nav-menu'      => [
            'dashboard'    => 'Dashboard',
            'transactions' => 'Transactions',
            'send-req'     => 'Cash-in/Out',
            'send-to-bank' => 'Send To Bank',
            'merchants'    => 'Merchants',
            'disputes'     => 'Disputes',
            'settings'     => 'Settings',
            'tickets'      => 'Tickets',
            'logout'       => 'Logout',
            'payout'       => 'Payout',
            'exchange'     => 'Exchange',
        ],

        // Dashboard Transactions List
        'left-table'    => [
            'title'            => 'Recent Activity',
            'date'             => 'Date',
            'description'      => 'Description',
            'status'           => 'Status',
            'currency'         => 'Currency',
            'amount'           => 'Amount',
            'view-all'         => 'View All',
            'no-transaction'   => 'No transaction found!',

            /**
             * Common static's in transaction dropdown
             */
            'details'          => 'Details',
            'fee'              => 'Fee',
            'total'            => 'Total',
            'transaction-id'   => 'Transaction ID',
            'transaction-date' => 'Transaction Date', //for print

            //deposit Static Data
            'deposit'          => [
                'deposited-to'     => 'Deposited To',
                'payment-method'   => 'Payment Method',
                'deposited-amount' => 'Deposited Amount',
                'deposited-via'    => 'Deposited Via', //for print
            ],

            //withdrawal Static Data
            'withdrawal'       => [
                'withdrawan-with'   => 'Payout With',
                'withdrawan-amount' => 'Payout Amount',
            ],

            //transferred Static Data
            'transferred'      => [
                'paid-with'          => 'Paid With',
                'transferred-amount' => 'Transferred Amount',
                'email'              => 'Email',
                'note'               => 'Note',
                'paid-to'            => 'Paid To',
                'transferred-to'     => 'Transferred To',
                'phone'              => 'Phone'
            ],
            'bank-transfer'    => [
                'bank-details'        => 'Bank Details',
                'bank-name'           => 'Bank Name',
                'bank-branch-name'    => 'Branch Name',
                'bank-account-name'   => 'Account Name',
                'bank-account-number' => 'Account Number', //pm1.9
                'transferred-with'    => 'Transferred With',
                'transferred-amount'  => 'Bank Transferred Amount',
            ],

            //received Static Data
            'received'         => [
                'paid-by'         => 'Paid By',
                'received-from'   => 'Received From',
                'received-amount' => 'Received Amount',
            ],

            //exchange-from Static Data
            'exchange-from'    => [
                'from-wallet'          => 'From Wallet',
                'exchange-from-amount' => 'Exchange Amount',
                'exchange-from-title'  => 'Exchange From',
                'exchange-to-title'    => 'Exchange To',
            ],

            //exchange-to Static Data
            'exchange-to'      => [
                'to-wallet' => 'To Wallet',
            ],

            //request-to Static Data
            'request-to'       => [
                'accept' => 'Accept',
            ],

            //payment-Sent-to Static Data
            'payment-Sent'     => [
                'payment-amount' => 'Payment Amount',
            ],
        ],
        'right-table'   => [
            'title'                => 'Wallets',
            'no-wallet'            => 'No wallet found!',
            'default-wallet-label' => 'Default',
            'crypto-send'          => 'Send',
            'crypto-receive'       => 'Receive',
        ],
        'button'        => [
            'deposit'         => 'Deposit',
            'withdraw'        => 'Payout',
            'payout'          => 'Payout',
            'exchange'        => 'Exchange',
            'submit'          => 'Submit',
            'send-money'      => 'Send Money',
            'send-request'    => 'Send Request',
            'create'          => 'Create',
            'activate'        => 'Activate',
            'new-merchant'    => 'New Merchant',
            'details'         => 'Details',
            'change-picture'  => 'Change Picture',
            'change-password' => 'Change Password',
            'new-ticket'      => 'New Ticket',
            'next'            => 'Next',
            'back'            => 'Back',
            'confirm'         => 'Confirm',
            'select-one'      => 'Select One',
            'update'          => 'Update',
            //new
            'filter'          => 'Filter',
        ],
        /////////second end////////////
        'deposit'       => [
            'title'                                       => 'Deposit',
            'deposit-via'                                 => 'Deposit Money via',
            'amount'                                      => 'Amount',
            'currency'                                    => 'Currency',
            'payment-method'                              => 'Payment Method',
            'no-payment-method'                           => 'Payment Method Not Found !',
            'fees-limit-payment-method-settings-inactive' => 'Fees Limit and Payment Method settings are both inactive',
            'total-fee'                                   => 'Total Fee: ',
            'total-fee-admin'                             => 'Total: ',
            'fee'                                         => 'Fee',
            //new
            'deposit-amount'                              => 'Deposit Amount',
            'completed-success'                           => 'Deposit Completed Successfully',
            'success'                                     => 'Success',
            'deposit-again'                               => 'Deposit Money Again',
            'deposit-stripe-form'                         => [
                'title'   => 'Deposit with Stripe',
                'card-no' => 'Card Number',
                'mm-yy'   => 'MM/YY',
                'cvc'     => 'CVC',
            ],
            'select-bank'                                 => 'Select Bank', //pm1.9
            'payment-references'                          => [
                'user-payment-reference' => 'User Payment Reference',
            ],
        ],
        'payout'        => [
            'menu'           => [
                // 'list' => 'List',
                'payouts'        => 'Payouts',
                'payout-setting' => 'Payout Setting',
                'new-payout'     => 'New Payout',
            ],
            'list'           => [
                'method'      => 'Method',
                // 'method-info' => 'Email/Account No.',
                'method-info' => 'Method Info',
                'charge'      => 'Charge',
                'amount'      => 'Amount',
                'currency'    => 'Currency',
                'status'      => 'Status',
                'date'        => 'Date',
                'not-found'   => 'Data Not Found !',
                'fee'         => 'Fee',
            ],
            'payout-setting' => [
                'add-setting' => 'Add Setting',
                'payout-type' => 'Payout Type',
                'account'     => 'Account',
                'action'      => 'Action',
                'modal'       => [
                    'title'                    => 'Add Payout Setting',
                    'payout-type'              => 'Payout Type',
                    'email'                    => 'Email',
                    'bank-account-holder-name' => 'Bank Account Holder\'s name',
                    'branch-name'              => 'Branch Name',
                    'account-number'           => 'Bank Account Number/IBAN',
                    'branch-city'              => 'Branch City',
                    'swift-code'               => 'SWIFT Code',
                    'branch-address'           => 'Branch Address',
                    'bank-name'                => 'Bank Name',
                    'attached-file'            => 'Attached File',
                    'country'                  => 'Country',
                    'payeer-account-number'    => 'Payeer Account Number',
                ],
            ],
            'new-payout'     => [
                'title'          => 'Payout',
                'payment-method' => 'Payment Method',
                'currency'       => 'Currency',
                // 'amount'=>'Payout Amount',
                'amount'         => 'Amount',
                'bank-info'      => 'Bank Account Info',
                'withdraw-via'   => 'You are about to payout money via',
                //new
                'success'        => 'Success',
                'payout-success' => 'Payout Completed Successfully',
                'payout-again'   => 'Payout Again',
            ],
        ],
        'confirmation'  => [
            'details' => 'Details',
            'amount'  => 'Amount',
            'fee'     => 'Fee',
            'total'   => 'Total',
        ],
        'transaction'   => [
            'date-range'      => 'Pick a date range',
            'all-trans-type'  => 'All Transaction Type',
            'payment-sent'    => 'Payment Sent',
            'payment-receive' => 'Payment Received',
            'payment-req'     => 'Payment Request',
            'exchanges'       => 'Exchanges', //TO DO:: translation to other languages
            'all-status'      => 'All Status',
            'all-currency'    => 'All Currency',
            'success'         => 'Success',
            'pending'         => 'Pending',
            'blocked'         => 'Cancelled',
            'refund'          => 'Refunded',
            'open-dispute'    => 'Open Dispute',
        ],
        'exchange'      => [
            'left-top'    => [
                'title'           => 'Exchange Currency',
                'select-wallet'   => 'Select Wallet',
                'amount-exchange' => 'Exchange Amount',
                'give-amount'     => 'You will give',
                'get-amount'      => 'You will get',
                'balance'         => 'Available',
                'from-wallet'     => 'From Wallet',
                'to-wallet'       => 'To Wallet',
                'base-wallet'     => 'From Wallet',
                'other-wallet'    => 'To Wallet',
                'type'            => 'Exchange Type',
                'type-text'       => 'Base currency is: ',
                'to-other'        => 'To Other Currency',
                'to-base'         => 'To Base Currency',
            ],
            'left-bottom' => [
                'title'            => 'Currency Exchange (To Base Currency)',
                'exchange-to-base' => 'Exchange to base',
                'wallet'           => 'Wallet',
            ],
            'right'       => [
                'title' => 'Exchange Rate',
            ],
            'confirm'     => [
                'title'                => 'Exchange Money',
                'exchanging'           => 'Exchanging',
                'of'                   => 'of',
                'equivalent-to'        => 'Equivalent To',
                'exchange-rate'        => 'Exchange Rate',
                'amount'               => 'Exchange Amount',
                'has-exchanged-to'     => 'has exchanged to',
                'exchange-money-again' => 'Exchange Money Again',
            ],
        ],

        'send-request'  => [
            'menu'         => [
                'send'    => 'Cash-In',
                'request' => 'Cash-Out',
            ],
            'send'         => [
                'title'        => 'Cash-In',
                'confirmation' => [
                    'title'              => 'Send Money',
                    'send-to'            => 'You are sending money to',
                    'transfer-amount'    => 'Transfer Amount',
                    'money-send'         => 'Money Transferred Successfully',
                    'bank-send'          => 'Money Transferred To Bank Successfully',
                    'send-again'         => 'Send Money Again',
                    'send-to-bank-again' => 'Transfer To Bank Again',
                ],
            ],
            'send-to-bank' => [
                'title'        => 'Transfer To Bank',
                'subtitle'     => 'Transfer Money To Bank',
                'confirmation' => [
                    'title'           => 'Transfer Money To Bank',
                    'send-to'         => 'You are sending money to',
                    'transfer-amount' => 'Transfer Amount',
                    'money-send'      => 'Money Transferred Successfully',
                    'send-again'      => 'Send money again',
                ],
            ],
            'request'      => [
                'title'        => 'Request Money',
                'confirmation' => [
                    'title'              => 'Request Money',
                    'request-money-from' => 'You are requesting money from',
                    'requested-amount'   => 'Requested Amount',
                    'success'            => 'Success',
                    'success-send'       => 'Money Request Sent Successfully',
                    'request-amount'     => 'Request Amount',
                    'request-again'      => 'Request Money Again',
                ],
                'success'      => [
                    'title'            => 'Accept Request Money',
                    'request-complete' => 'Requested Money Accepted Successfully',
                    'accept-amount'    => 'Accepted Amount',
                ],
                'accept'       => [
                    'title' => 'Accept Request Payment',
                ],
            ],
            'common'       => [
                'recipient'   => 'Receiver Email',
                'receivername'   => 'Receiver Name',
                'amount'      => 'Amount',
                'currency'    => 'Currency',
                'note'        => 'Note',
                'anyone-else' => 'We\'ll never share your email with anyone else.',
                'enter-note'  => 'Enter Note',
                'enter-email' => 'Enter Email',
            ],
        ],

        'vouchers'      => [
            'success' => [
                'print' => 'Print',
            ],
        ],

        'merchant'      => [
            'menu'                => [
                'merchant'      => 'Merchants',
                'payment'       => 'Payments',
                'list'          => 'List',
                'details'       => 'Details',
                'edit-merchant' => 'Edit Merhcant',
                'new-merchant'  => 'New Merchant',
            ],
            'table'               => [
                'id'            => 'ID',
                'business-name' => 'Business Name',
                'site-url'      => 'Site Url',
                'type'          => 'Type',
                'status'        => 'Status',
                'action'        => 'Action',
                'not-found'     => 'Data Not Found !',
                'moderation'    => 'Moderation',
                'disapproved'   => 'Disapproved',
                'approved'      => 'Approved',
            ],

            'html-form-generator' => [
                'title'             => 'HTML Form generator',
                'merchant-id'       => 'Merchant ID',
                'item-name'         => 'Item name',
                'order-number'      => 'Order number',
                'price'             => 'Price',
                'custom'            => 'Custom',
                'right-form-title'  => 'Example HTML form',
                'right-form-copy'   => 'Copy',
                'right-form-copied' => 'Copied',
                'right-form-footer' => 'Copy the form code and place it on your website.',
                'close'             => 'Close',
                'generate'          => 'Generate',
                'app-info'          => 'App info',
                'client-id'         => 'Client ID',
                'client-secret'     => 'Client Secret',
            ],

            'payment'             => [
                'merchant'   => 'Merchant',
                'method'     => 'Method',
                'order-no'   => 'Order no',
                'amount'     => 'Amount',
                'fee'        => 'Fee',
                'total'      => 'Total',
                'currency'   => 'Currency',
                'status'     => 'Status',
                'created-at' => 'Date',
                'pending'    => 'Pending',
                'success'    => 'Success',
                'block'      => 'Block',
                'refund'     => 'Refund',
            ],
            'add'                 => [
                'title'    => 'Create Merchant',
                'name'     => 'Name',
                'site-url' => 'Site Url',
                'type'     => 'Type',
                'note'     => 'Note',
                'logo'     => 'Logo',
            ],
            'details'             => [
                'merchant-id'   => 'Merchant ID',
                'business-name' => 'Business Name',
                'status'        => 'Status',
                'site-url'      => 'Site Url',
                'note'          => 'Note',
                'date'          => 'Date',
            ],
            'edit'                => [
                'comment-for-administration' => 'Comment for administration',
            ],
        ],

        'dispute'       => [
            'dispute'        => 'Disputes',
            'title'          => 'Title',
            'dispute-id'     => 'Dispute ID',
            'transaction-id' => 'Transaction ID',
            'created-at'     => 'Created At',
            'status'         => 'Status',
            'no-dispute'     => 'Data Not Found!',
            'defendant'      => 'Defendant',
            'claimant'       => 'Claimant',
            'description'    => 'Description',
            'status-type'    => [
                'open'   => 'Open',
                'solved' => 'Solved',
                'closed' => 'Closed',
                'solve'  => 'Solve',
                'close'  => 'Close',
            ],
            'discussion'     => [
                'sidebar' => [
                    'title-text'    => 'Dispute Information',
                    'header'        => 'Dispute Information',
                    'title'         => 'Title',
                    'reason'        => 'Reason',
                    'change-status' => 'Change Status',
                ],
                'form'    => [
                    'title'   => 'View Dispute',
                    'message' => 'Message',
                    'file'    => 'File',
                ],
            ],
        ],

        'setting'       => [
            'title'                   => 'User Profile',
            'change-avatar'           => 'Change Avatar',
            'change-avatar-here'      => 'You can change avatar here',
            'change-password'         => 'Change Password',
            'change-password-here'    => 'You can change password here',
            'profile-information'     => 'Profile Information',
            'email'                   => 'Email',
            'first-name'              => 'First Name',
            'last-name'               => 'Last Name',
            'mobile'                  => 'Mobile No',
            'address1'                => 'Address 1',
            'address2'                => 'Address 2',
            'city'                    => "City",
            'state'                   => 'State',
            'country'                 => 'Country',
            'timezone'                => 'Time Zone',
            'old-password'            => 'Old Password',
            'new-password'            => 'New Password',
            'confirm-password'        => 'Confirm Password',
            'add-phone'               => 'Add Phone',
            'add-phone-subhead1'      => 'Click on',
            'add-phone-subhead2'      => 'to add phone',
            'add-phone-subheadertext' => 'Enter the number you’d like to use',
            'get-code'                => 'Get Code',
            'phone-number'            => 'Phone Number',
            'edit-phone'              => 'Edit Phone',
            'default-wallet'          => 'Default Wallet',
        ],

        'ticket'        => [
            'title'     => 'Tickets',
            'ticket-no' => 'Ticket No',
            'subject'   => 'Subject',
            'status'    => 'Status',
            'priority'  => 'Priority',
            'date'      => 'Date',
            'action'    => 'Action',
            'no-ticket' => 'Data not found!',
            'add'       => [
                'title'    => 'New Ticket',
                'name'     => 'Name',
                'message'  => 'Message',
                'priority' => 'Priority',
            ],
            'details'   => [
                'sidebar' => [
                    'header'    => 'Ticket Information',
                    'ticket-id' => 'Ticket ID',
                    'subject'   => 'Subject',
                    'date'      => 'Date',
                    'priority'  => 'Priority',
                    'status'    => 'Status',
                ],
                'form'    => [
                    'title'   => 'View Ticket',
                    'message' => 'Message',
                    'file'    => 'File',
                ],
            ],
        ],

        // Crypto
        'crypto'        => [
            'send'    => [
                'create'  => [
                    'recipient-address-input-label-text'         => 'Recipient Address',
                    'recipient-address-input-placeholder-text-1' => 'Enter valid recipient',
                    'recipient-address-input-placeholder-text-2' => 'address',
                    'address-qr-code-foot-text-1'                => 'Only send',
                    'amount-warning-text-1'                      => 'The amount withdrawn/sent must at least be',
                    'amount-warning-text-2'                      => 'Please keep at least',
                    'amount-warning-text-3'                      => 'for network fees',
                    'amount-warning-text-4'                      => 'Crypto transactions might take few moments to complete',
                    'amount-allowed-decimal-text'                => 'Allowed upto 8 decimal places',
                ],
                'confirm' => [
                    'about-to-send-text-1' => 'You are about to send',
                    'about-to-send-text-2' => 'to',
                    'sent-amount'          => 'Sent Amount',
                    'network-fee'          => 'Network Fee',
                ],
                'success' => [
                    'sent-successfully' => 'Sent Successfully',
                    'amount-added'      => 'Amount will be added after',
                    'confirmations'     => 'confirmations',
                    'address'           => 'Address',
                    'again'             => 'Again',
                ],
            ],
            'receive' => [
                'address-qr-code-head-text'   => 'Receiving Address Qr Code',
                'address-qr-code-foot-text-1' => 'Only receive',
                'address-qr-code-foot-text-2' => 'to this address',
                'address-qr-code-foot-text-3' => 'receiving any other coin will result in permanent loss',
                'address-input-label-text'    => 'Receiving Address',
                'address-input-copy-text'     => 'Copy',
            ],
            'transactions' => [
                'receiver-address' => 'Receiver Address',
                'sender-address' => 'Sender Address',
                'confirmations' => 'Confirmations',
            ],
            'preference-disabled' => 'The system adminstrator has disabled crypto currency',
        ],
    ],

    'express-payment'      => [
        'payment'           => 'Payment',
        'pay-with'          => 'Pay with',
        'about-to-make'     => 'You are about to make payment via',
        'test-payment-form' => 'Test payment form',
        'pay-now'           => 'Pay now!',
    ],

    'express-payment-form' => [
        'merchant-not-found'   => 'Merchant Not Found ! Please try with valid merchant.',
        'merchant-found'       => 'The recipient underwent a special verification and confirmed his reliability',
        'continue'             => 'Continue',
        'email'                => 'Email',
        'password'             => 'Password',
        'cancel'               => 'Cancel',
        'go-to-payment'        => 'Go to payment',
        'payment-agreement'    => 'Payment is made on a secure page. When making a payment, you agree to the Terms of Agreement',
        'debit-credit-card'    => 'Credit/Debit Card',
        'merchant-payment'     => 'Merchant Payment',
        'sorry'                => 'Sorry!',
        'payment-unsuccessful' => 'Payment unsuccessful.',
        'success'              => 'Success!',                        //
        'payment-successfull'  => 'Payment successfully completed.', //
        'back-home'            => 'Back Home',
    ],

];
