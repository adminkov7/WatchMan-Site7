<?php
/*
Slave module: mail.php
Description:  SMA (Simple Mail Agent) for email box of the website
Version:      2.2.7
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

function wms7_mail_connection() {

  $val = get_option('wms7_main_settings');    
  $select_box = $val['mail_select'];
  $box = $val[$select_box];
  //массив папок почтового ящика
	$folder =explode(';',$box['mail_folders_alt'],-1);
	
	foreach ($folder as $key => $value) {
		$str_alt[] = substr($value, strpos($value, '}')+1);
	}
	$server = '';
	if (!isset($_REQUEST['mailbox'])) {$_REQUEST['mailbox'] = '';}
  switch ($_REQUEST['mailbox']) {
  case '' :
      $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}INBOX';
      break;  	
  case 'folder1':
      $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[0];
      break;
  case 'folder2':
      $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[1];
      break;
  case 'folder3':
      $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[2];
      break;
  case 'folder4':
      $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[3];
      break;      
  }

	$username = $box["mail_box_name"];
	$password = $box["mail_box_pwd"];
	if ($server !=='' && $username !=='' && $password !==''){
		$imap = @imap_open($server, $username, $password)or print_r(
		imap_last_error().
		"<br>server: ".$server.
		"<br>username: ".$username.
		"<br>password: ".$password);
		return $imap;
	}
}

function wms7_mail_header($msgno) {
	$imap = wms7_mail_connection();

	$headerinfo = imap_headerinfo($imap, $msgno);

	$arr['subject'] = $headerinfo->subject;
	$arr['from'] = $headerinfo->fromaddress.' ['.$headerinfo->from[0]->mailbox.'@'.$headerinfo->from[0]->host.']';
	$arr['to'] = $headerinfo->to[0]->mailbox.'@'.$headerinfo->to[0]->host;
	$pos = strpos($headerinfo->date, '+');
	if ($pos) {$headerinfo->date = substr($headerinfo->date, 0, $pos);}		
	$arr['date'] = $headerinfo->date;

	imap_close($imap);

	return($arr);
}

function wms7_mail_body($msgno) {
	$imap = wms7_mail_connection();
	//get $boundary
	$structure = imap_fetchstructure($imap, $msgno);

	$bound_main = get_object_vars($structure);
	$bound_main = (get_object_vars($bound_main['parameters'][0]));

	$bound_alt = get_object_vars($structure);
	$bound_alt = ($bound_alt['parts']) ? get_object_vars($bound_alt['parts'][0]) : NULL;
	$bound_alt = ($bound_alt) ? get_object_vars($bound_alt['parameters'][0]) : NULL;

	if ($bound_alt['attribute'] == 'BOUNDARY' && $bound_alt['value']){
	 		$boundary = $bound_alt['value'];
	 	}else{
			$boundary = $bound_main['value'];
	}
	//return parts of body mail
	$parts = array();
	$parts = wms7_mail_parts($structure, $parts);
	if ($structure->type == 1) {
			$mail_body = imap_fetchbody($imap, $msgno, '1');	
			if ($boundary) {$mail_body = wms7_getPlain($mail_body, $boundary);}				
			// Get attach
			$i = 1;
			foreach ($parts as $part){
				// Not text or multipart
				if ($part['type'] > 1) {
					$file = imap_fetchbody($imap, $msgno, $i);
					$mail[] = array(
					'filename' => imap_utf8($part['params'][0]['val']),
					'size'     => $part['bytes']);
					$item = get_option('wms7_main_settings');
      		$path_tmp = $item["mail_box_tmp"].'/';
					file_put_contents($_SERVER['DOCUMENT_ROOT'].$path_tmp.iconv("utf-8", "cp1251", imap_utf8($part['params'][0]['val'])), base64_decode($file));
				}
				if ($part['subtype'] !== 'HTML') {$i++;}
			}
		}else{
			$mail_body = imap_body($imap, $msgno);
			if ($boundary) {$mail_body = wms7_getPlain($mail_body, $boundary);}
	}
  switch ($parts[0]['encoding']) {
  case 1: //8bit	ENC8BIT
      $mail_body = imap_utf8($mail_body);
      break;
  case 3: //Base64	ENCBASE64
      $mail_body = base64_decode($mail_body);	      
      break;
  case 4: //Quoted-Printable	ENCQUOTEDPRINTABLE
      $mail_body = imap_utf8($mail_body);
      break;
  }
	$arr[0] = $mail_body;
	$arr[1] = $mail;
	imap_close($imap);

	return $arr;
}

function wms7_mailbox_selector(){

	$inbox  =$outbox =$draft  =$trash  =0;

  $val = get_option('wms7_main_settings');    
  $select_box = $val["mail_select"];
  $box = $val[$select_box];
  //массив папок почтового ящика
	$folder =explode(';',$box['mail_folders'],-1);
	if (!empty($folder)){
		foreach ($folder as $key => $value) {
			$str[] = substr($value, strpos($value, '}')+1);
		}
	}
	//массив папок почтового ящика
	$folder =explode(';',$box['mail_folders_alt'],-1);
	if (!empty($folder)){
		foreach ($folder as $key => $value) {
			$str_alt[] = substr($value, strpos($value, '}')+1);
		}
	}

	$folder1_alt = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[0];
	$folder2_alt = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[1];
	$folder3_alt = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[2];
	$folder4_alt = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}'.$str_alt[3];

	$username = $box["mail_box_name"];
	$password = $box["mail_box_pwd"];

	$str1_alt = $str2_alt = $str3_alt = $str4_alt = '';

	$str_alt[0] = str_replace(' ', '', $str_alt[0]);
  if($str_alt[0] !=='' && $username !=='' && $password !==''){
		$imap = @imap_open($folder1_alt, $username, $password);
		$check = @imap_check($imap);
		if ($check) $str1_alt = '('.$check->Nmsgs.')';
		@imap_close($imap);
	}
	$str_alt[1] = str_replace(' ', '', $str_alt[1]);
  if($str_alt[1] !=='' && $username !=='' && $password !==''){
		$imap = @imap_open($folder2_alt, $username, $password);
		$check = @imap_check($imap);
		if ($check) $str2_alt = '('.$check->Nmsgs.')';
		@imap_close($imap);
	}
	$str_alt[2] = str_replace(' ', '', $str_alt[2]);
  if($str_alt[2] !=='' && $username !=='' && $password !==''){	
		$imap = @imap_open($folder3_alt, $username, $password);
		$check = @imap_check($imap);
		if ($check) $str3_alt = '('.$check->Nmsgs.')';
		@imap_close($imap);
	}
	$str_alt[3] = str_replace(' ', '', $str_alt[3]);
  if($str_alt[3] !=='' && $username !=='' && $password !==''){	
		$imap = @imap_open($folder4_alt, $username, $password);
		$check = @imap_check($imap);
		if ($check) $str4_alt = '('.$check->Nmsgs.')';
		@imap_close($imap);
	}
	$str = "  <div style='position:relative; float:left; margin: -5px 0 10px 10px;'>
    <input class='radio' type='radio' id='folder1' name='radio_mail' value=$str[0] onclick='mailbox_selector(id)'/>
    <label for='folder1' style='color:black;'> ".$str[0]."$str1_alt</label>
    <input class='radio' type='radio' id='folder2' name='radio_mail' value=$str[1] onclick='mailbox_selector(id)'/>
    <label for='folder2' style='color:black;'> ".$str[1]."$str2_alt</label>
    <input class='radio' type='radio' id='folder3' name='radio_mail' value=$str[2] onclick='mailbox_selector(id)'/>
    <label for='folder3' style='color:black;'> ".$str[2]."$str3_alt</label>
    <input class='radio' type='radio' id='folder4' name='radio_mail' value=$str[3] onclick='mailbox_selector(id)'/>
    <label for='folder4' style='color:black;'> ".$str[3]."$str4_alt</label>
  </div>";
  

  return $str;
}

function wms7_mail_inbox() {
	$imap = wms7_mail_connection();
  if($imap) {
		$MC = imap_check($imap);
		// Получим обзор всех писем в ящике
		$result = imap_fetch_overview($imap,"1:{$MC->Nmsgs}",0);
		$i=0;
		$arr = array();
		foreach ($result as $overview) {
	    $arr[$i]['msgno'] = $overview->msgno;
			$pos = strpos($overview->date, '+');
			if ($pos) {$overview->date = substr($overview->date, 0, $pos);}	    
	    $arr[$i]['date'] = $overview->date;
	    $arr[$i]['from'] = iconv_mime_decode($overview->from, 0 , "UTF-8");
	    $arr[$i]['subject'] = iconv_mime_decode($overview->subject, 0 , "UTF-8");
	    $arr[$i]['seen'] = $overview->seen;
	    $i++;
		}
		imap_close($imap);
		$list = wms7_mail_table($arr);
  	}else{
    $list = 'Unable to open mailbox: ';
  }
  return $list;
}

function wms7_mail_table($arr){
	$url = get_option('wms7_current_url');
	$paged = get_option('wms7_current_page');

	$tbl_head = '<table class="table" style="background-color: #5B5B59;"><thead class="thead"><tr class="tr">'.
							"<th class='td' width='10%' style='cursor: pointer;'>id</th>".
							"<th class='td' width='30%' style='cursor: pointer;'>Date</th>".
							"<th class='td' width='30%' style='cursor: pointer;'>From</th>".
							"<th class='td' width='30%' style='cursor: pointer;'>Subject</th>".
							'</tr></thead>';
	$tbl_foot = '<tfoot class="tfoot"><tr class="tr">'.
							"<th class='td' width='10%' style='cursor: pointer;'>id</th>".
							"<th class='td' width='30%' style='cursor: pointer;'>Date</th>".
							"<th class='td' width='30%' style='cursor: pointer;'>From</th>".
							"<th class='td' width='30%' style='cursor: pointer;'>Subject</th>".
							'</tr></tfoot></table>';

	$tbl_body = '<tbody class="tbody" style= "height:180px;max-height:180px;width:650px;">';
	foreach($arr as $item){
		$msgno = $item['msgno'];
		$mailbox = $_REQUEST['mailbox'];
		$i = 0;
		if ($item['seen'] == 0){
				$tbl_body = $tbl_body."<tr class='tr' style='background-color: #75A3FF;'>";
			}else{
				$tbl_body = $tbl_body."<tr class='tr'>";	
		}
		foreach($item as $key=>$value){
	    switch ($i) {
	      case "0":
	      $tbl_body = $tbl_body."<td class='td' width='10%'><input type='checkbox' name=$msgno value='mail_number'>$value</td>"; break;
	      case "1":
	      $tbl_body = $tbl_body."<td class='td' width='30%'>$value</td>"; break;
	      case "2":
	      $tbl_body = $tbl_body."<td class='td' width='30%'>$value</td>"; break;
	      case "3":
	      $tbl_body = $tbl_body."<td class='td' width='30%'><a href='$url&paged=$paged&msgno=$msgno&mailbox=$mailbox'>$value</a></td>";break;
	    }
			$i++;
		}
		$tbl_body = $tbl_body."</tr>";	
	}
	$tbl_body = $tbl_body.'</tbody>';
	$tbl = $tbl_head.$tbl_body.$tbl_foot;

	return $tbl;
}

function wms7_mail_parts($object, &$parts) {
	
	if ($object->type == 1) {

		// Object is multipart
			foreach ($object->parts as $part) {
				wms7_mail_parts($part, $parts);
			}
		}else{
			$p['type'] = $object->type;
			$p['encoding'] = $object->encoding;
			$p['subtype'] = $object->subtype;
			$p['bytes'] = $object->bytes;
			if ($object->ifparameters == 1) {
				foreach ($object->parameters as $param) {
					$p['params'][] = array('attr' => $param->attribute,
					'val'  => $param->value);
				}
			}
			if ($object->ifdparameters == 1) {
				foreach ($object->dparameters as $param) {
					$p['dparams'][] = array('attr' => $param->attribute, 'val'  => $param->value);
				}
			}
			$p['disp'] = null;
			if ($object->ifdisposition == 1) {
				$p['disp'] = $object->disposition;
			}
			$parts[] = $p;
	}
	return $parts;
}

function wms7_getPlain($str, $boundary) {
$pos = strpos($boundary, '----');
if ($pos == 0){
	$boundary = substr( $boundary, 4 );
}
	$lines = explode("\n", $str);
	$plain = false;
	$res = '';
	$start = false;
	foreach ($lines as $line) {
		if (strpos($line, 'text/plain') !== false) $plain = true;
		if (strlen($line) == 1 && $plain) {
			$start = true;
			$plain = false;
			continue;
		}
		if ($start && strpos($line, 'Content-Type') !== false) $start = false;
		if ($start)	$res .= $line;
	}
	$res = substr($res, 0, strpos($res, '----ALT--'));
	$res = $res == '' ? $str : $res;

	return $res;
}

function wms7_mail_move() {
	$imap = wms7_mail_connection();
	foreach ($_POST as $key=>$value) {
		if($value == 'mail_number') {
			$uid = imap_uid($imap,$key);
			$box = $_POST['move_box'];
			$msg = imap_mail_move($imap, $uid, $box, CP_UID);
			imap_expunge($imap);
		}
	}
	imap_close($imap);
}

function wms7_mail_send() {
	global $mailer;
  $val = get_option('wms7_main_settings');    
  $select_box = $val["mail_select"];
  $box = $val[$select_box];

	if ( !is_object( $mailer ) || !is_a( $mailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$mailer = new PHPMailer( true );
	}
  $mailer->isSMTP();
  $mailer->Host = $box['smtp_server'];
  $mailer->SMTPAuth = true;
  $mailer->Username = $box['mail_box_name'];
  $mailer->Password = $box['mail_box_pwd'];
	$mailer->SMTPSecure = mb_strtolower($box['smtp_box_encryption']);
  $mailer->Port = $box['smtp_box_port'];
  $mailer->CharSet = 'UTF-8';
  $mailer->isHTML(false);
  // Дополнительные настройки…
  $mailer->From = $box['mail_box_name'];
  $blog_title = get_bloginfo('name');
	$mailer->FromName = $blog_title;
  $mailer->addAddress($_POST['mail_new_to']);
	$mailer->Subject = $_POST['mail_new_subject'];
	$mailer->Body = $_POST['mail_new_content'];
	//перемещаем во временный директорий /tmp
	$item = get_option('wms7_main_settings');
	$path_tmp = $item["mail_box_tmp"].'/';	

	if ($_FILES['mail_new_attach']['name'] !== '') {
		$file = $_SERVER['DOCUMENT_ROOT'].$path_tmp.$_FILES['mail_new_attach']['name'];
		move_uploaded_file($_FILES['mail_new_attach']['tmp_name'], $file);
		$mailer->AddAttachment($file);
	}

	if (isset($_GET['msgno'])){
		$arr = wms7_mail_body($_GET['msgno']);
		$arr_attach = $arr[1];	
		if ($arr_attach){
			$file = $_SERVER['DOCUMENT_ROOT'].$path_tmp.$arr_attach[0]['filename'];
			$mailer->AddAttachment($file);
		}
	}

	//$mailer->SMTPDebug = 2;

  //send the message, check for errors
	if ($mailer->send()) {
	    //переносим письмо в папку Отправленные
			$folder =explode(';',$box['mail_folders'],-1);
			$i=0;
			$sent = false;
			foreach ($folder as $key => $value) {
				$pos = strpos($value, 'Sent');
				if ($pos) {
						$sent = true;
						break;
					}else{
						$pos = strpos($value, 'Отправлен');
						if ($pos) {
							$sent = true;
							break;
						}
				}
				$i++;
			}
			if ($sent === FALSE) exit;
			//массив папок почтового ящика
			$folder = substr($folder[$i], strpos($folder[$i], '}')+1);			

			$imap = wms7_mail_connection();
			if (isset($_GET['msgno'])) {
				$uid = imap_uid($imap,$_GET['msgno']);
				$box = $_POST['move_box'];
				$msg = imap_mail_move($imap, $uid, $folder, CP_UID);
				imap_expunge($imap);
				imap_close($imap);
			}
		}else{
			echo "Mailer Error: ".$mailer->ErrorInfo;
	}
}

function wms7_mail_delete() {
	$imap = wms7_mail_connection();
	foreach ($_POST as $key=>$value) {
		if($value == 'mail_number') {
			//помечаем для удаления
			$uid = imap_uid($imap,$key);
			imap_delete($imap, $uid, FT_UID );
			//переносим в корзину
			if ($_POST['radio_mail'] !== 'trash'){	
					$box = 'Trash';
					$msg = imap_mail_move($imap, $uid, $box, CP_UID);
					imap_expunge($imap);
				}else{
					//удаление
					imap_expunge($imap);
			}	
		}
	}
	imap_close($imap);
}

function wms7_imap_list_decode($list) {
	$i = 0;
  foreach ($list as $value) {
  	$value_alt = $value;
		$pos = strpos($value, '&');
		$value_prev = '';
		if ($pos !== false){
				$value_prev = substr($value, 0, $pos);
				//специально для gmail
				$value_prev = str_replace('[Gmail]/', '', $value_prev);
				//
				$value = substr($value, $pos);
				$value = iconv('UTF-16BE','UTF-8',imap_utf7_decode($value));
		}
		if ($value == '') {
				unset($list[$i]);
			}else{
				$list[$i] = $value_prev.$value.'|'.$value_alt;
		}
    $i++;
  }
	return $list;
}

function wms7_mail_search() {
	$imap = wms7_mail_connection();
	$context = 'TEXT '.$_POST['mail_search_context'];
	$arr_search = imap_search($imap, $context, SE_UID, "UTF-8");
	$i=0;
	$arr = array();
	foreach ($arr_search as $item) {
		$result_overview = imap_fetch_overview($imap,$item,FT_UID);
		foreach ($result_overview as $overview) {
	    $arr[$i]['msgno'] = $overview->msgno;
			$pos = strpos($overview->date, '+');
			if ($pos) {$overview->date = substr($overview->date, 0, $pos);}	    
	    $arr[$i]['date'] = $overview->date;
	    $arr[$i]['from'] = iconv_mime_decode($overview->from, 0 , "UTF-8");
	    $arr[$i]['subject'] = iconv_mime_decode($overview->subject, 0 , "UTF-8");
	    $arr[$i]['seen'] = $overview->seen;
	    $i++;
		}
	}
	imap_close($imap);
	$list[0] = wms7_mail_table($arr);
	$list[1] = count($arr);
	return $list;
}

function wms7_mail_inbox_connection() {
	//функция с таким же названием есть в файле sse.php плагина
  $val = get_option('wms7_main_settings');    
  $select_box = $val['mail_select'];
  $box = $val[$select_box];

	if ($box["imap_server"] == '' || $box["mail_box_port"] == '' || $box["mail_box_encryption"] == '' ||
			$box["mail_box_name"] == '' || $box["mail_box_pwd"] == '') return;
		
  $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}INBOX';

  $username = $box["mail_box_name"];
  $password = $box["mail_box_pwd"];

  $imap = @imap_open($server, $username, $password);

  return $imap;
}

function wms7_mail_unseen() {
	//функция с таким же названием есть в файле sse.php плагина
  $imap = wms7_mail_inbox_connection();
  $i=0;
  if($imap) {
      $MC = imap_check($imap);
      // Получим обзор всех писем в ящике
      $result = imap_fetch_overview($imap,"1:{$MC->Nmsgs}",0);
      foreach ($result as $overview) {
          if ($overview->seen == '0'){
              $i++;
          }
      }
      imap_close($imap);
  }
  return $i;
}