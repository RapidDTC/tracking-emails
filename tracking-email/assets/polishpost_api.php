<?php
/**
 *
 * @package Polish Post API
 * @author RapidDev | Polish technology company
 * @copyright Copyright (c) 2018, RapidDev
 *
 * @link https://github.com/rapiddev/polishpost-php
 * @license https://github.com/rapiddev/polishpost-php/blob/master/LICENSE
 *
 * This file is part of the Polish Post tracking API.
 *
 * More info about API and documentation can be found on:
 * http://www.poczta-polska.pl/webservices/
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

	/**
	*
	* PolishPostAPI
	*
	* @author   Leszek Pomianowski <https://rapiddev.pl>
	* @version  $Id: polishpost_api.php,v 0.20 2018/08/10
	* @access   public
	*/
	if (!class_exists('PolishPostAPI')) {
		class PolishPostAPI
		{
			/**
			* Package number
			* @var string
			* @access public
			*/
			public $package;

			/**
			* Data for the request, username
			* @var string
			* @access private
			*/
			private $username;

			/**
			* Data for the request, password
			* @var string
			* @access private
			*/
			private $password;

			/**
			* Data for the request, URL
			* @var string
			* @access private
			*/
			private $request_url;

			/**
			* The values passed to the request
			* @var string
			* @access private
			*/
			private $request_body;

			/**
			* ID of the WSDL request function
			* @var string
			* @access private
			*/
			private $request_urn;

			/**
			* Result of curl
			* @var string
			* @access private
			*/
			private $parsed_xml = NULL;

			/**
			* constructor
			*
			* @param string $username in most cases the default should work
			* @param string $password in most cases the default should work
			*/
			public function __construct($package = 'testp0', $username = 'sledzeniepp', $password = 'PPSA')
			{
				$this->package = $package;
				$this->username = $username;
				$this->password = $password;
			}

			/**
			* get_package
			*
			* @return   array    Correct data table
			* @access   public
			*/
			public function get_package()
			{
				$this->request_url = 'http://tt.poczta-polska.pl/Sledzenie/services/Sledzenie.SledzenieHttpSoap11Endpoint/';
				$this->request_urn = 'sprawdzPrzesylkePl';
				$this->request_body = '<ns1:numer>'.$this->package.'</ns1:numer>';

				if ($this->parsed_xml === NULL) {
					$this->parsed_xml = self::parse_xml(self::curl());
				}
				
				return $this->parsed_xml;
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
				if (isset($package['Body']['sprawdzPrzesylkePlResponse']['return']['danePrzesylki']['zdarzenia']['zdarzenie']))
				{
					return $package['Body']['sprawdzPrzesylkePlResponse']['return']['danePrzesylki']['zdarzenia']['zdarzenie'];
				}
				else
				{
					return NULL;
				}
			}

			/**
			* get_last_event
			*
			* @return   array    Correct data table
			* @access   public
			*/
			public function get_last_event()
			{
				$events = self::get_events();

				if ($events !== NULL)
				{
					return end($events);
				}
			}

			/**
			* get_posting_date
			*
			* @return   string    Correct data
			* @access   public
			*/
			public function get_posting_date()
			{
				$package = self::get_package();

				if (isset($package['Body']['sprawdzPrzesylkePlResponse']['return']['danePrzesylki']['dataNadania']))
				{
					return $package['Body']['sprawdzPrzesylkePlResponse']['return']['danePrzesylki']['dataNadania'];
				}
				else
				{
					return NULL;
				}
			}

			/**
			* parse_xml
			*
			* @return  array   parsed XML data
			* @param   string  $xml_data xml data is prepared for processing and then changed from object to table
			* @access  private
			*/
			private function parse_xml($xml_data)
			{
				if (strpos($xml_data, 'soapenv:') !== FALSE && function_exists('simplexml_load_string'))
				{
					return json_decode(json_encode(simplexml_load_string(str_ireplace(['soapenv:', 'ax21:', 'ns:', ':soapenv', 'xsi:'], '', $xml_data))),1);
				}
				else
				{
					return NULL;
				}
			}

			/**
			* curl
			*
			* @return   string   XML data as string
			* @access   private
			*/
			private function curl()
			{
				$XML_REQUEST = '<?xml version="1.0" encoding="UTF-8"?><SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://sledzenie.pocztapolska.pl" xmlns:ns2="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><SOAP-ENV:Header><ns2:Security SOAP-ENV:mustUnderstand="1"><ns2:UsernameToken><ns2:Username>'.$this->username.'</ns2:Username><ns2:Password>'.$this->password.'</ns2:Password></ns2:UsernameToken></ns2:Security></SOAP-ENV:Header><SOAP-ENV:Body><ns1:'.$this->request_urn.'>'.$this->request_body.'</ns1:'.$this->request_urn.'></SOAP-ENV:Body></SOAP-ENV:Envelope>';
				$SOAP_CURL = curl_init();
				curl_setopt($SOAP_CURL, CURLOPT_URL, $this->request_url);
				curl_setopt($SOAP_CURL, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($SOAP_CURL, CURLOPT_POST, TRUE);
				curl_setopt($SOAP_CURL, CURLOPT_POSTFIELDS, $XML_REQUEST);
				curl_setopt($SOAP_CURL, CURLOPT_HTTPHEADER,array('Connection: Keep-Alive','User-Agent: PHP-SOAP/7.1.1','Content-Type: text/xml; charset=utf-8','SOAPAction: "urn:'.$this->request_urn.'"','Content-length: '.strlen($XML_REQUEST)));
				$result = curl_exec($SOAP_CURL);
				if (curl_error($SOAP_CURL))
				{
					$error_msg = curl_error($SOAP_CURL);
				}
				curl_close($SOAP_CURL);
				if (isset($error_msg))
				{
					return $error_msg;
				}
				else
				{
					return $result;
				}
			}
		}
	}
?>