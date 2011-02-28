<?php
/**
* @copyright	Copyright (C) 2007-2010 Hussfelt Consulting AB. All rights reserved.
* @license		GPL v2
* @example 		$file = fopen($filepath);
* 				$file = readfile($file);
* 				$ocr = new BOFRC( array( $file , false , "Y-m-d H:i:s" ) );
* 				print_r($ocr->returnData());
*/

/**
 * BGC OCR File Reader Class (BOFRC)
 *
 * Use to read eg. "INBETALNINGSSERVICE OCR2007-10-031.txt"
 * parse it to a php array and return.
 *
 * @author		Henrik Hussfelt <henrik@hussfelt.net>
 * @version     1.0
 */

class BOFRC{

	// PRIVATE VARS
	private $file = null;
	private $data = array();
	private $current_gironr = null;
	private $leading_zero = false;
	private $date_format = "Y-m-d";

	// PUBLIC VARS

	public function __construct( $options ){
		$this->file = $options[0];
		if($options[1]!="") $this->leading_zero = $options[1];
		if($options[2]!="") $this->date_format = $options[2];
		$this->ParseOrganizeData();
	}

	private function ParseOrganizeData(){
		$lines = explode("\n",$this->file);

		$data["RECIVER_POST"] = array();
		$data["TREATMENT_POST"] = array();
		$data["TRANSACTION_POST"] = array();
		$data["SUBTOTAL_POST"] = array();
		foreach ($lines as $line){
			list($pos1,$pos2,$pos3,$pos4) = preg_split("/[\s,]+/", $line);

			/**
			 * var $param may contain following:
			 * 00	=	OPENING POST
			 * 10	=	CUSTOMER POST
			 * 20	=	RECIVER POST
			 * 30	=	TREATMENT POST
			 * 40	=	TRANSACTION POST
			 * 50	=	SUBTOTAL POST
			 * 90	=	TOTAL POST
			*/
			switch ($pos1){
				/**
				 *
				*/
				case "00" :{
					// IF $pos4 != "" omorganisera
					if($pos4 != ""){
						$pos3 = date($this->date_format,mktime(0,0,0,substr($pos3,2,2),substr($pos3,4,2),substr($pos3,0,2)));
						$data["OPENING_POST"]=array($pos2,$pos3,$pos4);
					}
					else{
						$pos2 = date($this->date_format,mktime(0,0,0,substr($pos2,2,2),substr($pos2,4,2),substr($pos2,0,2)));
						$data["OPENING_POST"]=array("",$pos2,$pos3);
					}
					break;
				}
				/**
				 * NOT USED YET
				*/
				case "10" :{
					break;
				}
				case "20" :{
					$this->current_gironr = $pos2;
					$data["RECIVER_POST"][]=array($pos2);
					break;
				}
				case "30" :{
					$tmp_date=substr($pos2,(strlen($this->current_gironr)),6);
					$tmp_date=date($this->date_format,mktime(0,0,0,substr($tmp_date,2,2),substr($tmp_date,4,2),substr($tmp_date,0,2)));
					$data["TREATMENT_POST"][]=array(
					substr($pos2,0,strlen($this->current_gironr)),
					$tmp_date,
					($pos3 ? $pos3 : "")
					);
					break;
				}
				case "40" :{
					if(!$this->leading_zero) $amm = substr($pos2,12,13);
					else $amm = (int) $amm = substr($pos2,12,13);
					$data["TRANSACTION_POST"][$this->current_gironr][]=array(
					substr($pos2,0,11),
					$amm,
					($pos4 ? $pos3 : ""),
					($pos4 ? $pos4 : $pos3)
					);
					break;
				}
				case "50" :{
					$tmp_date=substr($pos2,(strlen($this->current_gironr)),6);
					$tmp_date=date($this->date_format,mktime(0,0,0,substr($tmp_date,2,2),substr($tmp_date,4,2),substr($tmp_date,0,2)));
					$nr_payments = substr($pos2,(strlen($this->current_gironr)+6),7);
					if($this->leading_zero) $nr_payments = (int) $nr_payments;
					$data["SUBTOTAL_POST"][$this->current_gironr][]=array(
					$this->current_gironr,
					$tmp_date,
					$nr_payments,
					$pos3
					);
					break;
				}
				case "90" :{
					$tmp_date=substr($pos2,0,6);
					$tmp_date=date($this->date_format,mktime(0,0,0,substr($tmp_date,2,2),substr($tmp_date,4,2),substr($tmp_date,0,2)));
					$nr_payments = substr($pos2,6,7);
					$amm_total = substr($pos2,13,15);
					if($this->leading_zero) $nr_payments = (int) $nr_payments;
					if($this->leading_zero) $amm_total = (int) $amm_total;
					$data["TOTAL_POST"]=array(
					$tmp_date,
					$nr_payments,
					$amm_total
					);
					break;
				}
			}
		}
		$this->data = $data;
	}

	public function returnData(){
		return $this->data;
	}
}
?>