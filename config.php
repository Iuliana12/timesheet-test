<?php
define("APP_PATH","/var/www/timesheets");
define("APP_URL","https://timesheets.newro.co");
define("FINANCIAL_YEAR_CHANGE_DATE","xxxx-04-01");
//this is a manual random generated number that will pe appended to all the session variables that will be used
//this way a XSRF attacker won't be able to guess the session vars name to access them
define("KEY","9hJt3iI45Ij6");
define("DB_PHPTYPE", 'pgsql');
define("DB_PROTOCOL",'tcp');
define("DB_DATABASE",'timesheetsystem');
define("DB_USERNAME",'timesheetwebentry');
define("DB_PASSWORD",'sh32sdkj');
//define("DB_USERNAME",'postgres');
//define("DB_PASSWORD",'WE@Eskl14#@');
define("DB_HOST",'localhost');
define("DB_PORT",5432);
define("CAS_SERVER","cas.newro.co/cas");
define("CAS_PORT",443);
define("EMAIL_SENDER",'timesheets@timesheets.newro.co');
define("EMAIL_ADMIN",'lucian@newro.co');


define("HOURS_PER_DAY",8);
define("TOIL_EXPIRATION_DAYS",95);
if(!defined("DB_DEBUG")) {define("DB_DEBUG",0);}
?>
