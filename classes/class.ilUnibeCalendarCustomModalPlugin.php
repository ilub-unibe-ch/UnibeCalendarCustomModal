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
			                    ->withUploadButton($f->button()->standard('Upload', '')));
		}

		return "";
	}


	/**
	 * @param \ilInfoScreenGUI $a_info
	 *
	 * @return \ilInfoScreenGUI
	 */
	public function infoscreenAddContent(ilInfoScreenGUI $a_info) {
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
		return (new ilUnibeUploadHandlerGUI())->buildUploadURL($this->getCategory()->getObjId());
	}
}
