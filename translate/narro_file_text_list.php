<?php
    /**
     * Narro is an application that allows online software translation and maintenance.
     * Copyright (C) 2008 Alexandru Szasz <alexxed@gmail.com>
     * http://code.google.com/p/narro/
     *
     * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
     * License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any
     * later version.
     *
     * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
     * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
     * more details.
     *
     * You should have received a copy of the GNU General Public License along with this program; if not, write to the
     * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
     */

    require_once('narro_text_list.php');
    class NarroFileTextListForm extends NarroTextListForm {

        protected $objNarroFile;


        protected function SetupNarroObject() {
            // Lookup Object PK information from Query String (if applicable)
            $intFileId = QApplication::QueryString('f');
            $intProjectId = QApplication::QueryString('p');
            if (($intFileId)) {
                $this->objNarroFile = NarroFile::Load(($intFileId));

                if (!$this->objNarroFile)
                    QApplication::Redirect(NarroLink::ProjectFileList($intProjectId));

            } else
                QApplication::Redirect(NarroLink::ProjectFileList($intProjectId));

            $this->objNarroProject = $this->objNarroFile->Project;
        }

        public function dtgNarroContextInfo_Actions_Render(NarroContextInfo $objNarroContextInfo, $intRowIndex) {
            if (QApplication::$objUser->hasPermission('Can suggest', $objNarroContextInfo->Context->ProjectId, QApplication::$Language->LanguageId) && QApplication::$objUser->hasPermission('Can vote', $objNarroContextInfo->Context->ProjectId, QApplication::$Language->LanguageId))
                $strText = t('Suggest / Vote');
            elseif (QApplication::$objUser->hasPermission('Can suggest', $objNarroContextInfo->Context->ProjectId, QApplication::$Language->LanguageId))
                $strText = t('Suggest');
            elseif (QApplication::$objUser->hasPermission('Can vote', $objNarroContextInfo->Context->ProjectId, QApplication::$Language->LanguageId))
                $strText = t('Vote');
            else
                $strText = t('Details');

            return NarroLink::ContextSuggest(
                        $this->objNarroFile->Project->ProjectId,
                        $objNarroContextInfo->ContextId,
                        $this->lstTextFilter->SelectedValue,
                        $this->lstSearchType->SelectedValue,
                        $this->txtSearch->Text,
                        $intRowIndex + (($this->dtgNarroContextInfo->PageNumber - 1) * $this->dtgNarroContextInfo->ItemsPerPage),
                        $this->dtgNarroContextInfo->TotalItemCount,
                        $this->dtgNarroContextInfo->SortColumnIndex,
                        $this->dtgNarroContextInfo->SortDirection,
                        $strText
                   );
        }

        public function lstTextFilter_Change() {
            QApplication::Redirect(NarroLink::FileTextList($this->objNarroFile->ProjectId, $this->objNarroFile->FileId, $this->lstTextFilter->SelectedValue, $this->lstSearchType->SelectedValue, $this->txtSearch->Text));
        }

        public function btnSearch_Click() {
            QApplication::Redirect(NarroLink::FileTextList($this->objNarroFile->ProjectId, $this->objNarroFile->FileId, $this->lstTextFilter->SelectedValue, $this->lstSearchType->SelectedValue, $this->txtSearch->Text));
        }


        protected function dtgNarroContextInfo_Bind() {
            // Because we want to enable pagination AND sorting, we need to setup the $objClauses array to send to LoadAll()

            $objCommonCondition = QQ::AndCondition(
                QQ::Equal(QQN::NarroContextInfo()->Context->FileId, $this->objNarroFile->FileId),
                QQ::Equal(QQN::NarroContextInfo()->LanguageId, QApplication::$Language->LanguageId),
                QQ::Equal(QQN::NarroContextInfo()->Context->Active, 1)
            );

            switch($this->lstSearchType->SelectedValue) {
                case NarroTextListForm::SEARCH_TEXTS:
                    $this->dtgNarroContextInfo->TotalItemCount = NarroContextInfo::CountByTextValue(
                        $this->txtSearch->Text,
                        $this->lstTextFilter->SelectedValue,
                        $objCommonCondition
                    );
                    break;
                case NarroTextListForm::SEARCH_SUGGESTIONS:
                    $this->dtgNarroContextInfo->TotalItemCount = NarroContextInfo::CountBySuggestionValue(
                        $this->txtSearch->Text,
                        $this->lstTextFilter->SelectedValue,
                        $objCommonCondition
                    );
                    break;
                case NarroTextListForm::SEARCH_CONTEXTS:
                    $this->dtgNarroContextInfo->TotalItemCount = NarroContextInfo::CountByContext(
                        $this->txtSearch->Text,
                        $this->lstTextFilter->SelectedValue,
                        $objCommonCondition
                    );
                    break;
            }

            // Setup the $objClauses Array
            $objClauses = array();

            // If a column is selected to be sorted, and if that column has a OrderByClause set on it, then let's add
            // the OrderByClause to the $objClauses array
            if ($objClause = $this->dtgNarroContextInfo->OrderByClause)
                array_push($objClauses, $objClause);

            // Add the LimitClause information, as well
            if ($objClause = $this->dtgNarroContextInfo->LimitClause)
                array_push($objClauses, $objClause);

            // Set the DataSource to be the array of all NarroContextInfo objects, given the clauses above
            switch($this->lstSearchType->SelectedValue) {
                case NarroTextListForm::SEARCH_TEXTS:
                    $this->dtgNarroContextInfo->DataSource = NarroContextInfo::LoadArrayByTextValue(
                        $this->txtSearch->Text,
                        $this->lstTextFilter->SelectedValue,
                        $this->dtgNarroContextInfo->LimitClause,
                        $this->dtgNarroContextInfo->OrderByClause,
                        $objCommonCondition
                    );
                    break;
                case NarroTextListForm::SEARCH_SUGGESTIONS:
                    $this->dtgNarroContextInfo->DataSource = NarroContextInfo::LoadArrayBySuggestionValue(
                        $this->txtSearch->Text,
                        $this->lstTextFilter->SelectedValue,
                        $this->dtgNarroContextInfo->LimitClause,
                        $this->dtgNarroContextInfo->OrderByClause,
                        $objCommonCondition
                    );
                    break;

                case NarroTextListForm::SEARCH_CONTEXTS:
                    $this->dtgNarroContextInfo->DataSource = NarroContextInfo::LoadArrayByContext(
                        $this->txtSearch->Text,
                        $this->lstTextFilter->SelectedValue,
                        $this->dtgNarroContextInfo->LimitClause,
                        $this->dtgNarroContextInfo->OrderByClause,
                        $objCommonCondition
                    );
                    break;
            }

            QApplication::ExecuteJavaScript('highlight_datagrid();');

        }

    }

    NarroFileTextListForm::Run('NarroFileTextListForm', 'templates/narro_file_text_list.tpl.php');
?>