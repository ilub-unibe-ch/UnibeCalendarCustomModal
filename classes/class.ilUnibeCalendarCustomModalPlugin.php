<?php
include_once("./Services/Calendar/classes/class.ilAppointmentCustomModalPlugin.php");

/**
 * Class ilUnibeCalendarCustomModalPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilUnibeCalendarCustomModalPlugin extends ilAppointmentCustomModalPlugin {

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
		global $DIC;

		if ($this->isSession()) {
			$f = $DIC->ui()->factory();
			$r = $DIC->ui()->renderer();

			return $r->render($f->dropzone()->file()->standard("#", $f->legacy("TRALLALA")));
		}

		return "";
	}


	/**
	 * @return string
	 */
	public function addExtraContent() {
		global $DIC;

		if ($this->isSession()) {
			$f = $DIC->ui()->factory();
			$r = $DIC->ui()->renderer();

			return $r->render($f->dropzone()
			                    ->file()
			                    ->standard("#")); // The seems ro be a JS problem within the Modal
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
}
