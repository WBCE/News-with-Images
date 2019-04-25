<?php
/*      Drag'N'Drop Position
**/

$aJsonRespond = array();
$aJsonRespond['success'] = false;
$aJsonRespond['message'] = '';
$aJsonRespond['icon'] = '';


if(!isset($_POST['action']) || !isset($_POST['post_id']) && !isset($_POST['group_id']))
{
    $aJsonRespond['message'] = 'one of the parameters does not exist';
    exit(json_encode($aJsonRespond));
}
 else
{
    require_once('../../../config.php');
    // check if user has permissions to access the news_img module
    require_once(WB_PATH.'/framework/class.admin.php');
    $admin = new admin('Pages', 'pages_modify', false, false);
    if (!($admin->is_authenticated() && $admin->get_permission('news_img', 'module'))) {
        $aJsonRespond['message'] = 'insuficcient rights';
        exit(json_encode($aJsonRespond));
    }

    // Sanitize variables
    $action = $admin->add_slashes($_POST['action']);
    if ($action == "updatePosition")
    {
        if(isset($_POST['post_id'])){
	    $aRows = $_POST['post_id'];
            $i = count($aRows);
            foreach ($aRows as $recID) {
        	$id = $admin->checkIDKEY($recID,0,'key',true);	    
        	// now we sanitize array
        	$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts`"
        	   . " SET `position` = '".$i."'"
        	   . " WHERE `post_id` = ".intval($id)." ");
        	$i--;

            }
	}
        if(isset($_POST['group_id'])){
	    $aRows = $_POST['group_id'];
            $i = 1;
            foreach ($aRows as $recID) {
        	$id = $admin->checkIDKEY($recID,0,'key',true);	    
        	// now we sanitize array
        	$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_groups`"
        	   . " SET `position` = '".$i."'"
        	   . " WHERE `group_id` = ".intval($id)." ");
        	$i++;

            }
	}
        if($database->is_error()) {
            $aJsonRespond['success'] = false;
            $aJsonRespond['message'] = 'db query failed: '.$database->get_error();
            $aJsonRespond['icon'] = 'cancel.gif';
            exit(json_encode($aJsonRespond));
        }
    }else{
        $aJsonRespond['message'] = 'wrong arguments "$action"';
        exit(json_encode($aJsonRespond));
    }

    $aJsonRespond['icon'] = 'ajax-loader.gif';
    $aJsonRespond['message'] = 'seems everything is fine';
    $aJsonRespond['success'] = true;
    exit(json_encode($aJsonRespond));
}
