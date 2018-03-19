<?php
/*
Slave module: wp-cron.php
Description:  Lists all events in CRON
Version:      2.2.5
Author:       Oleg Klenitskiy
Author URI: 	https://www.adminkov.bcr.by/category/wordpress/
*/

ini_set('max_execution_time', 30); //120 seconds = 2 minutes

if( !class_exists( 'wms7_cron' )){
	class wms7_cron {

		private $file_name;
		public $orphan_count = 0;
		public $plugin_count = 0;
		public $themes_count =0;
		public $wp_count =0;

		function __construct() {

		}

		function wms7_delete_item_crons(){

			foreach ($_REQUEST as $key=>$value) {
				if ($value == 'cron'){
					wp_clear_scheduled_hook($key);
				}
			}
			//unset($key);
		}

		function wms7_create_cron_table(){
			$this->wms7_delete_item_crons();
			
			$arr_cron = $this->wms7_cron_view();
			$table_row = explode(";", $arr_cron);
			$str='<tbody class="tbody" style="max-height: 200px;height: 200px;">';
			$i=0;
			foreach ($table_row as $val) {
				$i++;
				$val = explode("|", $val);
				if ($val[0] !== '') {
					if (isset($_REQUEST['cron_refresh'])) {
							$source_task = $this->wms7_search_into_directory ($val[0]);
						}else{
							$source_task[0] = '';
							$source_task[1] =  __('press Refresh', 'wms7');
					}
					$str=$str.'<tr class="tr"><td class="td" width="8%" style="padding-left:5px;"><input type="checkbox" name="'.$val[0].'" value="cron">'.$i.'</td><td class="td" width="36%">'.$val[0].'</td><td class="td" width="15%">'.$val[1].'</td><td class="td" width="20%">'.$val[2].'</td><td class="td" width="21%" title="'.$source_task[0].'">'.$source_task[1].'</td></tr>';
				}
			}
			$str = $str.'</tbody>';
			unset($val);
			return $str;
		}

		function wms7_cron_view(){
			$val3 = _get_cron_array();
			$wp_cron ='';

			foreach ($val3 as $timestamp => $cron) {
				foreach ($cron as $key1 => $value1) {
					foreach ($value1 as $key2 => $value2) {	
						$wp_cron=$wp_cron.$key1.'|'.$value2 ["schedule"].'|'.get_date_from_gmt(date('Y-m-d H:i:s', $timestamp), 'M j, Y -> H:i:s').';';
					}
					unset($key2);
				}
				unset($key1);
			}
			unset($timestamp);
			return $wp_cron;
		}

		function wms7_scan_dir($dirname, $context) {
     	// Read in the cycle directory  
			foreach (glob($dirname.'/*') as $file) {
				if(is_file($file)) {
					$path_parts = pathinfo($file);
			 		if (isset($path_parts['extension']) && $path_parts['extension'] == 'php') {
       			// If the file *.php processed content				
		 				$pos = strpos(file_get_contents($file), $context);
		  			if ($pos !== false) {	
		  				$this->file_name[0] = $path_parts['dirname'];
		  				$this->file_name[1] = $path_parts['basename'];
		  				return $this->file_name;
		  			}
			 		}
			 	}else{
						if (is_dir($file)) {									
						  // If it is a directory, recursively called function wms7_scan_dir			      		
							$ret = $this->wms7_scan_dir($file, $context);
							if ($ret!==false) {
								return $ret;
							}
						}
				}
			}
			unset($file);
			return false;
		}	

		function wms7_search_into_directory ($context) {

			$dirname_wp =  get_home_path().'wp-admin';
			$dirname_wp_add =  get_home_path().'wp-includes';
			$dirname_themes =  get_theme_root();
			$dirname_plugins =  $dirname_plugins =  substr(trailingslashit( dirname( plugin_dir_path(__DIR__) )), 0, -1);		

			$step1 = $this->wms7_scan_dir($dirname_wp, $context);
			if ($step1 !== false) {
				$this->wp_count++;
				$step1[1] = '<span style="color: brown;">'.$step1[1].'</span>';
				return $step1;
			}

			$step2 = $this->wms7_scan_dir($dirname_wp_add, $context);	
			if ($step2 !== false) {
				$this->wp_count++; 
				$step2[1] =  '<span style="color: brown;">'.$step2[1].'</span>';
				return $step2;
			}

			$step3 = $this->wms7_scan_dir($dirname_themes, $context);	
			if ($step3 !== false) {
				$this->themes_count++;
				$step3[1] = '<span style="color: green;">'.$step3[1].'</span>';
				return $step3;
			}

			$step4 = $this->wms7_scan_dir($dirname_plugins, $context);	
			if ($step4 !== false) {
				$this->plugin_count++;
				$step4[1] = '<span style="color: blue;">'.$step4[1].'</span>';
				return $step4;
			}

			$this->orphan_count++; return '<span style="color: red;">'.__('Source not found', 'wms7').'</span>';
		}
	}
}