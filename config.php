<?php 

if ( ! defined( 'ABSPATH' ) ) {
    die( '<h3>Direct access to this file do not allow!</h3>' );
}

define('STATE_NAME', 'Select your State');

define("RCP_SUBSCRIPTION_LEVAL_NAME", "no subscription");

//define('STATE_NAME', get_option('state_lbl'));
define('DUMMY_FILE_URL',UL_PLUGIN_URL.'/dummy_file/dummy_empty_file.text');
define('DUMMY_FILE_NAME','Dummy File');

###  s3_DATA key  start
define("S3_DATA_PATH","/home/uslegalwww/usl_staging/staging/s3_data/");
define("SECRETKEY",'AKIAJC5MH3W65ZFFEWKQ');
define("AWSACCESSKEY",'gwNDIQBF35Di834sg3OmXONpI3V2ev+tdt4/UjOl');
define("BUCKET",'uslf-forms');

?>