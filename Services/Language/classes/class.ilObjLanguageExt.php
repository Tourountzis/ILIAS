<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

require_once "./Services/Language/classes/class.ilObjLanguage.php";

/**
* Class ilObjLanguageExt
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: class.ilObjLanguageExt.php $
*
* @ingroup ServicesLanguage
*/
class ilObjLanguageExt extends ilObjLanguage
{
	
	/**
	* Constructor
	*/
	function ilObjLanguageExt($a_id = 0, $a_call_by_reference = false)
	{
		$this->ilObjLanguage($a_id, $a_call_by_reference);
	}
	
	/**
	* Read and get the global language file as an object
	* @return   object  	global language file
	*/
	public function getGlobalLanguageFile()
	{
		require_once "./Services/Language/classes/class.ilLanguageFile.php";
		return ilLanguageFile::_getGlobalLanguageFile($this->key);
	}

	/**
	* Set the local status of the language
	*
	* @param   boolean       local status (true/false)
	*/
	public function setLocal($a_local = true)
	{
		if ($this->isInstalled())
		{
			if ($a_local == true)
			{
				$this->setDescription("installed_local");
			}
			else
			{
                $this->setDescription("installed");
			}
			$this->update();
		}
	}

	
	/**
	* Get the full language description
	*
	* @return   string       description
	*/
	public function getLongDescription()
	{
		return $this->lng->txt($this->desc);
	}
	
	/**
	* Get the language files path
	*
	* @return   string       path of language files folder
	*/
	public function getLangPath()
	{
		return $this->lang_path;
	}

	/**
	* Get the customized language files path
	*
	* @return   string       path of customized language files folder
	*/
	public function getCustLangPath()
	{
		return $this->cust_lang_path;
	}

	/**
	* Get all values from the database
	*
	* @param    array       list of modules
	* @param    string      search pattern
	* @return   array       module.separator.topic => value
	*/
	public function getAllValues($a_modules = array(), $a_pattern = '')
	{
		return self::_getValues($this->key, $a_modules, NULL, $a_pattern);
	}
	
	
	/**
	* Get only the changed values from the database
	* which differ from the original language file.
	*
	* @param    array       list of modules
	* @param    string      search pattern
	* @return   array       module.separator.topic => value
	*/
	public function getChangedValues($a_modules = array(), $a_pattern = '')
	{
		return self::_getValues($this->key, $a_modules, NULL, $a_pattern, 'changed');
	}


	/**
	* Get only the unchanged values from the database
	* which are equal to the original language file.
	*
	* @param    array       list of modules
	* @param    array       search pattern
	* @return   array       module.separator.topic => value
	*/
	public function getUnchangedValues($a_modules = array(), $a_pattern = '')
	{
		return self::_getValues($this->key, $a_modules, NULL, $a_pattern, 'unchanged');
	}


	/**
	* Get all values from the database
	* for wich the global language file has a comment.
	*
	* @param    array       list of modules
	* @param    array       search pattern
	* @return   array       module.separator.topic => value
	*/
	public function getCommentedValues($a_modules = array(), $a_pattern = '')
	{
		$global_file_obj = $this->getGlobalLanguageFile();
		$global_values = $global_file_obj->getAllValues();
		$local_values = self::_getValues($this->key, $a_modules, NULL, $a_pattern);

		$commented = array();
		foreach ($local_values as $key => $value)
		{
			if ($global_comments[$key] != "")
			{
				$commented[$key] = $value;
			}
		}
		return $commented;
	}
	

	/**
	* Import a language file into the ilias database
	*
	* @param    string  	handling of existing values
	*						('keepall','keeknew','replace','delete')
	*/
	public function importLanguageFile($a_file, $a_mode_existing = 'keepnew')
	{
		global $ilDB, $ilErr;

		// read the new language file
		require_once "./Services/Language/classes/class.ilLanguageFile.php";
		$import_file_obj = new ilLanguageFile($a_file);
		if (!$import_file_obj->read())
  		{
			$ilErr->raiseError($import_file_obj->getErrorMessage(),$ilErr->MESSAGE);
		}

		switch($a_mode_existing)
		{
			// keep all existing entries
			case 'keepall':
				$to_keep = $this->getAllValues();
				break;

			// keep existing online changes
			case 'keepnew':
				$to_keep = $this->getChangedValues();
				break;

 			// replace all existing definitions
			case 'replace':
			    $to_keep = array();
			    break;

           // delete all existing entries
			case 'delete':
				ilObjLanguage::_deleteLangData($this->key);
				$st = $ilDB->prepareManip("DELETE FROM lng_modules WHERE lang_key = ?",
					array("text"));
				$ilDB->execute($st, array($this->key));
				$to_keep = array();
				break;
				
			default:
			    return;
		}
		
		// process the values of the import file
		$to_save = array();
		foreach ($import_file_obj->getAllValues() as $key => $value)
		{
			if (!isset($to_keep[$key]))
			{
				$to_save[$key] = $value;
			}
		}
		self::_saveValues($this->key, $to_save);
	}

	/**
	* Get all modules of a language
	*
	* @access   static
	* @param    string      language key
	* @return   array       list of modules
	*/
	public static function _getModules($a_lang_key)
	{
		global $ilDB;
		
		$st = $ilDB->prepare("SELECT DISTINCT module FROM lng_data WHERE ".
			" lang_key = ? order by module", array("text"));
		$set = $ilDB->execute($st, array($a_lang_key));

		while ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$modules[] = $rec["module"];
		}
		return $modules;
	}

	/**
	* Get the translations of specified topics
	*
	* @access   static
	* @param    string      language key
	* @param    array       list of modules
	* @param    array       list of topics
	* @param    array       search pattern
	* @param    string      local change state ('changed', 'unchanged', '')
	* @return   array       module.separator.topic => value
	*/
	public static function _getValues($a_lang_key,
			$a_modules = array(), $a_topics = array(),
			$a_pattern = '', $a_state = '')
	{
		global $ilDB, $lng;

		$q = "SELECT * FROM lng_data WHERE".
			" lang_key = ?";
		$type_array[] = "text";
		$val_array[] = $a_lang_key;
		
		if (is_array($a_modules) && count($a_modules) > 0)
		{
			$q .= " AND ".$ilDB->in("module", $a_modules);
			$type_array = $ilDB->addTypesToArray($type_array,
				"text", count($a_modules));
			$val_array = array_merge($val_array, $a_modules);
		}
		if (is_array($a_topics) && count($a_topics) > 0)
		{
			$q .= " AND ".$ilDB->in("identifier", $a_topics);
			$type_array = $ilDB->addTypesToArray($type_array,
				"text", count($a_modules));
			$val_array = array_merge($val_array, $a_topics);

		}
		if ($a_pattern)
		{
			$q .= " AND ".$ilDB->like("value", "blob");
			$type_array[] = "blob";
			$val_array[] = "%".$a_pattern."%";
		}
		if ($a_state == "changed")
		{
			$q .= " AND local_change <> ? ";
			$type_array[] = "timestamp";
			$val_array[] = "0000-00-00 00:00:00";
		}
		if ($a_state == "unchanged")
		{
			$q .= " AND local_change = ? ";
			$type_array[] = "timestamp";
			$val_array[] = "0000-00-00 00:00:00";
		}
		$q .= " ORDER BY module, identifier";
		$st = $ilDB->prepare($q, $type_array);
		$set = $ilDB->execute($st, $val_array);

		$values = array();
		while ($rec = $set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$values[$rec["module"].$lng->separator.$rec["identifier"]] = $rec["value"];
		}
		return $values;
	}

	/**
	* Save a set of translation in the database
	*
	* @access   static
	* @param    string      language key
	* @param    array       module.separator.topic => value
	*/
	public static function _saveValues($a_lang_key, $a_values = array())
	{
		global $ilDB, $lng;
		
		if (!is_array($a_values))
		{
			return;
		}
		$save_array = array();
		$save_date = date("Y-m-d H:i:s", time());
		
		// read and get the global values
		require_once "./Services/Language/classes/class.ilLanguageFile.php";
		$global_file_obj = ilLanguageFile::_getGlobalLanguageFile($a_lang_key);
		$global_values = $global_file_obj->getAllValues();
		
		// save the single translations in lng_data
		foreach ($a_values as $key => $value)
		{
			$keys = explode($lng->separator, $key);
			if (count($keys) == 2)
			{
				$module = $keys[0];
				$topic = $keys[1];
				$save_array[$module][$topic] = $value;
				$local_change = $global_values[$key] == $value ?
								"0000-00-00 00:00:00" : $save_date;
			
				ilObjLanguage::replaceLangEntry($module, $topic,
					$a_lang_key, $value, $local_change);
			}
		}

		// save the serialized module entries in lng_modules
		foreach ($save_array as $module => $entries)
		{
			$st = $ilDB->prepare("SELECT * FROM lng_modules " .
				"WHERE lang_key = ? AND module = ?",
				array("text", "text"));
			$set = $ilDB->execute($st, array($a_lang_key, $module));
			$row = $ilDB->fetchAssoc($set);
			
			$arr = unserialize($row["lang_array"]);
			if (is_array($arr))
			{
				$entries = array_merge($arr, $entries);
			}
			ilObjLanguage::replaceLangModule($a_lang_key, $module, $entries);
		}
	}
} // END class.ilObjLanguageExt
?>
