<?php

require_once('./Customizing/global/plugins/Services/Calendar/AppointmentCustomModal/UnibeCalendarCustomModal/vendor/autoload.php');

/**
 * Class ilUnibeCalendarCustomModalPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilUnibeCalendarCustomModalPlugin extends ilAppointmentCustomModalPlugin {
	/**
	 * ilCalendarCategory []
	 */
	protected $categories = [];

	/**
	 * @return string
	 */
	public final function getPluginName() {
		return "UnibeCalendarCustomModal";
	}


	/**
	 * @return bool
	 */
	private function isSession() {
		$appointment = $this->getAppointment();

		$cat_id = ilCalendarCategoryAssignments::_lookupCategory($appointment->getEntryId());
		$cat = ilCalendarCategory::getInstanceByCategoryId($cat_id);

		return $cat->getObjType() === "sess";
	}


	/**
	 * @return string
	 */
	public function replaceContent() {
		return "";
	}


	/**
	 * @return string
	 */
	public function addExtraContent() {
		global $DIC;

		if ($this->isSession() && $this->checkWriteAccess()) {
			$f = $DIC->ui()->factory();
			$r = $DIC->ui()->renderer();

			return $r->render($f->dropzone()
					->file()
					->standard($this->getUploadURL())
					->withUserDefinedFileNamesEnabled(true)
					->withAdditionalOnLoadCode(function($id) {
						return "il.Unibe.customizeWrapper($id)";
					})
					->withUploadButton($f->button()->standard('Upload', '')));

		}
	}


	/**
	 * @param \ilInfoScreenGUI $a_info
	 *
	 * @return \ilInfoScreenGUI
	 */
	public function infoscreenAddContent(ilInfoScreenGUI $a_info) {
		global $DIC;


		$files_property = null;
		foreach($a_info->section as $section_key => $section){
			foreach($section['properties'] as $property_key => $property){
				if($property['name'] == 'Files'){
					$files_property = $a_info->section[$section_key]['properties'][$property_key];
					$a_info->section[$section_key]['properties'][$property_key] = null;
				}
			}
		}

		$event_items = (ilObjectActivation::getItemsByEvent($this->getCategory()->getObjId()));

		$file_html = "";
		$renderer = $DIC->ui()->renderer();
		$factory = $DIC->ui()->factory();

		if (count($event_items)) {
			foreach ($event_items as $item) {
				if ($item['type'] == "file") {
					$file = new ilObjFile($item['ref_id']);
					//var_dump($file);
					$file_name =  $file->getFileName();
					$href = ilLink::_getStaticLink($file->getRefId(), "file", true,"download");
					$file_link = $renderer->render($factory->button()->shy($file->getTitle(), $href));
					$delete_link = "";
					if($this->checkWriteAccess()){
						$delete_action = (new ilUnibeFileHandlerGUI())->buildDeleteAction($this->getCategory()->getObjId(),$file->getRefId());
						$delete_link = "<a onclick=$delete_action style='float: right;'><span class='glyphicon glyphicon-trash' aria-hidden='true'></span></a>";
					}

					$file_html .= "<div class='il-unibe-file'>$file_link$delete_link</br></div>";
				}
			}
			$a_info->addSection("Ressourcen");

			$a_info->addProperty("Dateien",$file_html);
		}



		return $a_info;

	}


	/**
	 * @param ilToolbarGUI $a_toolbar
	 *
	 * @return ilToolbarGUI
	 */
	public function toolbarAddItems(ilToolbarGUI $a_toolbar) {


		return $a_toolbar;
	}


	/**
	 * @return ilToolbarGUI or empty
	 */
	public function toolbarReplaceContent() {
		return null;
	}


	/**
	 * @param string $title
	 *
	 * @return string
	 */
	public function editModalTitle($title) {
		return $title;
	}


	/**
	 * @return \ilCalendarCategory
	 */
	private function getCategory(): \ilCalendarCategory {
		$entry_id = $this->getAppointment()->getEntryId();
		if(! array_key_exists($entry_id, $this->categories)){
			$cat_id = ilCalendarCategoryAssignments::_lookupCategory($entry_id);
			$this->categories[$this->getAppointment()->getEntryId()] = ilCalendarCategory::getInstanceByCategoryId($cat_id);
		}
		return $this->categories[$entry_id];
	}

	/**
	 * @return bool
	 */
	public function checkWriteAccess(){
		global $DIC;

		$system = $DIC->rbac()->system();

		$ref_id = array_pop(ilObject::_getAllReferences($this->getCategory()->getObjId()));

		return $system->checkAccess("write",$ref_id);

	}


	/**
	 * @return string
	 */
	private function getUploadURL(): string {
		return (new ilUnibeFileHandlerGUI())->buildUploadURL($this->getCategory()->getObjId());
	}
}
