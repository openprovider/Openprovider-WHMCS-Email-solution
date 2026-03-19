<?php

use WHMCS\Database\Capsule;


add_hook('AdminProductConfigFieldsSave', 1, function ($vars) {
    try{
        $updateProductModuleValue = Capsule::table('tblproducts')->where('id', $vars['pid'])->where('servertype', 'email_solution')->first();

        $updateProductModule = $updateProductModuleValue->configoption1;
        if ($updateProductModuleValue->id) {
            $groupID = createConfigurableOption($vars['pid']);

            // if ($updateProductModule > 0) {
            //     assignConfigOption($vars['pid'], $groupID, 0);
            // } else {
            //     assignConfigOption($vars['pid'], $groupID, 1);
            // }
            logModuleCall('MailCow Email Account', __FUNCTION__, $vars, '', ['status' => 'successfully save changes ']);
        }

        if (Capsule::table('tblcustomfields')->where('fieldname', 'like', 'domain|%')->where('type','product')->where('relid' , $vars['pid'])->count() == 0) {
            
            Capsule::table('tblcustomfields')->insert([
                'type' => 'product',
                'fieldtype' => 'text',
                'relid' => $vars['pid'],
                'fieldname' => 'domain|Enter The Domain',
                'description' => '',
                'adminonly' => '',
                'required' => 'on',
                'showorder' => 'on',
                'showinvoice' => '',
                'regexpr' => '/^(?=.{4,253}$)(?!\-)([a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]\.)+[a-zA-Z]{2,}$/'
            ]);
        }


    }catch(Exception $e){
        logModuleCall('MailCow Email Account', __FUNCTION__, $vars, $e->getMessage(), ['error' => $e->getMessage()]);
    }
});


function assignConfigOption($pid, $groupid, $status)
{
    try {
        $attach =  Capsule::table('tblproductconfiglinks')->where('gid', $groupid)->where('pid', $pid)->value('id');

        if ($status == 0 && $attach > 0) {
            Capsule::table('tblproductconfiglinks')->where('gid', $groupid)->where('pid', $pid)->delete();
        } elseif ($status == 1) {
            if (!$attach > 0) {
                Capsule::table('tblproductconfiglinks')->insert([
                    'gid' => $groupid,
                    'pid' => $pid
                ]);
            }
        }
    } catch (Exception $e) {
        logModuleCall('MailCow Email Account', __FUNCTION__, '', $e->getMessage(), ['error' => $e->getMessage()]);
    }
}

function createConfigurableOption($pid)
{
    try {
        // $groupname = 'Squid Proxy-' . $pid;

        $groupname = 'email_solution';

        if (Capsule::table('tblproductconfiggroups')->where('name', $groupname)->count() == 0) {
            $groupid = Capsule::table('tblproductconfiggroups')->insertGetId(
                [
                    'name' => $groupname,
                    'description' => 'email_solution'
                ]
            );

            $productconfig = [
                'Quota' => [
                    'gid' => $groupid,
                    'optionname' => 'email|No. of email_solution',
                    'optiontype' => '4',
                    'qtyminimum' => '1',
                    'qtymaximum'  => '255',
                    'order' => '1'
                ]
            ];

            foreach ($productconfig as $key => $productconfigs) {

                if (Capsule::table('tblproductconfigoptions')->where('optiontype', $productconfigs['optiontype'])->where('gid', $productconfigs['gid'])->where('optionname', 'like', '%' . $productconfigs['optionname'] . '%')->count() == 0) {

                    $productconfigid = Capsule::table('tblproductconfigoptions')->insertGetId($productconfigs);
                    if ($productconfigid) {
                        if (Capsule::table('tblproductconfigoptionssub')->where('configid', $productconfigid)->where('optionname', 'like', '%' . $productconfigs['optionname'] . '%')->count() == 0) {
                            $productpriceid =  Capsule::table('tblproductconfigoptionssub')->insertGetId([
                                'configid' => $productconfigid,
                                'optionname' => $productconfigs['optionname'],
                                'sortorder' => '1',
                            ]);
                            // insertPriceForOptions($productpriceid);
                        }
                    }
                }
            }

            return $groupid;
        }else{
            return Capsule::table('tblproductconfiggroups')->where('name', $groupname)->value('id');
        }
    } catch (Exception $e) {
        logModuleCall('MailCow Email Account', __FUNCTION__, $pid, $e->getMessage(), ['error' => $e->getMessage()]);
    }
}

function insertPriceForOptions($subOptionId)
    {
        try {
            $currencies = Capsule::table('tblcurrencies')->get();
 
            if ($currencies->isEmpty()) {
                logActivity("No currencies found in tblcurrencies.");
                return;
            }
 
            foreach ($currencies as $currency) {
                $currId = $currency->id;
                $exists = Capsule::table('tblpricing')
                    ->where('type', 'configoptions')
                    ->where('currency', $currId)
                    ->where('relid', $subOptionId)
                    ->count() == 0;
 
                if ($exists) {
                    Capsule::table('tblpricing')->insert([
                        'type'       => 'configoptions',
                        'currency'   => $currId,
                        'relid'      => $subOptionId,
                        'msetupfee'  => 0.00,
                        'qsetupfee'  => 0.00,
                        'ssetupfee'  => 0.00,
                        'asetupfee'  => 0.00,
                        'bsetupfee'  => 0.00,
                        'tsetupfee'  => 0.00,
                        'monthly'    => 0.00,
                        'quarterly'  => 0.00,
                        'semiannually' => 0.00,
                        'annually'   => 0.00,
                        'biennially' => 0.00,
                        'triennially' => 0.00
                    ]);
                } 
            }
        } catch (Exception $e) {
        logModuleCall('MailCow Email Account', __FUNCTION__, '', $e->getMessage(), ['error' => $e->getMessage()]);
        }
    }
 


