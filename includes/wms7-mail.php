<?php
/**
 * Description: SMA (Simple Mail Agent) for email box of the website.
 *
 * @category    wms7-mail.php
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Used for mail box connection.
 *
 * @return object.
 */
function wms7_mail_connection() {

	$val        = get_option( 'wms7_main_settings' );
	$select_box = $val['mail_select'];
	$box        = $val[ $select_box ];
	// mailbox folder array.
	$folder = explode( ';', $box['mail_folders_alt'], -1 );

	foreach ( $folder as $key => $value ) {
		$str_alt[] = substr( $value, strpos( $value, '}' ) + 1 );
	}
	$server   = '';
	$_mailbox = filter_input( INPUT_GET, 'mailbox', FILTER_SANITIZE_STRING );
	if ( ! $_mailbox ) {
		$_REQUEST['mailbox'] = '';
	}
	switch ( $_mailbox ) {
		case '':
			$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}INBOX';
			break;
		case 'folder1':
			$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[0];
			break;
		case 'folder2':
			$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[1];
			break;
		case 'folder3':
			$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[2];
			break;
		case 'folder4':
			$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[3];
			break;
	}

	$username = $box['mail_box_name'];
	$password = $box['mail_box_pwd'];
	if ( '' !== $server && '' !== $username && '' !== $password ) {
		try {
			$imap = imap_open( $server, $username, $password );
		} catch ( Exception $e ) {
			$imap =
			$e->getMessage() .
			'<br>server: ' . $server .
			'<br>username: ' . $username .
			'<br>password: ' . $password;
		}
		return $imap;
	}
}
/**
 * Used for used to get the header of e-mail.
 *
 * @param string $msgno Number of e-mail into mailbox.
 * @return array.
 */
function wms7_mail_header( $msgno ) {
	$imap = wms7_mail_connection();

	$headerinfo = imap_headerinfo( $imap, $msgno );

	$arr['subject'] = $headerinfo->subject;
	$arr['from']    = $headerinfo->fromaddress;
	$arr['to']      = $headerinfo->to[0]->mailbox . '@' . $headerinfo->to[0]->host;
	$pos            = strpos( $headerinfo->date, '+' );
	if ( $pos ) {
		$headerinfo->date = substr( $headerinfo->date, 0, $pos );
	}
	$arr['date'] = $headerinfo->date;

	imap_close( $imap );

	return( $arr );
}
/**
 * Used for get the body of e-mail.
 *
 * @param string $msgno Number of e-mail into mailbox.
 * @return array.
 */
function wms7_mail_body( $msgno ) {
	WP_Filesystem();
	global $wp_filesystem;

	$_document_root = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	$imap           = wms7_mail_connection();
	// get $boundary.
	$structure = imap_fetchstructure( $imap, $msgno );

	$bound_main = get_object_vars( $structure );
	$bound_main = ( get_object_vars( $bound_main['parameters'][0] ) );

	$bound_alt = get_object_vars( $structure );
	$bound_alt = isset( $bound_alt['parts'] ) ? get_object_vars( $bound_alt['parts'][0] ) : null;
	$bound_alt = isset( $bound_alt ) ? get_object_vars( $bound_alt['parameters'][0] ) : null;

	if ( 'BOUNDARY' === strtoupper( $bound_alt['attribute'] ) && $bound_alt['value'] ) {
			$boundary = $bound_alt['value'];
	} else {
		$boundary = $bound_main['value'];
	}
	// return parts of body mail.
	$parts       = array();
	$file_attach = array();
	$parts       = wms7_mail_parts( $structure, $parts );
	if ( 1 === $structure->type ) {
			$mail_body = imap_fetchbody( $imap, $msgno, '1' );
		if ( $boundary ) {
			$mail_body = wms7_get_plain( $mail_body, $boundary );}
			// Get attach.
			$i = 1;
		foreach ( $parts as $part ) {
			// Not text or multipart.
			if ( $part['type'] > 1 ) {
				$file          = imap_fetchbody( $imap, $msgno, $i );
				$file_attach[] = array(
					'filename' => imap_utf8( $part['params'][0]['val'] ),
					'size'     => $part['bytes'],
				);
				$item          = get_option( 'wms7_main_settings' );
				$path_tmp      = $item['mail_box_tmp'] . '/';
				$wp_filesystem->put_contents(
					$_document_root . $path_tmp . iconv( 'utf-8', 'cp1251', imap_utf8( $part['params'][0]['val'] ) ),
					base64_decode( $file ),
					FS_CHMOD_FILE // predefined mode settings for WP files.
				);
			}
			if ( 'HTML' !== $part['subtype'] ) {
				$i++;
			}
		}
	} else {
		$mail_body = imap_body( $imap, $msgno );
	}
	switch ( $parts[0]['encoding'] ) {
		case 1: // 8bit  ENC8BIT.
			$mail_body = imap_utf8( $mail_body );
			break;
		case 3: // Base64    ENCBASE64.
			$mail_body = base64_decode( $mail_body );
			break;
		case 4: // Quoted-Printable  ENCQUOTEDPRINTABLE.
			$mail_body = quoted_printable_decode( $mail_body );
			break;
	}
	$arr[0] = $mail_body;
	$arr[1] = $file_attach;
	imap_close( $imap );

	return $arr;
}
/**
 * Return Name of mail folder.
 *
 * @param string $folder_num Number of folder mail box.
 */
function wms7_mail_folder_name( $folder_num ) {
	$folders     = wms7_mailbox_selector();
	$folder_num  = substr( $folder_num, -1 ) - 1;
	$name_folder = $folders[ $folder_num ]['name'];

	return $name_folder;
}
/**
 * Used for get the mailbox selector.
 *
 * @return array.
 */
function wms7_mailbox_selector() {
	$inbox  = 0;
	$outbox = 0;
	$draft  = 0;
	$trash  = 0;

	$val        = get_option( 'wms7_main_settings' );
	$select_box = $val['mail_select'];
	$box        = $val[ $select_box ];
	// mailbox folder array.
	$folder = explode( ';', $box['mail_folders'], -1 );
	if ( ! empty( $folder ) ) {
		foreach ( $folder as $key => $value ) {
			$folders[]['name'] = substr( $value, strpos( $value, '}' ) + 1 );
		}
	}
	// mailbox folder array.
	$folder = explode( ';', $box['mail_folders_alt'], -1 );
	if ( ! empty( $folder ) ) {
		foreach ( $folder as $key => $value ) {
			$str_alt[] = substr( $value, strpos( $value, '}' ) + 1 );
		}
	}
	$folder0_alt = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[0];
	$folder1_alt = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[1];
	$folder2_alt = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[2];
	$folder3_alt = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $str_alt[3];

	$username = $box['mail_box_name'];
	$password = $box['mail_box_pwd'];

	if ( '' !== $folder0_alt && '' !== $username && '' !== $password ) {
		try {
			$imap  = imap_open( $folder0_alt, $username, $password );
			$check = imap_check( $imap );
			imap_close( $imap );
		} catch ( Exception $e ) {
			$folders[0]['count'] = 'Error';
		}
		if ( $check ) {
			$folders[0]['count'] = '(' . $check->Nmsgs . ')';
		}
	}
	if ( '' !== $folder1_alt && '' !== $username && '' !== $password ) {
		try {
			$imap  = imap_open( $folder1_alt, $username, $password );
			$check = imap_check( $imap );
			imap_close( $imap );
		} catch ( Exception $e ) {
			$folders[1]['count'] = 'Error';
		}
		if ( $check ) {
			$folders[1]['count'] = '(' . $check->Nmsgs . ')';
		}
	}
	if ( '' !== $folder2_alt && '' !== $username && '' !== $password ) {
		try {
			$imap  = imap_open( $folder2_alt, $username, $password );
			$check = imap_check( $imap );
			imap_close( $imap );
		} catch ( Exception $e ) {
			$folders[2]['count'] = 'Error';
		}
		if ( $check ) {
			$folders[2]['count'] = '(' . $check->Nmsgs . ')';
		}
	}
	if ( '' !== $folder3_alt && '' !== $username && '' !== $password ) {
		try {
			$imap  = imap_open( $folder3_alt, $username, $password );
			$check = imap_check( $imap );
			imap_close( $imap );
		} catch ( Exception $e ) {
			$folders[3]['count'] = 'Error';
		}
		if ( $check ) {
			$folders[3]['count'] = '(' . $check->Nmsgs . ')';
		}
	}
	return $folders;
}
/**
 * Used for get imap fetch overview.
 *
 * @return array.
 */
function wms7_mail_inbox() {
	$imap = wms7_mail_connection();
	if ( $imap ) {
		$mc = imap_check( $imap );
		// Get an overview of all the letters in the box.
		$result = imap_fetch_overview( $imap, "1:{$mc->Nmsgs}", 0 );
		$i      = 0;
		$arr    = array();
		foreach ( $result as $overview ) {
			$arr[ $i ]['msgno'] = $overview->msgno;
			$pos                = strpos( $overview->date, '+' );
			if ( $pos ) {
				$overview->date = substr( $overview->date, 0, $pos );}
			$arr[ $i ]['date']    = $overview->date;
			$arr[ $i ]['from']    = iconv_mime_decode( $overview->from, 0, 'UTF-8' );
			$arr[ $i ]['subject'] = iconv_mime_decode( $overview->subject, 0, 'UTF-8' );
			$arr[ $i ]['seen']    = $overview->seen;
			$i++;
		}
		imap_close( $imap );
	} else {
		return 'Unable to open mailbox: ';
	}
	return $arr;
}
/**
 * Used for get mail parts.
 *
 * @param string $object object of mail.
 * @param string $parts  parts of mail.
 * @return array.
 */
function wms7_mail_parts( $object, &$parts ) {
	if ( 1 === $object->type ) {
		// Object is multipart.
		foreach ( $object->parts as $part ) {
			wms7_mail_parts( $part, $parts );
		}
	} else {
		$p['type']     = $object->type;
		$p['encoding'] = $object->encoding;
		$p['subtype']  = $object->subtype;
		$p['bytes']    = $object->bytes;
		if ( 1 === $object->ifparameters ) {
			foreach ( $object->parameters as $param ) {
				$p['params'][] = array(
					'attr' => $param->attribute,
					'val'  => $param->value,
				);
			}
		}
		if ( 1 === $object->ifdparameters ) {
			foreach ( $object->dparameters as $param ) {
				$p['dparams'][] = array(
					'attr' => $param->attribute,
					'val'  => $param->value,
				);
			}
		}
		$p['disp'] = null;
		if ( 1 === $object->ifdisposition ) {
			$p['disp'] = $object->disposition;
		}
		$parts[] = $p;
	}
	return $parts;
}
/**
 * Used for parse of mail parts.
 *
 * @param string $str       mail body.
 * @param string $boundary  boundary.
 * @return string.
 */
function wms7_get_plain( $str, $boundary ) {
	$lines = explode( "\n", $str );
	$plain = false;
	$res   = '';
	$start = false;
	foreach ( $lines as $line ) {
		if ( strpos( $line, 'text/plain' ) ) {
			$plain = true;
		}
		if ( 1 === strlen( $line ) && $plain ) {
			$start = true;
			$plain = false;
			continue;
		}
		if ( $start && strpos( $line, 'Content-Type' ) ) {
			$start = false;
		}
		if ( $start ) {
			$res .= $line;
		}
	}
	$res = substr( $res, 0, strpos( $res, '--' . $boundary ) );
	$res = ( $res ) ? $res : $str;

	return $res;
}
/**
 * Used for mail move to folder.
 */
function wms7_mail_move() {
	$_move_box    = filter_input( INPUT_POST, 'move_box', FILTER_SANITIZE_STRING );
	$_mail_number = filter_input_array( INPUT_POST, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$imap         = wms7_mail_connection();
	$mail_move    = array();
	foreach ( $_mail_number as $key => $value ) {
		if ( 'mail_number' === $value ) {
			$uid                = imap_uid( $imap, $key );
			$mail_move['id'][]  = $key;
			$mail_move['uid'][] = $uid;
		}
	}
	foreach ( $mail_move['uid'] as $value ) {
			imap_mail_move( $imap, $value, $_move_box, CP_UID );
			imap_expunge( $imap );
	}
	imap_close( $imap );
}
/**
 * Used for mail send.
 */
function wms7_mail_send() {
	global $mailer;

	$_files_attach     = filter_var_array( $_FILES );// WPCS: input var ok.
	$_files_attach     = $_files_attach['mail_new_attach'];
	$_mail_new_to      = filter_input( INPUT_POST, 'mail_new_to', FILTER_SANITIZE_STRING );
	$_mail_new_subject = filter_input( INPUT_POST, 'mail_new_subject', FILTER_SANITIZE_STRING );
	$_mail_new_content = filter_input( INPUT_POST, 'mail_new_content', FILTER_SANITIZE_STRING );
	$_document_root    = filter_input( INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING );
	$_msgno            = filter_input( INPUT_GET, 'msgno', FILTER_SANITIZE_STRING );
	$_move_box         = filter_input( INPUT_POST, 'move_box', FILTER_SANITIZE_STRING );
	$val               = get_option( 'wms7_main_settings' );
	$select_box        = $val['mail_select'];
	$box               = $val[ $select_box ];

	if ( ! is_object( $mailer ) || ! is_a( $mailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$mailer = new PHPMailer( true );
	}
	$mailer->isSMTP();
	$mailer->Host       = $box['smtp_server'];
	$mailer->SMTPAuth   = true;
	$mailer->Username   = $box['mail_box_name'];
	$mailer->Password   = $box['mail_box_pwd'];
	$mailer->SMTPSecure = mb_strtolower( $box['smtp_box_encryption'] );
	$mailer->Port       = $box['smtp_box_port'];
	$mailer->CharSet    = 'UTF-8';
	$mailer->isHTML( false );
	// Additional settings.
	$mailer->From     = $box['mail_box_name'];
	$blog_title       = get_bloginfo( 'name' );
	$mailer->FromName = $blog_title;
	$mailer->addAddress( $_mail_new_to );
	$mailer->Subject = $_mail_new_subject;
	$mailer->Body    = $_mail_new_content;
	// move to temporary directory /tmp.
	$item     = get_option( 'wms7_main_settings' );
	$path_tmp = $item['mail_box_tmp'] . '/';
	// for send draft or replay.
	if ( $_msgno ) {
		$arr        = wms7_mail_body( $_msgno );
		$arr_attach = $arr[1];
		if ( '' !== $arr_attach[0]['filename'] && 'zip' === substr( $_files_attach[0]['filename'], -3 ) ) {
			$file = $_document_root . $path_tmp . $arr_attach[0]['filename'];
			$mailer->AddAttachment( $file );
		}
	} else {
		// for new mail.
		if ( '' !== $_files_attach['name'] && 'zip' === substr( $_files_attach['name'], -3 ) ) {
			$file = $_document_root . $path_tmp . $_files_attach['name'];
			move_uploaded_file( $_files_attach['tmp_name'], $file );
			$mailer->AddAttachment( $file );
		}
	}
	// $mailer->SMTPDebug = 2;.
	// send the message, check for errors.
	if ( $mailer->send() ) {
		// array folders email-box.
		$folder = explode( ';', $box['mail_folders'], -1 );
		$i      = 0;
		$sent   = false;
		foreach ( $folder as $key => $value ) {
			$pos = strpos( $value, 'Sent' );
			if ( $pos ) {
				$sent = true;
				break;
			} else {
				$pos = strpos( $value, 'Отправлен' );
				if ( $pos ) {
					$sent = true;
					break;
				}
			}
			$i++;
		}
		$folder_alt = $box['mail_folders_alt'];
		$folder_alt = explode( ';', $folder_alt, -1 );
		$folder_alt = substr( $folder_alt[ $i ], strpos( $folder_alt[ $i ], '}' ) + 1 );
		$path       = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}' . $folder_alt;
		$imap       = imap_open( $path, $mailer->Username, $mailer->Password );
		$result     = imap_append( $imap, $path, $mailer->getSentMIMEMessage() );
		imap_close( $imap );
	} else {
		echo 'Mailer Error: ' . esc_html( $mailer->ErrorInfo );
	}
}
/**
 * Used for move mail to trash or delete.
 */
function wms7_mail_delete() {
	$_mail_number = filter_input_array( INPUT_POST, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$_radio_mail  = filter_input( INPUT_POST, 'radio_mail', FILTER_SANITIZE_STRING );
	$imap         = wms7_mail_connection();
	$val          = get_option( 'wms7_main_settings' );
	$select_box   = $val['mail_select'];
	$box          = $val[ $select_box ];

	foreach ( $_mail_number as $key => $value ) {
		if ( 'mail_number' === $value ) {
			$uid                  = imap_uid( $imap, $key );
			$mail_delete['id'][]  = $key;
			$mail_delete['uid'][] = $uid;
		}
	}
	$box = strpos( $box['mail_folders'], 'Trash' ) ? 'Trash' : 'Корзина';
	foreach ( $mail_delete['uid'] as $value ) {
			// mark for deletion.
			imap_delete( $imap, $value, FT_UID );
			// we transfer to the trash.
		if ( $box !== $_radio_mail ) {
			$msg = imap_mail_move( $imap, $value, $box, CP_UID );
			imap_expunge( $imap );
		} else {
			// delete.
			imap_expunge( $imap );
		}
	}
	imap_close( $imap );
}
/**
 * Used for decode imap list.
 *
 * @param string $list IMAP list.
 * @return array.
 */
function wms7_imap_list_decode( $list ) {
	$i = 0;
	foreach ( $list as $value ) {
		$value_alt  = $value;
		$pos        = strpos( $value, '&' );
		$value_prev = '';
		if ( $pos ) {
				$value_prev = substr( $value, 0, $pos );
				$value_prev = str_replace( '[Gmail]/', '', $value_prev );
				$value      = substr( $value, $pos );

				$arr = explode( ' ', $value );
			if ( 1 === count( $arr ) ) {
				$value = iconv( 'UTF-16BE', 'UTF-8', imap_utf7_decode( $arr[0] ) );
			} else {
				$result = '';
				foreach ( $arr as $item ) {
					$item   = iconv( 'UTF-16BE', 'UTF-8', imap_utf7_decode( $item ) );
					$result = $result . ' ' . $item;
				}
				$value = $result;
			}
		}
		if ( '' === $value ) {
				unset( $list[ $i ] );
		} else {
			$list[ $i ] = $value_prev . $value . '|' . $value_alt;
		}
		$i++;
	}
	return $list;
}
/**
 * Used for mail search.
 *
 * @return array.
 */
function wms7_mail_search() {
	$_mail_search_context = filter_input( INPUT_POST, 'mail_search_context', FILTER_SANITIZE_STRING );

	$imap       = wms7_mail_connection();
	$context    = 'TEXT ' . $_mail_search_context;
	$arr_search = imap_search( $imap, $context, SE_UID, 'UTF-8' );
	$i          = 0;
	$arr        = array();
	foreach ( $arr_search as $item ) {
		$result_overview = imap_fetch_overview( $imap, $item, FT_UID );
		foreach ( $result_overview as $overview ) {
			$arr[ $i ]['msgno'] = $overview->msgno;
			$pos                = strpos( $overview->date, '+' );
			if ( $pos ) {
				$overview->date = substr( $overview->date, 0, $pos );}
			$arr[ $i ]['date']    = $overview->date;
			$arr[ $i ]['from']    = iconv_mime_decode( $overview->from, 0, 'UTF-8' );
			$arr[ $i ]['subject'] = iconv_mime_decode( $overview->subject, 0, 'UTF-8' );
			$arr[ $i ]['seen']    = $overview->seen;
			$i++;
		}
	}
	imap_close( $imap );
	$list[0] = $arr;
	$list[1] = count( $arr );
	return $list;
}
/**
 * Used for mail inbox connection.
 *
 * @return object.
 */
function wms7_mail_inbox_connection() {
	$val        = get_option( 'wms7_main_settings' );
	$select_box = isset( $val['mail_select'] ) ? $val['mail_select'] : '';
	$box        = isset( $val[ $select_box ] ) ? $val[ $select_box ] : '';
	if ( $box ) {
		$box['imap_server']         = isset( $box['imap_server'] ) ? $box['imap_server'] : '';
		$box['mail_box_port']       = isset( $box['mail_box_port'] ) ? $box['mail_box_port'] : '';
		$box['mail_box_encryption'] = isset( $box['mail_box_encryption'] ) ? $box['mail_box_encryption'] : '';

		$server = '{' . $box['imap_server'] . ':' . $box['mail_box_port'] . '/imap/' . $box['mail_box_encryption'] . '/novalidate-cert}INBOX';
	} else {
		$server = '';
	}

	$username = isset( $box['mail_box_name'] ) ? $box['mail_box_name'] : '';
	$password = isset( $box['mail_box_pwd'] ) ? $box['mail_box_pwd'] : '';

	if ( $box && '' !== $username && '' !== $password ) {
		try {
			$imap = imap_open( $server, $username, $password );
		} catch ( Exception $e ) {
				$imap =
				$e->getMessage() .
				'<br>server: ' . $server .
				'<br>username: ' . $username .
				'<br>password: ' . $password;
		}
		return $imap;
	}
}
/**
 * Used for mail inbox unseen.
 *
 * @return number.
 */
function wms7_mail_unseen() {
	$imap = wms7_mail_inbox_connection();
	$i    = 0;
	if ( $imap ) {
		$mc = imap_check( $imap );
		// Get an overview of all the letters in the box.
		$result = imap_fetch_overview( $imap, "1:{$mc->Nmsgs}", 0 );
		foreach ( $result as $overview ) {
			if ( 0 === $overview->seen ) {
				$i++;
			}
		}
		imap_close( $imap );
	}
	return $i;
}
/**
 * Checking the connection to the servers SMTP.
 * Uses the PHPMailer library, which is not written in the standard snake_case.
 *
 * @param string $msg Sends a message to the admin about  on the website.
 * @return string Ok or error.
 */
function wms7_msg_smtp( $msg ) {
	$val = get_option( 'wms7_main_settings' );
	$box = $val['mail_select'];

	$mail_box_name       = isset( $val[ $box ]['mail_box_name'] ) ? $val[ $box ]['mail_box_name'] : '';
	$mail_box_pwd        = isset( $val[ $box ]['mail_box_pwd'] ) ? $val[ $box ]['mail_box_pwd'] : '';
	$smtp_server         = isset( $val[ $box ]['smtp_server'] ) ? $val[ $box ]['smtp_server'] : '';
	$smtp_box_encryption = isset( $val[ $box ]['smtp_box_encryption'] ) ? $val[ $box ]['smtp_box_encryption'] : '';
	$smtp_box_port       = isset( $val[ $box ]['smtp_box_port'] ) ? $val[ $box ]['smtp_box_port'] : '';

	$to = isset( $val[ $box ]['mail_box_name'] ) ? $val[ $box ]['mail_box_name'] : $val['box0']['mail_box_name'];

	if ( ! isset( $mailer ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$mailer = new PHPMailer( true );
	}
	$mailer->isSMTP();
	$mailer->Host       = $smtp_server;
	$mailer->SMTPAuth   = true;
	$mailer->Username   = $mail_box_name;
	$mailer->Password   = $mail_box_pwd;
	$mailer->SMTPSecure = mb_strtolower( $smtp_box_encryption );
	$mailer->Port       = $smtp_box_port;
	$mailer->CharSet    = 'UTF-8';
	$mailer->isHTML( false );
	// Add settings.
	$mailer->From     = $mail_box_name;
	$blog_title       = get_bloginfo( 'name' );
	$mailer->FromName = $blog_title;
	$mailer->addAddress( $to );
	$mailer->Subject = 'WatchMan-Site7 reports';
	$mailer->Body    = $msg . ' from ' . get_bloginfo( 'wpurl' );

	// $mailer->SMTPDebug = 2;.
	// send the message, check for errors.
	try {
		$mailer->Send();
		$smtp_server = 'Check the letter on your site';
	} catch ( phpmailerException $e ) {
		// PHPMailer error messages.
		$smtp_server = $e->errorMessage();
	} catch ( Exception $e ) {
		// other error messages.
		$smtp_server = $e->getMessage();
	}
	return $smtp_server;
}
/**
 * Used for mail save to folder Draft.
 */
function wms7_mail_save_to_draft() {
	WP_Filesystem();
	global $wp_filesystem;

	$_mail_new_subject = filter_input( INPUT_POST, 'mail_new_subject', FILTER_SANITIZE_STRING );
	$_mail_new_to      = filter_input( INPUT_POST, 'mail_new_to', FILTER_SANITIZE_STRING );
	$_mail_new_content = filter_input( INPUT_POST, 'mail_new_content', FILTER_SANITIZE_STRING );
	$val               = get_option( 'wms7_main_settings' );
	$select_box        = $val['mail_select'];
	$box               = $val[ $select_box ];

	// array folders email-box.
	$folder = explode( ';', $box['mail_folders'], -1 );
	$i      = 0;
	$draft  = false;
	foreach ( $folder as $key => $value ) {
		$pos = strpos( $value, 'Draft' );
		if ( $pos ) {
			$draft = true;
			break;
		} else {
			$pos = strpos( $value, 'Черновик' );
			if ( $pos ) {
				$draft = true;
				break;
			}
		}
		$i++;
	}
	if ( ! $draft ) {
		exit;
	}
	// array folders email-box.
	$folder = explode( ';', $box['mail_folders_alt'], -1 );
	$folder = substr( $folder[ $i ], strpos( $folder[ $i ], '{' ) );

	$date                = date( 'D, d M Y h:i:s', time() );
	$envelope['date']    = $date;
	$envelope['subject'] = '=?utf-8?B?' . base64_encode( $_mail_new_subject ) . '?=';
	$envelope['from']    = $box['mail_box_name'];
	$envelope['to']      = $_mail_new_to;

	$_files_attach = filter_var_array( $_FILES );// WPCS: input var ok.
	$_files_attach = $_files_attach['mail_new_attach'];

	if ( '' === $_files_attach['name'] ) {
		$part1['type']          = TYPETEXT;
		$part1['subtype']       = 'PLAIN';
		$part1['charset']       = 'UTF-8';
		$part1['contents.data'] = $_mail_new_content;
		$body[1]                = $part1;
	} elseif ( 'zip' === substr( $_files_attach['name'], -3 ) ) {
		$part1['type']    = TYPEMULTIPART;
		$part1['subtype'] = 'mixed';

		$part2['type']          = TYPETEXT;
		$part2['subtype']       = 'PLAIN';
		$part2['charset']       = 'UTF-8';
		$part2['contents.data'] = $_mail_new_content;

		$filename = $_files_attach['tmp_name'];
		$contents = $wp_filesystem->get_contents( $filename );

		$part3['type']             = TYPEAPPLICATION;
		$part3['encoding']         = ENCBINARY;
		$part3['subtype']          = 'octet-stream';
		$part3['description']      = $_files_attach['name'];
		$part3['disposition.type'] = 'attachment';
		$part3['disposition']      = array( 'filename' => $_files_attach['name'] );
		$part3['type.parameters']  = array( 'name' => $_files_attach['name'] );
		$part3['contents.data']    = $contents;

		$body[1] = $part1;
		$body[2] = $part2;
		$body[3] = $part3;
	}

	$msg = imap_mail_compose( $envelope, $body );

	$imap = wms7_mail_connection();

	if ( ! imap_append( $imap, $folder, $msg ) ) {
		die( 'could not append message: ' . esc_html( imap_last_error() ) );
	}
}
/**
 * Go to the sent folder of mailbox.
 *
 * @param string $_mail_new_nonce Mail new nonce.
 */
function wms7_goto_sent( $_mail_new_nonce ) {
	$val        = get_option( 'wms7_main_settings' );
	$select_box = $val['mail_select'];
	$box        = $val[ $select_box ];

	// array folders email-box.
	$folder = explode( ';', $box['mail_folders'], -1 );
	$i      = 0;
	$draft  = false;
	foreach ( $folder as $key => $value ) {
		$pos = strpos( $value, 'Sent' );
		if ( $pos ) {
			$draft = true;
			break;
		} else {
			$pos = strpos( $value, 'Отправлен' );
			if ( $pos ) {
				$draft = true;
				break;
			}
		}
		$i++;
	}
	if ( ! $draft ) {
		exit;
	}
	$i++;

	$folder = 'folder' . $i;
	?>
	<div class='loader' id='win-loader' style='top:350px;'></div>
	<script>wms7_popup_loader();</script>
	<script>mailbox_selector('<?php echo esc_html( $folder ); ?>',"",'<?php echo esc_html( $_mail_new_nonce ); ?>');</script>
	<?php
}
/**
 * Go to the draft folder of mailbox.
 *
 * @param string $_mail_new_nonce Mail new nonce.
 */
function wms7_goto_draft( $_mail_new_nonce ) {
	$val        = get_option( 'wms7_main_settings' );
	$select_box = $val['mail_select'];
	$box        = $val[ $select_box ];

	// array folders email-box.
	$folder = explode( ';', $box['mail_folders'], -1 );
	$i      = 0;
	$draft  = false;
	foreach ( $folder as $key => $value ) {
		$pos = strpos( $value, 'Draft' );
		if ( $pos ) {
			$draft = true;
			break;
		} else {
			$pos = strpos( $value, 'Черновик' );
			if ( $pos ) {
				$draft = true;
				break;
			}
		}
		$i++;
	}
	if ( ! $draft ) {
		exit;
	}
	$i++;

	$folder = 'folder' . $i;
	?>
	<div class='loader' id='win-loader' style='top:350px;'></div>
	<script>wms7_popup_loader();</script>
	<script>mailbox_selector('<?php echo esc_html( $folder ); ?>',"",'<?php echo esc_html( $_mail_new_nonce ); ?>');</script>
	<?php
}
