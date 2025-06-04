<?php


use WHMCS\Database\Capsule;

use WHMCS\Module\Server\emailSolution\Helper;
use Module\Server\emailSolution\ApiCall\ApiCall;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

$apiFile = __DIR__ . '/lib/ApiCall.php';

if (file_exists($apiFile)) {
    require_once $apiFile;
}

function email_solution_MetaData()
{
    return array(
        'DisplayName' => 'Openprovider Email Solution',
        'APIVersion' => '2.0',
        'RequiresServer' => true,
    );
}

function email_solution_ConfigOptions()
{
    try {
        if (!Capsule::schema()->hasTable('mod_email_solution_product')) {
            Capsule::schema()->create(
                'mod_email_solution_product',
                function ($table) {
                    $table->increments('id');
                    $table->string('service_id');
                    $table->text('domain');
                    $table->text('mailcow')->nullable();
                    $table->text('value1')->nullable();
                    $table->timestamp('datetime')->useCurrent();
                }
            );
        }

        if (Capsule::schema()->hasTable('mod_mailcow_product')) {
            $old_data = Capsule::table('mod_mailcow_product')->get();
            foreach ($old_data as $value) {
                Capsule::table('mod_email_solution_product')
                    ->insert([
                        'service_id' => $value->service_id,
                        'domain' => $value->domain,
                        'mailcow' => $value->mailcow,
                        'value1' => $value->value1,
                        'datetime' => $value->datetime,
                    ]);
                Capsule::table('mod_mailcow_product')->where('id', $value->id)->delete();    
            }
        }


        if (!Capsule::schema()->hasTable('mod_email_solution_token')) {
            Capsule::schema()->create(
                'mod_email_solution_token',
                function ($table) {
                    $table->increments('id');
                    $table->longText('response')->nullable();
                    $table->timestamp('datetime')->useCurrent();
                }
            );
        }


        if (Capsule::table('tblcustomfields')->where('fieldname', 'like', 'organization_handle|%')->count() == 0) {
            Capsule::table('tblcustomfields')->insert([
                'type' => 'client',
                'fieldtype' => 'text',
                'relid' => '0',
                'fieldname' => 'organization_handle|Organization Handle',
                'description' => 'use carefully',
                'adminonly' => 'on',
                'required' => '',
                'showorder' => '',
                'showinvoice' => '',
            ]);
        }
    } catch (Exception $e) {
        logModuleCall('Email Solution', __FUNCTION__, 'mod_email_solution_product create table', $e->getMessage(), ['error' => $e->getMessage()]);
    }

    return array(
        // a text field type allows for single line text input
        'No of Mail' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ),
        'Period Billing' => array(
            'Type' => 'dropdown',
            'Options' => array(
                '1' => 'Monthly',
                '12' => 'Yearly',
            ),
            'Description' => 'Choose one',
        ),
        'Description' => array(
            'Type' => 'textarea',
            'Default' => 'Server created by WHMCS #{$serviceId}',
            'Description' => 'If a Service ID is needed, please use the variable {$serviceId}; we will assign its value when the Service is created.',
        ),
        'Custom DNS' => array(
            'Type' => 'textarea',
            'Description' => 'Enter values in the format [Name/Host | Type | Value | Priority]. For multiple entries, use a new line for each.',
        ),
        'SMTP' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ),
        'IMAP' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ),
    );
}

function email_solution_TestConnection(array $params)
{
    try {

        if (!Capsule::schema()->hasTable('mod_email_solution_token')) {
            Capsule::schema()->create(
                'mod_email_solution_token',
                function ($table) {
                    $table->increments('id');
                    $table->longText('response')->nullable();
                    $table->timestamp('datetime')->useCurrent();
                }
            );
        }
        
        $apiCall = new ApiCall();
        $testConnection = $apiCall->generateToken();

        $testConnection = json_decode($testConnection, true);
        
        if ($testConnection['httpcode'] == 200) {
            return array('success' => true);
        } else {
            $errorMsg = $testConnection['result']['desc'];
            return array(
                'success' => false,
                'error' => $errorMsg,
            );
        }
        $errorMsg = '';
    } catch (Exception $e) {

        logModuleCall(
            'Email Solution',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
}

function email_solution_AdminServicesTabFields(array $params)
{

    if (!Capsule::schema()->hasTable('mod_email_solution_token')) {
        Capsule::schema()->create(
            'mod_email_solution_token',
            function ($table) {
                $table->increments('id');
                $table->longText('response')->nullable();
                $table->timestamp('datetime')->useCurrent();
            }
        );
    }

    try {
        $domainDetail = $getAlias = $getMailBox = [];
        $errorMessage = [];
        $returnAdmin = [];
        $token = '';
        $domain = $params['customfields']['domain'];

        if ($domain != '') {

            $apiCall = new ApiCall();

            // include new code 21-05-2025
            $oldToken = Capsule::table('mod_email_solution_token')->first();
            if ($oldToken->datetime) {
                $givenTime = new DateTime("$oldToken->datetime");
                $givenTime->modify('+48 hours');
                $currentTime = new DateTime();

                if ($currentTime >= $givenTime) {
                    $testConnection = $apiCall->generateToken();
                    $testConnection = json_decode($testConnection, true);
                } else {
                    $testConnection = $oldToken->response;
                    $testConnection = json_decode($testConnection, true);
                }
            } else {
                $testConnection = $apiCall->generateToken();
                $testConnection = json_decode($testConnection, true);
            }
            // end changes code 

            if ($testConnection['httpcode'] == 200) {

                $token = $testConnection['result']['data']['token'];

                $offset = 0;
                $limit = 100;

                // $baseUrl = "/v1beta/mailcow/domains/list?limit=$limit&offset=$offset";
                $baseUrl = "/v1beta/mailcow/domains/list";

                $getResponse = $apiCall->getCurl($token, $baseUrl, 'Get All Domain');

                if ($getResponse['httpcode'] == 200 && count($getResponse['result']->data->results) > 0) {
                    foreach ($getResponse['result']->data->results as $key => $value) {
                        $getDomain = $value->domain->name . '.' . $value->domain->extension;
                        if ($domain == $getDomain) {
                            $domainDetail = $value;
                            $returnAdmin['Domain Detail'] = "

                                <style>
                                    .cus.active{
                                        color: green;
                                        font-weight:bolder;
                                    }
                                    

                                </style>
                                <p><label>Domain:</label> <a href='$getDomain'>$getDomain</a><p>
                                <p><label>Message:</label>  {$value->message}</p>
                                <p><label>Status:</label> <span style='text-transform: capitalize;' class='cus {$value->status}'>{$value->status}</span> </p>
                            ";

                            $baseUrl = "/v1beta/mailcow/orders?limit=100&offset=0&domain.name={$domainDetail->domain->name}&domain.extension={$domainDetail->domain->extension}";
                            $getMailBox = $apiCall->getCurl($token, $baseUrl, 'Get MailBox');


                            $getMailBoxHTML = '<table width="100%" class="datatable" >
                                <tr >
                                    <th style="text-align:center;">Mailbox</th>
                                    <th style="text-align:center;">Mailbox Status</th>
                                    <th style="text-align:center;">Subscription Period</th>
                                    <th style="text-align:center;">Created At</th>
                                    <th style="text-align:center;">Renew At</th>
                                </tr>
                            ';

                            if ($getMailBox['httpcode'] == 200) {
                                $returnAdmin['Domain Detail'] .= "<p><label>Total No Of Mailbox: </label> {$getMailBox['result']->data->total}</p>";

                                if ($getMailBox['result']->data->total > 0) {
                                    foreach ($getMailBox['result']->data->results as $value) {

                                        if ($value->subscription_period == 1) {
                                            $subscription_period = 'Monthly';
                                        } else {
                                            $subscription_period = 'Yearly';
                                        }
                                        $getMailBoxHTML .= "<tr >
                                            <td style='text-align:center;' >{$value->mailbox}@{$value->domain->name}.{$value->domain->extension}</td>
                                            <td style='text-align:center; text-transform: capitalize;' class='cus {$value->mailbox_status}'>{$value->mailbox_status}</td>
                                            <td style='text-align:center;'>{$subscription_period}</td>
                                            <td style='text-align:center;'>{$value->created_at}</td>
                                            <td style='text-align:center;'>{$value->renew_at}</td>
                                        </tr>";
                                    }
                                } else {
                                    $getMailBoxHTML .= "<tr><td colspan=5>No Records Found</td></tr>";
                                }
                            }

                            $getMailBoxHTML .= '</table>';

                            $returnAdmin['All Mailbox'] = $getMailBoxHTML;

                            $baseUrl = "/v1beta/mailcow/alias";
                            $getAliases = $apiCall->getCurl($token, $baseUrl, 'Get MailBox Alias');

                            if ($getAliases['httpcode'] == 200) {
                                $getAliasHtml = '<table width="100%" class="datatable"><tr><th>Alias</th><th>Mailbox</th><th>Status</th></tr>';
                                $countAlias = 0;
                                foreach ($getAliases['result']->data->results as $value) {
                                    $domain = "@" . $domainDetail->domain->name . "." . $domainDetail->domain->extension;
                                    if (str_contains($value->mailbox, $domain)) {

                                        if ($value->active == 1) {
                                            $statusAlias = '<span class="cus active">Active</span>';
                                        } else {
                                            $statusAlias = '<span>Inactive</span>';
                                        }
                                        $getAliasHtml .= "<tr>
                                            <td style='text-align:center;'>{$value->alias}</td>
                                            <td style='text-align:center;'>{$value->mailbox}</td>
                                            <td style='text-align:center;'>{$statusAlias}</td>
                                        </tr>";
                                        $countAlias++;
                                    }
                                }

                                $returnAdmin['Domain Detail'] .= "<p><label>Total No Of Alias: </label> {$countAlias}</p>";

                                if ($countAlias == 0) {
                                    $getAliasHtml .= '<tr><td colspan=3>No Records Found</td></tr>';
                                }
                                $getAliasHtml .= '</table>';
                                $returnAdmin['All Alias'] = $getAliasHtml;
                            }
                        }
                    }
                }


                return $returnAdmin;
            } else {
                $errorMsg = $testConnection['result']->desc;
                throw new Exception($errorMsg);
            }
        } else {
            $errorMsg = 'Domain are not avaiable.';
            throw new Exception($errorMsg);
        }
    } catch (Exception $e) {
        logModuleCall('Email Solution', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
    }
}

function email_solution_CreateAccount(array $params)
{
    try {
        $apiCall = new ApiCall();

        $quantity = Capsule::table('mod_email_solution_product')->where('service_id', $params['serviceid'])->value('mailcow');
        if ($quantity == '') {
            if (!empty($params['configoptions']['email'])) {
                $quantity = $params['configoptions']['email'];
            } else {
                $quantity = $params['configoption1'];
            }
        }

        $desc = str_replace('{$serviceId}', $params['serviceid'], $params['configoption3']);

        if ($params['customfields']['domain'] != '') {
            if ((int)$quantity > 0) {

                // include new code 21-05-2025
                $oldToken = Capsule::table('mod_email_solution_token')->first();
                if ($oldToken->datetime) {
                    $givenTime = new DateTime("$oldToken->datetime");
                    $givenTime->modify('+48 hours');
                    $currentTime = new DateTime();

                    if ($currentTime >= $givenTime) {
                        $testConnection = $apiCall->generateToken();
                        $testConnection = json_decode($testConnection, true);
                    } else {
                        $testConnection = $oldToken->response;
                        $testConnection = json_decode($testConnection, true);
                    }
                } else {
                    $testConnection = $apiCall->generateToken();
                    $testConnection = json_decode($testConnection, true);
                }

                // end changes code 

                if ($testConnection['httpcode'] == 200) {
                    // $token = $testConnection['result']->data->token;
                    $token = $testConnection['result']['data']['token'];


                    $organization_handle_id = Capsule::table('tblcustomfields')->where('fieldname', 'like', 'organization_handle|%')->where('type', 'client')->value('id');
                    $organization_handle_value = Capsule::table('tblcustomfieldsvalues')->where('fieldid', $organization_handle_id)->where('relid', $params['userid'])->value('value');


                    if ($organization_handle_value == '') {
                        $data =  [
                            "address" => [
                                "city" => $params['clientsdetails']['city'],
                                "country" => $params['clientsdetails']['country'],
                                "number" => $params['clientsdetails']['address2'],
                                "state" => $params['clientsdetails']['state'],
                                "street" => $params['clientsdetails']['address1'],
                                "zipcode" => $params['clientsdetails']['postcode']
                            ],
                            "company_name" => $params['clientsdetails']['companyname'],
                            "email" => $params['clientsdetails']['email'],
                            "name" => [
                                "first_name" =>  $params['clientsdetails']['firstname'],
                                "full_name" =>  $params['clientsdetails']['fullname'],
                                "last_name" =>  $params['clientsdetails']['lastname'],
                            ],
                            "phone" => [
                                "area_code" =>  "72",
                                "country_code" =>  "+" . $params['clientsdetails']['phonecc'],
                                "subscriber_number" =>  $params['clientsdetails']['phonenumber']
                            ],
                        ];


                        $customer = $apiCall->postCurl($data, $token, '/v1beta/customers', 'Create Customer');

                        if ($customer['httpcode'] == 200) {
                            $organization_handle_value = $customer['result']->data->handle;

                            $customFieldIdForClient = Capsule::table('tblcustomfieldsvalues')->where('fieldid', $organization_handle_id)->where('relid', $params['userid'])->value('id');
                            if ($customFieldIdForClient) {
                                Capsule::table('tblcustomfieldsvalues')
                                    ->where('id', $customFieldIdForClient)
                                    ->update([
                                        'value' => $organization_handle_value
                                    ]);
                            } else {
                                Capsule::table('tblcustomfieldsvalues')->insert([
                                    'fieldid' =>  $organization_handle_id,
                                    'relid' => $params['userid'],
                                    'value' => $organization_handle_value
                                ]);
                            }
                        } else {
                            $errorMsg = $customer['result']->desc;
                            throw new Exception($errorMsg);
                        }
                    }

                    // $organization_handle_value store client id
                    if ($organization_handle_value != '') {

                        // $period = ($params['model']->billingcycle == 'Annually')? 12 : 1;
                        $period = (isset($params['configoption2']) && $params['configoption2'] > 0) ? $params['configoption2'] : 1;
                        $data = [
                            "period" => "$period",
                            "quantity" => "$quantity"
                        ];

                        $order = $apiCall->postCurl($data, $token, '/v1beta/mailcow/orders', 'Create order'); // create a new order 
                        if ($order['httpcode'] == 200) {
                            if ($params['customfields']['domain'] != '') {
                                $domains = explode('.', $params['customfields']['domain']);
                                $tld = $sld = '';

                                foreach ($domains as $key => $value) {
                                    if ($key == 0) {
                                        $sld = $value;
                                    } else {
                                        if ($tld == '') {
                                            $tld = $value;
                                        } else {
                                            $tld .= '.' . $value;
                                        }
                                    }
                                }

                                $data = [
                                    "description" =>  $desc, //change in future
                                    "domain" => [
                                        "extension" => "$tld",
                                        "name" => "$sld"
                                    ],
                                    "owner_handle" => "$organization_handle_value"
                                ];

                                $domain = $apiCall->postCurl($data, $token, '/v1beta/mailcow/domains', 'Create Domain'); // create domain
                                if ($domain['httpcode'] == 200) {

                                    if (Capsule::table('mod_email_solution_product')->where('service_id', $params['serviceid'])->count() > 0) {
                                        Capsule::table('mod_email_solution_product')
                                            ->where('service_id', $params['serviceid'])
                                            ->update([
                                                'mailcow' => (int)$quantity,
                                                'domain' => $params['customfields']['domain'],
                                            ]);
                                    } else {
                                        Capsule::table('mod_email_solution_product')->insert([
                                            'service_id' => $params['serviceid'],
                                            'domain' => $params['customfields']['domain'],
                                            'mailcow' => (int)$quantity
                                        ]);
                                    }
                                } else {
                                    $errorMsg = $domain['result']->desc;
                                    throw new Exception($errorMsg);
                                }
                            } else {
                                $errorMsg = 'Invalid domain name.';
                                throw new Exception($errorMsg);
                            }
                        } else {
                            $errorMsg = $order['result']->desc;
                            throw new Exception($errorMsg);
                        }
                    } else {
                        $errorMsg = 'Not Avaiable Organization Client Id ';
                        throw new Exception($errorMsg);
                    }
                } else {
                    $errorMsg = $testConnection['result']->desc;
                    throw new Exception($errorMsg);
                }
            } else {
                $errorMsg = 'NO Of  Email not present';
                throw new Exception($errorMsg);
            }
        } else {
            $errorMsg = 'Domain are not avaiable.';
            throw new Exception($errorMsg);
        }
    } catch (Exception $e) {
        logModuleCall('Email Solution ', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}


function email_solution_TerminateAccount(array $params)
{
    try {
        $apiCall = new ApiCall();

        // include new code 21-05-2025
        $oldToken = Capsule::table('mod_email_solution_token')->first();
        if ($oldToken->datetime) {
            $givenTime = new DateTime("$oldToken->datetime");
            $givenTime->modify('+48 hours');
            $currentTime = new DateTime();

            if ($currentTime >= $givenTime) {
                $testConnection = $apiCall->generateToken();
                $testConnection = json_decode($testConnection, true);
            } else {
                $testConnection = $oldToken->response;
                $testConnection = json_decode($testConnection, true);
            }
        } else {
            $testConnection = $apiCall->generateToken();
            $testConnection = json_decode($testConnection, true);
        }
        // end changes code 

        if ($testConnection['httpcode'] == 200) {
            // $token = $testConnection['result']->data->token;
            $token = $testConnection['result']['data']['token'];

            if ($params['customfields']['domain'] != '') {
                $domains = explode('.', $params['customfields']['domain']);
                $tld = $sld = '';

                foreach ($domains as $key => $value) {
                    if ($key == 0) {
                        $sld = $value;
                    } else {
                        if ($tld == '') {
                            $tld = $value;
                        } else {
                            $tld .= '.' . $value;
                        }
                    }
                }

                $baseUrl = "/v1beta/mailcow/orders?limit=100&offset=0&domain.name={$sld}&domain.extension={$tld}";
                $getMailBox = $apiCall->getCurl($token, $baseUrl, 'Get MailBox');

                if ($getMailBox['httpcode'] == 200) {
                    foreach ($getMailBox['result']->data->results as $value) {
                        $mailBoxId = $value->id;
                        $baseUrl = "/v1beta/mailcow/orders/{$mailBoxId}";
                        $delete = $apiCall->deleteCurl($token, $baseUrl, 'Delete MailBox');
                    }
                } else {
                    $errorMsg = $getMailBox['result']->desc;
                    throw new Exception($errorMsg);
                }

                $baseUrl = "/v1beta/mailcow/domains?domain.name=$sld&domain.extension=$tld";
                $delete = $apiCall->deleteCurl($token, $baseUrl, 'Delete Domain');

                if ($delete['httpcode'] == 200) {
                    Capsule::table('mod_email_solution_product')->where('service_id', $params['serviceid'])->delete();
                } else {
                    $errorMsg = $delete['result']->desc;
                    throw new Exception($errorMsg);
                }
            }
        } else {
            $errorMsg = $testConnection['result']->desc;
            throw new Exception($errorMsg);
        }
    } catch (Exception $e) {
        logModuleCall('Email Solution ', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}


function  email_solution_UnsuspendAccount(array $params)
{
    try {
    } catch (Exception $e) {
        logModuleCall('Email Solution ', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}

function  email_solution_SuspendAccount(array $params)
{
    try {
    } catch (Exception $e) {
        logModuleCall('Email Solution', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}


function email_solution_ClientArea(array $params)
{
    if (!Capsule::schema()->hasTable('mod_email_solution_token')) {
            Capsule::schema()->create(
                'mod_email_solution_token',
                function ($table) {
                    $table->increments('id');
                    $table->longText('response')->nullable();
                    $table->timestamp('datetime')->useCurrent();
                }
            );
        }
        
    try {
        $domainDetail = $getAlias = $getMailBox = [];
        $errorMessage = [];
        $token = '';
        $domain = $params['customfields']['domain'];

        if ($domain != '') {
            $apiCall = new ApiCall();

            // include new code 21-05-2025
            
            $oldToken = Capsule::table('mod_email_solution_token')->first();
            if ($oldToken->datetime) {
                $givenTime = new DateTime("$oldToken->datetime");
                $givenTime->modify('+48 hours');
                $currentTime = new DateTime();

                if ($currentTime >= $givenTime) {
                    $testConnection = $apiCall->generateToken();
                    $testConnection = json_decode($testConnection, true);
                } else {
                    $testConnection = $oldToken->response;
                    $testConnection = json_decode($testConnection, true);
                }
            } else {
                $testConnection = $apiCall->generateToken();
                $testConnection = json_decode($testConnection, true);
            }
            // end changes code 

            if ($testConnection['httpcode'] == 200) {
                // $token = $testConnection['result']->data->token;
                $token = $testConnection['result']['data']['token'];

                $offset = 0;
                $limit = 100;

                // $baseUrl = "/v1beta/mailcow/domains/list?limit=$limit&offset=$offset";
                $baseUrl = "/v1beta/mailcow/domains/list";
                $getResponse = $apiCall->getCurl($token, $baseUrl, 'Get All Domain');

                if ($getResponse['httpcode'] == 200 && count($getResponse['result']->data->results) > 0) {
                    foreach ($getResponse['result']->data->results as $key => $value) {
                        $getDomain = $value->domain->name . '.' . $value->domain->extension;
                        if ($domain == $getDomain) {
                            $domainDetail = $value;
                            break;
                        }
                    }
                }
            } else {
                $errorMsg = $testConnection['result']->desc;
                throw new Exception($errorMsg);
            }
        } else {
            $errorMsg = 'Domain are not avaiable.';
            throw new Exception($errorMsg);
        }



        if ($domainDetail->domain) {

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'getEmailDetail' && isset($_POST['emailId'])) {
                $response = array();
                $baseUrl = "/v1beta/mailcow/orders/{$_POST['emailId']}";
                $response['mailbox'] = ($apiCall->getCurl($token, $baseUrl, 'get mailbox'));

                // $baseUrl = "/v1beta/mailcow/orders/{$_POST['emailId']}/password";
                // $response['pass'] = ($apiCall->getCurl($token, $baseUrl, 'get mailbox password'));

                echo (json_encode($response));
                die();
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'getDNS') {
                $baseUrl = "/v1beta/mailcow/dkim?domain.name={$domainDetail->domain->name}&domain.extension={$domainDetail->domain->extension}";
                echo json_encode($apiCall->getCurl($token, $baseUrl, 'get mailbox'));
                die();
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'DeleteAlias' && isset($_POST['aliasId'])) {

                $baseUrl = "/v1beta/mailcow/alias/{$_POST['aliasId']}";
                $delete = $apiCall->deleteCurl($token, $baseUrl, 'Delete Alias');

                if ($delete['httpcode'] == 200) {
                    $errorMessage = ['status' => 'success', 'message' => 'Alias Deleted Successfully.'];
                } else {
                    $errorMsg = $delete['result']->desc;
                    $errorMessage = ['status' => 'error', 'message' => $errorMsg];
                }
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'DeleteMailbox' && isset($_POST['deleteMailbox'])) {
                $baseUrl = "/v1beta/mailcow/orders/{$_POST['deleteMailbox']}";
                $delete = $apiCall->deleteCurl($token, $baseUrl, 'Delete MailBox');

                if ($delete['httpcode'] == 200) {
                    $errorMessage = ['status' => 'success', 'message' => 'MailBox Deleted Successfully.'];
                } else {
                    $errorMsg = $delete['result']->desc;
                    $errorMessage = ['status' => 'error', 'message' => $errorMsg];
                }
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'addAlias' && isset($_POST['emailMailBox']) && isset($_POST['alias']) && strlen($_POST['alias']) > 0) {
                $data = [
                    "alias" => $_POST['alias'],
                    "domain" => [
                        "extension" => $domainDetail->domain->extension,
                        "name" => $domainDetail->domain->name
                    ],
                    "mailbox" => $_POST['emailMailBox']
                ];

                $createAliasMail = $apiCall->postCurl($data, $token, '/v1beta/mailcow/alias', 'Create Mail');

                if ($createAliasMail['httpcode'] == 200) {
                    $errorMessage = ['status' => 'success', 'message' => 'Alias is created successfully. '];
                } else {
                    $errorMsg = $createAliasMail['result']->desc;
                    $errorMessage = ['status' => 'error', 'message' => $errorMsg];
                }
            }

            $baseUrl = "/v1beta/mailcow/orders?limit=100&offset=0&domain.name={$domainDetail->domain->name}&domain.extension={$domainDetail->domain->extension}";
            $getMailBox = $apiCall->getCurl($token, $baseUrl, 'Get MailBox');

            $baseUrl = "/v1beta/mailcow/alias";
            $getAliases = $apiCall->getCurl($token, $baseUrl, 'Get MailBox Alias');
            foreach ($getAliases['result']->data->results as $value) {
                $domain = "@" . $domainDetail->domain->name . "." . $domainDetail->domain->extension;
                if (str_contains($value->mailbox, $domain)) {
                    $getAlias[] =  $value;
                }
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'addMailbox' && isset($_POST['full_name']) && isset($_POST['mailbox'])  && isset($_POST['password'])) {


                $limit = Capsule::table('mod_email_solution_product')->where('service_id', $params['serviceid'])->value('mailcow');

                $countTotalMailBox = 0;
                if (is_array($getMailBox['result']->data->results)) {
                    $countTotalMailBox = count($getMailBox['result']->data->results);
                }


                if ($limit > $countTotalMailBox && $limit > 0 && $limit != '') {
               
                    $resetPasswordFirstLogin = false;
                    if (isset($_POST['resetPasswordFirstLogin']) && $_POST['resetPasswordFirstLogin'] == 'on') {
                        $resetPasswordFirstLogin = true;
                    }

                    $subscription_period = (isset($params['configoption2']) && $params['configoption2'] > 0) ? $params['configoption2'] : 1;


                    $data = [
                        "domain" => [
                            "extension" => $domainDetail->domain->extension,
                            "name" => $domainDetail->domain->name
                        ],
                        "mailbox" => $_POST['mailbox'],
                        "name" => $_POST['full_name'],
                        "password" => $_POST['password'],
                        "reset_password" => $resetPasswordFirstLogin,
                        // "subscription_period" => $_POST['Subscriptionperiod']
                        "subscription_period" => $subscription_period
                    ];

                    

                    $createMail = $apiCall->postCurl($data, $token, '/v1beta/mailcow/orders/assign', 'Create Mail');

                    if ($createMail['httpcode'] == 200) {

                        $baseUrl = "/v1beta/mailcow/orders?limit=100&offset=0&domain.name={$domainDetail->domain->name}&domain.extension={$domainDetail->domain->extension}";
                        $getMailBox = $apiCall->getCurl($token, $baseUrl, 'Get MailBox');

                        $errorMessage = ['status' => 'success', 'message' => 'Mailbox is created successfully.'];
                    } else {
                        $errorMsg = $createMail['result']->desc;
                        $errorMessage = ['status' => 'error', 'message' => $errorMsg];
                    }
                } else {
                    $errorMessage = ['status' => 'error', 'message' => 'Mailbox limit reached. You cannot create more mailboxes under your current plan.'];
                }
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'changePassword' && isset($_POST['mailbox'])) {

                $data = [
                    "domain" => [
                        "extension" => $domainDetail->domain->extension,
                        "name" => $domainDetail->domain->name
                    ],
                    "mailbox" => $_POST['mailbox'],
                    "name" => $_POST['user_name'],
                    "password" => $_POST['new_password'],
                    "password_confirmation" => $_POST['confirm_password'],
                ];

                $changeMail = $apiCall->postCurl($data, $token, '/v1beta/mailcow/mailbox/edit', 'Change Password');

                if ($changeMail['httpcode'] == 200) {
                    $errorMessage = ['status' => 'success', 'message' => 'Mailbox Password and detail Save Successfully'];
                } else {
                    $errorMsg = $changeMail['result']->desc;
                    $errorMessage = ['status' => 'error', 'message' => $errorMsg];
                }
            }

            if (isset($_POST['formAction']) && $_POST['formAction'] == 'editMailbox' && isset($_POST['editMailId']) && isset($_POST['edit_password'])  && isset($_POST['edit_full_name'])) {

                echo ('<pre>');
                print_r($_POST);
                die();
            }
        } else {
            $errorMessage = ['status' => 'error', 'message' => 'Domain are not active'];
        }

        return array(
            'templatefile' => 'templates/manageDomain.tpl',
            'vars' => array(
                'domainDetail' => $domainDetail,
                'errorMessage' => $errorMessage,
                'countMailBox' => Capsule::table('mod_email_solution_product')->where('service_id', $params['serviceid'])->value('mailcow'),
                'getAlias' => $getAlias,
                'getMailBox' => $getMailBox,
                'customDNSRecord' => preg_split('/\r\n|\r|\n/', $params['configoption4'])
            ),
        );
    } catch (Exception $e) {
        logModuleCall('Email Solution', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
    }
}
