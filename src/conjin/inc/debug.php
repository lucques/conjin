<?
    function debug_aux_print_json($obj) {
        $json = json_encode($obj);
        if ($json === false) {
            echo 'Error during `json_encode`: ' . json_last_error_msg();
        }
        else {
            echo $json;
        }
    }

    if (!isset($_GET['what'])) {
        echo "Please specify what to debug via ?what=...\n";
        exit();
    }
    else {
        if ($_GET['what'] == 'preprocess-obj' && !isset($_GET['name'])) {
            echo "Please specify name via ?name=...\n";
            exit();
        }
    }

    $what = $_GET['what'];

    if ($what == 'config-json') {
        header('Content-Type: application/json');
        debug_aux_print_json($GLOBALS['config']);
    }
    elseif ($what == 'preprocess-obj' && in_array($_GET['name'], ['target_root', 'syslet_login', 'syslet_not_found', 'groups_2_userlist', 'nav'])) {

        // Special treatment for 'nav'
        if ($_GET['name'] == 'nav') {
            // Load classes -- hacky, but ok for debugging purposes
            include('../modules-shared/template-interbook/datatypes.php');
        }

        header('Content-Type: application/json');
        debug_aux_print_json(core_load_obj($_GET['name']));
    }
    elseif ($what == 'mime-types') {
        define('APACHE_MIME_TYPES_URL','http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');

        function generateUpToDateMimeArray($url){
            $s=array();
            foreach(@explode("\n",@file_get_contents($url))as $x)
                if(isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1)
                    for($i=1;$i<$c;$i++)
                        $s[]='&nbsp;&nbsp;&nbsp;\''.$out[1][$i].'\' => \''.$out[1][0].'\'';
            return @sort($s)?'$mime_types = array(<br />'.implode(',<br />', $s).'<br />);':false;
        }

        echo generateUpToDateMimeArray(APACHE_MIME_TYPES_URL);
    }
    elseif ($what == 'phpinfo') {
        phpinfo();
    }
    else {
        echo 'not found';
    }
?>