<?php
require_once 'config.php';
require_once 'functions.php';
@session_start();
setLocales();
echo 'var messages = {';
//this requires that the PEAR package File_Gettext-0.4.1 is installed
if(file_exists('classes/Gettext.php')){
    include_once 'classes/Gettext.php';
    header("Content-type: application/json; charset=UTF-8");

    $translationFile = 'messages/'.$_SESSION['language'].'/LC_MESSAGES/timesheets.po';

    //echo $translationFile;
    $gt = new File_Gettext();
    $transalations = $gt->factory('po',$translationFile);
    $transalations->load();
    //var_dump($transalations);

    foreach($transalations->strings as $msgid => $msgstr){
        $msgid = str_replace("\\","\\\\",$msgid);
        $msgstr = str_replace("\\","\\\\",$msgstr);
        $msgid = str_replace("\"","\\\"",$msgid);
        $msgstr = str_replace("\"","\\\"",$msgstr);
        $msgid = str_replace("\n",'\n',$msgid);
        $msgstr = str_replace("\n",'\n',$msgstr);
        echo '"'.$msgid.'": "'.$msgstr.'",'."\n";
    }
    echo '"": ""'."\n";
}
?>
}
function _(string) {
if(messages[string]){
return messages[string];
}
else return string;

}