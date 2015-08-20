<?php
/**********************************************************************
 *  OpenSRS - SiteLock WHMCS module
 * *
 *
 *  CREATED BY Tucows Co       ->    http://www.opensrs.com
 *  CONTACT                    ->	 help@tucows.com
 *  Version                    -> 	 2.0.1
 *  Release Date               -> 	 07/10/14
 *
 *
 * Copyright (C) 2014 by Tucows Co/OpenSRS.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 **********************************************************************/
defined('DS') ? null : define('DS',DIRECTORY_SEPARATOR);
if(!defined('PS')) define('PS', PATH_SEPARATOR);
if(!defined('CRLF')) define('CRLF', "\r\n");
require_once dirname(__FILE__).DS.'core'.DS.'openSRS.php';

//GLOBAL
$opensrs_sitelock_language = array();

//A LITTLE HELPER
if(function_exists('mysql_safequery') == false) {
    function mysql_safequery($query,$params=false) {
        if ($params) {
            foreach ($params as &$v) { $v = mysql_real_escape_string($v); }
            $sql_query = vsprintf( str_replace("?","'%s'",$query), $params );
            $sql_query = mysql_query($sql_query);
        } else {
            $sql_query = mysql_query($query);
        }
        return ($sql_query);
    }
}

/**
 * Get product type by name
 * @param type $key
 * @return string 
 */
function opensrs_sitelock_getProductType($key = null)
{
    $certs = array
    (
        'Basic'         =>  'sitelock_basic',
        'Premium'       =>  'sitelock_premium',
        'Enterprise'    =>  'sitelock_enterprise'
    );
    
    if(isset($key))
        return $certs[$key];
    
    return $certs;
}

/**
 * Return default configuration
 * @return type 
 */
function opensrs_sitelock_configoptions()
{
    mysql_safequery('CREATE TABLE IF NOT EXISTS `opensrs_sitelock_orders`
    (
        `account_id` INT(11) NOT NULL,
        `order_id`   INT(11) NOT NULL,
        UNIQUE KEY(`account_id`)
    ) DEFAULT CHARACTER SET UTF8 ENGINE = MyISAM');
    
    //EMAIL
    $q = mysql_safequery('SELECT COUNT(*) as `count` FROM tblemailtemplates WHERE name = "OpenSRS - SiteLock Welcome Email"');
    $row = mysql_fetch_assoc($q);
    if(!mysql_num_rows($q) || !$row['count'])
    {
        mysql_safequery("INSERT INTO `tblemailtemplates` (`type` ,`name` ,`subject` ,`message` ,`fromname` ,`fromemail` ,`disabled` ,`custom` ,`language` ,`copyto` ,`plaintext` )VALUES ('product', 'OpenSRS - SiteLock Welcome Email', 'SiteLock', '<p>Dear {\$client_name},</p>
        <p>Your order for {\$service_product_name} has now been activated. Please keep this message for your records.</p>
        <p>Product/Service: {\$service_product_name}<br /> Payment Method: {\$service_payment_method}<br /> Amount: {\$service_recurring_amount}<br /> Billing Cycle: {\$service_billing_cycle}<br /> Next Due Date: {\$service_next_due_date}</p>
        <p>Thank you for choosing us.</p>
        <p>{\$signature}</p>', '', '', '', '1', '', '', '0')");
    }
    else
    {
        mysql_safequery("UPDATE tblemailtemplates SET custom = 1 WHERE name = 'OpenSRS - SiteLock Welcome Email'");
    }
    
    return array
    (
        'username'          =>  array
        (
            'FriendlyName'  =>  'Username',
            'Type'          =>  'text',
            'Size'          =>  '25'
        ),
        'apikey'            =>  array
        (
            'FriendlyName'  =>  'API Key',
            'Type'          =>  'text',
            'Size'          =>  '25'
        ),
        'test'              =>  array
        (
            'FriendlyName'  =>  'Test Mode',
            'Type'          =>  'yesno',
        ),
        'type'              =>  array
        (
            'FriendlyName'  =>  'Type',
            'Type'          =>  'dropdown',
            'Options'       =>  implode(array_keys(opensrs_sitelock_getProductType()),',')
        )
    );
}

/** 
 * Create new account form SiteLock service
 * @param type $params
 * @return type 
 */
function opensrs_sitelock_CreateAccount($params)
{
    $domain = $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain'];
    $period = 1;
    $type = $params['configoption4'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'sw_register',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'domain'                =>  $domain,
            'contact_set'           =>  array
            (
                'admin'             =>  array
                (
                    'first_name'    =>  $params['clientsdetails']['firstname'],
                    'last_name'     =>  $params['clientsdetails']['lastname'],
                    'org_name'      =>  $params['clientsdetails']['companyname'],
                    'address1'      =>  $params['clientsdetails']['address1'],
                    'address2'      =>  $params['clientsdetails']['address2'],
                    'city'          =>  $params['clientsdetails']['city'],
                    'state'         =>  $params['clientsdetails']['state'],
                    'postal_code'   =>  $params['clientsdetails']['postcode'],
                    'country'       =>  $params['clientsdetails']['country'],
                    'phone'         =>  $params['clientsdetails']['phonenumber'],
                    'fax'           =>  $params['clientsdetails']['fax'],
                    'email'         =>  $params['clientsdetails']['email']
                )
            ), 
            'handle'                =>  'process',
            'period'                =>  $period,
            'product_type'          =>  opensrs_sitelock_getProductType($type),
            'reg_type'              =>  'new'
        )
    );
    
    $r = $openSRS->send($send);
    
    if($openSRS->isSuccess())
    {    
        mysql_safequery('REPLACE INTO opensrs_sitelock_orders SET `account_id` = ?, `order_id` = ? ', array(
            $params['accountid'],
            $r['attributes']['order_id']
        ));
        
        mysql_safequery("UPDATE tblhosting SET username = '', password = '', domain = ? WHERE id = ?", array($domain, $params['serviceid']));
        return 'success';
    }
    return opensrs_sitelock_translate($openSRS->getError());
}

/**
 * Terminate account
 * @param type $params
 * @return type 
 */
function opensrs_sitelock_terminateaccount($params)
{
    $q = mysql_safequery('SELECT order_id FROM opensrs_sitelock_orders WHERE account_id = ?', array($params['accountid']));
    $row = mysql_fetch_assoc($q);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'cancel_order',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'order_id'  =>  $row['order_id'],
        )
    );
    $r = $openSRS->send($send);
    
    if($openSRS->isSuccess())
       return 'success';
    
    return opensrs_sitelock_translate($openSRS->getError());
}


/**
 * Display login button in client area
 * @return string 
 */
function opensrs_sitelock_ClientAreaCustomButtonArray() 
{
    $buttonarray = array
    (
        opensrs_sitelock_translate('manage_service') => 'login',
        opensrs_sitelock_translate('on_demand_scan') =>  'scan'
    );
    return $buttonarray;
}

/**
 * Redirect user to SiteLock configuration page
 * @param type $params
 * @return type 
 */
function opensrs_sitelock_login($params)
{
    $q = mysql_safequery('SELECT order_id FROM opensrs_sitelock_orders WHERE account_id = ?', array($params['accountid']));
    $row = mysql_fetch_assoc($q);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'create_token',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'order_id'  =>  $row['order_id'],
        )
    );
    $r = $openSRS->send($send);
    
    if($openSRS->isSuccess())
    {
        $url = $r['attributes']['login_url'];
        header('Location: '.$url);
        die();
    }
    
    return opensrs_sitelock_translate($openSRS->getError());
}

/**
 * Display data in client area
 * @param type $params
 * @return string 
 */
function opensrs_sitelock_ClientArea($params)
{
    $code = '';
    $code .= '<div><button style="margin: 0 5px 0 5px" class="btn" onclick="window.open(\'clientarea.php?action=productdetails&id='.$params['accountid'].'&modop=custom&a=login\', \'_blank\')" ><img class="manage_img" src="modules/servers/opensrs_sitelock/img/keys.png"/>'.opensrs_sitelock_translate('manage_service').'</button>';
    $code .= '<button style="margin: 0 5px 0 5px" class="btn" onclick="window.location=\'clientarea.php?action=productdetails&id='.$params['accountid'].'&modop=custom&a=scan\'" ><img class="manage_img" src="modules/servers/opensrs_sitelock/img/subdomains.png"/>'.opensrs_sitelock_translate('on_demand_scan').'</button></div>';
    return $code;
} 

/**
 * Display some buttons in admin area
 * @return type 
 */
function opensrs_sitelock_admincustombuttonarray()
{
    return array
    (
        opensrs_sitelock_translate('renew') =>  'Renew'
    );
}

/**
 * Display some info about our service
 * @param type $params
 * @return string 
 */
function opensrs_sitelock_adminservicestabfields($params)
{
    $q = mysql_safequery('SELECT order_id FROM opensrs_sitelock_orders WHERE account_id = ?', array($params['accountid']));
    
    $row = mysql_fetch_assoc($q);
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'get_order_info',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'order_id'  =>  $row['order_id'],
        )
    );
    $r = $openSRS->send($send);
    
    if($openSRS->isSuccess())
    {
        $fieldsarray = array
        (
            '<b>Service details</b>'    =>  '<div id="modrenew" title="'.opensrs_sitelock_translate('renew_title').'" style="display:none;">
                                                <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 40px 0;"></span>'.opensrs_sitelock_translate('renew_question').'</p>
                                            </div>
                                            <script type="text/javascript">
                                                $(function(){
                                                    $(".button [value='.opensrs_sitelock_translate('renew').']");
                                                        
                                                    $(".button [value='.opensrs_sitelock_translate('renew').']").click(function(event){
                                                        event.preventDefault();
                                                        $("#modrenew").dialog({
                                                        autoOpen: true,
                                                        resizable: false,
                                                        width: 450,
                                                        modal: true,
                                                            buttons: {"Yes": function() {
                                                                       window.location="clientshosting.php?userid='.$_REQUEST['userid'].'&id='.$_REQUEST['id'].'&modop=custom&ac=Renew";
                                                                    },"No": function() {
                                                                        $(this).dialog("close");
                                                                    }}
                                                        });

                                                    });
                                                });
                                            </script>
                                            <div style="background-color: #fff">
                                              <table>
                                                <tr>
                                                    <td style="width: 150px; padding: 3px 10px 3px 0; text-align: right;"><b>'.opensrs_sitelock_translate('status').'</b></td>
                                                    <td>'.$r['attributes']['state'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 150px; padding: 3px 10px 3px 0; text-align: right;"><b>'.opensrs_sitelock_translate('registration_period').'</b></td>
                                                    <td>'.$r['attributes']['period'].'</td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 150px; padding: 3px 10px 3px 0; text-align: right;"><b>'.opensrs_sitelock_translate('product_type').'</b></td>
                                                    <td>'.$r['attributes']['product_type'].'</td>
                                                </tr>
                                              </table>
                                          </div>',
            '<b>Order ID</b>'   =>  '<input type="text" name="sitelock_order_id" value="'.$row['order_id'].'" />',
        );
        
        return $fieldsarray;
    }
    else
    {
        return array
        (
            '<b>Order ID</b>'   =>  '<input type="text" name="sitelock_order_id" value="'.$row['order_id'].'" />',
        );
    }
}

function opensrs_sitelock_AdminServicesTabFieldsSave($params)
{
    mysql_safequery('REPLACE INTO opensrs_sitelock_orders SET `account_id` = ?, `order_id` = ? ', array(
        $params['accountid'],
        $_REQUEST['sitelock_order_id']
    ));
    
    //Update Domain Name
    $domain = $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain'];
    mysql_safequery("UPDATE tblhosting SET username = '', password = '', domain = ? WHERE id = ?", array(
        $domain, 
        $params['serviceid']
    ));
    
}

/**
 * Renew SiteLock Service
 * @param type $params
 * @return type 
 */
function opensrs_sitelock_Renew($params)
{
    $q = mysql_safequery('SELECT order_id FROM opensrs_sitelock_orders WHERE account_id = ?', array($params['accountid']));
    $row = mysql_fetch_assoc($q);
    $order_id = $row['order_id'];
 
    $domain = $params['customfields']['Domain'] ? $params['customfields']['Domain'] : $params['domain'];
    $period = 1;
    $type = $params['configoption4'];
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'sw_register',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'domain'                =>  $domain,
            'order_id'              =>  $order_id,
            'contact_set'           =>  array
            (
                'admin'             =>  array
                (
                    'first_name'    =>  $params['clientsdetails']['firstname'],
                    'last_name'     =>  $params['clientsdetails']['lastname'],
                    'org_name'      =>  $params['clientsdetails']['companyname'],
                    'address1'      =>  $params['clientsdetails']['address1'],
                    'address2'      =>  $params['clientsdetails']['address2'],
                    'city'          =>  $params['clientsdetails']['city'],
                    'state'         =>  $params['clientsdetails']['state'],
                    'postal_code'   =>  $params['clientsdetails']['postcode'],
                    'country'       =>  $params['clientsdetails']['country'],
                    'phone'         =>  $params['clientsdetails']['phonenumber'],
                    'fax'           =>  $params['clientsdetails']['fax'],
                    'email'         =>  $params['clientsdetails']['email']
                )
            ),
            'handle'                =>  'process',
            'period'                =>  $period,
            'product_type'          =>  opensrs_sitelock_getProductType($type),
            'reg_type'              =>  'renew'
        )
    );
    
    $r = $openSRS->send($send);
    if($openSRS->isSuccess())
    {
        mysql_safequery('REPLACE INTO opensrs_sitelock_orders SET `account_id` = ?, `order_id` = ? ', array(
            $params['accountid'],
            $r['attributes']['order_id']
        ));
       return 'success';
    }
    return opensrs_sitelock_translate($openSRS->getError());  
}


/**
 * Change package
 * @param type $params
 * @return type 
 */
function opensrs_sitelock_ChangePackage($params)
{
    $q = mysql_safequery('SELECT order_id FROM opensrs_sitelock_orders WHERE account_id = ?', array($params['accountid']));
    $row = mysql_fetch_assoc($q);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    
    $send = array
    (
        'action'        =>  'sw_register',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'order_id'              =>  $row['order_id'],
            'handle'                =>  'process',
            'product_type'          =>  opensrs_sitelock_getProductType($params['configoption4']),
            'reg_type'              =>  'upgrade'
        )
    );
    
    
    $r = $openSRS->send($send);

    /////////////
    if($openSRS->isSuccess())
    {
        mysql_safequery('REPLACE INTO opensrs_sitelock_orders SET `account_id` = ?, `order_id` = ? ', array(
            $params['accountid'],
            $r['attributes']['order_id']
        ));
        
        return 'success';
    }
    return opensrs_sitelock_translate($openSRS->getError());
}

/**
 * Dummy function for suspend
 * @return string
 */
function opensrs_sitelock_SuspendAccount()
{
    return "success";
}

/** 
 * Dummy function for unsuspend
 * @return string
 */
function opensrs_sitelock_UnsuspendAccount ()
{
    return "success";
}

/**
 * User clicked on_demand_scan so we will send request to OpenSRS
 * @param type $params
 * @return type 
 */
function opensrs_sitelock_scan($params)
{
    $q = mysql_safequery('SELECT order_id FROM opensrs_sitelock_orders WHERE account_id = ?', array($params['accountid']));
    $row = mysql_fetch_assoc($q);
    
    $openSRS = new OpenSRS($params['configoption1'], 0, $params['configoption2'], $params['configoption3'] == 'on' ? 0 : 1);
    $send = array
    (
        'action'        =>  'request_on_demand_scan',
        'object'        =>  'trust_service',
        'attributes'    =>  array
        (
            'order_id'  =>  $row['order_id'],
        )
    );
    $r = $openSRS->send($send);
    
    if($openSRS->isSuccess())
    {
        return true; 
    }
    
    return opensrs_sitelock_translate($openSRS->getError());
}
/**
 * Load language
 * @global array $opensrs_truste_language
 * @return array 
 */
function opensrs_sitelock_loadLanguage()
{
    GLOBAL $opensrs_sitelock_language;
    if($opensrs_sitelock_language)
        return $opensrs_sitelock_language;
    
    $language = null;
    if(isset($_SESSION['Language'])) // GET LANG FROM SESSION
    { 
        $language = strtolower($_SESSION['Language']);
    }
    else
    {
        $q = mysql_safequery("SELECT language FROM tblclients WHERE id = ?", array($_SESSION['uid']));
        $row = mysql_fetch_assoc($q); 
        if($row['language'])
            $language = $row['language'];
    }
    
    if(!$language) //Ouuuh?
    {
        $q = mysql_safequery("SELECT value FROM tblconfiguration WHERE setting = 'Language' LIMIT 1");
        $row = mysql_fetch_assoc($q);
        $language = $row['language'];
    }
    $langfilename = dirname(__FILE__).DS.'lang'.DS.$language.'.php';
    $deflangfilename = dirname(__FILE__).DS.'lang'.DS.'english.php';
    if(file_exists($langfilename)) 
        include($langfilename);
    else
        include($deflangfilename);
    
    $opensrs_sitelock_language = $_LANG;
    
    return $_LANG;
}

/**
 * Translate one word to another
 * @param type $key
 * @return type 
 */
function opensrs_sitelock_translate($key)
{
    $_LANG = opensrs_sitelock_loadLanguage();
    
    if(isset($_LANG[$key]))
        return $_LANG[$key];
    
    return $key;
}