OpenSRS WHMCS Modules
Version 2.0.1
---------------------

README 12/6/13:

OpenSRS has developed 6 add on modules for use with WHMCS v5.3.x. To install each module (except Domains Pro), FTP to your WHMCS installation, and upload the entire folder to /modules/servers/. To install Domains Pro, please follow the README instructions included with that module. 

Please note that these modules are released as-is and open-source. Tucows/OpenSRS will continue to support and release updates to these 6 modules at http://opensrs.com/site/integration/tools/whmcs.


Requirements:
- WHMCS 5.3.x+
- PHP 5.2+
- PEAR - http://pear.php.net/
- mcrypt - http://www.php.net/manual/en/book.mcrypt.php
- getmypid() enabled
- 'TCP Out' ports 51000, 55000 and 55443 have to be open on the server for lookups and http(s) connections to OpenSRS API


###################################
Installation Instructions (v5.3.x):
####################################

PLEASE COMPLETE THE FOLLOWING BEFORE PROCEEDING:
- You must authorize your server IP in RWI before you begin. To do this visit RWI, login, scroll to the bottom, click the link  "Add IPs for script/API access" and put your IP in the address field. Note that it may take up to 1 hr for this to propagate. 


####################################
Product Configuration:
####################################

To begin using this module, login to your WHMCS installation and select Setup->Products/Services->Products/Services. Create a new group or select Create A New Product.

Product Type: Other
Product Group: <Select One>
Product Name: <Enter Product Name>

**If you want to test these modules on our test server, it will require that you generate a separate API key from the test interface.

####################################
OpenSRS SiteLock Module:
####################################

To setup OpenSRS SiteLock, add a new product and configure the 'modules settings' tab:

Username: <opensrs username>
API Key: <your opensrs API key>
Type: Basic, Premium or Enterprise

SiteLock requires a custom field called "Domain" to properly process orders.

To manage existing SiteLock purchases you need to:
1. Create an order from the WHMCS admin panel
2. Set created product to active and do not send invoice confirmations
3. Set the domain and product length details to match your previous purchase in RWI and submit order
4. When product is active you should see a field called "Order ID" under the customers Product/Services tab
5. Visit the RWI and find the SiteLock product you want to setup. Look for the Order ID field and copy that # into the WHMCS Order ID and press save changes
6. Click on save. If you see "Service Details" than everything should working fine.

*Please ensure that both domain fields match what is in the RWI, the Product/Service type must be the same (ie. basic, premium, enterprise) and set the expiry date manually to sync with RWI expiry date

###################################
Update Instructions:
####################################

Please replace all files with the ones included in this package. If you’ve made modifications to the .TPL files, you may need to reapply them again in this new version.

###################################
Email Templates:
####################################

We've included some default email templates with each module. We strongly advise that you edit these to match your brand. Select Setup->Email Templates and look under Product Messages to edit the available Welcome Emails. When setting up your products, select "Other" for welcome email. You can also set termination email templates from the Product page. 


####################################
CHANGES
####################################

Release 2.0.1
- Updated PHP Toolkit

Release 1.2
- Fixed a bug that caused the the cron script to fail out when suspending a sitelock product

Release 1.1
- Fixes an issue where the domainsync.php cron would fail
- Proper passing of custom domain name field to the internal domain name field

Release 1.0
- Updated read me file

Beta 5
- You can now add previously purchased SiteLock products to an existing account in WHMCS
- Updated README file
- Supports 5.2.2+

Beta 4
- Bug fixes

Beta 3
- Bug fixes

Beta 2
- Bug fixes

Beta 1
- Initial Release