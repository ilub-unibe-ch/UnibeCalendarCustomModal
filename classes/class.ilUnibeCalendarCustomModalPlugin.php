<?php

require_once('./Customizing/global/plugins/Services/Calendar/AppointmentCustomModal/UnibeCalendarCustomModal/vendor/autoload.php');

/**
 * Class ilUnibeCalendarCustomModalPlugin
 *
 * @author Timon Amstutz <timon.amstutz@ilub.unibe.ch>
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
        return $this->getCategory()->getObjType() === "sess";
    }

    /**
     * @return bool
     */
    public function checkWriteAccess(){
        global $DIC;

        $system = $DIC->rbac()->system();

        $ref_id = array_pop(ilObject::_getAllReferences($this->getCategory()->getObjId()));

        return $system->checkAccess("manage_materials",$ref_id,"sess");

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
     * @param ilInfoScreenGUI $a_info
     * @return ilInfoScreenGUI|mixed
     * @throws ilDatabaseException
     */
    public function infoscreenAddContent(ilInfoScreenGUI $a_info) {
        global $DIC;

        $renderer = $DIC->ui()->renderer();
        $factory = $DIC->ui()->factory();

        $files_property = null;
        foreach($a_info->section as $section_key => $section){
	        if(is_array($section['properties'])){
		        foreach($section['properties'] as $property_key => $property){
			        if($property['name'] == 'Dozierende'){
				        $a_info->section[$section_key]['properties'][$property_key]['value'] = $this->getMetaDataValueByTitle('Dozierende');
			        }
			        if($property['name'] == 'Files'){
				        $a_info->section[$section_key]['properties'][$property_key] = null;
			        }
			        if($property['name'] == 'Links'){
				        $a_info->section[$section_key]['properties'][$property_key]['value'] = $this->getMetaDataValueByTitle('Links');
			        }
                    if($property['name'] == 'Karte'){
                        $js_component = $factory->legacy("")->withOnLoadCode(function($id){
                            return "il.Unibe.loadMap('$id')";
                        });
                        $new_content = $a_info->section[$section_key]['properties'][$property_key]['value'].$renderer->renderAsync($js_component);
                        $a_info->section[$section_key]['properties'][$property_key]['value']  = $new_content;
                    }
		        }
	        }
        }

        $event_items = (ilObjectActivation::getItemsByEvent($this->getCategory()->getObjId()));

        $file_html = "";


        $has_files = count($event_items);
        $podcast = $this->parentCoursePodcast();

        if($has_files|| $podcast){
	        $a_info->addSection("Ressourcen");
        }
        if ($has_files) {
            foreach ($event_items as $item) {
                if ($item['type'] == "file") {
                    $file = new ilObjFile($item['ref_id']);
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

            $a_info->addProperty("Dateien",$file_html);
        }
	    if ($podcast) {
        	$DIC->ctrl()->setParameterByClass("ilObjPluginDispatchGUI","ref_id",$podcast);
		    $podcas_link = $DIC->ctrl()->getLinkTargetByClass(["ilObjPluginDispatchGUI","ilObjOpenCastGUI","xoctEventGUI"]);
		    $a_info->addProperty("Podcasts",$DIC->ui()->renderer()->render($DIC->ui()->factory()->link()->standard("Alle Podcasts der Veranstaltung",$podcas_link)));
	    }

        return $a_info;

    }

	/**
	 * @return int
	 */
    protected function parentCoursePodcast(){
	    /**
	     * @var \ILIAS\DI\Container
	     */
	    global $DIC;

	    $obj_id = $this->getCategory()->getObjId();
	    $ref_id = array_pop(ilObject::_getAllReferences($obj_id));
		if(!empty($ref_id)) {
			$parent_ref_id = $DIC->repositoryTree()->getParentId($ref_id);
			$children = $DIC->repositoryTree()->getChildsByType($parent_ref_id, "xoct");


			foreach ($children as $child) {
				if ($DIC->rbac()->system()->checkAccess("read", $child["ref_id"])) {
					return $child["ref_id"];
				}
			}
		}

    	return 0;
    }

	/**
	 * @param string $title
	 * @return string
	 * @throws ilDatabaseException
	 */
	protected function getMetaDataValueByTitle(string $title){
		global $DIC;

		$obj_id = $this->getCategory()->getObjId();
		$query = "SELECT val.value
			FROM adv_md_values_text as val
			INNER JOIN adv_mdf_definition as def ON  val.field_id = def.field_id
			WHERE def.title = '$title' AND val.obj_id = $obj_id";
		$row = $DIC->database()->query($query)->fetchRow();

		if($row['value']){

		    $fix_links = str_replace("< /a>","</a>",str_replace("< a href","<a href",$row['value']));
			return $fix_links;
		}
		return "";

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
     * @return string
     */
    private function getUploadURL(): string {
        return (new ilUnibeFileHandlerGUI())->buildUploadURL($this->getCategory()->getObjId());
    }
}
