<?php
    require(__META_CONTROLS_GEN__ . '/NarroSuggestionDataGridGen.class.php');

    /**
    * This is the "Meta" DataGrid customizable subclass for the List functionality
    * of the NarroSuggestion class.  This code-generated class extends
    * from the generated Meta DataGrid class which contains a QDataGrid class which
    * can be used by any QForm or QPanel, listing a collection of NarroSuggestion
    * objects.  It includes functionality to perform pagination and sorting on columns.
    *
    * To take advantage of some (or all) of these control objects, you
    * must create an instance of this DataGrid in a QForm or QPanel.
    *
    * This file is intended to be modified.  Subsequent code regenerations will NOT modify
    * or overwrite this file.
    *
    * @package Narro
    * @subpackage MetaControls
    *
    */
    class NarroSuggestionDataGrid extends NarroSuggestionDataGridGen {
            // Specify a CssClass
        protected $strCssClass = 'datagrid';

        // Let's Show a Footer
        protected $blnShowFooter = true;

        protected $blnAlwaysShowPaginator = true;

        // Let's define the footer to be to display our alternate paginator
        // We'll use the already built-in GetPaginatorRowHtml, sending in our ALTERNATE paginator, to help with the rendering
        protected function GetFooterRowHtml() {
            QApplication::ExecuteJavaScript(sprintf('highlight_datagrid(\'%s\');', $this->ControlId));
            if ($this->objPaginatorAlternate)
                return sprintf('<tr><td colspan="%s">%s</td></tr>', count($this->objColumnArray), $this->GetPaginatorRowHtml($this->objPaginatorAlternate));
        }

        ///////////////////////////
        // DataGrid Preferences
        ///////////////////////////

        // Feel free to specify global display preferences/defaults for all QDataGrid controls
        public function __construct($objParentObject, $strControlId = null) {
            try {
                parent::__construct($objParentObject, $strControlId);
            } catch (QCallerException  $objExc) {
                $objExc->IncrementOffset();
                throw $objExc;
            }
           


        }

        protected function GetPaginatorRowHtml($objPaginator) {
            if (!$this->blnAlwaysShowPaginator && $this->objPaginator->TotalItemCount < $this->objPaginator->ItemsPerPage)
                return false;
            else
                return parent::GetPaginatorRowHtml($objPaginator);
        }

        public function GetEndScript() {
            QApplication::ExecuteJavaScript(sprintf('highlight_datagrid("%s");', $this->ControlId));
            return parent::GetEndScript();
        }
        
        public function colSuggestion_Render( NarroSuggestion $objNarroSuggestion ) {
            return $objNarroSuggestion->SuggestionValue;
        }
        
        public function colText_Render( NarroSuggestion $objNarroSuggestion ) {
            return
            str_replace(
                    		'?l=' . QApplication::$TargetLanguage->LanguageCode,
                        	'?l=' . $objNarroSuggestion->Language->LanguageCode,
            NarroLink::Translate(0, '', NarroTranslatePanel::SHOW_ALL, "'" . $objNarroSuggestion->Text->TextValue . "'", 0, 0, 10, 0, 0, NarroString::HtmlEntities($objNarroSuggestion->Text->TextValue))
            );
        }
        
        public function colLanguage_Render( NarroSuggestion $objNarroSuggestion ) {
            return t($objNarroSuggestion->Language->LanguageName);
        }
        
        public function colCreated_Render( NarroSuggestion $objNarroSuggestion ) {
            $objDateSpan = new QDateTimeSpan(time() - $objNarroSuggestion->Created->Timestamp);
            $strModifiedWhen = $objDateSpan->SimpleDisplay();
        
            return sprintf(t('%s ago'), $strModifiedWhen);
        }

        /////////////////////////
        // Public Properties: SET
        /////////////////////////
        public function __set($strName, $mixValue) {
            switch ($strName) {
                case 'Title':
                    try {
                        $this->strLabelForNoneFound = '&nbsp;' . sprintf('<b>%s</b>: %s', $mixValue, QApplication::Translate('%s found nothing.'));/**Translators: ignore %s */
                        $this->strLabelForOneFound = '&nbsp;' . sprintf('<b>%s</b>: %s', $mixValue, QApplication::Translate(' 1 %s found.'));/**Translators: ignore %s */
                        $this->strLabelForMultipleFound = '&nbsp;' . sprintf('<b>%s</b>: %s', $mixValue, QApplication::Translate(' %d %s found.'));/**Translators: ignore %s */
                        $this->strLabelForPaginated = '&nbsp;' . sprintf('<b>%s</b>: %s', $mixValue, QApplication::Translate('%s %d-%d of %d.'));/**Translators: ignore %s */
                        $this->strNoun = '';
                        $this->strNounPlural = '';

                        break;
                    } catch (QInvalidCastException $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }

                case 'AlwaysShowPaginator':
                    try {
                        $this->blnAlwaysShowPaginator = QType::Cast($mixValue, QType::Boolean);
                        break;
                    } catch (QInvalidCastException $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }

                default:
                    try {
                        parent::__set($strName, $mixValue);
                        break;
                    } catch (QCallerException $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
            }
        }
    }
?>