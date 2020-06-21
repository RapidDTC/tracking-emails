<?php
/**
 * @package InPost PHP API
 * @author RapidDev | Leszek Pomianowski
 * @copyright Copyright (c) 2018-2020, RapidDev
 *
 * This file is part of the RDEV Tracking API.
 *
 * https://docs.inpost24.com/
 */

	/**
	*
	* RDEV_InPost
	*
	* @author   Leszek Pomianowski <https://rdev.cc/>
	* @version  1.0.0
	* @access   public
	*/
	if (!class_exists('RDEV_InPost'))
	{
		class RDEV_InPost
		{
			/**
			* Package status
			* @var string
			* @access public
			*/
			public $status;

			/**
			* Package number
			* @var string
			* @access public
			*/
			public $package;

			/**
			* RAW data
			* @var parsed json
			* @access public
			*/
			public $raw_data;

			/**
			* constructor
			*
			* @param string $username in most cases the default should work
			* @param string $password in most cases the default should work
			*/
			public function __construct($package = '') //662010348071436124727919
			{
				$this->package = $package;
			}

			/**
			* get_package
			*
			* @return   array    Correct data table
			* @access   public
			*/
			public function get_package()
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api-shipx-pl.easypack24.net/v1/tracking/' . $this->package);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);

				return json_decode($result, true);
			}

			/**
			* get_events
			*
			* @return   array    Correct data table
			* @access   public
			*/
			public function get_events()
			{
				$package = self::get_package();

				if(isset($package['error']))
				{
					if($package['error'] == 'resource_not_found')
					{
						$this->status = false;
						return array();
					}
				}

				if(isset($package['tracking_number']))
				{
					$this->status = true;
					return $package;
				}
			}
		}
	}

?>