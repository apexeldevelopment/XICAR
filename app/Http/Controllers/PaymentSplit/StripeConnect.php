<?php

namespace App\Http\Controllers\PaymentSplit;

use Stripe\Account;
use Stripe\Balance;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Traits\ImageTrait;
use App\Models\PaymentOption;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use App\Models\PaymentOptionsConfiguration;
use App\Http\Controllers\PaymentSplit\StripeConnectHelper;
use App\Models\StripePayout;

class StripeConnect
{
    use MerchantTrait, ImageTrait;
    /* Set Stripe Connect API */
    private static function set_api_key($merchant_id, $return_key_only = false)
    {
        $stripe_api_key = null;
        $merchant = Merchant::findOrFail($merchant_id);
        if (isset($merchant->Configuration->stripe_connect_enable) && $merchant->Configuration->stripe_connect_enable == 1) {
            $payment_option = PaymentOption::where('slug', 'STRIPE')->first();
            $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant->id], ['payment_option_id', '=', $payment_option->id]])->first();
            if (!$paymentoption) {
                throw new \Exception("Configuration not found");
            }
            $stripe_api_key = $paymentoption->api_secret_key;
        }
        if ($return_key_only) {
            return $stripe_api_key;
        }
        \Stripe\Stripe::setApiKey($stripe_api_key);
    }

    /* create driver account  */
    public static function create_driver_account($driver, $verification_details)
    {
        self::set_api_key($driver->merchant_id);
        $user_details = self::get_driver_details($driver, $verification_details);
        $account = self::create_account($user_details);
        $driver = self::save_details($driver, $account);
        // self::sync_account($driver);
        return $driver;
    }


    //    update driver account
    public static function update_driver_account($driver, $verification_details)
    {

        if (!$driver->sc_account_id) {
            throw new \Exception('Account not created');
        }

        self::set_api_key($driver->merchant_id);
        $user_details = self::get_driver_details($driver, $verification_details);

        $account = self::update_account($driver->sc_account_id, $user_details);

        $driver = self::save_details($driver, $account);

        // self::sync_account($driver , $driver->player_id);

        return $driver;
    }

    /*
     * save driver account details
     */
    public static function save_details($user_driver, $account)
    {
        $user_driver->sc_account_id = $account->id;
        $user_driver->sc_account_status = 'pending';
        $user_driver->save();


        // if ($user_driver->DriverDetail && $user_driver->DriverDetail->card_token != null) {
        //     try {
        //         // Set Stripe API key (usually secret key)
        //         self::set_api_key($user_driver->merchant_id);

        //         Log::info('Adding card to Stripe account', [
        //             'connected_account_id' => $account->id,
        //             'card_token' => $user_driver->DriverDetail->card_token,
        //         ]);

        //         // Add external card
        //         $card = \Stripe\Account::createExternalAccount(
        //             $account->id,
        //             [
        //                 'default_for_currency' => true,
        //                 'external_account' => $user_driver->DriverDetail->card_token,
        //             ]
        //         );

        //         Log::info('Card added successfully', [
        //             'card_id' => $card->id,
        //             'last4' => $card->last4,
        //             'brand' => $card->brand,
        //             'exp_month' => $card->exp_month,
        //             'exp_year' => $card->exp_year,
        //         ]);

        //         // Set card as default payout method
        //         \Stripe\Account::update(
        //             $account->id,
        //             [
        //                 'default_external_account' => $card->id,
        //             ]
        //         );

        //         Log::info('Card set as default payout method', [
        //             'connected_account_id' => $account->id,
        //             'default_card_id' => $card->id,
        //         ]);
        //     } catch (\Exception $e) {
        //         Log::error('Failed to add or set card for payout', [
        //             'connected_account_id' => $account->id,
        //             'error' => $e->getMessage(),
        //         ]);
        //     }
        // }


        $payment_option = PaymentOption::where('slug', 'STRIPE')->first();
        $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=',   $user_driver->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (!$paymentoption) {
            throw new \Exception("Configuration not found");
        }
        $api_public_key = $paymentoption->api_public_key;
        $user_driver->add_card = true;
        $user_driver->add_card_url = route('connect.card.form', [
            'publishableKey' => encrypt($api_public_key),
            'connectAccountId' => encrypt($user_driver->sc_account_id),
            'currency' => encrypt($user_driver->Country->isoCode) ?? 'USD',
        ]);
        $user_driver->add_card_success_url = route('stripe.connect.card.success');
        return $user_driver;
    }


    public static function add_debit_card($user_driver)
    {
        if ($user_driver->DriverDetail && $user_driver->DriverDetail->card_token != null) {
            try {
                // Set Stripe API key (usually secret key)
                self::set_api_key($user_driver->merchant_id);

                Log::info('Adding card to Stripe account', [
                    'connected_account_id' => $user_driver->sc_account_id,
                    'card_token' => $user_driver->DriverDetail->card_token,
                ]);

                // Add external card
                $card = \Stripe\Account::createExternalAccount(
                    $user_driver->sc_account_id,
                    [
                        'default_for_currency' => true,
                        'external_account' => $user_driver->DriverDetail->card_token,
                    ]
                );

                Log::info('Card added successfully', [
                    'card_id' => $card->id,
                    'last4' => $card->last4,
                    'brand' => $card->brand,
                    'exp_month' => $card->exp_month,
                    'exp_year' => $card->exp_year,
                ]);

                // // Set card as default payout method
                // \Stripe\Account::update(
                //     $user_driver->sc_account_id,
                //     [
                //         'default_external_account' => $card->id,
                //     ]
                // );
                return true;
                // Log::info('Card set as default payout method', [
                //     'connected_account_id' => $user_driver->sc_account_id,
                //     'default_card_id' => $card->id,
                // ]);
            } catch (\Exception $e) {
                Log::error('Failed to add or set card for payout', [
                    'connected_account_id' => $user_driver->sc_account_id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }
    }

    /*
     * update driver account details
     */
    public static function update_details($user_driver)
    {
        $user_driver->sc_account_status = 'pending';
        $user_driver->save();
        return $user_driver;
    }

    /*  generate details from driver instance.
        This function return details specific to
        account creation only for transfers mode + country USA.  */
    private static function get_driver_details(Driver $driver, $verification_details)
    {
        $short_code = strtoupper($driver->CountryArea->Country->short_code);
        switch ($short_code) {
            case 'US':
                return StripeConnectHelper::UnitedStateValidator($driver, $verification_details);
                break;
            case 'AU':
                return StripeConnectHelper::AustraliaValidator($driver, $verification_details);
                break;
            case 'LU':
                return StripeConnectHelper::LuxembourgValidator($driver, $verification_details);
                break;
            case 'GB':
                return StripeConnectHelper::UnitedKingdomValidator($driver, $verification_details);
                break;
            default:
                throw new \Exception('Sorry stripe connect is not in your country');
                break;
        }
    }

    private static function create_account($user_details)
    {
        $short_code = strtoupper($user_details['short_code']);
        switch ($short_code) {
            case 'US':
                return StripeConnectHelper::UnitedStateCreateAccount($user_details);
                break;
            case 'AU':
                return StripeConnectHelper::AustraliaCreateAccount($user_details);
                break;
            case 'LU':
                return StripeConnectHelper::LuxembourgCreateAccount($user_details);
                break;
            case 'GB':
                return StripeConnectHelper::UnitedKingdomCreateAccount($user_details);
                break;
            default:
                throw new \Exception('Sorry stripe connect in not in your country');
                break;
        }
    }

    private static function update_account($account_id, $user_details)
    {
        try {
            $update = \Stripe\Account::update(
                $account_id,
                [
                    'individual' => [
                        'first_name' => $user_details['first_name'],
                        'last_name' => $user_details['last_name'],
                        'dob' => [
                            'day' => $user_details['dob_day'],
                            'month' => $user_details['dob_month'],
                            'year' => $user_details['dob_year']
                        ],
                        //                        'ssn_last_4' => substr($user_details['ssn'] , -4),
                        'id_number' => $user_details['ssn'],
                        'phone' => $user_details['phone'],
                        'email' => $user_details['email'],
                        'address' => [
                            'line1' => $user_details['line1'],
                            //                        'line2' => $user_details['line2'],
                            'city' => $user_details['city'],
                            'state' => $user_details['state'],
                            'postal_code' => $user_details['postal_code']
                        ],
                        'verification' => [
                            //                        'customer_signature' => $user_details['personal'],
                            'document' => $user_details['document'],
                            'additional_document' => $user_details['additional_document']
                        ],
                    ],
                    'external_account' => $user_details['external_account']
                ]
            );

            return $update;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /*
     * bank details
     */
    private static function get_driver_bank_details(Driver $driver)
    {

        return [
            'object' => 'bank_account',
            'country' => $driver->CountryArea->Country->short_code,
            'currency' => $driver->CountryArea->Country->isoCode,
            'account_number' => $driver->account_number,
            'routing_number' => $driver->routing_number
        ];
    }

    /*
     * update bank details
     */
    private static function update_bank_details($account_id, $user_details)
    {
        try {

            $update = \Stripe\Account::update(
                $account_id,
                [
                    'external_account' => $user_details
                ]
            );

            return $update;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /*
     * update driver bank details
     */
    public static function update_driver_bank_details(Driver $driver)
    {
        if (!$driver->sc_account_id) {
            throw new \Exception('No stripe account added');
        }
        self::set_api_key($driver->merchant_id);

        $driver_bank_details = self::get_driver_bank_details($driver);
        self::update_bank_details($driver->sc_account_id, $driver_bank_details);
        $driver = self::update_details($driver);
        //        $driver = self::sync_account($driver , $driver->player_id);
        return $driver;
    }

    /*
     * tranfer amount to driver
     */
    public static function charge_amount($driver_payable_amount, $total_amount, $sc_account_id, $token, $currency, $merchant_id, $connectedAccId = NULL, $order_id)
    {
        self::set_api_key($merchant_id);
        try {
            $merchant = Merchant::findOrFail($merchant_id);

            if ($merchant->Configuration->stripe_connect_store_enable == 1) {
                $application_fee_amount = (int)($total_amount * 100) - (int)($driver_payable_amount * 100);

                $charge = \Stripe\Charge::create([
                    "amount" => (int)($total_amount * 100),
                    "currency" => $currency,
                    "customer" => $token,
                    "transfer_data" => [
                        "destination" => $sc_account_id,
                    ],
                    "application_fee_amount" => $application_fee_amount,
                    "transfer_group" => "ORDER_" . $order_id,
                ]);

                \Log::info('Charge created (with application_fee)', [
                    'charge_id' => $charge->id,
                    'amount' => $charge->amount,
                    'application_fee_amount' => $application_fee_amount,
                    'order_id' => $order_id,
                    'account' => $sc_account_id,
                ]);
            } else {
                if ($connectedAccId) {
                    $charge = \Stripe\Charge::create([
                        "amount" => (int)($total_amount * 100),
                        "currency" => $currency,
                        "customer" => $token,
                        "transfer_data" => [
                            "amount" => (int)($driver_payable_amount * 100),
                            "destination" => $sc_account_id,
                        ],
                    ], [
                        'stripe_account' => $connectedAccId,
                    ]);
                } else {
                    $charge = \Stripe\Charge::create([
                        "amount" => (int)($total_amount * 100),
                        "currency" => $currency,
                        "customer" => $token,
                        "transfer_data" => [
                            "amount" => (int)($driver_payable_amount * 100),
                            "destination" => $sc_account_id,
                        ],
                    ]);
                }

                \Log::info('Charge created (manual transfer)', [
                    'charge_id' => $charge->id,
                    'amount' => $charge->amount,
                    'order_id' => $order_id,
                    'account' => $sc_account_id,
                ]);

                $account = \Stripe\Account::retrieve($sc_account_id);
                $externalAccounts = $account->external_accounts->data ?? [];

                $hasDebitCard = collect($externalAccounts)->contains(function ($account) {
                    return $account->object === 'card' && $account->funding === 'debit';
                });

                if ($hasDebitCard) {
                    StripePayout::create([
                        'stripe_account' =>  $sc_account_id,
                        'amount' => (int)($driver_payable_amount * 100),
                        'currency' => $currency,
                        'merchant_id' => $merchant_id
                    ]);
                }
            }

            return $charge;
        } catch (\Exception $e) {
            \Log::error('Stripe Charge or Payout error', [
                'error' => $e->getMessage(),
                'merchant_id' => $merchant_id,
                'order_id' => $order_id,
            ]);
            throw new \Exception($e->getMessage());
        }
    }


    /*
     * Retrieve Account Details
     */
    public static function retrieve_account_details($sc_account_id, $merchant_id)
    {
        self::set_api_key($merchant_id);
        $account_details = \Stripe\Account::retrieve(
            $sc_account_id
        );
        return $account_details;
    }

    public static function upload_file($file, $merchant_id, $purpose)
    {
        self::set_api_key($merchant_id);
        try {
            $fp = fopen($file, 'r');
            $file = \Stripe\File::create([
                'purpose' => $purpose,
                'file' => $fp
            ]);
            return $file;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function sync_drivers_bulk()
    {
        $drivers = Driver::where('sc_account_status', 'pending')->get();
        foreach ($drivers as $driver) {
            self::set_api_key($driver->merchant_id);
            self::sync_account($driver, $driver->merchant_id);
        }
        return $drivers;
    }

    public static function sync_account($user_driver)
    {
        if (!$user_driver->sc_account_id) {
            throw new \Exception('Driver is not registered to stripe connect');
        }
        $error_array = [];
        $account = self::retrieve_account_details($user_driver->sc_account_id, $user_driver->merchant_id);
        $charges_enabled = $account->charges_enabled;
        $payouts_enabled = $account->payouts_enabled;
        $due_list = $account->requirements->currently_due;
        $error_list = $account->requirements->errors;
        $document_verification_status = $account->individual->verification->status;
        if ($charges_enabled && $payouts_enabled) {
            $user_driver->sc_account_status = 'active';
            $user_driver->sc_address_status = 'verified';
            $user_driver->sc_identity_photo_status = 'verified';
            $user_driver->save();

            $msg = 'Your bank account activated now.';
            $title = "Stripe Connect";
            $data['notification_type'] = "STRIPE_CONNECT";
            $data['segment_type'] = "";
            $data['segment_data'] = [];
            $arr_param = ['driver_id' => $user_driver->id, 'data' => $data, 'message' => $msg, 'merchant_id' => $user_driver->merchant_id, 'title' => $title, 'large_icon' => ''];
            Onesignal::DriverPushMessage($arr_param);
        } else {
            $change = false;
            if ($document_verification_status == 'verified') {
                $user_driver->sc_identity_photo_status = 'verified';
                $user_driver->sc_due_list = NULL;
                $change = true;
            }
            if (!empty($error_list)) {
                $user_driver->sc_account_status = 'rejected';
                $user_driver->sc_due_list = self::refactor_error_list($error_list);
                //                $user_driver->sc_due_list = self::refactor_due_list($due_list);
                $change = true;
            }
            if ($change) {
                $user_driver->save();
            }
        }
        return $user_driver;
    }

    //    public static function StripeConnectPendingAction()
    //    {
    //        $drivers = Driver::where('sc_account_status','pending')->get();
    //        if(!empty($drivers)){
    //            foreach ($drivers as $driver)
    //                self::sync_account($driver);
    //        }
    //    }

    //    public static function StripeConnectPending()
    //    {
    //        $drivers = Driver::where('sc_account_status','pending')->get();
    //        if(!empty($drivers)){
    //            return true;
    //        }else{
    //            return false;
    //        }
    //    }

    private static function refactor_due_list($due_list)
    {
        $data = [];
        foreach ($due_list as $due_item) {
            $item = explode('.', $due_item);
            $data[] = end($item);
        }

        return json_encode($data);
    }

    private static function refactor_error_list($error_list)
    {
        $data = [];
        foreach ((array)$error_list as $error) {
            $message = explode('.', $error->requirement)[2] . ' - ' . $error->reason;
            array_push($data, $message);
        }
        return json_encode($data);
    }

    public static function check_stripe_status($status)
    {
        switch ($status) {
            case 'active':
                $return = [
                    'result' => '1',
                    'message' => 'Account Active'
                ];
                break;

            case 'pending':
                $return = [
                    'result' => '2',
                    'message' => 'Account Pending'
                ];
                break;

            case 'rejected':
                $return = [
                    'result' => '3',
                    'message' => 'Account Rejected'
                ];
                break;

            default:
                $return = [
                    'result' => '4',
                    'message' => 'Not Registered'
                ];
                break;
        }
        return $return;
    }

    public static function delete_account($driver)
    {
        try {
            self::set_api_key($driver->merchant_id);
            $account = \Stripe\Account::retrieve(
                $driver->sc_account_id
            );
            $result = $account->delete();
            if (isset($result->deleted) && $result->deleted == 1) {
                $driver->sc_account_id = NULL;
                $driver->sc_account_status = NULL;
                $driver->sc_due_list = NULL;
                $driver->save();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }





    public static function create_store_account(array $store_details)
    {
        try {
            self::set_api_key($store_details['merchant_id']);

            $account_data = [
                'type' => 'custom',
                'country' => $store_details['country'],
                'email' => $store_details['email'],
                'business_type' => 'individual',
                'business_profile' => [
                    'url' => $store_details['business_url'] ?? '',
                    'mcc' => '5045', // example MCC (B2B Computer Equipment)
                ],
                'individual' => [
                    'first_name' => $store_details['first_name'],
                    'last_name' => $store_details['last_name'],
                    'email' => $store_details['email'],
                    'phone' => $store_details['phone'],
                    'dob' => [
                        'day' => $store_details['dob_day'],
                        'month' => $store_details['dob_month'],
                        'year' => $store_details['dob_year'],
                    ],
                    'address' => [
                        'line1' => $store_details['address_line_1'],
                        'line2' => $store_details['address_line_2'] ?? null,
                        'city' => $store_details['city'],
                        'state' => $store_details['state'],
                        'postal_code' => $store_details['postal_code'],
                        'country' => $store_details['country'],
                    ],
                ],
                'tos_acceptance' => [
                    'date' => time(),
                    'ip' => $store_details['ip'], // Must be real user IP
                ],
            ];

            // Country-specific data
            switch (strtoupper($store_details['country'])) {
                case 'US':
                    $account_data['individual']['ssn_last_4'] = $store_details['ssn_last_4'];
                    $external_account = [
                        'object' => 'bank_account',
                        'country' => 'US',
                        'currency' => 'usd',
                        'routing_number' => $store_details['routing_number'],
                        'account_number' => $store_details['account_number'],
                        'account_holder_name' => $store_details['first_name'] . ' ' . $store_details['last_name'],
                        'account_holder_type' => 'individual',
                    ];
                    break;

                case 'AU':
                    $account_data['individual']['id_number'] = $store_details['id_number'];
                    $account_data['individual']['verification'] = [
                        'document' => [
                            'front' => $store_details['document_front'], // File ID from Stripe upload
                        ]
                    ];
                    $external_account = [
                        'object' => 'bank_account',
                        'country' => 'AU',
                        'currency' => 'aud',
                        'bsb_number' => $store_details['bsb_number'],
                        'account_number' => $store_details['account_number'],
                        'account_holder_name' => $store_details['account_holder_name'],
                        'account_holder_type' => 'individual',
                    ];
                    break;

                case 'GB':
                    $external_account = [
                        'object' => 'bank_account',
                        'country' => 'GB',
                        'currency' => 'gbp',
                        'sort_code' => $store_details['sort_code'],
                        'account_number' => $store_details['account_number'],
                        'account_holder_name' => $store_details['account_holder_name'],
                        'account_holder_type' => 'individual',
                    ];
                    break;

                case 'LU':
                    $external_account = [
                        'object' => 'bank_account',
                        'country' => 'LU',
                        'currency' => 'eur',
                        'account_number' => $store_details['iban'], // or use 'iban' field directly
                        'account_holder_name' => $store_details['account_holder_name'],
                        'account_holder_type' => 'individual',
                    ];
                    break;

                default:
                    throw new \Exception("Unsupported country: " . $store_details['country']);
            }

            $account_data['requested_capabilities'] = ['transfers', 'card_payments'];
            $account_data['external_account'] = $external_account;

            // Create Stripe Connect Account
            $account = Account::create($account_data);
            return $account;
        } catch (ApiErrorException $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function transfer_to_store($data)
    {
        try {
            self::set_api_key($data['merchant_id']);

            $transfer = \Stripe\Transfer::create([
                'amount' => (int) $data['amount'] * 100,
                'currency' => $data['currency'],
                'destination' => $data['sc_account_id'],
                'transfer_group' => "ORDER_" . $data['order_id'],
            ]);

            \Log::channel('debugger')->emergency('Store transfer successful', [
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'store_stripe_account' => $data['sc_account_id'],
                'stripe_transfer_id' => $transfer->id,
            ]);

            return $transfer;
        } catch (\Exception $e) {
            \Log::channel('debugger')->emergency('Store transfer failed', [
                'order_id' => $data['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception($e->getMessage());
        }
    }


    public  function upoload_store_document($request, $docRule, $businessSegment)
    {
        self::set_api_key($businessSegment->merchant_id);

        // Save files to local server and prepare for Stripe upload
        $stripeDocumentPaths = [];
        $frontFileObj = $request->file('document_front');

        // $frontFilePath = $frontFileObj->store('stripe-documents', 'public');
        $stripeDocumentPaths['front'] =  $this->uploadImage('document_front', 'stripe_connect_store_document', $businessSegment->merchant_id);

        // Upload front to Stripe
        $frontFile = \Stripe\File::create([
            'purpose' => 'identity_document',
            'file' => fopen($frontFileObj->getRealPath(), 'r'),
        ]);

        $backFile = null;
        if ($docRule['back']) {
            $backFileObj = $request->file('document_back');
            $backFilePath = $this->uploadImage('document_back', 'stripe_connect_store_document', $businessSegment->merchant_id);
            $stripeDocumentPaths['back'] = $backFilePath;
            $backFile = \Stripe\File::create([
                'purpose' => 'identity_document',
                'file' => fopen($backFileObj->getRealPath(), 'r'),
            ]);
        }

        // Get the person object
        $persons = \Stripe\Account::allPersons($businessSegment->sc_account_id);
        if (count($persons->data) == 0) {
            return false;
        }

        $personId = $persons->data[0]->id;

        // Update person with verification document
        $updateData = [
            'verification' => [
                'document' => [
                    'front' => $frontFile->id,
                ],
            ],
        ];

        if ($backFile) {
            $updateData['verification']['document']['back'] = $backFile->id;
        }

        // Update document on Stripe
        \Stripe\Account::updatePerson($businessSegment->sc_account_id, $personId, $updateData);

        // Retrieve updated person (fix: use Account::retrievePerson)
        $updatedPerson = \Stripe\Account::retrievePerson(
            $businessSegment->sc_account_id,
            $personId
        );

        $stripeStatus = $updatedPerson->verification['document']['status'] ?? 'unverified';

        // Map to integer
        $statusMap = [
            'unverified' => 0,
            'pending' => 1,
            'verified' => 2,
            'rejected' => 3,
            'requires_input' => 3,
        ];

        $statusCode = $statusMap[$stripeStatus] ?? 0;


        $businessSegment->stripe_document = json_encode($stripeDocumentPaths);
        $businessSegment->stripe_document_status = $statusCode;
        $businessSegment->signup_status = 1;
        $businessSegment->sc_account_status = 'active';
        $businessSegment->save();
        return true;
    }

    public static function retrieve_store_account_details($sc_account_id, $merchant_id)
    {
        self::set_api_key($merchant_id);
        $account_details = \Stripe\Account::retrieve(
            $sc_account_id
        );
        if ($account_details->individual->verification->status == 'verified') {
            return 1;
        } else {
            return 2;
        }
    }

    public static function delete_store_account_details($sc_account_id, $merchant_id)
    {
        try {
            self::set_api_key($merchant_id);
            $account = \Stripe\Account::retrieve(
                $sc_account_id
            );
            $result = $account->delete();
            if (isset($result->deleted) && $result->deleted == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function getStripeAccountBalance($connectedAccountId, $merchantSecretKey)
    {
        // Set the API key
        self::set_api_key($merchantSecretKey);

        // Fetch the balance for the connected account
        $balance = Balance::retrieve([], [
            'stripe_account' => $connectedAccountId
        ]);

        // Format balance details
        $formatAmounts = function ($balances) {
            return collect($balances)->map(function ($bal) {
                return [
                    'amount' => $bal->amount / 100, // Convert from cents
                    'currency' => strtoupper($bal->currency),
                    'source_type' => $bal->source_type ?? null,
                ];
            });
        };

        return [
            'available' => $formatAmounts($balance->available),
            'pending' => $formatAmounts($balance->pending),
            'instant_available' => isset($balance->instant_available)
                ? $formatAmounts($balance->instant_available)
                : [],
            'connect_reserved' => isset($balance->connect_reserved)
                ? $formatAmounts($balance->connect_reserved)
                : [],
            'livemode' => $balance->livemode,
        ];
    }



    public static function instant_payout($data)
    {
        try {
            self::set_api_key($data->merchant_id);

            $account = \Stripe\Account::retrieve($data->stripe_account);

            $hasDebitCard = collect($account->external_accounts->data)->contains(function ($acc) {
                return $acc->object === 'card' && $acc->funding === 'debit';
            });

            if (!$hasDebitCard) {
                \Log::warning('No debit card found, skipping payout', [
                    'account' => $data->stripe_account
                ]);
                return false;
            }

            $payout = \Stripe\Payout::create([
                'amount' => (int)$data->amount,
                'currency' => $data->currency,
                'method' => 'instant',
                'statement_descriptor' => 'Earnings',
            ], [
                'stripe_account' => $data->stripe_account,
            ]);

            \Log::info('✅ Payout created', [
                'payout_id' => $payout->id,
                'amount' => (int)$payout->amount,
                'currency' => $payout->currency,
                'status' => $payout->status,
                'account' => $data->stripe_account,
            ]);

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('❌ Stripe payout error', [
                'error' => $e->getMessage(),
                'account' => $data->stripe_account,
                'amount' => $data->amount,
            ]);
            return false;
        }
    }
}
