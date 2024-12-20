<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
    header("Location: ./login.php");
    die();
}
if(isset($_SESSION['login']['level'])) {
    $user_level = $_SESSION['login']['level'];
} else {
    die('Error with user permissions');
}
if(isset($_SESSION['login']['user'])) {
    $user_name = $_SESSION['login']['user'];
} else {
    die('Error with user name');
}

//Set the base directory the factorio servers will be stored
$base_dir="/var/www/factorio/";
include('./getserver.php');
if(!isset($server_select)) {
    die('Error s'.__LINE__.': In server selection files.php');
}
session_write_close();

//Get the max upload size in megabytes and bytes for use later on
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last)
    {
        case 'g':
        $val *= 1024;
        case 'm':
        $val *= 1024;
        case 'k':
        $val *= 1024;
    }
    return $val;
}

function sendErrorResponse($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit();
}

$upload_max_filesize_m = ini_get('upload_max_filesize');
$upload_max_filesize_b = return_bytes($upload_max_filesize_m);

if(isset($_REQUEST['archive'])) {
    sendErrorResponse('this feature not ready yet.');
    try
    {
        $a = new PharData('archive.tar');
        
        // ADD FILES TO archive.tar FILE
        $a->addFile('data.xls');
        $a->addFile('index.php');
        
        // COMPRESS archive.tar FILE. COMPRESSED FILE WILL BE archive.tar.gz
        $a->compress(Phar::GZ);
        
        // NOTE THAT BOTH FILES WILL EXISTS. SO IF YOU WANT YOU CAN UNLINK archive.tar
        unlink('archive.tar');
    } 
    catch (Exception $e)
    {
        echo "Exception : " . $e;
    }
    die();
} elseif(isset($_REQUEST['download'])) {
    if($user_level=="viewonly") {
        sendErrorResponse('You have view only access.\nVisit our archive for file downloads\nwww.3ragaming.com/archive/factorio', 403);
    }
    if(empty($_REQUEST['download']))
    {
        sendErrorResponse('empty download request');
    }
    //file download requested.

    // file download found on http://www.media-division.com/php-download-script-with-resume-option/
    // get the file request, throw error if nothing supplied

    // hide notices
    @ini_set('error_reporting', E_ALL & ~ E_NOTICE);

    //- turn off compression on the server
    // if apache
    // this seems to return true even when using nginx as the server
    //if(function_exists( apache_setenv )) {
    //    @apache_setenv('no-gzip', 1);
    //}
    @ini_set('zlib.output_compression', 'Off');

    // sanitize the file request, keep just the name and extension
    $file_path = $_REQUEST['download'];
    $path_parts = pathinfo($file_path);
    $file_name  = $path_parts['basename'];
    $file_ext   = $path_parts['extension'];
    $file_path  = $base_dir . $server_select . "/saves/" . $file_name;
    // allow a file to be streamed instead of sent as an attachment
    $is_attachment = isset($_REQUEST['stream']) ? false : true;
    // make sure the file exists
    if (is_file($file_path))
    {
        $file_size  = filesize($file_path);
        $file = @fopen($file_path,"rb");
        if ($file)
        {
            // set the headers, prevent caching
            header("Pragma: public");
            header("Expires: -1");
            header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
            header("Content-Disposition: attachment; filename=\"$file_name\"");
     
            // set appropriate headers for attachment or streamed file
            if ($is_attachment)
                    header("Content-Disposition: attachment; filename=\"$file_name\"");
            else
                    header('Content-Disposition: inline;');
     
            // set the mime type based on extension, add yours if needed.
            $ctype_default = "application/octet-stream";
            $content_types = array(
                    "exe" => "application/octet-stream",
                    "zip" => "application/zip",
                    "tar.gz" => "application/tar+gzip"
                    );
            $ctype = $content_types[$file_ext] ?? $ctype_default;
            header("Content-Type: " . $ctype);
            //check if http_range is sent by browser (or download manager)
            if(isset($_SERVER['HTTP_RANGE'])) {
                list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if ($size_unit == 'bytes') {
                    //multiple ranges could be specified at the same time, but for simplicity only serve the first range
                    //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                    list($range, $extra_ranges) = explode(',', $range_orig, 2);
                } else {
                    $range = '';
                    sendErrorResponse('Requested Range Not Satisfiable', 416);
                }
            } else {
                $range = '';
            }
            //figure out download piece from range (if set)
            list($seek_start, $seek_end) = explode('-', $range, 2);
     
            //set start and end based on range (if set), else set defaults
            //also check for invalid ranges.
            $seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)),($file_size - 1));
            $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
     
            //Only send partial content header if downloading a piece of the file (IE workaround)
            if ($seek_start > 0 || $seek_end < ($file_size - 1)) {
                header('HTTP/1.1 206 Partial Content');
                header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
                header('Content-Length: '.($seek_end - $seek_start + 1));
            } else
            header("Content-Length: $file_size");
            header('Accept-Ranges: bytes');
            set_time_limit(0);
            fseek($file, $seek_start);
            while(!feof($file)) {
                print(@fread($file, 1024*8));
                ob_flush();
                flush();
                // Check the connection status. If the connection has been aborted or the script timed out
                if (connection_status()!=0) {
                    @fclose($file);
                    sendErrorResponse('Connection was lost during file download.', 500);
                }
            }
            // file save was a success
            @fclose($file);
            exit();
        } else {
            // file couldn't be opened
            sendErrorResponse('File could not be opened', 500);
        }
    } else {
        // file does not exist
        sendErrorResponse('File could not be found', 404);
    }
    /* END OF FILE DOWNLOAD */
    //no reason to continue
    exit();

} elseif(isset($_REQUEST['upload'])) {
    if($user_level=="viewonly") {
        sendErrorResponse('You have read only access.' ,403);
    } else {
        //Valdidate name
        if(isset($_FILES['file']['name'])) {
            $filename = strtolower($_FILES['file']['name']);
        } else { 
            sendErrorResponse('Error n'.__LINE__.': Invalid File');
        }

        //Validate size
        if(isset($_FILES['file']['size'])) {
            if($_FILES['file']['size']<$upload_max_filesize_b) {
                $filesize = $_FILES['file']['size'];
            } else {
                sendErrorResponse("File must be less than $upload_max_filesize_m");
            }
        } else {
            sendErrorResponse('Error s'.__LINE__.': Invalid File');
        }

        if(isset($_FILES['file']['type'])) {
            $fileType = $_FILES['file']['type'];
            if( $fileType == "application/zip" || $fileType == "application/x-zip-compressed" || ($fileType == "application/octet-stream" && pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION) == "zip") ) {
                //we good
            } else {
                sendErrorResponse($fileType.'Invalid File Type');
            }
        } else {
            sendErrorResponse('Error t'.__LINE__.': Invalid File');
        }

        if(isset($_FILES['file']['tmp_name'])) {
            $fileTmp = $_FILES['file']['tmp_name'];
            $zip = new ZipArchive();
            $res = $zip->open($fileTmp, ZipArchive::CHECKCONS);
            if ($res !== TRUE) {
                switch($res) {
                    case ZipArchive::ER_NOZIP:
                        unlink($fileTmp);
                        sendErrorResponse('Error z'.__LINE__.': Not a zip archive');
                    /*case ZipArchive::ER_INCONS :
                        unlink($fileTmp);
                        sendErrorResponse('Error z'.__LINE__.': Zip consistency check failed');//*/
                    case ZipArchive::ER_CRC :
                        unlink($fileTmp);
                        sendErrorResponse('Error z'.__LINE__.': Zip checksum failed');
                    /*default:
                        unlink($fileTmp);
                        sendErrorResponse('Error z'.__LINE__.': Zip error ' . $res);//*/
                }
            }
        } else {
            sendErrorResponse('Error t'.__LINE__.': Invalid File');
        }

        $filename = preg_replace('/\s+/', '_', $filename);
        $full_file_path = $base_dir.$server_select."/saves/".$filename;
        ////This didn't work. The fopen stream was adding strange data to the file, which would corrupt the zip archive somehow.
        //$fh = fopen('php://input','r') or sendErrorResponse("Error opening the file");
        //$blob = fgets($fh, 5);
        //if (strpos($blob, 'PK') !== false) {
            //looks like it is a zip file
        //} else {
            //fclose($fh);
            //sendErrorResponse( "invalid zip file" );
        //}
        $file_users_path = "$base_dir$server_select/saves.json";
        if(file_exists($file_users_path)) {
            $jsonString = file_get_contents($file_users_path);
            $file_list = json_decode($jsonString, true);
            $file_list_prehash = md5(serialize($file_list));
            if(isset($file_list[$filename])) {
                $session['login']['reload_report']='File "'.$filename.'" was replaced';
            }
        }
        $file_list[$filename] = $user_name;

        if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
            $move_uploaded_file = move_uploaded_file($fileTmp, $full_file_path);
            $file_list_prehash = null;
            if($move_uploaded_file == true) {
                $file_users_path = "$base_dir$server_select/saves.json";
                if(file_exists($file_users_path)) {
                    //Grab file list json and put into array
                    $jsonString = file_get_contents($file_users_path);
                    $file_list = json_decode($jsonString, true);
                    //md5 hash to check if it changes
                    $file_list_prehash = md5(serialize($file_list));
                    if(isset($file_list[$filename])) {
                        $session['login']['reload_report']='File "'.$filename.'" was replaced';
                    }
                }
                $file_list[$filename] = $user_name;
                //if hash changes, a user over writ someones previous file, or a file has been added
                if($file_list_prehash !== md5(serialize($file_list))) {
                    $newJsonString = json_encode($file_list, JSON_PRETTY_PRINT);
                    file_put_contents($file_users_path, $newJsonString);
                }
                //does echo do anything here?
                echo "complete";
            } else {
                $session['login']['reload_report']='Error u251: File failed to upload.';
            }
        } else {
            $session['login']['reload_report']='Error u254: '.$_FILES["file"]["error"];
        }
        if(isset($session['login']['reload_report'])) {
            if(!isset($_SESSION)) { session_start(); }
            $_SESSION['login']['reload_report'] = $session['login']['reload_report'];
            session_write_close();
        }
        //$pre = file_put_contents($full_file_path, $fh);
        //fwrite($fh, $pre);
        //fclose($fh);
    }
    //no reason to carry on
    exit();

} elseif(isset($_REQUEST['delete'])) {
    if($user_level=="viewonly") {
        sendErrorResponse('You have view only access.', 403);
    } else {
        if(empty($_REQUEST['delete']))
        {
            sendErrorResponse('No files selected for deletion');
        }
        //2017-01-06-10:54:26.log
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $delete_record = "";
        $server_save_loc = "$base_dir$server_select/saves/";
        $server_delete_loc = "$base_dir$server_select/logs/";
        $server_delete_path = "$base_dir$server_select/logs/file_deletion-$date.log";
        $file_users_path = "$base_dir$server_select/saves.json";
        if(file_exists($server_save_loc)) {
            if(isset($_POST['delete'])) {
                //var_dump(json_decode($_POST['delete']));
                $delete_array = json_decode($_POST['delete']);
                if ($delete_array == NULL || $delete_array === FALSE) {
                    sendErrorResponse('Error p'.__LINE__.': invalid json in post');
                }
                //set earlier $file_users_path
                if(file_exists($file_users_path)) {
                    $jsonString = file_get_contents($file_users_path);
                    $file_list = json_decode($jsonString, true);
                    $file_list_prehash = md5(serialize($file_list));
                }
                foreach($delete_array as $file) {
                    if(file_exists($server_save_loc.$file)) {
                        //echo "Will delete $server_save_loc$file\xA";
                        $tmp_file = $server_save_loc.$file;
                        if(unlink($tmp_file)) {
                            if(isset($file_list[$file])) {
                                unset($file_list[$file]);
                            }
                            $delete_record = $delete_record ."$date-$time\t".$user_name."\t$file\xA";
                        }
                        //log the delete and the user
                    }
                }
                if($delete_record != "") {
                    if (!is_dir($server_delete_loc)) {
                        // dir doesn't exist, make it
                        mkdir($server_delete_loc);
                    }
                    file_put_contents($server_delete_path, $delete_record, FILE_APPEND);
                    if(isset($file_list) && $file_list_prehash !== md5(serialize($file_list))) {
                        $newJsonString = json_encode($file_list, JSON_PRETTY_PRINT);
                        file_put_contents($file_users_path, $newJsonString);
                    }
                }
                $session['login']['reload_report'] = "Files Deleted";
                if(isset($session['login']['reload_report'])) {
                    session_start();
                    $_SESSION['login']['reload_report'] = $session['login']['reload_report'];
                    session_write_close();
                }
                sendErrorResponse('success', 200);
            } else {
                sendErrorResponse('Error p'.__LINE__.': with post information.');
            }
        } else {
            sendErrorResponse('Error p'.__LINE__.': in server path');
        }
    }
    //no reason to carry on
    exit();

} else {
    sendErrorResponse('Error u'.__LINE__.': No action requested');
}
