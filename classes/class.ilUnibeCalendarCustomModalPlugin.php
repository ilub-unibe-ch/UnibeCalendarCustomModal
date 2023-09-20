<?php

declare(strict_types=1);

use ILIAS\DI\Container;

/**
 * Class ilUnibeCalendarCustomModalPlugin
 *
 * @author Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 */
class ilUnibeCalendarCustomModalPlugin extends ilAppointmentCustomModalPlugin {

    /**
     * ilCalendarCategory []
     */
    protected array $categories = [];
    protected Container $dic;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        parent::__construct($db, $component_repository, $id);
        global $DIC;
        $this->dic = $DIC;
    }

    public final function getPluginName(): string {
        return "UnibeCalendarCustomModal";
    }


    private function isSession() :bool {
        return $this->getCategory()->getObjType() === "sess";
    }


    public function checkWriteAccess(): bool{


        $system = $this->dic->rbac()->system();
        $ref_array = ilObject::_getAllReferences($this->getCategory()->getObjId());
        $ref_id = (int)array_pop($ref_array);

        return $system->checkAccess("manage_materials",$ref_id,"sess");

    }


    public function replaceContent(): string{
        return "";
    }



    public function addExtraContent(): string {

        if ($this->isSession() && $this->checkWriteAccess()) {
            $f = $this->dic->ui()->factory();
            $r = $this->dic->ui()->renderer();

            return $r->render($f->dropzone()
                ->file()
                ->standard("title2", "", $this->getUploadURL(), $f->input()->field()->file(new ilUnibeFileHandlerGUI(), "Upload", "Drop files here"))
                ->withAdditionalOnLoadCode(function($id) {
                    return "il.Unibe.customizeWrapper($id)";
                })
                ->withUploadButton($f->button()->standard('Upload', '#')));

        }

        return "";

    }

    /**
     * @throws ilDatabaseException
     */
    public function infoscreenAddContent(ilInfoScreenGUI $a_info): ilInfoScreenGUI {


        $renderer = $this->dic->ui()->renderer();
        $factory = $this->dic->ui()->factory();

        $section = $a_info->getSection();
        foreach($section as $section_key => $a_section){
	        if(is_array($a_section['properties'])){
		        foreach($a_section['properties'] as $property_key => $property){
			        if($property['name'] == 'Dozierende'){
				        $section[$section_key]['properties'][$property_key]['value'] = $this->getMetaDataValueByTitle('Dozierende');
			        }
			        if($property['name'] == 'Dateien'){
				        $section[$section_key]['properties'][$property_key]['value'] = null;
			        }
			        if($property['name'] == 'Links'){
				        $section[$section_key]['properties'][$property_key]['value'] = $this->getMetaDataValueByTitle('Links');
			        }
                    if($property['name'] == 'Karte'){
                        $js_component = $factory->legacy("")->withOnLoadCode(function($id){
                            return "il.Unibe.loadMap('$id')";
                        });
                        $new_content = $section[$section_key]['properties'][$property_key]['value'].$renderer->renderAsync($js_component);
                        $section[$section_key]['properties'][$property_key]['value']  = $new_content;
                    }
		        }
	        }
        }
        $a_info->setSection($section);

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
                    $file = new ilObjFile((int)$item['ref_id']);
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
            $this->dic->ctrl()->setParameterByClass("ilObjPluginDispatchGUI","ref_id",$podcast);
		    $podcas_link = $this->dic->ctrl()->getLinkTargetByClass(["ilObjPluginDispatchGUI","ilObjOpenCastGUI","xoctEventGUI"]);
		    $a_info->addProperty("Podcasts",$this->dic->ui()->renderer()->render($this->dic->ui()->factory()->link()->standard("Alle Podcasts der Veranstaltung",$podcas_link)));
	    }

        return $a_info;

    }

    protected function parentCoursePodcast(): int{


        $ref_array = ilObject::_getAllReferences($this->getCategory()->getObjId());
        $ref_id = array_pop($ref_array);
		if(!empty($ref_id)) {
			$parent_ref_id = $this->dic->repositoryTree()->getParentId($ref_id);
			$children = $this->dic->repositoryTree()->getChildsByType($parent_ref_id, "xoct");


			foreach ($children as $child) {
				if ($this->dic->rbac()->system()->checkAccess("read", $child["ref_id"])) {
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
	protected function getMetaDataValueByTitle(string $title): string{

		$obj_id = $this->getCategory()->getObjId();
		$query = "SELECT val.value
			FROM adv_md_values_ltext as val
			INNER JOIN adv_mdf_definition as def ON  val.field_id = def.field_id
			WHERE def.title = '$title' AND val.obj_id = $obj_id";
		$row = $this->dic->database()->query($query)->fetchRow();

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
    public function toolbarAddItems(ilToolbarGUI $a_toolbar): ilToolbarGUI {


        return $a_toolbar;
    }



    public function toolbarReplaceContent(): ?ilToolbarGUI{
        return null;
    }


    /**
     * @param string $current_title
     *     not yet properly typed in parent class
     */
    public function editModalTitle($current_title): string {
        return $current_title;
    }


    private function getCategory(): ilCalendarCategory {
        $entry_id = $this->getAppointment()->getEntryId();
        if(!array_key_exists($entry_id, $this->categories)){
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
