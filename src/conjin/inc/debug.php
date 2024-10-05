<?
    ///////////////////////////////
    // Arteficts, objects, files //
    ///////////////////////////////

    $build_artifacts = [
        'htaccess'    => '.htaccess',
        'config-json' => 'config.json',
        'users-json'  => 'users.json',
    ];

    $preprocess_objs = [
        'target_root',
        'syslet_login',
        'syslet_not_found',
        'groups_2_userlist_ser',
        'groups_2_openidmarkerlist',
        'nav',
    ];

    $store_files = null;
    if (isset($GLOBALS['core_path_store'])) {
        $store_files = [];
        foreach (glob(path_store('*')) as $file) {
            $store_files[] = basename($file);
        }
    }


    //////////
    // Main //
    //////////

    if (!isset($_GET['what'])) {
        echo '<html><body><h1>What to debug?</h1><h2>System info</h2><ul>';
        echo '<li><a href="?what=mime-types">?what=mime-types</a></li>';
        echo '<li><a href="?what=phpinfo">?what=phpinfo</a></li>';
        echo '</ul><h2>Build artifacts</h2><table><tr><td>File</td><td>Size</td></tr>';

        foreach ($build_artifacts as $what => $file) {
            // Determine file size
            $file_path = '../' . $file;
            $file_size = file_exists($file_path) ? round(filesize($file_path) / 1024, 2) . ' KB' : 'N/A';

            echo '<tr><td><a href="?what=' . $what . '">' . $file . '</a></td><td>' . $file_size . '</td></tr>';
        }

        echo '</table><h2>Preprocess objects</h2><em>(Rendered as JSON)</em><table><tr><td>Name</td><td>Size</td></tr>';

        foreach ($preprocess_objs as $name) {
            // Determine file size
            $file_path = path_preprocess('obj/' . $name);
            $file_size = file_exists($file_path) ? round(filesize($file_path) / 1024, 2) . ' KB' : 'N/A';

            echo '<tr><td><a href="?what=preprocess-obj&name=' . $name . '">' . $name . '</a></td><td>' . $file_size . '</td></tr>';
        }
        
        echo '</table>';
        
        if ($store_files !== null) {    
            echo '<h2>Store files</h2><table><tr><td>Name</td><td>Size</td></tr>';

            foreach ($store_files as $file_name) {
                $file_size = round(filesize(path_store($file_name)) / 1024, 2) . ' KB';

                echo '<tr><td><a href="?what=store-file&name=' . urlencode($file_name) . '">' . $file_name . '</a></td><td>' . $file_size . '</td></tr>';
            }

            echo '</table>';
        }

        echo '</body></html>';

        exit();
    }
    else {
        if ($_GET['what'] == 'preprocess-obj' && !isset($_GET['name'])) {
            echo "Please specify name via ?name=...\n";
            exit();
        }
    }

    $what = $_GET['what'];

    if ($what == 'phpinfo') {
        phpinfo();
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
    elseif ($what == 'htaccess') {
        header('Content-Type: text/plain');
        readfile('../.htaccess');
    }
    elseif ($what == 'config-json') {
        header('Content-Type: application/json');
        readfile('../config.json');
    }
    elseif ($what == 'users-json') {
        header('Content-Type: application/json');
        readfile('../users.json');
    }
    elseif ($what == 'preprocess-obj' && in_array($_GET['name'], $preprocess_objs)) {
        // Special treatment for 'nav'
        if ($_GET['name'] == 'nav') {

            // Load classes -- hacky, but ok for debugging purposes
            include('../modules-shared/nav-common/datatypes.php');
            $obj = core_load_obj('nav');
            header('Content-Type: application/json');
            debug_aux_print_json($obj);
        }
        else {
            header('Content-Type: application/json');
            debug_aux_print_json(core_load_obj($_GET['name']));
        }
    }
    elseif ($what == 'store-file' && isset($_GET['name']) && in_array($_GET['name'], $store_files)) {
        readfile(path_store($_GET['name']));
    }
    else {
        echo 'not found';
    }


    /////////////
    // Helpers //
    /////////////

    function debug_aux_print_json($obj) {
        $json = json_encode($obj);
        if ($json === false) {
            echo 'Error during `json_encode`: ' . json_last_error_msg();
        }
        else {
            echo $json;
        }
    }
?>