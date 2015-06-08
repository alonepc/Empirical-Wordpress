<?php
/*
Plugin Name: GoogleDrive folder list
Plugin URI: http://http://wordpress.org/plugins/googledrive-folder-list/
Description: Shows a google docs from a shared folder using curl requests. Can show only links or links with preview
Author: Edush Maxim (vfrcbvrf2005@gmail.com)
Version: 2.2.2
Author URI: http://mvedush.moikrug.ru
*/
require_once("gdfl_lists.php");
require_once("gdfl_admin.php");

function gdrive_fl_main($atts){
    $html = "";
    $fid = 0;
    $title = "";
	$preview = null;
	$maxdepth = null;
	
    extract(shortcode_atts(array(
        'fid' => '',
        'title'=>'',
        'preview'=>null,
		'maxdepth'=>null
    ), $atts));

	if($preview == null){
		 $preview = get_option('gdfl_show_preview_default',0);
		 if($preview == 1) $preview = true;
		 else $preview = false;
	} else if($preview == "true"){
		$preview = true;
	} else if($preview == "false"){
		$preview = false;
	}
	
	if($maxdepth == null){
		$maxdepth = get_option('gdfl_subfolders_maxdepth',1);
	}

	$url = 'https://googledrive.com/host/'.$fid.'/';
	if($preview){
		$url = 'https://drive.google.com/folderview?id='.$fid;	
	}
	$ch = initCurl($url);
	
	if($preview){
		return gdrive_fl_renderFromFolderview($ch,$title);
	} else {
		return gdrive_fl_renderFromHost($ch,$title,false,$maxdepth);
    }
}

function initCurl($url){
	$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacerts.pem');
	return $ch;
}

function gdrive_fl_renderFromHost($ch,$title,$is_rec_call = false, $maxdepth = 1, $curdepth = 1){
    $result = curl_exec($ch);
	$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	if($code != 200){
		return "<h2 class='gdf-doclist-title'>".$title."</h2><p>Catalog is empty or inaccessable</p>";
	}
    preg_match_all('/<div class="folder-cell">(<[a|div].*?[a|div]>)<\/div>/s',$result,$matches);
    $matches = $matches[1];
	$html = "<ul class='gdf-doclist'>";
	// draw title only when recurce calls
	if(!$is_rec_call){
		$html = "<h2 class='gdf-doclist-title'>".$title."</h2>" . $html;
	} else {
		$html = "<span class='gdf-doclist-nested-title'>".$title."</span><ul class='gdf-doclist-nested'>";
	}
	// view all folder-cell elements
    for($i=0;$i<count($matches);$i++){
		if(strpos($matches[$i],'Back to parent') !== false){
			continue;
		}
		if(strpos($matches[$i],'webhosting-translucent') !== false){
			continue;
		}
        $name = $matches[$i];
        preg_match('/href="(\/host\/[\w_-]+?\/((.*)\/)*(.*?)(\.\w+?)?)"/si',$name,$singlelink);
		// 0 - href="....."
		// 1 - host/.....
		// 2 - subfolder with slash
		// 3 - subfolder name
		// 4 - file name
		// 5 - extension
		
		if(!$singlelink[5]){
			if($curdepth < $maxdepth){
				$ch1 = initCurl('https://googledrive.com' . $singlelink[1] . '/');
				$html .= "<li class='gdf-docitem-nested'>" . gdrive_fl_renderFromHost($ch1,$singlelink[4],true,$maxdepth,$curdepth+1) . "</li>";
			}
			continue;
		}
		$link = "<a href='https://googledrive.com".$singlelink[1]."'>".urldecode($singlelink[4].$singlelink[5])."</a>";
        // will filter service files
        if($singlelink[2] != "desktop"){
            $html .= "<li class='gdf-docitem'>" . $link . "</li>";
        }
    }
    return $html."</ul>";
}

function gdrive_fl_renderFromFolderview($ch,$title){
    $result = curl_exec($ch);
    preg_match('/var\sdata\s=\s(\{.*\})/mis',$result,$matches);
    $result = $matches[1];
    $result = preg_replace('/,+/mis',',',$result);
    $result = preg_replace('/\[,/mis','[',$result);
    $result = preg_replace('/\]\s,\}/mis',']}',$result);
    $result = preg_replace('/folderModel/mis','"folderModel"',$result);
    $result = preg_replace('/viewerItems/mis','"viewerItems"',$result);
    $result = preg_replace('/folderName/mis','"folderName"',$result);
    $result = preg_replace("/'/mis",'"',$result);

    $data = json_decode($result,true);
    $itemsc = count($data['folderModel']);

    $html = "<h2>".$title."</h2>";
    $html .= "<style type='text/css'>.doc-table tr td {vertical-align:top;}</style>";
    $html .= "<table class='doc-table'>";
    for($i = 0; $i<$itemsc; $i++){
        $l = $data['folderModel'][$i];
        $v = $data['viewerItems'][$i];
        $html .= "<tr><td><a href='".$l[1]."'><img style='height:50px;width:auto;' src='".$v[1]."'/></a></td><td><a href='".$l[1]."'>".$v['0']."</a></td></tr>";
    }
    $html .= "</table>";
    return $html;
}

function gdfl_compile_style() {

	$gdfl_cssStyles = getGdflStylesList();
	$compiledstyle = "<style type='text/css'>";
	foreach($gdfl_cssStyles as $style){
		$compiledstyle .= '.'.$style.'{'.get_option('gdfl_css_'.$style).'}';
	}
	$compiledstyle .= "</style>";
	return $compiledstyle;
}

function gdrive_insert_styles(){
	echo gdfl_compile_style();
}

add_shortcode('gdocs', 'gdrive_fl_main');
add_action('wp_head', 'gdrive_insert_styles');
?>