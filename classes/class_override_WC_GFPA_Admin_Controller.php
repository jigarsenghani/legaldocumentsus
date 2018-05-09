<?php 
if ( ! defined( 'ABSPATH' ) ) {
    die( '<h3>Direct access to this file do not allow!</h3>' );
}
class custom_WC_GFPA_Admin_Controller extends WC_GFPA_Admin_Controller 
{
  
  private static $instance;
  
  //const STATE_NAME_TEXT =  STATE_NAME

  public static function register() {
    if ( self::$instance == null ) {
      self::$instance = new custom_WC_GFPA_Admin_Controller;
    }
  }
  
	var $separator;
    var $lebalspecific;
	var $allowType;
	public function __construct() {
	$this->separator = '-';
    $this->allowType = array('product','select','radio');
    $this->lebalspecific = STATE_NAME;
    //add_action( 'admin_notices', array('WC_GFPA_Admin_Controller', 'admin_install_notices') );
    
	## Remove default class action "WC_GFPA_Admin_Controller" ref. woocommerce-gravityforms-product-addons plugin
	
	remove_action( 'add_meta_boxes', array('WC_GFPA_Admin_Controller', 'add_meta_box') );
    remove_action( 'woocommerce_process_product_meta', array('WC_GFPA_Admin_Controller', 'process_meta_box'), 1, 2 );
    
	remove_action( 'woocommerce_product_write_panel_tabs', array( 'WC_GFPA_Admin_Controller', 'add_tab' ) );


	## overwritre same calss method to over class
    add_action( 'add_meta_boxes', array($this, 'add_meta_box') );
    add_action( 'woocommerce_process_product_meta', array($this, 'process_meta_box'), 1, 2 );
    
    ## Ajax Call On changeEvent Or On add more button click event 
    
    add_action('wp_ajax_get_fromdataByID',array($this,'get_fromdataByID'));
    add_action('wp_ajax_nopriv_get_fromdataByID',array($this,'get_fromdataByID'));

    ## Ajax Call Controller Files  button click event 
    
    add_action('wp_ajax_generate_controllerfiles',array($this,'generate_controllerfiles'));
    add_action('wp_ajax_generate_controllerfiles',array($this,'generate_controllerfiles'));
    
    ## sorting Action Start 
    
    add_action('wp_ajax_datasorting',array($this,'generate_controllerfiles_datasorting'));
	
	
	
	## Export & import Rules Ajax action start 
	add_action('wp_ajax_rules_exports_by_id',array($this,'rules_exports_by_id'));
    add_action('wp_ajax_rules_import_by_file_url',array($this,'rules_import_by_file_url'));
    
    ## Script and Css ADD 
    add_action( 'admin_enqueue_scripts', array($this,'enqueue_css_jquery' ));
	
	//add_action( 'woocommerce_before_add_to_cart_button_myaction', array( 'WC_GFPA_Main', 'woocommerce_gravityform' ), 10 );
    
  }
 public function rules_import_by_file_url(){
	$responseArray = array();
	$responseArray['success'] = false;
	if(!empty($_POST['gravityform_id']) && !empty($_POST['product_id']) && !empty($_POST['fileurl']))
	{
		$gravity_rules_controls  = get_post_meta( $_POST['product_id'],'_gravity_form_rules',true);
		if(empty($gravity_rules_controls) && isset($gravity_rules_controls))
		{
			$responseArray['message'] = "<p class='alert-success'>Please Select CSV File</p>";
		}
		$expStr=explode("uploads",$_POST['fileurl']);
		$wp_upload_dir = wp_upload_dir();
		$filenameWithDir =  $wp_upload_dir['basedir'].$expStr[1];
		$row = 0;
		if (($handle = fopen($filenameWithDir, "r")) !== FALSE) {
		  $data =array();
			while (($datas = fgetcsv($handle, 100000, ",")) !== FALSE) {
			  $data[$row] = $datas;
			  $row++;
			}
			fclose($handle);
		}

		$rows = $data;
		$csvHeader = $data;
		$userHeader = array("rule_number","control_settings","controler_priority","system_generate","product_id","product_name","control_number");
		$importRows = array();
		foreach($rows as $keyIndex => $row)
		{
			if($keyIndex === 0 ) continue;
			
			$importRow = array_combine($userHeader,$row);
			$importRows = self::processRowForImport($importRows,$importRow);
			
		}
		
		
		### get Current data in in database interior with search by user import csv file. ####
		### if user can chnage system_generate  then default current data set in updated data ####
		
		$finalUpdate = array();
		foreach(unserialize($gravity_rules_controls) as $mainKeyIndex=>$gravity_rules)
		{
			
			$finalUpdate[$mainKeyIndex]['control_number'] = $gravity_rules['control_number'];
			$finalUpdate[$mainKeyIndex]['fields'] = $gravity_rules['fields'];
			$finalUpdate[$mainKeyIndex]['controler_priority'] = $gravity_rules['controler_priority'];
			$systemGenerateArray = array();
			$systemGenerateKey = 0;
			foreach($gravity_rules['generate_controllerfilestext'] as $generate_controllerfilestext)	
			{
					
				$systemGenerateArray[$systemGenerateKey]['system_generate'] = $generate_controllerfilestext['system_generate'];
				if(isset($generate_controllerfilestext['usermodify']))
				{
					foreach($generate_controllerfilestext['usermodify'] as $productid => $usermodify)
					{
						if(isset($importRows[$mainKeyIndex][$generate_controllerfilestext['system_generate']])){
							if(array_key_exists($productid,$importRows[$mainKeyIndex][$generate_controllerfilestext['system_generate']]))
							{
								$systemGenerateArray[$systemGenerateKey]['usermodify'] = $importRows[$mainKeyIndex][$generate_controllerfilestext['system_generate']];	
							}
						}else{
							$systemGenerateArray[$systemGenerateKey]['usermodify'] = $generate_controllerfilestext['usermodify'];	
						}
					}
				}else{
					$systemGenerateArray[$systemGenerateKey]['usermodify'] = array();
				}
				$systemGenerateKey++;
			}
			$finalUpdate[$mainKeyIndex]['generate_controllerfilestext'] = $systemGenerateArray;
			$finalUpdate[$mainKeyIndex]['gfuser_settings'] = $importRows[$mainKeyIndex]['control_settings'];
			$finalUpdate[$mainKeyIndex]['useraddcontrol_number'] = $gravity_rules['useraddcontrol_number'];
			
		}

		
		if(update_post_meta(intval($_POST['product_id']),'_gravity_form_rules',serialize($finalUpdate)) === false)
		{
			$responseArray['message'] = "<p class='alert-success'>Update failed please try again later</p>";
		}
		else
		{
			$responseArray['message'] = "<p class='alert-success'>Import File successfully update</p>";
			$responseArray['success'] = true;
		} 
	}else{
		$responseArray['message'] = "<p class='alert-success'>Please Select CSV File</p>";
	}
	
	/*echo "<pre>";
	print_r($finalUpdate);*/
	
	echo wp_json_encode($responseArray);
	die();
	
 }
 public static function processRowForImport($importRows,$importRow){
	$rule_number = $importRow['rule_number'] - 1;
	
	$importRows[$rule_number]['control_settings'] = $importRow['control_settings'];
	$importRows[$rule_number]['controler_priority'] = $importRow['controler_priority'];
	
	$importRows[$rule_number][$importRow['system_generate']][$importRow['product_id']] =  $importRow['control_number'];
	
	return $importRows;
 }

  public function rules_exports_by_id(){
	  
	  header('Content-Type: application/vnd.ms-excel');
	  $responseArray = array();
	  if(!empty($_POST['gravityform_id']) && !empty($_POST['product_id']))
	  {
		  
		 ## php external library  
		require_once  UL_PLUGIN_DIR .'/classes/PHPExcel/IOFactory.php';
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$gravity_rules_controls  = get_post_meta( $_POST['product_id'],'_gravity_form_rules',true);
		if(!empty($gravity_rules_controls) && isset($gravity_rules_controls))
		{
			$rulesNumber = 1;
			$mainLoop = 1;
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rulesNumber,'Rules Number');
			$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rulesNumber,'Control Setting');
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rulesNumber,'Controller Priority');
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rulesNumber,'System Generated');
			$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rulesNumber,'Product ID');
			$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rulesNumber,'Product Name');
			$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rulesNumber,'Control Number');
			$rulesNumber = 2;
			foreach(unserialize($gravity_rules_controls) as $key=>$gravity_rules)
			{
				foreach($gravity_rules['generate_controllerfilestext'] as $generate_controllerfilestext)	
				{
					foreach($generate_controllerfilestext['usermodify'] as $productid => $usermodify)
					{
						$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rulesNumber,$mainLoop);
						$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rulesNumber, $gravity_rules['gfuser_settings']);
						$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rulesNumber, $gravity_rules['controler_priority']);	
						$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rulesNumber, $generate_controllerfilestext['system_generate']);
						$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rulesNumber, $productid);
						$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rulesNumber,  htmlentities(get_the_title($productid)));
						$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rulesNumber, $usermodify);
						$rulesNumber++;
					}
				}
				$mainLoop++;
			}
			$wp_upload_dir = wp_upload_dir();
			
			$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
		//	$nowTime = echo (new \DateTime())->format('Y-m-d H:i:s');
			 $dateNow =  date("Y-m-d-H-i-s");
			$excelFileName =  $_POST['product_id'].'-'.$_POST['gravityform_id'].'-'.$dateNow;
			if (!file_exists($wp_upload_dir['basedir'] .'/legaldocuments_extrafiles/')) {
				mkdir($wp_upload_dir['basedir'] .'/legaldocuments_extrafiles/', 0777, true);
			}
			$objWriter->save( $wp_upload_dir['basedir'] .'/legaldocuments_extrafiles/'. $excelFileName.'.csv');
			$fileDir =$wp_upload_dir['baseurl'].'/legaldocuments_extrafiles/'.$excelFileName.'.csv';
			$responseArray['success'] = true;
			$responseArray['filename'] = $fileDir;
			$responseArray['message'] = "<p class='alert-success'>CSV file successfully exported</p>";
			
			
		}
		}else
		{
			$responseArray['success'] = false;
			$responseArray['message'] = "<p class='error-message'>Product Id or Gravity Id Not Found !!</p>";
		}
		echo json_encode($responseArray);
		die();
  }
  
  public function generate_controllerfiles_datasorting(){
    
    $tempData = str_replace("\\", "",$_POST['gencontrollerdata']);
    $tempDatan = json_decode($tempData,true);
    foreach ($tempDatan as $key => $row) {
      $system_generate[$key]  = $row['system_generate'];
      $usermodify[$key] = $row['usermodify'];
    }
    
    if($_POST['sorttype'] == 'ASC')
    {
      array_multisort($system_generate, SORT_ASC, $usermodify, SORT_ASC, $tempDatan);
    }elseif($_POST['sorttype'] == 'DESC'){
      array_multisort($system_generate, SORT_DESC, $usermodify, SORT_DESC, $tempDatan);
    }
    $indexKey = $_POST['indexKey'];
	$gencontrollerdataCount = 0;
    foreach($tempDatan as $orderingdata)
    {
      echo '<p class="form-field gravityform-display_title_field ">';
      echo '<span style="width:100%;"><label>Controller Generate</label>';
       echo '<input style="margin: 5px;" type="text" readonly name="downloadbale_file_name['.$indexKey.'][generate_controllerfilestext][system_generate][]" value="'.$orderingdata['system_generate'].'"></span><span>';
       
     foreach($orderingdata['usermodify'] as $key=>$userinput)
     {
      echo  '<input style="margin: 5px;" type="text" name="downloadbale_file_name['.$indexKey.'][generate_controllerfilestext][usermodify]['.$gencontrollerdataCount.']['.$key.']" value="'.$userinput.'">';
     }
    echo '</span><span class="deletedivcontroller" style="width:05%;"></span>';
      
    echo "</p>";
    $gencontrollerdataCount++;
    }
    echo "<input type='hidden' class='gencontrollerdata' name='gencontrollerdata' value='".json_encode($tempDatan)."'>";
    die();
  }
 
  public function enqueue_css_jquery() {
	  
	  
  wp_register_style( 'select2css', UL_PLUGIN_URL .'css/select2.css', false, UL_VERSION, 'all' );
  wp_enqueue_style( 'select2css' );
  wp_register_style( 'admin_style_us', UL_PLUGIN_URL .'css/admin_style.css', false, UL_VERSION, 'all' );
  wp_enqueue_style('admin_style_us');
  
  wp_register_script( 'select2',   UL_PLUGIN_URL .'js/select2.js', array( 'jquery' ), UL_VERSION, true );
  wp_enqueue_script( 'select2' );
  
  wp_register_script( 'customize_woo_adons',   UL_PLUGIN_URL .'js/customize_woo_adons_gravity.js', array( 'jquery' ), rand(), true );
  wp_enqueue_script( 'customize_woo_adons' );
  
  wp_localize_script('customize_woo_adons','ajax_custom', array( 'ajaxurl' => admin_url('admin-ajax.php'),'US_DIR' => UL_PLUGIN_URL));
  
  wp_enqueue_script('jquery-ui-tabs');
 
 }



  
### Start Codeing for generate_controllerfiles #####

  private function getMatchedcombination($input) {
    $result = array();

    while (list($key, $values) = each($input)) {
      if (empty($values)) {
        continue;
      }
      if (empty($result)) {
        foreach($values as $value) {
          $result[] = array($key => $value);
        }
      }
      else {
        $append = array();

        foreach($result as &$product) {
          $product[$key] = array_shift($values);

          $copy = $product;

          foreach($values as $item) {
            $copy[$key] = $item;
            $append[] = $copy;
          }
          array_unshift($values, $product[$key]);
        }
        $result = array_merge($result, $append);
      }
    }
    return $result;

  }

  protected function traverse($post_data = array(),$userselectednamerules,$useraddcontrol_number,$gravityFileds){
	
	$gfuser_settings = $post_data['gfuser_settings'];
    if(isset($post_data['productsku']) && !empty($post_data['productsku']))
    {
      $postSku = "_" . $post_data['productsku'];
    }
    $rules_indexkey = $post_data['rules_indexkey'];
    $rulesArray = array();
    $filenamecombiation = array();
    if((!empty($userselectednamerules) && isset($userselectednamerules)) && (!empty($gfuser_settings)))
    {
	  $new_array = array();
     
	 /*   echo "<pre>";
	  print_r($userselectednamerules); 
	  print_r($gravityFileds);   */
      
	  foreach(json_decode($userselectednamerules) as  $key=>$selectedrules)
      {
	    if($selectedrules[1] == '**')
        {	
          if(count($gravityFileds[$key]) > 15)
          {
			 
			echo  '<p class="warning-message"> First ** consider as State  because more then 15 recode in dropdown list</p>';
            unset($gravityFileds[$key]);
            $new_array[0]['name'] =  "**";
            $new_array[0]['value'] =  "**";
            $gravityFileds[$key] = $new_array;
            $filenamecombiation[$selectedrules[2]] = $gravityFileds[$key];
          }else
		  {
			  
            $filenamecombiation[$selectedrules[2]] = $gravityFileds[$key];
          }
        }
			$rulesArray[$selectedrules[2]] = $selectedrules[1];
      }
      
	  /*  echo "<pre>";
	  print_r($filenamecombiation);  
	  die();*/
      ## Start If value is "skip" and  "**" then Remove Key in RulesArray ##############
      foreach (array_keys($rulesArray, 'skip') as $key) {
        unset($rulesArray[$key]);
      }
      
      ### add if value is more ***  ####
      /*foreach (array_keys($rulesArray, '**') as $key) {
        unset($rulesArray[$key]);
      }*/
      
      ## End If value is "skip" and  "**" then Remove Key in RulesArray ##############

      $filename  = implode("_",$rulesArray);
      
      $newrulesArray = array();
      
		/* echo "<pre>";
		print_r($filenamecombiation);
		die(); */
	  
       foreach($filenamecombiation as $key=>$rulesArrayMain ){
        {
          foreach($rulesArrayMain as $innerKey=>$rulesArrayInner ){
            
            $newrulesArray[$key][$innerKey] = $rulesArrayInner['value'];
          } 
        }
      }
     

		
      $finaName = array();
      $mainindexcount = 0;
      foreach(self::getMatchedcombination($newrulesArray) as $f)
      {
        $finaName[$mainindexcount] = $rulesArray;
        foreach($f as $k=>$n){
          $finaName[$mainindexcount][$k] = $n;
        }
        $mainindexcount++;
      }
      $gencontrollerdata = array();
      $gencontrollerdataCount = 0;
      foreach($finaName as $resultedFilename)
      {
        ksort($resultedFilename);
         $resultcount = 1;
         $totalRecode = count($resultedFilename);
         $val = '';
         $userVal = '';
        foreach($resultedFilename as $name)
        { 
           if($resultcount != $totalRecode){
             $val .=  $name .'_';
             $userVal .=  $name .'-';
           }else{
             $val .=  $name;
             $userVal .=  $name;
           }
          $resultcount++;
        }
          $indexKey = $rules_indexkey;
          
          echo '<p class="form-field gravityform-display_title_field ">';
          echo '<span style="width:100%;"><label>Controller Generate</label>';
           echo '<input style="margin: 5px;" type="text" readonly name="downloadbale_file_name['.$indexKey.'][generate_controllerfilestext][system_generate][]" value="'.$val.$postSku.'"></span><span>';
          $gencontrollerdata[$gencontrollerdataCount]['system_generate'] = $val .$postSku;
       
        $useraddcontrol_numberdecodeval =  json_decode($useraddcontrol_number);
      
       foreach($useraddcontrol_numberdecodeval as $key => $usernumber)
       { 
        $newval = '';
        $newval .= str_replace(" ","-",$userVal);
        if(!empty($usernumber[2])){
          $newval .= "-" . str_replace(" ","-",$usernumber[2]);
        }
        echo '<input style="margin: 5px;" type="text" name="downloadbale_file_name['.$indexKey. '][generate_controllerfilestext][usermodify]['.$gencontrollerdataCount.']['.$usernumber[1].']" value="'.$newval.'">';
        $gencontrollerdata[$gencontrollerdataCount]['usermodify'][$usernumber[1]] = $newval;
        unset($useraddcontrol_numberdecodeval[$key]);
        
       }
       
       
       
        echo '</span><span class="deletedivcontroller" style="width:05%;"></span>';
        echo "</p>";
        $gencontrollerdataCount++;    
      }
        echo "<input type='hidden' class='gencontrollerdata' name='gencontrollerdata' value='".json_encode(
        $gencontrollerdata)."'>";
      
    }
  }
  protected function us_form($post_data = array(),$userselectednamerules,$useraddcontrol_number,$gravityFileds){
	
    $gfuser_settings = $post_data['gfuser_settings'];
    
    if(isset($post_data['productsku']) && !empty($post_data['productsku']))
    {
      $postSku = "_" . $post_data['productsku'];
    }
    
   // $userselectednamerules =  str_replace("\\", "",$post_data['userselectednamerules']);
    
   // $tempData = str_replace("\\", "",$post_data['controllernamerules']);
    // $useraddcontrol_number = str_replace("\\", "",$post_data['useraddcontrol_number']);
    //$rules_indexkey = $post_data['rules_indexkey'];

    
    $tempDataNew = json_decode($gravityFileds,true);
    $rulesArray = array();
    
    $filenamecombiation = array();
    if((!empty($userselectednamerules) && isset($userselectednamerules)) && (!empty($gfuser_settings)))
    {
		
      /*foreach(json_decode($userselectednamerules) as  $selectedrules)
      {
      
        if($selectedrules[1] == '**')
        {
          $filenamecombiation[$selectedrules[2]] = $tempDataNew[$selectedrules[2]];
        }
        if($selectedrules[1] != 'skip' && $selectedrules[1] != '**')
        {
            //$rulesArray[$selectedrules[2]] = $selectedrules[1];
        } 
        $rulesArray[$selectedrules[2]] = $selectedrules[1];
      }*/
	  
	  
	  
	//  $countSelect = 0;
      foreach(json_decode($userselectednamerules) as  $key=>$selectedrules)
      {
	    if($selectedrules[1] == '**')
        {	
          if(count($gravityFileds[$key]) > 15)
          {
			echo  '<p class="warning-message"> First ** consider as State  because more then 15 recode in dropdown list</p>';
            unset($gravityFileds[$key]);
            $new_array[0]['name'] =  "**";
            $new_array[0]['value'] =  "**";
            $gravityFileds[$key] = $new_array;
            $filenamecombiation[$selectedrules[2]] = $gravityFileds[$key];
          }else
		  {
			  
            $filenamecombiation[$selectedrules[2]] = $gravityFileds[$key];
          }
        }
			$rulesArray[$selectedrules[2]] = $selectedrules[1];
      }
      
      ## Start If value is "skip" and  "**" then Remove Key in RulesArray ##############
      
      foreach (array_keys($rulesArray, 'skip') as $key) {
        unset($rulesArray[$key]);
      }
      foreach (array_keys($rulesArray, '**') as $key) {
        unset($rulesArray[$key]);
      }
      
      ## End If value is "skip" and  "**" then Remove Key in RulesArray ##############

      $filename  = implode("_",$rulesArray);
      
      $newrulesArray = array();
      
      foreach($filenamecombiation as $key=>$rulesArrayMain ){
        {
          foreach($rulesArrayMain as $innerKey=>$rulesArrayInner ){
            
            $newrulesArray[$key][$innerKey] = $rulesArrayInner['value'];
          } 
        }
      }
      $finaName = array();
      $mainindexcount = 0;
      foreach(self::getMatchedcombination($newrulesArray) as $f)
      {
        $finaName[$mainindexcount] = $rulesArray;
        foreach($f as $k=>$n){
          
          $finaName[$mainindexcount][$k] = $n;
          //echo "<br>Key123 :- " . $k . " values :-" . $n."==================><br>";
        }
        $mainindexcount++;
      }
      $gencontrollerdata = array();
      $gencontrollerdataCount = 0;
      foreach($finaName as $resultedFilename)
      {
         $resultcount = 1;
         $totalRecode = count($resultedFilename);
         $val = '';
        foreach($resultedFilename as $name)
        {
           if($resultcount != $totalRecode){
             $val .=  $name .'_';
           }else{
            $val .=  $name;
           }
           $resultcount++;
        }
          $indexKey = $rules_indexkey;
          echo '<p class="form-field gravityform-display_title_field ">';
          echo '<span style="width:100%;"><label>Controller Generate</label>';
           echo '<input style="margin: 5px;" type="text" readonly name="downloadbale_file_name['.$indexKey.'][generate_controllerfilestext][system_generate][]" value="'.$val.$postSku.'"></span><span>';
          $gencontrollerdata[$gencontrollerdataCount]['system_generate'] = $val .$postSku;  
        $useraddcontrol_numberdecodeval =  json_decode($useraddcontrol_number);
       foreach($useraddcontrol_numberdecodeval as $key => $usernumber)
       { 
        $val = '';
        $val .= 'US'.$this->separator;
        $val .=  $usernumber[2];
        echo '<input style="margin: 5px;" type="text" name="downloadbale_file_name['.$indexKey. '][generate_controllerfilestext][usermodify]['.$gencontrollerdataCount.']['.$usernumber[1].']" value="'.$val.'">';
        $gencontrollerdata[$gencontrollerdataCount]['usermodify'][$usernumber[1]] = $val;
        unset($useraddcontrol_numberdecodeval[$key]);
       }
       
       
    
           echo '</span><span class="deletedivcontroller" style="width:05%;"></span>';
          echo "</p>";
          $gencontrollerdataCount++;
      }

    echo "<input type='hidden' class='gencontrollerdata' name='gencontrollerdata' value='".json_encode($gencontrollerdata)."'>";
      
    }
    
  }
  protected function state_specific($post_data = array(),$userselectednamerules,$useraddcontrol_number,$gravityFileds){
  	$gfuser_settings = $post_data['gfuser_settings'];
    if(isset($post_data['productsku']) && !empty($post_data['productsku']))
    {
      $postSku = "_" . $post_data['productsku'];
    }
    $rules_indexkey = $post_data['rules_indexkey'];
    $rulesArray = array();
    if((!empty($userselectednamerules) && isset($userselectednamerules)) && (!empty($gfuser_settings)) && !empty($useraddcontrol_number))
    {
	   foreach(json_decode($userselectednamerules) as  $selectedrules)
       {
	
		if(strtolower($selectedrules[0])  != strtolower($this->lebalspecific)) continue;
		
		$gencontrollerdataCount = 0;
		## 02-05-2018  state ID not identify so we have set gravity filed key if key 1 is state only for state_specific ####
        foreach($gravityFileds[0] as  $key=>$controllernamerules)
        {
			$val ='';
			$val .= $controllernamerules['value'];
			$indexKey = $rules_indexkey;
			echo '<p class="form-field gravityform-display_title_field ">';
			echo '<span style="width:100%;"><label>Controller Generate</label>';
			echo '<input style="margin: 5px;" type="text" readonly name="downloadbale_file_name['.$indexKey.'][generate_controllerfilestext][system_generate][]" value="'.$val .$postSku.'"></span><span>';
			$gencontrollerdata[$gencontrollerdataCount]['system_generate'] = $val .$postSku;
			 
		   $useraddcontrol_numberdecodeval =  json_decode($useraddcontrol_number);
		   foreach($useraddcontrol_numberdecodeval as $key => $usernumber)
		   { 
				$val = '';
				$val .= $controllernamerules['value'];
				$val .= $this->separator . $usernumber[2];
				echo '<input style="margin: 5px;" type="text" name="downloadbale_file_name['.$indexKey. '][generate_controllerfilestext][usermodify]['.$gencontrollerdataCount.']['.$usernumber[1].']" value="'.$val.'">';
				$gencontrollerdata[$gencontrollerdataCount]['usermodify'][$usernumber[1]] = $val;
				unset($useraddcontrol_numberdecodeval[$key]);
		   }
	   	   echo '</span><span class="deletedivcontroller" style="width:05%;"></span>';
		   echo "</p>";
	   
			$gencontrollerdataCount++;
			
		}
        echo "<input type='hidden' class='gencontrollerdata' name='gencontrollerdata' value='".json_encode($gencontrollerdata)."'>";
	 }
    }
    
	} 
  public function generate_controllerfiles()
  {

    if(empty($_POST['gfuser_settings']))
    {
	  echo self::error_notice_info('Please Select Setting for Generate Controller');
      die();
    }elseif(empty($_POST['useraddcontrol_number']) && $_POST['gfuser_settings'] != 'traverse')
    {
      echo self::error_notice_info('Please Enter Controller Number for Generate Controller');
      die();
    }
    if(isset($_POST['controllername']))
    {
      unset($_POST['productsku']);
      $lastCotrollerGet  = explode("_",$_POST['controllername']);
      $_POST['productsku'] = end($lastCotrollerGet);
    }
	
	$resultsGFAPI = GFAPI::get_form( intval($_POST['gravity_form_id']) );
	foreach($resultsGFAPI['fields'] as $key=>$formdata)
	{
		if(in_array($formdata['type'],$this->allowType))
		{
			$gravityFileds[$key] = $formdata['choices'];
		}
	}
	 
	$userselectednamerules =  str_replace("\\", "",$_POST['userselectednamerules']);
	$useraddcontrol_number = str_replace("\\", "",$_POST['useraddcontrol_number']);
	switch($_POST['gfuser_settings']){
      case 'us_form':
      self::us_form($_POST,$userselectednamerules,$useraddcontrol_number,$gravityFileds);
      break;
      case 'state_specific':
      self::state_specific($_POST,$userselectednamerules,$useraddcontrol_number,$gravityFileds);
      break;
      default: ##traverse
      self::traverse($_POST,$userselectednamerules,$useraddcontrol_number,$gravityFileds);
    }
    die();
  }

  
  
  private function error_notice_info($error_text)
  {
	  return  '<span class="error error-notice">'._e($error_text,'legaldocumentsus').'</span>'; 
  }
   ### End Codeing for generate_controllerfiles #####
 
  private function gravit_rules_output_html($from_ID,$post_id,$controlled_number = null,$selected_fields_rules = null,$controler_priority = null,$gravity_rules=array(),$gravity_form_data =array())
  {
    
    $output = '';
    $gravityData = array();


	## OLD Query SET 
	//$results = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rg_form_meta WHERE form_id = %d",$from_ID),OBJECT );


	## new API  get form details by ID V 2.3 gravity version
	$resultsGFAPI = GFAPI::get_form( intval($from_ID) );
	$output .=  '<div class="gravityform-custom-div">';
    $output .= '<p class="deletediv"></p>';
	
    if(!empty($resultsGFAPI))
    {
		$output .=  '<div class="gravityform-custom-div-innerFiled">';
		$countTotalFileds = 0;

		foreach($resultsGFAPI['fields'] as $key=>$formdata)
		{
		
			if(in_array($formdata['type'],$this->allowType))
			{
				
				$gravityData[$formdata['id']] = $formdata['choices'];
				//echo $formdata['label'];
				$output .= '<p class="form-field gravityform-display_title_field ">';
				$output .= '<label >'.$formdata['label'].'</label>';
				$output .= '<select  class="multiple_selection" id="gravity_filed-'.$formdata['id'].'" data-gravity_filed_id='.$formdata['id'].' name="'. $formdata['label'] .'" >';
				
				 $selected_field_value = self::getSelectedFieldValue($formdata['label'],$formdata['id'],$selected_fields_rules);
				
				$output .= '<option '.(($selected_field_value == '**')?'selected':' ').'value="**">**</option>';
				$output .= '<option '.(($selected_field_value == 'skip')?'selected':' ').' value="skip">skip</option>';
				foreach($formdata['choices'] as $choicesValue)
				{
					$blank = '';  
					$output .= '<option data-inputs_ID ="'.$choicesValue['value'].'" value="'.$choicesValue['value'].'" '.(($selected_field_value == $choicesValue['value'])?'selected':' ').' >'.$choicesValue['text'].'</option>';
				}
			 $output .= '</select>';
			 $output .= '</p>';  	
			} else {
				
			}
			$countTotalFileds++;
			
		}
		$output .=  '</div>'; //over "gravityform-custom-div-innerFiled"
		 
		$output .= '<p class="form-field gravityform-display_title_field ">';
		$output .= '<label data-fileds='.$countTotalFileds.'>Controller Name</label>';
		$arrayKey = rand();
		
		$output .= '<input type="hidden"  class="rules_indexkey" name="rules_indexkey" value="'.$arrayKey.'">';
		if(empty($gravity_rules['control_number']))
		{
		  $controllernameVal = $_POST['product_sku'];
		}else{
		  $controllernameVal =  $gravity_rules['control_number'];
		}
		
		$output .= '<input type="text"  class="controllername" name="downloadbale_file_name['.$arrayKey.'][control_number]"  value="'.$controllernameVal.'" placeholder="">';
		//echo "<pre>";
		//print_r($gravityData);
		
	  $output .= "<input type='hidden' class='gravityData_all' name='gravityData_all[".$arrayKey."]'  value='".json_encode($gravityData)."' >";
		$output .= "<input type='hidden' class='gravity_form_id' name='gravity_form_id[".$arrayKey."][gravity_form_id]'  value='".intval($from_ID)."' >";
			
		if(!empty($gravity_rules['fields']))
		{
			
			//print_r($gravity_rules['fields']);
			$gravity_rulesDisplay = $gravity_rules['fields'];
		}else{
			$gravity_rulesDisplay = '';
		}		

		$output .= "<input type='hidden' class='downloadbale_file_array' name='downloadbale_file_name[".$arrayKey."][control_fields]'  value='".$gravity_rulesDisplay."'>";
		
		
		
		$asterisksignadd = '';
		foreach(range(1,$countTotalFileds) as $numberoffileds)
		{
		  $asterisksignadd .= "**_"; 
		}
		$output .= '<span class="downloadbale_file_name">'.$asterisksignadd.get_post_meta($post_id,"_sku",true).'</span>';
		$output .= '<br><span class="copycontrollername"><i class="fa fa-files-o" aria-hidden="true"></i></span>';
		$output .= '</p>';
		$output .= '<p class="form-field gravityform-display_title_field ">';
		$output .= '<label>Priority</label>'; 
		$output .= '<input type="text" class="priority_controller" name="downloadbale_file_name['.$arrayKey.'][priority_controller]"  value="'.$controler_priority.'" placeholder="Select Conroller Priority set Condition">';
		
		$output .= '</p>';
		$output .= '<p class="form-fields gravityform-display_title_field ">';
		
		if(!empty($gravity_rules['gfuser_settings']))
		{
			$usergfuser_settings =  $gravity_rules['gfuser_settings'];
		}else{
			$usergfuser_settings = '';
		}
		
		$output .= '<select name="downloadbale_file_name['.$arrayKey.'][gfuser_settings]" class="gfuser_settings gfuser_settingsnew">';
		$output .= '<option value="">Select Setting</option>';
		$disableOption = '';
		if($gravity_form_data['loopCount'] != 0)
		
		{
			$disableOption = "disabled";

		}	
		$output .= '<option  ' . $disableOption   . (($usergfuser_settings == 'us_form')?'selected':' ').' value="us_form">US Form </option>';
		$output .= '<option ' . $disableOption    . (($usergfuser_settings == 'state_specific')?'selected':' ').' value="state_specific">State-Specific</option>';
		

		$output .= '<option '. (($usergfuser_settings == 'traverse')?'selected':' ') .' value="traverse">Traverse</option>';
		$output .= '</select>';
	  
		$is_producttypebundle = get_post_meta($post_id,'_yith_wcpb_bundle_data',true);
	  
		$output .= '<div class="product-name-list">';
		
		if(!empty($is_producttypebundle))
		{
			$output .= '<input type="hidden" class="is_producttypebundle" name="downloadbale_file_name['.$arrayKey.'][is_producttypebundle]" value="yes">';
			foreach($is_producttypebundle as $producttypebundle)
			{
			   $output .= '<div style="width: 50%;float:left;"><p class="form-field gravityform-display_title_field">';
			  $output .= '<label>'. get_the_title($producttypebundle['product_id']).'</label>';
			if(isset($gravity_rules['useraddcontrol_number']) && !empty($producttypebundle['product_id']))
			{
			  $output .= '<input type="text"  class="bundle_data_number useraddcontrol_number" data-rules_indexkey="'.$arrayKey.'"
			  data-product_id="'.@$producttypebundle['product_id'].'" name="downloadbale_file_name['.$arrayKey.'][useraddcontrol_number]['.@$producttypebundle['product_id'].']"  style="margin-left: 4%;float:none;" value="'.@$gravity_rules['useraddcontrol_number'][@$producttypebundle['product_id']].'">';
			}
			  $output .= '</p></div>';
			}
			
		}else
		{
			 
			$output .= '<p class="form-field gravityform-display_title_field" style="width: 50%;"><label>'.  get_the_title($is_producttypebundle).'</label>';
			 
			$gravity_rules_useraddcontrol_number = '';
			if(!empty($gravity_rules['useraddcontrol_number']))
			{
				$gravity_rules_useraddcontrol_number = $gravity_rules['useraddcontrol_number'];
			}
			$output .= '<input type="text" class="bundle_data_number useraddcontrol_number" data-rules_indexkey="'.$arrayKey.'"
			  data-product_id="'.$post_id.'" name="downloadbale_file_name['.$arrayKey.'][useraddcontrol_number]"  style="margin-left: 4%;float:none;" value="'.$gravity_rules_useraddcontrol_number.'">';
			 
			$output .= '<input type="hidden" class="is_producttypebundle"  name="downloadbale_file_name['.$arrayKey.'][is_producttypebundle]" value="no"></p>';
		}
		
		$output .= '&nbsp;&nbsp;&nbsp;<button type="button" class="generate_controllerfiles button button-primary button-medium">Generate controller</button>';
		$output .= '&nbsp;&nbsp;&nbsp;Edit <input type="checkbox" value="on" class="editgenBox ">';
		
		$output .= '</p>';
		$output .= '</div>';
		$output .='<div class="wrap tabwrapstart" style="display:none">';
		$output .= '<div id="tabs-'.$arrayKey.'">';
		$output .= ' <ul>';
		$output .= ' <li><a href="#tabs-'.$arrayKey.'1">Generated Controller</a></li>';
		$output .= '  <li><a href="#tabs-'.$arrayKey.'2">Addons</a></li>';
		if( $gravity_form_data['loopCount'] == 0){
		
			$output .= ' <li><a href="#tabs-'.$arrayKey.'3">Settings</a></li>';
		}
		$output .= ' </ul>';


		$output .= '<div id="tabs-'.$arrayKey.'1"> ';
		 
		$output .='<a style="cursor:  pointer;position: relative;left: 8px;bottom: 5px;" class="datasorting" data-sortby="ASC"><i class="fa fa-sort-asc" aria-hidden="true"></i></a>';
		$output .='<a style="cursor:  pointer;" class="datasorting" data-sortby="DESC"><i class="fa fa-sort-desc" aria-hidden="true"></i></a>';
		$output .= '<div class="controllfilenameresponse">';
		if(isset($gravity_rules['generate_controllerfilestext']))
		{
			$output .= '<p class="warning-message"> First ** consider as State  because more then 15 recode in dropdown list</p>';
			$gencontrollerdataCount = 0;
			foreach($gravity_rules['generate_controllerfilestext']  as  $gravit_rules_usereditdata)
			{
				$indexKey = rand();
				$output .= '<p class="form-field gravityform-display_title_field ">';
				$output .= '<span style="width:100%;"><label>Controller Generate</label>';
				$output .= '<input style="margin: 5px;" type="text" readonly  name="downloadbale_file_name['.$arrayKey.'][generate_controllerfilestext][system_generate][]" value="'.$gravit_rules_usereditdata['system_generate'].'"></span><span>';
				if(isset($gravit_rules_usereditdata['usermodify']))
				{
					foreach($gravit_rules_usereditdata['usermodify'] as $key=>$userinput)
					{
						$output .= '<input style = "margin: 5px;" type="text" name="downloadbale_file_name['.$arrayKey.'][generate_controllerfilestext][usermodify]['.$gencontrollerdataCount.']['.$key.']" value="'.$userinput.'">';
					}
				}
				$output .='</span><span class="deletedivcontroller" style="width:05%;"></span>';
				$output .= "</p>";
				$gencontrollerdataCount ++; 
			}
			$output .= "<input type='hidden' class='gencontrollerdata' name='gencontrollerdata' value='".json_encode($gravity_rules['generate_controllerfilestext'])."'>";
		}
		 
		$output .= '</div>';  
		$output .= '</div>';  //tab1 closing
		$output .= '<div id="tabs-'.$arrayKey.'2"> Addons</div>';
		if( $gravity_form_data['loopCount'] == 0){

			$output .= '<div id="tabs-'.$arrayKey.'3" class="count-'. $gravity_form_data['loopCount'].'">'; 
			$output .= self::extra_metaBox_woo_gravity($gravity_form_data);
			$output .='</div>';
		}
		$output .='</div></div>';
  
    
	}
  $output .="<script>
    jQuery(document).ready(function ($) {
        $( '#tabs-".$arrayKey."').tabs();
    });
  </script>";
  $output .= "<hr>";
   $output .= "</div>";
   return $output;
     
}
  
	public function get_fromdataByID(){
	
		$gravity_form_data['loopCount'] = intval($_POST['loopCount']);
		
	  echo self::gravit_rules_output_html($_POST['fromID'],$_POST['product_id'],$controlled_number = null,$selected_fields_rules = null,$controler_priority = null,$gravity_rules=array(),$gravity_form_data);
	  die();
	}
	public function add_meta_box() {
		global $post;
		add_meta_box( 'woocommerce-gravityforms-meta', __( 'Gravity Forms Product Add-Ons', 'wc_gf_addons' ), array($this, 'meta_box'), 'product', 'normal', 'default' );
	}

  
  private function getSelectedFieldValue($gravity_label,$gravity_id,$selected_fields_rules){
	if(!empty($selected_fields_rules)){
    foreach($selected_fields_rules as $selected_field_rule){
      if($selected_field_rule[2] == $gravity_id) return $selected_field_rule[1];
    }
    return null;
    }
  }
      
function my_array_merge(&$array1, &$array2)
{
  $result = Array();
  foreach($array1 as $key => &$value) {
    $result[$key] = $value;
    if(isset($array2[$key])) $result[$key] = array_merge($value, $array2[$key]);
  }
  return $result;
 }
     

	 
function get_gravity_form_data( $post_id ) 
{
		$product = wc_get_product( $post_id );
		$data    = false;
		if ( $product ) {
			$data = $product->get_meta( '_gravity_form_data' );
		}

		return apply_filters( 'woocommerce_gforms_get_product_form_data', $data, $post_id );

}






protected function woocommerce_wp_text_input_return( $field ) { 
    global $thepostid, $post; 
	$woocommerce_wp_text_input = '';
    $thepostid = empty( $thepostid ) ? $post->ID : $thepostid; 
    $field['placeholder'] = isset( $field['placeholder'] ) ? $field['placeholder'] : ''; 
    $field['class'] = isset( $field['class'] ) ? $field['class'] : 'short'; 
    $field['style'] = isset( $field['style'] ) ? $field['style'] : ''; 
    $field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : ''; 
    $field['value'] = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true ); 
    $field['name'] = isset( $field['name'] ) ? $field['name'] : $field['id']; 
    $field['type'] = isset( $field['type'] ) ? $field['type'] : 'text'; 
    $field['desc_tip'] = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false; 
    $data_type = empty( $field['data_type'] ) ? '' : $field['data_type']; 
 
    switch ( $data_type ) { 
        case 'price' : 
            $field['class'] .= ' wc_input_price'; 
            $field['value'] = wc_format_localized_price( $field['value'] ); 
            break; 
        case 'decimal' : 
            $field['class'] .= ' wc_input_decimal'; 
            $field['value'] = wc_format_localized_decimal( $field['value'] ); 
            break; 
        case 'stock' : 
            $field['class'] .= ' wc_input_stock'; 
            $field['value'] = wc_stock_amount( $field['value'] ); 
            break; 
        case 'url' : 
            $field['class'] .= ' wc_input_url'; 
            $field['value'] = esc_url( $field['value'] ); 
            break; 
 
        default : 
            break; 
    } 
 
    // Custom attribute handling 
    $custom_attributes = array(); 
 
    if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) { 
 
        foreach ( $field['custom_attributes'] as $attribute => $value ) { 
            $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"'; 
        } 
    } 
 
    $woocommerce_wp_text_input  .= '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"> 
        <label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>'; 
 
    if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) { 
         $woocommerce_wp_text_input  .=  wc_help_tip( $field['description'] ); 
    } 
 
     $woocommerce_wp_text_input  .=  '<input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> '; 
 
    if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) { 
         $woocommerce_wp_text_input  .=  '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>'; 
    } 
 
     $woocommerce_wp_text_input  .=  '</p>'; 
	 
	 return  $woocommerce_wp_text_input;
} 



protected function woocommerce_wp_select_return( $field ) { 
    global $thepostid, $post; 
	$woocommerce_wp_select_return = '';
    $thepostid = empty( $thepostid ) ? $post->ID : $thepostid; 
    $field['class'] = isset( $field['class'] ) ? $field['class'] : 'select short'; 
    $field['style'] = isset( $field['style'] ) ? $field['style'] : ''; 
    $field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : ''; 
    $field['value'] = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true ); 
    $field['name'] = isset( $field['name'] ) ? $field['name'] : $field['id']; 
    $field['desc_tip'] = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false; 
 
    // Custom attribute handling 
    $custom_attributes = array(); 
 
    if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) { 
 
        foreach ( $field['custom_attributes'] as $attribute => $value ) { 
            $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"'; 
        } 
    } 
 
    $woocommerce_wp_select_return .=  '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"> 
        <label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>'; 
 
    if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) { 
         $woocommerce_wp_select_return .= wc_help_tip( $field['description'] ); 
    } 
 
     $woocommerce_wp_select_return .= '<select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" ' . implode( ' ', $custom_attributes ) . '>'; 
 
    foreach ( $field['options'] as $key => $value ) { 
         $woocommerce_wp_select_return .= '<option value="' . esc_attr( $key ) . '" ' . selected( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>'; 
    } 
 
     $woocommerce_wp_select_return .= '</select> '; 
 
    if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) { 
         $woocommerce_wp_select_return .= '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>'; 
    } 
 
     $woocommerce_wp_select_return .= '</p>'; 
	 
	 return  $woocommerce_wp_select_return;
} 
protected function woocommerce_wp_checkbox_return( $field ) { 
    global $thepostid, $post; 
	$woocommerce_wp_checkbox_return = '';
    $thepostid = empty( $thepostid ) ? $post->ID : $thepostid; 
    $field['class'] = isset( $field['class'] ) ? $field['class'] : 'checkbox'; 
    $field['style'] = isset( $field['style'] ) ? $field['style'] : ''; 
    $field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : ''; 
    $field['value'] = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true ); 
    $field['cbvalue'] = isset( $field['cbvalue'] ) ? $field['cbvalue'] : 'yes'; 
    $field['name'] = isset( $field['name'] ) ? $field['name'] : $field['id']; 
    $field['desc_tip'] = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false; 
 
    // Custom attribute handling 
    $custom_attributes = array(); 
 
    if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) { 
 
        foreach ( $field['custom_attributes'] as $attribute => $value ) { 
            $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"'; 
        } 
    } 
 
    $woocommerce_wp_checkbox_return .= '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"> 
        <label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>'; 
 
    if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) { 
         $woocommerce_wp_checkbox_return .= wc_help_tip( $field['description'] ); 
    } 
 
     $woocommerce_wp_checkbox_return .= '<input type="checkbox" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['cbvalue'] ) . '" ' . checked( $field['value'], $field['cbvalue'], false ) . '  ' . implode( ' ', $custom_attributes ) . '/> '; 
 
    if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) { 
         $woocommerce_wp_checkbox_return .= '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>'; 
    } 
 
     $woocommerce_wp_checkbox_return .= '</p>'; 
	 
	 return  $woocommerce_wp_checkbox_return;
}




function extra_metaBox_woo_gravity($gravity_form_data)
{
	  
	
	  
  $out.= self::woocommerce_wp_checkbox_return( array(
	'id' => 'gravityform-display_title',
	'label' => __( 'Display Title', 'wc_gf_addons' ),
	'value' => isset( $gravity_form_data['display_title'] ) && $gravity_form_data['display_title'] ? 'yes' : '') );

 $out.= self::woocommerce_wp_checkbox_return( array(
	'id' => 'gravityform-display_description',
	'label' => __( 'Display Description', 'wc_gf_addons' ),
	'value' => isset( $gravity_form_data['display_description'] ) && $gravity_form_data['display_description'] ? 'yes' : '') );

		
	if(!empty( $gravity_form_data['id'] ))
	{  
		
		$out .= '<div id="multipage_forms_data" class="gforms-panel panel woocommerce_options_panel">';
		$out .= '<h4>' . __( 'Multipage Forms', 'wc_gf_addons' ) .'</h4>';
		$out .= '<div class="options_group">';
		  
		$out.= self::woocommerce_wp_checkbox_return( array(
				'id' => 'gravityform-disable_anchor',
				'label' => __( 'Disable Gravity Forms Anchors', 'wc_gf_addons' ),
				'value' => isset( $gravity_form_data['disable_anchor'] ) ? $gravity_form_data['disable_anchor'] : '') );
       
        $out .= '</div></div>';
		
		
		
		
		
		
		
		
		$out .='<div id="price_labels_data" class="gforms-panel panel woocommerce_options_panel">';
		$out .= '<h4>'. __("Price Labels", "wc_gf_addons") .'</h4>';
		$out .= '<div class="options_group">';
			
		$out.= self::woocommerce_wp_checkbox_return( array(
				'id' => 'gravityform-disable_woocommerce_price',
				'label' => __( 'Remove WooCommerce Price?', 'wc_gf_addons' ),
				'value' => isset( $gravity_form_data['disable_woocommerce_price'] ) ? $gravity_form_data['disable_woocommerce_price'] : '') );

		$out.= self::woocommerce_wp_text_input_return( array('id' => 'gravityform-price-before', 'label' => __( 'Price Before', 'wc_gf_addons' ),
				'value' => isset( $gravity_form_data['price_before'] ) ? $gravity_form_data['price_before'] : '',
				'placeholder' => __( 'Base Price:', 'wc_gf_addons' ), 'description' => __( 'Enter text you would like printed before the price of the product.', 'wc_gf_addons' )) );

		$out.= self::woocommerce_wp_text_input_return( array('id' => 'gravityform-price-after', 'label' => __( 'Price After', 'wc_gf_addons' ),
				'value' => isset( $gravity_form_data['price_after'] ) ? $gravity_form_data['price_after'] : '',
				'placeholder' => __( '', 'wc_gf_addons' ), 'description' => __( 'Enter text you would like printed after the price of the product.', 'wc_gf_addons' )) );
			
		 $out.= '</div></div>';
		 
		 
		 
		 
		 
	   $out .= '<div id="total_labels_data" class="gforms-panel panel woocommerce_options_panel">';
       $out .='<h4> '. __("Total Calculations", "wc_gf_addons").'</h4>';
       $out .= '<div class="options_group">';
      if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
        $out .= self::woocommerce_wp_select_return(
          array(
              'id' => 'gravityform_use_ajax',
              'label' => __( 'Enable Dynamic Pricing?', 'wc_gf_addons' ),
              'value' => isset( $gravity_form_data['use_ajax'] ) ? $gravity_form_data['use_ajax'] : '',
              'options' => array('no' => 'No', 'yes' => 'Yes'),
              'description' => __( 'Enable Dynamic Pricing calculations if you are using Dynamic Pricing to modify the price of this product.', 'wc_gf_addons' )
          )
        );
      }
      $out.= '</div>';
      $out.= '<div class="options_group">';
      $out.= self::woocommerce_wp_checkbox_return( array(
          'id' => 'gravityform-disable_calculations',
          'label' => __( 'Disable Calculations?', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['disable_calculations'] ) ? $gravity_form_data['disable_calculations'] : '') );
      $out.= '</div>';
      $out.= '<div class="options_group">';
	  
      $out.= self::woocommerce_wp_checkbox_return( array(
          'id' => 'gravityform-disable_label_subtotal',
          'label' => __( 'Disable Subtotal?', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['disable_label_subtotal'] ) ? $gravity_form_data['disable_label_subtotal'] : '') );

      $out.= self::woocommerce_wp_text_input_return( array('id' => 'gravityform-label_subtotal', 'label' => __( 'Subtotal Label', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['label_subtotal'] ) && !empty( $gravity_form_data['label_subtotal'] ) ? $gravity_form_data['label_subtotal'] : 'Subtotal',
          'placeholder' => __( 'Subtotal', 'wc_gf_addons' ), 'description' => __( 'Enter "Subtotal" label to display on for single products.', 'wc_gf_addons' )) );
      $out.= '</div><div class="options_group">';
	  
      $out.= self::woocommerce_wp_checkbox_return( array(
          'id' => 'gravityform-disable_label_options',
          'label' => __( 'Disable Options Label?', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['disable_label_options'] ) ? $gravity_form_data['disable_label_options'] : '') );

      $out.= self::woocommerce_wp_text_input_return( array('id' => 'gravityform-label_options', 'label' => __( 'Options Label', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['label_options'] ) && !empty( $gravity_form_data['label_options'] ) ? $gravity_form_data['label_options'] : 'Options',
          'placeholder' => __( 'Options', 'wc_gf_addons' ), 'description' => __( 'Enter the "Options" label to display for single products.', 'wc_gf_addons' )) );
       $out.= '</div><div class="options_group">';
      $out.= self::woocommerce_wp_checkbox_return( array(
          'id' => 'gravityform-disable_label_total',
          'label' => __( 'Disable Total Label?', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['disable_label_total'] ) ? $gravity_form_data['disable_label_total'] : '') );

      $out.= self::woocommerce_wp_text_input_return( array('id' => 'gravityform-label_total', 'label' => __( 'Total Label', 'wc_gf_addons' ),
          'value' => isset( $gravity_form_data['label_total'] ) && !empty( $gravity_form_data['label_total'] ) ? $gravity_form_data['label_total'] : 'Total',
          'placeholder' => __( 'Total', 'wc_gf_addons' ), 'description' => __( 'Enter the "Total" label to display for single products.', 'wc_gf_addons' )) );
       $out.= '</div></div>';
		 
		 
		 
	}
	
return $out;
}
	
  function meta_box( $post ) {
    ?>
    
    
    <div id="gravityforms_data" class="panel woocommerce_options_panel">
      <h4><?php _e( 'General', 'wc_gf_addons' ); ?></h4>
      
      <?php
      $gravity_form_data = get_post_meta( $post->ID, '_gravity_form_data', true );
      $gravityform = NULL;
      if ( is_array( $gravity_form_data ) && isset( $gravity_form_data['id'] ) && is_numeric( $gravity_form_data['id'] ) ) {

        $form_meta = RGFormsModel::get_form_meta( $gravity_form_data['id'] );

        if ( !empty( $form_meta ) ) {
          $gravityform = RGFormsModel::get_form( $gravity_form_data['id'] );
        }
      }
      ?>
      <div class="options_group">
      
        <p class="form-field">
          <label for="gravityform-id"><?php _e( 'Choose Form', 'wc_gf_addons' ); ?></label>
          <?php
          echo '<select id="gravityform-id" class="gravityform_class_select"  name="gravityform-id"><option value="">' . __( 'None', 'wc_gf_addons' ) . '</option>';
          foreach ( RGFormsModel::get_forms() as $form ) {
            echo '<option ' . selected( $form->id, (isset( $gravity_form_data['id'] ) ? $gravity_form_data['id'] : 0 ) ) . ' value="' . esc_attr( $form->id ) . '">' . wptexturize( $form->title ) . '</option>';
          }
          echo '</select>';
          ?>
        </p>

        <?php
      

       
        ?>
      </div>

      <div class="options_group" style="padding: 0 9px;">
        <?php if ( !empty( $gravityform ) && is_object( $gravityform ) ) : ?>
		
          <h4><a href="<?php printf( '%s/admin.php?page=gf_edit_forms&id=%d', get_admin_url(), $gravityform->id ) ?>" class="edit_gravityform">Edit <?php echo $gravityform->title; ?> Gravity Form</a></h4>
		<div class="import_input" style="margin: 10px 0px; width: 20%;display: inline-block;vertical-align: top;">
		<button type="button" data-product_id="<?php the_ID();?>" data-gravityform_id="<?php echo $gravityform->id;?>" class="rules_export button button-primary button-medium">Export</button>
		</div>
		<div class="import-data import_input" style="margin: 10px 0px; width: 60%;display: inline-block;vertical-align: top;">
		
		<div class="import_input_btn all_import_data" style="display:none">
		<input type="text" name="rules_import" id="rules_import" value="">&nbsp;&nbsp;&nbsp;
		<button type="button" data-product_id="<?php the_ID();?>" data-gravityform_id="<?php echo $gravityform->id;?>" class="rules_selectfile button button-primary button-medium">Select File</button>
		<button type="button" data-product_id="<?php the_ID();?>" data-gravityform_id="<?php echo $gravityform->id;?>" class="rules_import_action button button-primary button-medium">Import</button>
		
		</div>
		
		<div class="import_input_btn only_btn_inner " >
		
		<button type="button" data-product_id="<?php the_ID();?>" data-gravityform_id="<?php echo $gravityform->id;?>" class=" button button-primary button-medium">Import</button>
		
		</div>
		</div>
		<?php endif; ?>
		
		
		
		<div class="gravityform_inport_export_response"></div>
      </div>
    </div>
    <!-- Added to add dynamic gravity fields for generating controller number -->
    <div id="gravityforms_data_response" class="panel woocommerce_options_panel">
        <?php 
      $gravity_rules_controls  = get_post_meta( $post->ID,'_gravity_form_rules',true);
      if((!empty($gravity_rules_controls) && isset($gravity_rules_controls)) && !empty($gravity_form_data['id']))
      {
        foreach(unserialize($gravity_rules_controls) as $loopcount=>$gravity_rules)
        {
			// echo "<pre>";
          // print_r($gravity_rules);
           $controlled_number =   json_decode($gravity_rules['control_number'],true);
           $selected_fields_rules = json_decode($gravity_rules['fields'],true);
           $controler_priority =  json_decode($gravity_rules['controler_priority'],true);   
           $gravity_form_data['loopCount'] = $loopcount;
          echo self::gravit_rules_output_html($gravity_form_data['id'],$post->ID,$controlled_number,$selected_fields_rules,$controler_priority,$gravity_rules,$gravity_form_data);
        }
      }
    ?>

      <div id="gravityforms_data_response_custom"></div>
      <div class="input_fields_container">
        <button class="add_more_button button button-primary button-medium" data-gravity_form="<?php echo !empty($gravity_form_data['id'])?$gravity_form_data['id']:''?>">Add More Fields</button>
        
      </div>
    </div>
    <!-- Ends here Added to add dynamic gravity fields for generating controller number -->
  

    

    

    
    
    <?php
  }
 
  private  function combine_arr($a, $b) 
  { 
    $acount = count($a); 
    $bcount = count($b); 
    $size = ($acount > $bcount) ? $bcount : $acount; 
    $a = array_slice($a, 0, $size); 
    $b = array_slice($b, 0, $size); 
    return array_combine($a, $b); 
  } 


  public function process_meta_box( $post_id, $post ) {
    global $woocommerce_errors;


    // Save gravity form as serialised array
    if ( isset( $_POST['gravityform-id'] ) && !empty( $_POST['gravityform-id'] ) ) {

      $product = null;
      if ( function_exists( 'get_product' ) ) {
        $product = get_product( $post_id );
      } else {
        $product = new WC_Product( $post_id );
      }

      if ( $product->product_type != 'variable' && empty( $product->price ) && ($product->price != '0' || $product->price != '0.00') ) {
        $woocommerce_errors[] = __( 'You must set a price for the product before the gravity form will be visible.  Set the price to 0 if you are performing all price calculations with the attached Gravity Form.', 'woocommerce' );
      }

      $gravity_form_data = array(
          'id' => $_POST['gravityform-id'],
          'display_title' => isset( $_POST['gravityform-display_title'] ) ? true : false,
          'display_description' => isset( $_POST['gravityform-display_description'] ) ? true : false,
          'disable_woocommerce_price' => isset( $_POST['gravityform-disable_woocommerce_price'] ) ? 'yes' : 'no',
          'price_before' => $_POST['gravityform-price-before'],
          'price_after' => $_POST['gravityform-price-after'],
          'disable_calculations' => isset( $_POST['gravityform-disable_calculations'] ) ? 'yes' : 'no',
          'disable_label_subtotal' => isset( $_POST['gravityform-disable_label_subtotal'] ) ? 'yes' : 'no',
          'disable_label_options' => isset( $_POST['gravityform-disable_label_options'] ) ? 'yes' : 'no',
          'disable_label_total' => isset( $_POST['gravityform-disable_label_total'] ) ? 'yes' : 'no',
          'disable_anchor' => isset( $_POST['gravityform-disable_anchor'] ) ? 'yes' : 'no',
          'label_subtotal' => $_POST['gravityform-label_subtotal'],
          'label_options' => $_POST['gravityform-label_options'],
          'label_total' => $_POST['gravityform-label_total'],
          'use_ajax' => isset( $_POST['gravityform_use_ajax'] ) ? $_POST['gravityform_use_ajax'] : 'no'
      );
      update_post_meta( $post_id, '_gravity_form_data', $gravity_form_data );
      
    if(isset($_POST['downloadbale_file_name']))
    {
      $countRules = 0;
      $gravity_form_rulesArray = array();
		//print_r($_POST['downloadbale_file_name']);
	//die();
      foreach($_POST['downloadbale_file_name'] as $downloadbale_file_nameDATA)
      {
        
        if($downloadbale_file_nameDATA['control_number']!= '')
        {
          $gravity_form_rulesArray[$countRules]['control_number'] = $downloadbale_file_nameDATA['control_number'];
          $tempData = str_replace("\\", "",$downloadbale_file_nameDATA['control_fields']);
          //echo "<pre>";print_r($tempData);        
          $gravity_form_rulesArray[$countRules]['fields'] = $tempData;
          $gravity_form_rulesArray[$countRules]['controler_priority'] = $downloadbale_file_nameDATA['priority_controller'];
        }
        
        $systemandusercombine = array();
        if(!empty($downloadbale_file_nameDATA['generate_controllerfilestext']))
        {
          $systemCombine =0;
          foreach($downloadbale_file_nameDATA['generate_controllerfilestext']['system_generate'] as $systemKey=>$systme )
          {
          
            $systemandusercombine[$systemCombine]['system_generate'] = $systme;
            $systemandusercombine[$systemCombine]['usermodify'] = $downloadbale_file_nameDATA['generate_controllerfilestext']['usermodify'][$systemKey]; 
            $systemCombine ++;
          }
          $gravity_form_rulesArray[$countRules]['generate_controllerfilestext'] = $systemandusercombine;
        }
        
        
        $gravity_form_rulesArray[$countRules]['gfuser_settings'] = $downloadbale_file_nameDATA['gfuser_settings'];
        $gravity_form_rulesArray[$countRules]['useraddcontrol_number'] = $downloadbale_file_nameDATA['useraddcontrol_number'];
        
        $countRules++;
      }

     
      update_post_meta( $post_id,'_gravity_form_rules',serialize($gravity_form_rulesArray));
    }else
	{
      update_post_meta( $post_id,'_gravity_form_rules','');
    }
    
    
    if(isset($_POST['generate_controllerfilestext']))
    {
      $countRulesindex = 0;
      $gravity_form_rulesCustom = array();
      foreach($_POST['generate_controllerfilestext'] as $generate_controllerfilestext)
      {
        
        if($generate_controllerfilestext[0]!= '' )
        {
          $gravity_form_rulesCustom[$countRulesindex]['control_number_useredit'] = $generate_controllerfilestext[1];
          $gravity_form_rulesCustom[$countRulesindex]['control_number_main'] = $generate_controllerfilestext[0];
        }
  
        $countRulesindex++;
      } 
      
      update_post_meta( $post_id,'_gravity_form_rules_usereditdata',serialize($gravity_form_rulesCustom));
    }else
	{
      update_post_meta( $post_id,'_gravity_form_rules_usereditdata','');
    }
    
    } else
	{
      delete_post_meta( $post_id, '_gravity_form_data' );
    }
  }

}
new custom_WC_GFPA_Admin_Controller();