<?php

namespace local_inveniordm\form;
defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class upload_form extends \moodleform {
    public function definition() {
        global $PAGE;
        $mform = $this->_form;
        $PAGE->requires->css(new \moodle_url('/local/inveniordm/styles/upload.css'));

        $backurl = new \moodle_url('/local/inveniordm/index.php');

        $mform->addElement('html', '
        <div class="upload-hero">
            <div class="upload-hero-content">
                <h1>
                    <i class="fa fa-upload"></i> 
                    Upload Resource</h1>
                <p>Upload learning materials and publish them to the repository.</p>
            </div>
            <div class="upload-hero-actions">
                <a href="'.$backurl->out(false).'" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> 
                    Back
                </a>
            </div>
        </div>
        ');

        $mform->addElement('html', '<div class="upload-form-wrapper">');

        $this->open_section($mform, 'generalinfo', 'fa-info-circle', 'General Information');
        $this->add_field_2col($mform, 'textarea', 'description', 'Description', ['rows' => 4]);
        $this->add_field_2col($mform, 'text', 'title', 'Title');
        $this->add_field_2col($mform, 'text', 'free_keyword', 'Keywords');
        $this->add_field_2col($mform, 'select', 'language', 'Language', ['vi' => 'Vietnamese', 'en' => 'English', 'fr' => 'French']);
        $this->add_field_2col($mform, 'select', 'documentary_type', 'Documentary Type', [
            'collection' => 'Collection',
            'dataset' => 'Dataset',
            'event' => 'Event',
            'image' => 'Image',
            'moving image' => 'Moving Image',
            'still image' => 'Still Image',
            'software' => 'Software',
            'physical object' => 'Physical Object',
            'interactive resource' => 'Interactive Resource',
            'service' => 'Service',
            'sound' => 'Sound',
            'text' => 'Text'
        ]);
        $this->close_section($mform);

        $this->open_section($mform, 'technicalinfo', 'fa-cogs', 'Technical Information');
        $this->add_field_2col($mform, 'select', 'format', 'Format', [
            'pdf' => 'PDF',
            'doc' => 'DOC',
            'docx' => 'DOCX',
            'xls' => 'XLS',
            'xlsx' => 'XLSX',
            'ppt' => 'PPT',
            'pptx' => 'PPTX',
            'odt' => 'ODT',
            'jpg' => 'JPG',
            'jpeg' => 'JPEG',
            'png' => 'PNG',
            'zip' => 'ZIP'
        ]);
        $this->add_field_2col($mform, 'text', 'location', 'Location URL');
        $this->close_section($mform);

        $this->open_section($mform, 'educationalinfo', 'fa-graduation-cap', 'Educational Information');
        $this->add_field_2col($mform, 'select', 'learning_resource_type', 'Learning Resource Type', [
            'exercise' => 'Exercise',
            'simulation' => 'Simulation',
            'demonstration' => 'Demonstration',
            'questionnaire' => 'Questionnaire',
            'exam' => 'Exam',
            'assessment' => 'Assessment',
            'experiment' => 'Experiment',
            'lesson' => 'Lesson',
            'animation' => 'Animation',
            'tutorial' => 'Tutorial',
            'glossary' => 'Glossary',
            'guide' => 'Guide',
            'reference material' => 'Reference Material',
            'methodology' => 'Methodology',
            'tool' => 'Tool',
            'teaching scenario' => 'Teaching Scenario',
            'self-assessment' => 'Self Assessment',
            'problem statement' => 'Problem Statement'
        ]);
        $this->add_field_2col($mform, 'select', 'target_audience', 'Target Audience', [
            'teacher' => 'Teacher',
            'author' => 'Author',
            'learner' => 'Learner',
            'administrator' => 'Administrator'
        ]);
        $this->add_field_2col($mform, 'select', 'educational_level', 'Educational Level', [
            'school education' => 'School Education',
            'higher education' => 'Higher Education',
            'vocational training' => 'Vocational Training',
            'primary education' => 'Primary Education',
            'secondary education' => 'Secondary Education',
            'bachelor\'s degree' => 'Bachelor Degree',
            'master\'s degree' => 'Master Degree',
            'doctorate' => 'Doctorate',
            'continuing education' => 'Continuing Education',
            'in-company training' => 'In-company Training'
        ]);
        $this->add_field_2col($mform, 'select', 'induced_activity', 'Induced Activity', [
            'facilitate' => 'Facilitate',
            'learn' => 'Learn',
            'collaborate' => 'Collaborate',
            'communicate' => 'Communicate',
            'cooperate' => 'Cooperate',
            'create' => 'Create',
            'exchange' => 'Exchange',
            'read' => 'Read',
            'observe' => 'Observe',
            'organise' => 'Organise',
            'produce' => 'Produce',
            'publish' => 'Publish',
            'research' => 'Research',
            'self-study' => 'Self Study',
            'practise' => 'Practise',
            'find out' => 'Find Out',
            'train' => 'Train',
            'simulate' => 'Simulate',
            'assess' => 'Assess'
        ]);
        $this->close_section($mform);

        $this->open_section($mform, 'rightsinfo', 'fa-balance-scale', 'Rights');
        $this->add_field_2col($mform, 'select', 'copyright', 'Copyright', ['yes' => 'Yes', 'no' => 'No']);
        $this->close_section($mform);

        $this->open_section($mform, 'classificationinfo', 'fa-tags', 'Classification');
        $this->add_field_2col($mform, 'select', 'objective', 'Objective', [
            'discipline' => 'Discipline',
            'concept' => 'Concept',
            'prerequisite' => 'Prerequisite',
            'learning objective' => 'Learning Objective',
            'accessibility restrictions' => 'Accessibility Restrictions',
            'educational level' => 'Educational Level',
            'proficiency level' => 'Proficiency Level',
            'security level' => 'Security Level',
            'competency' => 'Competency'
        ]);
        $this->add_field_2col($mform, 'text', 'taxon_entry', 'Taxon Entry');
        $this->close_section($mform);

        $this->open_section($mform, 'lifecycleinfo', 'fa-sync', 'Life Cycle');
        $this->add_field_2col($mform, 'select', 'role', 'Role', [
            'author' => 'Author',
            'publisher' => 'Publisher',
            'graphic designer' => 'Graphic Designer',
            'instructional designer' => 'Instructional Designer',
            'contributor' => 'Contributor',
            'subject matter expert' => 'Subject Matter Expert',
            'content provider' => 'Content Provider',
            'technical implementer' => 'Technical Implementer',
            'editor-in-chief' => 'Editor-in-Chief',
            'validator' => 'Validator'
        ]);
        $this->add_field_2col($mform, 'text', 'entity', 'Contributor');
        $this->add_field_2col($mform, 'date_selector', 'date', 'Date');
        $this->close_section($mform);

        $this->open_section($mform, 'relationinfo', 'fa-link', 'Relation');
        $this->add_field_2col($mform, 'select', 'relation', 'Relation', [
            'is a part of' => 'Is A Part Of',
            'contains' => 'Contains',
            'is a version of' => 'Is A Version Of',
            'requires' => 'Requires',
            'is required by' => 'Is Required By',
            'is associated with' => 'Is Associated With',
            'is based on' => 'Is Based On',
            'is a prerequisite for' => 'Is A Prerequisite For'
        ]);
        $this->close_section($mform);

        $this->open_section($mform, 'metainfo', 'fa-database', 'Metadata');
        $this->add_field_2col($mform, 'select', 'metadata_accessibility', 'Metadata Accessibility', [
            'public access' => 'Public Access',
            'restricted access' => 'Restricted Access',
            'read-only' => 'Read Only'
        ]);
        $this->close_section($mform);

        $this->open_section($mform, 'fileinfo', 'fa-file', 'Upload Resource File');
        $mform->addElement('html', '<div class="upload-field-full file-upload-wrapper">');
        $mform->addElement('filepicker', 'resourcefile', '');
        $mform->addElement('html', '</div>');
        $this->close_section($mform);

        $mform->addElement('html', '</div>');

        $mform->addElement('html', '
        <div class="upload-actions">
            <button type="submit" class="btn-upload-resource">
                <i class="fa fa-upload"></i>
                Upload Resource
            </button>
        ');

        $mform->addElement('html', '
        <button type="reset" class="btn-cancel-resource" onclick="return confirm(\'Clear all entered data?\')">
            <i class="fa fa-times"></i>
            Clear Form
        </button>
        ');

        echo '</div>';

        $mform->addElement('html', '
        <style>
            .error-border input, .error-border textarea {
                border: 2px solid #da4453 !important;
                background-color: #fff8f8 !important;
            }
            .error-msg {
                color: #da4453;
                font-size: 12px;
                margin-top: 4px;
                display: block;
            }
        </style>

        <script>
        (function() {
            var rules = {
                title: { check: function(v) { return v.trim().length >= 3; }, msg: "Title must be at least 3 characters" },
                description: { check: function(v) { return v.trim().length >= 10; }, msg: "Description must be at least 10 characters" },
                keywords: { check: function(v) { return v.trim().length > 0; }, msg: "Keywords are required" }
            };

            function validateField(id) {
                var input = document.getElementById("id_" + id);
                if (!input) return;
                var rule = rules[id];
                if (!rule) return;
                var isValid = rule.check(input.value);
                var fitem = input.closest(".fitem");
                if (fitem) {
                    var oldMsg = fitem.querySelector(".error-msg");
                    if (!isValid) {
                        fitem.classList.add("error-border");
                        if (!oldMsg) {
                            var msg = document.createElement("div");
                            msg.className = "error-msg";
                            msg.innerText = rule.msg;
                            input.closest(".felement").appendChild(msg);
                        }
                    } else {
                        fitem.classList.remove("error-border");
                        if (oldMsg) oldMsg.remove();
                    }
                }
            }

            document.addEventListener("DOMContentLoaded", function() {
                for (var id in rules) {
                    var el = document.getElementById("id_" + id);
                    if (el) {
                        el.addEventListener("input", function(id) { return function() { validateField(id); }; }(id));
                        el.addEventListener("blur", function(id) { return function() { validateField(id); }; }(id));
                    }
                }
            });
        })();
        </script>
        ');
    }

    private function open_section($mform, $name, $icon, $title) {
        $extraclass = '';
        if ($name === 'educationalinfo') {
            $extraclass = ' educational-single-column';
        }
        $mform->addElement('html', '
        <div class="upload-section">
            <div class="upload-section-header">
                <h3><i class="fa '.$icon.'"></i> '.$title.'</h3>
            </div>
            <div class="upload-section-body">
                <div class="upload-fields-grid'.$extraclass.'">
        ');
    }

    private function close_section($mform) {
        $mform->addElement('html', '
                </div>
            </div>
        </div>
        ');
    }

    private function add_field_2col($mform, $type, $name, $label, $options = null) {
        $fullwidth = [
            'description',
            'metadata_accessibility',
            'copyright',
            'relation'
        ];
        $extraclass = in_array($name, $fullwidth) ? 'field-cold field-full' : 'field-col';
        $mform->addElement('html', '<div class="'.$extraclass.'">');
        $mform->addElement('html', '<div class="field-label">'.$label.'</div>');
        $mform->addElement('html', '<div class="field-control">');

        if ($type === 'text') {
            $mform->addElement('text', $name, '');
            $mform->setType($name, PARAM_TEXT);
        } elseif ($type === 'textarea') {
            $mform->addElement('textarea', $name, '', $options ?? ['rows' => 4]);
            $mform->setType($name, PARAM_TEXT);
        } elseif ($type === 'select') {
            $mform->addElement('select', $name, '', $options);
        } elseif ($type === 'date_selector') {
            $mform->addElement('date_selector', $name, '');
        }

        $mform->addElement('html', '</div></div>');
    }

    public function validation($data, $files) {
        $errors = [];
        if (strlen(trim($data['title'])) < 3) {
            $errors['title'] = 'Title must contain at least 3 characters.';
        }
        if (!empty($data['description']) && strlen(trim($data['description'])) < 10) {
            $errors['description'] = 'Description must contain at least 10 characters.';
        }
        return $errors;
    }
}